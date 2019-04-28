<?php

/**
 * Copyright (C) 2015-2019 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace FeatherBB\Controller;

use FeatherBB\Core\Error;
use FeatherBB\Core\Interfaces\Container;
use FeatherBB\Core\Interfaces\ForumEnv;
use FeatherBB\Core\Interfaces\ForumSettings;
use FeatherBB\Core\Interfaces\Hooks;
use FeatherBB\Core\Interfaces\Input;
use FeatherBB\Core\Interfaces\Lang;
use FeatherBB\Core\Interfaces\Parser;
use FeatherBB\Core\Interfaces\Request;
use FeatherBB\Core\Interfaces\Router;
use FeatherBB\Core\Interfaces\User;
use FeatherBB\Core\Interfaces\View;
use FeatherBB\Core\Url;
use FeatherBB\Core\Utils;

class Post
{
    public function __construct()
    {
        $this->model = new \FeatherBB\Model\Post();
        Lang::load('prof_reg');
        Lang::load('delete');
        Lang::load('post');
        Lang::load('misc');
        Lang::load('register');
        Lang::load('antispam');
        Lang::load('bbeditor');
    }

    public function newreply($req, $res, $args)
    {
        Hooks::fire('controller.post.newreply');

        return $this->newPost($req, $res, $args);
    }

    public function newPost($req, $res, $args)
    {
        if (!isset($args['fid'])) {
            $args['fid'] = null;
        }

        if (!isset($args['tid'])) {
            $args['tid'] = null;
        }

        if (!isset($args['qid'])) {
            $args['qid'] = null;
        }

        Hooks::fire('controller.post.create', $args['fid'], $args['tid'], $args['qid']);

        // Antispam feature
        $langAntispamQuestions = require ForumEnv::get('FEATHER_ROOT').'featherbb/lang/'.User::getPref('language').'/antispam.php';
        $indexQuestions = rand(0, count($langAntispamQuestions)-1);

        // If $_pOST['username'] is filled, we are facing a bot
        if (Input::post('username')) {
            throw new Error(__('Bad request'), 400);
        }

        // Fetch some info about the topic and/or the forum
        $curPosting = $this->model->getInfoPost($args['tid'], $args['fid']);

        $isSubscribed = $args['tid'] && $curPosting['is_subscribed'];

        // Is someone trying to post into a redirect forum?
        if ($curPosting['redirect_url'] != '') {
            throw new Error(__('Bad request'), 400);
        }

        // Sort out who the moderators are and if we are currently a moderator (or an admin)
        $modsArray = ($curPosting['moderators'] != '') ? unserialize($curPosting['moderators']) : [];
        $isAdmmod = (User::isAdmin() || (User::isAdminMod() && array_key_exists(User::get()->username, $modsArray))) ? true : false;

        // Do we have permission to post?
        if ((($args['tid'] && (($curPosting['post_replies'] == '' && !User::can('topic.reply')) || $curPosting['post_replies'] == 0)) ||
                ($args['fid'] && (($curPosting['post_topics'] == '' && !User::can('topic.post')) || $curPosting['post_topics'] == 0)) ||
                (isset($curPosting['closed']) && $curPosting['closed'] == 1)) &&
                !$isAdmmod) {
            throw new Error(__('No permission'), 403);
        }

        // Start with a clean slate
        $errors = [];

        $post = '';

        // Did someone just hit "Submit" or "Preview"?
        if (Request::isPost()) {

            // Let's see if everything went right
            $errors = $this->model->checkErrorsPost($args['fid'], $errors);

            // Setup some variables before post
            $post = $this->model->setupVariables($errors, $isAdmmod);

            // Did everything go according to plan?
            if (empty($errors) && !Input::post('preview')) {
                // If it's a reply
                if ($args['tid']) {
                    // Insert the reply, get the new_pid
                    $new = $this->model->reply($post, $args['tid'], $curPosting, $isSubscribed);

                    // Should we send out notifications?
                    if (ForumSettings::get('o_topic_subscriptions') == 1) {
                        $this->model->sendNotificationsReply($args['tid'], $curPosting, $new['pid'], $post);
                    }
                }
                // If it's a new topic
                elseif ($args['fid']) {
                    // Insert the topic, get the new_pid
                    $new = $this->model->insertTopic($post, $args['fid']);

                    // Should we send out notifications?
                    if (ForumSettings::get('o_forum_subscriptions') == 1) {
                        $this->model->sendNotificationsNewTopic($post, $curPosting, $new['tid']);
                    }
                }

                // If we previously found out that the email was banned
                if (User::get()->is_guest && isset($errors['banned_email']) && ForumSettings::get('o_mailing_list') != '') {
                    $this->model->warnBannedUser($post, $new);
                }

                // If the posting user is logged in, increment his/her post count
                if (!User::get()->is_guest) {
                    $this->model->incrementPostCount($post, $new['tid']);
                }

                return Router::redirect(Router::pathFor('viewPost', ['id' => $new['tid'], 'name' => $new['topic_subject'], 'pid' => $new['pid']]).'#p'.$new['pid'], __('Post redirect'));
            }
        }

        $quote = '';

        // If a topic ID was specified in the url (it's a reply)
        if ($args['tid']) {
            $action = __('Post a reply');
            $form = '<form id="post" method="post" action="'.Router::pathFor('newReply', ['tid' => $args['tid']]).'">';

                // If a quote ID was specified in the url
                if (isset($args['qid'])) {
                    $quote = $this->model->getQuote($args['qid'], $args['tid']);
                    $form = '<form id="post" method="post" action="'.Router::pathFor('newQuoteReply', ['tid' => $args['tid'], 'qid' => $args['qid']]).'">';
                }
        }
        // If a forum ID was specified in the url (new topic)
        elseif ($args['fid']) {
            $action = __('Post new topic');
            $form = '<form id="post" method="post" action="'.Router::pathFor('newTopic', ['fid' => $args['fid']]).'">';
        } else {
            throw new Error(__('Bad request'), 404);
        }

        $urlForum = Url::slug($curPosting['forum_name']);

        $isSubscribed = $args['tid'] && $curPosting['is_subscribed'];

        if (isset($curPosting['subject'])) {
            $urlTopic = Url::slug($curPosting['subject']);
        } else {
            $urlTopic = '';
        }

        // Get the current state of checkboxes
        $checkboxes = $this->model->getCheckboxes($args['fid'], $isAdmmod, $isSubscribed);

        // Check to see if the topic review is to be displayed
        if ($args['tid'] && ForumSettings::get('o_topic_review') != 0) {
            $postData = $this->model->review($args['tid']);
        } else {
            $postData = '';
        }

        if (Input::post('preview')) {
            $previewMessage = Parser::parseMessage($post['message'], $post['hide_smilies']);
            $previewMessage = Hooks::fire('controller.post.edit.preview', $previewMessage);
        } else {
            $previewMessage = '';
        }

        return View::setPageInfo([
                'title' => [Utils::escape(ForumSettings::get('o_board_title')), $action],
                'active_page' => 'post',
                'post' => $post,
                'tid' => $args['tid'],
                'fid' => $args['fid'],
                'cur_posting' => $curPosting,
                'lang_antispam_questions' => $langAntispamQuestions,
                'index_questions' => $indexQuestions,
                'checkboxes' => $checkboxes,
                'action' => $action,
                'form' => $form,
                'post_data' => $postData,
                'preview_message' => $previewMessage,
                'url_forum' => $urlForum,
                'url_topic' => $urlTopic,
                'quote' => $quote,
                'errors'    =>    $errors,
            ]
        )->addTemplate('@forum/post')->display();
    }

    public function delete($req, $res, $args)
    {
        Hooks::fire('controller.post.delete');

        // Fetch some information about the post, the topic and the forum
        $curPost = $this->model->getInfoDelete($args['id']);

        if (ForumSettings::get('o_censoring') == 1) {
            $curPost['subject'] = Utils::censor($curPost['subject']);
        }

        // Sort out who the moderators are and if we are currently a moderator (or an admin)
        $modsArray = ($curPost['moderators'] != '') ? unserialize($curPost['moderators']) : [];
        $isAdmmod = (User::isAdmin() || (User::isAdminMod() && array_key_exists(User::get()->username, $modsArray))) ? true : false;

        $isTopicPost = ($args['id'] == $curPost['first_post_id']) ? true : false;

        // Do we have permission to edit this post?
        if ((!User::can('post.delete') ||
                (!User::can('topic.delete') && $isTopicPost) ||
                $curPost['poster_id'] != User::get()->id ||
                $curPost['closed'] == 1) &&
                !$isAdmmod) {
            throw new Error(__('No permission'), 403);
        }

        if ($isAdmmod && User::get()->g_id != ForumEnv::get('FEATHER_ADMIN') && in_array($curPost['poster_id'], Utils::getAdminIds())) {
            throw new Error(__('No permission'), 403);
        }

        if (Request::isPost()) {
            return $this->model->handleDeletion($isTopicPost, $args['id'], $curPost);
        }

        $curPost['message'] = Parser::parseMessage($curPost['message'], $curPost['hide_smilies']);

        return View::setPageInfo([
            'title' => [Utils::escape(ForumSettings::get('o_board_title')), __('Delete post')],
            'active_page' => 'delete',
            'cur_post' => $curPost,
            'id' => $args['id'],
            'is_topic_post' => $isTopicPost
        ])->addTemplate('@forum/delete')->display();
    }

    public function editpost($req, $res, $args)
    {
        Hooks::fire('controller.post.edit');

        // Fetch some information about the post, the topic and the forum
        $curPost = $this->model->getInfoEdit($args['id']);

        // Sort out who the moderators are and if we are currently a moderator (or an admin)
        $modsArray = ($curPost['moderators'] != '') ? unserialize($curPost['moderators']) : [];
        $isAdmmod = (User::isAdmin() || (User::isAdminMod() && array_key_exists(User::get()->username, $modsArray))) ? true : false;

        $canEditSubject = $args['id'] == $curPost['first_post_id'];

        if (ForumSettings::get('o_censoring') == 1) {
            $curPost['subject'] = Utils::censor($curPost['subject']);
            $curPost['message'] = Utils::censor($curPost['message']);
        }

        // Do we have permission to edit this post?
        if ((!User::can('post.edit') || $curPost['poster_id'] != User::get()->id || $curPost['closed'] == 1) && !$isAdmmod) {
            throw new Error(__('No permission'), 403);
        }

        if ($isAdmmod && User::get()->g_id != ForumEnv::get('FEATHER_ADMIN') && in_array($curPost['poster_id'], Utils::getAdminIds())) {
            throw new Error(__('No permission'), 403);
        }

        // Start with a clean slate
        $errors = [];

        if (Request::isPost()) {
            Hooks::fire('controller.post.edit.submit', $args['id']);

            // Let's see if everything went right
            $errors = $this->model->checkErrorsEdit($canEditSubject, $errors, $isAdmmod);

            // Setup some variables before post
            $post = $this->model->setupEditVariables($curPost, $isAdmmod, $canEditSubject, $errors);

            // Did everything go according to plan?
            if (empty($errors) && !Input::post('preview')) {
                Hooks::fire('controller.post.edit.valid', $args['id']);
                // Edit the post
                $this->model->editPost($args['id'], $canEditSubject, $post, $curPost, $isAdmmod);

                return Router::redirect(Router::pathFor('viewPost', ['id' => $curPost->tid, 'name' => Input::post('topic_subject'), 'pid' => $args['id']]).'#p'.$args['id'], __('Edit redirect'));
            }
        } else {
            $post = '';
        }

        if (Input::post('preview')) {
            $previewMessage = Parser::parseMessage($post['message'], $post['hide_smilies']);
            $previewMessage = Hooks::fire('controller.post.edit.preview', $previewMessage);
        } else {
            $previewMessage = '';
        }

        return View::setPageInfo([
                'title' => [Utils::escape(ForumSettings::get('o_board_title')), __('Edit post')],
                'cur_post' => $curPost,
                'errors' => $errors,
                'preview_message' => $previewMessage,
                'id' => $args['id'],
                'checkboxes' => $this->model->getEditCheckboxes($canEditSubject, $isAdmmod, $curPost, 1),
                'can_edit_subject' => $canEditSubject,
                'post' => $post,
            ]
        )->addTemplate('@forum/edit')->display();
    }

    public function report($req, $res, $args)
    {
        $args['id'] = Hooks::fire('controller.post.report', $args['id']);

        if (Request::isPost()) {
            return $this->model->report($args['id']);
        }

        // Fetch some info about the post, the topic and the forum
        $curPost = $this->model->getInfoReport($args['id']);

        if (ForumSettings::get('o_censoring') == 1) {
            $curPost['subject'] = Utils::censor($curPost['subject']);
        }

        return View::setPageInfo([
                'title' => [Utils::escape(ForumSettings::get('o_board_title')), __('Report post')],
                'active_page' => 'report',
                'id' => $args['id'],
                'cur_post' => $curPost
            ]
        )->addTemplate('@forum/misc/report')->display();
    }

    public function gethost($req, $res, $args)
    {
        $args['pid'] = Hooks::fire('controller.post.gethost', $args['pid']);

        $this->model->displayIpAddress($args['pid']);
    }
}
