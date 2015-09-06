<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace FeatherBB\Model\Admin;

use FeatherBB\Core\Utils;
use FeatherBB\Core\Url;
use DB;

class Forums
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

    //
    // Forum
    //

    public function add_forum($cat_id, $forum_name)
    {
        $set_add_forum = array('forum_name' => $forum_name,
                                'cat_id' => $cat_id);

        $set_add_forum = $this->hook->fire('add_forum', $set_add_forum);

        $forum = DB::for_table('forums')
                    ->create()
                    ->set($set_add_forum);
        $forum->save();

        return $forum->id();
    }

    public function update_forum($forum_id, array $forum_data)
    {
        $update_forum = DB::for_table('forums')
                    ->find_one($forum_id)
                    ->set($forum_data);
        $update_forum = $this->hook->fireDB('update_forum_query', $update_forum);
        $update_forum = $update_forum->save();

        return $update_forum;
    }

    public function delete_forum($forum_id)
    {
        // Load the maintenance.php model file for prune public function
        require $this->feather->forum_env['FEATHER_ROOT'] . 'model/admin/maintenance.php';

        $forum_id = $this->hook->fire('delete_forum_start', $forum_id);

        // Prune all posts and topics
        $this->maintenance = new \FeatherBB\Model\Admin\maintenance();
        $this->maintenance->prune($forum_id, 1, -1);

        // Delete the forum
        $delete_forum = DB::for_table('forums')->find_one($forum_id);
        $delete_forum = $this->hook->fireDB('delete_forum_query', $delete_forum);
        $delete_forum->delete();

        // Delete forum specific group permissions and subscriptions
        $delete_forum_perms = DB::for_table('forum_perms')->where('forum_id', $forum_id);
        $delete_forum_perms = $this->hook->fireDB('delete_forum_perms_query', $delete_forum_perms);
        $delete_forum_perms->delete_many();

        $delete_forum_subs = DB::for_table('forum_subscriptions')->where('forum_id', $forum_id);
        $delete_forum_subs = $this->hook->fireDB('delete_forum_subs_query', $delete_forum_subs);
        $delete_forum_subs->delete_many();

        // Delete orphaned redirect topics
        $orphans = DB::for_table('topics')
                    ->table_alias('t1')
                    ->left_outer_join('topics', array('t1.moved_to', '=', 't2.id'), 't2')
                    ->where_null('t2.id')
                    ->where_not_null('t1.moved_to');
        $orphans = $this->hook->fireDB('delete_orphan_redirect_topics_query', $orphans);
        $orphans = $orphans->find_many();

        if (count($orphans) > 0) {
            $orphans->delete_many();
        }

        return true; // TODO, better error handling
    }

    public function get_forum_info($forum_id)
    {
        $result = DB::for_table('forums')
                    ->where('id', $forum_id);
        $result = $this->hook->fireDB('get_forum_infos', $result);
        $result = $result->find_array();

        return $result[0];
    }

    public function get_forums()
    {
        $forum_data = array();
        $forum_data = $this->hook->fire('get_forums_start', $forum_data);

        $select_get_forums = array('cid' => 'c.id', 'c.cat_name', 'cat_position' => 'c.disp_position', 'fid' => 'f.id', 'f.forum_name', 'forum_position' => 'f.disp_position');

        $result = DB::for_table('categories')
                    ->table_alias('c')
                    ->select_many($select_get_forums)
                    ->inner_join('forums', array('c.id', '=', 'f.cat_id'), 'f')
                    ->order_by_asc('f.disp_position')
                    ->order_by_asc('c.disp_position');
        $result = $this->hook->fireDB('get_forums_query', $result);
        $result = $result->find_array();

        foreach ($result as $forum) {
            if (!isset($forum_data[$forum['cid']])) {
                $forum_data[$forum['cid']] = array('cat_name' => $forum['cat_name'],
                                                   'cat_position' => $forum['cat_position'],
                                                   'cat_forums' => array());
            }
            $forum_data[$forum['cid']]['cat_forums'][] = array('forum_id' => $forum['fid'],
                                                               'forum_name' => $forum['forum_name'],
                                                               'position' => $forum['forum_position']);
        }

        $forum_data = $this->hook->fire('get_forums', $forum_data);
        return $forum_data;
    }

    public function update_positions($forum_id, $position)
    {
        $this->hook->fire('update_positions_start', $forum_id, $position);

        return DB::for_table('forums')
                ->find_one($forum_id)
                ->set('disp_position', $position)
                ->save();
    }

    //
    // Permissions
    //

    public function get_permissions($forum_id)
    {
        $perm_data = array();
        $forum_id = $this->hook->fire('get_permissions_start', $forum_id);

        $select_permissions = array('g.g_id', 'g.g_title', 'g.g_read_board', 'g.g_post_replies', 'g.g_post_topics', 'fp.read_forum', 'fp.post_replies', 'fp.post_topics');

        $permissions = DB::for_table('groups')
                        ->table_alias('g')
                        ->select_many($select_permissions)
                        ->left_outer_join('forum_perms', 'g.g_id=fp.group_id AND fp.forum_id='.$forum_id, 'fp') // Workaround
                        ->where_not_equal('g.g_id', FEATHER_ADMIN)
                        ->order_by_asc('g.g_id');
        $permissions = $this->hook->fireDB('get_permissions_query', $permissions);
        $permissions = $permissions->find_many();

        foreach($permissions as $cur_perm) {
            $cur_perm['read_forum'] = ($cur_perm['read_forum'] != '0') ? true : false;
            $cur_perm['post_replies'] = (($cur_perm['g_post_replies'] == '0' && $cur_perm['post_replies'] == '1') || ($cur_perm['g_post_replies'] == '1' && $cur_perm['post_replies'] != '0')) ? true : false;
            $cur_perm['post_topics'] = (($cur_perm['g_post_topics'] == '0' && $cur_perm['post_topics'] == '1') || ($cur_perm['g_post_topics'] == '1' && $cur_perm['post_topics'] != '0')) ? true : false;

            // Determine if the current settings differ from the default or not
            $cur_perm['read_forum_def'] = ($cur_perm['read_forum'] == '0') ? false : true;
            $cur_perm['post_replies_def'] = (($cur_perm['post_replies'] && $cur_perm['g_post_replies'] == '0') || (!$cur_perm['post_replies'] && ($cur_perm['g_post_replies'] == '' || $cur_perm['g_post_replies'] == '1'))) ? false : true;
            $cur_perm['post_topics_def'] = (($cur_perm['post_topics'] && $cur_perm['g_post_topics'] == '0') || ($cur_perm['post_topics'] && ($cur_perm['g_post_topics'] == '' || $cur_perm['g_post_topics'] == '1'))) ? false : true;

            $perm_data[] = $cur_perm;
        }

        $perm_data = $this->hook->fire('get_permissions', $perm_data);
        return $perm_data;
    }

    public function get_default_group_permissions($fetch_admin = true)
    {
        $select_get_default_group_permissions = array('g_id', 'g_read_board', 'g_post_replies', 'g_post_topics');

        $result = DB::for_table('groups')
                    ->select_many($select_get_default_group_permissions);

        if (!$fetch_admin) {
            $result->where_not_equal('g_id', FEATHER_ADMIN);
        }

        $result = $result->order_by_asc('g_id');
        $result = $this->hook->fireDB('get_default_group_permissions_query', $result);
        $result = $result->find_array();

        return $result;
    }

    public function update_permissions(array $permissions_data)
    {
        $permissions_data = $this->hook->fire('update_permissions_start', $permissions_data);

        $permissions = DB::for_table('forum_perms')
                            ->where('forum_id', $permissions_data['forum_id'])
                            ->where('group_id', $permissions_data['group_id'])
                            ->delete_many();

        if ($permissions) {
            return DB::for_table('forum_perms')
                    ->create()
                    ->set($permissions_data)
                    ->save();
        }

    }

    public function delete_permissions($forum_id, $group_id = null)
    {
        $result = DB::for_table('forum_perms')
                    ->where('forum_id', $forum_id);

        if ($group_id) {
            $result->where('group_id', $group_id);
        }

        $result = $this->hook->fireDB('delete_permissions_query', $result);

        return $result->delete_many();
    }
}
