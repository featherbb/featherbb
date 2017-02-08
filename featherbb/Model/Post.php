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
use FeatherBB\Core\Track;
use FeatherBB\Core\Url;
use FeatherBB\Core\Utils;

class Post
{
    public function getInfoPost($tid, $fid)
    {
        Container::get('hooks')->fire('model.post.get_info_post_start', $tid, $fid);

        $curPosting['where'] = [
            ['fp.read_forum' => 'IS NULL'],
            ['fp.read_forum' => '1']
        ];

        if ($tid) {
            $curPosting['select'] = ['f.id', 'f.forum_name', 'f.moderators', 'f.redirect_url', 'fp.post_replies', 'fp.post_topics', 't.subject', 't.closed', 'is_subscribed' => 's.user_id'];

            $curPosting = DB::forTable('topics')
                            ->tableAlias('t')
                            ->selectMany($curPosting['select'])
                            ->innerJoin('forums', ['f.id', '=', 't.forum_id'], 'f')
                            ->leftOuterJoin('forum_perms', 'fp.forum_id=f.id AND fp.group_id='.User::get()->g_id, 'fp')
                            ->leftOuterJoin('topic_subscriptions', 't.id=s.topic_id AND s.user_id='.User::get()->g_id, 's')
                            ->whereAnyIs($curPosting['where'])
                            ->where('t.id', $tid);
        } else {
            $curPosting['select'] = ['f.id', 'f.forum_name', 'f.moderators', 'f.redirect_url', 'fp.post_replies', 'fp.post_topics'];

            $curPosting = DB::forTable('forums')
                            ->tableAlias('f')
                            ->selectMany($curPosting['select'])
                            ->leftOuterJoin('forum_perms', 'fp.forum_id=f.id AND fp.group_id='.User::get()->g_id, 'fp')
                            ->whereAnyIs($curPosting['where'])
                            ->where('f.id', $fid);
        }

        $curPosting = Container::get('hooks')->fireDB('model.post.get_info_post_query', $curPosting);
        $curPosting = $curPosting->findOne();

        if (!$curPosting) {
            throw new Error(__('Bad request'), 404);
        }

        $curPosting = Container::get('hooks')->fire('model.post.get_info_post', $curPosting);

        return $curPosting;
    }

    // Fetch some info about the post, the topic and the forum
    public function getInfoEdit($id)
    {
        $id = Container::get('hooks')->fire('model.post.get_info_edit_start', $id);

        $curPost['select'] = ['fid' => 'f.id', 'f.forum_name', 'f.moderators', 'f.redirect_url', 'fp.post_topics', 'tid' => 't.id', 't.subject', 't.posted', 't.first_post_id', 't.sticky', 't.closed', 'p.poster', 'p.poster_id', 'p.message', 'p.hide_smilies'];
        $curPost['where'] = [
            ['fp.read_forum' => 'IS NULL'],
            ['fp.read_forum' => '1']
        ];

        $curPost = DB::forTable('posts')
                    ->tableAlias('p')
                    ->selectMany($curPost['select'])
                    ->innerJoin('topics', ['t.id', '=', 'p.topic_id'], 't')
                    ->innerJoin('forums', ['f.id', '=', 't.forum_id'], 'f')
                    ->leftOuterJoin('forum_perms', 'fp.forum_id=f.id AND fp.group_id='.User::get()->g_id, 'fp')
                    ->whereAnyIs($curPost['where'])
                    ->where('p.id', $id);

        $curPost = Container::get('hooks')->fireDB('model.post.get_info_edit_query', $curPost);

        $curPost = $curPost->findOne();

        if (!$curPost) {
            throw new Error(__('Bad request'), 400);
        }

        return $curPost;
    }

