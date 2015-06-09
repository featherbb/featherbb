<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

//  Get some info about the post
function get_info_post($tid, $fid)
{
    global $db, $pun_user, $lang_common;
    
    if ($tid) {
        $result = $db->query('SELECT f.id, f.forum_name, f.moderators, f.redirect_url, fp.post_replies, fp.post_topics, t.subject, t.closed, s.user_id AS is_subscribed FROM '.$db->prefix.'topics AS t INNER JOIN '.$db->prefix.'forums AS f ON f.id=t.forum_id LEFT JOIN '.$db->prefix.'forum_perms AS fp ON (fp.forum_id=f.id AND fp.group_id='.$pun_user['g_id'].') LEFT JOIN '.$db->prefix.'topic_subscriptions AS s ON (t.id=s.topic_id AND s.user_id='.$pun_user['id'].') WHERE (fp.read_forum IS NULL OR fp.read_forum=1) AND t.id='.$tid) or error('Unable to fetch forum info', __FILE__, __LINE__, $db->error());
    } else {
        $result = $db->query('SELECT f.id, f.forum_name, f.moderators, f.redirect_url, fp.post_replies, fp.post_topics FROM '.$db->prefix.'forums AS f LEFT JOIN '.$db->prefix.'forum_perms AS fp ON (fp.forum_id=f.id AND fp.group_id='.$pun_user['g_id'].') WHERE (fp.read_forum IS NULL OR fp.read_forum=1) AND f.id='.$fid) or error('Unable to fetch forum info', __FILE__, __LINE__, $db->error());
    }

    if (!$db->num_rows($result)) {
        message($lang_common['Bad request'], false, '404 Not Found');
    }

    $cur_posting = $db->fetch_assoc($result);
    
    return $cur_posting;
}

// Checks the post for errors before posting
function check_errors_before_post($fid, $post_data, $errors)
{
    global $db, $pun_user, $pun_config, $lang_post, $re_list, $smilies;
    
    // Flood protection
    if (!isset($post_data['preview']) && $pun_user['last_post'] != '' && (time() - $pun_user['last_post']) < $pun_user['g_post_flood']) {
        $errors[] = sprintf($lang_post['Flood start'], $pun_user['g_post_flood'], $pun_user['g_post_flood'] - (time() - $pun_user['last_post']));
    }

    // Make sure they got here from the site
    confirm_referrer(array('post.php', 'viewtopic.php'));

    // If it's a new topic
    if ($fid) {
        $subject = pun_trim($post_data['req_subject']);

        if ($pun_config['o_censoring'] == '1') {
            $censored_subject = pun_trim(censor_words($subject));
        }

        if ($subject == '') {
            $errors[] = $lang_post['No subject'];
        } elseif ($pun_config['o_censoring'] == '1' && $censored_subject == '') {
            $errors[] = $lang_post['No subject after censoring'];
        } elseif (pun_strlen($subject) > 70) {
            $errors[] = $lang_post['Too long subject'];
        } elseif ($pun_config['p_subject_all_caps'] == '0' && is_all_uppercase($subject) && !$pun_user['is_admmod']) {
            $errors[] = $lang_post['All caps subject'];
        }
    }

    // If the user is logged in we get the username and email from $pun_user
    if (!$pun_user['is_guest']) {
        $username = $pun_user['username'];
        $email = $pun_user['email'];
    }
    // Otherwise it should be in $post_data
    else {
        $username = pun_trim($post_data['req_username']);
        $email = strtolower(pun_trim(($pun_config['p_force_guest_email'] == '1') ? $post_data['req_email'] : $post_data['email']));
        $banned_email = false;

        // Load the register.php/prof_reg.php language files
        require PUN_ROOT.'lang/'.$pun_user['language'].'/prof_reg.php';
        require PUN_ROOT.'lang/'.$pun_user['language'].'/register.php';

        // It's a guest, so we have to validate the username
        check_username($username);

        if ($pun_config['p_force_guest_email'] == '1' || $email != '') {
            require PUN_ROOT.'include/email.php';
            if (!is_valid_email($email)) {
                $errors[] = $lang_common['Invalid email'];
            }

            // Check if it's a banned email address
            // we should only check guests because members' addresses are already verified
            if ($pun_user['is_guest'] && is_banned_email($email)) {
                if ($pun_config['p_allow_banned_email'] == '0') {
                    $errors[] = $lang_prof_reg['Banned email'];
                }

                $banned_email = true; // Used later when we send an alert email
            }
        }
    }

    // Clean up message from POST
    $orig_message = $message = pun_linebreaks(pun_trim($post_data['req_message']));

    // Here we use strlen() not pun_strlen() as we want to limit the post to PUN_MAX_POSTSIZE bytes, not characters
    if (strlen($message) > PUN_MAX_POSTSIZE) {
        $errors[] = sprintf($lang_post['Too long message'], forum_number_format(PUN_MAX_POSTSIZE));
    } elseif ($pun_config['p_message_all_caps'] == '0' && is_all_uppercase($message) && !$pun_user['is_admmod']) {
        $errors[] = $lang_post['All caps message'];
    }

    // Validate BBCode syntax
    if ($pun_config['p_message_bbcode'] == '1') {
        require PUN_ROOT.'include/parser.php';
        $message = preparse_bbcode($message, $errors);
    }

    if (empty($errors)) {
        if ($message == '') {
            $errors[] = $lang_post['No message'];
        } elseif ($pun_config['o_censoring'] == '1') {
            // Censor message to see if that causes problems
            $censored_message = pun_trim(censor_words($message));

            if ($censored_message == '') {
                $errors[] = $lang_post['No message after censoring'];
            }
        }
    }
    
    return $errors;
}

