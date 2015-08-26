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
        $this->hook = $this->feather->hooks;
    }

    public function load_user($user_id)
    {
        $user_id = (int) $user_id;
        $result['select'] = array('u.*', 'g.*', 'o.logged', 'o.idle');
        $result['where'] = array('u.id' => $user_id);
        $result['join'] = ($user_id == 1) ? $this->feather->request->getIp() : 'u.id';
        $escape = ($user_id == 1) ? true : false;

        $result = DB::for_table('users')
                    ->table_alias('u')
                    ->select_many($result['select'])
                    ->inner_join('groups', array('u.group_id', '=', 'g.g_id'), 'g')
                    ->left_outer_join('online', array('o.user_id', '=', $result['join']), 'o', $escape)
                    ->where($result['where']);
        //$result = $this->hook->fireDB('load_user_query', $result);
        $result = $result->find_result_set();

        foreach ($result as $user) {
            return $user;
        }
    }

    //
    // Wrapper for Slim setCookie method
    //
    public function feather_setcookie($user_id, $password, $expires)
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