    // Checks the post for errors before posting
    public function checkErrorsPost($fid, $errors)
    {
        $langAntispamQuestions = require ForumEnv::get('FEATHER_ROOT').'featherbb/lang/'.User::getPref('language').'/antispam.php';

        $fid = Container::get('hooks')->fire('model.post.check_errors_before_post_start', $fid);

        // Antispam feature
        if (User::get()->is_guest) {

            // It's a guest, so we have to validate the username
            $profile = new \FeatherBB\Model\Profile();
            $errors = $profile->checkUsername(Utils::trim(Input::post('req_username')), $errors);

            $errors = Container::get('hooks')->fire('model.post.check_errors_before_post_antispam', $errors);

            $question = Input::post('captcha_q') ? trim(Input::post('captcha_q')) : '';
            $answer = Input::post('captcha') ? strtoupper(trim(Input::post('captcha'))) : '';
            $langAntispamQuestionsArray = [];

            foreach ($langAntispamQuestions as $k => $v) {
                $langAntispamQuestionsArray[md5($k)] = strtoupper($v);
            }

            if (empty($langAntispamQuestionsArray[$question]) || $langAntispamQuestionsArray[$question] != $answer) {
                $errors[] = __('Robot test fail');
            }
        }

        // Flood protection
        if (Input::post('preview') != '' && User::get()->last_post != '' && (time() - User::get()->last_post) < User::getPref('post.min_interval')) {
            $errors[] = sprintf(__('Flood start'), User::getPref('post.min_interval'), User::getPref('post.min_interval') - (time() - User::get()->last_post));
        }

        // If it's a new topic
        if ($fid) {
            $subject = Utils::trim(Input::post('req_subject'));
            $subject = Container::get('hooks')->fire('model.post.check_errors_before_new_topic_subject', $subject);

            if (ForumSettings::get('o_censoring') == '1') {
                $censoredSubject = Utils::trim(Utils::censor($subject));
                $censoredSubject = Container::get('hooks')->fire('model.post.check_errors_before_censored', $censoredSubject);
            }

            if ($subject == '') {
                $errors[] = __('No subject');
            } elseif (ForumSettings::get('o_censoring') == '1' && $censoredSubject == '') {
                $errors[] = __('No subject after censoring');
            } elseif (Utils::strlen($subject) > 70) {
                $errors[] = __('Too long subject');
            } elseif (ForumSettings::get('p_subject_all_caps') == '0' && Utils::isAllUppercase($subject) && !User::isAdminMod()) {
                $errors[] = __('All caps subject');
            }

            $errors = Container::get('hooks')->fire('model.post.check_errors_before_new_topic_errors', $errors);
        }

        if (User::get()->is_guest) {
            $email = strtolower(Utils::trim((ForumSettings::get('p_force_guest_email') == '1') ? Input::post('req_email') : Input::post('email')));

            if (ForumSettings::get('p_force_guest_email') == '1' || $email != '') {
                $errors = Container::get('hooks')->fire('model.post.check_errors_before_post_email', $errors, $email);

                if (!Container::get('email')->isValidEmail($email)) {
                    $errors[] = __('Invalid email');
                }

                // Check if it's a banned email address
                // we should only check guests because members' addresses are already verified
                if (User::get()->is_guest && Container::get('email')->isBannedEmail($email)) {
                    if (ForumSettings::get('p_allow_banned_email') == '0') {
                        $errors[] = __('Banned email');
                    }

                    $errors['banned_email'] = 1; // Used later when we send an alert email
                }
            }
        }

        // Clean up message from POST
        $message = Utils::linebreaks(Utils::trim(Input::post('req_message')));
        $message = Container::get('hooks')->fire('model.post.check_errors_before_post_message', $message);

        // Here we use strlen() not Utils::strlen() as we want to limit the post to FEATHER_MAX_POSTSIZE bytes, not characters
        if (strlen($message) > ForumEnv::get('FEATHER_MAX_POSTSIZE')) {
            $errors[] = sprintf(__('Too long message'), Utils::forumNumberFormat(ForumEnv::get('FEATHER_MAX_POSTSIZE')));
        } elseif (ForumSettings::get('p_message_all_caps') == '0' && Utils::isAllUppercase($message) && !User::isAdminMod()) {
            $errors[] = __('All caps message');
        }

        // Validate BBCode syntax
        if (ForumSettings::get('p_message_bbcode') == '1') {
            $message = Container::get('parser')->preparseBbcode($message, $errors);
            $message = Container::get('hooks')->fire('model.post.check_errors_before_post_bbcode', $message);
        }

        if (empty($errors)) {
            $errors = Container::get('hooks')->fire('model.post.check_errors_before_post_no_error', $errors);
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

        $errors = Container::get('hooks')->fire('model.post.check_errors_before_post', $errors);

        return $errors;
    }

    public static function checkErrorsEdit($canEditSubject, $errors, $isAdmmod)
    {
        $errors = Container::get('hooks')->fire('model.post.check_errors_before_edit_start', $errors);

        // If it's a topic it must contain a subject
        if ($canEditSubject) {
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
            } elseif (ForumSettings::get('p_subject_all_caps') == '0' && Utils::isAllUppercase($subject) && !$isAdmmod) {
                $errors[] = __('All caps subject');
            }
        }

        // Clean up message from POST
        $message = Utils::linebreaks(Utils::trim(Input::post('req_message')));

        // Here we use strlen() not Utils::strlen() as we want to limit the post to FEATHER_MAX_POSTSIZE bytes, not characters
        if (strlen($message) > ForumEnv::get('FEATHER_MAX_POSTSIZE')) {
            $errors[] = sprintf(__('Too long message'), Utils::forumNumberFormat(ForumEnv::get('FEATHER_MAX_POSTSIZE')));
        } elseif (ForumSettings::get('p_message_all_caps') == '0' && Utils::isAllUppercase($message) && !$isAdmmod) {
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

        $errors = Container::get('hooks')->fire('model.post.check_errors_before_edit', $errors);

        return $errors;
    }

    // If the previous check went OK, setup some variables used later
    public function setupVariables($errors, $isAdmmod)
    {
        $post = [];

        $post = Container::get('hooks')->fire('model.post.setup_variables_start', $post, $errors, $isAdmmod);

        if (!User::get()->is_guest) {
            $post['username'] = User::get()->username;
            $post['email'] = User::get()->email;
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

        $post = Container::get('hooks')->fire('model.post.setup_variables', $post);

        return $post;
    }

    // If the previous check went OK, setup some variables used later
    public static function setupEditVariables($curPost, $isAdmmod, $canEditSubject, $errors)
    {
        Container::get('hooks')->fire('model.post.setup_edit_variables_start');

        $post = [];

        $post['hide_smilies'] = Input::post('hide_smilies') ? '1' : '0';
        $post['stick_topic'] = Input::post('stick_topic') ? '1' : '0';
        if (!$isAdmmod) {
            $post['stick_topic'] = $curPost['sticky'];
        }

        // Clean up message from POST
        $post['message'] = Utils::linebreaks(Utils::trim(Input::post('req_message')));

        // Validate BBCode syntax
        if (ForumSettings::get('p_message_bbcode') == '1') {
            $post['message'] = Container::get('parser')->preparseBbcode($post['message'], $errors);
        }

        // Replace four-byte characters (MySQL cannot handle them)
        $post['message'] = Utils::stripBadMultibyteChars($post['message']);

        // Get the subject
        if ($canEditSubject) {
            $post['subject'] = Utils::trim(Input::post('req_subject'));
        }

        $post = Container::get('hooks')->fire('model.post.setup_edit_variables', $post);

        return $post;
    }

    public function getInfoDelete($id)
    {
        $id = Container::get('hooks')->fire('model.post.get_info_delete_start', $id);

        $query['select'] = ['fid' => 'f.id', 'f.forum_name', 'f.moderators', 'f.redirect_url', 'fp.post_replies',  'fp.post_topics', 'tid' => 't.id', 't.subject', 't.first_post_id', 't.closed', 'p.poster', 'p.posted', 'p.poster_id', 'p.message', 'p.hide_smilies'];
        $query['where'] = [
            ['fp.read_forum' => 'IS NULL'],
            ['fp.read_forum' => '1']
        ];

        $query = DB::forTable('posts')
            ->tableAlias('p')
            ->selectMany($query['select'])
            ->innerJoin('topics', ['t.id', '=', 'p.topic_id'], 't')
            ->innerJoin('forums', ['f.id', '=', 't.forum_id'], 'f')
            ->leftOuterJoin('forum_perms', 'fp.forum_id=f.id AND fp.group_id='.User::get()->g_id, 'fp')
            ->whereAnyIs($query['where'])
            ->where('p.id', $id);

        $query = Container::get('hooks')->fireDB('model.post.get_info_delete_query', $query);

        $query = $query->findOne();

        if (!$query) {
            throw new Error(__('Bad request'), 404);
        }

        return $query;
    }

    public static function handleDeletion($isTopicPost, $id, $curPost)
    {
        Container::get('hooks')->fire('model.post.handle_deletion_start', $isTopicPost, $id, $curPost);

        $tid = $curPost['tid'];
        $fid = $curPost['fid'];
        $topicSubject = Url::slug($curPost['subject']);
        $forumUrl = Url::slug($curPost['forum_name']);

        if ($isTopicPost) {
            Container::get('hooks')->fire('model.post.model.topic.delete', $isTopicPost, $id, $curPost);

            // Delete the topic and all of its posts
            Topic::delete($tid);
            Forum::update($fid);

            return Router::redirect(Router::pathFor('Forum', ['id' => $fid, 'name' => $forumUrl]), __('Topic del redirect'));
        } else {
            Container::get('hooks')->fire('model.post.handle_deletion', $isTopicPost, $id, $curPost);

            // Delete just this one post
            self::delete($id, $tid);
            Forum::update($fid);

            // Redirect towards the previous post
            $post = DB::forTable('posts')
                ->select('id')
                ->where('topic_id', $tid)
                ->whereLt('id', $id)
                ->orderByDesc('id');

            $post = Container::get('hooks')->fireDB('model.post.handle_deletion_query', $post);

            $post = $post->findOne();

            return Router::redirect(Router::pathFor('viewPost', ['id' => $tid, 'name' => $topicSubject, 'pid' => $post['id']]).'#p'.$post['id'], __('Post del redirect'));
        }
    }

    //
    // Delete a single post
    //
    public static function delete($postId, $topicId)
    {
        $result = DB::forTable('posts')
            ->selectMany('id', 'poster', 'posted')
            ->where('topic_id', $topicId)
            ->orderByDesc('id')
            ->limit(2)
            ->findMany();

        $i = 0;
        foreach ($result as $curResult) {
            if ($i == 0) {
                $lastId = $curResult['id'];
            } else {
                $secondLastId = $curResult['id'];
                $secondPoster = $curResult['poster'];
                $secondPosted = $curResult['posted'];
            }
            ++$i;
        }

        // Delete the post
        DB::forTable('posts')
            ->where('id', $postId)
            ->findOne()
            ->delete();

        $search = new \FeatherBB\Core\Search();
        $search->stripSearchIndex($postId);

        // Count number of replies in the topic
        $numReplies = DB::forTable('posts')->where('topic_id', $topicId)->count() - 1;

        // If the message we deleted is the most recent in the topic (at the end of the topic)
        if ($lastId == $postId) {
            // If there is a $secondLastId there is more than 1 reply to the topic
            if (isset($secondLastId)) {
                $updateTopic = [
                    'last_post'  => $secondPosted,
                    'last_post_id'  => $secondLastId,
                    'last_poster'  => $secondPoster,
                    'num_replies'  => $numReplies,
                ];
                DB::forTable('topics')
                    ->where('id', $topicId)
                    ->findOne()
                    ->set($updateTopic)
                    ->save();
            } else {
                // We deleted the only reply, so now last_post/last_post_id/last_poster is posted/id/poster from the topic itself
                DB::forTable('topics')
                    ->where('id', $topicId)
                    ->findOne()
                    ->setExpr('last_post', 'posted')
                    ->setExpr('last_post_id', 'id')
                    ->setExpr('last_poster', 'poster')
                    ->set('num_replies', $numReplies)
                    ->save();
            }
        } else {
            // Otherwise we just decrement the reply counter
            DB::forTable('topics')
                ->where('id', $topicId)
                ->findOne()
                ->set('num_replies', $numReplies)
                ->save();
        }
    }

    public static function editPost($id, $canEditSubject, $post, $curPost, $isAdmmod, $username = null)
    {
        Container::get('hooks')->fire('model.post.edit_post_start');

        if ($canEditSubject) {
            // Update the topic and any redirect topics
            $whereTopic = [
                ['id' => $curPost['tid']],
                ['moved_to' => $curPost['tid']]
            ];

            $query['update_topic'] = [
                'subject' => $post['subject'],
                'sticky'  => $post['stick_topic']
            ];

            $query = DB::forTable('topics')->whereAnyIs($whereTopic)
                                            ->findOne()
                                            ->set($query['update_topic']);

            $query = Container::get('hooks')->fireDB('model.post.edit_post_can_edit_subject', $query);

            $query = $query->save();

            // We changed the subject, so we need to take that into account when we update the search words
            \FeatherBB\Core\Search::updateSearchIndex('edit', $id, $post['message'], $post['subject']);
        } else {
            \FeatherBB\Core\Search::updateSearchIndex('edit', $id, $post['message']);
        }

        // Update the post
        unset($query);
        $query['update_post'] = [
            'message' => $post['message'],
            'hide_smilies'  => $post['hide_smilies']
        ];

        if (!Input::post('silent') || !$isAdmmod) {
            $query['update_post']['edited'] = time();
            $query['update_post']['edited_by'] = (is_null($username) ? User::get()->username : $username);
        }

        $query = DB::forTable('posts')->where('id', $id)
                                       ->findOne()
                                       ->set($query['update_post']);
        $query = Container::get('hooks')->fireDB('model.post.edit_post_query', $query);
        $query = $query->save();
    }

    public function report($postId)
    {
        $postId = Container::get('hooks')->fire('model.post.insert_report_start', $postId);

        // Clean up reason from POST
        $reason = Utils::linebreaks(Utils::trim(Input::post('req_reason')));
        if ($reason == '') {
            throw new Error(__('No reason'), 400);
        } elseif (strlen($reason) > 65535) { // TEXT field can only hold 65535 bytes
            throw new Error(__('Reason too long'), 400);
        }

        if (User::get()->last_report_sent != '' && (time() - User::get()->last_report_sent) < User::getPref('report.min_interval') && (time() - User::get()->last_report_sent) >= 0) {
            throw new Error(sprintf(__('Report flood'), User::getPref('report.min_interval'), User::getPref('report.min_interval') - (time() - User::get()->last_report_sent)), 429);
        }

        // Get the topic ID
        $topic = DB::forTable('posts')->select('topic_id')
                                      ->where('id', $postId);
        $topic = Container::get('hooks')->fireDB('model.post.insert_report_topic_id', $topic);
        $topic = $topic->findOne();

        if (!$topic) {
            throw new Error(__('Bad request'), 404);
        }

        // Get the subject and forum ID
        $report['select'] = ['subject', 'forum_id'];
        $report = DB::forTable('topics')->selectMany($report['select'])
                                        ->where('id', $topic['topic_id']);
        $report = Container::get('hooks')->fireDB('model.post.insert_report_get_subject', $report);
        $report = $report->findOne();

        if (!$report) {
            throw new Error(__('Bad request'), 404);
        }

        // Should we use the internal report handling?
        if (ForumSettings::get('o_report_method') == '0' || ForumSettings::get('o_report_method') == '2') {

            // Insert the report
            $query['insert'] = [
                'post_id' => $postId,
                'topic_id'  => $topic['topic_id'],
                'forum_id'  => $report['forum_id'],
                'reported_by'  => User::get()->id,
                'created'  => time(),
                'message'  => $reason,
            ];
            $query = DB::forTable('reports')
                ->create()
                ->set($query['insert']);
            $query = Container::get('hooks')->fireDB('model.post.insert_report_query', $query);
            $query = $query->save();
        }

        // Should we email the report?
        if (ForumSettings::get('o_report_method') == '1' || ForumSettings::get('o_report_method') == '2') {
            // We send it to the complete mailing-list in one swoop
            if (ForumSettings::get('o_mailing_list') != '') {
                // Load the "new report" template
                $mailTpl = trim(file_get_contents(ForumEnv::get('FEATHER_ROOT').'featherbb/lang/'.User::getPref('language').'/mail_templates/new_report.tpl'));
                $mailTpl = Container::get('hooks')->fire('model.post.insert_report_mail_tpl', $mailTpl);

                // The first row contains the subject
                $firstCrlf = strpos($mailTpl, "\n");
                $mailSubject = trim(substr($mailTpl, 8, $firstCrlf-8));
                $mailMessage = trim(substr($mailTpl, $firstCrlf));

                $mailSubject = str_replace('<forum_id>', $report['forum_id'], $mailSubject);
                $mailSubject = str_replace('<topic_subject>', $report['subject'], $mailSubject);
                $mailMessage = str_replace('<username>', User::get()->username, $mailMessage);
                $mailMessage = str_replace('<post_url>', Router::pathFor('viewPost', ['id' => $topic['topic_id'], 'name' => Url::slug($report['subject']), 'pid' => $postId]).'#p'.$postId, $mailMessage);
                $mailMessage = str_replace('<reason>', $reason, $mailMessage);
                $mailMessage = str_replace('<board_mailer>', ForumSettings::get('o_board_title'), $mailMessage);

                $mailMessage = Container::get('hooks')->fire('model.post.insert_report_mail_message', $mailMessage);

                Container::get('email')->send(ForumSettings::get('o_mailing_list'), $mailSubject, $mailMessage);
            }
        }

        $lastReportSent = DB::forTable('users')->where('id', User::get()->id)
            ->findOne()
            ->set('last_report_sent', time());
        $lastReportSent = Container::get('hooks')->fireDB('model.post.insert_last_report_sent', $lastReportSent);
        $lastReportSent = $lastReportSent->save();

        return Router::redirect(Router::pathFor('viewPost', ['id' => $topic['topic_id'], 'name' => Url::slug($report['subject']), 'pid' => $postId]).'#p'.$postId, __('Report redirect'));
    }

    public function getInfoReport($postId)
    {
        $postId = Container::get('hooks')->fire('model.post.get_info_report_start', $postId);

        $curPost['select'] = ['fid' => 'f.id', 'f.forum_name', 'tid' => 't.id', 't.subject'];
        $curPost['where'] = [
            ['fp.read_forum' => 'IS NULL'],
            ['fp.read_forum' => '1']
        ];

        $curPost = DB::forTable('posts')
                        ->tableAlias('p')
                        ->selectMany($curPost['select'])
                        ->innerJoin('topics', ['t.id', '=', 'p.topic_id'], 't')
                        ->innerJoin('forums', ['f.id', '=', 't.forum_id'], 'f')
                        ->leftOuterJoin('forum_perms', 'fp.forum_id=f.id AND fp.group_id='.User::get()->g_id, 'fp')
                        ->whereAnyIs($curPost['where'])
                        ->where('p.id', $postId);
        $curPost = Container::get('hooks')->fireDB('model.post.get_info_report_query', $curPost);
        $curPost = $curPost->findOne();

        if (!$curPost) {
            throw new Error(__('Bad request'), 404);
        }

        $curPost = Container::get('hooks')->fire('model.post.get_info_report', $curPost);

        return $curPost;
    }

    // Insert a reply
    public function reply($post, $tid, $curPosting, $isSubscribed)
    {
        $new = [];

        $new = Container::get('hooks')->fireDB('model.post.insert_reply_start', $new, $post, $tid, $curPosting, $isSubscribed);

        if (!User::get()->is_guest) {
            $new['tid'] = $tid;

            // Insert the new post
            $query['insert'] = [
                'poster' => $post['username'],
                'poster_id' => User::get()->id,
                'poster_ip' => Utils::getIp(),
                'message' => $post['message'],
                'hide_smilies' => $post['hide_smilies'],
                'posted'  => $post['time'],
                'topic_id'  => $tid,
            ];

            $query = DB::forTable('posts')
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
                        'user_id'   =>  User::get()->id,
                        'topic_id'  =>  $tid
                    ];

                    $subscription = DB::forTable('topic_subscriptions')
                                        ->create()
                                        ->set($subscription['insert']);
                    $subscription = Container::get('hooks')->fireDB('model.post.insert_reply_subscription', $subscription);
                    $subscription = $subscription->save();

                // We reply and we don't want to be subscribed anymore
                } elseif ($post['subscribe'] == '0' && $isSubscribed) {
                    $unsubscription = DB::forTable('topic_subscriptions')
                                        ->where('user_id', User::get()->id)
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

            $query = DB::forTable('posts')
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

        $topic = DB::forTable('topics')
                    ->where('id', $tid)
                    ->findOne()
                    ->set($topic['update'])
                    ->setExpr('num_replies', 'num_replies+1');
        $topic = Container::get('hooks')->fireDB('model.post.insert_reply_update_query', $topic);

        // Get topic subject to redirect
        $new['topic_subject'] = Url::slug($topic->subject);

        $topic = $topic->save();

        \FeatherBB\Core\Search::updateSearchIndex('post', $new['pid'], $post['message']);

        Forum::update($curPosting['id']);

        $new = Container::get('hooks')->fireDB('model.post.insert_reply', $new);

        return $new;
    }

    // Send notifications for replies
    public static function sendNotificationsReply($tid, $curPosting, $newPid, $post)
    {
        Container::get('hooks')->fire('model.post.send_notifications_reply_start', $tid, $curPosting, $newPid, $post);

        // Get the post time for the previous post in this topic
        $previousPostTime = DB::forTable('posts')
                                ->select('posted')
                                ->where('topic_id', $tid)
                                ->orderByDesc('id')
                                ->limit(1)
                                ->offset(1);
        $previousPostTime = Container::get('hooks')->fireDB('model.post.send_notifications_reply_previous', $previousPostTime);
        $previousPostTime = $previousPostTime->findOne();

        // Get any subscribed users that should be notified (banned users are excluded)
        $result['where'] = [
            ['fp.read_forum' => 'IS NULL'],
            ['fp.read_forum' => '1']
        ];
        $result['select'] = ['u.id', 'u.email', 'u.group_id'];

        $result = DB::forTable('users')
                    ->tableAlias('u')
                    ->selectMany($result['select'])
                    ->innerJoin('topic_subscriptions', ['u.id', '=', 's.user_id'], 's')
                    ->leftOuterJoin('forum_perms', 'fp.forum_id='.$curPosting['id'].' AND fp.group_id=u.group_id', 'fp')
                    ->leftOuterJoin('online', ['u.id', '=', 'o.user_id'], 'o')
                    ->leftOuterJoin('bans', ['u.username', '=', 'b.username'], 'b')
                    ->whereRaw('COALESCE(o.logged, u.last_visit)>'.$previousPostTime['posted'])
                    ->whereNull('b.username')
                    ->whereAnyIs($result['where'])
                    ->where('s.topic_id', $tid)
                    ->whereNotEqual('u.id', User::get()->id);
        $result = Container::get('hooks')->fireDB('model.post.send_notifications_reply_query', $result);
        $result = $result->findMany();

        if ($result) {
            $notificationEmails = [];

            $censoredMessage = Utils::trim(Utils::censor($post['message']));

            if (ForumSettings::get('o_censoring') == '1') {
                $cleanedMessage = Container::get('email')->bbcode2email($censoredMessage, -1);
            } else {
                $cleanedMessage = Container::get('email')->bbcode2email($post['message'], -1);
            }

            // Loop through subscribed users and send emails
            foreach ($result as $curSubscriber) {
                $curSubscriber['prefs'] = Container::get('prefs')->loadPrefs($curSubscriber);
                // Is the subscription email for User::getPref('language', $curSubscriber['id']) cached or not?
                if (!isset($notificationEmails[$curSubscriber['prefs']['language']])) {
                    if (file_exists(ForumEnv::get('FEATHER_ROOT').'featherbb/lang/'.$curSubscriber['prefs']['language'].'/mail_templates/new_reply.tpl')) {
                        // Load the "new reply" template
                        $mailTpl = trim(file_get_contents(ForumEnv::get('FEATHER_ROOT').'featherbb/lang/'.$curSubscriber['prefs']['language'].'/mail_templates/new_reply.tpl'));
                        $mailTpl = Container::get('hooks')->fire('model.post.send_notifications_reply_mail_tpl', $mailTpl);

                        // Load the "new reply full" template (with post included)
                        $mailTplFull = trim(file_get_contents(ForumEnv::get('FEATHER_ROOT').'featherbb/lang/'.$curSubscriber['prefs']['language'].'/mail_templates/new_reply_full.tpl'));
                        $mailTplFull = Container::get('hooks')->fire('model.post.send_notifications_reply_mail_tpl_full', $mailTplFull);

                        // The first row contains the subject (it also starts with "Subject:")
                        $firstCrlf = strpos($mailTpl, "\n");
                        $mailSubject = trim(substr($mailTpl, 8, $firstCrlf-8));
                        $mailSubject = Container::get('hooks')->fire('model.post.send_notifications_reply_mail_subject', $mailSubject);
                        $mailMessage = trim(substr($mailTpl, $firstCrlf));

                        $firstCrlf = strpos($mailTplFull, "\n");
                        $mailSubjectFull = trim(substr($mailTplFull, 8, $firstCrlf-8));
                        $mailMessageFull = trim(substr($mailTplFull, $firstCrlf));

                        $mailSubject = str_replace('<topic_subject>', $curPosting['subject'], $mailSubject);
                        $mailMessage = str_replace('<topic_subject>', $curPosting['subject'], $mailMessage);
                        $mailMessage = str_replace('<replier>', $post['username'], $mailMessage);
                        $mailMessage = str_replace('<post_url>', Router::pathFor('viewPost', ['id' => $tid, 'name' => Url::slug($curPosting['subject']), 'pid' => $newPid]).'#p'.$newPid, $mailMessage);
                        $mailMessage = str_replace('<unsubscribe_url>', Router::pathFor('unsubscribeTopic', ['id' => $tid, 'name' => Url::slug($curPosting['subject'])]), $mailMessage);
                        $mailMessage = str_replace('<board_mailer>', ForumSettings::get('o_board_title'), $mailMessage);
                        $mailMessage = Container::get('hooks')->fire('model.post.send_notifications_reply_mail_message', $mailMessage);

                        $mailSubjectFull = str_replace('<topic_subject>', $curPosting['subject'], $mailSubjectFull);
                        $mailMessageFull = str_replace('<topic_subject>', $curPosting['subject'], $mailMessageFull);
                        $mailMessageFull = str_replace('<replier>', $post['username'], $mailMessageFull);
                        $mailMessageFull = str_replace('<message>', $cleanedMessage, $mailMessageFull);
                        $mailMessageFull = str_replace('<post_url>', Router::pathFor('viewPost', ['id' => $tid, 'name' => Url::slug($curPosting['subject']), 'pid' => $newPid]).'#p'.$newPid, $mailMessageFull);
                        $mailMessageFull = str_replace('<unsubscribe_url>', Router::pathFor('unsubscribeTopic', ['id' => $tid, 'name' => Url::slug($curPosting['subject'])]), $mailMessageFull);
                        $mailMessageFull = str_replace('<board_mailer>', ForumSettings::get('o_board_title'), $mailMessageFull);
                        $mailMessageFull = Container::get('hooks')->fire('model.post.send_notifications_reply_mail_message_full', $mailMessageFull);

                        $notificationEmails[$curSubscriber['prefs']['language']][0] = $mailSubject;
                        $notificationEmails[$curSubscriber['prefs']['language']][1] = $mailMessage;
                        $notificationEmails[$curSubscriber['prefs']['language']][2] = $mailSubjectFull;
                        $notificationEmails[$curSubscriber['prefs']['language']][3] = $mailMessageFull;

                        $mailSubject = $mailMessage = $mailSubjectFull = $mailMessageFull = null;
                    }
                }

                // We have to double check here because the templates could be missing
                if (isset($notificationEmails[$curSubscriber['prefs']['language']])) {
                    if ($curSubscriber['prefs']['notify_with_post'] == '0') {
                        Container::get('email')->send($curSubscriber['email'], $notificationEmails[$curSubscriber['prefs']['language']][0], $notificationEmails[$curSubscriber['prefs']['language']][1]);
                    } else {
                        Container::get('email')->send($curSubscriber['email'], $notificationEmails[$curSubscriber['prefs']['language']][2], $notificationEmails[$curSubscriber['prefs']['language']][3]);
                    }
                }
            }

            Container::get('hooks')->fire('model.post.send_notifications_reply');

            unset($cleanedMessage);
        }
    }

    // Insert a topic
    public function insertTopic($post, $fid)
    {
        $new = [];

        $new = Container::get('hooks')->fireDB('model.post.insert_topic_start', $new, $post, $fid);

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

        $topic = DB::forTable('topics')
                    ->create()
                    ->set($topic['insert']);
        $topic = Container::get('hooks')->fireDB('model.post.insert_topic_create', $topic);
        $topic = $topic->save();

        $new['tid'] = DB::getDb()->lastInsertId(ForumSettings::get('db_prefix').'topics');

        if (!User::get()->is_guest) {
            // To subscribe or not to subscribe, that ...
            if (ForumSettings::get('o_topic_subscriptions') == '1' && $post['subscribe']) {
                $subscription['insert'] = [
                    'user_id'   =>  User::get()->id,
                    'topic_id'  =>  $new['tid']
                ];

                $subscription = DB::forTable('topic_subscriptions')
                                    ->create()
                                    ->set($subscription['insert']);
                $subscription = Container::get('hooks')->fireDB('model.post.insert_topic_subscription_member', $subscription);
                $subscription = $subscription->save();
            }

            // Create the post ("topic post")
            $query['insert'] = [
                'poster' => $post['username'],
                'poster_id' => User::get()->id,
                'poster_ip' => Utils::getIp(),
                'message' => $post['message'],
                'hide_smilies' => $post['hide_smilies'],
                'posted'  => $post['time'],
                'topic_id'  => $new['tid'],
            ];

            $query = DB::forTable('posts')
                        ->create()
                        ->set($query['insert']);
            $query = Container::get('hooks')->fireDB('model.post.insert_topic_post_member', $query);
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

            $query = DB::forTable('posts')
                ->create()
                ->set($query['insert']);
            $query = Container::get('hooks')->fireDB('model.post.insert_topic_post_member', $query);
            $query = $query->save();
        }
        $new['pid'] = DB::getDb()->lastInsertId(ForumSettings::get('db_prefix').'topics');
        $new['topic_subject'] = Url::slug($post['subject']);

        // Update the topic with last_post_id
        unset($topic);
        $topic['update'] = [
            'last_post_id'  =>  $new['pid'],
            'first_post_id' =>  $new['pid'],
        ];

        $topic = DB::forTable('topics')
                    ->where('id', $new['tid'])
                    ->findOne()
                    ->set($topic['update']);
        $topic = Container::get('hooks')->fireDB('model.post.insert_topic_post_topic', $topic);
        $topic = $topic->save();

        \FeatherBB\Core\Search::updateSearchIndex('post', $new['pid'], $post['message'], $post['subject']);

        Forum::update($fid);

        $new = Container::get('hooks')->fireDB('model.post.insert_topic', $new);

        return $new;
    }

    // Send notifications for new topics
    public function sendNotificationsNewTopic($post, $curPosting, $newTid)
    {
        Container::get('hooks')->fire('model.post.send_notifications_new_topic_start', $post, $curPosting, $newTid);

        // Get any subscribed users that should be notified (banned users are excluded)
        $result['where'] = [
            ['fp.read_forum' => 'IS NULL'],
            ['fp.read_forum' => '1']
        ];
        $result['select'] = ['u.id', 'u.group_id', 'u.email'];

        $result = DB::forTable('users')
                    ->tableAlias('u')
                    ->selectMany($result['select'])
                    ->innerJoin('forum_subscriptions', ['u.id', '=', 's.user_id'], 's')
                    ->leftOuterJoin('forum_perms', 'fp.forum_id='.$curPosting['id'].' AND fp.group_id=u.group_id', 'fp')
                    ->leftOuterJoin('bans', ['u.username', '=', 'b.username'], 'b')
                    ->whereNull('b.username')
                    ->whereAnyIs($result['where'])
                    ->where('s.forum_id', $curPosting['id'])
                    ->whereNotEqual('u.id', User::get()->id);
        $result = Container::get('hooks')->fireDB('model.post.send_notifications_new_topic_query', $result);
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
                $curSubscriber['prefs'] = Container::get('prefs')->loadPrefs($curSubscriber);
                // Is the subscription email for User::getPref('language', $curSubscriber['id']) cached or not?
                if (!isset($notificationEmails[$curSubscriber['prefs']['language']])) {
                    if (file_exists(ForumEnv::get('FEATHER_ROOT').'featherbb/lang/'.$curSubscriber['prefs']['language'].'/mail_templates/new_topic.tpl')) {
                        // Load the "new topic" template
                        $mailTpl = trim(file_get_contents(ForumEnv::get('FEATHER_ROOT').'featherbb/lang/'.$curSubscriber['prefs']['language'].'/mail_templates/new_topic.tpl'));
                        $mailTpl = Container::get('hooks')->fire('model.post.send_notifications_new_topic_mail_tpl', $mailTpl);

                        // Load the "new topic full" template (with post included)
                        $mailTplFull = trim(file_get_contents(ForumEnv::get('FEATHER_ROOT').'featherbb/lang/'.$curSubscriber['prefs']['language'].'/mail_templates/new_topic_full.tpl'));

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
                        $mailMessage = Container::get('hooks')->fire('model.post.send_notifications_new_topic_mail_message', $mailMessage);

                        $mailSubjectFull = str_replace('<forum_name>', $curPosting['forum_name'], $mailSubjectFull);
                        $mailMessageFull = str_replace('<topic_subject>', $cleanedSubject, $mailMessageFull);
                        $mailMessageFull = str_replace('<forum_name>', $curPosting['forum_name'], $mailMessageFull);
                        $mailMessageFull = str_replace('<poster>', $post['username'], $mailMessageFull);
                        $mailMessageFull = str_replace('<message>', $cleanedMessage, $mailMessageFull);
                        $mailMessageFull = str_replace('<topic_url>', Router::pathFor('Topic', ['id' => $newTid, 'name' => Url::slug($post['subject'])]), $mailMessageFull);
                        $mailMessageFull = str_replace('<unsubscribe_url>', Router::pathFor('unsubscribeTopic', ['id' => $newTid, 'name' => Url::slug($post['subject'])]), $mailMessageFull);
                        $mailMessageFull = str_replace('<board_mailer>', ForumSettings::get('o_board_title'), $mailMessageFull);
                        $mailMessageFull = Container::get('hooks')->fire('model.post.send_notifications_new_topic_mail_message_full', $mailMessageFull);

                        $notificationEmails[$curSubscriber['prefs']['language']][0] = $mailSubject;
                        $notificationEmails[$curSubscriber['prefs']['language']][1] = $mailMessage;
                        $notificationEmails[$curSubscriber['prefs']['language']][2] = $mailSubjectFull;
                        $notificationEmails[$curSubscriber['prefs']['language']][3] = $mailMessageFull;
                    }
                }

                // We have to double check here because the templates could be missing
                if (isset($notificationEmails[$curSubscriber['prefs']['language']])) {
                    if ($curSubscriber['prefs']['notify_with_post'] == '0') {
                        Container::get('email')->send($curSubscriber['email'], $notificationEmails[$curSubscriber['prefs']['language']][0], $notificationEmails[$curSubscriber['prefs']['language']][1]);
                    } else {
                        Container::get('email')->send($curSubscriber['email'], $notificationEmails[$curSubscriber['prefs']['language']][2], $notificationEmails[$curSubscriber['prefs']['language']][3]);
                    }
                }
            }

            Container::get('hooks')->fire('model.post.send_notifications_new_topic');

            unset($cleanedMessage);
        }
    }

