<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace FeatherBB\Model;

use DB;
use FeatherBB\Core\Utils;

class Userlist
{

    public function __construct()
    {
        $this->feather = \Slim\Slim::getInstance();
        $this->start = $this->feather->start;
        $this->config = $this->feather->config;
        $this->user = $this->feather->user;
        $this->request = $this->feather->request;
        $this->hook = $this->feather->hooks;
    }

    // Counts the numeber of user for a specific query
    public function fetch_user_count($username, $show_group)
    {
        // Fetch user count
        $num_users = DB::for_table('users')->table_alias('u')
                        ->where_gt('u.id', 1)
                        ->where_not_equal('u.group_id', $this->feather->forum_env['FEATHER_UNVERIFIED']);

        if ($username != '') {
            $num_users = $num_users->where_like('u.username', str_replace('*', '%', $username));
        }
        if ($show_group > -1) {
            $num_users = $num_users->where('u.group_id', $show_group);
        }

        $num_users = $num_users->count('id');

        $num_users = $this->hook->fire('model.fetch_user_count', $num_users);

        return $num_users;
    }

    // Generates the dropdown menu containing groups
    public function generate_dropdown_menu($show_group)
    {
        $show_group = $this->hook->fire('model.generate_dropdown_menu_start', $show_group);

        $dropdown_menu = '';

        $result['select'] = array('g_id', 'g_title');

        $result = DB::for_table('groups')
                        ->select_many($result['select'])
                        ->where_not_equal('g_id', $this->feather->forum_env['FEATHER_GUEST'])
                        ->order_by('g_id');
        $result = $this->hook->fireDB('generate_dropdown_menu_query', $result);
        $result = $result->find_many();

        foreach($result as $cur_group) {
            if ($cur_group['g_id'] == $show_group) {
                $dropdown_menu .= "\t\t\t\t\t\t\t".'<option value="'.$cur_group['g_id'].'" selected="selected">'.Utils::escape($cur_group['g_title']).'</option>'."\n";
            } else {
                $dropdown_menu .= "\t\t\t\t\t\t\t".'<option value="'.$cur_group['g_id'].'">'.Utils::escape($cur_group['g_title']).'</option>'."\n";
            }
        }

        $dropdown_menu = $this->hook->fire('model.generate_dropdown_menu', $dropdown_menu);

        return $dropdown_menu;
    }

    // Prints the users
    public function print_users($username, $start_from, $sort_by, $sort_dir, $show_group)
    {
        $userlist_data = array();

        $username = $this->hook->fire('model.print_users_start', $username, $start_from, $sort_by, $sort_dir, $show_group);

        // Retrieve a list of user IDs, LIMIT is (really) expensive so we only fetch the IDs here then later fetch the remaining data
        $result = DB::for_table('users')
                    ->select('u.id')
                    ->table_alias('u')
                    ->where_gt('u.id', 1)
                    ->where_not_equal('u.group_id', $this->feather->forum_env['FEATHER_UNVERIFIED']);

        if ($username != '') {
            $result = $result->where_like('u.username', str_replace('*', '%', $username));
        }
        if ($show_group > -1) {
            $result = $result->where('u.group_id', $show_group);
        }

        $result = $result->order_by($sort_by, $sort_dir)
                         ->order_by_asc('u.id')
                         ->limit(50)
                         ->offset($start_from);

        $result = $this->hook->fireDB('print_users_query', $result);
        $result = $result->find_many();

        if ($result) {
            $user_ids = array();
            foreach ($result as $cur_user_id) {
                $user_ids[] = $cur_user_id['id'];
            }

            // Grab the users
            $result['select'] = array('u.id', 'u.username', 'u.title', 'u.num_posts', 'u.registered', 'g.g_id', 'g.g_user_title');

            $result = DB::for_table('users')
                          ->table_alias('u')
                          ->select_many($result['select'])
                          ->left_outer_join('groups' ,array('g.g_id', '=', 'u.group_id'), 'g')
                          ->where_in('u.id', $user_ids)
                          ->order_by($sort_by, $sort_dir)
                          ->order_by_asc('u.id');
            $result = $this->hook->fireDB('print_users_grab_query', $result);
            $result = $result->find_many();

            foreach($result as $user_data) {
                $userlist_data[] = $user_data;
            }
        }

        $userlist_data = $this->hook->fire('model.print_users', $userlist_data);

        return $userlist_data;
    }
}
