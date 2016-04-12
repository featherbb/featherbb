<?php

/**
 * Copyright (C) 2015-2016 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace FeatherBB\Model\Api;

use FeatherBB\Core\Error;

class Post extends Api
{
    public function display($id)
    {
        $post = new \FeatherBB\Model\Post();

        try {
            $data = $post->get_info_edit($id);
        } catch (Error $e) {
            return $this->errorMessage;
        }

        $data = $data->as_array();

        $data['moderators'] = unserialize($data['moderators']);

        return $data;
    }

    public function getPermissions($cur_post, $args)
    {
        $mods_array = ($cur_post['moderators'] != '') ? unserialize($cur_post['moderators']) : array();
        $is_admmod = ($this->user->g_id == ForumEnv::get('FEATHER_ADMIN') || ($this->user->g_moderator == '1' && array_key_exists($this->user->username, $mods_array))) ? true : false;

        $is_topic_post = ($args['id'] == $cur_post['first_post_id']) ? true : false;

        // Do we have permission to edit this post?
        if (($this->user->g_delete_posts == '0' ||
                ($this->user->g_delete_topics == '0' && $is_topic_post) ||
                $cur_post['poster_id'] != $this->user->id ||
                $cur_post['closed'] == '1') &&
            !$is_admmod) {
            throw new Error(__('No permission'), 403);
        }

        if ($is_admmod && $this->user->g_id != ForumEnv::get('FEATHER_ADMIN') && in_array($cur_post['poster_id'], Utils::get_admin_ids())) {
            throw new Error(__('No permission'), 403);
        }

        return $is_topic_post;
    }
}