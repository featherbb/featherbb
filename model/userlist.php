<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace model;

class userlist
{

    public function __construct()
    {
        $this->feather = \Slim\Slim::getInstance();
        $this->db = $this->feather->db;
        $this->start = $this->feather->start;
        $this->config = $this->feather->config;
        $this->user = $this->feather->user;
        $this->request = $this->feather->request;
    }
 
    // Counts the numeber of user for a specific query
    public function fetch_user_count($username, $show_group)
    {
        global $db_type;

        // Create any SQL for the WHERE clause
        $where_sql = array();
        $like_command = ($db_type == 'pgsql') ? 'ILIKE' : 'LIKE';

        if ($username != '') {
            $where_sql[] = 'u.username '.$like_command.' \''.$this->db->escape(str_replace('*', '%', $username)).'\'';
        }
        if ($show_group > -1) {
            $where_sql[] = 'u.group_id='.$show_group;
        }

        // Fetch user count
        $result = $this->db->query('SELECT COUNT(id) FROM '.$this->db->prefix.'users AS u WHERE u.id>1 AND u.group_id!='.FEATHER_UNVERIFIED.(!empty($where_sql) ? ' AND '.implode(' AND ', $where_sql) : '')) or error('Unable to fetch user list count', __FILE__, __LINE__, $this->db->error());
        $num_users = $this->db->result($result);

        return $num_users;
    }

    // Generates the dropdown menu containing groups
    public function generate_dropdown_menu($show_group)
    {
        $dropdown_menu = '';

        $result = $this->db->query('SELECT g_id, g_title FROM '.$this->db->prefix.'groups WHERE g_id!='.FEATHER_GUEST.' ORDER BY g_id') or error('Unable to fetch user group list', __FILE__, __LINE__, $this->db->error());

        while ($cur_group = $this->db->fetch_assoc($result)) {
            if ($cur_group['g_id'] == $show_group) {
                $dropdown_menu .= "\t\t\t\t\t\t\t".'<option value="'.$cur_group['g_id'].'" selected="selected">'.feather_htmlspecialchars($cur_group['g_title']).'</option>'."\n";
            } else {
                $dropdown_menu .= "\t\t\t\t\t\t\t".'<option value="'.$cur_group['g_id'].'">'.feather_htmlspecialchars($cur_group['g_title']).'</option>'."\n";
            }
        }
        
        return $dropdown_menu;
    }

    // Prints the users
    public function print_users($username, $start_from, $sort_by, $sort_dir, $show_group)
    {
        global $db_type;

        $userlist_data = array();

        // Create any SQL for the WHERE clause
        $where_sql = array();
        $like_command = ($db_type == 'pgsql') ? 'ILIKE' : 'LIKE';

        if ($username != '') {
            $where_sql[] = 'u.username '.$like_command.' \''.$this->db->escape(str_replace('*', '%', $username)).'\'';
        }
        if ($show_group > -1) {
            $where_sql[] = 'u.group_id='.$show_group;
        }

        // Retrieve a list of user IDs, LIMIT is (really) expensive so we only fetch the IDs here then later fetch the remaining data
        $result = $this->db->query('SELECT u.id FROM '.$this->db->prefix.'users AS u WHERE u.id>1 AND u.group_id!='.FEATHER_UNVERIFIED.(!empty($where_sql) ? ' AND '.implode(' AND ', $where_sql) : '').' ORDER BY '.$sort_by.' '.$sort_dir.', u.id ASC LIMIT '.$start_from.', 50') or error('Unable to fetch user IDs', __FILE__, __LINE__, $this->db->error());

        if ($this->db->num_rows($result)) {
            $user_ids = array();
            for ($i = 0;$cur_user_id = $this->db->result($result, $i);$i++) {
                $user_ids[] = $cur_user_id;
            }

            // Grab the users
            $result = $this->db->query('SELECT u.id, u.username, u.title, u.num_posts, u.registered, g.g_id, g.g_user_title FROM '.$this->db->prefix.'users AS u LEFT JOIN '.$this->db->prefix.'groups AS g ON g.g_id=u.group_id WHERE u.id IN('.implode(',', $user_ids).') ORDER BY '.$sort_by.' '.$sort_dir.', u.id ASC') or error('Unable to fetch user list', __FILE__, __LINE__, $this->db->error());

            while ($user_data = $this->db->fetch_assoc($result)) {
                $userlist_data[] = $user_data;
            }
        }

        return $userlist_data;
    }
}