<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace FeatherBB\Model;

use FeatherBB\Utils;
use FeatherBB\Url;
use DB;

class Delete
{
    public function __construct()
    {
        $this->feather = \Slim\Slim::getInstance();
        $this->start = $this->feather->start;
        $this->config = $this->feather->config;
        $this->user = $this->feather->user;
        $this->request = $this->feather->request;
        $this->hook = $this->feather->hooks;
    }

    public function get_info_delete($id)
    {
        $id = $this->hook->fire('get_info_delete_start', $id);

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

        $query = $this->hook->fireDB('get_info_delete_query', $query);

        $query = $query->find_one();

        if (!$query) {
            throw new \FeatherBB\Error(__('Bad request'), 404);
        }

        return $query;
    }

    public function handle_deletion($is_topic_post, $id, $tid, $fid)
    {
        $this->hook->fire('handle_deletion_start', $is_topic_post, $id, $tid, $fid);

        if ($is_topic_post) {
            $this->hook->fire('handle_deletion_topic_post', $tid, $fid);

            // Delete the topic and all of its posts
            delete_topic($tid);
            update_forum($fid);

            redirect(Url::get('forum/'.$fid.'/'), __('Topic del redirect'));
        } else {
            $this->hook->fire('handle_deletion', $tid, $fid, $id);

            // Delete just this one post
            delete_post($id, $tid);
            update_forum($fid);

            // Redirect towards the previous post
            $post = DB::for_table('posts')
                ->select('id')
                ->where('topic_id', $tid)
                ->where_lt('id', $id)
                ->order_by_desc('id');

            $post = $this->hook->fireDB('handle_deletion_query', $post);

            $post = $post->find_one();

            redirect(Url::get('post/'.$post['id'].'/#p'.$post['id']), __('Post del redirect'));
        }
    }
}
