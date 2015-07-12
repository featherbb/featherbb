<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace model;

class post
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
 
    //  Get some info about the post
    public function get_info_post($tid, $fid)
    {
        global $lang_common;

        if ($tid) {
            $result = $this->db->query('SELECT f.id, f.forum_name, f.moderators, f.redirect_url, fp.post_replies, fp.post_topics, t.subject, t.closed, s.user_id AS is_subscribed FROM '.$this->db->prefix.'topics AS t INNER JOIN '.$this->db->prefix.'forums AS f ON f.id=t.forum_id LEFT JOIN '.$this->db->prefix.'forum_perms AS fp ON (fp.forum_id=f.id AND fp.group_id='.$this->user['g_id'].') LEFT JOIN '.$this->db->prefix.'topic_subscriptions AS s ON (t.id=s.topic_id AND s.user_id='.$this->user['id'].') WHERE (fp.read_forum IS NULL OR fp.read_forum=1) AND t.id='.$tid) or error('Unable to fetch forum info', __FILE__, __LINE__, $this->db->error());
        } else {
            $result = $this->db->query('SELECT f.id, f.forum_name, f.moderators, f.redirect_url, fp.post_replies, fp.post_topics FROM '.$this->db->prefix.'forums AS f LEFT JOIN '.$this->db->prefix.'forum_perms AS fp ON (fp.forum_id=f.id AND fp.group_id='.$this->user['g_id'].') WHERE (fp.read_forum IS NULL OR fp.read_forum=1) AND f.id='.$fid) or error('Unable to fetch forum info', __FILE__, __LINE__, $this->db->error());
        }

        if (!$this->db->num_rows($result)) {
            message($lang_common['Bad request'], false, '404 Not Found');
        }

        $cur_posting = $this->db->fetch_assoc($result);

        return $cur_posting;
    }

    // Checks the post for errors before posting
    public function check_errors_before_post($fid, $tid, $qid, $pid, $page, $errors)
    {
        global $lang_post, $lang_common, $lang_prof_reg, $lang_register, $lang_antispam, $lang_antispam_questions, $pd;

        // Antispam feature
        if ($this->user['is_guest']) {

            // It's a guest, so we have to validate the username
            $errors = check_username(feather_trim($this->request->post('req_username')), $errors);

            $question = $this->request->post('captcha_q') ? trim($this->request->post('captcha_q')) : '';
            $answer = $this->request->post('captcha') ? strtoupper(trim($this->request->post('captcha'))) : '';
            $lang_antispam_questions_array = array();

            foreach ($lang_antispam_questions as $k => $v) {
                $lang_antispam_questions_array[md5($k)] = strtoupper($v);
            }

            if (empty($lang_antispam_questions_array[$question]) || $lang_antispam_questions_array[$question] != $answer) {
                $errors[] = $lang_antispam['Robot test fail'];
            }
        }

        // Flood protection
        if ($this->request->post('preview') != '' && $this->user['last_post'] != '' && (time() - $this->user['last_post']) < $this->user['g_post_flood']) {
            $errors[] = sprintf($lang_post['Flood start'], $this->user['g_post_flood'], $this->user['g_post_flood'] - (time() - $this->user['last_post']));
        }

        if ($tid) {
            $result = $this->db->query('SELECT subject FROM '.$this->db->prefix.'topics WHERE id='.$tid) or error('Unable to get subject', __FILE__, __LINE__, $this->db->error());
            $subject_tid = $this->db->result($result);
            if (!$this->db->num_rows($result)) {
                message($lang_common['Bad request'], false, '404 Not Found');
            }
            $url_subject = url_friendly($subject_tid);
        } else {
            $url_subject = '';
        }

        // Make sure they got here from the site
        confirm_referrer(array(
            get_link_r('post/new-topic/'.$fid.'/'),
            get_link_r('post/reply/'.$tid.'/'),
            get_link_r('post/reply/'.$tid.'/quote/'.$qid.'/'),
            get_link_r('topic/'.$tid.'/'.$url_subject.'/'),
            get_link_r('topic/'.$tid.'/'),
            get_link_r('topic/'.$tid.'/'.$url_subject.'/page/'.$page.'/'),
            get_link_r('post/'.$pid.'/#p'.$pid),
            )
        );

        // If it's a new topic
        if ($fid) {
            $subject = feather_trim($this->request->post('req_subject'));

            if ($this->config['o_censoring'] == '1') {
                $censored_subject = feather_trim(censor_words($subject));
            }

            if ($subject == '') {
                $errors[] = $lang_post['No subject'];
            } elseif ($this->config['o_censoring'] == '1' && $censored_subject == '') {
                $errors[] = $lang_post['No subject after censoring'];
            } elseif (feather_strlen($subject) > 70) {
                $errors[] = $lang_post['Too long subject'];
            } elseif ($this->config['p_subject_all_caps'] == '0' && is_all_uppercase($subject) && !$this->user['is_admmod']) {
                $errors[] = $lang_post['All caps subject'];
            }
        }

        // If the user is logged in we get the username and email from $this->user
        if (!$this->user['is_guest']) {
            $username = $this->user['username'];
            $email = $this->user['email'];
        }
        // Otherwise it should be in $feather ($_POST)
        else {
            $email = strtolower(feather_trim(($this->config['p_force_guest_email'] == '1') ? $this->request->post('req_email') : $this->request->post('email')));

            // Load the register.php/prof_reg.php language files
            require FEATHER_ROOT.'lang/'.$this->user['language'].'/prof_reg.php';
            require FEATHER_ROOT.'lang/'.$this->user['language'].'/register.php';

            if ($this->config['p_force_guest_email'] == '1' || $email != '') {
                require FEATHER_ROOT.'include/email.php';
                if (!is_valid_email($email)) {
                    $errors[] = $lang_common['Invalid email'];
                }

                // Check if it's a banned email address
                // we should only check guests because members' addresses are already verified
                if ($this->user['is_guest'] && is_banned_email($email)) {
                    if ($this->config['p_allow_banned_email'] == '0') {
                        $errors[] = $lang_prof_reg['Banned email'];
                    }

                    $errors['banned_email'] = 1; // Used later when we send an alert email
                }
            }
        }

        // Clean up message from POST
        $orig_message = $message = feather_linebreaks(feather_trim($this->request->post('req_message')));

        // Here we use strlen() not feather_strlen() as we want to limit the post to FEATHER_MAX_POSTSIZE bytes, not characters
        if (strlen($message) > FEATHER_MAX_POSTSIZE) {
            $errors[] = sprintf($lang_post['Too long message'], forum_number_format(FEATHER_MAX_POSTSIZE));
        } elseif ($this->config['p_message_all_caps'] == '0' && is_all_uppercase($message) && !$this->user['is_admmod']) {
            $errors[] = $lang_post['All caps message'];
        }

        // Validate BBCode syntax
        if ($this->config['p_message_bbcode'] == '1') {
            require FEATHER_ROOT.'include/parser.php';
            $message = preparse_bbcode($message, $errors);
        }

        if (empty($errors)) {
            if ($message == '') {
                $errors[] = $lang_post['No message'];
            } elseif ($this->config['o_censoring'] == '1') {
                // Censor message to see if that causes problems
                $censored_message = feather_trim(censor_words($message));

                if ($censored_message == '') {
                    $errors[] = $lang_post['No message after censoring'];
                }
            }
        }

        return $errors;
    }

    // If the previous check went OK, setup some variables used later
    public function setup_variables($errors, $is_admmod)
    {
        $post = array();

        if (!$this->user['is_guest']) {
            $post['username'] = $this->user['username'];
            $post['email'] = $this->user['email'];
        }
        // Otherwise it should be in $feather ($_POST)
        else {
            $post['username'] = feather_trim($this->request->post('req_username'));
            $post['email'] = strtolower(feather_trim(($this->config['p_force_guest_email'] == '1') ? $this->request->post('req_email') : $this->request->post('email')));
        }

        if ($this->request->post('req_subject')) {
            $post['subject'] = feather_trim($this->request->post('req_subject'));
        }

        $post['hide_smilies'] = $this->request->post('hide_smilies') ? '1' : '0';
        $post['subscribe'] = $this->request->post('subscribe') ? '1' : '0';
        $post['stick_topic'] = $this->request->post('stick_topic') && $is_admmod ? '1' : '0';

        $post['message']  = feather_linebreaks(feather_trim($this->request->post('req_message')));

        // Validate BBCode syntax
        if ($this->config['p_message_bbcode'] == '1') {
            require_once FEATHER_ROOT.'include/parser.php';
            $post['message']  = preparse_bbcode($post['message'], $errors);
        }

        // Replace four-byte characters (MySQL cannot handle them)
        $post['message'] = strip_bad_multibyte_chars($post['message']);

        $post['time'] = time();

        return $post;
    }

    // Insert a reply
    public function insert_reply($post, $tid, $cur_posting, $is_subscribed)
    {
        $new = array();

        if (!$this->user['is_guest']) {
            $new['tid'] = $tid;

            // Insert the new post
            $this->db->query('INSERT INTO '.$this->db->prefix.'posts (poster, poster_id, poster_ip, message, hide_smilies, posted, topic_id) VALUES(\''.$this->db->escape($post['username']).'\', '.$this->user['id'].', \''.$this->db->escape(get_remote_address()).'\', \''.$this->db->escape($post['message']).'\', '.$post['hide_smilies'].', '.$post['time'].', '.$tid.')') or error('Unable to create post', __FILE__, __LINE__, $this->db->error());
            $new['pid'] = $this->db->insert_id();

            // To subscribe or not to subscribe, that ...
            if ($this->config['o_topic_subscriptions'] == '1') {
                if (isset($post['subscribe']) && $post['subscribe'] && !$is_subscribed) {
                    $this->db->query('INSERT INTO '.$this->db->prefix.'topic_subscriptions (user_id, topic_id) VALUES('.$this->user['id'].' ,'.$tid.')') or error('Unable to add subscription', __FILE__, __LINE__, $this->db->error());
                } elseif (!isset($post['subscribe']) && $is_subscribed) {
                    $this->db->query('DELETE FROM '.$this->db->prefix.'topic_subscriptions WHERE user_id='.$this->user['id'].' AND topic_id='.$tid) or error('Unable to remove subscription', __FILE__, __LINE__, $this->db->error());
                }
            }
        } else {
            // It's a guest. Insert the new post
            $email_sql = ($this->config['p_force_guest_email'] == '1' || $post['email'] != '') ? '\''.$this->db->escape($post['email']).'\'' : 'NULL';
            $this->db->query('INSERT INTO '.$this->db->prefix.'posts (poster, poster_ip, poster_email, message, hide_smilies, posted, topic_id) VALUES(\''.$this->db->escape($post['username']).'\', \''.$this->db->escape(get_remote_address()).'\', '.$email_sql.', \''.$this->db->escape($post['message']).'\', '.$post['hide_smilies'].', '.$post['time'].', '.$tid.')') or error('Unable to create post', __FILE__, __LINE__, $this->db->error());
            $new['pid'] = $this->db->insert_id();
        }

        // Update topic
        $this->db->query('UPDATE '.$this->db->prefix.'topics SET num_replies=num_replies+1, last_post='.$post['time'].', last_post_id='.$new['pid'].', last_poster=\''.$this->db->escape($post['username']).'\' WHERE id='.$tid) or error('Unable to update topic', __FILE__, __LINE__, $this->db->error());

        update_search_index('post', $new['pid'], $post['message']);

        update_forum($cur_posting['id']);

        return $new;
    }

    // Send notifications for replies
    public function send_notifications_reply($tid, $cur_posting, $new_pid, $post)
    {
        // Get the post time for the previous post in this topic
        $result = $this->db->query('SELECT posted FROM '.$this->db->prefix.'posts WHERE topic_id='.$tid.' ORDER BY id DESC LIMIT 1, 1') or error('Unable to fetch post info', __FILE__, __LINE__, $this->db->error());
        $previous_post_time = $this->db->result($result);

        // Get any subscribed users that should be notified (banned users are excluded)
        $result = $this->db->query('SELECT u.id, u.email, u.notify_with_post, u.language FROM '.$this->db->prefix.'users AS u INNER JOIN '.$this->db->prefix.'topic_subscriptions AS s ON u.id=s.user_id LEFT JOIN '.$this->db->prefix.'forum_perms AS fp ON (fp.forum_id='.$cur_posting['id'].' AND fp.group_id=u.group_id) LEFT JOIN '.$this->db->prefix.'online AS o ON u.id=o.user_id LEFT JOIN '.$this->db->prefix.'bans AS b ON u.username=b.username WHERE b.username IS NULL AND COALESCE(o.logged, u.last_visit)>'.$previous_post_time.' AND (fp.read_forum IS NULL OR fp.read_forum=1) AND s.topic_id='.$tid.' AND u.id!='.$this->user['id']) or error('Unable to fetch subscription info', __FILE__, __LINE__, $this->db->error());
        if ($this->db->num_rows($result)) {
            require_once FEATHER_ROOT.'include/email.php';

            $notification_emails = array();

            $censored_message = feather_trim(censor_words($post['message']));

            if ($this->config['o_censoring'] == '1') {
                $cleaned_message = bbcode2email($censored_message, -1);
            } else {
                $cleaned_message = bbcode2email($post['message'], -1);
            }

            // Loop through subscribed users and send emails
            while ($cur_subscriber = $this->db->fetch_assoc($result)) {
                // Is the subscription email for $cur_subscriber['language'] cached or not?
                if (!isset($notification_emails[$cur_subscriber['language']])) {
                    if (file_exists(FEATHER_ROOT.'lang/'.$cur_subscriber['language'].'/mail_templates/new_reply.tpl')) {
                        // Load the "new reply" template
                        $mail_tpl = trim(file_get_contents(FEATHER_ROOT.'lang/'.$cur_subscriber['language'].'/mail_templates/new_reply.tpl'));

                        // Load the "new reply full" template (with post included)
                        $mail_tpl_full = trim(file_get_contents(FEATHER_ROOT.'lang/'.$cur_subscriber['language'].'/mail_templates/new_reply_full.tpl'));

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
                        $mail_message = str_replace('<post_url>', get_link('post/'.$new_pid.'/#p'.$new_pid), $mail_message);
                        $mail_message = str_replace('<unsubscribe_url>', get_base_url().'/misc.php?action=unsubscribe&tid='.$tid, $mail_message);
                        $mail_message = str_replace('<board_mailer>', $this->config['o_board_title'], $mail_message);

                        $mail_subject_full = str_replace('<topic_subject>', $cur_posting['subject'], $mail_subject_full);
                        $mail_message_full = str_replace('<topic_subject>', $cur_posting['subject'], $mail_message_full);
                        $mail_message_full = str_replace('<replier>', $post['username'], $mail_message_full);
                        $mail_message_full = str_replace('<message>', $cleaned_message, $mail_message_full);
                        $mail_message_full = str_replace('<post_url>', get_link('post/'.$new_pid.'/#p'.$new_pid), $mail_message_full);
                        $mail_message_full = str_replace('<unsubscribe_url>', get_base_url().'/misc.php?action=unsubscribe&tid='.$tid, $mail_message_full);
                        $mail_message_full = str_replace('<board_mailer>', $this->config['o_board_title'], $mail_message_full);

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
    public function insert_topic($post, $fid)
    {
        $new = array();

        // Create the topic
        $this->db->query('INSERT INTO '.$this->db->prefix.'topics (poster, subject, posted, last_post, last_poster, sticky, forum_id) VALUES(\''.$this->db->escape($post['username']).'\', \''.$this->db->escape($post['subject']).'\', '.$post['time'].', '.$post['time'].', \''.$this->db->escape($post['username']).'\', '.$post['stick_topic'].', '.$fid.')') or error('Unable to create topic', __FILE__, __LINE__, $this->db->error());
        $new['tid'] = $this->db->insert_id();

        if (!$this->user['is_guest']) {
            // To subscribe or not to subscribe, that ...
            if ($this->config['o_topic_subscriptions'] == '1' && $post['subscribe']) {
                $this->db->query('INSERT INTO '.$this->db->prefix.'topic_subscriptions (user_id, topic_id) VALUES('.$this->user['id'].' ,'.$new['tid'].')') or error('Unable to add subscription', __FILE__, __LINE__, $this->db->error());
            }

            // Create the post ("topic post")
            $this->db->query('INSERT INTO '.$this->db->prefix.'posts (poster, poster_id, poster_ip, message, hide_smilies, posted, topic_id) VALUES(\''.$this->db->escape($post['username']).'\', '.$this->user['id'].', \''.$this->db->escape(get_remote_address()).'\', \''.$this->db->escape($post['message']).'\', '.$post['hide_smilies'].', '.$post['time'].', '.$new['tid'].')') or error('Unable to create post', __FILE__, __LINE__, $this->db->error());
        } else {
            // Create the post ("topic post")
            $email_sql = ($this->config['p_force_guest_email'] == '1' || $post['email'] != '') ? '\''.$this->db->escape($post['email']).'\'' : 'NULL';
            $this->db->query('INSERT INTO '.$this->db->prefix.'posts (poster, poster_ip, poster_email, message, hide_smilies, posted, topic_id) VALUES(\''.$this->db->escape($post['username']).'\', \''.$this->db->escape(get_remote_address()).'\', '.$email_sql.', \''.$this->db->escape($post['message']).'\', '.$post['hide_smilies'].', '.$post['time'].', '.$new['tid'].')') or error('Unable to create post', __FILE__, __LINE__, $this->db->error());
        }
        $new['pid'] = $this->db->insert_id();

        // Update the topic with last_post_id
        $this->db->query('UPDATE '.$this->db->prefix.'topics SET last_post_id='.$new['pid'].', first_post_id='.$new['pid'].' WHERE id='.$new['tid']) or error('Unable to update topic', __FILE__, __LINE__, $this->db->error());

        update_search_index('post', $new['pid'], $post['message'], $post['subject']);

        update_forum($fid);

        return $new;
    }

    // Send notifications for new topics
    public function send_notifications_new_topic($post, $cur_posting, $new_tid)
    {
        // Get any subscribed users that should be notified (banned users are excluded)
        $result = $this->db->query('SELECT u.id, u.email, u.notify_with_post, u.language FROM '.$this->db->prefix.'users AS u INNER JOIN '.$this->db->prefix.'forum_subscriptions AS s ON u.id=s.user_id LEFT JOIN '.$this->db->prefix.'forum_perms AS fp ON (fp.forum_id='.$cur_posting['id'].' AND fp.group_id=u.group_id) LEFT JOIN '.$this->db->prefix.'bans AS b ON u.username=b.username WHERE b.username IS NULL AND (fp.read_forum IS NULL OR fp.read_forum=1) AND s.forum_id='.$cur_posting['id'].' AND u.id!='.$this->user['id']) or error('Unable to fetch subscription info', __FILE__, __LINE__, $this->db->error());
        if ($this->db->num_rows($result)) {
            require_once FEATHER_ROOT.'include/email.php';

            $notification_emails = array();

            if ($this->config['o_censoring'] == '1') {
                $cleaned_message = bbcode2email($censored_message, -1);
            } else {
                $cleaned_message = bbcode2email($post['message'], -1);
            }

            // Loop through subscribed users and send emails
            while ($cur_subscriber = $this->db->fetch_assoc($result)) {
                // Is the subscription email for $cur_subscriber['language'] cached or not?
                if (!isset($notification_emails[$cur_subscriber['language']])) {
                    if (file_exists(FEATHER_ROOT.'lang/'.$cur_subscriber['language'].'/mail_templates/new_topic.tpl')) {
                        // Load the "new topic" template
                        $mail_tpl = trim(file_get_contents(FEATHER_ROOT.'lang/'.$cur_subscriber['language'].'/mail_templates/new_topic.tpl'));

                        // Load the "new topic full" template (with post included)
                        $mail_tpl_full = trim(file_get_contents(FEATHER_ROOT.'lang/'.$cur_subscriber['language'].'/mail_templates/new_topic_full.tpl'));

                        // The first row contains the subject (it also starts with "Subject:")
                        $first_crlf = strpos($mail_tpl, "\n");
                        $mail_subject = trim(substr($mail_tpl, 8, $first_crlf-8));
                        $mail_message = trim(substr($mail_tpl, $first_crlf));

                        $first_crlf = strpos($mail_tpl_full, "\n");
                        $mail_subject_full = trim(substr($mail_tpl_full, 8, $first_crlf-8));
                        $mail_message_full = trim(substr($mail_tpl_full, $first_crlf));

                        $mail_subject = str_replace('<forum_name>', $cur_posting['forum_name'], $mail_subject);
                        $mail_message = str_replace('<topic_subject>', $this->config['o_censoring'] == '1' ? $censored_subject : $post['subject'], $mail_message);
                        $mail_message = str_replace('<forum_name>', $cur_posting['forum_name'], $mail_message);
                        $mail_message = str_replace('<poster>', $post['username'], $mail_message);
                        $mail_message = str_replace('<topic_url>', get_link('topic/'.$new_tid.'/'), $mail_message);
                        $mail_message = str_replace('<unsubscribe_url>', get_base_url().'/misc.php?action=unsubscribe&fid='.$cur_posting['id'], $mail_message);
                        $mail_message = str_replace('<board_mailer>', $this->config['o_board_title'], $mail_message);

                        $mail_subject_full = str_replace('<forum_name>', $cur_posting['forum_name'], $mail_subject_full);
                        $mail_message_full = str_replace('<topic_subject>', $this->config['o_censoring'] == '1' ? $censored_subject : $post['subject'], $mail_message_full);
                        $mail_message_full = str_replace('<forum_name>', $cur_posting['forum_name'], $mail_message_full);
                        $mail_message_full = str_replace('<poster>', $post['username'], $mail_message_full);
                        $mail_message_full = str_replace('<message>', $cleaned_message, $mail_message_full);
                        $mail_message_full = str_replace('<topic_url>', get_link('topic/'.$new_tid.'/'), $mail_message_full);
                        $mail_message_full = str_replace('<unsubscribe_url>', get_base_url().'/misc.php?action=unsubscribe&fid='.$cur_posting['id'], $mail_message_full);
                        $mail_message_full = str_replace('<board_mailer>', $this->config['o_board_title'], $mail_message_full);

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
    public function warn_banned_user($post, $new_pid)
    {
        // Load the "banned email post" template
        $mail_tpl = trim(file_get_contents(FEATHER_ROOT.'lang/'.$this->user['language'].'/mail_templates/banned_email_post.tpl'));

        // The first row contains the subject
        $first_crlf = strpos($mail_tpl, "\n");
        $mail_subject = trim(substr($mail_tpl, 8, $first_crlf-8));
        $mail_message = trim(substr($mail_tpl, $first_crlf));

        $mail_message = str_replace('<username>', $post['username'], $mail_message);
        $mail_message = str_replace('<email>', $post['email'], $mail_message);
        $mail_message = str_replace('<post_url>', get_link('post/'.$new_pid.'/#p'.$new_pid), $mail_message);
        $mail_message = str_replace('<board_mailer>', $this->config['o_board_title'], $mail_message);

        pun_mail($this->config['o_mailing_list'], $mail_subject, $mail_message);
    }

    // Increment post count, change group if needed
    public function increment_post_count($post, $new_tid)
    {
        if (!$this->user['is_guest']) {
            $this->db->query('UPDATE '.$this->db->prefix.'users SET num_posts=num_posts+1, last_post='.$post['time'].' WHERE id='.$this->user['id']) or error('Unable to update user', __FILE__, __LINE__, $this->db->error());

            // Promote this user to a new group if enabled
            if ($this->user['g_promote_next_group'] != 0 && $this->user['num_posts'] + 1 >= $this->user['g_promote_min_posts']) {
                $new_group_id = $this->user['g_promote_next_group'];
                $this->db->query('UPDATE '.$this->db->prefix.'users SET group_id='.$new_group_id.' WHERE id='.$this->user['id']) or error('Unable to promote user to new group', __FILE__, __LINE__, $this->db->error());
            }

            // Topic tracking stuff...
            $tracked_topics = get_tracked_topics();
            $tracked_topics['topics'][$new_tid] = time();
            set_tracked_topics($tracked_topics);
        } else {
            $this->db->query('UPDATE '.$this->db->prefix.'online SET last_post='.$post['time'].' WHERE ident=\''.$this->db->escape(get_remote_address()).'\'') or error('Unable to update user', __FILE__, __LINE__, $this->db->error());
        }
    }

    //
    // Split text into chunks ($inside contains all text inside $start and $end, and $outside contains all text outside)
    //
    public function split_text($text, $start, $end, $retab = true)
    {
        $result = array(0 => array(), 1 => array()); // 0 = inside, 1 = outside

        // split the text into parts
        $parts = preg_split('%'.preg_quote($start, '%').'(.*)'.preg_quote($end, '%').'%Us', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
        $num_parts = count($parts);

        // preg_split results in outside parts having even indices, inside parts having odd
        for ($i = 0;$i < $num_parts;$i++) {
            $result[1 - ($i % 2)][] = $parts[$i];
        }

        if ($this->config['o_indent_num_spaces'] != 8 && $retab) {
            $spaces = str_repeat(' ', $this->config['o_indent_num_spaces']);
            $result[1] = str_replace("\t", $spaces, $result[1]);
        }

        return $result;
    }

    // If we are quoting a message
    public function get_quote_message($qid, $tid)
    {
        global $lang_common;

        $result = $this->db->query('SELECT poster, message FROM '.$this->db->prefix.'posts WHERE id='.$qid.' AND topic_id='.$tid) or error('Unable to fetch quote info', __FILE__, __LINE__, $this->db->error());
        if (!$this->db->num_rows($result)) {
            message($lang_common['Bad request'], false, '404 Not Found');
        }

        list($q_poster, $q_message) = $this->db->fetch_row($result);

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

        if ($this->config['o_censoring'] == '1') {
            $q_message = censor_words($q_message);
        }

        $q_message = feather_escape($q_message);

        if ($this->config['p_message_bbcode'] == '1') {    // Sanitize username for inclusion within QUOTE BBCode attribute.
                //   This is a bit tricky because a username can have any "special"
                //   characters such as backslash \ square brackets [] and quotes '".
                if (preg_match('/[[\]\'"]/S', $q_poster)) {
                    // Check if we need to quote it.
                    // Post has special chars. Escape escapes and quotes then wrap in quotes.
                    if (strpos($q_poster, '"') !== false && strpos($q_poster, '\'') === false) { // If there are double quotes but no single quotes, use single quotes,
                        $q_poster = feather_escape(str_replace('\\', '\\\\', $q_poster));
                        $q_poster = '\''. $q_poster .'#'. $qid .'\'';
                    } else { // otherwise use double quotes.
                        $q_poster = feather_escape(str_replace(array('\\', '"'), array('\\\\', '\\"'), $q_poster));
                        $q_poster = '"'. $q_poster .'#'. $qid .'"';
                    }
                } else {
                    $q_poster = $q_poster .'#'. $qid;
                }
            $quote = '[quote='. $q_poster .']'.$q_message.'[/quote]'."\n";
        } else {
            $quote = '> '.$q_poster.' '.$lang_common['wrote']."\n\n".'> '.$q_message."\n";
        }

        return $quote;
    }

    // Get the current state of checkboxes
    public function get_checkboxes($fid, $is_admmod, $is_subscribed)
    {
        global $lang_post, $lang_common;

        $cur_index = 1;

        $checkboxes = array();
        if ($fid && $is_admmod) {
            $checkboxes[] = '<label><input type="checkbox" name="stick_topic" value="1" tabindex="'.($cur_index++).'"'.($this->request->post('stick_topic') ? ' checked="checked"' : '').' />'.$lang_common['Stick topic'].'<br /></label>';
        }

        if (!$this->user['is_guest']) {
            if ($this->config['o_smilies'] == '1') {
                $checkboxes[] = '<label><input type="checkbox" name="hide_smilies" value="1" tabindex="'.($cur_index++).'"'.($this->request->post('hide_smilies') ? ' checked="checked"' : '').' />'.$lang_post['Hide smilies'].'<br /></label>';
            }

            if ($this->config['o_topic_subscriptions'] == '1') {
                $subscr_checked = false;

                // If it's a preview
                if ($this->request->post('preview')) {
                    $subscr_checked = ($this->request->post('subscribe')) ? true : false;
                }
                // If auto subscribed
                elseif ($this->user['auto_notify']) {
                    $subscr_checked = true;
                }
                // If already subscribed to the topic
                elseif ($is_subscribed) {
                    $subscr_checked = true;
                }

                $checkboxes[] = '<label><input type="checkbox" name="subscribe" value="1" tabindex="'.($cur_index++).'"'.($subscr_checked ? ' checked="checked"' : '').' />'.($is_subscribed ? $lang_post['Stay subscribed'] : $lang_post['Subscribe']).'<br /></label>';
            }
        } elseif ($this->config['o_smilies'] == '1') {
            $checkboxes[] = '<label><input type="checkbox" name="hide_smilies" value="1" tabindex="'.($cur_index++).'"'.($this->request->post('hide_smilies') ? ' checked="checked"' : '').' />'.$lang_post['Hide smilies'].'<br /></label>';
        }

        return $checkboxes;
    }

    // Display the topic review if needed
    public function topic_review($tid)
    {
        global $pd;

        $post_data = array();

        require_once FEATHER_ROOT.'include/parser.php';

        $result = $this->db->query('SELECT poster, message, hide_smilies, posted FROM '.$this->db->prefix.'posts WHERE topic_id='.$tid.' ORDER BY id DESC LIMIT '.$this->config['o_topic_review']) or error('Unable to fetch topic review', __FILE__, __LINE__, $this->db->error());

        while ($cur_post = $this->db->fetch_assoc($result)) {
            $cur_post['message'] = parse_message($cur_post['message'], $cur_post['hide_smilies']);
            $post_data[] = $cur_post;
        }
        return $post_data;
    }
}