// If the previous check went OK, setup some variables used later
function setup_variables($post_data, $errors, $is_admmod)
{
    global $pun_user, $pun_config;
    
    $post = array();
    
    if (!$pun_user['is_guest']) {
        $post['username'] = $pun_user['username'];
        $post['email'] = $pun_user['email'];
    }
    // Otherwise it should be in $post_data
    else {
        $post['username'] = pun_trim($post_data['req_username']);
        $post['email'] = strtolower(pun_trim(($pun_config['p_force_guest_email'] == '1') ? $post_data['req_email'] : $post_data['email']));
    }
    
    $post['subject'] = pun_trim($post_data['req_subject']);
    
    $post['hide_smilies'] = isset($post_data['hide_smilies']) ? '1' : '0';
    $post['subscribe'] = isset($post_data['subscribe']) ? '1' : '0';
    $post['stick_topic'] = isset($post_data['stick_topic']) && $is_admmod ? '1' : '0';
    
    $post['message']  = pun_linebreaks(pun_trim($post_data['req_message']));
    
    // Validate BBCode syntax
    if ($pun_config['p_message_bbcode'] == '1') {
        require_once PUN_ROOT.'include/parser.php';
        $post['message']  = preparse_bbcode($post['message'], $errors);
    }

    // Replace four-byte characters (MySQL cannot handle them)
    $post['message'] = strip_bad_multibyte_chars($post['message']);

    $post['time'] = time();
    
    return $post;
}

