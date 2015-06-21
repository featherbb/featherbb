<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

// Tell header.php to use the admin template
define('PUN_ADMIN_CONSOLE', 1);

define('PUN_ROOT', dirname(__FILE__).'/');
require PUN_ROOT.'include/common.php';
require PUN_ROOT.'include/common_admin.php';


if (!$pun_user['is_admmod']) {
    message($lang_common['No permission'], false, '403 Forbidden');
}

// Load the admin_users.php language file
require PUN_ROOT.'lang/'.$admin_language.'/admin_users.php';

// Load the admin_users.php model file
require PUN_ROOT.'model/admin_users.php';

// Show IP statistics for a certain user ID
if (isset($_GET['ip_stats'])) {
	
    $ip_stats = intval($_GET['ip_stats']);
    if ($ip_stats < 1) {
        message($lang_common['Bad request'], false, '404 Not Found');
    }

    // Fetch ip count
    $num_ips = get_num_ip($ip_stats);

    // Determine the ip offset (based on $_GET['p'])
    $num_pages = ceil($num_ips / 50);

    $p = (!isset($_GET['p']) || $_GET['p'] <= 1 || $_GET['p'] > $num_pages) ? 1 : intval($_GET['p']);
    $start_from = 50 * ($p - 1);

    // Generate paging links
    $paging_links = '<span class="pages-label">'.$lang_common['Pages'].' </span>'.paginate($num_pages, $p, 'admin_users.php?ip_stats='.$ip_stats);

    $page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), $lang_admin_common['Admin'], $lang_admin_common['Users'], $lang_admin_users['Results head']);
    define('PUN_ACTIVE_PAGE', 'admin');
    require PUN_ROOT.'include/header.php';
	
	$ip_data = get_ip_stats($ip_stats, $start_from);

    // Load the admin_users.php view file
    require PUN_ROOT.'view/admin_users/search_ip.php';

    require PUN_ROOT.'include/footer.php';
}


if (isset($_GET['show_users'])) {
	
    $ip = pun_trim($_GET['show_users']);

    if (!@preg_match('%^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}$%', $ip) && !@preg_match('%^((([0-9A-Fa-f]{1,4}:){7}[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){6}:[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){5}:([0-9A-Fa-f]{1,4}:)?[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){4}:([0-9A-Fa-f]{1,4}:){0,2}[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){3}:([0-9A-Fa-f]{1,4}:){0,3}[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){2}:([0-9A-Fa-f]{1,4}:){0,4}[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){6}((\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b)\.){3}(\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b))|(([0-9A-Fa-f]{1,4}:){0,5}:((\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b)\.){3}(\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b))|(::([0-9A-Fa-f]{1,4}:){0,5}((\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b)\.){3}(\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b))|([0-9A-Fa-f]{1,4}::([0-9A-Fa-f]{1,4}:){0,5}[0-9A-Fa-f]{1,4})|(::([0-9A-Fa-f]{1,4}:){0,6}[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){1,7}:))$%', $ip)) {
        message($lang_admin_users['Bad IP message']);
    }

    // Fetch user count
    $num_users = get_num_users_ip($ip);

    // Determine the user offset (based on $_GET['p'])
    $num_pages = ceil($num_users / 50);

    $p = (!isset($_GET['p']) || $_GET['p'] <= 1 || $_GET['p'] > $num_pages) ? 1 : intval($_GET['p']);
    $start_from = 50 * ($p - 1);

    // Generate paging links
    $paging_links = '<span class="pages-label">'.$lang_common['Pages'].' </span>'.paginate($num_pages, $p, 'admin_users.php?show_users='.$ip);

    $page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), $lang_admin_common['Admin'], $lang_admin_common['Users'], $lang_admin_users['Results head']);
    define('PUN_ACTIVE_PAGE', 'admin');
    require PUN_ROOT.'include/header.php';
	
	$info = get_info_poster($ip, $start_from);

    // Load the admin_users.php view file
    require PUN_ROOT.'view/admin_users/show_users.php';
	
    require PUN_ROOT.'include/footer.php';
}


