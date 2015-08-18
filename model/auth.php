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

    public function load_user($user_id)
    {
        $user_id = (int) $user_id;
        $select_load_user = array('u.*', 'g.*', 'o.logged', 'o.idle');
        $where_load_user = array('u.id' => $user_id);
        $left_outer_join_load_user = ($user_id == 1) ? $this->feather->request->getIp() : 'u.id';
        $escape = ($user_id == 1) ? true : false;

        $result = DB::for_table('users')
            ->table_alias('u')
            ->select_many($select_load_user)
            ->inner_join('groups', array('u.group_id', '=', 'g.g_id'), 'g')
            ->left_outer_join('online', array('o.user_id', '=', $left_outer_join_load_user), 'o', $escape)
            ->where($where_load_user)
            ->find_result_set();

        foreach ($result as $user) {
            return $user;
        }
    }
}
