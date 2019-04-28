<?php

/**
 * Copyright (C) 2015-2019 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace FeatherBB\Model\Api;

use FeatherBB\Core\Database as DB;
use FeatherBB\Core\Error;
use FeatherBB\Core\Interfaces\Hooks;
use FeatherBB\Core\Interfaces\User;
use FeatherBB\Core\Interfaces\User;
use FeatherBB\Core\Utils;

class Post extends Api
{
    public function display($id)
    {
        $post = new \FeatherBB\Model\Post();

        try {
            $data = $post->getInfoEdit($id);
        } catch (Error $e) {
            return $this->errorMessage;
        }

        $data = $data->asArray();

        $data['moderators'] = unserialize($data['moderators']);

        return $data;
    }

    public function getDeletePermissions($curPost, $args)
    {
        $modsArray = ($curPost['moderators'] != '') ? unserialize($curPost['moderators']) : [];
        $isAdmmod = (User::isAdmin($this->user) || (User::isAdminMod($this->user) && array_key_exists($this->user->username, $modsArray))) ? true : false;

        $isTopicPost = ($args['id'] == $curPost['first_post_id']) ? true : false;

        // Do we have permission to edit this post?
        if ((!User::can('post.delete', $this->user) ||
                (!User::can('post.delete', $this->user) && $isTopicPost) ||
                $curPost['poster_id'] != $this->user->id ||
                $curPost['closed'] == 1) &&
            !$isAdmmod) {
            return json_encode($this->errorMessage, JSON_PRETTY_PRINT);
        }

        if ($isAdmmod && !User::isAdmin($this->user) && in_array($curPost['poster_id'], Utils::getAdminIds())) {
            return json_encode($this->errorMessage, JSON_PRETTY_PRINT);
        }

        return $isTopicPost;
    }
    
    public function getEditPermissions($curPost)
    {
        // Sort out who the moderators are and if we are currently a moderator (or an admin)
        $modsArray = ($curPost['moderators'] != '') ? unserialize($curPost['moderators']) : [];
        $isAdmmod = (User::isAdmin($this->user) || (User::isAdminMod($this->user) && array_key_exists($this->user->username, $modsArray))) ? true : false;

        // Do we have permission to edit this post?
        if ((!User::can('post.edit', $this->user) || $curPost['poster_id'] != $this->user->id || $curPost['closed'] == 1) && !$isAdmmod) {
            return json_encode($this->errorMessage, JSON_PRETTY_PRINT);
        }

        if ($isAdmmod && !User::isAdmin($this->user) && in_array($curPost['poster_id'], Utils::getAdminIds())) {
            return json_encode($this->errorMessage, JSON_PRETTY_PRINT);
        }

        return $isAdmmod;
    }

    public function getInfoEdit($id)
    {
        $curPost['select'] = ['fid' => 'f.id', 'f.forum_name', 'f.moderators', 'f.redirect_url', 'fp.post_topics', 'tid' => 't.id', 't.subject', 't.posted', 't.first_post_id', 't.sticky', 't.closed', 'p.poster', 'p.poster_id', 'p.message', 'p.hide_smilies'];
        $curPost['where'] = [
            ['fp.read_forum' => 'IS NULL'],
            ['fp.read_forum' => 1]
        ];

        $curPost = DB::table('posts')
            ->tableAlias('p')
            ->selectMany($curPost['select'])
            ->innerJoin('topics', ['t.id', '=', 'p.topic_id'], 't')
            ->innerJoin('forums', ['f.id', '=', 't.forum_id'], 'f')
            ->leftOuterJoin('forum_perms', 'fp.forum_id=f.id AND fp.group_id='.$this->user->g_id, 'fp')
            ->whereAnyIs($curPost['where'])
            ->where('p.id', $id);

        $curPost = $curPost->findOne();

        if (!$curPost) {
            return json_encode($this->errorMessage, JSON_PRETTY_PRINT);
        }

        return $curPost;
    }

    public function getInfoDelete($id)
    {
        $id = Hooks::fire('model.post.get_info_delete_start', $id);

        $query['select'] = ['fid' => 'f.id', 'f.forum_name', 'f.moderators', 'f.redirect_url', 'fp.post_replies',  'fp.post_topics', 'tid' => 't.id', 't.subject', 't.first_post_id', 't.closed', 'p.poster', 'p.posted', 'p.poster_id', 'p.message', 'p.hide_smilies'];
        $query['where'] = [
            ['fp.read_forum' => 'IS NULL'],
            ['fp.read_forum' => 1]
        ];

        $query = DB::table('posts')
            ->tableAlias('p')
            ->selectMany($query['select'])
            ->innerJoin('topics', ['t.id', '=', 'p.topic_id'], 't')
            ->innerJoin('forums', ['f.id', '=', 't.forum_id'], 'f')
            ->leftOuterJoin('forum_perms', 'fp.forum_id=f.id AND fp.group_id='.$this->user->g_id, 'fp')
            ->whereAnyIs($query['where'])
            ->where('p.id', $id);

        $query = Hooks::fireDB('model.post.get_info_delete_query', $query);

        $query = $query->findOne();

        if (!$query) {
            return json_encode($this->errorMessage, JSON_PRETTY_PRINT);
        }

        return $query;
    }

    public function update($args, $canEditSubject, $post, $curPost, $isAdmmod)
    {
        \FeatherBB\Model\Post::editPost($args['id'], $canEditSubject, $post, $curPost, $isAdmmod, $this->user->username);
    }
}
