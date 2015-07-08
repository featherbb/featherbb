<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace model;

class register
{
    public function __construct()
    {
        $this->feather = \Slim\Slim::getInstance();
        $this->db = $this->feather->db;
        $this->start = $this->feather->start;
        $this->config = $this->feather->config;
        $this->user = $this->feather->user;
        $this->request = $this->feather->request;
    }
 
    public function check_for_errors($feather)
    {
        global $lang_register, $lang_prof_reg, $lang_common, $lang_antispam, $lang_antispam_questions;

        $user = array();
        $user['errors'] = '';

        // Check that someone from this IP didn't register a user within the last hour (DoS prevention)
        $result = $this->db->query('SELECT 1 FROM '.$this->db->prefix.'users WHERE registration_ip=\''.$this->db->escape(get_remote_address()).'\' AND registered>'.(time() - 3600)) or error('Unable to fetch user info', __FILE__, __LINE__, $this->db->error());

        if ($this->db->num_rows($result)) {
            message($lang_register['Registration flood']);
        }


        $user['username'] = feather_trim($this->request->post('req_user'));
        $user['email1'] = strtolower(feather_trim($this->request->post('req_email1')));

        if ($this->config['o_regs_verify'] == '1') {
            $email2 = strtolower(feather_trim($this->request->post('req_email2')));

            $user['password1'] = random_pass(12);
            $password2 = $user['password1'];
        } else {
            $user['password1'] = feather_trim($this->request->post('req_password1'));
            $password2 = feather_trim($this->request->post('req_password2'));
        }

        // Validate username and passwords
        $user['errors'] = check_username($user['username'], $user['errors']);

        if (feather_strlen($user['password1']) < 6) {
            $user['errors'][] = $lang_prof_reg['Pass too short'];
        } elseif ($user['password1'] != $password2) {
            $user['errors'][] = $lang_prof_reg['Pass not match'];
        }

        // Antispam feature
        $question = $this->request->post('captcha_q') ? trim($this->request->post('captcha_q')) : '';
        $answer = $this->request->post('captcha') ? strtoupper(trim($this->request->post('captcha'))) : '';
        $lang_antispam_questions_array = array();

        foreach ($lang_antispam_questions as $k => $v) {
            $lang_antispam_questions_array[md5($k)] = strtoupper($v);
        }
        if (empty($lang_antispam_questions_array[$question]) || $lang_antispam_questions_array[$question] != $answer) {
            $user['errors'][] = $lang_antispam['Robot test fail'];
        }

        // Validate email
        require FEATHER_ROOT.'include/email.php';

        if (!is_valid_email($user['email1'])) {
            $user['errors'][] = $lang_common['Invalid email'];
        } elseif ($this->config['o_regs_verify'] == '1' && $user['email1'] != $email2) {
            $user['errors'][] = $lang_register['Email not match'];
        }

        // Check if it's a banned email address
        if (is_banned_email($user['email1'])) {
            if ($this->config['p_allow_banned_email'] == '0') {
                $user['errors'][] = $lang_prof_reg['Banned email'];
            }
            $user['banned_email'] = 1; // Used later when we send an alert email
        }

        // Check if someone else already has registered with that email address
        $dupe_list = array();

        $result = $this->db->query('SELECT username FROM '.$this->db->prefix.'users WHERE email=\''.$this->db->escape($user['email1']).'\'') or error('Unable to fetch user info', __FILE__, __LINE__, $this->db->error());
        if ($this->db->num_rows($result)) {
            if ($this->config['p_allow_dupe_email'] == '0') {
                $user['errors'][] = $lang_prof_reg['Dupe email'];
            }

            while ($cur_dupe = $this->db->fetch_assoc($result)) {
                $dupe_list[] = $cur_dupe['username'];
            }
        }

        // Make sure we got a valid language string
        if ($this->request->post('language')) {
            $user['language'] = preg_replace('%[\.\\\/]%', '', $this->request->post('language'));
            if (!file_exists(FEATHER_ROOT.'lang/'.$user['language'].'/common.php')) {
                message($lang_common['Bad request'], false, '404 Not Found');
            }
        } else {
            $user['language'] = $this->config['o_default_lang'];
        }

        return $user;
    }

