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
use FeatherBB\Core\Interfaces\ForumEnv;
use FeatherBB\Core\Interfaces\Hooks;
use FeatherBB\Core\Interfaces\Input;
use FeatherBB\Core\Interfaces\Perms;
use FeatherBB\Core\Interfaces\Router;
use FeatherBB\Core\Interfaces\User;
use FeatherBB\Core\Utils;
use FeatherBB\Model\Cache;

class Users
{
    public function getNumIp($ipStats)
    {
        $numIps = DB::table('posts')->where('poster_id', $ipStats)->groupBy('poster_ip');
        $numIps = Hooks::fireDB('model.admin.model.admin.users.get_num_ip', $numIps);
        $numIps = $numIps->count('poster_ip');

        return $numIps;
    }

    public function getIpStats($ipStats, $startFrom)
    {
        $ipData = [];

        $result = DB::table('posts')->where('poster_id', $ipStats)
                    ->select('poster_ip')
                    ->selectExpr('MAX(posted)', 'last_used')
                    ->selectExpr('COUNT(id)', 'used_times')
                    ->select('poster_ip')
                    ->groupBy('poster_ip')
                    ->orderByDesc('last_used')
                    ->offset($startFrom)
                    ->limit(50);
        $result = Hooks::fireDB('model.admin.model.admin.users.get_ip_stats.query', $result);
        $result = $result->findMany();

        if ($result) {
            foreach ($result as $curIp) {
                $ipData[] = $curIp;
            }
        }

        $ipData = Hooks::fire('model.admin.model.users.get_ip_stats.ip_data', $ipData);
        return $ipData;
    }

    public function getNumUsersIp($ip)
    {
        $numUsers = DB::table('posts')->where('poster_ip', $ip)->distinct();
        $numUsers = Hooks::fireDB('model.admin.model.admin.users.get_num_users_ip.query', $numUsers);
        $numUsers = $numUsers->count('poster_id');

        return $numUsers;
    }

    public function getNumUsersSearch($conditions)
    {
        $conditions = Hooks::fire('model.admin.model.users.get_num_users_search.conditions', $conditions);

        $numUsers = DB::table('users')->tableAlias('u')
                        ->leftOuterJoin('groups', ['g.g_id', '=', 'u.group_id'], 'g')
                        ->whereRaw('u.id>1'.(!empty($conditions) ? ' AND '.implode(' AND ', $conditions) : ''));
        $numUsers = Hooks::fireDB('model.admin.model.admin.users.get_num_users_search.query', $numUsers);
        $numUsers = $numUsers->count('id');

        return $numUsers;
    }

    public function getInfoPoster($ip, $startFrom)
    {
        $ip = Hooks::fire('model.admin.model.users.get_info_poster.ip', $ip);

        $info = [];

        $selectInfoGetInfoPoster = ['poster_id', 'poster'];

        $result = DB::table('posts')->selectMany($selectInfoGetInfoPoster)
                        ->distinct()
                        ->where('poster_ip', $ip)
                        ->orderByAsc('poster')
                        ->offset($startFrom)
                        ->limit(50);
        $result = Hooks::fireDB('model.admin.model.admin.users.get_info_poster.select_info_get_info_poster', $result);
        $result = $result->findMany();

        $info['num_posts'] = count($result);

        if ($result) {
            $posterIds = [];
            foreach ($result as $curPoster) {
                $info['posters'][] = $curPoster;
                $posterIds[] = $curPoster['poster_id'];
            }

            $selectGetInfoPoster = ['u.id', 'u.username', 'u.email', 'u.title', 'u.num_posts', 'u.admin_note', 'g.g_id', 'g.g_user_title'];

            $result = DB::table('users')->tableAlias('u')
                ->selectMany($selectGetInfoPoster)
                ->innerJoin('groups', ['g.g_id', '=', 'u.group_id'], 'g')
                ->whereGt('u.id', 1)
                ->whereIn('u.id', $posterIds);
            $result = Hooks::fireDB('model.admin.model.admin.users.get_info_poster.select_get_info_poster', $result);
            $result = $result->findMany();

            foreach ($result as $curUser) {
                $info['user_data'][$curUser['id']] = $curUser;
            }
        }

        $info = Hooks::fire('model.admin.model.users.get_info_poster.info', $info);
        return $info;
    }

