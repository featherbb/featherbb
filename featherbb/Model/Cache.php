<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace FeatherBB\Model;

use FeatherBB\Core\Utils;
use FeatherBB\Core\Url;
use DB;

class Cache
{
    public function __construct()
    {
        $this->feather = \Slim\Slim::getInstance();
    }

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
                                    ->where_not_equal('group_id', FEATHER_UNVERIFIED)
                                    ->where_not_equal('id', 1)
                                    ->count();
        $stats['last_user'] = DB::for_table('users')->select_many($select_get_users_info)
                            ->where_not_equal('group_id', FEATHER_UNVERIFIED)
                            ->order_by_desc('registered')
                            ->limit(1)
                            ->find_array()[0];
        return $stats;
    }

    public static function get_admin_ids()
    {
        return DB::for_table('users')
                ->select('id')
                ->where('group_id', FEATHER_ADMIN)
                ->find_array();
    }

    public static function get_quickjump()
    {
        $select_quickjump = array('g_id', 'g_read_board');
        $read_perms = DB::for_table('groups')
                        ->select_many($select_quickjump)
                        ->where('g_read_board', 1)
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

    public static function get_stopwords($lang_path)
    {
        $files = new \DirectoryIterator($lang_path);
        $stopwords = array();
        foreach($files as $file) {
            if(!$file->isDot() && $file->getBasename() != '.DS_Store' && $file->isDir() && file_exists($file->getPathName().'/stopwords.txt')) {
                $stopwords = array_merge($stopwords, file($file->getPathName().'/stopwords.txt'));
            }
        }
        return array_map('trim', $stopwords);
    }
}
