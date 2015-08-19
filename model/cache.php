<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace model;
use DB;

class cache
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

}