    public function moveUsers()
    {
        $move = [];

        if (Input::post('users')) {
            $move['user_ids'] = is_array(Input::post('users')) ? array_keys(Input::post('users')) : explode(',', Input::post('users'));
            $move['user_ids'] = array_map('intval', $move['user_ids']);

            // Delete invalid IDs
            $move['user_ids'] = array_diff($move['user_ids'], [0, 1]);
        } else {
            $move['user_ids'] = [];
        }

        $move['user_ids'] = Hooks::fire('model.admin.model.users.move_users.user_ids', $move['user_ids']);

        if (empty($move['user_ids'])) {
            throw new Error(__('No users selected'), 404);
        }

        // Are we trying to batch move any admins?
        $isAdmin = DB::table('users')->whereIn('id', $move['user_ids'])
                        ->where('group_id', ForumEnv::get('FEATHER_ADMIN'))
                        ->findOne();
        if ($isAdmin) {
            throw new Error(__('No move admins message'), 403);
        }

        // Fetch all user groups
        $selectUserGroups = ['g_id', 'g_title'];
        $whereNotIn = [ForumEnv::get('FEATHER_GUEST'), ForumEnv::get('FEATHER_ADMIN')];

        $result = DB::table('groups')->selectMany($selectUserGroups)
            ->whereNotIn('g_id', $whereNotIn)
            ->orderByAsc('g_title');
        $result = Hooks::fireDB('model.admin.model.admin.users.move_users.all_user_groups_query', $result);
        $result = $result->findMany();

        foreach ($result as $row) {
            $move['all_groups'][$row['g_id']] = $row['g_title'];
        }

        if (Input::post('move_users_comply')) {
            if (Input::post('new_group') && isset($move['all_groups'][Input::post('new_group')])) {
                $newGroup = Input::post('new_group');
            } else {
                throw new Error(__('Invalid group message'), 400);
            }
            $newGroup = Hooks::fire('model.admin.model.users.move_users.new_group', $newGroup);

            // Is the new group a moderator group?
            $newGroupMod = Perms::getGroupPermissions($newGroup, 'mod.is_mod');

            // Fetch user groups
            $userGroups = [];
            $selectFetchUserGroups = ['id', 'group_id'];
            $result = DB::table('users')->selectMany($selectFetchUserGroups)
                ->whereIn('id', $move['user_ids']);
            $result = Hooks::fireDB('model.admin.model.admin.users.move_users.user_groups_query', $result);
            $result = $result->findMany();

            foreach ($result as $curUser) {
                if (!isset($userGroups[$curUser['group_id']])) {
                    $userGroups[$curUser['group_id']] = [];
                }

                $userGroups[$curUser['group_id']][] = $curUser['id'];
            }

            // Are any users moderators?
            $groupIds = array_keys($userGroups);
            foreach ($groupIds as $groupId) {
                if (!Perms::getGroupPermissions($groupId, 'mod.is_mod')) {
                    unset($userGroups[$groupId]);
                }
            }

            $userGroups = Hooks::fire('model.admin.model.users.move_users.user_groups', $userGroups);

            if (!empty($userGroups) && $newGroup != ForumEnv::get('FEATHER_ADMIN') && !$newGroupMod) {
                // Fetch forum list and clean up their moderator list
                $selectMods = ['id', 'moderators'];
                $result = DB::table('forums')
                            ->selectMany($selectMods)
                            ->findMany();

                foreach ($result as $curForum) {
                    $curModerators = ($curForum['moderators'] != '') ? unserialize($curForum['moderators']) : [];

                    foreach ($userGroups as $groupUsers) {
                        $curModerators = array_diff($curModerators, $groupUsers);
                    }

                    if (!empty($curModerators)) {
                        DB::table('forums')->where('id', $curForum['id'])
                            ->findOne()
                            ->set('moderators', serialize($curModerators))
                            ->save();
                    } else {
                        DB::table('forums')->where('id', $curForum['id'])
                            ->findOne()
                            ->setExpr('moderators', 'NULL')
                            ->save();
                    }
                }
            }

            // Change user group
            DB::table('users')->whereIn('id', $move['user_ids'])
                                                      ->updateMany('group_id', $newGroup);

            return Router::redirect(Router::pathFor('adminUsers'), __('Users move redirect'));
        }

        $move = Hooks::fire('model.admin.model.users.move_users.move', $move);
        return $move;
    }

