<?php

/**
 * Copyright (C) 2015-2017 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace FeatherBB\Model\Admin;

use FeatherBB\Core\Database as DB;
use FeatherBB\Core\Interfaces\Container;
use FeatherBB\Core\Interfaces\ForumEnv;
use FeatherBB\Core\Interfaces\Hooks;
use FeatherBB\Core\Interfaces\Perms;

class Forums
{
    public function addForum($catId, $forumName)
    {
        $setAddForum = ['forum_name' => $forumName,
                                'cat_id' => $catId];

        $setAddForum = Hooks::fire('model.admin.forums.add_forum', $setAddForum);

        $forum = DB::table('forums')
                    ->create()
                    ->set($setAddForum);
        $forum->save();

        return $forum->id();
    }

    public function updateForum($forumId, array $forumData)
    {
        $updateForum = DB::table('forums')
                    ->findOne($forumId)
                    ->set($forumData);
        $updateForum = Hooks::fireDB('model.admin.forums.update_forum_query', $updateForum);
        $updateForum = $updateForum->save();

        return $updateForum;
    }

    public function deleteForum($forumId)
    {
        $forumId = Hooks::fire('model.admin.forums.delete_forum_start', $forumId);

        // Prune all posts and topics
        $this->maintenance = new \FeatherBB\Model\Admin\Maintenance();
        $this->maintenance->prune($forumId, 1, -1);

        // Delete the forum
        $deleteForum = DB::table('forums')->findOne($forumId);
        $deleteForum = Hooks::fireDB('model.admin.forums.delete_forum_query', $deleteForum);
        $deleteForum->delete();

        // Delete forum specific group permissions and subscriptions
        $deleteForumPerms = DB::table('forum_perms')->where('forum_id', $forumId);
        $deleteForumPerms = Hooks::fireDB('model.admin.forums.delete_forum_perms_query', $deleteForumPerms);
        $deleteForumPerms->deleteMany();

        $deleteForumSubs = DB::table('forum_subscriptions')->where('forum_id', $forumId);
        $deleteForumSubs = Hooks::fireDB('model.admin.forums.delete_forum_subs_query', $deleteForumSubs);
        $deleteForumSubs->deleteMany();

        // Delete orphaned redirect topics
        $orphans = DB::table('topics')
                    ->tableAlias('t1')
                    ->leftOuterJoin('topics', ['t1.moved_to', '=', 't2.id'], 't2')
                    ->whereNull('t2.id')
                    ->whereNotNull('t1.moved_to');
        $orphans = Hooks::fireDB('model.admin.forums.delete_orphan_redirect_topics_query', $orphans);
        $orphans = $orphans->findMany();

        if (count($orphans) > 0) {
            $orphans->deleteMany();
        }

        return true; // TODO, better error handling
    }

    public function getForumInfo($forumId)
    {
        $result = DB::table('forums')
                    ->where('id', $forumId);
        $result = Hooks::fireDB('model.admin.forums.get_forum_infos', $result);
        $result = $result->findOne();

        return $result;
    }

    public function getForums()
    {
        $forumData = [];
        $forumData = Hooks::fire('model.admin.forums.get_forums_start', $forumData);

        $selectGetForums = ['cid' => 'c.id', 'c.cat_name', 'cat_position' => 'c.disp_position', 'fid' => 'f.id', 'f.forum_name', 'forum_position' => 'f.disp_position'];

        $result = DB::table('categories')
                    ->tableAlias('c')
                    ->selectMany($selectGetForums)
                    ->innerJoin('forums', ['c.id', '=', 'f.cat_id'], 'f')
                    ->orderByAsc('f.disp_position')
                    ->orderByAsc('c.disp_position');
        $result = Hooks::fireDB('model.admin.forums.get_forums_query', $result);
        $result = $result->findArray();

        foreach ($result as $forum) {
            if (!isset($forumData[$forum['cid']])) {
                $forumData[$forum['cid']] = ['cat_name' => $forum['cat_name'],
                                                   'cat_position' => $forum['cat_position'],
                                                   'cat_forums' => []];
            }
            $forumData[$forum['cid']]['cat_forums'][] = ['forum_id' => $forum['fid'],
                                                               'forum_name' => $forum['forum_name'],
                                                               'position' => $forum['forum_position']];
        }

        $forumData = Hooks::fire('model.admin.forums.get_forums', $forumData);
        return $forumData;
    }

    public function updatePositions($forumId, $position)
    {
        Hooks::fire('model.admin.forums.update_positions_start', $forumId, $position);

        return DB::table('forums')
                ->findOne($forumId)
                ->set('disp_position', $position)
                ->save();
    }

    public function getPermissions($forumId)
    {
        $permData = [];
        $forumId = Hooks::fire('model.admin.forums.get_permissions_start', $forumId);

        $selectPermissions = ['g.g_id', 'g.g_title', 'fp.read_forum', 'fp.post_replies', 'fp.post_topics'];

        $permissions = DB::table('groups')
                        ->tableAlias('g')
                        ->selectMany($selectPermissions)
                        ->leftOuterJoin('forum_perms', 'g.g_id=fp.group_id AND fp.forum_id='.$forumId, 'fp')
                        ->whereNotEqual('g.g_id', ForumEnv::get('FEATHER_ADMIN'))
                        ->orderByAsc('g.g_id');
        $permissions = Hooks::fireDB('model.admin.forums.get_permissions_query', $permissions);
        $permissions = $permissions->findMany();

        foreach ($permissions as $curPerm) {
            $groupPermissions = Perms::getGroupPermissions($curPerm['g_id']);

            $curPerm['board.read'] = isset($groupPermissions['board.read']);
            $curPerm['read_forum'] = ($curPerm['read_forum'] != '0') ? true : false;
            $curPerm['post_replies'] = ((!isset($groupPermissions['topic.reply']) && $curPerm['post_replies'] == '1') || (isset($groupPermissions['topic.reply']) && $curPerm['post_replies'] != '0')) ? true : false;
            $curPerm['post_topics'] = ((!isset($groupPermissions['topic.post']) && $curPerm['post_topics'] == '1') || (isset($groupPermissions['topic.post']) && $curPerm['post_topics'] != '0')) ? true : false;

            // Determine if the current settings differ from the default or not
            $curPerm['read_forum_def'] = ($curPerm['read_forum'] == '0') ? false : true;
            $curPerm['post_replies_def'] = (($curPerm['post_replies'] && !isset($groupPermissions['topic.reply'])) || (!$curPerm['post_replies'] && isset($groupPermissions['topic.reply']))) ? false : true;
            $curPerm['post_topics_def'] = (($curPerm['post_topics'] && !isset($groupPermissions['topic.post'])) || (!$curPerm['post_topics'] && isset($groupPermissions['topic.post']))) ? false : true;

            $permData[] = $curPerm;
        }

        $permData = Hooks::fire('model.admin.forums.get_permissions', $permData);
        return $permData;
    }

    public function getDefaultGroupPermissions($fetchAdmin = true)
    {
        $permData = [];

        $result = DB::table('groups')->select('g_id');

        if (!$fetchAdmin) {
            $result->whereNotEqual('g_id', ForumEnv::get('FEATHER_ADMIN'));
        }

        $result = $result->orderByAsc('g_id');
        $result = Hooks::fireDB('model.admin.forums.get_default_group_permissions_query', $result);
        $result = $result->findArray();

        foreach ($result as $curPerm) {
            $groupPermissions = Perms::getGroupPermissions($curPerm['g_id']);
            $curPerm['board.read'] = $groupPermissions['board.read'];
            $curPerm['topic.reply'] = $groupPermissions['topic.reply'];
            $curPerm['topic.post'] = $groupPermissions['topic.post'];

            $permData[] = $curPerm;
        }

        return $permData;
    }

    public function updatePermissions(array $permissionsData)
    {
        $permissionsData = Hooks::fire('model.admin.forums.update_permissions_start', $permissionsData);

        $permissions = DB::table('forum_perms')
                            ->where('forum_id', $permissionsData['forum_id'])
                            ->where('group_id', $permissionsData['group_id'])
                            ->deleteMany();

        if ($permissions) {
            return DB::table('forum_perms')
                    ->create()
                    ->set($permissionsData)
                    ->save();
        }
    }

    public function deletePermissions($forumId, $groupId = null)
    {
        $result = DB::table('forum_perms')
                    ->where('forum_id', $forumId);

        if ($groupId) {
            $result->where('group_id', $groupId);
        }

        $result = Hooks::fireDB('model.admin.forums.delete_permissions_query', $result);

        return $result->deleteMany();
    }
}
