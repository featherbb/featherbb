<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace model;

use DB;

class post
{
    public function __construct()
    {
        $this->feather = \Slim\Slim::getInstance();
        $this->start = $this->feather->start;
        $this->config = $this->feather->config;
        $this->user = $this->feather->user;
        $this->request = $this->feather->request;
        $this->hook = $this->feather->hooks;
        $this->email = $this->feather->email;
        $this->search = new \FeatherBB\Search();
    }
 
    //  Get some info about the post
    public function get_info_post($tid, $fid)
    {
        $this->hook->fire('get_info_post_start', $tid, $fid);

        $cur_posting['where'] = array(
            array('fp.read_forum' => 'IS NULL'),
            array('fp.read_forum' => '1')
        );

        if ($tid) {
            $cur_posting['select'] = array('f.id', 'f.forum_name', 'f.moderators', 'f.redirect_url', 'fp.post_replies', 'fp.post_topics', 't.subject', 't.closed', 'is_subscribed' => 's.user_id');

            $cur_posting = DB::for_table('topics')
                            ->table_alias('t')
                            ->select_many($cur_posting['select'])
                            ->inner_join('forums', array('f.id', '=', 't.forum_id'), 'f')
                            ->left_outer_join('forum_perms', array('fp.forum_id', '=', 'f.id'), 'fp')
                            ->left_outer_join('forum_perms', array('fp.group_id', '=', $this->user->g_id), null, true)
                            ->left_outer_join('topic_subscriptions', array('t.id', '=', 's.topic_id'), 's')
                            ->left_outer_join('topic_subscriptions', array('s.user_id', '=', $this->user->id), null, true)
                            ->where_any_is($cur_posting['where'])
                            ->where('t.id', $tid);

        } else {
            $cur_posting['select'] = array('f.id', 'f.forum_name', 'f.moderators', 'f.redirect_url', 'fp.post_replies', 'fp.post_topics');

            $cur_posting = DB::for_table('forums')
                            ->table_alias('f')
                            ->select_many($cur_posting['select'])
                            ->left_outer_join('forum_perms', array('fp.forum_id', '=', 'f.id'), 'fp')
                            ->left_outer_join('forum_perms', array('fp.group_id', '=', $this->user->g_id), null, true)
                            ->where_any_is($cur_posting['where'])
                            ->where('f.id', $fid);
        }

        $cur_posting = $this->hook->fireDB('get_info_post_query', $cur_posting);
        $cur_posting = $cur_posting->find_one();

        if (!$cur_posting) {
            message(__('Bad request'), '404');
        }

        $cur_posting = $this->hook->fire('get_info_post', $cur_posting);

        return $cur_posting;
    }