// Move multiple users to other user groups
elseif (isset($_POST['move_users']) || isset($_POST['move_users_comply'])) {
	
    if ($pun_user['g_id'] > PUN_ADMIN) {
        message($lang_common['No permission'], false, '403 Forbidden');
    }

	$move = move_users($_POST);

    $page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), $lang_admin_common['Admin'], $lang_admin_common['Users'], $lang_admin_users['Move users']);
    define('PUN_ACTIVE_PAGE', 'admin');
    require PUN_ROOT.'include/header.php';

    generate_admin_menu('users');

    // Load the admin_users.php view file
    require PUN_ROOT.'view/admin_users/move_users.php';

    require PUN_ROOT.'include/footer.php';
}


// Delete multiple users
elseif (isset($_POST['delete_users']) || isset($_POST['delete_users_comply'])) {
	
    if ($pun_user['g_id'] > PUN_ADMIN) {
        message($lang_common['No permission'], false, '403 Forbidden');
    }

	$user_ids = delete_users($_POST);

    $page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), $lang_admin_common['Admin'], $lang_admin_common['Users'], $lang_admin_users['Delete users']);
    define('PUN_ACTIVE_PAGE', 'admin');
    require PUN_ROOT.'include/header.php';

    generate_admin_menu('users');

    // Load the admin_users.php view file
    require PUN_ROOT.'view/admin_users/delete_users.php';

    require PUN_ROOT.'include/footer.php';
}


// Ban multiple users
elseif (isset($_POST['ban_users']) || isset($_POST['ban_users_comply'])) {
	
    if ($pun_user['g_id'] != PUN_ADMIN && ($pun_user['g_moderator'] != '1' || $pun_user['g_mod_ban_users'] == '0')) {
        message($lang_common['No permission'], false, '403 Forbidden');
    }

	$user_ids = ban_users($_POST);

    $page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), $lang_admin_common['Admin'], $lang_admin_common['Bans']);
    $focus_element = array('bans2', 'ban_message');
    define('PUN_ACTIVE_PAGE', 'admin');
    require PUN_ROOT.'include/header.php';

    generate_admin_menu('users');

    // Load the admin_users.php view file
    require PUN_ROOT.'view/admin_users/ban_users.php';

    require PUN_ROOT.'include/footer.php';
	
}

// Find users
elseif (isset($_GET['find_user'])) {

	// Return conditions and query string for the URL
	$search = get_user_search($_GET);

    // Fetch user count
    $num_users = get_num_users_search($search['conditions']);

    // Determine the user offset (based on $_GET['p'])
    $num_pages = ceil($num_users / 50);

    $p = (!isset($_GET['p']) || $_GET['p'] <= 1 || $_GET['p'] > $num_pages) ? 1 : intval($_GET['p']);
    $start_from = 50 * ($p - 1);

    // Generate paging links
    $paging_links = '<span class="pages-label">'.$lang_common['Pages'].' </span>'.paginate($num_pages, $p, 'admin_users.php?find_user=&amp;'.implode('&amp;', $search['query_str']));

    // Some helper variables for permissions
    $can_delete = $can_move = $pun_user['g_id'] == PUN_ADMIN;
    $can_ban = $pun_user['g_id'] == PUN_ADMIN || ($pun_user['g_moderator'] == '1' && $pun_user['g_mod_ban_users'] == '1');
    $can_action = ($can_delete || $can_ban || $can_move) && $num_users > 0;

    $page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), $lang_admin_common['Admin'], $lang_admin_common['Users'], $lang_admin_users['Results head']);
    $page_head = array('js' => '<script type="text/javascript" src="common.js"></script>');
    define('PUN_ACTIVE_PAGE', 'admin');
    require PUN_ROOT.'include/header.php';

    // Load the admin_users.php view file
    require PUN_ROOT.'view/admin_users/find_users.php';

    require PUN_ROOT.'include/footer.php';
	
} else {
    $page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), $lang_admin_common['Admin'], $lang_admin_common['Users']);
    $focus_element = array('find_user', 'form[username]');
    define('PUN_ACTIVE_PAGE', 'admin');
    require PUN_ROOT.'include/header.php';

    generate_admin_menu('users');

    // Load the admin_users.php view file
    require PUN_ROOT.'view/admin_users/admin_users.php';

    require PUN_ROOT.'include/footer.php';
}
