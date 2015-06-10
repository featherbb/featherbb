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

// Load the admin_index.php language file
require PUN_ROOT.'lang/'.$admin_language.'/admin_index.php';

$action = isset($_GET['action']) ? $_GET['action'] : null;

// Check for upgrade
if ($action == 'check_upgrade') {
    if (!ini_get('allow_url_fopen')) {
        message($lang_admin_index['fopen disabled message']);
    }

    $latest_version = trim(@file_get_contents('http://featherbb.org/latest_version'));
    if (empty($latest_version)) {
        message($lang_admin_index['Upgrade check failed message']);
    }

    if (version_compare($pun_config['o_cur_version'], $latest_version, '>=')) {
        message($lang_admin_index['Running latest version message']);
    } else {
        message(sprintf($lang_admin_index['New version available message'], '<a href="http://featherbb.org/">FeatherBB.org</a>'));
    }
}
// Remove install.php
elseif ($action == 'remove_install_file') {
    $deleted = @unlink(PUN_ROOT.'install.php');

    if ($deleted) {
        redirect('admin_index.php', $lang_admin_index['Deleted install.php redirect']);
    } else {
        message($lang_admin_index['Delete install.php failed']);
    }
}

$install_file_exists = is_file(PUN_ROOT.'install.php');

$page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), $lang_admin_common['Admin'], $lang_admin_common['Index']);
define('PUN_ACTIVE_PAGE', 'admin');
require PUN_ROOT.'header.php';

generate_admin_menu('index');

// Load the admin_index.php view file
require PUN_ROOT.'view/admin_index.php';

require PUN_ROOT.'footer.php';