    // Checks the post for errors before posting
    public function check_errors_before_post($fid, $tid, $qid, $pid, $page, $errors)
    {
        global $lang_antispam, $lang_antispam_questions, $pd;

        $fid = $this->hook->fire('check_errors_before_post_start', $fid);

        // Antispam feature
        if ($this->user->is_guest) {

            // It's a guest, so we have to validate the username
            $errors = check_username(feather_trim($this->request->post('req_username')), $errors);

            $errors = $this->hook->fire('check_errors_before_post_antispam', $errors);

            $question = $this->request->post('captcha_q') ? trim($this->request->post('captcha_q')) : '';
            $answer = $this->request->post('captcha') ? strtoupper(trim($this->request->post('captcha'))) : '';
            $lang_antispam_questions_array = array();

            foreach ($lang_antispam_questions as $k => $v) {
                $lang_antispam_questions_array[md5($k)] = strtoupper($v);
            }

            if (empty($lang_antispam_questions_array[$question]) || $lang_antispam_questions_array[$question] != $answer) {
                $errors[] = __('Robot test fail');
            }
        }

        // Flood protection
        if ($this->request->post('preview') != '' && $this->user->last_post != '' && (time() - $this->user->last_post) < $this->user->g_post_flood) {
            $errors[] = sprintf(__('Flood start'), $this->user->g_post_flood, $this->user->g_post_flood - (time() - $this->user->last_post));
        }

        // If it's a new topic
        if ($fid) {
            $subject = feather_trim($this->request->post('req_subject'));
            $subject = $this->hook->fire('check_errors_before_new_topic_subject', $subject);

            if ($this->config['o_censoring'] == '1') {
                $censored_subject = feather_trim(censor_words($subject));
                $censored_subject = $this->hook->fire('check_errors_before_censored', $censored_subject);
            }

            if ($subject == '') {
                $errors[] = __('No subject');
            } elseif ($this->config['o_censoring'] == '1' && $censored_subject == '') {
                $errors[] = __('No subject after censoring');
            } elseif (feather_strlen($subject) > 70) {
                $errors[] = __('Too long subject');
            } elseif ($this->config['p_subject_all_caps'] == '0' && is_all_uppercase($subject) && !$this->user->is_admmod) {
                $errors[] = __('All caps subject');
            }

            $errors = $this->hook->fire('check_errors_before_new_topic_errors', $errors);
        }

        if ($this->user->is_guest) {
            $email = strtolower(feather_trim(($this->config['p_force_guest_email'] == '1') ? $this->request->post('req_email') : $this->request->post('email')));

            if ($this->config['p_force_guest_email'] == '1' || $email != '') {
                $errors = $this->hook->fire('check_errors_before_post_email', $errors, $email);

                if (!$this->email->is_valid_email($email)) {
                    $errors[] = __('Invalid email');
                }

                // Check if it's a banned email address
                // we should only check guests because members' addresses are already verified
                if ($this->user->is_guest && $this->email->is_banned_email($email)) {
                    if ($this->config['p_allow_banned_email'] == '0') {
                        $errors[] = __('Banned email');
                    }

                    $errors['banned_email'] = 1; // Used later when we send an alert email
                }
            }
        }

        // Clean up message from POST
        $message = feather_linebreaks(feather_trim($this->request->post('req_message')));
        $message = $this->hook->fire('check_errors_before_post_message', $message);

        // Here we use strlen() not feather_strlen() as we want to limit the post to FEATHER_MAX_POSTSIZE bytes, not characters
        if (strlen($message) > FEATHER_MAX_POSTSIZE) {
            $errors[] = sprintf(__('Too long message'), forum_number_format(FEATHER_MAX_POSTSIZE));
        } elseif ($this->config['p_message_all_caps'] == '0' && is_all_uppercase($message) && !$this->user->is_admmod) {
            $errors[] = __('All caps message');
        }

        // Validate BBCode syntax
        if ($this->config['p_message_bbcode'] == '1') {
            require FEATHER_ROOT.'include/parser.php';
            $message = preparse_bbcode($message, $errors);
            $message = $this->hook->fire('check_errors_before_post_bbcode', $message);
        }

        if (empty($errors)) {
            $errors = $this->hook->fire('check_errors_before_post_no_error', $errors);
            if ($message == '') {
                $errors[] = __('No message');
            } elseif ($this->config['o_censoring'] == '1') {
                // Censor message to see if that causes problems
                $censored_message = feather_trim(censor_words($message));

                if ($censored_message == '') {
                    $errors[] = __('No message after censoring');
                }
            }
        }

        $errors = $this->hook->fire('check_errors_before_post', $errors);

        return $errors;
    }