// Insert a reply
function insert_reply($post, $tid, $cur_posting)
{
    global $db, $pun_user, $pun_config;
    
    if (!$pun_user['is_guest']) {
        $new_tid = $tid;

        // Insert the new post
        $db->query('INSERT INTO '.$db->prefix.'posts (poster, poster_id, poster_ip, message, hide_smilies, posted, topic_id) VALUES(\''.$db->escape($post['username']).'\', '.$pun_user['id'].', \''.$db->escape(get_remote_address()).'\', \''.$db->escape($post['message']).'\', '.$post['hide_smilies'].', '.$post['time'].', '.$tid.')') or error('Unable to create post', __FILE__, __LINE__, $db->error());
        $new_pid = $db->insert_id();

        // To subscribe or not to subscribe, that ...
        if ($pun_config['o_topic_subscriptions'] == '1') {
            if ($post['subscribe'] && !$is_subscribed) {
                $db->query('INSERT INTO '.$db->prefix.'topic_subscriptions (user_id, topic_id) VALUES('.$pun_user['id'].' ,'.$tid.')') or error('Unable to add subscription', __FILE__, __LINE__, $db->error());
            } elseif (!$post['subscribe'] && $is_subscribed) {
                $db->query('DELETE FROM '.$db->prefix.'topic_subscriptions WHERE user_id='.$pun_user['id'].' AND topic_id='.$tid) or error('Unable to remove subscription', __FILE__, __LINE__, $db->error());
            }
        }
    } else {
        // It's a guest. Insert the new post
        $email_sql = ($pun_config['p_force_guest_email'] == '1' || $post['email'] != '') ? '\''.$db->escape($post['email']).'\'' : 'NULL';
        $db->query('INSERT INTO '.$db->prefix.'posts (poster, poster_ip, poster_email, message, hide_smilies, posted, topic_id) VALUES(\''.$db->escape($post['username']).'\', \''.$db->escape(get_remote_address()).'\', '.$email_sql.', \''.$db->escape($post['message']).'\', '.$post['hide_smilies'].', '.$post['time'].', '.$tid.')') or error('Unable to create post', __FILE__, __LINE__, $db->error());
        $new_pid = $db->insert_id();
    }

    // Update topic
    $db->query('UPDATE '.$db->prefix.'topics SET num_replies=num_replies+1, last_post='.$post['time'].', last_post_id='.$new_pid.', last_poster=\''.$db->escape($post['username']).'\' WHERE id='.$tid) or error('Unable to update topic', __FILE__, __LINE__, $db->error());

    update_search_index('post', $new_pid, $post['message']);

    update_forum($cur_posting['id']);
    
    return $new_pid;
}

