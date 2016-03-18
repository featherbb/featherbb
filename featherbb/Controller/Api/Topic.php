<?php

/**
 * Copyright (C) 2015-2016 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace FeatherBB\Controller\Api;

class Topic
{
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
        $postModel = new \FeatherBB\Model\Post;
        // Fetch some info about the topic and/or the forum
        $cur_posting = $postModel->get_info_post($args['tid'], $args['fid']);

        $is_subscribed = $args['tid'] && $cur_posting['is_subscribed'];

        // Is someone trying to post into a redirect forum?
        if ($cur_posting['redirect_url'] != '') {
            throw new Error(__('Bad request'), 400);
        }

        // Sort out who the moderators are and if we are currently a moderator (or an admin)
        $mods_array = ($cur_posting['moderators'] != '') ? unserialize($cur_posting['moderators']) : array();
        $is_admmod = (User::get()->g_id == ForumEnv::get('FEATHER_ADMIN') || (User::get()->g_moderator == '1' && array_key_exists(User::get()->username, $mods_array))) ? true : false;

        // Do we have permission to post?
        if ((($args['tid'] && (($cur_posting['post_replies'] == '' && User::get()->g_post_replies == '0') || $cur_posting['post_replies'] == '0')) ||
                ($args['fid'] && (($cur_posting['post_topics'] == '' && User::get()->g_post_topics == '0') || $cur_posting['post_topics'] == '0')) ||
                (isset($cur_posting['closed']) && $cur_posting['closed'] == '1')) &&
            !$is_admmod) {
            throw new Error(__('No permission'), 403);
        }

        // Start with a clean slate
        $errors = array();

        // Did someone just hit "Submit" or "Preview"?
        if (Request::isPost()) {

            // Include $pid and $page if needed for confirm_referrer function called in check_errors_before_post()
            if (Input::post('pid')) {
                $pid = Input::post('pid');
            } else {
                $pid = '';
            }

            if (Input::post('page')) {
                $page = Input::post('page');
            } else {
                $page = '';
            }

            // Let's see if everything went right
            $errors = $postModel->check_errors_before_post($args['fid'], $args['tid'], $args['qid'], $pid, $page, $errors);

            // Setup some variables before post
            $post = $postModel->setup_variables($errors, $is_admmod);

            // Did everything go according to plan?
            if (empty($errors) && !Input::post('preview')) {
                // If it's a reply
                if ($args['tid']) {
                    // Insert the reply, get the new_pid
                    $new = $postModel->insert_reply($post, $args['tid'], $cur_posting, $is_subscribed);

                    // Should we send out notifications?
                    if (ForumSettings::get('o_topic_subscriptions') == '1') {
                        $postModel->send_notifications_reply($args['tid'], $cur_posting, $new['pid'], $post);
                    }
                }
                // If it's a new topic
                elseif ($args['fid']) {
                    // Insert the topic, get the new_pid
                    $new = $postModel->insert_topic($post, $args['fid']);

                    // Should we send out notifications?
                    if (ForumSettings::get('o_forum_subscriptions') == '1') {
                        $postModel->send_notifications_new_topic($post, $cur_posting, $new['tid']);
                    }
                }

                // If we previously found out that the email was banned
                if (User::get()->is_guest && isset($errors['banned_email']) && ForumSettings::get('o_mailing_list') != '') {
                    $postModel->warn_banned_user($post, $new['pid']);
                }

                // If the posting user is logged in, increment his/her post count
                if (!User::get()->is_guest) {
                    $postModel->increment_post_count($post, $new['tid']);
                }

                return Router::redirect(Router::pathFor('viewPost', ['pid' => $new['pid']]).'#p'.$new['pid'], __('Post redirect'));
            }
        }
    }
}