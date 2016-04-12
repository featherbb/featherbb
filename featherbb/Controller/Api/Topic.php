<?php

/**
 * Copyright (C) 2015-2016 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace FeatherBB\Controller\Api;

class Topic extends Api
{
    private $model;

    public function __construct()
    {
        $this->model = new \FeatherBB\Model\Api\Topic();
    }

    public function display($req, $res, $args)
    {
        return json_encode($this->model->display($args['id']), JSON_PRETTY_PRINT);
    }

    public function newTopic($req, $res, $args)
    {
        // Fetch some info about the topic and/or the forum
        $cur_posting = $this->model->get_info_post(false, $args['id']);

        $is_admmod = $this->model->checkPermissions($cur_posting, null, $args['id']);

        if (!is_bool($is_admmod)) {
            return $is_admmod;
        }

        // Start with a clean slate
        $errors = array();

        // Let's see if everything went right
        $errors = $this->model->check_errors_before_post($args['id'], $errors);

        // Setup some variables before post
        $post = $this->model->setup_variables($errors, $is_admmod);

        // Did everything go according to plan?
        if (empty($errors)) {
            // If it's a new topic
            // Insert the topic, get the new_pid
            $new = $this->model->insert_topic($post, $args['id']);

            // Should we send out notifications?
            if (ForumSettings::get('o_forum_subscriptions') == '1') {
                $this->model->send_notifications_new_topic($post, $cur_posting, $new['tid']);
            }

            // If we previously found out that the email was banned
            if ($this->user->is_guest && isset($errors['banned_email']) && ForumSettings::get('o_mailing_list') != '') {
                \FeatherBB\Model\Post::warn_banned_user($post, $new['pid']);
            }

            // If the posting user is logged in, increment his/her post count
            $this->model->increment_post_count($post, $new['tid']);

            return Router::redirect(Router::pathFor('postApi', ['id' => $new['pid']]));
        }
        else {
            return json_encode($errors, JSON_PRETTY_PRINT);
        }
    }

    public function newReply($req, $res, $args)
    {
        // Fetch some info about the topic and/or the forum
        $cur_posting = $this->model->get_info_post($args['id'], false);

        $is_subscribed = $args['id'] && $cur_posting['is_subscribed'];

        $is_admmod = $this->model->checkPermissions($cur_posting, null, $args['id']);

        if (!is_bool($is_admmod)) {
            return $is_admmod;
        }

        // Start with a clean slate
        $errors = array();

        // Let's see if everything went right
        $errors = $this->model->check_errors_before_post($args['id'], $errors);

        // Setup some variables before post
        $post = $this->model->setup_variables($errors, $is_admmod);

        // Did everything go according to plan?
        if (empty($errors)) {
            // It's a reply
            // Insert the reply, get the new_pid
            $new = $this->model->insert_reply($post, $args['id'], $cur_posting, $is_subscribed);

            // Should we send out notifications?
            if (ForumSettings::get('o_topic_subscriptions') == '1') {
                \FeatherBB\Model\Post::send_notifications_reply($args['id'], $cur_posting, $new['pid'], $post);
            }

            // If we previously found out that the email was banned
            if ($this->user->is_guest && isset($errors['banned_email']) && ForumSettings::get('o_mailing_list') != '') {
                \FeatherBB\Model\Post::warn_banned_user($post, $new['pid']);
            }

            // If the posting user is logged in, increment his/her post count
            $this->model->increment_post_count($post, $new['id']);

            return Router::redirect(Router::pathFor('postApi', ['id' => $new['pid']]));
        }
        else {
            return json_encode($errors, JSON_PRETTY_PRINT);
        }
    }
}