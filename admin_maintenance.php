<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

// Tell header.php to use the admin template
define('PUN_ADMIN_CONSOLE', 1);
// Tell common.php that we don't want output buffering
define('PUN_DISABLE_BUFFERING', 1);

define('PUN_ROOT', dirname(__FILE__).'/');
require PUN_ROOT.'include/common.php';
require PUN_ROOT.'include/common_admin.php';


if ($pun_user['g_id'] != PUN_ADMIN) {
    message($lang_common['No permission'], false, '403 Forbidden');
}

// Load the admin_maintenance.php language file
require PUN_ROOT.'lang/'.$admin_language.'/admin_maintenance.php';

// Load the admin_maintenance.php model file
require PUN_ROOT.'model/admin_maintenance.php';

$action = isset($_REQUEST['action']) ? pun_trim($_REQUEST['action']) : '';

if ($action == 'rebuild') {
	rebuild($_GET);

    $page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), $lang_admin_maintenance['Rebuilding search index']);

	// Load the admin_maintenance.php view file
	require PUN_ROOT.'view/admin_maintenance/rebuild.php';

	$query_str = get_query_str($_GET);

    exit('<script type="text/javascript">window.location="admin_maintenance.php'.$query_str.'"</script><hr /><p>'.sprintf($lang_admin_maintenance['Javascript redirect failed'], '<a href="admin_maintenance.php'.$query_str.'">'.$lang_admin_maintenance['Click here'].'</a>').'</p>');
}

if ($action == 'prune') {
    $prune_from = pun_trim($_POST['prune_from']);
    $prune_sticky = intval($_POST['prune_sticky']);

    if (isset($_POST['prune_comply'])) {
		prune_comply($_POST, $prune_from, $prune_sticky);
    }

	$prune = get_info_prune($_POST, $prune_sticky, $prune_from);


    $page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), $lang_admin_common['Admin'], $lang_admin_common['Prune']);
    define('PUN_ACTIVE_PAGE', 'admin');
    require PUN_ROOT.'include/header.php';

    generate_admin_menu('maintenance');

	// Load the admin_maintenance.php view file
	require PUN_ROOT.'view/admin_maintenance/prune.php';

    require PUN_ROOT.'include/footer.php';
    exit;
}


// Get the first post ID from the db
$result = $db->query('SELECT id FROM '.$db->prefix.'posts ORDER BY id ASC LIMIT 1') or error('Unable to fetch topic info', __FILE__, __LINE__, $db->error());
if ($db->num_rows($result)) {
    $first_id = $db->result($result);
}

$page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), $lang_admin_common['Admin'], $lang_admin_common['Maintenance']);
define('PUN_ACTIVE_PAGE', 'admin');
require PUN_ROOT.'include/header.php';

generate_admin_menu('maintenance');

// Load the admin_maintenance.php view file
require PUN_ROOT.'view/admin_maintenance/admin_maintenance.php';

require PUN_ROOT.'include/footer.php';