    public function insert_user($user)
    {
        global $lang_register;

        // Insert the new user into the database. We do this now to get the last inserted ID for later use
        $now = time();

        $intial_group_id = ($this->config['o_regs_verify'] == '0') ? $this->config['o_default_user_group'] : FEATHER_UNVERIFIED;
        $password_hash = feather_hash($user['password1']);

        // Add the user
        $this->db->query('INSERT INTO '.$this->db->prefix.'users (username, group_id, password, email, email_setting, timezone, dst, language, style, registered, registration_ip, last_visit) VALUES(\''.$this->db->escape($user['username']).'\', '.$intial_group_id.', \''.$password_hash.'\', \''.$this->db->escape($user['email1']).'\', '.$this->config['o_default_email_setting'].', '.$this->config['o_default_timezone'].' , 0, \''.$this->db->escape($user['language']).'\', \''.$this->config['o_default_style'].'\', '.$now.', \''.$this->db->escape(get_remote_address()).'\', '.$now.')') or error('Unable to create user', __FILE__, __LINE__, $this->db->error());
        $new_uid = $this->db->insert_id();

        if ($this->config['o_regs_verify'] == '0') {
            // Regenerate the users info cache
            if (!defined('FORUM_CACHE_FUNCTIONS_LOADED')) {
                require FEATHER_ROOT.'include/cache.php';
            }

            generate_users_info_cache();
        }

        // If the mailing list isn't empty, we may need to send out some alerts
        if ($this->config['o_mailing_list'] != '') {
            // If we previously found out that the email was banned
            if (isset($user['banned_email'])) {
                // Load the "banned email register" template
                $mail_tpl = trim(file_get_contents(FEATHER_ROOT.'lang/'.$this->user['language'].'/mail_templates/banned_email_register.tpl'));

                // The first row contains the subject
                $first_crlf = strpos($mail_tpl, "\n");
                $mail_subject = trim(substr($mail_tpl, 8, $first_crlf-8));
                $mail_message = trim(substr($mail_tpl, $first_crlf));

                $mail_message = str_replace('<username>', $user['username'], $mail_message);
                $mail_message = str_replace('<email>', $user['email1'], $mail_message);
                $mail_message = str_replace('<profile_url>', get_link('user/'.$new_uid.'/'), $mail_message);
                $mail_message = str_replace('<board_mailer>', $this->config['o_board_title'], $mail_message);

                pun_mail($this->config['o_mailing_list'], $mail_subject, $mail_message);
            }

            // If we previously found out that the email was a dupe
            if (!empty($dupe_list)) {
                // Load the "dupe email register" template
                $mail_tpl = trim(file_get_contents(FEATHER_ROOT.'lang/'.$this->user['language'].'/mail_templates/dupe_email_register.tpl'));

                // The first row contains the subject
                $first_crlf = strpos($mail_tpl, "\n");
                $mail_subject = trim(substr($mail_tpl, 8, $first_crlf-8));
                $mail_message = trim(substr($mail_tpl, $first_crlf));

                $mail_message = str_replace('<username>', $user['username'], $mail_message);
                $mail_message = str_replace('<dupe_list>', implode(', ', $dupe_list), $mail_message);
                $mail_message = str_replace('<profile_url>', get_link('user/'.$new_uid.'/'), $mail_message);
                $mail_message = str_replace('<board_mailer>', $this->config['o_board_title'], $mail_message);

                pun_mail($this->config['o_mailing_list'], $mail_subject, $mail_message);
            }

            // Should we alert people on the admin mailing list that a new user has registered?
            if ($this->config['o_regs_report'] == '1') {
                // Load the "new user" template
                $mail_tpl = trim(file_get_contents(FEATHER_ROOT.'lang/'.$this->user['language'].'/mail_templates/new_user.tpl'));

                // The first row contains the subject
                $first_crlf = strpos($mail_tpl, "\n");
                $mail_subject = trim(substr($mail_tpl, 8, $first_crlf-8));
                $mail_message = trim(substr($mail_tpl, $first_crlf));

                $mail_message = str_replace('<username>', $user['username'], $mail_message);
                $mail_message = str_replace('<base_url>', get_base_url().'/', $mail_message);
                $mail_message = str_replace('<profile_url>', get_link('user/'.$new_uid.'/'), $mail_message);
                $mail_message = str_replace('<admin_url>', get_link('user/'.$new_uid.'/section/admin/'), $mail_message);
                $mail_message = str_replace('<board_mailer>', $this->config['o_board_title'], $mail_message);

                pun_mail($this->config['o_mailing_list'], $mail_subject, $mail_message);
            }
        }

        // Must the user verify the registration or do we log him/her in right now?
        if ($this->config['o_regs_verify'] == '1') {
            // Load the "welcome" template
            $mail_tpl = trim(file_get_contents(FEATHER_ROOT.'lang/'.$this->user['language'].'/mail_templates/welcome.tpl'));

            // The first row contains the subject
            $first_crlf = strpos($mail_tpl, "\n");
            $mail_subject = trim(substr($mail_tpl, 8, $first_crlf-8));
            $mail_message = trim(substr($mail_tpl, $first_crlf));

            $mail_subject = str_replace('<board_title>', $this->config['o_board_title'], $mail_subject);
            $mail_message = str_replace('<base_url>', get_base_url().'/', $mail_message);
            $mail_message = str_replace('<username>', $user['username'], $mail_message);
            $mail_message = str_replace('<password>', $user['password1'], $mail_message);
            $mail_message = str_replace('<login_url>', get_base_url().'/login.php', $mail_message);
            $mail_message = str_replace('<board_mailer>', $this->config['o_board_title'], $mail_message);

            pun_mail($user['email1'], $mail_subject, $mail_message);

            message($lang_register['Reg email'].' <a href="mailto:'.feather_htmlspecialchars($this->config['o_admin_email']).'">'.feather_htmlspecialchars($this->config['o_admin_email']).'</a>.', true);
        }

        feather_setcookie($new_uid, $password_hash, time() + $this->config['o_timeout_visit']);

        redirect(get_base_url(), $lang_register['Reg complete']);
    }
}