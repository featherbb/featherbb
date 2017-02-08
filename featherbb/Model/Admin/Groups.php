<?php

/**
 * Copyright (C) 2015-2017 FeatherBB
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
    public function groups()
    {
        $result = DB::for_table('groups')->order_by('g_id')->find_many();
        Container::get('hooks')->fireDB('model.admin.groups.fetch_groups_query', $result);
        $groups = [];
        foreach ($result as $cur_group) {
            $groups[$cur_group['g_id']] = $cur_group;
            $groups[$cur_group['g_id']]['is_moderator'] = Container::get('perms')->getGroupPermissions($cur_group['g_id'], 'mod.is_mod');
        }

        $groups = Container::get('hooks')->fire('model.admin.groups.fetch_groups', $groups);

        return $groups;
    }

    public function infoAdd($groups, $id)
    {
        $group = [];

        if (Input::post('add_group')) {
            $group['base_group'] = $id; // Equals intval(Input::post('base_group'))
            $group['base_group'] = Container::get('hooks')->fire('model.admin.groups.add_user_group', $group['base_group']);

            $group['mode'] = 'add';
        } else {
            // We are editing a group
            if (!isset($groups[$id])) {
                throw new Error(__('Bad request'), 404);
            }
            $groups[$id] = Container::get('hooks')->fire('model.admin.groups.update_user_group', $groups[$id]);

            $group['mode'] = 'edit';
        }

        $group['info'] = $groups[$id];
        $group['prefs'] = Container::get('prefs')->getGroupPreferences($id);
        $group['perms'] = Container::get('perms')->getGroupPermissions($id);

        $group = Container::get('hooks')->fire('model.admin.groups.info_add_group', $group);
        return $group;
    }

    public function getGroupList($groups, $group)
    {
        $output = '';

        foreach ($groups as $cur_group) {
            if (($cur_group['g_id'] != $group['info']['g_id'] || $group['mode'] == 'add') && $cur_group['g_id'] != ForumEnv::get('FEATHER_ADMIN') && $cur_group['g_id'] != ForumEnv::get('FEATHER_GUEST')) {
                if ($cur_group['g_id'] == $group['prefs']['promote.next_group']) {
                    $output .= "\t\t\t\t\t\t\t\t\t\t\t".'<option value="'.$cur_group['g_id'].'" selected="selected">'.Utils::escape($cur_group['g_title']).'</option>'."\n";
                } else {
                    $output .= "\t\t\t\t\t\t\t\t\t\t\t".'<option value="'.$cur_group['g_id'].'">'.Utils::escape($cur_group['g_title']).'</option>'."\n";
                }
            }
        }

        $output = Container::get('hooks')->fire('model.admin.groups.get_group_list', $output);
        return $output;
    }

    public function groupListDelete($group_id)
    {
        $group_id = Container::get('hooks')->fire('model.admin.groups.get_group_list_delete_start', $group_id);

        $select_get_group_list_delete = ['g_id', 'g_title'];
        $result = DB::for_table('groups')->select_many($select_get_group_list_delete)
                        ->where_not_equal('g_id', ForumEnv::get('FEATHER_GUEST'))
                        ->where_not_equal('g_id', $group_id)
                        ->order_by('g_title');
        $result = Container::get('hooks')->fireDB('model.admin.groups.get_group_list_delete', $result);
        $result = $result->find_many();

        $output = '';

        foreach ($result as $cur_group) {
            if ($cur_group['g_id'] == ForumEnv::get('FEATHER_MEMBER')) {
                // Pre-select the pre-defined Members group
                $output .= "\t\t\t\t\t\t\t\t\t\t".'<option value="'.$cur_group['g_id'].'" selected="selected">'.Utils::escape($cur_group['g_title']).'</option>'."\n";
            } else {
                $output .= "\t\t\t\t\t\t\t\t\t\t".'<option value="'.$cur_group['g_id'].'">'.Utils::escape($cur_group['g_title']).'</option>'."\n";
            }
        }

        $output = Container::get('hooks')->fire('model.admin.groups.get_group_list.output', $output);
        return $output;
    }

    public function addEdit($groups)
    {
        if (Input::post('group_id')) {
            $group_id = Input::post('group_id');
        } else {
            $group_id = 0;
        }

        $group_id = Container::get('hooks')->fire('model.admin.groups.add_edit_group_start', $group_id);

        // Is this the admin group? (special rules apply)
        $is_admin_group = (Input::post('group_id') && Input::post('group_id') == ForumEnv::get('FEATHER_ADMIN')) ? true : false;

        // Set group title
        $title = Utils::trim(Input::post('req_title'));
        if ($title == '') {
            throw new Error(__('Must enter title message'), 400);
        }
        $title = Container::get('hooks')->fire('model.admin.groups.add_edit_group_set_title', $title);
        // Set user title
        $user_title = Utils::trim(Input::post('user_title'));
        $user_title = ($user_title != '') ? $user_title : 'NULL';
        $user_title = Container::get('hooks')->fire('model.admin.groups.add_edit_group.set_user_title', $user_title);

        $promote_min_posts = Input::post('promote_min_posts') ? intval(Input::post('promote_min_posts')) : '0';
        if (Input::post('promote_next_group') &&
                isset($groups[Input::post('promote_next_group')]) &&
                !in_array(Input::post('promote_next_group'), [ForumEnv::get('FEATHER_ADMIN'), ForumEnv::get('FEATHER_GUEST')]) &&
                (Input::post('group_id') || Input::post('promote_next_group') != Input::post('group_id'))) {
            $promote_next_group = Input::post('promote_next_group');
        } else {
            $promote_next_group = '0';
        }

        // Permissions
        $moderator = Input::post('moderator') && Input::post('moderator') == '1' ? '1' : '0';
        $mod_edit_users = $moderator == '1' && Input::post('mod_edit_users') == '1' ? '1' : '0';
        $mod_rename_users = $moderator == '1' && Input::post('mod_rename_users') == '1' ? '1' : '0';
        $mod_change_passwords = $moderator == '1' && Input::post('mod_change_passwords') == '1' ? '1' : '0';
        $mod_ban_users = $moderator == '1' && Input::post('mod_ban_users') == '1' ? '1' : '0';
        $mod_promote_users = $moderator == '1' && Input::post('mod_promote_users') == '1' ? '1' : '0';
        $read_board = Input::post('read_board') ? intval(Input::post('read_board')) : '1';
        $view_users = (Input::post('view_users') && Input::post('view_users') == '1') || $is_admin_group ? '1' : '0';
        $post_replies = Input::post('post_replies') ? intval(Input::post('post_replies')) : '1';
        $post_topics = Input::post('post_topics') ? intval(Input::post('post_topics')) : '1';
        $edit_posts = Input::post('edit_posts') ? intval(Input::post('edit_posts')) : ($is_admin_group) ? '1' : '0';
        $delete_posts = Input::post('delete_posts') ? intval(Input::post('delete_posts')) : ($is_admin_group) ? '1' : '0';
        $delete_topics = Input::post('delete_topics') ? intval(Input::post('delete_topics')) : ($is_admin_group) ? '1' : '0';
        $post_links = Input::post('post_links') ? intval(Input::post('post_links')) : '1';
        $set_title = Input::post('set_title') ? intval(Input::post('set_title')) : ($is_admin_group) ? '1' : '0';
        $search = Input::post('search') ? intval(Input::post('search')) : '1';
        $search_users = Input::post('search_users') ? intval(Input::post('search_users')) : '1';
        $send_email = (Input::post('send_email') && Input::post('send_email') == '1') || $is_admin_group ? '1' : '0';
        // Preferences
        $post_flood = (Input::post('post_flood') && Input::post('post_flood') >= 0) ? Input::post('post_flood') : '0';
        $search_flood = (Input::post('search_flood') && Input::post('search_flood') >= 0) ? Input::post('search_flood') : '0';
        $email_flood = (Input::post('email_flood') && Input::post('email_flood') >= 0) ? Input::post('email_flood') : '0';
        $report_flood = (Input::post('report_flood') && Input::post('report_flood') >= 0) ? Input::post('report_flood') : '0';

        $insert_update_group = [
            'g_title'               =>  $title,
            'g_user_title'          =>  $user_title,
        ];
        $group_preferences = [
            'post.min_interval'     => (int) $post_flood,
            'search.min_interval'   => (int) $search_flood,
            'email.min_interval'    => (int) $email_flood,
            'report.min_interval'   => (int) $report_flood,
            'promote.min_posts'     => (int) $promote_min_posts,
            'promote.next_group'    => (int) $promote_next_group,
        ];
        $group_permissions = [
            'mod.is_mod'            => (int) $moderator,
            'mod.edit_users'        => (int) $mod_edit_users,
            'mod.rename_users'      => (int) $mod_rename_users,
            'mod.change_passwords'  => (int) $mod_change_passwords,
            'mod.promote_users'     => (int) $mod_promote_users,
            'mod.ban_users'         => (int) $mod_ban_users,
            'board.read'            => (int) $read_board,
            'topic.reply'           => (int) $post_replies,
            'topic.post'            => (int) $post_topics,
            'topic.delete'          => (int) $delete_topics,
            'post.edit'             => (int) $edit_posts,
            'post.delete'           => (int) $delete_posts,
            'post.links'            => (int) $post_links,
            'users.view'            => (int) $view_users,
            'user.set_title'        => (int) $set_title,
            'search.topics'         => (int) $search,
            'search.users'          => (int) $search_users,
            'email.send'            => (int) $send_email,
        ];

        $insert_update_group = Container::get('hooks')->fire('model.admin.groups.add_edit_group.data', $insert_update_group);
        $group_preferences = Container::get('hooks')->fire('model.admin.groups.add_edit_group.preferences', $group_preferences);
        $group_permissions = Container::get('hooks')->fire('model.admin.groups.add_edit_group.permissions', $group_permissions);

        if (Input::post('mode') == 'add') {
            // Creating a new group
            $title_exists = DB::for_table('groups')->where('g_title', $title)->find_one();
            if ($title_exists) {
                throw new Error(sprintf(__('Title already exists message'), Utils::escape($title)), 400);
            }

            $add = DB::for_table('groups')
                        ->create();
            $add->set($insert_update_group)->save();
            $new_group_id = Container::get('hooks')->fire('model.admin.groups.add_edit_group.new_group_id', (int) $add->id());

            // Set new group preferences
            Container::get('prefs')->setGroup($new_group_id, $group_preferences);

            // Now lets copy the forum specific permissions from the group which this group is based on
            $select_forum_perms = ['forum_id', 'read_forum', 'post_replies', 'post_topics'];
            $result = DB::for_table('forum_perms')->select_many($select_forum_perms)
                            ->where('group_id', Input::post('base_group'));
            $result = Container::get('hooks')->fireDB('model.admin.groups.add_edit_group.select_forum_perms_query', $result);
            $result = $result->find_many();

            foreach ($result as $cur_forum_perm) {
                $insert_perms = [
                    'group_id'       =>  $new_group_id,
                    'forum_id'       =>  $cur_forum_perm['forum_id'],
                    'read_forum'     =>  $cur_forum_perm['read_forum'],
                    'post_replies'   =>  $cur_forum_perm['post_replies'],
                    'post_topics'    =>  $cur_forum_perm['post_topics'],
                ];

                DB::for_table('forum_perms')
                        ->create()
                        ->set($insert_perms)
                        ->save();
            }
        } else {
            // We are editing an existing group
            $title_exists = DB::for_table('groups')->where('g_title', $title)->where_not_equal('g_id', Input::post('group_id'))->find_one();
            if ($title_exists) {
                throw new Error(sprintf(__('Title already exists message'), Utils::escape($title)), 400);
            }
            DB::for_table('groups')
                    ->find_one(Input::post('group_id'))
                    ->set($insert_update_group)
                    ->save();

            // Update group preferences
            Container::get('prefs')->setGroup(Input::post('group_id'), $group_preferences);

            // Promote all users who would be promoted to this group on their next post
            if ($promote_next_group) {
                DB::for_table('users')->where('group_id', Input::post('group_id'))
                        ->where_gte('num_posts', $promote_min_posts)
                        ->update_many('group_id', $promote_next_group);
            }
        }

        $group_id = Input::post('mode') == 'add' ? $new_group_id : Input::post('group_id');
        $group_id = Container::get('hooks')->fire('model.admin.groups.add_edit_group.group_id', $group_id);

        // Update group permissions
        $allowed_perms = array_filter($group_permissions);
        $denied_perms = array_diff($group_permissions, $allowed_perms);
        Container::get('perms')->allowGroup($group_id, array_keys($allowed_perms));
        Container::get('perms')->denyGroup($group_id, array_keys($denied_perms));
        // Reload cache
        Container::get('cache')->store('permissions', \FeatherBB\Model\Cache::getPermissions());
        Container::get('cache')->store('group_preferences', \FeatherBB\Model\Cache::getGroupPreferences());

        // Regenerate the quick jump cache
        Container::get('cache')->store('quickjump', Cache::quickjump());

        if (Input::post('mode') == 'edit') {
            return Router::redirect(Router::pathFor('adminGroups'), __('Group edited redirect'));
        } else {
            return Router::redirect(Router::pathFor('adminGroups'), __('Group added redirect'));
        }
    }

    public function setDefaultGroup($groups)
    {
        $group_id = intval(Input::post('default_group'));
        $group_id = Container::get('hooks')->fire('model.admin.groups.set_default_group.group_id', $group_id);

        // Make sure it's not the admin or guest groups
        if ($group_id == ForumEnv::get('FEATHER_ADMIN') || $group_id == ForumEnv::get('FEATHER_GUEST')) {
            throw new Error(__('Bad request'), 404);
        }

        // Make sure it's not a moderator group
        if (Container::get('perms')->getGroupPermissions($group_id, 'mod.is_mod')) {
            throw new Error(__('Bad request'), 404);
        }

        DB::for_table('config')->where('conf_name', 'o_default_user_group')
                                                   ->update_many('conf_value', $group_id);

        // Regenerate the config cache
        $config = array_merge(Cache::getConfig(), Cache::getPreferences());
        Container::get('cache')->store('config', $config);

        return Router::redirect(Router::pathFor('adminGroups'), __('Default group redirect'));
    }

    public function checkMembers($group_id)
    {
        $group_id = Container::get('hooks')->fire('model.admin.groups.check_members_start', $group_id);

        $is_member = DB::for_table('groups')->table_alias('g')
            ->select('g.g_title')
            ->select_expr('COUNT(u.id)', 'members')
            ->inner_join('users', ['g.g_id', '=', 'u.group_id'], 'u')
            ->where('g.g_id', $group_id)
            ->group_by('g.g_id')
            ->group_by('g_title');
        $is_member = Container::get('hooks')->fireDB('model.admin.groups.check_members', $is_member);
        $is_member = $is_member->find_one();

        return (bool) $is_member;
    }

    public function deleteGroup($group_id)
    {
        $group_id = Container::get('hooks')->fire('model.admin.groups.delete_group.group_id', $group_id);

        if (Input::post('del_group')) {
            $move_to_group = intval(Input::post('move_to_group'));
            $move_to_group = Container::get('hooks')->fire('model.admin.groups.delete_group.move_to_group', $move_to_group);
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
        DB::for_table('permissions')
            ->where('group', $group_id)
            ->delete_many();
        DB::for_table('preferences')
            ->where('group', $group_id)
            ->delete_many();

        // Don't let users be promoted to this group
        DB::for_table('preferences')->where('promote.next_group', $group_id)->delete_many();

        return Router::redirect(Router::pathFor('adminGroups'), __('Group removed redirect'));
    }

    public function groupTitle($group_id)
    {
        $group_id = Container::get('hooks')->fireDB('model.admin.groups.get_group_title.group_id', $group_id);

        $group_title = DB::for_table('groups')->where('g_id', $group_id);
        $group_title = Container::get('hooks')->fireDB('model.admin.groups.get_group_title.query', $group_title);
        $group_title = $group_title->find_one_col('g_title');

        return $group_title;
    }

    public function titleMembers($group_id)
    {
        $group_id = Container::get('hooks')->fire('model.admin.groups.get_title_members.group_id', $group_id);

        $group = DB::for_table('groups')->table_alias('g')
                    ->select('g.g_title')
                    ->select_expr('COUNT(u.id)', 'members')
                    ->inner_join('users', ['g.g_id', '=', 'u.group_id'], 'u')
                    ->where('g.g_id', $group_id)
                    ->group_by('g.g_id')
                    ->group_by('g_title');
        $group = Container::get('hooks')->fireDB('model.admin.groups.get_title_members.query', $group);
        $group = $group->find_one();

        $group_info['title'] = $group['g_title'];
        $group_info['members'] = $group['members'];

        $group_info = Container::get('hooks')->fire('model.admin.groups.get_title_members.group_info', $group_info);
        return $group_info;
    }
}