    public function deleteUsers()
    {
        if (Input::post('users')) {
            $userIds = is_array(Input::post('users')) ? array_keys(Input::post('users')) : explode(',', Input::post('users'));
            $userIds = array_map('intval', $userIds);

            // Delete invalid IDs
            $userIds = array_diff($userIds, [0, 1]);
        } else {
            $userIds = [];
        }

        $userIds = Hooks::fire('model.admin.model.users.delete_users.user_ids', $userIds);

        if (empty($userIds)) {
            throw new Error(__('No users selected'), 404);
        }

        // Are we trying to delete any admins?
        $isAdmin = DB::table('users')->whereIn('id', $userIds)
            ->where('group_id', ForumEnv::get('FEATHER_ADMIN'))
            ->findOne();
        if ($isAdmin) {
            throw new Error(__('No delete admins message'), 403);
        }

        if (Input::post('delete_users_comply')) {
            // Fetch user groups
            $userGroups = [];
            $result['select'] = ['id', 'group_id'];
            $result = DB::table('users')
                        ->selectMany($result['select'])
                        ->whereIn('id', $userIds);
            $result = Hooks::fireDB('model.admin.model.admin.users.delete_users.user_groups_query', $result);
            $result = $result->findMany();

            foreach ($result as $curUser) {
                if (!isset($userGroups[$curUser['group_id']])) {
                    $userGroups[$curUser['group_id']] = [];
                }

                $userGroups[$curUser['group_id']][] = $curUser['id'];
            }

            // Are any users moderators?
            $groupIds = array_keys($userGroups);
            foreach ($groupIds as $groupId) {
                if (!Perms::getGroupPermissions($groupId, 'mod.is_mod')) {
                    unset($userGroups[$groupId]);
                }
            }

            $userGroups = Hooks::fire('model.admin.model.users.delete_users.user_groups', $userGroups);

            // Fetch forum list and clean up their moderator list
            $selectMods = ['id', 'moderators'];
            $result = DB::table('forums')
                ->selectMany($selectMods)
                ->findMany();

            foreach ($result as $curForum) {
                $curModerators = ($curForum['moderators'] != '') ? unserialize($curForum['moderators']) : [];

                foreach ($userGroups as $groupUsers) {
                    $curModerators = array_diff($curModerators, $groupUsers);
                }

                if (!empty($curModerators)) {
                    DB::table('forums')->where('id', $curForum['id'])
                        ->findOne()
                        ->set('moderators', serialize($curModerators))
                        ->save();
                } else {
                    DB::table('forums')->where('id', $curForum['id'])
                        ->findOne()
                        ->setExpr('moderators', 'NULL')
                        ->save();
                }
            }


            // Delete any subscriptions
            DB::table('topic_subscriptions')
                    ->whereIn('user_id', $userIds)
                    ->deleteMany();
            DB::table('forum_subscriptions')
                    ->whereIn('user_id', $userIds)
                    ->deleteMany();

            // Remove them from the online list (if they happen to be logged in)
            DB::table('online')
                    ->whereIn('user_id', $userIds)
                    ->deleteMany();

            // Should we delete all posts made by these users?
            if (Input::post('delete_posts')) {
                @set_time_limit(0);

                // Find all posts made by this user
                $selectUserPosts = ['p.id', 'p.topic_id', 't.forum_id'];

                $result = DB::table('posts')
                    ->tableAlias('p')
                    ->selectMany($selectUserPosts)
                    ->innerJoin('topics', ['t.id', '=', 'p.topic_id'], 't')
                    ->innerJoin('forums', ['f.id', '=', 't.forum_id'], 'f')
                    ->where('p.poster_id', $userIds);
                $result = Hooks::fireDB('model.admin.model.admin.users.delete_users.user_posts_query', $result);
                $result = $result->findMany();

                if ($result) {
                    foreach ($result as $curPost) {
                        // Determine whether this post is the "topic post" or not
                        $result2 = DB::table('posts')
                                        ->where('topic_id', $curPost['topic_id'])
                                        ->orderBy('posted')
                                        ->findOneCol('id');

                        if ($result2 == $curPost['id']) {
                            \FeatherBB\Model\Topic::delete($curPost['topic_id']);
                        } else {
                            \FeatherBB\Model\Post::delete($curPost['id'], $curPost['topic_id']);
                        }

                        \FeatherBB\Model\Forum::update($curPost['forum_id']);
                    }
                }
            } else {
                // Set all their posts to guest
                DB::table('posts')
                        ->whereIn('poster_id', $userIds)
                        ->updateMany('poster_id', '1');
            }

            // Delete the users
            DB::table('users')
                    ->whereIn('id', $userIds)
                    ->deleteMany();


            // Delete user avatars
            $userProfile = new \FeatherBB\Model\Profile();
            foreach ($userIds as $userId) {
                $userProfile->deleteAvatar($userId);
            }

            // Regenerate the users info cache
            if (!CacheInterface::isCached('users_info')) {
                CacheInterface::store('users_info', Cache::getUsersInfo());
            }

            $stats = CacheInterface::retrieve('users_info');

            return Router::redirect(Router::pathFor('adminUsers'), __('Users delete redirect'));
        }

        return $userIds;
    }

