<?php

/**
 * Copyright (C) 2008-2012 FluxBB
 * based on code by Rickard Andersson copyright (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

define('PUN_ROOT', dirname(__FILE__).'/');
require PUN_ROOT.'include/common.php';


if ($pun_user['g_read_board'] == '0')
	message($lang_common['No view'], false, '403 Forbidden');
else if ($pun_user['g_view_users'] == '0')
	message($lang_common['No permission'], false, '403 Forbidden');

// Load the userlist.php language file
require PUN_ROOT.'lang/'.$pun_user['language'].'/userlist.php';

// Load the search.php language file
require PUN_ROOT.'lang/'.$pun_user['language'].'/search.php';

// Load the userlist.php model file
require PUN_ROOT.'model/userlist.php';


// Determine if we are allowed to view post counts
$show_post_count = ($pun_config['o_show_post_count'] == '1' || $pun_user['is_admmod']) ? true : false;

$username = isset($_GET['username']) && $pun_user['g_search_users'] == '1' ? pun_trim($_GET['username']) : '';
$show_group = isset($_GET['show_group']) ? intval($_GET['show_group']) : -1;
$sort_by = isset($_GET['sort_by']) && (in_array($_GET['sort_by'], array('username', 'registered')) || ($_GET['sort_by'] == 'num_posts' && $show_post_count)) ? $_GET['sort_by'] : 'username';
$sort_dir = isset($_GET['sort_dir']) && $_GET['sort_dir'] == 'DESC' ? 'DESC' : 'ASC';

$num_users = fetch_user_count($username);

// Determine the user offset (based on $_GET['p'])
$num_pages = ceil($num_users / 50);

$p = (!isset($_GET['p']) || $_GET['p'] <= 1 || $_GET['p'] > $num_pages) ? 1 : intval($_GET['p']);
$start_from = 50 * ($p - 1);

$page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), $lang_common['User list']);
if ($pun_user['g_search_users'] == '1')
	$focus_element = array('userlist', 'username');

// Generate paging links
$paging_links = '<span class="pages-label">'.$lang_common['Pages'].' </span>'.paginate($num_pages, $p, 'userlist.php?username='.urlencode($username).'&amp;show_group='.$show_group.'&amp;sort_by='.$sort_by.'&amp;sort_dir='.$sort_dir);


define('PUN_ALLOW_INDEX', 1);
define('PUN_ACTIVE_PAGE', 'userlist');
require PUN_ROOT.'header.php';

// Print the users
$userlist_data = print_users($username, $start_from, $sort_by, $sort_dir, $show_post_count);

// Load the userlist.php view file
require PUN_ROOT.'view/userlist.php';

require PUN_ROOT.'footer.php';