// Send notifications for replies
function send_notifications_reply($tid, $cur_posting)
{
    global $db, $pun_config, $pun_user;
    
    // Get the post time for the previous post in this topic
    $result = $db->query('SELECT posted FROM '.$db->prefix.'posts WHERE topic_id='.$tid.' ORDER BY id DESC LIMIT 1, 1') or error('Unable to fetch post info', __FILE__, __LINE__, $db->error());
    $previous_post_time = $db->result($result);

    // Get any subscribed users that should be notified (banned users are excluded)
    $result = $db->query('SELECT u.id, u.email, u.notify_with_post, u.language FROM '.$db->prefix.'users AS u INNER JOIN '.$db->prefix.'topic_subscriptions AS s ON u.id=s.user_id LEFT JOIN '.$db->prefix.'forum_perms AS fp ON (fp.forum_id='.$cur_posting['id'].' AND fp.group_id=u.group_id) LEFT JOIN '.$db->prefix.'online AS o ON u.id=o.user_id LEFT JOIN '.$db->prefix.'bans AS b ON u.username=b.username WHERE b.username IS NULL AND COALESCE(o.logged, u.last_visit)>'.$previous_post_time.' AND (fp.read_forum IS NULL OR fp.read_forum=1) AND s.topic_id='.$tid.' AND u.id!='.$pun_user['id']) or error('Unable to fetch subscription info', __FILE__, __LINE__, $db->error());
    if ($db->num_rows($result)) {
        require_once PUN_ROOT.'include/email.php';

        $notification_emails = array();

        if ($pun_config['o_censoring'] == '1') {
            $cleaned_message = bbcode2email($censored_message, -1);
        } else {
            $cleaned_message = bbcode2email($post['message'], -1);
        }

        // Loop through subscribed users and send emails
        while ($cur_subscriber = $db->fetch_assoc($result)) {
            // Is the subscription email for $cur_subscriber['language'] cached or not?
            if (!isset($notification_emails[$cur_subscriber['language']])) {
                if (file_exists(PUN_ROOT.'lang/'.$cur_subscriber['language'].'/mail_templates/new_reply.tpl')) {
                    // Load the "new reply" template
                    $mail_tpl = trim(file_get_contents(PUN_ROOT.'lang/'.$cur_subscriber['language'].'/mail_templates/new_reply.tpl'));

                    // Load the "new reply full" template (with post included)
                    $mail_tpl_full = trim(file_get_contents(PUN_ROOT.'lang/'.$cur_subscriber['language'].'/mail_templates/new_reply_full.tpl'));

                    // The first row contains the subject (it also starts with "Subject:")
                    $first_crlf = strpos($mail_tpl, "\n");
                    $mail_subject = trim(substr($mail_tpl, 8, $first_crlf-8));
                    $mail_message = trim(substr($mail_tpl, $first_crlf));

                    $first_crlf = strpos($mail_tpl_full, "\n");
                    $mail_subject_full = trim(substr($mail_tpl_full, 8, $first_crlf-8));
                    $mail_message_full = trim(substr($mail_tpl_full, $first_crlf));

                    $mail_subject = str_replace('<topic_subject>', $cur_posting['subject'], $mail_subject);
                    $mail_message = str_replace('<topic_subject>', $cur_posting['subject'], $mail_message);
                    $mail_message = str_replace('<replier>', $post['username'], $mail_message);
                    $mail_message = str_replace('<post_url>', get_base_url().'/viewtopic.php?pid='.$new_pid.'#p'.$new_pid, $mail_message);
                    $mail_message = str_replace('<unsubscribe_url>', get_base_url().'/misc.php?action=unsubscribe&tid='.$tid, $mail_message);
                    $mail_message = str_replace('<board_mailer>', $pun_config['o_board_title'], $mail_message);

                    $mail_subject_full = str_replace('<topic_subject>', $cur_posting['subject'], $mail_subject_full);
                    $mail_message_full = str_replace('<topic_subject>', $cur_posting['subject'], $mail_message_full);
                    $mail_message_full = str_replace('<replier>', $post['username'], $mail_message_full);
                    $mail_message_full = str_replace('<message>', $cleaned_message, $mail_message_full);
                    $mail_message_full = str_replace('<post_url>', get_base_url().'/viewtopic.php?pid='.$new_pid.'#p'.$new_pid, $mail_message_full);
                    $mail_message_full = str_replace('<unsubscribe_url>', get_base_url().'/misc.php?action=unsubscribe&tid='.$tid, $mail_message_full);
                    $mail_message_full = str_replace('<board_mailer>', $pun_config['o_board_title'], $mail_message_full);

                    $notification_emails[$cur_subscriber['language']][0] = $mail_subject;
                    $notification_emails[$cur_subscriber['language']][1] = $mail_message;
                    $notification_emails[$cur_subscriber['language']][2] = $mail_subject_full;
                    $notification_emails[$cur_subscriber['language']][3] = $mail_message_full;

                    $mail_subject = $mail_message = $mail_subject_full = $mail_message_full = null;
                }
            }

            // We have to double check here because the templates could be missing
            if (isset($notification_emails[$cur_subscriber['language']])) {
                if ($cur_subscriber['notify_with_post'] == '0') {
                    pun_mail($cur_subscriber['email'], $notification_emails[$cur_subscriber['language']][0], $notification_emails[$cur_subscriber['language']][1]);
                } else {
                    pun_mail($cur_subscriber['email'], $notification_emails[$cur_subscriber['language']][2], $notification_emails[$cur_subscriber['language']][3]);
                }
            }
        }

        unset($cleaned_message);
    }
}

