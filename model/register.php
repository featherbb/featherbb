<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */
 
function check_for_errors($post_data, $user_field)
{
    global $db, $pun_user, $pun_config, $lang_register, $lang_prof_reg, $lang_common;
    
    $user = array();
    
    // Check that someone from this IP didn't register a user within the last hour (DoS prevention)
    $result = $db->query('SELECT 1 FROM '.$db->prefix.'users WHERE registration_ip=\''.$db->escape(get_remote_address()).'\' AND registered>'.(time() - 3600)) or error('Unable to fetch user info', __FILE__, __LINE__, $db->error());

    if ($db->num_rows($result)) {
        message($lang_register['Registration flood']);
    }


    $user['username'] = pun_trim($post_data[$user_field]);
    $user['email1'] = strtolower(pun_trim($post_data['req_email1']));

    if ($pun_config['o_regs_verify'] == '1') {
        $email2 = strtolower(pun_trim($post_data['req_email2']));

        $user['password1'] = random_pass(12);
        $password2 = $user['password1'];
    } else {
        $user['password1'] = pun_trim($post_data['req_password1']);
        $password2 = pun_trim($post_data['req_password2']);
    }

    // Validate username and passwords
    check_username($user['username']);

    if (pun_strlen($user['password1']) < 6) {
        $user['errors'][] = $lang_prof_reg['Pass too short'];
    } elseif ($user['password1'] != $password2) {
        $user['errors'][] = $lang_prof_reg['Pass not match'];
    }

    // Validate email
    require PUN_ROOT.'include/email.php';

    if (!is_valid_email($user['email1'])) {
        $user['errors'][] = $lang_common['Invalid email'];
    } elseif ($pun_config['o_regs_verify'] == '1' && $user['email1'] != $email2) {
        $user['errors'][] = $lang_register['Email not match'];
    }

    // Check if it's a banned email address
    if (is_banned_email($user['email1'])) {
        if ($pun_config['p_allow_banned_email'] == '0') {
            $user['errors'][] = $lang_prof_reg['Banned email'];
        }

        $user['banned_email'] = 1; // Used later when we send an alert email
    } else {
        $user['banned_email'] = 0;
    }

    // Check if someone else already has registered with that email address
    $dupe_list = array();

    $result = $db->query('SELECT username FROM '.$db->prefix.'users WHERE email=\''.$db->escape($user['email1']).'\'') or error('Unable to fetch user info', __FILE__, __LINE__, $db->error());
    if ($db->num_rows($result)) {
        if ($pun_config['p_allow_dupe_email'] == '0') {
            $user['errors'][] = $lang_prof_reg['Dupe email'];
        }

        while ($cur_dupe = $db->fetch_assoc($result)) {
            $dupe_list[] = $cur_dupe['username'];
        }
    }

    // Make sure we got a valid language string
    if (isset($post_data['language'])) {
        $user['language'] = preg_replace('%[\.\\\/]%', '', $post_data['language']);
        if (!file_exists(PUN_ROOT.'lang/'.$user['language'].'/common.php')) {
            message($lang_common['Bad request'], false, '404 Not Found');
        }
    } else {
        $user['language'] = $pun_config['o_default_lang'];
    }

    return $user;
}

