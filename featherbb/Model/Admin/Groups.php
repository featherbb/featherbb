<?php

/**
 * Copyright (C) 2015-2016 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace FeatherBB\Model\Admin;

use FeatherBB\Core\Database as DB;
use FeatherBB\Core\Error;
use FeatherBB\Core\Url;
use FeatherBB\Core\Utils;
use FeatherBB\Model\Cache;

class Groups
{
    public function __construct()
    {
        $this->feather = \Slim\Slim::getInstance();
        $this->start = $this->feather->start;
        $this->config = $this->feather->config;
        $this->user = Container::get('user');
        $this->request = $this->feather->request;
        $this->hook = $this->feather->hooks;
    }

    public function fetch_groups()
    {
        $result = DB::for_table('groups')->order_by('g_id')->find_many();
        $this->hook->fireDB('model.admin.groups.fetch_groups_query', $result);
        $groups = array();
        foreach ($result as $cur_group) {
            $groups[$cur_group['g_id']] = $cur_group;
        }

        $groups = $this->hook->fire('model.admin.groups.fetch_groups', $groups);

        return $groups;
    }

    public function info_add_group($groups, $id)
    {
        $group = array();

        if ($this->request->post('add_group')) {
            $group['base_group'] = intval($this->request->post('base_group'));
            $group['base_group'] = $this->hook->fire('model.admin.groups.add_user_group', $group['base_group']);
            $group['info'] = $groups[$group['base_group']];

            $group['mode'] = 'add';
        } else {
            // We are editing a group
            if (!isset($groups[$id])) {
                throw new Error(__('Bad request'), 404);
            }

            $groups[$id] = $this->hook->fire('model.admin.groups.update_user_group', $groups[$id]);

            $group['info'] = $groups[$id];

            $group['mode'] = 'edit';
        }

        $group = $this->hook->fire('model.admin.groups.info_add_group', $group);
        return $group;
    }

    public function get_group_list($groups, $group)
    {
        $output = '';

        foreach ($groups as $cur_group) {
            if (($cur_group['g_id'] != $group['info']['g_id'] || $group['mode'] == 'add') && $cur_group['g_id'] != Container::get('forum_env')['FEATHER_ADMIN'] && $cur_group['g_id'] != Container::get('forum_env')['FEATHER_GUEST']) {
                if ($cur_group['g_id'] == $group['info']['g_promote_next_group']) {
                    $output .= "\t\t\t\t\t\t\t\t\t\t\t".'<option value="'.$cur_group['g_id'].'" selected="selected">'.Utils::escape($cur_group['g_title']).'</option>'."\n";
                } else {
                    $output .= "\t\t\t\t\t\t\t\t\t\t\t".'<option value="'.$cur_group['g_id'].'">'.Utils::escape($cur_group['g_title']).'</option>'."\n";
                }
            }
        }

        $output = $this->hook->fire('model.admin.groups.get_group_list', $output);
        return $output;
    }

    public function get_group_list_delete($group_id)
    {
        $group_id = $this->hook->fire('model.admin.groups.get_group_list_delete_start', $group_id);

        $select_get_group_list_delete = array('g_id', 'g_title');
        $result = DB::for_table('groups')->select_many($select_get_group_list_delete)
                        ->where_not_equal('g_id', Container::get('forum_env')['FEATHER_GUEST'])
                        ->where_not_equal('g_id', $group_id)
                        ->order_by('g_title');
        $result = $this->hook->fireDB('model.admin.groups.get_group_list_delete', $result);
        $result = $result->find_many();

        $output = '';

        foreach ($result as $cur_group) {
            if ($cur_group['g_id'] == Container::get('forum_env')['FEATHER_MEMBER']) {
                // Pre-select the pre-defined Members group
                $output .= "\t\t\t\t\t\t\t\t\t\t".'<option value="'.$cur_group['g_id'].'" selected="selected">'.Utils::escape($cur_group['g_title']).'</option>'."\n";
            } else {
                $output .= "\t\t\t\t\t\t\t\t\t\t".'<option value="'.$cur_group['g_id'].'">'.Utils::escape($cur_group['g_title']).'</option>'."\n";
            }
        }

        $output = $this->hook->fire('model.admin.groups.get_group_list.output', $output);
        return $output;
    }

    public function add_edit_group($groups)
    {
        if ($this->request->post('group_id')) {
            $group_id = $this->request->post('group_id');
        } else {
            $group_id = 0;
        }

        $group_id = $this->hook->fire('model.admin.groups.add_edit_group_start', $group_id);

        // Is this the admin group? (special rules apply)
        $is_admin_group = ($this->request->post('group_id') && $this->request->post('group_id') == Container::get('forum_env')['FEATHER_ADMIN']) ? true : false;

        // Set group title
        $title = Utils::trim($this->request->post('req_title'));
        if ($title == '') {
            throw new Error(__('Must enter title message'), 400);
        }
        $title = $this->hook->fire('model.admin.groups.add_edit_group_set_title', $title);
        // Set user title
        $user_title = Utils::trim($this->request->post('user_title'));
        $user_title = ($user_title != '') ? $user_title : 'NULL';
        $user_title = $this->hook->fire('model.admin.groups.add_edit_group_set_user_title', $user_title);

        $promote_min_posts = $this->request->post('promote_min_posts') ? intval($this->request->post('promote_min_posts')) : '0';
        if ($this->request->post('promote_next_group') &&
                isset($groups[$this->request->post('promote_next_group')]) &&
                !in_array($this->request->post('promote_next_group'), array(Container::get('forum_env')['FEATHER_ADMIN'], Container::get('forum_env')['FEATHER_GUEST'])) &&
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
            'g_search_flood'        =>  $search_flood,
            'g_email_flood'         =>  $email_flood,
            'g_report_flood'        =>  $report_flood,
        );

        $insert_update_group = $this->hook->fire('model.admin.groups.add_edit_group_data', $insert_update_group);

        if ($this->request->post('mode') == 'add') {
            // Creating a new group
            $title_exists = DB::for_table('groups')->where('g_title', $title)->find_one();
            if ($title_exists) {
                throw new Error(sprintf(__('Title already exists message'), Utils::escape($title)), 400);
            }

            $add = DB::for_table('groups')
                        ->create();
            $add->set($insert_update_group)->save();
            $new_group_id = $this->hook->fire('model.admin.groups.add_edit_group.new_group_id', (int) $add->id());

            // Set new preferences
            $this->feather->prefs->setGroup($new_group_id, array('post.min_interval' => (int) $post_flood));

            // Now lets copy the forum specific permissions from the group which this group is based on
            $select_forum_perms = array('forum_id', 'read_forum', 'post_replies', 'post_topics');
            $result = DB::for_table('forum_perms')->select_many($select_forum_perms)
                            ->where('group_id', $this->request->post('base_group'));
            $result = $this->hook->fireDB('model.admin.groups.add_edit_group.select_forum_perms_query', $result);
            $result = $result->find_many();

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
            // We are editing an existing group
            $title_exists = DB::for_table('groups')->where('g_title', $title)->where_not_equal('g_id', $this->request->post('group_id'))->find_one();
            if ($title_exists) {
                throw new Error(sprintf(__('Title already exists message'), Utils::escape($title)), 400);
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

        $group_id = $this->request->post('mode') == 'add' ? $new_group_id : $this->request->post('group_id');
        $group_id = $this->hook->fire('model.admin.groups.add_edit_group.group_id', $group_id);

        // Regenerate the quick jump cache
        $this->feather->cache->store('quickjump', Cache::get_quickjump());

        if ($this->request->post('mode') == 'edit') {
            Router::redirect(Router::pathFor('adminGroups'), __('Group edited redirect'));
        } else {
            Router::redirect(Router::pathFor('adminGroups'), __('Group added redirect'));
        }
    }

    public function set_default_group($groups)
    {
        $group_id = intval($this->request->post('default_group'));
        $group_id = $this->hook->fire('model.admin.groups.set_default_group.group_id', $group_id);

        // Make sure it's not the admin or guest groups
        if ($group_id == Container::get('forum_env')['FEATHER_ADMIN'] || $group_id == Container::get('forum_env')['FEATHER_GUEST']) {
            throw new Error(__('Bad request'), 404);
        }

        // Make sure it's not a moderator group
        if ($groups[$group_id]['g_moderator'] != 0) {
            throw new Error(__('Bad request'), 404);
        }

        DB::for_table('config')->where('conf_name', 'o_default_user_group')
                                                   ->update_many('conf_value', $group_id);

        // Regenerate the config cache
        $this->feather->cache->store('config', Cache::get_config());

        Router::redirect(Router::pathFor('adminGroups'), __('Default group redirect'));
    }

    public function check_members($group_id)
    {
        $group_id = $this->hook->fire('model.admin.groups.check_members_start', $group_id);

        $is_member = DB::for_table('groups')->table_alias('g')
            ->select('g.g_title')
            ->select_expr('COUNT(u.id)', 'members')
            ->inner_join('users', array('g.g_id', '=', 'u.group_id'), 'u')
            ->where('g.g_id', $group_id)
            ->group_by('g.g_id')
            ->group_by('g_title');
        $is_member = $this->hook->fireDB('model.admin.groups.check_members', $is_member);
        $is_member = $is_member->find_one();

        return (bool) $is_member;
    }

    public function delete_group($group_id)
    {
        $group_id = $this->hook->fire('model.admin.groups.delete_group.group_id', $group_id);

        if ($this->request->post('del_group')) {
            $move_to_group = intval($this->request->post('move_to_group'));
            $move_to_group = $this->hook->fire('model.admin.groups.delete_group.move_to_group', $move_to_group);
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

        Router::redirect(Router::pathFor('adminGroups'), __('Group removed redirect'));
    }

    public function get_group_title($group_id)
    {
        $group_id = $this->hook->fireDB('model.admin.groups.get_group_title.group_id', $group_id);

        $group_title = DB::for_table('groups')->where('g_id', $group_id);
        $group_title = $this->hook->fireDB('model.admin.groups.get_group_title.query', $group_title);
        $group_title = $group_title->find_one_col('g_title');

        return $group_title;
    }

    public function get_title_members($group_id)
    {
        $group_id = $this->hook->fire('model.admin.groups.get_title_members.group_id', $group_id);

        $group = DB::for_table('groups')->table_alias('g')
                    ->select('g.g_title')
                    ->select_expr('COUNT(u.id)', 'members')
                    ->inner_join('users', array('g.g_id', '=', 'u.group_id'), 'u')
                    ->where('g.g_id', $group_id)
                    ->group_by('g.g_id')
                    ->group_by('g_title');
        $group = $this->hook->fireDB('model.admin.groups.get_title_members.query', $group);
        $group = $group->find_one();

        $group_info['title'] = $group['g_title'];
        $group_info['members'] = $group['members'];

        $group_info = $this->hook->fire('model.admin.groups.get_title_members.group_info', $group_info);
        return $group_info;
    }
}
