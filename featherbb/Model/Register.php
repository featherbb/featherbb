<?php

/**
 * Copyright (C) 2015-2017 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace FeatherBB\Model;

use FeatherBB\Core\Database as DB;
use FeatherBB\Core\Error;
use FeatherBB\Core\Interfaces\Cache as CacheInterface;
use FeatherBB\Core\Interfaces\Container;
use FeatherBB\Core\Interfaces\ForumEnv;
use FeatherBB\Core\Interfaces\ForumSettings;
use FeatherBB\Core\Interfaces\Hooks;
use FeatherBB\Core\Interfaces\Input;
use FeatherBB\Core\Interfaces\Router;
use FeatherBB\Core\Interfaces\User;
use FeatherBB\Core\Random;
use FeatherBB\Core\Utils;
use FeatherBB\Model\Auth as AuthModel;

class Register
{
    public function checkErrors()
    {
        $user = [];
        $user['errors'] = [];

        $user = Hooks::fire('model.register.check_for_errors_start', $user);

        // Check that someone from this IP didn't register a user within the last hour (DoS prevention)
        $alreadyRegistered = DB::table('users')
                                  ->where('registration_ip', Utils::getIp())
                                  ->whereGt('registered', time() - 3600);
        $alreadyRegistered = Hooks::fireDB('model.register.check_for_errors_ip_query', $alreadyRegistered);
        $alreadyRegistered = $alreadyRegistered->findOne();

        if ($alreadyRegistered) {
            throw new Error(__('Registration flood'), 429);
        }


        $user['username'] = Utils::trim(Input::post('req_user'));
        $user['email1'] = strtolower(Utils::trim(Input::post('req_email1')));

        if (ForumSettings::get('o_regs_verify') == '1') {
            $email2 = strtolower(Utils::trim(Input::post('req_email2')));

            $user['password1'] = Random::pass(12);
            $password2 = $user['password1'];
        } else {
            $user['password1'] = Utils::trim(Input::post('req_password1'));
            $password2 = Utils::trim(Input::post('req_password2'));
        }

        // Validate username and passwords
        $profile = new \FeatherBB\Model\Profile();
        $user['errors'] = $profile->checkUsername($user['username'], $user['errors']);

        if (Utils::strlen($user['password1']) < 6) {
            $user['errors'][] = __('Pass too short');
        } elseif ($user['password1'] != $password2) {
            $user['errors'][] = __('Pass not match');
        }

        // Antispam feature
        $langAntispamQuestions = require ForumEnv::get('FEATHER_ROOT').'featherbb/lang/'.User::getPref('language').'/antispam.php';
        $question = Input::post('captcha_q') ? trim(Input::post('captcha_q')) : '';
        $answer = Input::post('captcha') ? strtoupper(trim(Input::post('captcha'))) : '';
        $langAntispamQuestionsArray = [];

        foreach ($langAntispamQuestions as $k => $v) {
            $langAntispamQuestionsArray[md5($k)] = strtoupper($v);
        }
        if (empty($langAntispamQuestionsArray[$question]) || $langAntispamQuestionsArray[$question] != $answer) {
            $user['errors'][] = __('Robot test fail');
        }

        // Validate email
        if (!Container::get('email')->isValidEmail($user['email1'])) {
            $user['errors'][] = __('Invalid email');
        } elseif (ForumSettings::get('o_regs_verify') == '1' && $user['email1'] != $email2) {
            $user['errors'][] = __('Email not match');
        }

        // Check if it's a banned email address
        if (Container::get('email')->isBannedEmail($user['email1'])) {
            if (ForumSettings::get('p_allow_banned_email') == '0') {
                $user['errors'][] = __('Banned email');
            }
            $user['banned_email'] = 1; // Used later when we send an alert email
        }

        // Check if someone else already has registered with that email address
        $dupeList = [];

        $dupeMail = DB::table('users')
                        ->select('username')
                        ->where('email', $user['email1']);
        $dupeMail = Hooks::fireDB('model.register.check_for_errors_dupe', $dupeMail);
        $dupeMail = $dupeMail->findMany();

        if ($dupeMail) {
            if (ForumSettings::get('p_allow_dupe_email') == '0') {
                $user['errors'][] = __('Dupe email');
            }

            foreach ($dupeMail as $curDupe) {
                $dupeList[] = $curDupe['username'];
            }
        }

        // Make sure we got a valid language string
        if (Input::post('language')) {
            $user['language'] = preg_replace('%[\.\\\/]%', '', Input::post('language'));
            if (!file_exists(ForumEnv::get('FEATHER_ROOT').'featherbb/lang/'.$user['language'].'/common.po')) {
                throw new Error(__('Bad request'), 500);
            }
        } else {
            $user['language'] = ForumSettings::get('language');
        }

        $user = Hooks::fire('model.register.check_for_errors', $user);

        return $user;
    }

    public function insertUser($user)
    {
        $user = Hooks::fire('model.register.insert_user_start', $user);

        // Insert the new user into the database. We do this now to get the last inserted ID for later use
        $now = time();

        $intialGroupId = (ForumSettings::get('o_regs_verify') == '0') ? ForumSettings::get('o_default_user_group') : ForumEnv::get('FEATHER_UNVERIFIED');
        $passwordHash = Utils::passwordHash($user['password1']);

        // Add the user
        $userData = [
            'username'        => $user['username'],
            'group_id'        => $intialGroupId,
            'password'        => $passwordHash,
            'email'           => $user['email1'],
            'registered'      => $now,
            'registration_ip' => Utils::getIp(),
            'last_visit'      => $now,
        ];

        $insertUser = DB::table('users')
                    ->create()
                    ->set($userData);
        $insertUser = Hooks::fireDB('model.register.insert_user_query', $insertUser);
        $insertUser = $insertUser->save();

        $newUid = DB::getDb()->lastInsertId(ForumSettings::get('db_prefix').'users');

        if (ForumSettings::get('o_regs_verify') == '1') {
            Container::get('prefs')->setUser($newUid, ['language' => $user['language']], ForumEnv::get('FEATHER_UNVERIFIED'));
        } else {
            Container::get('prefs')->setUser($newUid, ['language' => $user['language']]);
        }

        // If the mailing list isn't empty, we may need to send out some alerts
        if (ForumSettings::get('o_mailing_list') != '') {
            // If we previously found out that the email was banned
            if (isset($user['banned_email'])) {
                // Load the "banned email register" template
                $mailTpl = trim(file_get_contents(ForumEnv::get('FEATHER_ROOT').'featherbb/lang/'.ForumSettings::get('language').'/mail_templates/banned_email_register.tpl'));
                $mailTpl = Hooks::fire('model.register.insert_user_banned_mail_tpl', $mailTpl);

                // The first row contains the subject
                $firstCrlf = strpos($mailTpl, "\n");
                $mailSubject = trim(substr($mailTpl, 8, $firstCrlf-8));
                $mailSubject = Hooks::fire('model.register.insert_user_banned_mail_subject', $mailSubject);

                $mailMessage = trim(substr($mailTpl, $firstCrlf));
                $mailMessage = str_replace('<username>', $user['username'], $mailMessage);
                $mailMessage = str_replace('<email>', $user['email1'], $mailMessage);
                $mailMessage = str_replace('<profile_url>', Router::pathFor('userProfile', ['id' => $newUid]), $mailMessage);
                $mailMessage = str_replace('<board_mailer>', ForumSettings::get('o_board_title'), $mailMessage);
                $mailMessage = Hooks::fire('model.register.insert_user_banned_mail_message', $mailMessage);

                Container::get('email')->send(ForumSettings::get('o_mailing_list'), $mailSubject, $mailMessage);
            }

            // If we previously found out that the email was a dupe
            if (!empty($dupeList)) {
                // Load the "dupe email register" template
                $mailTpl = trim(file_get_contents(ForumEnv::get('FEATHER_ROOT').'featherbb/lang/'.ForumSettings::get('language').'/mail_templates/dupe_email_register.tpl'));
                $mailTpl = Hooks::fire('model.register.insert_user_dupe_mail_tpl', $mailTpl);

                // The first row contains the subject
                $firstCrlf = strpos($mailTpl, "\n");
                $mailSubject = trim(substr($mailTpl, 8, $firstCrlf-8));
                $mailSubject = Hooks::fire('model.register.insert_user_dupe_mail_subject', $mailSubject);

                $mailMessage = trim(substr($mailTpl, $firstCrlf));
                $mailMessage = str_replace('<username>', $user['username'], $mailMessage);
                $mailMessage = str_replace('<dupe_list>', implode(', ', $dupeList), $mailMessage);
                $mailMessage = str_replace('<profile_url>', Router::pathFor('userProfile', ['id' => $newUid]), $mailMessage);
                $mailMessage = str_replace('<board_mailer>', ForumSettings::get('o_board_title'), $mailMessage);
                $mailMessage = Hooks::fire('model.register.insert_user_dupe_mail_message', $mailMessage);

                Container::get('email')->send(ForumSettings::get('o_mailing_list'), $mailSubject, $mailMessage);
            }

            // Should we alert people on the admin mailing list that a new user has registered?
            if (ForumSettings::get('o_regs_report') == '1') {
                // Load the "new user" template
                $mailTpl = trim(file_get_contents(ForumEnv::get('FEATHER_ROOT').'featherbb/lang/'.ForumSettings::get('language').'/mail_templates/new_user.tpl'));
                $mailTpl = Hooks::fire('model.register.insert_user_new_mail_tpl', $mailTpl);

                // The first row contains the subject
                $firstCrlf = strpos($mailTpl, "\n");
                $mailSubject = trim(substr($mailTpl, 8, $firstCrlf-8));
                $mailSubject = Hooks::fire('model.register.insert_user_new_mail_subject', $mailSubject);

                $mailMessage = trim(substr($mailTpl, $firstCrlf));
                $mailMessage = str_replace('<username>', $user['username'], $mailMessage);
                $mailMessage = str_replace('<base_url>', Router::pathFor('home'), $mailMessage);
                $mailMessage = str_replace('<profile_url>', Router::pathFor('userProfile', ['id' => $newUid]), $mailMessage);
                $mailMessage = str_replace('<admin_url>', Router::pathFor('profileSection', ['id' => $newUid, 'section' => 'admin']), $mailMessage);
                $mailMessage = str_replace('<board_mailer>', ForumSettings::get('o_board_title'), $mailMessage);
                $mailMessage = Hooks::fire('model.register.insert_user_new_mail_message', $mailMessage);

                Container::get('email')->send(ForumSettings::get('o_mailing_list'), $mailSubject, $mailMessage);
            }
        }

        // Must the user verify the registration or do we log him/her in right now?
        if (ForumSettings::get('o_regs_verify') == '1') {
            // Load the "welcome" template
            $mailTpl = trim(file_get_contents(ForumEnv::get('FEATHER_ROOT').'featherbb/lang/'.$user['language'].'/mail_templates/welcome.tpl'));
            $mailTpl = Hooks::fire('model.register.insert_user_welcome_mail_tpl', $mailTpl);

            // The first row contains the subject
            $firstCrlf = strpos($mailTpl, "\n");
            $mailSubject = trim(substr($mailTpl, 8, $firstCrlf-8));
            $mailSubject = Hooks::fire('model.register.insert_user_welcome_mail_subject', $mailSubject);

            $mailMessage = trim(substr($mailTpl, $firstCrlf));
            $mailSubject = str_replace('<board_title>', ForumSettings::get('o_board_title'), $mailSubject);
            $mailMessage = str_replace('<base_url>', Router::pathFor('home'), $mailMessage);
            $mailMessage = str_replace('<username>', $user['username'], $mailMessage);
            $mailMessage = str_replace('<password>', $user['password1'], $mailMessage);
            $mailMessage = str_replace('<login_url>', Router::pathFor('login'), $mailMessage);
            $mailMessage = str_replace('<board_mailer>', ForumSettings::get('o_board_title'), $mailMessage);
            $mailMessage = Hooks::fire('model.register.insert_user_welcome_mail_message', $mailMessage);

            Container::get('email')->send($user['email1'], $mailSubject, $mailMessage);

            return Router::redirect(Router::pathFor('home'), __('Reg email').' <a href="mailto:'.Utils::escape(ForumSettings::get('o_admin_email')).'">'.Utils::escape(ForumSettings::get('o_admin_email')).'</a>.');
        } else {
            // Auto auth only if registrations verify is disabled
            $userObject = new \stdClass();
            $userObject->id = $newUid;
            $userObject->username = $user['username'];
            $expire = time() + ForumSettings::get('o_timeout_visit');
            $jwt = AuthModel::generateJwt($userObject, $expire);
            AuthModel::setCookie('Bearer '.$jwt, $expire);
        }

        // Refresh cache
        CacheInterface::store('users_info', Cache::getUsersInfo());

        Hooks::fire('model.register.insert_user');

        return Router::redirect(Router::pathFor('home'), __('Reg complete'));
    }
}
