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


if ($pun_user['g_id'] != PUN_ADMIN && ($pun_user['g_moderator'] != '1' || $pun_user['g_mod_ban_users'] == '0')) {
    message($lang_common['No permission'], false, '403 Forbidden');
}

// Load the admin_bans.php language file
require PUN_ROOT.'lang/'.$admin_language.'/admin_bans.php';

// Load the admin_bans.php model file
require PUN_ROOT.'model/admin_bans.php';

// Add/edit a ban (stage 1)
if (isset($_REQUEST['add_ban']) || isset($_GET['edit_ban'])) {
    if (isset($_GET['add_ban']) || isset($_POST['add_ban'])) {
        $ban = add_ban_info($_GET, $_POST);
    } else { // We are editing a ban
        $ban = edit_ban_info($_GET);
    }

    $page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), $lang_admin_common['Admin'], $lang_admin_common['Bans']);
    $focus_element = array('bans2', 'ban_user');
    define('PUN_ACTIVE_PAGE', 'admin');
    require PUN_ROOT.'include/header.php';

    generate_admin_menu('bans');

    // Load the admin_bans.php view file
    require PUN_ROOT.'view/admin_bans/add_ban.php';

    require PUN_ROOT.'include/footer.php';
}

// Add/edit a ban (stage 2)
elseif (isset($_POST['add_edit_ban'])) {
    insert_ban($_POST);
}

// Remove a ban
elseif (isset($_GET['del_ban'])) {
    remove_ban($_GET);
}

// Find bans
elseif (isset($_GET['find_ban'])) {
    $ban_info = find_ban($_GET);

    // Determine the ban offset (based on $_GET['p'])
    $num_pages = ceil($ban_info['num_bans'] / 50);

    $p = (!isset($_GET['p']) || $_GET['p'] <= 1 || $_GET['p'] > $num_pages) ? 1 : intval($_GET['p']);
    $start_from = 50 * ($p - 1);

    // Generate paging links
    $paging_links = '<span class="pages-label">'.$lang_common['Pages'].' </span>'.paginate($num_pages, $p, 'admin_bans.php?find_ban=&amp;'.implode('&amp;', $ban_info['query_str']));

    $page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), $lang_admin_common['Admin'], $lang_admin_common['Bans'], $lang_admin_bans['Results head']);
    define('PUN_ACTIVE_PAGE', 'admin');
    require PUN_ROOT.'include/header.php';
    
    $ban_data = print_bans($ban_info['conditions'], $ban_info['order_by'], $ban_info['direction'], $start_from);

    // Load the admin_bans.php view file
    require PUN_ROOT.'view/admin_bans/search_ban.php';

    require PUN_ROOT.'include/footer.php';
}

$page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), $lang_admin_common['Admin'], $lang_admin_common['Bans']);
$focus_element = array('bans', 'new_ban_user');
define('PUN_ACTIVE_PAGE', 'admin');
require PUN_ROOT.'include/header.php';

generate_admin_menu('bans');

// Load the admin_bans.php view file
require PUN_ROOT.'view/admin_bans/admin_bans.php';

require PUN_ROOT.'include/footer.php';
