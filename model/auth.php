<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace model;
use DB;

class auth
{
    public function __construct()
    {
        $this->feather = \Slim\Slim::getInstance();
    }

    public static function load_user($user_id)
    {
        // Get Slim current session
        $feather = \Slim\Slim::getInstance();

        $user_id = (int) $user_id;
        $result['select'] = array('u.*', 'g.*', 'o.logged', 'o.idle');
        $result['where'] = array('u.id' => $user_id);
        $result['join'] = ($user_id == 1) ? $feather->request->getIp() : 'u.id';
        $escape = ($user_id == 1) ? true : false;

        $result = DB::for_table('users')
                    ->table_alias('u')
                    ->select_many($result['select'])
                    ->inner_join('groups', array('u.group_id', '=', 'g.g_id'), 'g')
                    ->left_outer_join('online', array('o.user_id', '=', $result['join']), 'o', $escape)
                    ->where($result['where']);
        $result = $result->find_result_set();

        foreach ($result as $user) {
            return $user;
        }
    }

    public static function delete_online_by_ip($ip)
    {
        // Get Slim current session
        $feather = \Slim\Slim::getInstance();

        $delete_online = DB::for_table('online')->where('ident', $ip);
        $delete_online = $feather->hooks->fireDB('delete_online_login', $delete_online);
        return $delete_online->delete_many();
    }

    public static function delete_online_by_id($user_id)
    {
        // Get Slim current session
        $feather = \Slim\Slim::getInstance();

        // Remove user from "users online" list
        $delete_online = DB::for_table('online')->where('user_id', $user_id);
        $delete_online = $feather->hooks->fireDB('delete_online_logout', $delete_online);
        return $delete_online->delete_many();
    }

    public static function get_user_from_name($username)
    {
        // Get Slim current session
        $feather = \Slim\Slim::getInstance();

        $user = DB::for_table('users')->where('username', $username);
        $user = $feather->hooks->fireDB('find_user_login', $user);
        return $user->find_one();
    }

    public static function update_group($user_id, $group_id)
    {
        $update_usergroup = DB::for_table('users')->where('id', $user_id)
            ->find_one()
            ->set('group_id', $group_id);
        $update_usergroup = $this->feather->hooks->fireDB('update_usergroup_login', $update_usergroup);
        return $update_usergroup->save();
    }

    public static function feather_setcookie($user_id, $password, $expires)
    {
        // Get Slim current session
        $feather = \Slim\Slim::getInstance();
        $cookie_data = array('user_id' => $user_id,
            'password_hash' => hash_hmac('sha1', $password, $feather->forum_settings['cookie_seed'].'_password_hash'),
            'expires' => $expires,
            'checksum' => hash_hmac('sha1', $user_id.$expires, $feather->forum_settings['cookie_seed'].'_checksum'));
        $feather->setCookie($feather->forum_settings['cookie_name'], json_encode($cookie_data), $expires);
    }
}
