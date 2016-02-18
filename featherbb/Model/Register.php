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
use FeatherBB\Core\Url;
use FeatherBB\Core\Utils;

class Register
{
    public function __construct()
    {
        $this->feather = \Slim\Slim::getInstance();
        $this->start = $this->feather->start;
        $this->config = $this->feather->config;
        $this->user = Container::get('user');
        $this->request = $this->feather->request;
        $this->hook = $this->feather->hooks;
        $this->email = $this->feather->email;
        $this->auth = new \FeatherBB\Model\Auth();
    }

    public function check_for_errors()
    {
        global $lang_antispam, $lang_antispam_questions;

        $user = array();
        $user['errors'] = '';

        $user = $this->hook->fire('model.register.check_for_errors_start', $user);

        // Check that someone from this IP didn't register a user within the last hour (DoS prevention)
        $already_registered = DB::for_table('users')
                                  ->where('registration_ip', $this->request->getIp())
                                  ->where_gt('registered', time() - 3600);
        $already_registered = $this->hook->fireDB('model.register.check_for_errors_ip_query', $already_registered);
        $already_registered = $already_registered->find_one();

        if ($already_registered) {
            throw new Error(__('Registration flood'), 429);
        }


        $user['username'] = Utils::trim($this->request->post('req_user'));
        $user['email1'] = strtolower(Utils::trim($this->request->post('req_email1')));

        if ($this->config['o_regs_verify'] == '1') {
            $email2 = strtolower(Utils::trim($this->request->post('req_email2')));

            $user['password1'] = Random::pass(12);
            $password2 = $user['password1'];
        } else {
            $user['password1'] = Utils::trim($this->request->post('req_password1'));
            $password2 = Utils::trim($this->request->post('req_password2'));
        }

        // Validate username and passwords
        $profile = new \FeatherBB\Model\Profile();
        $user['errors'] = $profile->check_username($user['username'], $user['errors']);

        if (Utils::strlen($user['password1']) < 6) {
            $user['errors'][] = __('Pass too short');
        } elseif ($user['password1'] != $password2) {
            $user['errors'][] = __('Pass not match');
        }

        // Antispam feature
        require $this->feather->forum_env['FEATHER_ROOT'].'featherbb/lang/'.$this->user->language.'/antispam.php';
        $question = $this->request->post('captcha_q') ? trim($this->request->post('captcha_q')) : '';
        $answer = $this->request->post('captcha') ? strtoupper(trim($this->request->post('captcha'))) : '';
        $lang_antispam_questions_array = array();

        foreach ($lang_antispam_questions as $k => $v) {
            $lang_antispam_questions_array[md5($k)] = strtoupper($v);
        }
        if (empty($lang_antispam_questions_array[$question]) || $lang_antispam_questions_array[$question] != $answer) {
            $user['errors'][] = __('Robot test fail');
        }

        // Validate email
        if (!$this->email->is_valid_email($user['email1'])) {
            $user['errors'][] = __('Invalid email');
        } elseif ($this->config['o_regs_verify'] == '1' && $user['email1'] != $email2) {
            $user['errors'][] = __('Email not match');
        }

        // Check if it's a banned email address
        if ($this->email->is_banned_email($user['email1'])) {
            if ($this->config['p_allow_banned_email'] == '0') {
                $user['errors'][] = __('Banned email');
            }
            $user['banned_email'] = 1; // Used later when we send an alert email
        }

        // Check if someone else already has registered with that email address
        $dupe_list = array();

        $dupe_mail = DB::for_table('users')
                        ->select('username')
                        ->where('email', $user['email1']);
        $dupe_mail = $this->hook->fireDB('model.register.check_for_errors_dupe', $dupe_mail);
        $dupe_mail = $dupe_mail->find_many();

        if ($dupe_mail) {
            if ($this->config['p_allow_dupe_email'] == '0') {
                $user['errors'][] = __('Dupe email');
            }

            foreach($dupe_mail as $cur_dupe) {
                $dupe_list[] = $cur_dupe['username'];
            }
        }

        // Make sure we got a valid language string
        if ($this->request->post('language')) {
            $user['language'] = preg_replace('%[\.\\\/]%', '', $this->request->post('language'));
            if (!file_exists($this->feather->forum_env['FEATHER_ROOT'].'featherbb/lang/'.$user['language'].'/common.po')) {
                throw new Error(__('Bad request'), 500);
            }
        } else {
            $user['language'] = $this->config['o_default_lang'];
        }

        $user = $this->hook->fire('model.register.check_for_errors', $user);

        return $user;
    }

