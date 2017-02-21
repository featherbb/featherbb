<?php

/**
 * Copyright (C) 2015-2017 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace FeatherBB\Model\Api;

use FeatherBB\Core\Database as DB;
use FeatherBB\Core\Error;
use FeatherBB\Core\Interfaces\Container;
use FeatherBB\Core\Interfaces\ForumSettings;
use FeatherBB\Core\Interfaces\Hooks;
use FeatherBB\Core\Interfaces\Input;
use FeatherBB\Core\Interfaces\Router;
use FeatherBB\Core\Interfaces\User;
use FeatherBB\Core\Interfaces\User;
use FeatherBB\Core\Track;
use FeatherBB\Core\Url;
use FeatherBB\Core\Utils;

class Topic extends Api
{
    public function display($id)
    {
        $topic = new \FeatherBB\Model\Topic();

        try {
            $data = $topic->getInfoTopic($id);
        } catch (Error $e) {
            return $this->errorMessage;
        }

        $data = $data->asArray();

        $data['moderators'] = unserialize($data['moderators']);

        return $data;
    }

    //  Get some info about the post
    public function getInfoPost($tid, $fid)
    {
        if (!$fid && !$tid) {
            return json_encode($this->errorMessage, JSON_PRETTY_PRINT);
        }

        $curPosting['where'] = [
            ['fp.read_forum' => 'IS NULL'],
            ['fp.read_forum' => '1']
        ];

        if ($tid) {
            $curPosting['select'] = ['f.id', 'f.forum_name', 'f.moderators', 'f.redirect_url', 'fp.post_replies', 'fp.post_topics', 't.subject', 't.closed', 'is_subscribed' => 's.user_id'];

            $curPosting = DB::table('topics')
                ->tableAlias('t')
                ->selectMany($curPosting['select'])
                ->innerJoin('forums', ['f.id', '=', 't.forum_id'], 'f')
                ->leftOuterJoin('forum_perms', 'fp.forum_id=f.id AND fp.group_id='.$this->user->g_id, 'fp')
                ->leftOuterJoin('topic_subscriptions', 't.id=s.topic_id AND s.user_id='.$this->user->id, 's')
                ->whereAnyIs($curPosting['where'])
                ->where('t.id', $tid);
        } else {
            $curPosting['select'] = ['f.id', 'f.forum_name', 'f.moderators', 'f.redirect_url', 'fp.post_replies', 'fp.post_topics'];

            $curPosting = DB::table('forums')
                ->tableAlias('f')
                ->selectMany($curPosting['select'])
                ->leftOuterJoin('forum_perms', 'fp.forum_id=f.id AND fp.group_id='.$this->user->g_id, 'fp')
                ->whereAnyIs($curPosting['where'])
                ->where('f.id', $fid);
        }

        $curPosting = $curPosting->findOne();

        if (!$curPosting) {
            return json_encode($this->errorMessage, JSON_PRETTY_PRINT);
        }

        return $curPosting;
    }

    public function checkPermissions($curPosting, $tid, $fid)
    {
        // Is someone trying to post into a redirect forum?
        if ($curPosting['redirect_url'] != '') {
            return json_encode($this->errorMessage, JSON_PRETTY_PRINT);
        }

        // Sort out who the moderators are and if we are currently a moderator (or an admin)
        $modsArray = ($curPosting['moderators'] != '') ? unserialize($curPosting['moderators']) : [];
        $isAdmmod = (User::isAdmin($this->user) || (User::isAdminMod($this->user) && array_key_exists($this->user->username, $modsArray))) ? true : false;

        // Do we have permission to post?
        if ((($tid && (($curPosting['post_replies'] == '' && !User::can('topic.reply', $this->user)) || $curPosting['post_replies'] == '0')) ||
                ($fid && (($curPosting['post_topics'] == '' && !User::can('topic.post', $this->user)) || $curPosting['post_topics'] == '0')) ||
                (isset($curPosting['closed']) && $curPosting['closed'] == '1')) &&
            !$isAdmmod) {
            return json_encode($this->errorMessage, JSON_PRETTY_PRINT);
        }

        return $isAdmmod;
    }

    public function checkErrorsBeforePost($fid, $errors)
    {
        // Flood protection
        if (Input::post('preview') != '' && $this->user->last_post != '' && (time() - $this->user->last_post) < Container::get('prefs')->get($this->user, 'post.min_interval')) {
            $errors[] = sprintf(__('Flood start'), User::getPref('post.min_interval', $this->user), User::getPref('post.min_interval', $this->user) - (time() - $this->user->last_post));
        }

        // If it's a new topic
        if ($fid) {
            $subject = Utils::trim(Input::post('req_subject'));

            if (ForumSettings::get('o_censoring') == '1') {
                $censoredSubject = Utils::trim(Utils::censor($subject));
            }

            if ($subject == '') {
                $errors[] = __('No subject');
            } elseif (ForumSettings::get('o_censoring') == '1' && $censoredSubject == '') {
                $errors[] = __('No subject after censoring');
            } elseif (Utils::strlen($subject) > 70) {
                $errors[] = __('Too long subject');
            } elseif (ForumSettings::get('p_subject_all_caps') == '0' && Utils::isAllUppercase($subject) && !User::isAdminMod($this->user)) {
                $errors[] = __('All caps subject');
            }
        }

        if ($this->user->is_guest) {
            $email = strtolower(Utils::trim((ForumSettings::get('p_force_guest_email') == '1') ? Input::post('req_email') : Input::post('email')));

            if (ForumSettings::get('p_force_guest_email') == '1' || $email != '') {
                if (!Container::get('email')->isValidEmail($email)) {
                    $errors[] = __('Invalid email');
                }

                // Check if it's a banned email address
                // we should only check guests because members' addresses are already verified
                if ($this->user->is_guest && Container::get('email')->isBannedEmail($email)) {
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
            $errors[] = sprintf(__('Too long message'), Utils::forumNumberFormat(ForumEnv::get('FEATHER_MAX_POSTSIZE')));
        } elseif (ForumSettings::get('p_message_all_caps') == '0' && Utils::isAllUppercase($message) && !User::isAdminMod($this->user)) {
            $errors[] = __('All caps message');
        }

        // Validate BBCode syntax
        if (ForumSettings::get('p_message_bbcode') == '1') {
            $message = Container::get('parser')->preparseBbcode($message, $errors);
        }

        if (empty($errors)) {
            if ($message == '') {
                $errors[] = __('No message');
            } elseif (ForumSettings::get('o_censoring') == '1') {
                // Censor message to see if that causes problems
                $censoredMessage = Utils::trim(Utils::censor($message));

                if ($censoredMessage == '') {
                    $errors[] = __('No message after censoring');
                }
            }
        }

        return $errors;
    }

    // If the previous check went OK, setup some variables used later
    public function setupVariables($errors, $isAdmmod)
    {
        $post = [];

        if (!$this->user->is_guest) {
            $post['username'] = $this->user->username;
            $post['email'] = $this->user->email;
        }
        // Otherwise it should be in $feather ($_pOST)
        else {
            $post['username'] = Utils::trim(Input::post('req_username'));
            $post['email'] = strtolower(Utils::trim((ForumSettings::get('p_force_guest_email') == '1') ? Input::post('req_email') : Input::post('email')));
        }

        if (Input::post('req_subject')) {
            $post['subject'] = Utils::trim(Input::post('req_subject'));
        }

        $post['hide_smilies'] = Input::post('hide_smilies') ? '1' : '0';
        $post['subscribe'] = Input::post('subscribe') ? '1' : '0';
        $post['stick_topic'] = Input::post('stick_topic') && $isAdmmod ? '1' : '0';

        $post['message']  = Utils::linebreaks(Utils::trim(Input::post('req_message')));

        // Validate BBCode syntax
        if (ForumSettings::get('p_message_bbcode') == '1') {
            $post['message']  = Container::get('parser')->preparseBbcode($post['message'], $errors);
        }

        // Replace four-byte characters (MySQL cannot handle them)
        $post['message'] = Utils::stripBadMultibyteChars($post['message']);

        $post['time'] = time();

        return $post;
    }

    // Insert a topic
    public function insertTopic($post, $fid)
    {
        $new = [];

        // Create the topic
        $topic['insert'] = [
            'poster' => $post['username'],
            'subject' => $post['subject'],
            'posted'  => $post['time'],
            'last_post'  => $post['time'],
            'last_poster'  => $post['username'],
            'sticky'  => $post['stick_topic'],
            'forum_id'  => $fid,
        ];

        $topic = DB::table('topics')
            ->create()
            ->set($topic['insert']);
        $topic = $topic->save();

        $new['tid'] = DB::getDb()->lastInsertId(ForumSettings::get('db_prefix').'topics');

        if (!$this->user->is_guest) {
            // To subscribe or not to subscribe, that ...
            if (ForumSettings::get('o_topic_subscriptions') == '1' && $post['subscribe']) {
                $subscription['insert'] = [
                    'user_id'   =>  $this->user->id,
                    'topic_id'  =>  $new['tid']
                ];

                $subscription = DB::table('topic_subscriptions')
                    ->create()
                    ->set($subscription['insert']);
                $subscription = $subscription->save();
            }

            // Create the post ("topic post")
            $query['insert'] = [
                'poster' => $post['username'],
                'poster_id' => $this->user->id,
                'poster_ip' => Utils::getIp(),
                'message' => $post['message'],
                'hide_smilies' => $post['hide_smilies'],
                'posted'  => $post['time'],
                'topic_id'  => $new['tid'],
            ];

            $query = DB::table('posts')
                ->create()
                ->set($query['insert']);
            $query = $query->save();
        } else {
            // It's a guest
            // Create the post ("topic post")
            $query['insert'] = [
                'poster' => $post['username'],
                'poster_ip' => Utils::getIp(),
                'message' => $post['message'],
                'hide_smilies' => $post['hide_smilies'],
                'posted'  => $post['time'],
                'topic_id'  => $new['tid'],
            ];

            if (ForumSettings::get('p_force_guest_email') == '1' || $post['email'] != '') {
                $query['poster_email'] = $post['email'];
            }

            $query = DB::table('posts')
                ->create()
                ->set($query['insert']);
            $query = $query->save();
        }
        $new['pid'] = DB::getDb()->lastInsertId(ForumSettings::get('db_prefix').'topics');

        // Update the topic with last_post_id
        unset($topic);
        $topic['update'] = [
            'last_post_id'  =>  $new['pid'],
            'first_post_id' =>  $new['pid'],
        ];

        $topic = DB::table('topics')
            ->where('id', $new['tid'])
            ->findOne()
            ->set($topic['update']);
        $topic = $topic->save();

        $search = new \FeatherBB\Core\Search();

        $search->updateSearchIndex('post', $new['pid'], $post['message'], $post['subject']);

        \FeatherBB\Model\Forum::update($fid);

        return $new;
    }

    // Send notifications for new topics
    public function sendNotificationsNewTopic($post, $curPosting, $newTid)
    {
        // Get any subscribed users that should be notified (banned users are excluded)
        $result['where'] = [
            ['fp.read_forum' => 'IS NULL'],
            ['fp.read_forum' => '1']
        ];
        $result['select'] = ['u.id', 'u.email', 'u.notify_with_post', 'u.language'];

        $result = DB::table('users')
            ->tableAlias('u')
            ->selectMany($result['select'])
            ->innerJoin('forum_subscriptions', ['u.id', '=', 's.user_id'], 's')
            ->leftOuterJoin('forum_perms', 'fp.forum_id='.$curPosting['id'].' AND fp.group_id=u.group_id', 'fp')
            ->leftOuterJoin('bans', ['u.username', '=', 'b.username'], 'b')
            ->whereNull('b.username')
            ->whereAnyIs($result['where'])
            ->where('s.forum_id', $curPosting['id'])
            ->whereNotEqual('u.id', $this->user->id);
        $result = $result->findMany();

        if ($result) {
            $notificationEmails = [];

            $censoredMessage = Utils::trim(Utils::censor($post['message']));
            $censoredSubject = Utils::trim(Utils::censor($post['subject']));

            if (ForumSettings::get('o_censoring') == '1') {
                $cleanedMessage = Container::get('email')->bbcode2email($censoredMessage, -1);
            } else {
                $cleanedMessage = Container::get('email')->bbcode2email($post['message'], -1);
            }

            $cleanedSubject = ForumSettings::get('o_censoring') == '1' ? $censoredSubject : $post['subject'];

            // Loop through subscribed users and send emails
            foreach ($result as $curSubscriber) {
                // Is the subscription email for $curSubscriber['language'] cached or not?
                if (!isset($notificationEmails[$curSubscriber['language']])) {
                    if (file_exists(ForumEnv::get('FEATHER_ROOT').'featherbb/lang/'.$curSubscriber['language'].'/mail_templates/new_topic.tpl')) {
                        // Load the "new topic" template
                        $mailTpl = trim(file_get_contents(ForumEnv::get('FEATHER_ROOT').'featherbb/lang/'.$curSubscriber['language'].'/mail_templates/new_topic.tpl'));

                        // Load the "new topic full" template (with post included)
                        $mailTplFull = trim(file_get_contents(ForumEnv::get('FEATHER_ROOT').'featherbb/lang/'.$curSubscriber['language'].'/mail_templates/new_topic_full.tpl'));

                        // The first row contains the subject (it also starts with "Subject:")
                        $firstCrlf = strpos($mailTpl, "\n");
                        $mailSubject = trim(substr($mailTpl, 8, $firstCrlf-8));
                        $mailMessage = trim(substr($mailTpl, $firstCrlf));

                        $firstCrlf = strpos($mailTplFull, "\n");
                        $mailSubjectFull = trim(substr($mailTplFull, 8, $firstCrlf-8));
                        $mailMessageFull = trim(substr($mailTplFull, $firstCrlf));

                        $mailSubject = str_replace('<forum_name>', $curPosting['forum_name'], $mailSubject);
                        $mailMessage = str_replace('<topic_subject>', $cleanedSubject, $mailMessage);
                        $mailMessage = str_replace('<forum_name>', $curPosting['forum_name'], $mailMessage);
                        $mailMessage = str_replace('<poster>', $post['username'], $mailMessage);
                        $mailMessage = str_replace('<topic_url>', Router::pathFor('Topic', ['id' => $newTid, 'name' => Url::slug($post['subject'])]), $mailMessage);
                        $mailMessage = str_replace('<unsubscribe_url>', Router::pathFor('unsubscribeTopic', ['id' => $curPosting['id'], 'name' => Url::slug($post['subject'])]), $mailMessage);
                        $mailMessage = str_replace('<board_mailer>', ForumSettings::get('o_board_title'), $mailMessage);

                        $mailSubjectFull = str_replace('<forum_name>', $curPosting['forum_name'], $mailSubjectFull);
                        $mailMessageFull = str_replace('<topic_subject>', $cleanedSubject, $mailMessageFull);
                        $mailMessageFull = str_replace('<forum_name>', $curPosting['forum_name'], $mailMessageFull);
                        $mailMessageFull = str_replace('<poster>', $post['username'], $mailMessageFull);
                        $mailMessageFull = str_replace('<message>', $cleanedMessage, $mailMessageFull);
                        $mailMessageFull = str_replace('<topic_url>', Router::pathFor('Topic', ['id' => $newTid, 'name' => Url::slug($post['subject'])]), $mailMessageFull);
                        $mailMessageFull = str_replace('<unsubscribe_url>', Router::pathFor('unsubscribeTopic', ['id' => $tid, 'name' => Url::slug($post['subject'])]), $mailMessageFull);
                        $mailMessageFull = str_replace('<board_mailer>', ForumSettings::get('o_board_title'), $mailMessageFull);

                        $notificationEmails[$curSubscriber['language']][0] = $mailSubject;
                        $notificationEmails[$curSubscriber['language']][1] = $mailMessage;
                        $notificationEmails[$curSubscriber['language']][2] = $mailSubjectFull;
                        $notificationEmails[$curSubscriber['language']][3] = $mailMessageFull;
                    }
                }

                // We have to double check here because the templates could be missing
                if (isset($notificationEmails[$curSubscriber['language']])) {
                    if ($curSubscriber['notify_with_post'] == '0') {
                        Container::get('email')->send($curSubscriber['email'], $notificationEmails[$curSubscriber['language']][0], $notificationEmails[$curSubscriber['language']][1]);
                    } else {
                        Container::get('email')->send($curSubscriber['email'], $notificationEmails[$curSubscriber['language']][2], $notificationEmails[$curSubscriber['language']][3]);
                    }
                }
            }

            unset($cleanedMessage);
        }
    }

    // Increment post count, change group if needed
    public function incrementPostCount($post, $newTid)
    {
        if (!$this->user->is_guest) {
            $increment = DB::table('users')
                ->where('id', $this->user->id)
                ->findOne()
                ->set('last_post', $post['time'])
                ->setExpr('num_posts', 'num_posts+1');
            $increment = $increment->save();

            // Promote this user to a new group if enabled
            if (User::getPref('promote.next_group', $this->user) && $this->user->num_posts + 1 >= User::getPref('promote.min_posts', $this->user)) {
                $newGroupId = User::getPref('promote.next_group', $this->user);
                $promote = DB::table('users')
                    ->where('id', $this->user->id)
                    ->findOne()
                    ->set('group_id', $newGroupId);
                $promote = $promote->save();
            }

            // Topic tracking stuff...
            $trackedTopics = Track::getTrackedTopics();
            $trackedTopics['topics'][$newTid] = time();
            Track::setTrackedTopics($trackedTopics);
        } else {
            // Update the last_post field for guests
            $lastPost = DB::table('online')
                ->where('ident', Utils::getIp())
                ->findOne()
                ->set('last_post', $post['time']);
            $lastPost = $lastPost->save();
        }
    }

    // Insert a reply
    public function insertReply($post, $tid, $curPosting, $isSubscribed)
    {
        $new = [];

        $new = Container::get('hooks')->fireDB('model.post.insert_reply_start', $new, $post, $tid, $curPosting, $isSubscribed);

        if (!$this->user->is_guest) {
            $new['tid'] = $tid;

            // Insert the new post
            $query['insert'] = [
                'poster' => $post['username'],
                'poster_id' => $this->user->id,
                'poster_ip' => Utils::getIp(),
                'message' => $post['message'],
                'hide_smilies' => $post['hide_smilies'],
                'posted'  => $post['time'],
                'topic_id'  => $tid,
            ];

            $query = DB::table('posts')
                ->create()
                ->set($query['insert']);
            $query = Container::get('hooks')->fireDB('model.post.insert_reply_guest_query', $query);
            $query = $query->save();

            $new['pid'] = DB::getDb()->lastInsertId(ForumSettings::get('db_prefix').'posts');

            // To subscribe or not to subscribe, that ...
            if (ForumSettings::get('o_topic_subscriptions') == '1') {
                // ... is the question
                // Let's do it
                if (isset($post['subscribe']) && $post['subscribe'] && !$isSubscribed) {
                    $subscription['insert'] = [
                        'user_id'   =>  $this->user->id,
                        'topic_id'  =>  $tid
                    ];

                    $subscription = DB::table('topic_subscriptions')
                        ->create()
                        ->set($subscription['insert']);
                    $subscription = Container::get('hooks')->fireDB('model.post.insert_reply_subscription', $subscription);
                    $subscription = $subscription->save();

                    // We reply and we don't want to be subscribed anymore
                } elseif ($post['subscribe'] == '0' && $isSubscribed) {
                    $unsubscription = DB::table('topic_subscriptions')
                        ->where('user_id', $this->user->id)
                        ->where('topic_id', $tid);
                    $unsubscription = Container::get('hooks')->fireDB('model.post.insert_reply_unsubscription', $unsubscription);
                    $unsubscription = $unsubscription->deleteMany();
                }
            }
        } else {
            // It's a guest. Insert the new post
            $query['insert'] = [
                'poster' => $post['username'],
                'poster_ip' => Utils::getIp(),
                'message' => $post['message'],
                'hide_smilies' => $post['hide_smilies'],
                'posted'  => $post['time'],
                'topic_id'  => $tid,
            ];

            if (ForumSettings::get('p_force_guest_email') == '1' || $post['email'] != '') {
                $query['insert']['poster_email'] = $post['email'];
            }

            $query = DB::table('posts')
                ->create()
                ->set($query['insert']);
            $query = Container::get('hooks')->fireDB('model.post.insert_reply_member_query', $query);
            $query = $query->save();

            $new['pid'] = DB::getDb()->lastInsertId(ForumSettings::get('db_prefix').'posts');
        }

        // Update topic
        $topic['update'] = [
            'last_post' => $post['time'],
            'last_post_id'  => $new['pid'],
            'last_poster'  => $post['username'],
        ];

        $topic = DB::table('topics')
            ->where('id', $tid)
            ->findOne()
            ->set($topic['update'])
            ->setExpr('num_replies', 'num_replies+1');
        $topic = Container::get('hooks')->fireDB('model.post.insert_reply_update_query', $topic);

        // Get topic subject to redirect
        $new['topic_subject'] = Url::slug($topic->subject);

        $topic = $topic->save();

        $search = new \FeatherBB\Core\Search();

        $search->updateSearchIndex('post', $new['pid'], $post['message']);

        \FeatherBB\Model\Forum::update($curPosting['id']);

        $new = Container::get('hooks')->fireDB('model.post.insert_reply', $new);

        return $new;
    }
}
