<?php

/**
 * Copyright (C) 2015-2017 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace FeatherBB\Controller;

use FeatherBB\Core\Database as DB;
use FeatherBB\Core\Email;
use FeatherBB\Core\Error;
use FeatherBB\Core\Interfaces\Container;
use FeatherBB\Core\Interfaces\Cache as CacheInterface;
use FeatherBB\Core\Interfaces\ForumEnv;
use FeatherBB\Core\Interfaces\ForumSettings;
use FeatherBB\Core\Interfaces\Hooks;
use FeatherBB\Core\Interfaces\Input;
use FeatherBB\Core\Interfaces\Lang;
use FeatherBB\Core\Interfaces\Request;
use FeatherBB\Core\Interfaces\Router;
use FeatherBB\Core\Interfaces\User;
use FeatherBB\Core\Interfaces\View;
use FeatherBB\Core\Random;
use FeatherBB\Core\Track;
use FeatherBB\Core\Url;
use FeatherBB\Core\Utils;
use FeatherBB\Model\Auth as ModelAuth;
use FeatherBB\Model\Cache;

class Auth
{
    public function __construct()
    {
        Lang::load('profile');
        Lang::load('login');
    }

    public function login($req, $res, $args)
    {
        if (!User::get()->is_guest) {
            return Router::redirect(Router::pathFor('home'), 'Already logged in');
        }

        if (Request::isPost()) {
            Hooks::fire('controller.login');
            $formUsername = Input::post('req_username');
            $formPassword = Input::post('req_password');
            $savePass = (bool)Input::post('save_pass');

            $user = ModelAuth::getUserFromName($formUsername);

            if ($user && !empty($user->password)) {
                // Convert old password to BCrypt if needed
                $oldPasswordHash = Random::hash($formPassword);
                $passwordToConvert = Utils::hashEquals($oldPasswordHash, $user->password);
                
                if (Utils::passwordVerify($formPassword, $user->password) || $passwordToConvert) {
                    if ($passwordToConvert) {
                        ModelAuth::updatePassword($user->id, $formPassword);
                        $user = ModelAuth::getUserFromName($formUsername);
                    }

                    if ($user->group_id == ForumEnv::get('FEATHER_UNVERIFIED')) {
                        ModelAuth::updateGroup($user->id, ForumSettings::get('o_default_user_group'));
                        if (!CacheInterface::isCached('users_info')) {
                            CacheInterface::store('users_info', Cache::getUsersInfo());
                        }
                    }

                    ModelAuth::deleteOnlineByIP(Utils::getIp());
                    // Reset tracked topics
                    Track::setTrackedTopics(null);

                    $expire = ($savePass) ? Container::get('now') + 1209600 : Container::get('now') + ForumSettings::get('o_timeout_visit');
                    $expire = Hooks::fire('controller.expire_login', $expire);

                    $jwt = ModelAuth::generateJwt($user, $expire);
                    ModelAuth::setCookie('Bearer ' . $jwt, $expire);

                    return Router::redirect(Router::pathFor('home'), __('Login redirect'));
                } else {
                    throw new Error(__('Wrong user/pass').' <a href="'.Router::pathFor('resetPassword').'">'.__('Forgotten pass').'</a>', 403, true, true);
                }
            } else {
                throw new Error(__('Wrong user/pass').' <a href="'.Router::pathFor('resetPassword').'">'.__('Forgotten pass').'</a>', 403, true, true);
            }
        } else {
            View::setPageInfo([
                    'active_page' => 'login',
                    'title' => [Utils::escape(ForumSettings::get('o_board_title')), __('Login')],
                ]
            )->addTemplate('@forum/login/form')->display();
        }
    }

    public function logout($req, $res, $args)
    {
        $token = Hooks::fire('controller.logout', $args['token']);

        if (User::get()->is_guest || !isset($token) || !Utils::hashEquals($token, Random::hash(User::get()->id.Random::hash(Utils::getIp())))) {
            return Router::redirect(Router::pathFor('home'), 'Not logged in');
        }

        ModelAuth::deleteOnlineById(User::get()->id);

        // Update last_visit (make sure there's something to update it with)
        if (isset(User::get()->logged)) {
            ModelAuth::setLastVisit(User::get()->id, User::get()->logged);
        }

        ModelAuth::setCookie('Bearer ', 1);
        Hooks::fire('controller.logout_end');

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
            if (!Email::isValidEmail($email)) {
                throw new Error(__('Invalid email'), 400);
            }
            $user = ModelAuth::getUserFromEmail($email);

            if ($user) {
                // Load the "activate password" template
                $mailTpl = trim(file_get_contents(ForumEnv::get('FEATHER_ROOT').'featherbb/lang/'.User::getPref('language').'/mail_templates/activate_password.tpl'));
                $mailTpl = Hooks::fire('controller.mail_tpl_password_forgotten', $mailTpl);

                // The first row contains the subject
                $firstCrlf = strpos($mailTpl, "\n");
                $mailSubject = trim(substr($mailTpl, 8, $firstCrlf-8));
                $mailMessage = trim(substr($mailTpl, $firstCrlf));

                // Do the generic replacements first (they apply to all emails sent out here)
                $mailMessage = str_replace('<base_url>', Url::base().'/', $mailMessage);
                $mailMessage = str_replace('<board_mailer>', ForumSettings::get('o_board_title'), $mailMessage);

                $mailMessage = Hooks::fire('controller.mail_message_password_forgotten', $mailMessage);

                if ($user->last_email_sent != '' && (time() - $user->last_email_sent) < 3600 && (time() - $user->last_email_sent) >= 0) {
                    throw new Error(sprintf(__('Email flood'), intval((3600 - (time() - $user->last_email_sent)) / 60)), 429);
                }

                // Generate a new password and a new password activation code
                $newPassword = Random::pass(12);
                $newPasswordKey = Random::pass(8);

                ModelAuth::setNewPassword($newPassword, $newPasswordKey, $user->id);

                // Do the user specific replacements to the template
                $curMailMessage = str_replace('<username>', $user->username, $mailMessage);
                $curMailMessage = str_replace('<activation_url>', Router::pathFor('resetPassword', [], ['key' => $newPasswordKey, 'user_id' => $user->id]), $curMailMessage);
                $curMailMessage = str_replace('<new_password>', $newPassword, $curMailMessage);
                $curMailMessage = Hooks::fire('controller.cur_mail_message_password_forgotten', $curMailMessage);

                Email::send($email, $mailSubject, $curMailMessage);

                return Router::redirect(Router::pathFor('home'), __('Forget mail').' <a href="mailto:'.Utils::escape(ForumSettings::get('o_admin_email')).'">'.Utils::escape(ForumSettings::get('o_admin_email')).'</a>.', 200);
            } else {
                throw new Error(__('No email match').' '.Utils::escape($email).'.', 400);
            }
        }

        if (Input::query('key') && Input::query('user_id')) {
            $key = Input::query('key');
            $key = Hooks::fire('controller.auth.password_forgotten_key', $key);

            $id = Input::query('user_id');
            $id = Hooks::fire('controller.auth.password_forgotten_user_id', $id);

            $curUser = DB::table('users')
                ->where('id', $id);
            $curUser = Hooks::fireDB('controller.auth.password_forgotten_user_query', $curUser);
            $curUser = $curUser->findOne();

            if ($key == '' || $key != $curUser['activate_key']) {
                throw new Error(__('Pass key bad').' <a href="mailto:'.Utils::escape(ForumSettings::get('o_admin_email')).'">'.Utils::escape(ForumSettings::get('o_admin_email')).'</a>.', 400, true, true);
            } else {
                $query = DB::table('users')
                    ->where('id', $id)
                    ->findOne()
                    ->set('password', $curUser['activate_string'])
                    ->setExpr('activate_string', 'NULL')
                    ->setExpr('activate_key', 'NULL');
                $query = Hooks::fireDB('controller.auth.password_forgotten_activate_query', $query);
                $query = $query->save();

                return Router::redirect(Router::pathFor('home'), __('Pass updated'));
            }
        }

        View::setPageInfo([
                'active_page' => 'login',
                'title' => [Utils::escape(ForumSettings::get('o_board_title')), __('Request pass')]
            ]
        )->addTemplate('@forum/login/password_forgotten')->display();
    }
}
