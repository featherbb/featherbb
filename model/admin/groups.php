<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace model\admin;

use DB;

class groups
{
    public function __construct()
    {
        $this->feather = \Slim\Slim::getInstance();
        $this->start = $this->feather->start;
        $this->config = $this->feather->config;
        $this->user = $this->feather->user;
        $this->request = $this->feather->request;
    }
 
    public function fetch_groups()
    {
        $result = DB::for_table('groups')->order_by('g_id')->find_many();
        $groups = array();
        foreach ($result as $cur_group) {
            $groups[$cur_group['g_id']] = $cur_group;
        }

        return $groups;
    }

    public function info_add_group($groups, $id)
    {
        global $lang_common;

        $group = array();

        if ($this->request->post('add_group')) {
            $group['base_group'] = intval($this->request->post('base_group'));
            $group['info'] = $groups[$group['base_group']];

            $group['mode'] = 'add';
        } else {
            // We are editing a group
            if (!isset($groups[$id])) {
                message($lang_common['Bad request'], '404');
            }

            $group['info'] = $groups[$id];

            $group['mode'] = 'edit';
        }

        return $group;
    }

    public function get_group_list($groups, $group)
    {
        $output = '';

        foreach ($groups as $cur_group) {
            if (($cur_group['g_id'] != $group['info']['g_id'] || $group['mode'] == 'add') && $cur_group['g_id'] != FEATHER_ADMIN && $cur_group['g_id'] != FEATHER_GUEST) {
                if ($cur_group['g_id'] == $group['info']['g_promote_next_group']) {
                    $output .= "\t\t\t\t\t\t\t\t\t\t\t".'<option value="'.$cur_group['g_id'].'" selected="selected">'.feather_escape($cur_group['g_title']).'</option>'."\n";
                } else {
                    $output .= "\t\t\t\t\t\t\t\t\t\t\t".'<option value="'.$cur_group['g_id'].'">'.feather_escape($cur_group['g_title']).'</option>'."\n";
                }
            }
        }

        return $output;
    }

    public function get_group_list_delete($group_id)
    {
        $select_get_group_list_delete = array('g_id', 'g_title');
        $result = DB::for_table('groups')->select_many($select_get_group_list_delete)
                        ->where_not_equal('g_id', FEATHER_GUEST)
                        ->where_not_equal('g_id', $group_id)
                        ->order_by('g_title')
                        ->find_many();

        foreach ($result as $cur_group) {
            if ($cur_group['g_id'] == FEATHER_MEMBER) {
                // Pre-select the pre-defined Members group
                echo "\t\t\t\t\t\t\t\t\t\t".'<option value="'.$cur_group['g_id'].'" selected="selected">'.feather_escape($cur_group['g_title']).'</option>'."\n";
            } else {
                echo "\t\t\t\t\t\t\t\t\t\t".'<option value="'.$cur_group['g_id'].'">'.feather_escape($cur_group['g_title']).'</option>'."\n";
            }
        }
    }