// Insert a topic
function insert_topic($post, $fid)
{
    global $db, $pun_user, $pun_config;
    
    // Create the topic
    $db->query('INSERT INTO '.$db->prefix.'topics (poster, subject, posted, last_post, last_poster, sticky, forum_id) VALUES(\''.$db->escape($post['username']).'\', \''.$db->escape($post['subject']).'\', '.$post['time'].', '.$post['time'].', \''.$db->escape($post['username']).'\', '.$post['stick_topic'].', '.$fid.')') or error('Unable to create topic', __FILE__, __LINE__, $db->error());
    $new_tid = $db->insert_id();

    if (!$pun_user['is_guest']) {
        // To subscribe or not to subscribe, that ...
        if ($pun_config['o_topic_subscriptions'] == '1' && $post['subscribe']) {
            $db->query('INSERT INTO '.$db->prefix.'topic_subscriptions (user_id, topic_id) VALUES('.$pun_user['id'].' ,'.$new_tid.')') or error('Unable to add subscription', __FILE__, __LINE__, $db->error());
        }

        // Create the post ("topic post")
        $db->query('INSERT INTO '.$db->prefix.'posts (poster, poster_id, poster_ip, message, hide_smilies, posted, topic_id) VALUES(\''.$db->escape($post['username']).'\', '.$pun_user['id'].', \''.$db->escape(get_remote_address()).'\', \''.$db->escape($post['message']).'\', '.$post['hide_smilies'].', '.$post['time'].', '.$new_tid.')') or error('Unable to create post', __FILE__, __LINE__, $db->error());
    } else {
        // Create the post ("topic post")
        $email_sql = ($pun_config['p_force_guest_email'] == '1' || $post['email'] != '') ? '\''.$db->escape($post['email']).'\'' : 'NULL';
        $db->query('INSERT INTO '.$db->prefix.'posts (poster, poster_ip, poster_email, message, hide_smilies, posted, topic_id) VALUES(\''.$db->escape($post['username']).'\', \''.$db->escape(get_remote_address()).'\', '.$email_sql.', \''.$db->escape($post['message']).'\', '.$post['hide_smilies'].', '.$post['time'].', '.$new_tid.')') or error('Unable to create post', __FILE__, __LINE__, $db->error());
    }
    $new_pid = $db->insert_id();

    // Update the topic with last_post_id
    $db->query('UPDATE '.$db->prefix.'topics SET last_post_id='.$new_pid.', first_post_id='.$new_pid.' WHERE id='.$new_tid) or error('Unable to update topic', __FILE__, __LINE__, $db->error());

    update_search_index('post', $new_pid, $post['message'], $post['subject']);

    update_forum($fid);
    
    return $new_pid;
}