function insert_user($user)
{
    global $db, $pun_user, $pun_config, $lang_register;
    
    // Insert the new user into the database. We do this now to get the last inserted ID for later use
    $now = time();

    $intial_group_id = ($pun_config['o_regs_verify'] == '0') ? $pun_config['o_default_user_group'] : PUN_UNVERIFIED;
    $password_hash = pun_hash($user['password1']);

    // Add the user
    $db->query('INSERT INTO '.$db->prefix.'users (username, group_id, password, email, email_setting, timezone, dst, language, style, registered, registration_ip, last_visit) VALUES(\''.$db->escape($user['username']).'\', '.$intial_group_id.', \''.$password_hash.'\', \''.$db->escape($user['email1']).'\', '.$pun_config['o_default_email_setting'].', '.$pun_config['o_default_timezone'].' , 0, \''.$db->escape($user['language']).'\', \''.$pun_config['o_default_style'].'\', '.$now.', \''.$db->escape(get_remote_address()).'\', '.$now.')') or error('Unable to create user', __FILE__, __LINE__, $db->error());
    $new_uid = $db->insert_id();

    if ($pun_config['o_regs_verify'] == '0') {
        // Regenerate the users info cache
        if (!defined('FORUM_CACHE_FUNCTIONS_LOADED')) {
            require PUN_ROOT.'include/cache.php';
        }

        generate_users_info_cache();
    }

    // If the mailing list isn't empty, we may need to send out some alerts
    if ($pun_config['o_mailing_list'] != '') {
        // If we previously found out that the email was banned
        if ($user['banned_email']) {
            // Load the "banned email register" template
            $mail_tpl = trim(file_get_contents(PUN_ROOT.'lang/'.$pun_user['language'].'/mail_templates/banned_email_register.tpl'));

            // The first row contains the subject
            $first_crlf = strpos($mail_tpl, "\n");
            $mail_subject = trim(substr($mail_tpl, 8, $first_crlf-8));
            $mail_message = trim(substr($mail_tpl, $first_crlf));

            $mail_message = str_replace('<username>', $user['username'], $mail_message);
            $mail_message = str_replace('<email>', $user['email1'], $mail_message);
            $mail_message = str_replace('<profile_url>', get_base_url().'/profile.php?id='.$new_uid, $mail_message);
            $mail_message = str_replace('<board_mailer>', $pun_config['o_board_title'], $mail_message);

            pun_mail($pun_config['o_mailing_list'], $mail_subject, $mail_message);
        }

        // If we previously found out that the email was a dupe
        if (!empty($dupe_list)) {
            // Load the "dupe email register" template
            $mail_tpl = trim(file_get_contents(PUN_ROOT.'lang/'.$pun_user['language'].'/mail_templates/dupe_email_register.tpl'));

            // The first row contains the subject
            $first_crlf = strpos($mail_tpl, "\n");
            $mail_subject = trim(substr($mail_tpl, 8, $first_crlf-8));
            $mail_message = trim(substr($mail_tpl, $first_crlf));

            $mail_message = str_replace('<username>', $user['username'], $mail_message);
            $mail_message = str_replace('<dupe_list>', implode(', ', $dupe_list), $mail_message);
            $mail_message = str_replace('<profile_url>', get_base_url().'/profile.php?id='.$new_uid, $mail_message);
            $mail_message = str_replace('<board_mailer>', $pun_config['o_board_title'], $mail_message);

            pun_mail($pun_config['o_mailing_list'], $mail_subject, $mail_message);
        }

        // Should we alert people on the admin mailing list that a new user has registered?
        if ($pun_config['o_regs_report'] == '1') {
            // Load the "new user" template
            $mail_tpl = trim(file_get_contents(PUN_ROOT.'lang/'.$pun_user['language'].'/mail_templates/new_user.tpl'));

            // The first row contains the subject
            $first_crlf = strpos($mail_tpl, "\n");
            $mail_subject = trim(substr($mail_tpl, 8, $first_crlf-8));
            $mail_message = trim(substr($mail_tpl, $first_crlf));

            $mail_message = str_replace('<username>', $user['username'], $mail_message);
            $mail_message = str_replace('<base_url>', get_base_url().'/', $mail_message);
            $mail_message = str_replace('<profile_url>', get_base_url().'/profile.php?id='.$new_uid, $mail_message);
            $mail_message = str_replace('<admin_url>', get_base_url().'/profile.php?section=admin&id='.$new_uid, $mail_message);
            $mail_message = str_replace('<board_mailer>', $pun_config['o_board_title'], $mail_message);

            pun_mail($pun_config['o_mailing_list'], $mail_subject, $mail_message);
        }
    }

    // Must the user verify the registration or do we log him/her in right now?
    if ($pun_config['o_regs_verify'] == '1') {
        // Load the "welcome" template
        $mail_tpl = trim(file_get_contents(PUN_ROOT.'lang/'.$pun_user['language'].'/mail_templates/welcome.tpl'));

        // The first row contains the subject
        $first_crlf = strpos($mail_tpl, "\n");
        $mail_subject = trim(substr($mail_tpl, 8, $first_crlf-8));
        $mail_message = trim(substr($mail_tpl, $first_crlf));

        $mail_subject = str_replace('<board_title>', $pun_config['o_board_title'], $mail_subject);
        $mail_message = str_replace('<base_url>', get_base_url().'/', $mail_message);
        $mail_message = str_replace('<username>', $user['username'], $mail_message);
        $mail_message = str_replace('<password>', $user['password1'], $mail_message);
        $mail_message = str_replace('<login_url>', get_base_url().'/login.php', $mail_message);
        $mail_message = str_replace('<board_mailer>', $pun_config['o_board_title'], $mail_message);

        pun_mail($user['email1'], $mail_subject, $mail_message);

        message($lang_register['Reg email'].' <a href="mailto:'.pun_htmlspecialchars($pun_config['o_admin_email']).'">'.pun_htmlspecialchars($pun_config['o_admin_email']).'</a>.', true);
    }

    pun_setcookie($new_uid, $password_hash, time() + $pun_config['o_timeout_visit']);

    redirect('index.php', $lang_register['Reg complete']);
}
