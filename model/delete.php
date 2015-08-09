<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace model;

use DB;

class delete
{
    public function __construct()
    {
        $this->feather = \Slim\Slim::getInstance();
        $this->start = $this->feather->start;
        $this->config = $this->feather->config;
        $this->user = $this->feather->user;
        $this->request = $this->feather->request;
    }
 
    public function get_info_delete($id)
    {
        global $lang_common;
        
        $select_get_info_delete = array('fid' => 'f.id', 'f.forum_name', 'f.moderators', 'f.redirect_url', 'fp.post_replies',  'fp.post_topics', 'tid' => 't.id', 't.subject', 't.first_post_id', 't.closed', 'p.poster', 'p.posted', 'p.poster_id', 'p.message', 'p.hide_smilies');
        $where_get_info_delete = array(
            array('fp.read_forum' => 'IS NULL'),
            array('fp.read_forum' => '1')
        );

        $cur_post = DB::for_table('posts')
            ->table_alias('p')
            ->select_many($select_get_info_delete)
            ->inner_join('topics', array('t.id', '=', 'p.topic_id'), 't')
            ->inner_join('forums', array('f.id', '=', 't.forum_id'), 'f')
            ->left_outer_join('forum_perms', array('fp.forum_id', '=', 'f.id'), 'fp')
            ->left_outer_join('forum_perms', array('fp.group_id', '=', $this->user->g_id), null, true)
            ->where_any_is($where_get_info_delete)
            ->where('p.id', $id)
            ->find_one();
        
        if (!$cur_post) {
            message($lang_common['Bad request'], '404');
        }

        return $cur_post;
    }

    public function handle_deletion($is_topic_post, $id, $tid, $fid)
    {
        global $lang_delete;

        require FEATHER_ROOT.'include/search_idx.php';

        if ($is_topic_post) {
            // Delete the topic and all of its posts
            delete_topic($tid);
            update_forum($fid);

            redirect(get_link('forum/'.$fid.'/'), $lang_delete['Topic del redirect']);
        } else {
            // Delete just this one post
            delete_post($id, $tid);
            update_forum($fid);

            // Redirect towards the previous post
            $post = DB::for_table('posts')
                ->select('id')
                ->where('topic_id', $tid)
                ->where_lt('id', $id)
                ->order_by_desc('id')
                ->find_one();

            redirect(get_link('post/'.$post['id'].'/#p'.$post['id']), $lang_delete['Post del redirect']);
        }
    }
}