    public function banUsers()
    {
        if (Input::post('users')) {
            $userIds = is_array(Input::post('users')) ? array_keys(Input::post('users')) : explode(',', Input::post('users'));
            $userIds = array_map('intval', $userIds);

            // Delete invalid IDs
            $userIds = array_diff($userIds, [0, 1]);
        } else {
            $userIds = [];
        }

        $userIds = Hooks::fire('model.admin.model.users.ban_users.user_ids', $userIds);

        if (empty($userIds)) {
            throw new Error(__('No users selected'), 404);
        }

        // Are we trying to ban any admins?
        $isAdmin = DB::table('users')->whereIn('id', $userIds)
            ->where('group_id', ForumEnv::get('FEATHER_ADMIN'))
            ->findOne();
        if ($isAdmin) {
            throw new Error(__('No ban admins message'), 403);
        }

        // Also, we cannot ban moderators
        $isMod = DB::table('users')->tableAlias('u')
            ->innerJoin('permissions', ['u.group_id', '=', 'p.group'], 'p')
            ->where('p.allow', 1)
            ->where('p.permission_name', 'mod.is_mod')
            ->whereIn('u.id', $userIds)
            ->findOne();
        if ($isMod) {
            throw new Error(__('No ban mods message'), 403);
        }

        if (Input::post('ban_users_comply')) {
            $banMessage = Utils::trim(Input::post('ban_message'));
            $banExpire = Utils::trim(Input::post('ban_expire'));
            $banTheIp = Input::post('ban_the_ip') ? intval(Input::post('ban_the_ip')) : 0;

            Hooks::fire('model.admin.model.users.ban_users.comply', $banMessage, $banExpire, $banTheIp);

            if ($banExpire != '' && $banExpire != 'Never') {
                $banExpire = strtotime($banExpire . ' GMT');

                if ($banExpire == -1 || !$banExpire) {
                    throw new Error(__('Invalid date message') . ' ' . __('Invalid date reasons'), 400);
                }

                $diff = (User::getPref('timezone') + User::getPref('dst')) * 3600;
                $banExpire -= $diff;

                if ($banExpire <= time()) {
                    throw new Error(__('Invalid date message') . ' ' . __('Invalid date reasons'), 400);
                }
            } else {
                $banExpire = 'NULL';
            }

            $banMessage = ($banMessage != '') ? $banMessage : 'NULL';

            // Fetch user information
            $userInfo = [];
            $selectFetchUserInformation = ['id', 'username', 'email', 'registration_ip'];
            $result = DB::table('users')->selectMany($selectFetchUserInformation)
                ->whereIn('id', $userIds);
            $result = Hooks::fireDB('model.admin.model.admin.users.ban_users.user_info_query', $result);
            $result = $result->findMany();

            foreach ($result as $curUser) {
                $userInfo[$curUser['id']] = ['username' => $curUser['username'], 'email' => $curUser['email'], 'ip' => $curUser['registration_ip']];
            }

            // Overwrite the registration IP with one from the last post (if it exists)
            if ($banTheIp != 0) {
                $result = DB::table('posts')->rawQuery('SELECT p.poster_id, p.poster_ip FROM ' . ForumEnv::get('DB_PREFIX') . 'posts AS p INNER JOIN (SELECT MAX(id) AS id FROM ' . ForumEnv::get('DB_PREFIX') . 'posts WHERE poster_id IN (' . implode(',', $userIds) . ') GROUP BY poster_id) AS i ON p.id=i.id')->findMany();
                foreach ($result as $curAddress) {
                    $userInfo[$curAddress['poster_id']]['ip'] = $curAddress['poster_ip'];
                }
            }

            $userInfo = Hooks::fire('model.admin.model.users.ban_users.user_info', $userInfo);

            // And insert the bans!
            foreach ($userIds as $userId) {
                $banUsername = $userInfo[$userId]['username'];
                $banEmail = $userInfo[$userId]['email'];
                $banIp = ($banTheIp != 0) ? $userInfo[$userId]['ip'] : 'NULL';

                $insertUpdateBan = [
                    'username' => $banUsername,
                    'ip' => $banIp,
                    'email' => $banEmail,
                    'message' => $banMessage,
                    'expire' => $banExpire,
                    'ban_creator' => User::get()->id,
                ];

                $insertUpdateBan = Hooks::fire('model.admin.model.users.ban_users.ban_data', $insertUpdateBan);

                if (Input::post('mode') == 'add') {
                    $insertUpdateBan['ban_creator'] = User::get()->id;

                    DB::table('bans')
                        ->create()
                        ->set($insertUpdateBan)
                        ->save();
                }

                // Regenerate the bans cache
                CacheInterface::store('bans', Cache::getBans());

                return Router::redirect(Router::pathFor('adminUsers'), __('Users banned redirect'));
            }
        }
        return $userIds;
    }

