<?php

/**
 * Copyright (C) 2015-2017 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace FeatherBB\Model;

use FeatherBB\Core\Database as DB;

class Cache
{
    public static function getConfig()
    {
        $result = DB::table('config')
                    ->findArray();
        $config = [];
        foreach ($result as $item) {
            $config[$item['conf_name']] = $item['conf_value'];
        }
        return $config;
    }

    public static function getPreferences()
    {
        $result = DB::table('preferences')
                    ->where('default', 1)
                    ->findArray();
        $preferences = [];
        foreach ($result as $item) {
            $preferences[$item['preference_name']] = $item['preference_value'];
        }
        return $preferences;
    }

    public static function getBans()
    {
        return DB::table('bans')
                ->findArray();
    }

    public static function getCensoring($selectCensoring = 'search_for')
    {
        $result = DB::table('censoring')
                    ->selectMany($selectCensoring)
                    ->findArray();
        $output = [];

        foreach ($result as $item) {
            $output[] = ($selectCensoring == 'search_for') ? '%(?<=[^\p{L}\p{N}])('.str_replace('\*', '[\p{L}\p{N}]*?', preg_quote($item['search_for'], '%')).')(?=[^\p{L}\p{N}])%iu' : $item['replace_with'];
        }
        return $output;
    }

    public static function getUsersInfo()
    {
        $stats = [];
        $selectGetUsersInfo = ['id', 'username'];
        $stats['total_users'] = DB::table('users')
                                    ->whereNotEqual('group_id', ForumEnv::get('FEATHER_UNVERIFIED'))
                                    ->whereNotEqual('id', 1)
                                    ->count();
        $stats['last_user'] = DB::table('users')->selectMany($selectGetUsersInfo)
                            ->whereNotEqual('group_id', ForumEnv::get('FEATHER_UNVERIFIED'))
                            ->orderByDesc('registered')
                            ->limit(1)
                            ->findArray()[0];
        return $stats;
    }

    public static function getAdminIds()
    {
        return DB::table('users')
                ->select('id')
                ->where('group_id', ForumEnv::get('FEATHER_ADMIN'))
                ->findArray();
    }

    public static function quickjump()
    {
        $readPerms = DB::table('permissions')
            ->select('group', 'g_id')
            ->whereAnyIs([
                ['permission_name' => 'board.read'],
                ['permission_name' => '*']
            ])
            ->where('allow', 1)
            ->findArray();

        $output = [];
        foreach ($readPerms as $item) {
            $selectQuickjump = ['cid' => 'c.id', 'c.cat_name', 'fid' => 'f.id', 'f.forum_name', 'f.redirect_url'];
            $whereQuickjump = [
                ['fp.read_forum' => 'IS NULL'],
                ['fp.read_forum' => '1']
            ];
            $orderByQuickjump = ['c.disp_position', 'c.id', 'f.disp_position'];

            $result = DB::table('categories')
                        ->tableAlias('c')
                        ->selectMany($selectQuickjump)
                        ->innerJoin('forums', ['c.id', '=', 'f.cat_id'], 'f')
                        ->leftOuterJoin('forum_perms', 'fp.forum_id=f.id AND fp.group_id='.$item['g_id'], 'fp')
                        ->whereAnyIs($whereQuickjump)
                        ->whereNull('f.redirect_url')
                        ->orderByMany($orderByQuickjump)
                        ->findMany();

            $forumData = [];
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
            $output[(int) $item['g_id']] = $forumData;
        }
        return $output;
    }

    public static function getStopwords($langPath = null)
    {
        if (!$langPath) {
            $langPath = ForumEnv::get('FEATHER_ROOT').'featherbb/lang';
        }
        $files = new \DirectoryIterator($langPath);
        $stopwords = [];
        foreach ($files as $file) {
            if (!$file->isDot() && $file->getBasename() != '.DS_Store' && $file->isDir() && file_exists($file->getPathName().'/stopwords.txt')) {
                $stopwords = array_merge($stopwords, file($file->getPathName().'/stopwords.txt'));
            }
        }
        return array_map('trim', $stopwords);
    }

    public static function getPermissions()
    {
        // Initial empty array
        $result = [];

        // First, get default group permissions
        $groupsPerms = DB::table('permissions')->whereNull('user')->orderByDesc('group')->findArray();
        foreach ($groupsPerms as $perm) {
            if ((bool) $perm['allow']) {
                $result[$perm['group']][0][$perm['permission_name']] = true;
            }
        }

        // Then get optionnal user permissions to override their group defaults
        // TODO: Add a page in profile Administration section to display custom permissions
        $usersPerms = DB::table('permissions')
            ->tableAlias('p')
            ->selectMany('p.permission_name', 'p.allow', 'p.deny', 'p.user', 'u.group_id')
            ->innerJoin('users', ['u.id', '=', 'p.user'], 'u')
            ->whereNotNull('p.user')
            ->findArray();
        foreach ($usersPerms as $perm) {
            $result[$perm['group_id']][$perm['user']][$perm['permission_name']] = (bool) $perm['allow'] || !(bool) $perm['deny'];
        }

        return $result;
    }

    public static function getGroupPreferences()
    {
        $groupsPreferences = [];

        $groups = DB::table('groups')->select('g_id')->findArray();

        foreach ($groups as $group) {
            $result = DB::table('preferences')
                ->tableAlias('p')
                ->whereAnyIs([
                    ['p.group' => $group['g_id']],
                    ['p.default' => 1],
                ])
                ->orderByDesc('p.default')
                ->findArray();

            $groupsPreferences[$group['g_id']] = [];
            foreach ($result as $pref) {
                $groupsPreferences[$group['g_id']][(string) $pref['preference_name']] = $pref['preference_value'];
            }
        }

        return (array) $groupsPreferences;
    }
}