// Send notifications for new topics
function send_notifications_new_topic($post, $cur_posting)
{
    global $db, $pun_user;
    
    // Get any subscribed users that should be notified (banned users are excluded)
    $result = $db->query('SELECT u.id, u.email, u.notify_with_post, u.language FROM '.$db->prefix.'users AS u INNER JOIN '.$db->prefix.'forum_subscriptions AS s ON u.id=s.user_id LEFT JOIN '.$db->prefix.'forum_perms AS fp ON (fp.forum_id='.$cur_posting['id'].' AND fp.group_id=u.group_id) LEFT JOIN '.$db->prefix.'bans AS b ON u.username=b.username WHERE b.username IS NULL AND (fp.read_forum IS NULL OR fp.read_forum=1) AND s.forum_id='.$cur_posting['id'].' AND u.id!='.$pun_user['id']) or error('Unable to fetch subscription info', __FILE__, __LINE__, $db->error());
    if ($db->num_rows($result)) {
        require_once PUN_ROOT.'include/email.php';

        $notification_emails = array();

        if ($pun_config['o_censoring'] == '1') {
            $cleaned_message = bbcode2email($censored_message, -1);
        } else {
            $cleaned_message = bbcode2email($post['message'], -1);
        }

        // Loop through subscribed users and send emails
        while ($cur_subscriber = $db->fetch_assoc($result)) {
            // Is the subscription email for $cur_subscriber['language'] cached or not?
            if (!isset($notification_emails[$cur_subscriber['language']])) {
                if (file_exists(PUN_ROOT.'lang/'.$cur_subscriber['language'].'/mail_templates/new_topic.tpl')) {
                    // Load the "new topic" template
                    $mail_tpl = trim(file_get_contents(PUN_ROOT.'lang/'.$cur_subscriber['language'].'/mail_templates/new_topic.tpl'));

                    // Load the "new topic full" template (with post included)
                    $mail_tpl_full = trim(file_get_contents(PUN_ROOT.'lang/'.$cur_subscriber['language'].'/mail_templates/new_topic_full.tpl'));

                    // The first row contains the subject (it also starts with "Subject:")
                    $first_crlf = strpos($mail_tpl, "\n");
                    $mail_subject = trim(substr($mail_tpl, 8, $first_crlf-8));
                    $mail_message = trim(substr($mail_tpl, $first_crlf));

                    $first_crlf = strpos($mail_tpl_full, "\n");
                    $mail_subject_full = trim(substr($mail_tpl_full, 8, $first_crlf-8));
                    $mail_message_full = trim(substr($mail_tpl_full, $first_crlf));

                    $mail_subject = str_replace('<forum_name>', $cur_posting['forum_name'], $mail_subject);
                    $mail_message = str_replace('<topic_subject>', $pun_config['o_censoring'] == '1' ? $censored_subject : $post['subject'], $mail_message);
                    $mail_message = str_replace('<forum_name>', $cur_posting['forum_name'], $mail_message);
                    $mail_message = str_replace('<poster>', $post['username'], $mail_message);
                    $mail_message = str_replace('<topic_url>', get_base_url().'/viewtopic.php?id='.$new_tid, $mail_message);
                    $mail_message = str_replace('<unsubscribe_url>', get_base_url().'/misc.php?action=unsubscribe&fid='.$cur_posting['id'], $mail_message);
                    $mail_message = str_replace('<board_mailer>', $pun_config['o_board_title'], $mail_message);

                    $mail_subject_full = str_replace('<forum_name>', $cur_posting['forum_name'], $mail_subject_full);
                    $mail_message_full = str_replace('<topic_subject>', $pun_config['o_censoring'] == '1' ? $censored_subject : $post['subject'], $mail_message_full);
                    $mail_message_full = str_replace('<forum_name>', $cur_posting['forum_name'], $mail_message_full);
                    $mail_message_full = str_replace('<poster>', $post['username'], $mail_message_full);
                    $mail_message_full = str_replace('<message>', $cleaned_message, $mail_message_full);
                    $mail_message_full = str_replace('<topic_url>', get_base_url().'/viewtopic.php?id='.$new_tid, $mail_message_full);
                    $mail_message_full = str_replace('<unsubscribe_url>', get_base_url().'/misc.php?action=unsubscribe&fid='.$cur_posting['id'], $mail_message_full);
                    $mail_message_full = str_replace('<board_mailer>', $pun_config['o_board_title'], $mail_message_full);

                    $notification_emails[$cur_subscriber['language']][0] = $mail_subject;
                    $notification_emails[$cur_subscriber['language']][1] = $mail_message;
                    $notification_emails[$cur_subscriber['language']][2] = $mail_subject_full;
                    $notification_emails[$cur_subscriber['language']][3] = $mail_message_full;

                    $mail_subject = $mail_message = $mail_subject_full = $mail_message_full = null;
                }
            }

            // We have to double check here because the templates could be missing
            if (isset($notification_emails[$cur_subscriber['language']])) {
                if ($cur_subscriber['notify_with_post'] == '0') {
                    pun_mail($cur_subscriber['email'], $notification_emails[$cur_subscriber['language']][0], $notification_emails[$cur_subscriber['language']][1]);
                } else {
                    pun_mail($cur_subscriber['email'], $notification_emails[$cur_subscriber['language']][2], $notification_emails[$cur_subscriber['language']][3]);
                }
            }
        }

        unset($cleaned_message);
    }
}