    public function insert_user($user)
    {
        $user = $this->hook->fire('model.register.insert_user_start', $user);

        // Insert the new user into the database. We do this now to get the last inserted ID for later use
        $now = time();

        $intial_group_id = ($this->config['o_regs_verify'] == '0') ? $this->config['o_default_user_group'] : $this->feather->forum_env['FEATHER_UNVERIFIED'];
        $password_hash = Random::hash($user['password1']);

        // Add the user
        $user['insert'] = array(
            'username'        => $user['username'],
            'group_id'        => $intial_group_id,
            'password'        => $password_hash,
            'email'           => $user['email1'],
            'email_setting'   => $this->config['o_default_email_setting'],
            'timezone'        => $this->config['o_default_timezone'],
            'dst'             => 0,
            'language'        => $user['language'],
            'style'           => $this->config['o_default_style'],
            'registered'      => $now,
            'registration_ip' => $this->request->getIp(),
            'last_visit'      => $now,
        );

        $user = DB::for_table('users')
                    ->create()
                    ->set($user['insert']);
        $user = $this->hook->fireDB('model.register.insert_user_query', $user);
        $user = $user->save();

        $new_uid = DB::get_db()->lastInsertId($this->feather->forum_settings['db_prefix'].'users');


        if ($this->config['o_regs_verify'] == '0') {
            // Regenerate the users info cache
            if (!$this->feather->cache->isCached('users_info')) {
                $this->feather->cache->store('users_info', Cache::get_users_info());
            }

            $stats = $this->feather->cache->retrieve('users_info');
        }

        // If the mailing list isn't empty, we may need to send out some alerts
        if ($this->config['o_mailing_list'] != '') {
            // If we previously found out that the email was banned
            if (isset($user['banned_email'])) {
                // Load the "banned email register" template
                $mail_tpl = trim(file_get_contents($this->feather->forum_env['FEATHER_ROOT'].'featherbb/lang/'.$this->user->language.'/mail_templates/banned_email_register.tpl'));
                $mail_tpl = $this->hook->fire('model.register.insert_user_banned_mail_tpl', $mail_tpl);

                // The first row contains the subject
                $first_crlf = strpos($mail_tpl, "\n");
                $mail_subject = trim(substr($mail_tpl, 8, $first_crlf-8));
                $mail_subject = $this->hook->fire('model.register.insert_user_banned_mail_subject', $mail_subject);

                $mail_message = trim(substr($mail_tpl, $first_crlf));
                $mail_message = str_replace('<username>', $user['username'], $mail_message);
                $mail_message = str_replace('<email>', $user['email1'], $mail_message);
                $mail_message = str_replace('<profile_url>', $this->feather->urlFor('userProfile', ['id' => $new_uid]), $mail_message);
                $mail_message = str_replace('<board_mailer>', $this->config['o_board_title'], $mail_message);
                $mail_message = $this->hook->fire('model.register.insert_user_banned_mail_message', $mail_message);

                $this->email->feather_mail($this->config['o_mailing_list'], $mail_subject, $mail_message);
            }

            // If we previously found out that the email was a dupe
            if (!empty($dupe_list)) {
                // Load the "dupe email register" template
                $mail_tpl = trim(file_get_contents($this->feather->forum_env['FEATHER_ROOT'].'featherbb/lang/'.$this->user->language.'/mail_templates/dupe_email_register.tpl'));
                $mail_tpl = $this->hook->fire('model.register.insert_user_dupe_mail_tpl', $mail_tpl);

                // The first row contains the subject
                $first_crlf = strpos($mail_tpl, "\n");
                $mail_subject = trim(substr($mail_tpl, 8, $first_crlf-8));
                $mail_subject = $this->hook->fire('model.register.insert_user_dupe_mail_subject', $mail_subject);

                $mail_message = trim(substr($mail_tpl, $first_crlf));
                $mail_message = str_replace('<username>', $user['username'], $mail_message);
                $mail_message = str_replace('<dupe_list>', implode(', ', $dupe_list), $mail_message);
                $mail_message = str_replace('<profile_url>', $this->feather->urlFor('userProfile', ['id' => $new_uid]), $mail_message);
                $mail_message = str_replace('<board_mailer>', $this->config['o_board_title'], $mail_message);
                $mail_message = $this->hook->fire('model.register.insert_user_dupe_mail_message', $mail_message);

                $this->email->feather_mail($this->config['o_mailing_list'], $mail_subject, $mail_message);
            }

            // Should we alert people on the admin mailing list that a new user has registered?
            if ($this->config['o_regs_report'] == '1') {
                // Load the "new user" template
                $mail_tpl = trim(file_get_contents($this->feather->forum_env['FEATHER_ROOT'].'featherbb/lang/'.$this->user->language.'/mail_templates/new_user.tpl'));
                $mail_tpl = $this->hook->fire('model.register.insert_user_new_mail_tpl', $mail_tpl);

                // The first row contains the subject
                $first_crlf = strpos($mail_tpl, "\n");
                $mail_subject = trim(substr($mail_tpl, 8, $first_crlf-8));
                $mail_subject = $this->hook->fire('model.register.insert_user_new_mail_subject', $mail_subject);

                $mail_message = trim(substr($mail_tpl, $first_crlf));
                $mail_message = str_replace('<username>', $user['username'], $mail_message);
                $mail_message = str_replace('<base_url>', $this->feather->urlFor('home'), $mail_message);
                $mail_message = str_replace('<profile_url>', $this->feather->urlFor('userProfile', ['id' => $new_uid]), $mail_message);
                $mail_message = str_replace('<admin_url>', $this->feather->urlFor('profileSection', ['id' => $new_uid, 'section' => 'admin']), $mail_message);
                $mail_message = str_replace('<board_mailer>', $this->config['o_board_title'], $mail_message);
                $mail_message = $this->hook->fire('model.register.insert_user_new_mail_message', $mail_message);

                $this->email->feather_mail($this->config['o_mailing_list'], $mail_subject, $mail_message);
            }
        }

        // Must the user verify the registration or do we log him/her in right now?
        if ($this->config['o_regs_verify'] == '1') {
            // Load the "welcome" template
            $mail_tpl = trim(file_get_contents($this->feather->forum_env['FEATHER_ROOT'].'featherbb/lang/'.$this->user->language.'/mail_templates/welcome.tpl'));
            $mail_tpl = $this->hook->fire('model.register.insert_user_welcome_mail_tpl', $mail_tpl);

            // The first row contains the subject
            $first_crlf = strpos($mail_tpl, "\n");
            $mail_subject = trim(substr($mail_tpl, 8, $first_crlf-8));
            $mail_subject = $this->hook->fire('model.register.insert_user_welcome_mail_subject', $mail_subject);

            $mail_message = trim(substr($mail_tpl, $first_crlf));
            $mail_subject = str_replace('<board_title>', $this->config['o_board_title'], $mail_subject);
            $mail_message = str_replace('<base_url>', $this->feather->urlFor('home'), $mail_message);
            $mail_message = str_replace('<username>', $user['username'], $mail_message);
            $mail_message = str_replace('<password>', $user['password1'], $mail_message);
            $mail_message = str_replace('<login_url>', $this->feather->urlFor('login'), $mail_message);
            $mail_message = str_replace('<board_mailer>', $this->config['o_board_title'], $mail_message);
            $mail_message = $this->hook->fire('model.register.insert_user_welcome_mail_message', $mail_message);

            $this->email->feather_mail($user['email1'], $mail_subject, $mail_message);

            Router::redirect(Router::pathFor('home'), __('Reg email').' <a href="mailto:'.Utils::escape($this->config['o_admin_email']).'">'.Utils::escape($this->config['o_admin_email']).'</a>.');
        }

        $this->auth->feather_setcookie($new_uid, $password_hash, time() + $this->config['o_timeout_visit']);

        $this->hook->fire('model.register.insert_user');

        Router::redirect(Router::pathFor('home'), __('Reg complete'));
    }
}
