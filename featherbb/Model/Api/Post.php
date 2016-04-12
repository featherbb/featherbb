<?php

/**
 * Copyright (C) 2015-2016 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace FeatherBB\Model\Api;

use FeatherBB\Core\Error;
use FeatherBB\Core\Database as DB;
use FeatherBB\Core\Utils;

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

    public function getDeletePermissions($cur_post, $args)
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
            return json_encode($this->errorMessage, JSON_PRETTY_PRINT);
        }

        if ($is_admmod && $this->user->g_id != ForumEnv::get('FEATHER_ADMIN') && in_array($cur_post['poster_id'], Utils::get_admin_ids())) {
            return json_encode($this->errorMessage, JSON_PRETTY_PRINT);
        }

        return $is_topic_post;
    }
    
    public function getEditPermissions($cur_post)
    {
        // Sort out who the moderators are and if we are currently a moderator (or an admin)
        $mods_array = ($cur_post['moderators'] != '') ? unserialize($cur_post['moderators']) : array();
        
        $is_admmod = ($this->user->g_id == ForumEnv::get('FEATHER_ADMIN') || ($this->user->g_moderator == '1' && array_key_exists($this->user->username, $mods_array))) ? true : false;

        // Do we have permission to edit this post?
        if (($this->user->g_edit_posts == '0' || $cur_post['poster_id'] != $this->user->id || $cur_post['closed'] == '1') && !$is_admmod) {
            return json_encode($this->errorMessage, JSON_PRETTY_PRINT);
        }

        if ($is_admmod && $this->user->g_id != ForumEnv::get('FEATHER_ADMIN') && in_array($cur_post['poster_id'], Utils::get_admin_ids())) {
            return json_encode($this->errorMessage, JSON_PRETTY_PRINT);
        }

        return $is_admmod;
    }

    public function get_info_edit($id)
    {
        $cur_post['select'] = array('fid' => 'f.id', 'f.forum_name', 'f.moderators', 'f.redirect_url', 'fp.post_topics', 'tid' => 't.id', 't.subject', 't.posted', 't.first_post_id', 't.sticky', 't.closed', 'p.poster', 'p.poster_id', 'p.message', 'p.hide_smilies');
        $cur_post['where'] = array(
            array('fp.read_forum' => 'IS NULL'),
            array('fp.read_forum' => '1')
        );

        $cur_post = DB::for_table('posts')
            ->table_alias('p')
            ->select_many($cur_post['select'])
            ->inner_join('topics', array('t.id', '=', 'p.topic_id'), 't')
            ->inner_join('forums', array('f.id', '=', 't.forum_id'), 'f')
            ->left_outer_join('forum_perms', array('fp.forum_id', '=', 'f.id'), 'fp')
            ->left_outer_join('forum_perms', array('fp.group_id', '=', $this->user->g_id), null, true)
            ->where_any_is($cur_post['where'])
            ->where('p.id', $id);

        $cur_post = $cur_post->find_one();

        if (!$cur_post) {
            return json_encode($this->errorMessage, JSON_PRETTY_PRINT);
        }

        return $cur_post;
    }

    public function get_info_delete($id)
    {
        $id = Container::get('hooks')->fire('model.post.get_info_delete_start', $id);

        $query['select'] = array('fid' => 'f.id', 'f.forum_name', 'f.moderators', 'f.redirect_url', 'fp.post_replies',  'fp.post_topics', 'tid' => 't.id', 't.subject', 't.first_post_id', 't.closed', 'p.poster', 'p.posted', 'p.poster_id', 'p.message', 'p.hide_smilies');
        $query['where'] = array(
            array('fp.read_forum' => 'IS NULL'),
            array('fp.read_forum' => '1')
        );

        $query = DB::for_table('posts')
            ->table_alias('p')
            ->select_many($query['select'])
            ->inner_join('topics', array('t.id', '=', 'p.topic_id'), 't')
            ->inner_join('forums', array('f.id', '=', 't.forum_id'), 'f')
            ->left_outer_join('forum_perms', array('fp.forum_id', '=', 'f.id'), 'fp')
            ->left_outer_join('forum_perms', array('fp.group_id', '=', $this->user->g_id), null, true)
            ->where_any_is($query['where'])
            ->where('p.id', $id);

        $query = Container::get('hooks')->fireDB('model.post.get_info_delete_query', $query);

        $query = $query->find_one();

        if (!$query) {
            return json_encode($this->errorMessage, JSON_PRETTY_PRINT);
        }

        return $query;
    }

    public function update($args, $can_edit_subject, $post, $cur_post, $is_admmod)
    {
        \FeatherBB\Model\Post::edit_post($args['id'], $can_edit_subject, $post, $cur_post, $is_admmod, $this->user->username);
    }
}