// Warn the admin if a banned user posts
function warn_banned_user($post, $new_pid)
{
    global $pun_config, $pun_user;
    
    // Load the "banned email post" template
    $mail_tpl = trim(file_get_contents(PUN_ROOT.'lang/'.$pun_user['language'].'/mail_templates/banned_email_post.tpl'));

    // The first row contains the subject
    $first_crlf = strpos($mail_tpl, "\n");
    $mail_subject = trim(substr($mail_tpl, 8, $first_crlf-8));
    $mail_message = trim(substr($mail_tpl, $first_crlf));

    $mail_message = str_replace('<username>', $post['username'], $mail_message);
    $mail_message = str_replace('<email>', $post['email'], $mail_message);
    $mail_message = str_replace('<post_url>', get_base_url().'/viewtopic.php?pid='.$new_pid.'#p'.$new_pid, $mail_message);
    $mail_message = str_replace('<board_mailer>', $pun_config['o_board_title'], $mail_message);

    pun_mail($pun_config['o_mailing_list'], $mail_subject, $mail_message);
}

// Increment post count, change group if needed
function increment_post_count($post)
{
    global $db, $pun_user;
    
    if (!$pun_user['is_guest']) {
        $db->query('UPDATE '.$db->prefix.'users SET num_posts=num_posts+1, last_post='.$post['time'].' WHERE id='.$pun_user['id']) or error('Unable to update user', __FILE__, __LINE__, $db->error());

        // Promote this user to a new group if enabled
        if ($pun_user['g_promote_next_group'] != 0 && $pun_user['num_posts'] + 1 >= $pun_user['g_promote_min_posts']) {
            $new_group_id = $pun_user['g_promote_next_group'];
            $db->query('UPDATE '.$db->prefix.'users SET group_id='.$new_group_id.' WHERE id='.$pun_user['id']) or error('Unable to promote user to new group', __FILE__, __LINE__, $db->error());
        }

        // Topic tracking stuff...
        $tracked_topics = get_tracked_topics();
        $tracked_topics['topics'][$new_tid] = time();
        set_tracked_topics($tracked_topics);
    } else {
        $db->query('UPDATE '.$db->prefix.'online SET last_post='.$post['time'].' WHERE ident=\''.$db->escape(get_remote_address()).'\'') or error('Unable to update user', __FILE__, __LINE__, $db->error());
    }
}

// If we are quoting a message
function get_quote_message($qid, $tid)
{
    global $db, $pun_config;

    if ($qid < 1) {
        message($lang_common['Bad request'], false, '404 Not Found');
    }

    $result = $db->query('SELECT poster, message FROM '.$db->prefix.'posts WHERE id='.$qid.' AND topic_id='.$tid) or error('Unable to fetch quote info', __FILE__, __LINE__, $db->error());
    if (!$db->num_rows($result)) {
        message($lang_common['Bad request'], false, '404 Not Found');
    }

    list($q_poster, $q_message) = $db->fetch_row($result);

    // If the message contains a code tag we have to split it up (text within [code][/code] shouldn't be touched)
    if (strpos($q_message, '[code]') !== false && strpos($q_message, '[/code]') !== false) {
        list($inside, $outside) = split_text($q_message, '[code]', '[/code]');

        $q_message = implode("\1", $outside);
    }

    // Remove [img] tags from quoted message
    $q_message = preg_replace('%\[img(?:=(?:[^\[]*?))?\]((ht|f)tps?://)([^\s<"]*?)\[/img\]%U', '\1\3', $q_message);

    // If we split up the message before we have to concatenate it together again (code tags)
    if (isset($inside)) {
        $outside = explode("\1", $q_message);
        $q_message = '';

        $num_tokens = count($outside);
        for ($i = 0; $i < $num_tokens; ++$i) {
            $q_message .= $outside[$i];
            if (isset($inside[$i])) {
                $q_message .= '[code]'.$inside[$i].'[/code]';
            }
        }

        unset($inside);
    }

    if ($pun_config['o_censoring'] == '1') {
        $q_message = censor_words($q_message);
    }

    $q_message = pun_htmlspecialchars($q_message);

    if ($pun_config['p_message_bbcode'] == '1') {
        // If username contains a square bracket, we add "" or '' around it (so we know when it starts and ends)
        if (strpos($q_poster, '[') !== false || strpos($q_poster, ']') !== false) {
            if (strpos($q_poster, '\'') !== false) {
                $q_poster = '"'.$q_poster.'"';
            } else {
                $q_poster = '\''.$q_poster.'\'';
            }
        } else {
            // Get the characters at the start and end of $q_poster
            $ends = substr($q_poster, 0, 1).substr($q_poster, -1, 1);

            // Deal with quoting "Username" or 'Username' (becomes '"Username"' or "'Username'")
            if ($ends == '\'\'') {
                $q_poster = '"'.$q_poster.'"';
            } elseif ($ends == '""') {
                $q_poster = '\''.$q_poster.'\'';
            }
        }

        $quote = '[quote='.$q_poster.']'.$q_message.'[/quote]'."\n";
    } else {
        $quote = '> '.$q_poster.' '.$lang_common['wrote']."\n\n".'> '.$q_message."\n";
    }
    
    return $quote;
}

