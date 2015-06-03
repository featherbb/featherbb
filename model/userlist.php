<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

// Counts the numeber of user for a specific query
function fetch_user_count($username)
{
	global $db;
	
	// Create any SQL for the WHERE clause
	$where_sql = array();
	$like_command = ($db_type == 'pgsql') ? 'ILIKE' : 'LIKE';

	if ($username != '')
		$where_sql[] = 'u.username '.$like_command.' \''.$db->escape(str_replace('*', '%', $username)).'\'';
	if ($show_group > -1)
		$where_sql[] = 'u.group_id='.$show_group;

	// Fetch user count
	$result = $db->query('SELECT COUNT(id) FROM '.$db->prefix.'users AS u WHERE u.id>1 AND u.group_id!='.PUN_UNVERIFIED.(!empty($where_sql) ? ' AND '.implode(' AND ', $where_sql) : '')) or error('Unable to fetch user list count', __FILE__, __LINE__, $db->error());
	$num_users = $db->result($result);
	
	return $num_users;
}

// Generates the dropdown menu containing groups
function generate_dropdown_menu()
{
	global $db;
	
	$result = $db->query('SELECT g_id, g_title FROM '.$db->prefix.'groups WHERE g_id!='.PUN_GUEST.' ORDER BY g_id') or error('Unable to fetch user group list', __FILE__, __LINE__, $db->error());

	while ($cur_group = $db->fetch_assoc($result))
	{
		if ($cur_group['g_id'] == $show_group)
			echo "\t\t\t\t\t\t\t".'<option value="'.$cur_group['g_id'].'" selected="selected">'.pun_htmlspecialchars($cur_group['g_title']).'</option>'."\n";
		else
			echo "\t\t\t\t\t\t\t".'<option value="'.$cur_group['g_id'].'">'.pun_htmlspecialchars($cur_group['g_title']).'</option>'."\n";
	}
}

// Prints the users
function print_users($username, $start_from, $sort_by, $sort_dir, $show_post_count)
{
	global $db;
	
	$userlist_data = array();
	
	// Create any SQL for the WHERE clause
	$where_sql = array();
	$like_command = ($db_type == 'pgsql') ? 'ILIKE' : 'LIKE';

	if ($username != '')
		$where_sql[] = 'u.username '.$like_command.' \''.$db->escape(str_replace('*', '%', $username)).'\'';
	if ($show_group > -1)
		$where_sql[] = 'u.group_id='.$show_group;
	
	// Retrieve a list of user IDs, LIMIT is (really) expensive so we only fetch the IDs here then later fetch the remaining data
	$result = $db->query('SELECT u.id FROM '.$db->prefix.'users AS u WHERE u.id>1 AND u.group_id!='.PUN_UNVERIFIED.(!empty($where_sql) ? ' AND '.implode(' AND ', $where_sql) : '').' ORDER BY '.$sort_by.' '.$sort_dir.', u.id ASC LIMIT '.$start_from.', 50') or error('Unable to fetch user IDs', __FILE__, __LINE__, $db->error());

	if ($db->num_rows($result))
	{
		$user_ids = array();
		for ($i = 0;$cur_user_id = $db->result($result, $i);$i++)
			$user_ids[] = $cur_user_id;

		// Grab the users
		$result = $db->query('SELECT u.id, u.username, u.title, u.num_posts, u.registered, g.g_id, g.g_user_title FROM '.$db->prefix.'users AS u LEFT JOIN '.$db->prefix.'groups AS g ON g.g_id=u.group_id WHERE u.id IN('.implode(',', $user_ids).') ORDER BY '.$sort_by.' '.$sort_dir.', u.id ASC') or error('Unable to fetch user list', __FILE__, __LINE__, $db->error());

		while ($user_data = $db->fetch_assoc($result))
			$userlist_data[] = $user_data;
	}
	
	return $userlist_data;
}