    // Warn the admin if a banned user posts
    public static function warnBannedUser($post, $newPost)
    {
        Container::get('hooks')->fire('model.post.warn_banned_user_start', $post, $newPost);

        // Load the "banned email post" template
        $mailTpl = trim(file_get_contents(ForumEnv::get('FEATHER_ROOT').'featherbb/lang/'.User::getPref('language').'/mail_templates/banned_email_post.tpl'));
        $mailTpl = Container::get('hooks')->fire('model.post.warn_banned_user_mail_tpl', $mailTpl);

        // The first row contains the subject
        $firstCrlf = strpos($mailTpl, "\n");
        $mailSubject = trim(substr($mailTpl, 8, $firstCrlf-8));
        $mailMessage = trim(substr($mailTpl, $firstCrlf));

        $mailMessage = str_replace('<username>', $post['username'], $mailMessage);
        $mailMessage = str_replace('<email>', $post['email'], $mailMessage);
        $mailMessage = str_replace('<post_url>', Router::pathFor('viewPost', ['id' => $newPost['tid'], 'name' => $newPost['topic_subject'], 'pid' => $newPost['pid']]).'#p'.$newPost['pid'], $mailMessage);
        $mailMessage = str_replace('<board_mailer>', ForumSettings::get('o_board_title'), $mailMessage);
        $mailMessage = Container::get('hooks')->fire('model.post.warn_banned_user_mail_message', $mailMessage);

        Container::get('email')->send(ForumSettings::get('o_mailing_list'), $mailSubject, $mailMessage);
    }