// Get the current state of checkboxes
function get_checkboxes($post_data, $fid, $is_admmod)
{
    global $pun_user, $lang_post, $lang_common, $pun_config;
    
    $checkboxes = array();
    if ($fid && $is_admmod) {
        $checkboxes[] = '<label><input type="checkbox" name="stick_topic" value="1" tabindex="'.($cur_index++).'"'.(isset($post_data['stick_topic']) ? ' checked="checked"' : '').' />'.$lang_common['Stick topic'].'<br /></label>';
    }

    if (!$pun_user['is_guest']) {
        if ($pun_config['o_smilies'] == '1') {
            $checkboxes[] = '<label><input type="checkbox" name="hide_smilies" value="1" tabindex="'.($cur_index++).'"'.(isset($post_data['hide_smilies']) ? ' checked="checked"' : '').' />'.$lang_post['Hide smilies'].'<br /></label>';
        }

        if ($pun_config['o_topic_subscriptions'] == '1') {
            $subscr_checked = false;

            // If it's a preview
            if (isset($post_data['preview'])) {
                $subscr_checked = isset($post_data['subscribe']) ? true : false;
            }
            // If auto subscribed
            elseif ($pun_user['auto_notify']) {
                $subscr_checked = true;
            }
            // If already subscribed to the topic
            elseif ($is_subscribed) {
                $subscr_checked = true;
            }

            $checkboxes[] = '<label><input type="checkbox" name="subscribe" value="1" tabindex="'.($cur_index++).'"'.($subscr_checked ? ' checked="checked"' : '').' />'.($is_subscribed ? $lang_post['Stay subscribed'] : $lang_post['Subscribe']).'<br /></label>';
        }
    } elseif ($pun_config['o_smilies'] == '1') {
        $checkboxes[] = '<label><input type="checkbox" name="hide_smilies" value="1" tabindex="'.($cur_index++).'"'.(isset($post_data['hide_smilies']) ? ' checked="checked"' : '').' />'.$lang_post['Hide smilies'].'<br /></label>';
    }
    
    return $checkboxes;
}

// Display the topic review if needed
function topic_review($tid)
{
    global $db, $pun_config, $smilies, $re_list;
    
    $post_data = array();

    require_once PUN_ROOT.'include/parser.php';

    $result = $db->query('SELECT poster, message, hide_smilies, posted FROM '.$db->prefix.'posts WHERE topic_id='.$tid.' ORDER BY id DESC LIMIT '.$pun_config['o_topic_review']) or error('Unable to fetch topic review', __FILE__, __LINE__, $db->error());

    while ($cur_post = $db->fetch_assoc($result)) {
        $cur_post['message'] = parse_message($cur_post['message'], $cur_post['hide_smilies']);
        $post_data[] = $cur_post;
    }
    return $post_data;
}
