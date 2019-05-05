<?php

/**
 * Copyright (C) 2015-2019 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace FeatherBB\Model\Admin;

use FeatherBB\Core\Database as DB;
use FeatherBB\Core\Error;
use FeatherBB\Core\Interfaces\Cache as CacheInterface;
use FeatherBB\Core\Interfaces\Container;
use FeatherBB\Core\Interfaces\ForumEnv;
use FeatherBB\Core\Interfaces\Hooks;
use FeatherBB\Core\Interfaces\Input;
use FeatherBB\Core\Interfaces\Perms;
use FeatherBB\Core\Interfaces\Prefs;
use FeatherBB\Core\Interfaces\Router;
use FeatherBB\Core\Utils;
use FeatherBB\Model\Cache;

class Groups
{
    public function groups()
    {
        $result = DB::table('groups')->orderBy('g_id')->findMany();
        Hooks::fireDB('model.admin.groups.fetch_groups_query', $result);
        $groups = [];
        foreach ($result as $curGroup) {
            $groups[$curGroup['g_id']] = $curGroup;
            $groups[$curGroup['g_id']]['is_moderator'] = Perms::getGroupPermissions($curGroup['g_id'], 'mod.is_mod');
        }

        $groups = Hooks::fire('model.admin.groups.fetch_groups', $groups);

        return $groups;
    }

    public function infoAdd($groups, $id)
    {
        $group = [];

        if (Input::post('add_group')) {
            $group['base_group'] = $id; // Equals intval(Input::post('base_group'))
            $group['base_group'] = Hooks::fire('model.admin.groups.add_user_group', $group['base_group']);

            $group['mode'] = 'add';
        } else {
            // We are editing a group
            if (!isset($groups[$id])) {
                throw new Error(__('Bad request'), 404);
            }
            $groups[$id] = Hooks::fire('model.admin.groups.update_user_group', $groups[$id]);

            $group['mode'] = 'edit';
        }

        $group['info'] = $groups[$id];
        $group['prefs'] = Prefs::getGroupPreferences($id);
        $group['perms'] = Perms::getGroupPermissions($id);

        $group = Hooks::fire('model.admin.groups.info_add_group', $group);
        return $group;
    }

    public function getGroupList($groups, $group)
    {
        $output = '';

        foreach ($groups as $curGroup) {
            if (($curGroup['g_id'] != $group['info']['g_id'] || $group['mode'] == 'add') && $curGroup['g_id'] != ForumEnv::get('FEATHER_ADMIN') && $curGroup['g_id'] != ForumEnv::get('FEATHER_GUEST')) {
                if ($curGroup['g_id'] == $group['prefs']['promote.next_group']) {
                    $output .= "\t\t\t\t\t\t\t\t\t\t\t".'<option value="'.$curGroup['g_id'].'" selected="selected">'.Utils::escape($curGroup['g_title']).'</option>'."\n";
                } else {
                    $output .= "\t\t\t\t\t\t\t\t\t\t\t".'<option value="'.$curGroup['g_id'].'">'.Utils::escape($curGroup['g_title']).'</option>'."\n";
                }
            }
        }

        $output = Hooks::fire('model.admin.groups.get_group_list', $output);
        return $output;
    }

    public function groupListDelete($groupId)
    {
        $groupId = Hooks::fire('model.admin.groups.get_group_list_delete_start', $groupId);

        $selectGetGroupListDelete = ['g_id', 'g_title'];
        $result = DB::table('groups')->selectMany($selectGetGroupListDelete)
                        ->whereNotEqual('g_id', ForumEnv::get('FEATHER_GUEST'))
                        ->whereNotEqual('g_id', $groupId)
                        ->orderBy('g_title');
        $result = Hooks::fireDB('model.admin.groups.get_group_list_delete', $result);
        $result = $result->findMany();

        $output = '';

        foreach ($result as $curGroup) {
            if ($curGroup['g_id'] == ForumEnv::get('FEATHER_MEMBER')) {
                // Pre-select the pre-defined Members group
                $output .= "\t\t\t\t\t\t\t\t\t\t".'<option value="'.$curGroup['g_id'].'" selected="selected">'.Utils::escape($curGroup['g_title']).'</option>'."\n";
            } else {
                $output .= "\t\t\t\t\t\t\t\t\t\t".'<option value="'.$curGroup['g_id'].'">'.Utils::escape($curGroup['g_title']).'</option>'."\n";
            }
        }

        $output = Hooks::fire('model.admin.groups.get_group_list.output', $output);
        return $output;
    }

    public function addEdit($groups)
    {
        if (Input::post('group_id')) {
            $groupId = Input::post('group_id');
        } else {
            $groupId = 0;
        }

        $groupId = Hooks::fire('model.admin.groups.add_edit_group_start', $groupId);

        // Is this the admin group? (special rules apply)
        $isAdminGroup = (Input::post('group_id') && Input::post('group_id') == ForumEnv::get('FEATHER_ADMIN')) ? true : false;

        // Set group title
        $title = Utils::trim(Input::post('req_title'));
        if ($title == '') {
            throw new Error(__('Must enter title message'), 400);
        }
        $title = Hooks::fire('model.admin.groups.add_edit_group_set_title', $title);
        // Set user title
        $userTitle = Utils::trim(Input::post('user_title'));
        $userTitle = ($userTitle != '') ? $userTitle : 'NULL';
        $userTitle = Hooks::fire('model.admin.groups.add_edit_group.set_user_title', $userTitle);

        $promoteMinPosts = Input::post('promote_min_posts') ? intval(Input::post('promote_min_posts')) : '0';
        if (Input::post('promote_next_group') &&
                isset($groups[Input::post('promote_next_group')]) &&
                !in_array(Input::post('promote_next_group'), [ForumEnv::get('FEATHER_ADMIN'), ForumEnv::get('FEATHER_GUEST')]) &&
                (Input::post('group_id') || Input::post('promote_next_group') != Input::post('group_id'))) {
            $promoteNextGroup = Input::post('promote_next_group');
        } else {
            $promoteNextGroup = '0';
        }

        // Permissions
        $moderator = Input::post('moderator') != null && Input::post('moderator') == 1 ? 1 : 0;
        $modEditUsers = $moderator == 1 && Input::post('mod_edit_users') == 1 ? 1 : 0;
        $modRenameUsers = $moderator == 1 && Input::post('mod_rename_users') == 1 ? 1 : 0;
        $modChangePasswords = $moderator == 1 && Input::post('mod_change_passwords') == 1 ? 1 : 0;
        $modBanUsers = $moderator == 1 && Input::post('mod_ban_users') == 1 ? 1 : 0;
        $modPromoteUsers = $moderator == 1 && Input::post('mod_promote_users') == 1 ? 1 : 0;
        $readBoard = Input::post('read_board') != null ? intval(Input::post('read_board')) : 1;
        $viewUsers = (Input::post('view_users') != null && Input::post('view_users') == 1) || $isAdminGroup ? 1 : 0;
        $postReplies = Input::post('post_replies') != null ? intval(Input::post('post_replies')) : 1;
        $postTopics = Input::post('post_topics') != null ? intval(Input::post('post_topics')) : 1;
        $editPosts = Input::post('edit_posts') != null ? intval(Input::post('edit_posts')) : ($isAdminGroup) ? 1 : 0;
        $deletePosts = Input::post('delete_posts') != null ? intval(Input::post('delete_posts')) : ($isAdminGroup) ? 1 : 0;
        $deleteTopics = Input::post('delete_topics') != null ? intval(Input::post('delete_topics')) : ($isAdminGroup) ? 1 : 0;
        $postLinks = Input::post('post_links') != null ? intval(Input::post('post_links')) : 1;
        $setTitle = Input::post('set_title') != null ? intval(Input::post('set_title')) : ($isAdminGroup) ? 1 : 0;
        $search = Input::post('search') != null ? intval(Input::post('search')) : 1;
        $searchUsers = Input::post('search_users') != null ? intval(Input::post('search_users')) : 1;
        $sendEmail = (Input::post('send_email') != null && Input::post('send_email') == 1) || $isAdminGroup ? 1 : 0;

        // Preferences
        $postFlood = (Input::post('post_flood') != null && Input::post('post_flood') >= 0) ? Input::post('post_flood') : 0;
        $searchFlood = (Input::post('search_flood') != null && Input::post('search_flood') >= 0) ? Input::post('search_flood') : 0;
        $emailFlood = (Input::post('email_flood') != null && Input::post('email_flood') >= 0) ? Input::post('email_flood') : 0;
        $reportFlood = (Input::post('report_flood') != null && Input::post('report_flood') >= 0) ? Input::post('report_flood') : 0;

        $insertUpdateGroup = [
            'g_title'               =>  $title,
            'g_user_title'          =>  $userTitle,
        ];
        $groupPreferences = [
            'post.min_interval'     => (int) $postFlood,
            'search.min_interval'   => (int) $searchFlood,
            'email.min_interval'    => (int) $emailFlood,
            'report.min_interval'   => (int) $reportFlood,
            'promote.min_posts'     => (int) $promoteMinPosts,
            'promote.next_group'    => (int) $promoteNextGroup,
        ];
        $groupPermissions = [
            'mod.is_mod'            => (int) $moderator,
            'mod.edit_users'        => (int) $modEditUsers,
            'mod.rename_users'      => (int) $modRenameUsers,
            'mod.change_passwords'  => (int) $modChangePasswords,
            'mod.promote_users'     => (int) $modPromoteUsers,
            'mod.ban_users'         => (int) $modBanUsers,
            'board.read'            => (int) $readBoard,
            'topic.reply'           => (int) $postReplies,
            'topic.post'            => (int) $postTopics,
            'topic.delete'          => (int) $deleteTopics,
            'post.edit'             => (int) $editPosts,
            'post.delete'           => (int) $deletePosts,
            'post.links'            => (int) $postLinks,
            'users.view'            => (int) $viewUsers,
            'user.set_title'        => (int) $setTitle,
            'search.topics'         => (int) $search,
            'search.users'          => (int) $searchUsers,
            'email.send'            => (int) $sendEmail,
        ];

        $insertUpdateGroup = Hooks::fire('model.admin.groups.add_edit_group.data', $insertUpdateGroup);
        $groupPreferences = Hooks::fire('model.admin.groups.add_edit_group.preferences', $groupPreferences);
        $groupPermissions = Hooks::fire('model.admin.groups.add_edit_group.permissions', $groupPermissions);

        if (Input::post('mode') == 'add') {
            // Creating a new group
            $titleExists = DB::table('groups')->where('g_title', $title)->findOne();
            if ($titleExists) {
                throw new Error(sprintf(__('Title already exists message'), Utils::escape($title)), 400);
            }

            $add = DB::table('groups')
                        ->create();
            $add->set($insertUpdateGroup)->save();
            $newGroupId = Hooks::fire('model.admin.groups.add_edit_group.new_group_id', (int) $add->id());

            // Set new group preferences
            Prefs::setGroup($newGroupId, $groupPreferences);

            // Now lets copy the forum specific permissions from the group which this group is based on
            $selectForumPerms = ['forum_id', 'read_forum', 'post_replies', 'post_topics'];
            $result = DB::table('forum_perms')->selectMany($selectForumPerms)
                            ->where('group_id', Input::post('base_group'));
            $result = Hooks::fireDB('model.admin.groups.add_edit_group.select_forum_perms_query', $result);
            $result = $result->findMany();

            foreach ($result as $curForumPerm) {
                $insertPerms = [
                    'group_id'       =>  $newGroupId,
                    'forum_id'       =>  $curForumPerm['forum_id'],
                    'read_forum'     =>  $curForumPerm['read_forum'],
                    'post_replies'   =>  $curForumPerm['post_replies'],
                    'post_topics'    =>  $curForumPerm['post_topics'],
                ];

                DB::table('forum_perms')
                        ->create()
                        ->set($insertPerms)
                        ->save();
            }
        } else {
            // We are editing an existing group
            $titleExists = DB::table('groups')->where('g_title', $title)->whereNotEqual('g_id', Input::post('group_id'))->findOne();
            if ($titleExists) {
                throw new Error(sprintf(__('Title already exists message'), Utils::escape($title)), 400);
            }
            DB::table('groups')
                    ->findOne(Input::post('group_id'))
                    ->set($insertUpdateGroup)
                    ->save();

            // Update group preferences
            Prefs::setGroup(Input::post('group_id'), $groupPreferences);

            // Promote all users who would be promoted to this group on their next post
            if ($promoteNextGroup) {
                DB::table('users')->where('group_id', Input::post('group_id'))
                        ->whereGte('num_posts', $promoteMinPosts)
                        ->updateMany('group_id', $promoteNextGroup);
            }
        }

        $groupId = Input::post('mode') == 'add' ? $newGroupId : Input::post('group_id');
        $groupId = Hooks::fire('model.admin.groups.add_edit_group.group_id', $groupId);

        // Update group permissions
        $allowedPerms = array_filter($groupPermissions);
        $deniedPerms = array_diff($groupPermissions, $allowedPerms);
        Perms::allowGroup($groupId, array_keys($allowedPerms));
        Perms::denyGroup($groupId, array_keys($deniedPerms));
        // Reload cache
        CacheInterface::store('permissions', Cache::getPermissions());
        CacheInterface::store('group_preferences', Cache::getGroupPreferences());

        // Regenerate the quick jump cache
        CacheInterface::store('quickjump', Cache::quickjump());

        if (Input::post('mode') == 'edit') {
            return Router::redirect(Router::pathFor('adminGroups'), __('Group edited redirect'));
        } else {
            return Router::redirect(Router::pathFor('adminGroups'), __('Group added redirect'));
        }
    }

    public function setDefaultGroup($groups)
    {
        $groupId = intval(Input::post('default_group'));
        $groupId = Hooks::fire('model.admin.groups.set_default_group.group_id', $groupId);

        // Make sure it's not the admin or guest groups
        if ($groupId == ForumEnv::get('FEATHER_ADMIN') || $groupId == ForumEnv::get('FEATHER_GUEST')) {
            throw new Error(__('Bad request'), 404);
        }

        // Make sure it's not a moderator group
        if (Perms::getGroupPermissions($groupId, 'mod.is_mod')) {
            throw new Error(__('Bad request'), 404);
        }

        DB::table('config')->where('conf_name', 'o_default_user_group')
                                                   ->updateMany('conf_value', $groupId);

        // Regenerate the config cache
        $config = array_merge(Cache::getConfig(), Cache::getPreferences());
        CacheInterface::store('config', $config);

        return Router::redirect(Router::pathFor('adminGroups'), __('Default group redirect'));
    }

    public function checkMembers($groupId)
    {
        $groupId = Hooks::fire('model.admin.groups.check_members_start', $groupId);

        $isMember = DB::table('groups')->tableAlias('g')
            ->select('g.g_title')
            ->selectExpr('COUNT(u.id)', 'members')
            ->innerJoin('users', ['g.g_id', '=', 'u.group_id'], 'u')
            ->where('g.g_id', $groupId)
            ->groupBy('g.g_id')
            ->groupBy('g_title');
        $isMember = Hooks::fireDB('model.admin.groups.check_members', $isMember);
        $isMember = $isMember->findOne();

        return (bool) $isMember;
    }

    public function deleteGroup($groupId)
    {
        $groupId = Hooks::fire('model.admin.groups.delete_group.group_id', $groupId);

        if (Input::post('del_group')) {
            $moveToGroup = intval(Input::post('move_to_group'));
            $moveToGroup = Hooks::fire('model.admin.groups.delete_group.move_to_group', $moveToGroup);
            DB::table('users')->where('group_id', $groupId)
                                                      ->updateMany('group_id', $moveToGroup);
        }

        // Delete the group and any forum specific permissions
        DB::table('groups')
            ->where('g_id', $groupId)
            ->deleteMany();
        DB::table('forum_perms')
            ->where('group_id', $groupId)
            ->deleteMany();
        DB::table('permissions')
            ->where('group', $groupId)
            ->deleteMany();
        DB::table('preferences')
            ->where('group', $groupId)
            ->deleteMany();

        // Don't let users be promoted to this group
        DB::table('preferences')->where('promote.next_group', $groupId)->deleteMany();

        return Router::redirect(Router::pathFor('adminGroups'), __('Group removed redirect'));
    }

    public function groupTitle($groupId)
    {
        $groupId = Hooks::fireDB('model.admin.groups.get_group_title.group_id', $groupId);

        $groupTitle = DB::table('groups')->where('g_id', $groupId);
        $groupTitle = Hooks::fireDB('model.admin.groups.get_group_title.query', $groupTitle);
        $groupTitle = $groupTitle->findOneCol('g_title');

        return $groupTitle;
    }

    public function titleMembers($groupId)
    {
        $groupId = Hooks::fire('model.admin.groups.get_title_members.group_id', $groupId);

        $group = DB::table('groups')->tableAlias('g')
                    ->select('g.g_title')
                    ->selectExpr('COUNT(u.id)', 'members')
                    ->innerJoin('users', ['g.g_id', '=', 'u.group_id'], 'u')
                    ->where('g.g_id', $groupId)
                    ->groupBy('g.g_id')
                    ->groupBy('g_title');
        $group = Hooks::fireDB('model.admin.groups.get_title_members.query', $group);
        $group = $group->findOne();

        $groupInfo['title'] = $group['g_title'];
        $groupInfo['members'] = $group['members'];

        $groupInfo = Hooks::fire('model.admin.groups.get_title_members.group_info', $groupInfo);
        return $groupInfo;
    }
}
