<?php

/**
 * Copyright (C) 2015-2017 FeatherBB
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
        $cur_posting = $this->model->getInfoPost(false, $args['id']);

        if (!is_object($cur_posting)) {
            return $cur_posting;
        }

        $is_admmod = $this->model->checkPermissions($cur_posting, null, $args['id']);

        if (!is_bool($is_admmod)) {
            return $is_admmod;
        }

        // Start with a clean slate
        $errors = [];

        // Let's see if everything went right
        $errors = $this->model->checkErrorsBeforePost($args['id'], $errors);

        // Setup some variables before post
        $post = $this->model->setupVariables($errors, $is_admmod);

        // Did everything go according to plan?
        if (empty($errors)) {
            // If it's a new topic
            // Insert the topic, get the new_pid
            $new = $this->model->insertTopic($post, $args['id']);

            // Should we send out notifications?
            if (ForumSettings::get('o_forum_subscriptions') == '1') {
                $this->model->sendNotificationsNewTopic($post, $cur_posting, $new['tid']);
            }

            // If we previously found out that the email was banned
            if ($this->user->is_guest && isset($errors['banned_email']) && ForumSettings::get('o_mailing_list') != '') {
                \FeatherBB\Model\Post::warnBannedUser($post, $new['pid']);
            }

            // If the posting user is logged in, increment his/her post count
            $this->model->incrementPostCount($post, $new['tid']);

            return Router::redirect(Router::pathFor('postApi', ['id' => $new['pid']]));
        } else {
            return json_encode($errors, JSON_PRETTY_PRINT);
        }
    }

    public function newReply($req, $res, $args)
    {
        // Fetch some info about the topic and/or the forum
        $cur_posting = $this->model->getInfoPost($args['id'], false);

        if (!is_object($cur_posting)) {
            return $cur_posting;
        }

        $is_subscribed = $args['id'] && $cur_posting['is_subscribed'];

        $is_admmod = $this->model->checkPermissions($cur_posting, null, $args['id']);

        if (!is_bool($is_admmod)) {
            return $is_admmod;
        }

        // Start with a clean slate
        $errors = [];

        // Let's see if everything went right
        $errors = $this->model->checkErrorsBeforePost($args['id'], $errors);

        // Setup some variables before post
        $post = $this->model->setupVariables($errors, $is_admmod);

        // Append quote if needed
        if (isset($args['qid'])) {
            $post['message'] = \FeatherBB\Model\Post::getQuote($args['qid'], $args['id']).$post['message'];
        }

        // Did everything go according to plan?
        if (empty($errors)) {
            // It's a reply
            // Insert the reply, get the new_pid
            $new = $this->model->insertReply($post, $args['id'], $cur_posting, $is_subscribed);

            // Should we send out notifications?
            if (ForumSettings::get('o_topic_subscriptions') == '1') {
                \FeatherBB\Model\Post::sendNotificationsReply($args['id'], $cur_posting, $new['pid'], $post);
            }

            // If we previously found out that the email was banned
            if ($this->user->is_guest && isset($errors['banned_email']) && ForumSettings::get('o_mailing_list') != '') {
                \FeatherBB\Model\Post::warnBannedUser($post, $new['pid']);
            }

            // If the posting user is logged in, increment his/her post count
            $this->model->incrementPostCount($post, $new['id']);

            return Router::redirect(Router::pathFor('postApi', ['id' => $new['pid']]));
        } else {
            return json_encode($errors, JSON_PRETTY_PRINT);
        }
    }
}