    // Increment post count, change group if needed
    public function incrementPostCount($post, $newTid)
    {
        Container::get('hooks')->fire('model.post.increment_post_count_start', $post, $newTid);

        if (!User::get()->is_guest) {
            $increment = DB::forTable('users')
                            ->where('id', User::get()->id)
                            ->findOne()
                            ->set('last_post', $post['time'])
                            ->setExpr('num_posts', 'num_posts+1');
            $increment = Container::get('hooks')->fireDB('model.post.increment_post_count_query', $increment);
            $increment = $increment->save();

            // Promote this user to a new group if enabled
            if (User::getPref('promote.next_group') && User::get()->num_posts + 1 >= User::getPref('promote.min_posts')) {
                $newGroupId = User::getPref('promote.next_group');
                $promote = DB::forTable('users')
                            ->where('id', User::get()->id)
                            ->findOne()
                            ->set('group_id', $newGroupId);
                $promote = Container::get('hooks')->fireDB('model.post.increment_post_count_query', $promote);
                $promote = $promote->save();
            }

            // Topic tracking stuff...
            $trackedTopics = Track::getTrackedTopics();
            $trackedTopics['topics'][$newTid] = time();
            Track::setTrackedTopics($trackedTopics);
        } else {
            // Update the last_post field for guests
            $lastPost = DB::forTable('online')
                            ->where('ident', Utils::getIp())
                            ->findOne()
                            ->set('last_post', $post['time']);
            $lastPost = Container::get('hooks')->fireDB('model.post.increment_post_count_last_post', $lastPost);
            $lastPost = $lastPost->save();
        }

        Container::get('hooks')->fire('model.post.increment_post_count');
    }