    // If the previous check went OK, setup some variables used later
    public function setup_variables($errors, $is_admmod)
    {
        $post = array();

        $post = $this->hook->fire('setup_variables_start', $post, $errors, $is_admmod);

        if (!$this->user->is_guest) {
            $post['username'] = $this->user->username;
            $post['email'] = $this->user->email;
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

        $post = $this->hook->fire('setup_variables', $post);

        return $post;
    }

    // Insert a reply
    public function insert_reply($post, $tid, $cur_posting, $is_subscribed)
    {
        $new = array();

        $new = $this->hook->fireDB('insert_reply_start', $new, $post, $tid, $cur_posting, $is_subscribed);

        if (!$this->user->is_guest) {
            $new['tid'] = $tid;

            // Insert the new post
            $query['insert'] = array(
                'poster' => $post['username'],
                'poster_id' => $this->user->id,
                'poster_ip' => $this->request->getIp(),
                'message' => $post['message'],
                'hide_smilies' => $post['hide_smilies'],
                'posted'  => $post['time'],
                'topic_id'  => $tid,
            );

            $query = DB::for_table('posts')
                        ->create()
                        ->set($query['insert']);
            $query = $this->hook->fireDB('insert_reply_guest_query', $query);
            $query = $query->save();

            $new['pid'] = DB::get_db()->lastInsertId($this->feather->forum_settings['db_prefix'].'posts');

            // To subscribe or not to subscribe, that ...
            if ($this->config['o_topic_subscriptions'] == '1') {
                // ... is the question
                // Let's do it
                if (isset($post['subscribe']) && $post['subscribe'] && !$is_subscribed) {

                    $subscription['insert'] = array(
                        'user_id'   =>  $this->user->id,
                        'topic_id'  =>  $tid
                    );

                    $subscription = DB::for_table('topic_subscriptions')
                                        ->create()
                                        ->set($subscription['insert']);
                    $subscription = $this->hook->fireDB('insert_reply_subscription', $subscription);
                    $subscription = $subscription->save();

                // We reply and we don't want to be subscribed anymore
                } elseif ($post['subscribe'] == '0' && $is_subscribed) {

                    $unsubscription = DB::for_table('topic_subscriptions')
                                        ->where('user_id', $this->user->id)
                                        ->where('topic_id', $tid);
                    $unsubscription = $this->hook->fireDB('insert_reply_unsubscription', $unsubscription);
                    $unsubscription = $unsubscription->delete_many();

                }
            }
        } else {
            // It's a guest. Insert the new post
            $query['insert'] = array(
                'poster' => $post['username'],
                'poster_ip' => $this->request->getIp(),
                'message' => $post['message'],
                'hide_smilies' => $post['hide_smilies'],
                'posted'  => $post['time'],
                'topic_id'  => $tid,
            );

            if ($this->config['p_force_guest_email'] == '1' || $post['email'] != '') {
                $query['insert']['poster_email'] = $post['email'];
            }

            $query = DB::for_table('posts')
                        ->create()
                        ->set($query['insert']);
            $query = $this->hook->fireDB('insert_reply_member_query', $query);
            $query = $query->save();

            $new['pid'] = DB::get_db()->lastInsertId($this->feather->forum_settings['db_prefix'].'posts');
        }

        // Update topic
        $topic['update'] = array(
            'last_post' => $post['time'],
            'last_post_id'  => $new['pid'],
            'last_poster'  => $post['username'],
        );

        $topic = DB::for_table('topics')
                    ->where('id', $tid)
                    ->find_one()
                    ->set($topic['update'])
                    ->set_expr('num_replies', 'num_replies+1');
        $topic = $this->hook->fireDB('insert_reply_update_query', $topic);
        $topic = $topic->save();

        $this->search->update_search_index('post', $new['pid'], $post['message']);

        update_forum($cur_posting['id']);

        $new = $this->hook->fireDB('insert_reply', $new);

        return $new;
    }

    // Send notifications for replies
    public function send_notifications_reply($tid, $cur_posting, $new_pid, $post)
    {
        $this->hook->fire('send_notifications_reply_start', $tid, $cur_posting, $new_pid, $post);

        // Get the post time for the previous post in this topic
        $previous_post_time = DB::for_table('posts')
                                ->where('topic_id', $tid)
                                ->order_by_desc('id');
        $previous_post_time = $this->hook->fireDB('send_notifications_reply_previous', $previous_post_time);
        $previous_post_time = $previous_post_time->find_one_col('posted');

        // Get any subscribed users that should be notified (banned users are excluded)
        $result['where'] = array(
            array('fp.read_forum' => 'IS NULL'),
            array('fp.read_forum' => '1')
        );
        $result['select'] = array('u.id', 'u.email', 'u.notify_with_post', 'u.language');

        $result = DB::for_table('users')
                    ->table_alias('u')
                    ->select_many($result['select'])
                    ->inner_join('topic_subscriptions', array('u.id', '=', 's.user_id'), 's')
                    ->left_outer_join('forum_perms', array('fp.forum_id', '=', $cur_posting['id']), 'fp', true)
                    ->left_outer_join('forum_perms', array('fp.group_id', '=', 'u.group_id'))
                    ->left_outer_join('online', array('u.id', '=', 'o.user_id'), 'o')
                    ->left_outer_join('bans', array('u.username', '=', 'b.username'), 'b')
                    ->where_raw('COALESCE(o.logged, u.last_visit)>'.$previous_post_time)
                    ->where_null('b.username')
                    ->where_any_is($result['where'])
                    ->where('s.topic_id', $tid)
                    ->where_not_equal('u.id', $this->user->id);
        $result = $this->hook->fireDB('send_notifications_reply_query', $result);
        $result = $result->find_many();

        if ($result) {
            $notification_emails = array();

            $censored_message = feather_trim(censor_words($post['message']));

            if ($this->config['o_censoring'] == '1') {
                $cleaned_message = $this->email->bbcode2email($censored_message, -1);
            } else {
                $cleaned_message = $this->email->bbcode2email($post['message'], -1);
            }

            // Loop through subscribed users and send emails
            foreach($result as $cur_subscriber) {
                // Is the subscription email for $cur_subscriber['language'] cached or not?
                if (!isset($notification_emails[$cur_subscriber['language']])) {
                    if (file_exists(FEATHER_ROOT.'lang/'.$cur_subscriber['language'].'/mail_templates/new_reply.tpl')) {
                        // Load the "new reply" template
                        $mail_tpl = trim(file_get_contents(FEATHER_ROOT.'lang/'.$cur_subscriber['language'].'/mail_templates/new_reply.tpl'));
                        $mail_tpl = $this->hook->fire('send_notifications_reply_mail_tpl', $mail_tpl);

                        // Load the "new reply full" template (with post included)
                        $mail_tpl_full = trim(file_get_contents(FEATHER_ROOT.'lang/'.$cur_subscriber['language'].'/mail_templates/new_reply_full.tpl'));
                        $mail_tpl_full = $this->hook->fire('send_notifications_reply_mail_tpl_full', $mail_tpl_full);

                        // The first row contains the subject (it also starts with "Subject:")
                        $first_crlf = strpos($mail_tpl, "\n");
                        $mail_subject = trim(substr($mail_tpl, 8, $first_crlf-8));
                        $mail_subject = $this->hook->fire('send_notifications_reply_mail_subject', $mail_subject);
                        $mail_message = trim(substr($mail_tpl, $first_crlf));

                        $first_crlf = strpos($mail_tpl_full, "\n");
                        $mail_subject_full = trim(substr($mail_tpl_full, 8, $first_crlf-8));
                        $mail_message_full = trim(substr($mail_tpl_full, $first_crlf));

                        $mail_subject = str_replace('<topic_subject>', $cur_posting['subject'], $mail_subject);
                        $mail_message = str_replace('<topic_subject>', $cur_posting['subject'], $mail_message);
                        $mail_message = str_replace('<replier>', $post['username'], $mail_message);
                        $mail_message = str_replace('<post_url>', get_link('post/'.$new_pid.'/#p'.$new_pid), $mail_message);
                        $mail_message = str_replace('<unsubscribe_url>', get_link('unsubscribe/topic/'.$tid.'/'), $mail_message);
                        $mail_message = str_replace('<board_mailer>', $this->config['o_board_title'], $mail_message);
                        $mail_message = $this->hook->fire('send_notifications_reply_mail_message', $mail_message);

                        $mail_subject_full = str_replace('<topic_subject>', $cur_posting['subject'], $mail_subject_full);
                        $mail_message_full = str_replace('<topic_subject>', $cur_posting['subject'], $mail_message_full);
                        $mail_message_full = str_replace('<replier>', $post['username'], $mail_message_full);
                        $mail_message_full = str_replace('<message>', $cleaned_message, $mail_message_full);
                        $mail_message_full = str_replace('<post_url>', get_link('post/'.$new_pid.'/#p'.$new_pid), $mail_message_full);
                        $mail_message_full = str_replace('<unsubscribe_url>', get_link('unsubscribe/topic/'.$tid.'/'), $mail_message_full);
                        $mail_message_full = str_replace('<board_mailer>', $this->config['o_board_title'], $mail_message_full);
                        $mail_message_full = $this->hook->fire('send_notifications_reply_mail_message_full', $mail_message_full);

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
                        $this->email->feather_mail($cur_subscriber['email'], $notification_emails[$cur_subscriber['language']][0], $notification_emails[$cur_subscriber['language']][1]);
                    } else {
                        $this->email->feather_mail($cur_subscriber['email'], $notification_emails[$cur_subscriber['language']][2], $notification_emails[$cur_subscriber['language']][3]);
                    }
                }
            }

            $this->hook->fire('send_notifications_reply');

            unset($cleaned_message);
        }
    }

    // Insert a topic
    public function insert_topic($post, $fid)
    {
        $new = array();

        $new = $this->hook->fireDB('insert_topic_start', $new, $post, $fid);

        // Create the topic
        $topic['insert'] = array(
            'poster' => $post['username'],
            'subject' => $post['subject'],
            'posted'  => $post['time'],
            'last_post'  => $post['time'],
            'last_poster'  => $post['username'],
            'sticky'  => $post['stick_topic'],
            'forum_id'  => $fid,
        );

        $topic = DB::for_table('topics')
                    ->create()
                    ->set($topic['insert']);
        $topic = $this->hook->fireDB('insert_topic_create', $topic);
        $topic = $topic->save();

        $new['tid'] = DB::get_db()->lastInsertId($this->feather->forum_settings['db_prefix'].'topics');

        if (!$this->user->is_guest) {
            // To subscribe or not to subscribe, that ...
            if ($this->config['o_topic_subscriptions'] == '1' && $post['subscribe']) {

                $subscription['insert'] = array(
                    'user_id'   =>  $this->user->id,
                    'topic_id'  =>  $new['tid']
                );

                $subscription = DB::for_table('topic_subscriptions')
                                    ->create()
                                    ->set($subscription['insert']);
                $subscription = $this->hook->fireDB('insert_topic_subscription_member', $subscription);
                $subscription = $subscription->save();

            }

            // Create the post ("topic post")
            $query['insert'] = array(
                'poster' => $post['username'],
                'poster_id' => $this->user->id,
                'poster_ip' => $this->request->getIp(),
                'message' => $post['message'],
                'hide_smilies' => $post['hide_smilies'],
                'posted'  => $post['time'],
                'topic_id'  => $new['tid'],
            );

            $query = DB::for_table('posts')
                        ->create()
                        ->set($query['insert']);
            $query = $this->hook->fireDB('insert_topic_post_member', $query);
            $query = $query->save();
        } else {
            // It's a guest
            // Create the post ("topic post")
            $query['insert'] = array(
                'poster' => $post['username'],
                'poster_ip' => $this->request->getIp(),
                'message' => $post['message'],
                'hide_smilies' => $post['hide_smilies'],
                'posted'  => $post['time'],
                'topic_id'  => $new['tid'],
            );

            if ($this->config['p_force_guest_email'] == '1' || $post['email'] != '') {
                $query['poster_email'] = $post['email'];
            }

            $query = DB::for_table('posts')
                ->create()
                ->set($query['insert']);
            $query = $this->hook->fireDB('insert_topic_post_member', $query);
            $query = $query->save();
        }
        $new['pid'] = DB::get_db()->lastInsertId($this->feather->forum_settings['db_prefix'].'topics');

        // Update the topic with last_post_id
        $topic['update'] = array(
            'last_post_id'  =>  $new['pid'],
            'first_post_id' =>  $new['pid'],
        );

        $topic = DB::for_table('topics')
                    ->where('id', $new['tid'])
                    ->find_one()
                    ->set($topic['update']);
        $topic = $this->hook->fireDB('insert_topic_post_topic', $topic);
        $topic = $topic->save();

        $this->search->update_search_index('post', $new['pid'], $post['message'], $post['subject']);

        update_forum($fid);

        $new = $this->hook->fireDB('insert_topic', $new);

        return $new;
    }

    // Send notifications for new topics
    public function send_notifications_new_topic($post, $cur_posting, $new_tid)
    {
        $this->hook->fire('send_notifications_new_topic_start', $post, $cur_posting, $new_tid);

        // Get any subscribed users that should be notified (banned users are excluded)
        $result['where'] = array(
            array('fp.read_forum' => 'IS NULL'),
            array('fp.read_forum' => '1')
        );
        $result['select'] = array('u.id', 'u.email', 'u.notify_with_post', 'u.language');

        $result = DB::for_table('users')
                    ->table_alias('u')
                    ->select_many($result['select'])
                    ->inner_join('forum_subscriptions', array('u.id', '=', 's.user_id'), 's')
                    ->left_outer_join('forum_perms', array('fp.forum_id', '=', $cur_posting['id']), 'fp', true)
                    ->left_outer_join('forum_perms', array('fp.group_id', '=', 'u.group_id'))
                    ->left_outer_join('bans', array('u.username', '=', 'b.username'), 'b')
                    ->where_null('b.username')
                    ->where_any_is($result['where'])
                    ->where('s.forum_id', $cur_posting['id'])
                    ->where_not_equal('u.id', $this->user->id);
        $result = $this->hook->fireDB('send_notifications_new_topic_query');
        $result = $result->find_many();

        if ($result) {
            $notification_emails = array();

            $censored_message = feather_trim(censor_words($post['message']));
            $censored_subject = feather_trim(censor_words($post['subject']));

            if ($this->config['o_censoring'] == '1') {
                $cleaned_message = $this->email->bbcode2email($censored_message, -1);
            } else {
                $cleaned_message = $this->email->bbcode2email($post['message'], -1);
            }

            // Loop through subscribed users and send emails
            foreach($result as $cur_subscriber) {
                // Is the subscription email for $cur_subscriber['language'] cached or not?
                if (!isset($notification_emails[$cur_subscriber['language']])) {
                    if (file_exists(FEATHER_ROOT.'lang/'.$cur_subscriber['language'].'/mail_templates/new_topic.tpl')) {
                        // Load the "new topic" template
                        $mail_tpl = trim(file_get_contents(FEATHER_ROOT.'lang/'.$cur_subscriber['language'].'/mail_templates/new_topic.tpl'));
                        $mail_tpl = $this->hook->fire('send_notifications_new_topic_mail_tpl', $mail_tpl);

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
                        $mail_message = str_replace('<unsubscribe_url>', get_link('unsubscribe/topic/'.$cur_posting['id'].'/'), $mail_message);
                        $mail_message = str_replace('<board_mailer>', $this->config['o_board_title'], $mail_message);
                        $mail_message = $this->hook->fire('send_notifications_new_topic_mail_message', $mail_message);

                        $mail_subject_full = str_replace('<forum_name>', $cur_posting['forum_name'], $mail_subject_full);
                        $mail_message_full = str_replace('<topic_subject>', $this->config['o_censoring'] == '1' ? $censored_subject : $post['subject'], $mail_message_full);
                        $mail_message_full = str_replace('<forum_name>', $cur_posting['forum_name'], $mail_message_full);
                        $mail_message_full = str_replace('<poster>', $post['username'], $mail_message_full);
                        $mail_message_full = str_replace('<message>', $cleaned_message, $mail_message_full);
                        $mail_message_full = str_replace('<topic_url>', get_link('topic/'.$new_tid.'/'), $mail_message_full);
                        $mail_message_full = str_replace('<unsubscribe_url>', get_link('unsubscribe/topic/'.$cur_posting['id'].'/'), $mail_message_full);
                        $mail_message_full = str_replace('<board_mailer>', $this->config['o_board_title'], $mail_message_full);
                        $mail_message_full = $this->hook->fire('send_notifications_new_topic_mail_message_full', $mail_message_full);

                        $notification_emails[$cur_subscriber['language']][0] = $mail_subject;
                        $notification_emails[$cur_subscriber['language']][1] = $mail_message;
                        $notification_emails[$cur_subscriber['language']][2] = $mail_subject_full;
                        $notification_emails[$cur_subscriber['language']][3] = $mail_message_full;
                    }
                }

                // We have to double check here because the templates could be missing
                if (isset($notification_emails[$cur_subscriber['language']])) {
                    if ($cur_subscriber['notify_with_post'] == '0') {
                        $this->email->feather_mail($cur_subscriber['email'], $notification_emails[$cur_subscriber['language']][0], $notification_emails[$cur_subscriber['language']][1]);
                    } else {
                        $this->email->feather_mail($cur_subscriber['email'], $notification_emails[$cur_subscriber['language']][2], $notification_emails[$cur_subscriber['language']][3]);
                    }
                }
            }

            $this->hook->fire('send_notifications_new_topic');

            unset($cleaned_message);
        }
    }

    // Warn the admin if a banned user posts
    public function warn_banned_user($post, $new_pid)
    {
        $this->hook->fire('warn_banned_user_start', $post, $new_pid);

        // Load the "banned email post" template
        $mail_tpl = trim(file_get_contents(FEATHER_ROOT.'lang/'.$this->user->language.'/mail_templates/banned_email_post.tpl'));
        $mail_tpl = $this->hook->fire('warn_banned_user_mail_tpl', $mail_tpl);

        // The first row contains the subject
        $first_crlf = strpos($mail_tpl, "\n");
        $mail_subject = trim(substr($mail_tpl, 8, $first_crlf-8));
        $mail_message = trim(substr($mail_tpl, $first_crlf));

        $mail_message = str_replace('<username>', $post['username'], $mail_message);
        $mail_message = str_replace('<email>', $post['email'], $mail_message);
        $mail_message = str_replace('<post_url>', get_link('post/'.$new_pid.'/#p'.$new_pid), $mail_message);
        $mail_message = str_replace('<board_mailer>', $this->config['o_board_title'], $mail_message);
        $mail_message = $this->hook->fire('warn_banned_user_mail_message', $mail_message);

        $this->email->feather_mail($this->config['o_mailing_list'], $mail_subject, $mail_message);
    }

    // Increment post count, change group if needed
    public function increment_post_count($post, $new_tid)
    {
        $this->hook->fire('increment_post_count_start', $post, $new_tid);

        if (!$this->user->is_guest) {
            $increment = DB::for_table('users')
                            ->where('id', $this->user->id)
                            ->find_one()
                            ->set('last_post', $post['time'])
                            ->set_expr('num_posts', 'num_posts+1');
            $increment = $this->hook->fireDB('increment_post_count_query', $increment);
            $increment = $increment->save();

            // Promote this user to a new group if enabled
            if ($this->user->g_promote_next_group != 0 && $this->user->num_posts + 1 >= $this->user->g_promote_min_posts) {
                $new_group_id = $this->user->g_promote_next_group;
                $promote = DB::for_table('users')
                            ->where('id', $this->user->id)
                            ->find_one()
                            ->set('group_id', $new_group_id);
                $promote = $this->hook->fireDB('increment_post_count_query', $promote);
                $promote = $promote->save();
            }

            // Topic tracking stuff...
            $tracked_topics = get_tracked_topics();
            $tracked_topics['topics'][$new_tid] = time();
            set_tracked_topics($tracked_topics);
        } else {
            // Update the last_post field for guests
            $last_post = DB::for_table('online')
                            ->where('ident', $this->request->getIp())
                            ->find_one()
                            ->set('last_post', $post['time']);
            $last_post = $this->hook->fireDB('increment_post_count_last_post', $last_post);
            $last_post = $last_post->save();
        }

        $this->hook->fire('increment_post_count');
    }

    //
    // Split text into chunks ($inside contains all text inside $start and $end, and $outside contains all text outside)
    //
    public function split_text($text, $start, $end, $retab = true)
    {
        $result = array(0 => array(), 1 => array()); // 0 = inside, 1 = outside

        $result = $this->hook->fire('split_text_start', $result, $text, $start, $end, $retab);

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

        $result = $this->hook->fire('split_text_start', $result);

        return $result;
    }

    // If we are quoting a message
    public function get_quote_message($qid, $tid)
    {
        $quote = array();

        $quote = $this->hook->fire('get_quote_message', $quote, $qid, $tid);

        $quote['select'] = array('poster', 'message');

        $quote = DB::for_table('posts')->select_many($quote['select'])
                     ->where('id', $qid)
                     ->where('topic_id', $tid);
        $quote = $this->hook->fireDB('get_quote_message_query', $quote);
        $quote = $quote->find_one();

        if (!$quote) {
            message(__('Bad request'), '404');
        }

        // If the message contains a code tag we have to split it up (text within [code][/code] shouldn't be touched)
        if (strpos($quote['message'], '[code]') !== false && strpos($quote['message'], '[/code]') !== false) {
            list($inside, $outside) = split_text($quote['message'], '[code]', '[/code]');

            $quote['message'] = implode("\1", $outside);
        }

        // Remove [img] tags from quoted message
        $quote['message'] = preg_replace('%\[img(?:=(?:[^\[]*?))?\]((ht|f)tps?://)([^\s<"]*?)\[/img\]%U', '\1\3', $quote['message']);

        // If we split up the message before we have to concatenate it together again (code tags)
        if (isset($inside)) {
            $outside = explode("\1", $quote['message']);
            $quote['message'] = '';

            $num_tokens = count($outside);
            for ($i = 0; $i < $num_tokens; ++$i) {
                $quote['message'] .= $outside[$i];
                if (isset($inside[$i])) {
                    $quote['message'] .= '[code]'.$inside[$i].'[/code]';
                }
            }

            unset($inside);
        }

        if ($this->config['o_censoring'] == '1') {
            $quote['message'] = censor_words($quote['message']);
        }

        $quote['message'] = feather_escape($quote['message']);

        if ($this->config['p_message_bbcode'] == '1') {    // Sanitize username for inclusion within QUOTE BBCode attribute.
                //   This is a bit tricky because a username can have any "special"
                //   characters such as backslash \ square brackets [] and quotes '".
                if (preg_match('/[[\]\'"]/S', $quote['poster'])) {
                    // Check if we need to quote it.
                    // Post has special chars. Escape escapes and quotes then wrap in quotes.
                    if (strpos($quote['poster'], '"') !== false && strpos($quote['poster'], '\'') === false) { // If there are double quotes but no single quotes, use single quotes,
                        $quote['poster'] = feather_escape(str_replace('\\', '\\\\', $quote['poster']));
                        $quote['poster'] = '\''. $quote['poster'] .'#'. $qid .'\'';
                    } else { // otherwise use double quotes.
                        $quote['poster'] = feather_escape(str_replace(array('\\', '"'), array('\\\\', '\\"'), $quote['poster']));
                        $quote['poster'] = '"'. $quote['poster'] .'#'. $qid .'"';
                    }
                } else {
                    $quote['poster'] = $quote['poster'] .'#'. $qid;
                }
            $quote = '[quote='. $quote['poster'] .']'.$quote['message'].'[/quote]'."\n";
        } else {
            $quote = '> '.$quote['poster'].' '.__('wrote')."\n\n".'> '.$quote['message']."\n";
        }

        $quote = $this->hook->fire('get_quote_message', $quote);

        return $quote;
    }

    // Get the current state of checkboxes
    public function get_checkboxes($fid, $is_admmod, $is_subscribed)
    {
        $this->hook->fire('get_checkboxes_start', $fid, $is_admmod, $is_subscribed);

        $cur_index = 1;

        $checkboxes = array();
        if ($fid && $is_admmod) {
            $checkboxes[] = '<label><input type="checkbox" name="stick_topic" value="1" tabindex="'.($cur_index++).'"'.($this->request->post('stick_topic') ? ' checked="checked"' : '').' />'.__('Stick topic').'<br /></label>';
        }

        if (!$this->user->is_guest) {
            if ($this->config['o_smilies'] == '1') {
                $checkboxes[] = '<label><input type="checkbox" name="hide_smilies" value="1" tabindex="'.($cur_index++).'"'.($this->request->post('hide_smilies') ? ' checked="checked"' : '').' />'.__('Hide smilies').'<br /></label>';
            }

            if ($this->config['o_topic_subscriptions'] == '1') {
                $subscr_checked = false;

                // If it's a preview
                if ($this->request->post('preview')) {
                    $subscr_checked = ($this->request->post('subscribe')) ? true : false;
                }
                // If auto subscribed
                elseif ($this->user->auto_notify) {
                    $subscr_checked = true;
                }
                // If already subscribed to the topic
                elseif ($is_subscribed) {
                    $subscr_checked = true;
                }

                $checkboxes[] = '<label><input type="checkbox" name="subscribe" value="1" tabindex="'.($cur_index++).'"'.($subscr_checked ? ' checked="checked"' : '').' />'.($is_subscribed ? __('Stay subscribed') : __('Subscribe')).'<br /></label>';
            }
        } elseif ($this->config['o_smilies'] == '1') {
            $checkboxes[] = '<label><input type="checkbox" name="hide_smilies" value="1" tabindex="'.($cur_index++).'"'.($this->request->post('hide_smilies') ? ' checked="checked"' : '').' />'.__('Hide smilies').'<br /></label>';
        }

        $checkboxes = $this->hook->fire('get_checkboxes', $checkboxes);

        return $checkboxes;
    }

    // Display the topic review if needed
    public function topic_review($tid)
    {
        global $pd;

        $post_data = array();

        $post_data = $this->hook->fire('topic_review_start', $post_data, $tid);

        require_once FEATHER_ROOT.'include/parser.php';

        $select_topic_review = array('poster', 'message', 'hide_smilies', 'posted');

        $result = DB::for_table('posts')->select_many($select_topic_review)
                    ->where('topic_id', $tid)
                    ->order_by_desc('id');
        $result = $this->hook->fire('topic_review_query', $result);
        $result = $result->find_many();

        foreach($result as $cur_post) {
            $cur_post['message'] = parse_message($cur_post['message'], $cur_post['hide_smilies']);
            $post_data[] = $cur_post;
        }

        $post_data = $this->hook->fire('topic_review', $post_data);

        return $post_data;
    }
}
