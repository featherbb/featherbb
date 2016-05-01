<?php

/**
 * Copyright (C) 2015-2016 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace FeatherBB\Controller;

use FeatherBB\Core\Error;
use FeatherBB\Core\Url;
use FeatherBB\Core\Utils;

class Post
{
    public function __construct()
    {
        $this->model = new \FeatherBB\Model\Post();
        translate('prof_reg');
        translate('delete');
        translate('post');
        translate('misc');
        translate('register');
        translate('antispam');
        translate('bbeditor');
    }

    public function newreply($req, $res, $args)
    {
        Container::get('hooks')->fire('controller.post.newreply');

        return $this->newpost($req, $res, $args);
    }

    public function newpost($req, $res, $args)
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

        Container::get('hooks')->fire('controller.post.create', $args['fid'], $args['tid'], $args['qid']);

        // Antispam feature
        $lang_antispam_questions = require ForumEnv::get('FEATHER_ROOT').'featherbb/lang/'.User::getPref('language').'/antispam.php';
        $index_questions = rand(0, count($lang_antispam_questions)-1);

        // If $_POST['username'] is filled, we are facing a bot
        if (Input::post('username')) {
            throw new Error(__('Bad request'), 400);
        }

        // Fetch some info about the topic and/or the forum
        $cur_posting = $this->model->get_info_post($args['tid'], $args['fid']);

        $is_subscribed = $args['tid'] && $cur_posting['is_subscribed'];

        // Is someone trying to post into a redirect forum?
        if ($cur_posting['redirect_url'] != '') {
            throw new Error(__('Bad request'), 400);
        }

        // Sort out who the moderators are and if we are currently a moderator (or an admin)
        $mods_array = ($cur_posting['moderators'] != '') ? unserialize($cur_posting['moderators']) : array();
        $is_admmod = (User::isAdmin() || (User::isAdminMod() && array_key_exists(User::get()->username, $mods_array))) ? true : false;

        // Do we have permission to post?
        if ((($args['tid'] && (($cur_posting['post_replies'] == '' && !User::can('topic.reply')) || $cur_posting['post_replies'] == '0')) ||
                ($args['fid'] && (($cur_posting['post_topics'] == '' && !User::can('topic.post')) || $cur_posting['post_topics'] == '0')) ||
                (isset($cur_posting['closed']) && $cur_posting['closed'] == '1')) &&
                !$is_admmod) {
            throw new Error(__('No permission'), 403);
        }

        // Start with a clean slate
        $errors = array();

        $post = '';

        // Did someone just hit "Submit" or "Preview"?
        if (Request::isPost()) {

            // Let's see if everything went right
            $errors = $this->model->check_errors_before_post($args['fid'], $errors);

            // Setup some variables before post
            $post = $this->model->setup_variables($errors, $is_admmod);

            // Did everything go according to plan?
            if (empty($errors) && !Input::post('preview')) {
                // If it's a reply
                if ($args['tid']) {
                    // Insert the reply, get the new_pid
                    $new = $this->model->insert_reply($post, $args['tid'], $cur_posting, $is_subscribed);

                    // Should we send out notifications?
                    if (ForumSettings::get('o_topic_subscriptions') == '1') {
                        $this->model->send_notifications_reply($args['tid'], $cur_posting, $new['pid'], $post);
                    }
                }
                // If it's a new topic
                elseif ($args['fid']) {
                    // Insert the topic, get the new_pid
                    $new = $this->model->insert_topic($post, $args['fid']);

                    // Should we send out notifications?
                    if (ForumSettings::get('o_forum_subscriptions') == '1') {
                        $this->model->send_notifications_new_topic($post, $cur_posting, $new['tid']);
                    }
                }

                // If we previously found out that the email was banned
                if (User::get()->is_guest && isset($errors['banned_email']) && ForumSettings::get('o_mailing_list') != '') {
                    $this->model->warn_banned_user($post, $new);
                }

                // If the posting user is logged in, increment his/her post count
                if (!User::get()->is_guest) {
                    $this->model->increment_post_count($post, $new['tid']);
                }
                // return var_dump($post, $new);

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
                    $quote = $this->model->get_quote_message($args['qid'], $args['tid']);
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

        $url_forum = Url::url_friendly($cur_posting['forum_name']);

        $is_subscribed = $args['tid'] && $cur_posting['is_subscribed'];

        if (isset($cur_posting['subject'])) {
            $url_topic = Url::url_friendly($cur_posting['subject']);
        } else {
            $url_topic = '';
        }

        // Get the current state of checkboxes
        $checkboxes = $this->model->get_checkboxes($args['fid'], $is_admmod, $is_subscribed);

        // Check to see if the topic review is to be displayed
        if ($args['tid'] && ForumSettings::get('o_topic_review') != '0') {
            $post_data = $this->model->topic_review($args['tid']);
        } else {
            $post_data = '';
        }

        return View::setPageInfo(array(
                'title' => array(Utils::escape(ForumSettings::get('o_board_title')), $action),
                'active_page' => 'post',
                'post' => $post,
                'tid' => $args['tid'],
                'fid' => $args['fid'],
                'cur_posting' => $cur_posting,
                'lang_antispam_questions' => $lang_antispam_questions,
                'index_questions' => $index_questions,
                'checkboxes' => $checkboxes,
                'action' => $action,
                'form' => $form,
                'post_data' => $post_data,
                'url_forum' => $url_forum,
                'url_topic' => $url_topic,
                'quote' => $quote,
                'errors'    =>    $errors,
            )
        )->addTemplate('post.php')->display();
    }

    public function delete($req, $res, $args)
    {
        Container::get('hooks')->fire('controller.post.delete');

        // Fetch some information about the post, the topic and the forum
        $cur_post = $this->model->get_info_delete($args['id']);

        if (ForumSettings::get('o_censoring') == '1') {
            $cur_post['subject'] = Utils::censor($cur_post['subject']);
        }

        // Sort out who the moderators are and if we are currently a moderator (or an admin)
        $mods_array = ($cur_post['moderators'] != '') ? unserialize($cur_post['moderators']) : array();
        $is_admmod = (User::isAdmin() || (User::isAdminMod() && array_key_exists(User::get()->username, $mods_array))) ? true : false;

        $is_topic_post = ($args['id'] == $cur_post['first_post_id']) ? true : false;

        // Do we have permission to edit this post?
        if ((!User::can('post.delete') ||
                (!User::can('topic.delete') && $is_topic_post) ||
                $cur_post['poster_id'] != User::get()->id ||
                $cur_post['closed'] == '1') &&
                !$is_admmod) {
            throw new Error(__('No permission'), 403);
        }

        if ($is_admmod && User::get()->g_id != ForumEnv::get('FEATHER_ADMIN') && in_array($cur_post['poster_id'], Utils::get_admin_ids())) {
            throw new Error(__('No permission'), 403);
        }

        if (Request::isPost()) {
            return $this->model->handle_deletion($is_topic_post, $args['id'], $cur_post);
        }

        $cur_post['message'] = Container::get('parser')->parse_message($cur_post['message'], $cur_post['hide_smilies']);

        return View::setPageInfo(array(
            'title' => array(Utils::escape(ForumSettings::get('o_board_title')), __('Delete post')),
            'active_page' => 'delete',
            'cur_post' => $cur_post,
            'id' => $args['id'],
            'is_topic_post' => $is_topic_post
        ))->addTemplate('delete.php')->display();
    }

    public function editpost($req, $res, $args)
    {
        Container::get('hooks')->fire('controller.post.edit');

        // Fetch some information about the post, the topic and the forum
        $cur_post = $this->model->get_info_edit($args['id']);

        // Sort out who the moderators are and if we are currently a moderator (or an admin)
        $mods_array = ($cur_post['moderators'] != '') ? unserialize($cur_post['moderators']) : array();
        $is_admmod = (User::isAdmin() || (User::isAdminMod() && array_key_exists(User::get()->username, $mods_array))) ? true : false;

        $can_edit_subject = $args['id'] == $cur_post['first_post_id'];

        if (ForumSettings::get('o_censoring') == '1') {
            $cur_post['subject'] = Utils::censor($cur_post['subject']);
            $cur_post['message'] = Utils::censor($cur_post['message']);
        }

        // Do we have permission to edit this post?
        if ((!User::can('post.edit') || $cur_post['poster_id'] != User::get()->id || $cur_post['closed'] == '1') && !$is_admmod) {
            throw new Error(__('No permission'), 403);
        }

        if ($is_admmod && User::get()->g_id != ForumEnv::get('FEATHER_ADMIN') && in_array($cur_post['poster_id'], Utils::get_admin_ids())) {
            throw new Error(__('No permission'), 403);
        }

        // Start with a clean slate
        $errors = array();

        if (Request::isPost()) {
            Container::get('hooks')->fire('controller.post.edit.submit', $args['id']);

            // Let's see if everything went right
            $errors = $this->model->check_errors_before_edit($can_edit_subject, $errors, $is_admmod);

            // Setup some variables before post
            $post = $this->model->setup_edit_variables($cur_post, $is_admmod, $can_edit_subject, $errors);

            // Did everything go according to plan?
            if (empty($errors) && !Input::post('preview')) {
                Container::get('hooks')->fire('controller.post.edit.valid', $args['id']);
                // Edit the post
                $this->model->edit_post($args['id'], $can_edit_subject, $post, $cur_post, $is_admmod);

                return Router::redirect(Router::pathFor('viewPost', ['id' => $cur_post->tid, 'name' => Input::post('topic_subject'), 'pid' => $args['id']]).'#p'.$args['id'], __('Edit redirect'));
            }
        } else {
            $post = '';
        }

        if (Input::post('preview')) {
            $preview_message = Container::get('parser')->parse_message($post['message'], $post['hide_smilies']);
            $preview_message = Container::get('hooks')->fire('controller.post.edit.preview', $preview_message);
        } else {
            $preview_message = '';
        }

        return View::setPageInfo(array(
                'title' => array(Utils::escape(ForumSettings::get('o_board_title')), __('Edit post')),
                'cur_post' => $cur_post,
                'errors' => $errors,
                'preview_message' => $preview_message,
                'id' => $args['id'],
                'checkboxes' => $this->model->get_edit_checkboxes($can_edit_subject, $is_admmod, $cur_post, 1),
                'can_edit_subject' => $can_edit_subject,
                'post' => $post,
            )
        )->addTemplate('edit.php')->display();
    }

    public function report($req, $res, $args)
    {
        $args['id'] = Container::get('hooks')->fire('controller.post.report', $args['id']);

        if (Request::isPost()) {
            return $this->model->insert_report($args['id']);
        }

        // Fetch some info about the post, the topic and the forum
        $cur_post = $this->model->get_info_report($args['id']);

        if (ForumSettings::get('o_censoring') == '1') {
            $cur_post['subject'] = Utils::censor($cur_post['subject']);
        }

        return View::setPageInfo(array(
                'title' => array(Utils::escape(ForumSettings::get('o_board_title')), __('Report post')),
                'active_page' => 'report',
                'id' => $args['id'],
                'cur_post' => $cur_post
            )
        )->addTemplate('misc/report.php')->display();
    }

    public function gethost($req, $res, $args)
    {
        $args['pid'] = Container::get('hooks')->fire('controller.post.gethost', $args['pid']);

        $this->model->display_ip_address($args['pid']);
    }
}
