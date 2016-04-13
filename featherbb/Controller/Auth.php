<?php

/**
 * Copyright (C) 2015-2016 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace FeatherBB\Controller;

use FeatherBB\Core\Error;
use FeatherBB\Core\Random;
use FeatherBB\Core\Track;
use FeatherBB\Core\Url;
use FeatherBB\Core\Utils;
use FeatherBB\Model\Auth as ModelAuth;
use FeatherBB\Model\Cache;
use FeatherBB\Core\Database as DB;

class Auth
{
    public function __construct()
    {
        translate('profile');
        translate('login');
    }

    public function login($req, $res, $args)
    {
        if (!User::get()->is_guest) {
            return Router::redirect(Router::pathFor('home'), 'Already logged in');
        }

        if (Request::isPost()) {
            Container::get('hooks')->fire('controller.login');
            $form_username = Input::post('req_username');
            $form_password = Input::post('req_password');
            $save_pass = (bool)Input::post('save_pass');

            $user = ModelAuth::get_user_from_name($form_username);

            if ($user && !empty($user->password)) {
                // Convert old password to BCrypt if needed
                $old_password_hash = Random::hash($form_password);
                $password_to_convert = Utils::hash_equals($old_password_hash, $user->password);
                
                if (Utils::password_verify($form_password, $user->password) || $password_to_convert) {

                    if ($password_to_convert) {
                        ModelAuth::update_password($user->id, $form_password);
                        $user = ModelAuth::get_user_from_name($form_username);
                    }

                    if ($user->group_id == ForumEnv::get('FEATHER_UNVERIFIED')) {
                        ModelAuth::update_group($user->id, ForumSettings::get('o_default_user_group'));
                        if (!Container::get('cache')->isCached('users_info')) {
                            Container::get('cache')->store('users_info', Cache::get_users_info());
                        }
                    }

                    ModelAuth::delete_online_by_ip(Utils::getIp());
                    // Reset tracked topics
                    Track::set_tracked_topics(null);

                    $expire = ($save_pass) ? Container::get('now') + 1209600 : Container::get('now') + ForumSettings::get('o_timeout_visit');
                    $expire = Container::get('hooks')->fire('controller.expire_login', $expire);

                    $jwt = ModelAuth::generate_jwt($user, $expire);
                    ModelAuth::feather_setcookie('Bearer ' . $jwt, $expire);

                    return Router::redirect(Router::pathFor('home'), __('Login redirect'));
                }
                else {
                    throw new Error(__('Wrong user/pass').' <a href="'.Router::pathFor('resetPassword').'">'.__('Forgotten pass').'</a>', 403, true, true);
                }
            } else {
                throw new Error(__('Wrong user/pass').' <a href="'.Router::pathFor('resetPassword').'">'.__('Forgotten pass').'</a>', 403, true, true);
            }
        } else {
            View::setPageInfo(array(
                    'active_page' => 'login',
                    'title' => array(Utils::escape(ForumSettings::get('o_board_title')), __('Login')),
                )
            )->addTemplate('login/form.php')->display();
        }
    }

    public function logout($req, $res, $args)
    {
        $token = Container::get('hooks')->fire('controller.logout', $args['token']);

        if (User::get()->is_guest || !isset($token) || !Utils::hash_equals($token, Random::hash(User::get()->id.Random::hash(Utils::getIp())))) {
            return Router::redirect(Router::pathFor('home'), 'Not logged in');
        }

        ModelAuth::delete_online_by_id(User::get()->id);

        // Update last_visit (make sure there's something to update it with)
        if (isset(User::get()->logged)) {
            ModelAuth::set_last_visit(User::get()->id, User::get()->logged);
        }

        ModelAuth::feather_setcookie('Bearer ', 1);
        Container::get('hooks')->fire('controller.logout_end');

        return Router::redirect(Router::pathFor('home'), __('Logout redirect'));
    }

    public function forget($req, $res, $args)
    {
        // If the user is already logged in we shouldn't be here :)
        if (!User::get()->is_guest) {
            return Router::redirect(Router::pathFor('home'), 'Already logged in');
        }

        if (Request::isPost()) {
            // Validate the email address
            $email = strtolower(Utils::trim(Input::post('req_email')));
            if (!Container::get('email')->is_valid_email($email)) {
                throw new Error(__('Invalid email'), 400);
            }
            $user = ModelAuth::get_user_from_email($email);

            if ($user) {
                // Load the "activate password" template
                $mail_tpl = trim(file_get_contents(ForumEnv::get('FEATHER_ROOT').'featherbb/lang/'.User::get()->language.'/mail_templates/activate_password.tpl'));
                $mail_tpl = Container::get('hooks')->fire('controller.mail_tpl_password_forgotten', $mail_tpl);

                // The first row contains the subject
                $first_crlf = strpos($mail_tpl, "\n");
                $mail_subject = trim(substr($mail_tpl, 8, $first_crlf-8));
                $mail_message = trim(substr($mail_tpl, $first_crlf));

                // Do the generic replacements first (they apply to all emails sent out here)
                $mail_message = str_replace('<base_url>', Url::base().'/', $mail_message);
                $mail_message = str_replace('<board_mailer>', ForumSettings::get('o_board_title'), $mail_message);

                $mail_message = Container::get('hooks')->fire('controller.mail_message_password_forgotten', $mail_message);

                if ($user->last_email_sent != '' && (time() - $user->last_email_sent) < 3600 && (time() - $user->last_email_sent) >= 0) {
                    throw new Error(sprintf(__('Email flood'), intval((3600 - (time() - $user->last_email_sent)) / 60)), 429);
                }

                // Generate a new password and a new password activation code
                $new_password = Random::pass(12);
                $new_password_key = Random::pass(8);

                ModelAuth::set_new_password($new_password, $new_password_key, $user->id);

                // Do the user specific replacements to the template
                $cur_mail_message = str_replace('<username>', $user->username, $mail_message);
                $cur_mail_message = str_replace('<activation_url>', Router::pathFor('resetPassword', [], ['key' => $new_password_key, 'user_id' => $user->id]), $cur_mail_message);
                $cur_mail_message = str_replace('<new_password>', $new_password, $cur_mail_message);
                $cur_mail_message = Container::get('hooks')->fire('controller.cur_mail_message_password_forgotten', $cur_mail_message);

                Container::get('email')->feather_mail($email, $mail_subject, $cur_mail_message);

                return Router::redirect(Router::pathFor('home'), __('Forget mail').' <a href="mailto:'.Utils::escape(ForumSettings::get('o_admin_email')).'">'.Utils::escape(ForumSettings::get('o_admin_email')).'</a>.', 200, true, true);
            } else {
                throw new Error(__('No email match').' '.Utils::escape($email).'.', 400);
            }
        }

        if (Input::query('key') && Input::query('user_id')) {

            $key = Input::query('key');
            $key = Container::get('hooks')->fire('controller.auth.password_forgotten_key', $key);

            $id = Input::query('user_id');
            $id = Container::get('hooks')->fire('controller.auth.password_forgotten_user_id', $id);

            $cur_user = DB::for_table('users')
                ->where('id', $id);
            $cur_user = Container::get('hooks')->fireDB('controller.auth.password_forgotten_user_query', $cur_user);
            $cur_user = $cur_user->find_one();

            if ($key == '' || $key != $cur_user['activate_key']) {
                throw new Error(__('Pass key bad').' <a href="mailto:'.Utils::escape(ForumSettings::get('o_admin_email')).'">'.Utils::escape(ForumSettings::get('o_admin_email')).'</a>.', 400, true, true);
            } else {
                $query = DB::for_table('users')
                    ->where('id', $id)
                    ->find_one()
                    ->set('password', $cur_user['activate_string'])
                    ->set_expr('activate_string', 'NULL')
                    ->set_expr('activate_key', 'NULL');
                $query = Container::get('hooks')->fireDB('controller.auth.password_forgotten_activate_query', $query);
                $query = $query->save();

                return Router::redirect(Router::pathFor('home'), __('Pass updated'));
            }
        }

        View::setPageInfo(array(
//                'errors'    =>    $this->model->password_forgotten(),
                'active_page' => 'login',
                'title' => array(Utils::escape(ForumSettings::get('o_board_title')), __('Request pass'))
            )
        )->addTemplate('login/password_forgotten.php')->display();
    }
}