    public function add_edit_group($groups)
    {
        global $lang_admin_groups;

        if ($this->request->post('group_id')) {
            $group_id = $this->request->post('group_id');
        } else {
            $group_id = 0;
        }

        // Is this the admin group? (special rules apply)
        $is_admin_group = ($this->request->post('group_id') && $this->request->post('group_id') == FEATHER_ADMIN) ? true : false;

        $title = feather_trim($this->request->post('req_title'));
        $user_title = feather_trim($this->request->post('user_title'));

        $promote_min_posts = $this->request->post('promote_min_posts') ? intval($this->request->post('promote_min_posts')) : '0';
        if ($this->request->post('promote_next_group') &&
                isset($groups[$this->request->post('promote_next_group')]) &&
                !in_array($this->request->post('promote_next_group'), array(FEATHER_ADMIN, FEATHER_GUEST)) &&
                ($this->request->post('group_id') || $this->request->post('promote_next_group') != $this->request->post('group_id'))) {
            $promote_next_group = $this->request->post('promote_next_group');
        } else {
            $promote_next_group = '0';
        }

        $moderator = $this->request->post('moderator') && $this->request->post('moderator') == '1' ? '1' : '0';
        $mod_edit_users = $moderator == '1' && $this->request->post('mod_edit_users') == '1' ? '1' : '0';
        $mod_rename_users = $moderator == '1' && $this->request->post('mod_rename_users') == '1' ? '1' : '0';
        $mod_change_passwords = $moderator == '1' && $this->request->post('mod_change_passwords') == '1' ? '1' : '0';
        $mod_ban_users = $moderator == '1' && $this->request->post('mod_ban_users') == '1' ? '1' : '0';
        $mod_promote_users = $moderator == '1' && $this->request->post('mod_promote_users') == '1' ? '1' : '0';
        $read_board = ($this->request->post('read_board') == 0) ? $this->request->post('read_board') : '1';
        $view_users = ($this->request->post('view_users') && $this->request->post('view_users') == '1') || $is_admin_group ? '1' : '0';
        $post_replies = ($this->request->post('post_replies') == 0) ? $this->request->post('post_replies') : '1';
        $post_topics = ($this->request->post('post_topics') == 0) ? $this->request->post('post_topics') : '1';
        $edit_posts = ($this->request->post('edit_posts') == 0) ? $this->request->post('edit_posts') : ($is_admin_group) ? '1' : '0';
        $delete_posts = ($this->request->post('delete_posts') == 0) ? $this->request->post('delete_posts') : ($is_admin_group) ? '1' : '0';
        $delete_topics = ($this->request->post('delete_topics') == 0) ? $this->request->post('delete_topics') : ($is_admin_group) ? '1' : '0';
        $post_links = ($this->request->post('post_links') == 0) ? $this->request->post('post_links') : '1';
        $set_title = ($this->request->post('set_title') == 0) ? $this->request->post('set_title') : ($is_admin_group) ? '1' : '0';
        $search = ($this->request->post('search') == 0) ? $this->request->post('search') : '1';
        $search_users = ($this->request->post('search_users') == 0) ? $this->request->post('search_users') : '1';
        $send_email = ($this->request->post('send_email') && $this->request->post('send_email') == '1') || $is_admin_group ? '1' : '0';
        $post_flood = ($this->request->post('post_flood') && $this->request->post('post_flood') >= 0) ? $this->request->post('post_flood') : '0';
        $search_flood = ($this->request->post('search_flood') && $this->request->post('search_flood') >= 0) ? $this->request->post('search_flood') : '0';
        $email_flood = ($this->request->post('email_flood') && $this->request->post('email_flood') >= 0) ? $this->request->post('email_flood') : '0';
        $report_flood = ($this->request->post('report_flood') >= 0) ? $this->request->post('report_flood') : '0';

        if ($title == '') {
            message($lang_admin_groups['Must enter title message']);
        }

        $user_title = ($user_title != '') ? $user_title : 'NULL';

        $insert_update_group = array(
            'g_title'               =>  $title,
            'g_user_title'          =>  $user_title,
            'g_promote_min_posts'   =>  $promote_min_posts,
            'g_promote_next_group'  =>  $promote_next_group,
            'g_moderator'           =>  $moderator,
            'g_mod_edit_users'      =>  $mod_edit_users,
            'g_mod_rename_users'    =>  $mod_rename_users,
            'g_mod_change_passwords'=>  $mod_change_passwords,
            'g_mod_ban_users'       =>  $mod_ban_users,
            'g_mod_promote_users'   =>  $mod_promote_users,
            'g_read_board'          =>  $read_board,
            'g_view_users'          =>  $view_users,
            'g_post_replies'        =>  $post_replies,
            'g_post_topics'         =>  $post_topics,
            'g_edit_posts'          =>  $edit_posts,
            'g_delete_posts'        =>  $delete_posts,
            'g_delete_topics'       =>  $delete_topics,
            'g_post_links'          =>  $post_links,
            'g_set_title'           =>  $set_title,
            'g_search'              =>  $search,
            'g_search_users'        =>  $search_users,
            'g_send_email'          =>  $send_email,
            'g_post_flood'          =>  $post_flood,
            'g_search_flood'        =>  $search_flood,
            'g_email_flood'         =>  $email_flood,
            'g_report_flood'        =>  $report_flood,
        );

        if ($this->request->post('mode') == 'add') {
            $title_exists = DB::for_table('groups')->where('g_title', $title)->find_one();
            if ($title_exists) {
                message(sprintf($lang_admin_groups['Title already exists message'], feather_escape($title)));
            }

            DB::for_table('groups')
                ->create()
                ->set($insert_update_group)
                ->save();
            $new_group_id = DB::get_db()->lastInsertId($this->feather->prefix.'groups');

            // Now lets copy the forum specific permissions from the group which this group is based on
            $select_forum_perms = array('forum_id', 'read_forum', 'post_replies', 'post_topics');
            $result = DB::for_table('forum_perms')->select_many($select_forum_perms)
                            ->where('group_id', $this->request->post('base_group'))
                            ->find_many();

            foreach ($result as $cur_forum_perm) {
                $insert_perms = array(
                    'group_id'       =>  $new_group_id,
                    'forum_id'       =>  $cur_forum_perm['forum_id'],
                    'read_forum'     =>  $cur_forum_perm['read_forum'],
                    'post_replies'   =>  $cur_forum_perm['post_replies'],
                    'post_topics'    =>  $cur_forum_perm['post_topics'],
                );

                DB::for_table('forum_perms')
                        ->create()
                        ->set($insert_perms)
                        ->save();
            }
        } else {
            $title_exists = DB::for_table('groups')->where('g_title', $title)->where_not_equal('g_id', $this->request->post('group_id'))->find_one();
            if ($title_exists) {
                message(sprintf($lang_admin_groups['Title already exists message'], feather_escape($title)));
            }
            DB::for_table('groups')
                    ->find_one($this->request->post('group_id'))
                    ->set($insert_update_group)
                    ->save();

            // Promote all users who would be promoted to this group on their next post
            if ($promote_next_group) {
                DB::for_table('users')->where('group_id', $this->request->post('group_id'))
                        ->where_gte('num_posts', $promote_min_posts)
                        ->update_many('group_id', $promote_next_group);
            }
        }

        // Regenerate the quick jump cache
        if (!defined('FORUM_CACHE_FUNCTIONS_LOADED')) {
            require FEATHER_ROOT.'include/cache.php';
        }

        $group_id = $this->request->post('mode') == 'add' ? $new_group_id : $this->request->post('group_id');

        generate_quickjump_cache($group_id);

        if ($this->request->post('mode') == 'edit') {
            redirect(get_link('admin/groups/'), $lang_admin_groups['Group edited redirect']);
        } else {
            redirect(get_link('admin/groups/'), $lang_admin_groups['Group added redirect']);
        }
    }

