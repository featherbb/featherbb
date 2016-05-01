<?php

/**
 * Copyright (C) 2015-2016 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace FeatherBB\Model;

use FeatherBB\Core\Database as DB;

class Cache
{
    public static function get_config()
    {
        $result = DB::for_table('config')
                    ->find_array();
        $config = array();
        foreach ($result as $item) {
            $config[$item['conf_name']] = $item['conf_value'];
        }
        return $config;
    }

    public static function get_preferences()
    {
        $result = DB::for_table('preferences')
                    ->where('default', 1)
                    ->find_array();
        $preferences = array();
        foreach ($result as $item) {
            $preferences[$item['preference_name']] = $item['preference_value'];
        }
        return $preferences;
    }

    public static function get_bans()
    {
        return DB::for_table('bans')
                ->find_array();
    }

    public static function get_censoring($select_censoring = 'search_for')
    {
        $result = DB::for_table('censoring')
                    ->select_many($select_censoring)
                    ->find_array();
        $output = array();

        foreach ($result as $item) {
            $output[] = ($select_censoring == 'search_for') ? '%(?<=[^\p{L}\p{N}])('.str_replace('\*', '[\p{L}\p{N}]*?', preg_quote($item['search_for'], '%')).')(?=[^\p{L}\p{N}])%iu' : $item['replace_with'];
        }
        return $output;
    }

    public static function get_users_info()
    {
        $stats = array();
        $select_get_users_info = array('id', 'username');
        $stats['total_users'] = DB::for_table('users')
                                    ->where_not_equal('group_id', ForumEnv::get('FEATHER_UNVERIFIED'))
                                    ->where_not_equal('id', 1)
                                    ->count();
        $stats['last_user'] = DB::for_table('users')->select_many($select_get_users_info)
                            ->where_not_equal('group_id', ForumEnv::get('FEATHER_UNVERIFIED'))
                            ->order_by_desc('registered')
                            ->limit(1)
                            ->find_array()[0];
        return $stats;
    }

    public static function get_admin_ids()
    {
        return DB::for_table('users')
                ->select('id')
                ->where('group_id', ForumEnv::get('FEATHER_ADMIN'))
                ->find_array();
    }

    public static function get_quickjump()
    {
        $read_perms = DB::for_table('permissions')
            ->select('group', 'g_id')
            ->where_any_is(array(
                ['permission_name' => 'board.read'],
                ['permission_name' => '*']
            ))
            ->where('allow', 1)
            ->find_array();

        $output = array();
        foreach ($read_perms as $item) {
            $select_quickjump = array('cid' => 'c.id', 'c.cat_name', 'fid' => 'f.id', 'f.forum_name', 'f.redirect_url');
            $where_quickjump = array(
                array('fp.read_forum' => 'IS NULL'),
                array('fp.read_forum' => '1')
            );
            $order_by_quickjump = array('c.disp_position', 'c.id', 'f.disp_position');

            $result = DB::for_table('categories')
                        ->table_alias('c')
                        ->select_many($select_quickjump)
                        ->inner_join('forums', array('c.id', '=', 'f.cat_id'), 'f')
                        ->left_outer_join('forum_perms', array('fp.forum_id', '=', 'f.id'), 'fp')
                        ->left_outer_join('forum_perms', array('fp.group_id', '=', $item['g_id']), null, true)
                        ->where_any_is($where_quickjump)
                        ->where_null('f.redirect_url')
                        ->order_by_many($order_by_quickjump)
                        ->find_many();

            $forum_data = array();
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
            $output[(int) $item['g_id']] = $forum_data;
        }
        return $output;
    }

    public static function get_stopwords($lang_path = null)
    {
        if (!$lang_path) {
            $lang_path = ForumEnv::get('FEATHER_ROOT').'featherbb/lang';
        }
        $files = new \DirectoryIterator($lang_path);
        $stopwords = array();
        foreach($files as $file) {
            if(!$file->isDot() && $file->getBasename() != '.DS_Store' && $file->isDir() && file_exists($file->getPathName().'/stopwords.txt')) {
                $stopwords = array_merge($stopwords, file($file->getPathName().'/stopwords.txt'));
            }
        }
        return array_map('trim', $stopwords);
    }

    public static function get_permissions()
    {
        // Initial empty array
        $result = array();

        // First, get default group permissions
        $groups_perms = DB::for_table('permissions')->where_null('user')->order_by_desc('group')->find_array();
        foreach ($groups_perms as $perm) {
            if ((bool) $perm['allow']) {
                $result[$perm['group']][0][$perm['permission_name']] = true;
            }
        }

        // Then get optionnal user permissions to override their group defaults
        // TODO: Add a page in profile Administration section to display custom permissions
        $users_perms = DB::for_table('permissions')
            ->table_alias('p')
            ->select_many('p.permission_name', 'p.allow', 'p.deny', 'p.user', 'u.group_id')
            ->inner_join('users', array('u.id', '=', 'p.user'), 'u')
            ->where_not_null('p.user')
            ->find_array();
        foreach ($users_perms as $perm) {
            $result[$perm['group_id']][$perm['user']][$perm['permission_name']] = (bool) $perm['allow'] || !(bool) $perm['deny'];
        }

        return $result;
    }

    public static function get_group_preferences()
    {
        $groups_preferences = array();

        $groups = DB::for_table('groups')->select('g_id')->find_array();

        foreach ($groups as $group) {
            $result = DB::for_table('preferences')
                ->table_alias('p')
                ->where_any_is(array(
                    array('p.group' => $group['g_id']),
                    array('p.default' => 1),
                ))
                ->order_by_desc('p.default')
                ->find_array();

            $groups_preferences[$group['g_id']] = array();
            foreach ($result as $pref) {
                $groups_preferences[$group['g_id']][(string) $pref['preference_name']] = $pref['preference_value'];
            }
        }

        return (array) $groups_preferences;
    }
}