    //
    // Split text into chunks ($inside contains all text inside $start and $end, and $outside contains all text outside)
    //
    public function splitText($text, $start, $end, $retab = true)
    {
        $result = [0 => [], 1 => []]; // 0 = inside, 1 = outside

        $result = Container::get('hooks')->fire('model.post.split_text_start', $result, $text, $start, $end, $retab);

        // split the text into parts
        $parts = preg_split('%'.preg_quote($start, '%').'(.*)'.preg_quote($end, '%').'%Us', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
        $numParts = count($parts);

        // preg_split results in outside parts having even indices, inside parts having odd
        for ($i = 0;$i < $numParts;$i++) {
            $result[1 - ($i % 2)][] = $parts[$i];
        }

        if (ForumSettings::get('o_indent_num_spaces') != 8 && $retab) {
            $spaces = str_repeat(' ', ForumSettings::get('o_indent_num_spaces'));
            $result[1] = str_replace("\t", $spaces, $result[1]);
        }

        $result = Container::get('hooks')->fire('model.post.split_text_start', $result);

        return $result;
    }

    // If we are quoting a message
    public static function getQuote($qid, $tid)
    {
        $quote = [];

        $quote = Container::get('hooks')->fire('model.post.get_quote_message', $quote, $qid, $tid);

        $quote['select'] = ['poster', 'message'];

        $quote = DB::forTable('posts')->selectMany($quote['select'])
                     ->where('id', $qid)
                     ->where('topic_id', $tid);
        $quote = Container::get('hooks')->fireDB('model.post.get_quote_message_query', $quote);
        $quote = $quote->findOne();

        if (!$quote) {
            throw new Error(__('Bad request'), 404);
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

            $numTokens = count($outside);
            for ($i = 0; $i < $numTokens; ++$i) {
                $quote['message'] .= $outside[$i];
                if (isset($inside[$i])) {
                    $quote['message'] .= '[code]'.$inside[$i].'[/code]';
                }
            }

            unset($inside);
        }

        if (ForumSettings::get('o_censoring') == '1') {
            $quote['message'] = Utils::censor($quote['message']);
        }

        $quote['message'] = Utils::escape($quote['message']);

        if (ForumSettings::get('p_message_bbcode') == '1') {    // Sanitize username for inclusion within QUOTE BBCode attribute.
                //   This is a bit tricky because a username can have any "special"
                //   characters such as backslash \ square brackets [] and quotes '".
                if (preg_match('/[[\]\'"]/S', $quote['poster'])) {
                    // Check if we need to quote it.
                    // Post has special chars. Escape escapes and quotes then wrap in quotes.
                    if (strpos($quote['poster'], '"') !== false && strpos($quote['poster'], '\'') === false) { // If there are double quotes but no single quotes, use single quotes,
                        $quote['poster'] = Utils::escape(str_replace('\\', '\\\\', $quote['poster']));
                        $quote['poster'] = '\''. $quote['poster'] .'#'. $qid .'\'';
                    } else { // otherwise use double quotes.
                        $quote['poster'] = Utils::escape(str_replace(['\\', '"'], ['\\\\', '\\"'], $quote['poster']));
                        $quote['poster'] = '"'. $quote['poster'] .'#'. $qid .'"';
                    }
                } else {
                    $quote['poster'] = $quote['poster'] .'#'. $qid;
                }
            $quote = '[quote='. $quote['poster'] .']'.$quote['message'].'[/quote]'."\n";
        } else {
            $quote = '> '.$quote['poster'].' '.__('wrote')."\n\n".'> '.$quote['message']."\n";
        }

        $quote = Container::get('hooks')->fire('model.post.get_quote_message', $quote);

        return $quote;
    }

    // Get the current state of checkboxes
    public function getCheckboxes($fid, $isAdmmod, $isSubscribed)
    {
        Container::get('hooks')->fire('model.post.get_checkboxes_start', $fid, $isAdmmod, $isSubscribed);

        $curIndex = 1;

        $checkboxes = [];
        if ($fid && $isAdmmod) {
            $checkboxes[] = '<label><input type="checkbox" name="stick_topic" value="1" tabindex="'.($curIndex++).'"'.(Input::post('stick_topic') ? ' checked="checked"' : '').' />'.__('Stick topic').'<br /></label>';
        }

        if (!User::get()->is_guest) {
            if (ForumSettings::get('show.smilies') == '1') {
                $checkboxes[] = '<label><input type="checkbox" name="hide_smilies" value="1" tabindex="'.($curIndex++).'"'.(Input::post('hide_smilies') ? ' checked="checked"' : '').' />'.__('Hide smilies').'<br /></label>';
            }

            if (ForumSettings::get('o_topic_subscriptions') == '1') {
                $subscrChecked = false;

                // If it's a preview
                if (Input::post('preview')) {
                    $subscrChecked = (Input::post('subscribe')) ? true : false;
                }
                // If auto subscribed
                elseif (User::getPref('auto_notify')) {
                    $subscrChecked = true;
                }
                // If already subscribed to the topic
                elseif ($isSubscribed) {
                    $subscrChecked = true;
                }

                $checkboxes[] = '<label><input type="checkbox" name="subscribe" value="1" tabindex="'.($curIndex++).'"'.($subscrChecked ? ' checked="checked"' : '').' />'.($isSubscribed ? __('Stay subscribed') : __('Subscribe')).'<br /></label>';
            }
        } elseif (ForumSettings::get('show.smilies') == '1') {
            $checkboxes[] = '<label><input type="checkbox" name="hide_smilies" value="1" tabindex="'.($curIndex++).'"'.(Input::post('hide_smilies') ? ' checked="checked"' : '').' />'.__('Hide smilies').'<br /></label>';
        }

        $checkboxes = Container::get('hooks')->fire('model.post.get_checkboxes', $checkboxes);

        return $checkboxes;
    }

    public function getEditCheckboxes($canEditSubject, $isAdmmod, $curPost, $curIndex)
    {
        Container::get('hooks')->fire('model.post.get_checkboxes_start', $canEditSubject, $isAdmmod, $curPost, $curIndex);

        $checkboxes = [];

        if ($canEditSubject && $isAdmmod) {
            if (Input::post('stick_topic') || $curPost['sticky'] == '1') {
                $checkboxes[] = '<label><input type="checkbox" name="stick_topic" value="1" checked="checked" tabindex="'.($curIndex++).'" />'.__('Stick topic').'<br /></label>';
            } else {
                $checkboxes[] = '<label><input type="checkbox" name="stick_topic" value="1" tabindex="'.($curIndex++).'" />'.__('Stick topic').'<br /></label>';
            }
        }

        if (ForumSettings::get('show.smilies') == '1') {
            if (Input::post('hide_smilies') || $curPost['hide_smilies'] == '1') {
                $checkboxes[] = '<label><input type="checkbox" name="hide_smilies" value="1" checked="checked" tabindex="'.($curIndex++).'" />'.__('Hide smilies').'<br /></label>';
            } else {
                $checkboxes[] = '<label><input type="checkbox" name="hide_smilies" value="1" tabindex="'.($curIndex++).'" />'.__('Hide smilies').'<br /></label>';
            }
        }

        if ($isAdmmod) {
            if (Request::isPost() && Input::post('silent') || Request::isPost() == '') {
                $checkboxes[] = '<label><input type="checkbox" name="silent" value="1" tabindex="'.($curIndex++).'" checked="checked" />'.__('Silent edit').'<br /></label>';
            } else {
                $checkboxes[] = '<label><input type="checkbox" name="silent" value="1" tabindex="'.($curIndex++).'" />'.__('Silent edit').'<br /></label>';
            }
        }

        $checkboxes = Container::get('hooks')->fire('model.post.get_checkboxes', $checkboxes);

        return $checkboxes;
    }

    // Display the topic review if needed
    public function review($tid)
    {
        $postData = [];

        $postData = Container::get('hooks')->fire('model.post.topic_review_start', $postData, $tid);

        $selectTopicReview = ['poster', 'message', 'hide_smilies', 'posted'];

        $result = DB::forTable('posts')->selectMany($selectTopicReview)
                    ->where('topic_id', $tid)
                    ->orderByDesc('id');
        $result = Container::get('hooks')->fire('model.post.topic_review_query', $result);
        $result = $result->findMany();

        foreach ($result as $curPost) {
            $curPost['message'] = Container::get('parser')->parseMessage($curPost['message'], $curPost['hide_smilies']);
            $postData[] = $curPost;
        }

        $postData = Container::get('hooks')->fire('model.post.topic_review', $postData);

        return $postData;
    }

    public function displayIpAddress($pid)
    {
        $pid = Container::get('hooks')->fire('model.post.display_ip_address_post_start', $pid);

        $ip = DB::forTable('posts')
            ->where('id', $pid);
        $ip = Container::get('hooks')->fireDB('model.post.display_ip_address_post_query', $ip);
        $ip = $ip->findOneCol('poster_ip');

        if (!$ip) {
            throw new Error(__('Bad request'), 404);
        }

        $ip = Container::get('hooks')->fire('model.post.display_ip_address_post', $ip);

        throw new Error(sprintf(__('Host info 1'), $ip).'<br />'.sprintf(__('Host info 2'), @gethostbyaddr($ip)).'<br /><br /><a href="'.Router::pathFor('usersIpShow', ['ip' => $ip]).'">'.__('Show more users').'</a>', 400, true, true);
    }
}
