<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace model;

use DB;

class userlist
{

    public function __construct()
    {
        $this->feather = \Slim\Slim::getInstance();
        $this->start = $this->feather->start;
        $this->config = $this->feather->config;
        $this->user = $this->feather->user;
        $this->request = $this->feather->request;
    }
 
    // Counts the numeber of user for a specific query
    public function fetch_user_count($username, $show_group)
    {
        // Fetch user count
        $num_users = DB::for_table('users')->table_alias('u')
                        ->where_gt('u.id', 1)
                        ->where_not_equal('u.group_id', FEATHER_UNVERIFIED);

        if ($username != '') {
            $num_users = $num_users->where_like('u.username', str_replace('*', '%', $username));
        }
        if ($show_group > -1) {
            $num_users = $num_users->where('u.group_id', $show_group);
        }

        $num_users = $num_users->count('id');

        return $num_users;
    }

    // Generates the dropdown menu containing groups
    public function generate_dropdown_menu($show_group)
    {
        $dropdown_menu = '';

        $select_dropdown_menu = array('g_id', 'g_title');

        $result = DB::for_table('groups')->select_many($select_dropdown_menu)
                        ->where_not_equal('g_id', FEATHER_GUEST)
                        ->order_by('g_id')
                        ->find_many();

        foreach($result as $cur_group) {
            if ($cur_group['g_id'] == $show_group) {
                $dropdown_menu .= "\t\t\t\t\t\t\t".'<option value="'.$cur_group['g_id'].'" selected="selected">'.feather_escape($cur_group['g_title']).'</option>'."\n";
            } else {
                $dropdown_menu .= "\t\t\t\t\t\t\t".'<option value="'.$cur_group['g_id'].'">'.feather_escape($cur_group['g_title']).'</option>'."\n";
            }
        }
        
        return $dropdown_menu;
    }

    // Prints the users
    public function print_users($username, $start_from, $sort_by, $sort_dir, $show_group)
    {
        $userlist_data = array();

        // Retrieve a list of user IDs, LIMIT is (really) expensive so we only fetch the IDs here then later fetch the remaining data
        $result = DB::for_table('users')->select('u.id')
                    ->table_alias('u')
                    ->where_gt('u.id', 1)
                    ->where_not_equal('u.group_id', FEATHER_UNVERIFIED);

        if ($username != '') {
            $result = $result->where_like('u.username', str_replace('*', '%', $username));
        }
        if ($show_group > -1) {
            $result = $result->where('u.group_id', $show_group);
        }

        $result = $result->order_by($sort_by, $sort_dir)
                         ->order_by_asc('u.id')
                         ->limit(50)
                         ->offset($start_from)
                         ->find_many();

        if ($result) {
            $user_ids = array();
            foreach ($result as $cur_user_id) {
                $user_ids[] = $cur_user_id['id'];
            }

            // Grab the users
            $select_users = array('u.id', 'u.username', 'u.title', 'u.num_posts', 'u.registered', 'g.g_id', 'g.g_user_title');

            $result = DB::for_table('users')->table_alias('u')
                          ->select_many($select_users)
                          ->left_outer_join('groups' ,array('g.g_id', '=', 'u.group_id'), 'g')
                          ->where_in('u.id', $user_ids)
                          ->order_by($sort_by, $sort_dir)
                          ->order_by_asc('u.id')
                          ->find_many();

            foreach($result as $user_data) {
                $userlist_data[] = $user_data;
            }
        }

        return $userlist_data;
    }
}