    public function getUserSearch()
    {
        $form = Input::query('form', [], false);
        $form = Hooks::fire('model.admin.model.users.get_user_search.form', $form);

        $search = [];

        // trim() all elements in $form
        $form = array_map('trim', $form);

        $postsGreater = Input::query('posts_greater') ? Utils::trim(Input::query('posts_greater')) : '';
        $postsLess = Input::query('posts_less') ? Utils::trim(Input::query('posts_less')) : '';
        $lastPostAfter = Input::query('last_post_after') ? Utils::trim(Input::query('last_post_after')) : '';
        $lastPostBefore = Input::query('last_post_before') ? Utils::trim(Input::query('last_post_before')) : '';
        $lastVisitAfter = Input::query('last_visit_after') ? Utils::trim(Input::query('last_visit_after')) : '';
        $lastVisitBefore = Input::query('last_visit_before') ? Utils::trim(Input::query('last_visit_before')) : '';
        $registeredAfter = Input::query('registered_after') ? Utils::trim(Input::query('registered_after')) : '';
        $registeredBefore = Input::query('registered_before') ? Utils::trim(Input::query('registered_before')) : '';
        $orderBy = $search['order_by'] = Input::query('order_by') && in_array(Input::query('order_by'), ['username', 'email', 'num_posts', 'last_post', 'last_visit', 'registered']) ? Input::query('order_by') : 'username';
        $direction = $search['direction'] = Input::query('direction') && Input::query('direction') == 'DESC' ? 'DESC' : 'ASC';
        $userGroup = Input::query('user_group') ? intval(Input::query('user_group')) : -1;

        $search['query_str'][] = 'order_by='.$orderBy;
        $search['query_str'][] = 'direction='.$direction;
        $search['query_str'][] = 'user_group='.$userGroup;

        if (preg_match('%[^0-9]%', $postsGreater.$postsLess)) {
            throw new Error(__('Non numeric message'), 400);
        }

        $search['conditions'] = [];

        // Try to convert date/time to timestamps
        if ($lastPostAfter != '') {
            $search['query_str'][] = 'last_post_after='.$lastPostAfter;

            $lastPostAfter = strtotime($lastPostAfter);
            if ($lastPostAfter === false || $lastPostAfter == -1) {
                throw new Error(__('Invalid date time message'), 400);
            }

            $search['conditions'][] = 'u.last_post>'.$lastPostAfter;
        }
        if ($lastPostBefore != '') {
            $search['query_str'][] = 'last_post_before='.$lastPostBefore;

            $lastPostBefore = strtotime($lastPostBefore);
            if ($lastPostBefore === false || $lastPostBefore == -1) {
                throw new Error(__('Invalid date time message'), 400);
            }

            $search['conditions'][] = 'u.last_post<'.$lastPostBefore;
        }
        if ($lastVisitAfter != '') {
            $search['query_str'][] = 'last_visit_after='.$lastVisitAfter;

            $lastVisitAfter = strtotime($lastVisitAfter);
            if ($lastVisitAfter === false || $lastVisitAfter == -1) {
                throw new Error(__('Invalid date time message'), 400);
            }

            $search['conditions'][] = 'u.last_visit>'.$lastVisitAfter;
        }
        if ($lastVisitBefore != '') {
            $search['query_str'][] = 'last_visit_before='.$lastVisitBefore;

            $lastVisitBefore = strtotime($lastVisitBefore);
            if ($lastVisitBefore === false || $lastVisitBefore == -1) {
                throw new Error(__('Invalid date time message'), 400);
            }

            $search['conditions'][] = 'u.last_visit<'.$lastVisitBefore;
        }
        if ($registeredAfter != '') {
            $search['query_str'][] = 'registered_after='.$registeredAfter;

            $registeredAfter = strtotime($registeredAfter);
            if ($registeredAfter === false || $registeredAfter == -1) {
                throw new Error(__('Invalid date time message'), 400);
            }

            $search['conditions'][] = 'u.registered>'.$registeredAfter;
        }
        if ($registeredBefore != '') {
            $search['query_str'][] = 'registered_before='.$registeredBefore;

            $registeredBefore = strtotime($registeredBefore);
            if ($registeredBefore === false || $registeredBefore == -1) {
                throw new Error(__('Invalid date time message'), 400);
            }

            $search['conditions'][] = 'u.registered<'.$registeredBefore;
        }

        $likeCommand = (ForumEnv::get('DB_TYPE') == 'pgsql') ? 'ILIKE' : 'LIKE';
        foreach ($form as $key => $input) {
            if ($input != '' && in_array($key, ['username', 'email', 'title', 'realname', 'url', 'location', 'signature', 'admin_note'])) {
                $search['conditions'][] = 'u.'.str_replace("'", "''", $key).' '.$likeCommand.' \''.str_replace("'", "''", str_replace('*', '%', $input)).'\'';
                $search['query_str'][] = 'form%5B'.$key.'%5D='.urlencode($input);
            }
        }

        if ($postsGreater != '') {
            $search['query_str'][] = 'posts_greater='.$postsGreater;
            $search['conditions'][] = 'u.num_posts>'.$postsGreater;
        }
        if ($postsLess != '') {
            $search['query_str'][] = 'posts_less='.$postsLess;
            $search['conditions'][] = 'u.num_posts<'.$postsLess;
        }

        if ($userGroup > -1) {
            $search['conditions'][] = 'u.group_id='.$userGroup;
        }

        $search = Hooks::fire('model.admin.model.users.get_user_search.search', $search);
        return $search;
    }

