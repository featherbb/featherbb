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

// Load the admin_statistics.php model file
require PUN_ROOT.'model/admin_statistics.php';

$action = isset($_GET['action']) ? $_GET['action'] : null;


// Show phpinfo() output
if ($action == 'phpinfo' && $pun_user['g_id'] == PUN_ADMIN) {
    // Is phpinfo() a disabled function?
    if (strpos(strtolower((string) ini_get('disable_functions')), 'phpinfo') !== false) {
        message($lang_admin_index['PHPinfo disabled message']);
    }

    phpinfo();
    exit;
}


// Get the server load averages (if possible)
$server_load = get_server_load();


// Get number of current visitors
$num_online = get_num_online();


// Collect some additional info about MySQL
$total_size = get_total_size();


// Check for the existence of various PHP opcode caches/optimizers
$php_accelerator = get_php_accelerator();


$page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), $lang_admin_common['Admin'], $lang_admin_common['Server statistics']);
define('PUN_ACTIVE_PAGE', 'admin');
require PUN_ROOT.'header.php';

generate_admin_menu('index');

// Load the admin_index.php view file
require PUN_ROOT.'view/admin_statistics.php';

require PUN_ROOT.'footer.php';
