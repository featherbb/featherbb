<?php

/**
 * Copyright (C) 2015-2016 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace FeatherBB\Model\Api;

use FeatherBB\Core\Database as DB;
use FeatherBB\Core\Error;
use FeatherBB\Core\Track;
use FeatherBB\Core\Utils;
use FeatherBB\Core\Url;

class Topic extends Api
{
    public function display($id)
    {
        $topic = new \FeatherBB\Model\Topic();

        try {
            $data = $topic->get_info_topic($id);
        } catch (Error $e) {
            return $this->errorMessage;
        }

        $data = $data->as_array();

        $data['moderators'] = unserialize($data['moderators']);

        return $data;
    }

    //  Get some info about the post
    public function get_info_post($tid, $fid)
    {
        if (!$fid && !$tid) {
            return json_encode($this->errorMessage, JSON_PRETTY_PRINT);
        }

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

        $cur_posting = $cur_posting->find_one();

        if (!$cur_posting) {
            return json_encode($this->errorMessage, JSON_PRETTY_PRINT);
        }

        return $cur_posting;
    }

    public function checkPermissions($cur_posting, $tid, $fid)
    {
        // Is someone trying to post into a redirect forum?
        if ($cur_posting['redirect_url'] != '') {
            return json_encode($this->errorMessage, JSON_PRETTY_PRINT);
        }

        // Sort out who the moderators are and if we are currently a moderator (or an admin)
        $mods_array = ($cur_posting['moderators'] != '') ? unserialize($cur_posting['moderators']) : array();
        $is_admmod = ($this->user->g_id == ForumEnv::get('FEATHER_ADMIN') || ($this->user->g_moderator == '1' && array_key_exists($this->user->username, $mods_array))) ? true : false;

        // Do we have permission to post?
        if ((($tid && (($cur_posting['post_replies'] == '' && $this->user->g_post_replies == '0') || $cur_posting['post_replies'] == '0')) ||
                ($fid && (($cur_posting['post_topics'] == '' && $this->user->g_post_topics == '0') || $cur_posting['post_topics'] == '0')) ||
                (isset($cur_posting['closed']) && $cur_posting['closed'] == '1')) &&
            !$is_admmod) {
            return json_encode($this->errorMessage, JSON_PRETTY_PRINT);
        }

        return $is_admmod;
    }

    public function check_errors_before_post($fid, $errors)
    {
        // Flood protection
        if (Input::post('preview') != '' && $this->user->last_post != '' && (time() - $this->user->last_post) < Container::get('prefs')->get($this->user, 'post.min_interval')) {
            $errors[] = sprintf(__('Flood start'), Container::get('prefs')->get($this->user, 'post.min_interval'), Container::get('prefs')->get($this->user, 'post.min_interval') - (time() - $this->user->last_post));
        }

        // If it's a new topic
        if ($fid) {
            $subject = Utils::trim(Input::post('req_subject'));

            if (ForumSettings::get('o_censoring') == '1') {
                $censored_subject = Utils::trim(Utils::censor($subject));
            }

            if ($subject == '') {
                $errors[] = __('No subject');
            } elseif (ForumSettings::get('o_censoring') == '1' && $censored_subject == '') {
                $errors[] = __('No subject after censoring');
            } elseif (Utils::strlen($subject) > 70) {
                $errors[] = __('Too long subject');
            } elseif (ForumSettings::get('p_subject_all_caps') == '0' && Utils::is_all_uppercase($subject) && !$this->user->is_admmod) {
                $errors[] = __('All caps subject');
            }
        }

        if ($this->user->is_guest) {
            $email = strtolower(Utils::trim((ForumSettings::get('p_force_guest_email') == '1') ? Input::post('req_email') : Input::post('email')));

            if (ForumSettings::get('p_force_guest_email') == '1' || $email != '') {

                if (!Container::get('email')->is_valid_email($email)) {
                    $errors[] = __('Invalid email');
                }

                // Check if it's a banned email address
                // we should only check guests because members' addresses are already verified
                if ($this->user->is_guest && Container::get('email')->is_banned_email($email)) {
                    if (ForumSettings::get('p_allow_banned_email') == '0') {
                        $errors[] = __('Banned email');
                    }

                    $errors['banned_email'] = 1; // Used later when we send an alert email
                }
            }
        }

        // Clean up message from POST
        $message = Utils::linebreaks(Utils::trim(Input::post('req_message')));

        // Here we use strlen() not Utils::strlen() as we want to limit the post to FEATHER_MAX_POSTSIZE bytes, not characters
        if (strlen($message) > ForumEnv::get('FEATHER_MAX_POSTSIZE')) {
            $errors[] = sprintf(__('Too long message'), Utils::forum_number_format(ForumEnv::get('FEATHER_MAX_POSTSIZE')));
        } elseif (ForumSettings::get('p_message_all_caps') == '0' && Utils::is_all_uppercase($message) && !$this->user->is_admmod) {
            $errors[] = __('All caps message');
        }

        // Validate BBCode syntax
        if (ForumSettings::get('p_message_bbcode') == '1') {
            $message = Container::get('parser')->preparse_bbcode($message, $errors);
        }

        if (empty($errors)) {
            if ($message == '') {
                $errors[] = __('No message');
            } elseif (ForumSettings::get('o_censoring') == '1') {
                // Censor message to see if that causes problems
                $censored_message = Utils::trim(Utils::censor($message));

                if ($censored_message == '') {
                    $errors[] = __('No message after censoring');
                }
            }
        }

        return $errors;
    }

    // If the previous check went OK, setup some variables used later
    public function setup_variables($errors, $is_admmod)
    {
        $post = array();

        if (!$this->user->is_guest) {
            $post['username'] = $this->user->username;
            $post['email'] = $this->user->email;
        }
        // Otherwise it should be in $feather ($_POST)
        else {
            $post['username'] = Utils::trim(Input::post('req_username'));
            $post['email'] = strtolower(Utils::trim((ForumSettings::get('p_force_guest_email') == '1') ? Input::post('req_email') : Input::post('email')));
        }

        if (Input::post('req_subject')) {
            $post['subject'] = Utils::trim(Input::post('req_subject'));
        }

        $post['hide_smilies'] = Input::post('hide_smilies') ? '1' : '0';
        $post['subscribe'] = Input::post('subscribe') ? '1' : '0';
        $post['stick_topic'] = Input::post('stick_topic') && $is_admmod ? '1' : '0';

        $post['message']  = Utils::linebreaks(Utils::trim(Input::post('req_message')));

        // Validate BBCode syntax
        if (ForumSettings::get('p_message_bbcode') == '1') {
            $post['message']  = Container::get('parser')->preparse_bbcode($post['message'], $errors);
        }

        // Replace four-byte characters (MySQL cannot handle them)
        $post['message'] = Utils::strip_bad_multibyte_chars($post['message']);

        $post['time'] = time();

        return $post;
    }

    // Insert a topic
    public function insert_topic($post, $fid)
    {
        $new = array();

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
        $topic = $topic->save();

        $new['tid'] = DB::get_db()->lastInsertId(ForumSettings::get('db_prefix').'topics');

        if (!$this->user->is_guest) {
            // To subscribe or not to subscribe, that ...
            if (ForumSettings::get('o_topic_subscriptions') == '1' && $post['subscribe']) {

                $subscription['insert'] = array(
                    'user_id'   =>  $this->user->id,
                    'topic_id'  =>  $new['tid']
                );

                $subscription = DB::for_table('topic_subscriptions')
                    ->create()
                    ->set($subscription['insert']);
                $subscription = $subscription->save();

            }

            // Create the post ("topic post")
            $query['insert'] = array(
                'poster' => $post['username'],
                'poster_id' => $this->user->id,
                'poster_ip' => Utils::getIp(),
                'message' => $post['message'],
                'hide_smilies' => $post['hide_smilies'],
                'posted'  => $post['time'],
                'topic_id'  => $new['tid'],
            );

            $query = DB::for_table('posts')
                ->create()
                ->set($query['insert']);
            $query = $query->save();
        } else {
            // It's a guest
            // Create the post ("topic post")
            $query['insert'] = array(
                'poster' => $post['username'],
                'poster_ip' => Utils::getIp(),
                'message' => $post['message'],
                'hide_smilies' => $post['hide_smilies'],
                'posted'  => $post['time'],
                'topic_id'  => $new['tid'],
            );

            if (ForumSettings::get('p_force_guest_email') == '1' || $post['email'] != '') {
                $query['poster_email'] = $post['email'];
            }

            $query = DB::for_table('posts')
                ->create()
                ->set($query['insert']);
            $query = $query->save();
        }
        $new['pid'] = DB::get_db()->lastInsertId(ForumSettings::get('db_prefix').'topics');

        // Update the topic with last_post_id
        unset($topic);
        $topic['update'] = array(
            'last_post_id'  =>  $new['pid'],
            'first_post_id' =>  $new['pid'],
        );

        $topic = DB::for_table('topics')
            ->where('id', $new['tid'])
            ->find_one()
            ->set($topic['update']);
        $topic = $topic->save();

        $search = new \FeatherBB\Core\Search();

        $search->update_search_index('post', $new['pid'], $post['message'], $post['subject']);

        \FeatherBB\Model\Forum::update($fid);

        return $new;
    }

    // Send notifications for new topics
    public function send_notifications_new_topic($post, $cur_posting, $new_tid)
    {
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
        $result = $result->find_many();

        if ($result) {
            $notification_emails = array();

            $censored_message = Utils::trim(Utils::censor($post['message']));
            $censored_subject = Utils::trim(Utils::censor($post['subject']));

            if (ForumSettings::get('o_censoring') == '1') {
                $cleaned_message = Container::get('email')->bbcode2email($censored_message, -1);
            } else {
                $cleaned_message = Container::get('email')->bbcode2email($post['message'], -1);
            }

            $cleaned_subject = ForumSettings::get('o_censoring') == '1' ? $censored_subject : $post['subject'];

            // Loop through subscribed users and send emails
            foreach($result as $cur_subscriber) {
                // Is the subscription email for $cur_subscriber['language'] cached or not?
                if (!isset($notification_emails[$cur_subscriber['language']])) {
                    if (file_exists(ForumEnv::get('FEATHER_ROOT').'featherbb/lang/'.$cur_subscriber['language'].'/mail_templates/new_topic.tpl')) {
                        // Load the "new topic" template
                        $mail_tpl = trim(file_get_contents(ForumEnv::get('FEATHER_ROOT').'featherbb/lang/'.$cur_subscriber['language'].'/mail_templates/new_topic.tpl'));

                        // Load the "new topic full" template (with post included)
                        $mail_tpl_full = trim(file_get_contents(ForumEnv::get('FEATHER_ROOT').'featherbb/lang/'.$cur_subscriber['language'].'/mail_templates/new_topic_full.tpl'));

                        // The first row contains the subject (it also starts with "Subject:")
                        $first_crlf = strpos($mail_tpl, "\n");
                        $mail_subject = trim(substr($mail_tpl, 8, $first_crlf-8));
                        $mail_message = trim(substr($mail_tpl, $first_crlf));

                        $first_crlf = strpos($mail_tpl_full, "\n");
                        $mail_subject_full = trim(substr($mail_tpl_full, 8, $first_crlf-8));
                        $mail_message_full = trim(substr($mail_tpl_full, $first_crlf));

                        $mail_subject = str_replace('<forum_name>', $cur_posting['forum_name'], $mail_subject);
                        $mail_message = str_replace('<topic_subject>', $cleaned_subject, $mail_message);
                        $mail_message = str_replace('<forum_name>', $cur_posting['forum_name'], $mail_message);
                        $mail_message = str_replace('<poster>', $post['username'], $mail_message);
                        $mail_message = str_replace('<topic_url>', Router::pathFor('Topic', ['id' => $new_tid, 'name' => Url::url_friendly($post['subject'])]), $mail_message);
                        $mail_message = str_replace('<unsubscribe_url>', Router::pathFor('unsubscribeTopic', ['id' => $cur_posting['id'], 'name' => Url::url_friendly($post['subject'])]), $mail_message);
                        $mail_message = str_replace('<board_mailer>', ForumSettings::get('o_board_title'), $mail_message);

                        $mail_subject_full = str_replace('<forum_name>', $cur_posting['forum_name'], $mail_subject_full);
                        $mail_message_full = str_replace('<topic_subject>', $cleaned_subject, $mail_message_full);
                        $mail_message_full = str_replace('<forum_name>', $cur_posting['forum_name'], $mail_message_full);
                        $mail_message_full = str_replace('<poster>', $post['username'], $mail_message_full);
                        $mail_message_full = str_replace('<message>', $cleaned_message, $mail_message_full);
                        $mail_message_full = str_replace('<topic_url>', Router::pathFor('Topic', ['id' => $new_tid, 'name' => Url::url_friendly($post['subject'])]), $mail_message_full);
                        $mail_message_full = str_replace('<unsubscribe_url>', Router::pathFor('unsubscribeTopic', ['id' => $tid, 'name' => Url::url_friendly($post['subject'])]), $mail_message_full);
                        $mail_message_full = str_replace('<board_mailer>', ForumSettings::get('o_board_title'), $mail_message_full);

                        $notification_emails[$cur_subscriber['language']][0] = $mail_subject;
                        $notification_emails[$cur_subscriber['language']][1] = $mail_message;
                        $notification_emails[$cur_subscriber['language']][2] = $mail_subject_full;
                        $notification_emails[$cur_subscriber['language']][3] = $mail_message_full;
                    }
                }

                // We have to double check here because the templates could be missing
                if (isset($notification_emails[$cur_subscriber['language']])) {
                    if ($cur_subscriber['notify_with_post'] == '0') {
                        Container::get('email')->feather_mail($cur_subscriber['email'], $notification_emails[$cur_subscriber['language']][0], $notification_emails[$cur_subscriber['language']][1]);
                    } else {
                        Container::get('email')->feather_mail($cur_subscriber['email'], $notification_emails[$cur_subscriber['language']][2], $notification_emails[$cur_subscriber['language']][3]);
                    }
                }
            }

            unset($cleaned_message);
        }
    }

    // Increment post count, change group if needed
    public function increment_post_count($post, $new_tid)
    {
        if (!$this->user->is_guest) {
            $increment = DB::for_table('users')
                ->where('id', $this->user->id)
                ->find_one()
                ->set('last_post', $post['time'])
                ->set_expr('num_posts', 'num_posts+1');
            $increment = $increment->save();

            // Promote this user to a new group if enabled
            if ($this->user->g_promote_next_group != 0 && $this->user->num_posts + 1 >= $this->user->g_promote_min_posts) {
                $new_group_id = $this->user->g_promote_next_group;
                $promote = DB::for_table('users')
                    ->where('id', $this->user->id)
                    ->find_one()
                    ->set('group_id', $new_group_id);
                $promote = $promote->save();
            }

            // Topic tracking stuff...
            $tracked_topics = Track::get_tracked_topics();
            $tracked_topics['topics'][$new_tid] = time();
            Track::set_tracked_topics($tracked_topics);
        } else {
            // Update the last_post field for guests
            $last_post = DB::for_table('online')
                ->where('ident', Utils::getIp())
                ->find_one()
                ->set('last_post', $post['time']);
            $last_post = $last_post->save();
        }
    }

    // Insert a reply
    public function insert_reply($post, $tid, $cur_posting, $is_subscribed)
    {
        $new = array();

        $new = Container::get('hooks')->fireDB('model.post.insert_reply_start', $new, $post, $tid, $cur_posting, $is_subscribed);

        if (!$this->user->is_guest) {
            $new['tid'] = $tid;

            // Insert the new post
            $query['insert'] = array(
                'poster' => $post['username'],
                'poster_id' => $this->user->id,
                'poster_ip' => Utils::getIp(),
                'message' => $post['message'],
                'hide_smilies' => $post['hide_smilies'],
                'posted'  => $post['time'],
                'topic_id'  => $tid,
            );

            $query = DB::for_table('posts')
                ->create()
                ->set($query['insert']);
            $query = Container::get('hooks')->fireDB('model.post.insert_reply_guest_query', $query);
            $query = $query->save();

            $new['pid'] = DB::get_db()->lastInsertId(ForumSettings::get('db_prefix').'posts');

            // To subscribe or not to subscribe, that ...
            if (ForumSettings::get('o_topic_subscriptions') == '1') {
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
                    $subscription = Container::get('hooks')->fireDB('model.post.insert_reply_subscription', $subscription);
                    $subscription = $subscription->save();

                    // We reply and we don't want to be subscribed anymore
                } elseif ($post['subscribe'] == '0' && $is_subscribed) {

                    $unsubscription = DB::for_table('topic_subscriptions')
                        ->where('user_id', $this->user->id)
                        ->where('topic_id', $tid);
                    $unsubscription = Container::get('hooks')->fireDB('model.post.insert_reply_unsubscription', $unsubscription);
                    $unsubscription = $unsubscription->delete_many();

                }
            }
        } else {
            // It's a guest. Insert the new post
            $query['insert'] = array(
                'poster' => $post['username'],
                'poster_ip' => Utils::getIp(),
                'message' => $post['message'],
                'hide_smilies' => $post['hide_smilies'],
                'posted'  => $post['time'],
                'topic_id'  => $tid,
            );

            if (ForumSettings::get('p_force_guest_email') == '1' || $post['email'] != '') {
                $query['insert']['poster_email'] = $post['email'];
            }

            $query = DB::for_table('posts')
                ->create()
                ->set($query['insert']);
            $query = Container::get('hooks')->fireDB('model.post.insert_reply_member_query', $query);
            $query = $query->save();

            $new['pid'] = DB::get_db()->lastInsertId(ForumSettings::get('db_prefix').'posts');
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
        $topic = Container::get('hooks')->fireDB('model.post.insert_reply_update_query', $topic);

        // Get topic subject to redirect
        $new['topic_subject'] = Url::url_friendly($topic->subject);

        $topic = $topic->save();

        $search = new \FeatherBB\Core\Search();

        $search->update_search_index('post', $new['pid'], $post['message']);

        \FeatherBB\Model\Forum::update($cur_posting['id']);

        $new = Container::get('hooks')->fireDB('model.post.insert_reply', $new);

        return $new;
    }

}