    public function printUsers($conditions, $orderBy, $direction, $startFrom)
    {
        $userData = [];

        $selectPrintUsers = ['u.id', 'u.username', 'u.email', 'u.title', 'u.num_posts', 'u.admin_note', 'g.g_id', 'g.g_user_title'];
        $result = DB::table('users')->tableAlias('u')
            ->selectMany($selectPrintUsers)
            ->leftOuterJoin('groups', ['g.g_id', '=', 'u.group_id'], 'g')
            ->whereRaw('u.id>1'.(!empty($conditions) ? ' AND '.implode(' AND ', $conditions) : ''))
            ->offset($startFrom)
            ->limit(50)
            ->orderBy($orderBy, $direction);
        $result = Hooks::fireDB('model.admin.model.admin.users.print_users.query', $result);
        $result = $result->findMany();

        if ($result) {
            foreach ($result as $curUser) {
                $curUser['user_title'] = Utils::getTitle($curUser);

                // This script is a special case in that we want to display "Not verified" for non-verified users
                if (($curUser['g_id'] == '' || $curUser['g_id'] == ForumEnv::get('FEATHER_UNVERIFIED')) && $curUser['user_title'] != __('Banned')) {
                    $curUser['user_title'] = '<span class="warntext">'.__('Not verified').'</span>';
                }

                $userData[] = $curUser;
            }
        }

        $userData = Hooks::fire('model.admin.model.users.print_users.user_data', $userData);
        return $userData;
    }

    public function getGroupList()
    {
        $output = '';

        $selectGetGroupList = ['g_id', 'g_title'];
        $result = DB::table('groups')->selectMany($selectGetGroupList)
                        ->whereNotEqual('g_id', ForumEnv::get('FEATHER_GUEST'))
                        ->orderBy('g_title');

        foreach ($result as $curGroup) {
            $output .= "\t\t\t\t\t\t\t\t\t\t\t".'<option value="'.$curGroup['g_id'].'">'.Utils::escape($curGroup['g_title']).'</option>'."\n";
        }

        $output = Hooks::fire('model.admin.model.users.get_group_list.output', $output);
        return $output;
    }
}