    public function set_default_group($groups)
    {
        global $lang_admin_groups, $lang_common;

        $group_id = intval($this->request->post('default_group'));

        // Make sure it's not the admin or guest groups
        if ($group_id == FEATHER_ADMIN || $group_id == FEATHER_GUEST) {
            message($lang_common['Bad request'], '404');
        }

        // Make sure it's not a moderator group
        if ($groups[$group_id]['g_moderator'] != 0) {
            message($lang_common['Bad request'], '404');
        }

        DB::for_table('config')->where('conf_name', 'o_default_user_group')
                                                   ->update_many('conf_value', $group_id);

        // Regenerate the config cache
        if (!defined('FORUM_CACHE_FUNCTIONS_LOADED')) {
            require FEATHER_ROOT.'include/cache.php';
        }

        generate_config_cache();

        redirect(get_link('admin/groups/'), $lang_admin_groups['Default group redirect']);
    }

    public function check_members($group_id)
    {
        $is_member = DB::for_table('groups')->table_alias('g')
            ->select('g.g_title')
            ->select_expr('COUNT(u.id)', 'members')
            ->inner_join('users', array('g.g_id', '=', 'u.group_id'), 'u')
            ->where('g.g_id', $group_id)
            ->group_by('g.g_id')
            ->group_by('g_title')
            ->find_one();

        return (bool) $is_member;
    }

    public function delete_group($group_id)
    {
        global $lang_admin_groups;

        if ($this->request->post('del_group')) {
            $move_to_group = intval($this->request->post('move_to_group'));
            DB::for_table('users')->where('group_id', $group_id)
                                                      ->update_many('group_id', $move_to_group);
        }

        // Delete the group and any forum specific permissions
        DB::for_table('groups')
            ->where('g_id', $group_id)
            ->delete_many();
        DB::for_table('forum_perms')
            ->where('group_id', $group_id)
            ->delete_many();

        // Don't let users be promoted to this group
        DB::for_table('groups')->where('g_promote_next_group', $group_id)
                                                   ->update_many('g_promote_next_group', 0);

        redirect(get_link('admin/groups/'), $lang_admin_groups['Group removed redirect']);
    }

    public function get_group_title($group_id)
    {
        $group_title = DB::for_table('groups')->where('g_id', $group_id)
                            ->find_one_col('g_title');

        return $group_title;
    }

    public function get_title_members($group_id)
    {
        $group = DB::for_table('groups')->table_alias('g')
                    ->select('g.g_title')
                    ->select_expr('COUNT(u.id)', 'members')
                    ->inner_join('users', array('g.g_id', '=', 'u.group_id'), 'u')
                    ->where('g.g_id', $group_id)
                    ->group_by('g.g_id')
                    ->group_by('g_title')
                    ->find_one();

        $group_info['title'] = $group['g_title'];
        $group_info['members'] = $group['members'];

        return $group_info;
    }
}
