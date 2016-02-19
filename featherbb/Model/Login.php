<?php

/**
 * Copyright (C) 2015-2016 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace FeatherBB\Model;

use FeatherBB\Core\Database as DB;
use FeatherBB\Core\Error;
use FeatherBB\Core\Random;
use FeatherBB\Core\Track;
use FeatherBB\Core\Url;
use FeatherBB\Core\Utils;

class Login
{
    public function __construct()
    {
        $this->auth = new \FeatherBB\Model\Auth();
    }

    public function login()
    {
        Container::get('hooks')->fire('model.login.login_start');

        $form_username = Utils::trim(Input::post('req_username'));
        $form_password = Utils::trim(Input::post('req_password'));
        $save_pass = Input::post('save_pass');

        $user = DB::for_table('users')->where('username', $form_username);

        $user = Container::get('hooks')->fireDB('model.login.find_user_login', $user);

        $user = $user->find_one();

        $authorized = false;

        if (!empty($user->password)) {
            $form_password_hash = Random::hash($form_password); // Will result in a SHA-1 hash
            $authorized = ($user->password == $form_password_hash);
        }

        $authorized = Container::get('hooks')->fire('model.login.authorized_login', $authorized);

        if (!$authorized) {
            throw new Error(__('Wrong user/pass').' <a href="'.Router::pathFor('resetPassword').'">'.__('Forgotten pass').'</a>', 403);
        }

        // Update the status if this is the first time the user logged in
        if ($user->group_id == ForumEnv::get('FEATHER_UNVERIFIED')) {
            $update_usergroup = DB::for_table('users')->where('id', $user->id)
                ->find_one()
                ->set('group_id', ForumSettings::get('o_default_user_group'));
            $update_usergroup = Container::get('hooks')->fireDB('model.login.update_usergroup_login', $update_usergroup);
            $update_usergroup = $update_usergroup->save();

            // Regenerate the users info cache
            if (!Container::get('cache')->isCached('users_info')) {
                Container::get('cache')->store('users_info', Cache::get_users_info());
            }

            $stats = Container::get('cache')->retrieve('users_info');
        }

        // Remove this user's guest entry from the online list
        $delete_online = DB::for_table('online')->where('ident', Utils::getIp());
        $delete_online = Container::get('hooks')->fireDB('model.login.delete_online_login', $delete_online);
        $delete_online = $delete_online->delete_many();

        $expire = ($save_pass == '1') ? time() + 1209600 : time() + ForumSettings::get('o_timeout_visit');
        $expire = Container::get('hooks')->fire('model.login.expire_login', $expire);
        $this->auth->feather_setcookie($user->id, $form_password_hash, $expire);

        // Reset tracked topics
        Track:: set_tracked_topics(null);

        // Try to determine if the data in redirect_url is valid (if not, we redirect to index.php after login)
        $redirect_url = Input::post('redirect_url');
        $redirect_url = Container::get('hooks')->fire('model.login.redirect_url_login', $redirect_url);

        return Router::redirect(Utils::escape($redirect_url), __('Login redirect'));
    }

    public function logout($id, $token)
    {
        $token = Container::get('hooks')->fire('model.login.logout_start', $token, $id);

        if (Container::get('user')->is_guest || !isset($id) || $id != Container::get('user')->id || !isset($token) || $token != Random::hash(Container::get('user')->id.Random::hash(Utils::getIp()))) {
            return Router::redirect(Router::pathFor('home'));
        }

        // Remove user from "users online" list
        $delete_online = DB::for_table('online')->where('user_id', Container::get('user')->id);
        $delete_online = Container::get('hooks')->fireDB('model.login.delete_online_logout', $delete_online);
        $delete_online = $delete_online->delete_many();

        // Update last_visit (make sure there's something to update it with)
        if (isset(Container::get('user')->logged)) {
            $update_last_visit = DB::for_table('users')->where('id', Container::get('user')->id)
                ->find_one()
                ->set('last_visit', Container::get('user')->logged);
            $update_last_visit = Container::get('hooks')->fireDB('model.login.update_online_logout', $update_last_visit);
            $update_last_visit = $update_last_visit->save();
        }

        Container::get('hooks')->fire('model.login.logout_end');

        $this->auth->feather_setcookie(1, Random::hash(uniqid(rand(), true)), time() + 31536000);

        return Router::redirect(Router::pathFor('home'), __('Logout redirect'));
    }

    public function password_forgotten()
    {
        Container::get('hooks')->fire('model.login.password_forgotten_start');

        if (!Container::get('user')->is_guest) {
            return Router::redirect(Router::pathFor('home'));
        }
        // Start with a clean slate
        $errors = array();

        if (Request::isPost()) {
            // Validate the email address
            $email = strtolower(Utils::trim(Input::post('req_email')));
            if (!Container::get('email')->is_valid_email($email)) {
                $errors[] = __('Invalid email');
            }

            // Did everything go according to plan?
            if (empty($errors)) {
                $result['select'] = array('id', 'username', 'last_email_sent');

                $result = DB::for_table('users')
                    ->select_many($result['select'])
                    ->where('email', $email);
                $result = Container::get('hooks')->fireDB('model.login.password_forgotten_query', $result);
                $result = $result->find_many();

                if ($result) {
                    // Load the "activate password" template
                    $mail_tpl = trim(file_get_contents(ForumEnv::get('FEATHER_ROOT').'featherbb/lang/'.Container::get('user')->language.'/mail_templates/activate_password.tpl'));
                    $mail_tpl = Container::get('hooks')->fire('model.login.mail_tpl_password_forgotten', $mail_tpl);

                    // The first row contains the subject
                    $first_crlf = strpos($mail_tpl, "\n");
                    $mail_subject = trim(substr($mail_tpl, 8, $first_crlf-8));
                    $mail_message = trim(substr($mail_tpl, $first_crlf));

                    // Do the generic replacements first (they apply to all emails sent out here)
                    $mail_message = str_replace('<base_url>', Url::base().'/', $mail_message);
                    $mail_message = str_replace('<board_mailer>', ForumSettings::get('o_board_title'), $mail_message);

                    $mail_message = Container::get('hooks')->fire('model.login.mail_message_password_forgotten', $mail_message);

                    // Loop through users we found
                    foreach($result as $cur_hit) {
                        if ($cur_hit->last_email_sent != '' && (time() - $cur_hit->last_email_sent) < 3600 && (time() - $cur_hit->last_email_sent) >= 0) {
                            throw new Error(sprintf(__('Email flood'), intval((3600 - (time() - $cur_hit->last_email_sent)) / 60)), 429);
                        }

                        // Generate a new password and a new password activation code
                        $new_password = Random::pass(12);
                        $new_password_key = Random::pass(8);

                        $query['update'] = array(
                            'activate_string' => Random::hash($new_password),
                            'activate_key'    => $new_password_key,
                            'last_email_sent' => time()
                        );

                        $query = DB::for_table('users')
                                    ->where('id', $cur_hit->id)
                                    ->find_one()
                                    ->set($query['update']);
                        $query = Container::get('hooks')->fireDB('model.login.password_forgotten_mail_query', $query);
                        $query = $query->save();

                        // Do the user specific replacements to the template
                        $cur_mail_message = str_replace('<username>', $cur_hit->username, $mail_message);
                        $cur_mail_message = str_replace('<activation_url>', Router::pathFor('profileAction', ['id' => $cur_hit->id, 'action' => 'change_pass']).'?key='.$new_password_key, $cur_mail_message);
                        $cur_mail_message = str_replace('<new_password>', $new_password, $cur_mail_message);
                        $cur_mail_message = Container::get('hooks')->fire('model.login.cur_mail_message_password_forgotten', $cur_mail_message);

                        Container::get('email')->feather_mail($email, $mail_subject, $cur_mail_message);
                    }

                    throw new Error(__('Forget mail').' <a href="mailto:'.Utils::escape(ForumSettings::get('o_admin_email')).'">'.Utils::escape(ForumSettings::get('o_admin_email')).'</a>.', 400);
                } else {
                    $errors[] = __('No email match').' '.Utils::escape($email).'.';
                }
            }
        }

        $errors = Container::get('hooks')->fire('model.login.password_forgotten', $errors);

        return $errors;
    }

    public function get_redirect_url()
    {
        Container::get('hooks')->fire('model.login.get_redirect_url_start');

        if (!empty($this->request->getReferrer())) {
            $redirect_url = $this->request->getReferrer();
        }

        if (!isset($redirect_url)) {
            $redirect_url = Url::base();
        } elseif (preg_match('%Topic\.php\?pid=(\d+)$%', $redirect_url, $matches)) { // TODO
            $redirect_url .= '#p'.$matches[1];
        }

        $redirect_url = Container::get('hooks')->fire('model.login.get_redirect_url', $redirect_url);

        return $redirect_url;
    }

    // TODO: This function was in Misc controller
    public function get_redirect_url2($recipient_id)
    {
        $recipient_id = Container::get('hooks')->fire('model.login.get_redirect_url_start', $recipient_id);

        // Try to determine if the data in HTTP_REFERER is valid (if not, we redirect to the user's profile after the email is sent)
        // TODO
        if ($this->request->getReferrer()) {
            $redirect_url = validate_redirect($this->request->getReferrer(), null);
        }

        if (!isset($redirect_url)) {
            $redirect_url = Router::pathFor('userProfile', ['id' => $recipient_id]);
        } elseif (preg_match('%Topic\.php\?pid=(\d+)$%', $redirect_url, $matches)) {
            $redirect_url .= '#p'.$matches[1];
        }

        $redirect_url = Container::get('hooks')->fire('model.login.get_redirect_url', $redirect_url);

        return $redirect_url;
    }

}
