<?php

/**
 * Copyright (C) 2015-2017 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace FeatherBB\Controller\Api;

use FeatherBB\Core\Utils;

class Post extends Api
{
    private $model;
    
    public function __construct()
    {
        $this->model = new \FeatherBB\Model\Api\Post();
    }

    public function display($req, $res, $args)
    {
        return json_encode($this->model->display($args['id']), JSON_PRETTY_PRINT);
    }

    public function delete($req, $res, $args)
    {
        // Fetch some information about the post, the topic and the forum
        $cur_post = $this->model->getInfoDelete($args['id']);

        if (!is_object($cur_post)) {
            return $cur_post;
        }

        if (ForumSettings::get('o_censoring') == '1') {
            $cur_post['subject'] = Utils::censor($cur_post['subject']);
        }

        // Sort out who the moderators are and if we are currently a moderator (or an admin)
        $is_topic_post = $this->model->getDeletePermissions($cur_post, $args);

        \FeatherBB\Model\Post::handleDeletion($is_topic_post, $args['id'], $cur_post);

        return json_encode("Success", JSON_PRETTY_PRINT);
    }

    public function update($req, $res, $args)
    {
        // Fetch some information about the post, the topic and the forum
        $cur_post = $this->model->getInfoEdit($args['id']);

        if (!is_object($cur_post)) {
            return $cur_post;
        }

        $is_admmod = $this->model->getEditPermissions($cur_post);

        $can_edit_subject = $args['id'] == $cur_post['first_post_id'];

        if (ForumSettings::get('o_censoring') == '1') {
            $cur_post['subject'] = Utils::censor($cur_post['subject']);
            $cur_post['message'] = Utils::censor($cur_post['message']);
        }

        // Start with a clean slate
        $errors = [];

        // Let's see if everything went right
        $errors = \FeatherBB\Model\Post::checkErrorsEdit($can_edit_subject, $errors, $is_admmod);

        // Setup some variables before post
        $post = \FeatherBB\Model\Post::setupEditVariables($cur_post, $is_admmod, $can_edit_subject, $errors);

        // Did everything go according to plan?
        if (empty($errors)) {
            // Edit the post
            $this->model->update($args, $can_edit_subject, $post, $cur_post, $is_admmod);

            return json_encode($this->model->display($args['id']), JSON_PRETTY_PRINT);
        }

        return json_encode($errors, JSON_PRETTY_PRINT);
    }
}
