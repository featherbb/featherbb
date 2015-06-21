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


if ($pun_user['g_id'] != PUN_ADMIN) {
	message($lang_common['No permission'], false, '403 Forbidden');
}

// Load the admin_censoring.php language file
require PUN_ROOT.'lang/'.$admin_language.'/admin_groups.php';

// Load the admin_groups.php model file
require PUN_ROOT.'model/admin_groups.php';


// Fetch all groups
$groups = fetch_groups();

// Add/edit a group (stage 1)
if (isset($_POST['add_group']) || isset($_GET['edit_group'])) {
	
    $group = info_add_group($groups, $_POST, $_GET);

	$page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), $lang_admin_common['Admin'], $lang_admin_common['User groups']);
	$required_fields = array('req_title' => $lang_admin_groups['Group title label']);
	$focus_element = array('groups2', 'req_title');
	define('PUN_ACTIVE_PAGE', 'admin');
	require PUN_ROOT.'include/header.php';

	generate_admin_menu('groups');

	// Load the admin_groups.php view file
	require PUN_ROOT.'view/admin_groups/add_edit_group.php';

	require PUN_ROOT.'include/footer.php';
}


// Add/edit a group (stage 2)
else if (isset($_POST['add_edit_group'])) {
	add_edit_group($groups, $_POST);
}

// Set default group
else if (isset($_POST['set_default_group'])) {
	set_default_group($groups, $_POST);
}

// Remove a group
else if (isset($_GET['del_group'])) {
	
	confirm_referrer('admin_groups.php');

	$group_id = isset($_POST['group_to_delete']) ? intval($_POST['group_to_delete']) : intval($_GET['del_group']);
	if ($group_id < 5) {
		message($lang_common['Bad request'], false, '404 Not Found');
	}

	// Make sure we don't remove the default group
	if ($group_id == $pun_config['o_default_user_group']) {
		message($lang_admin_groups['Cannot remove default message']);
	}

	// Check if this group has any members
	$is_member = check_members($group_id);

	// If the group doesn't have any members or if we've already selected a group to move the members to
	if (!$is_member || isset($_POST['del_group']))
	{
		if (isset($_POST['del_group_comply']) || isset($_POST['del_group'])) {
			delete_group($_POST, $group_id);
		}
		else
		{
			$group_title = get_group_title($group_id);

			$page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), $lang_admin_common['Admin'], $lang_admin_common['User groups']);
			define('PUN_ACTIVE_PAGE', 'admin');
			require PUN_ROOT.'include/header.php';

			generate_admin_menu('groups');

			// Load the admin_groups.php view file
			require PUN_ROOT.'view/admin_groups/confirm_delete.php';

			require PUN_ROOT.'include/footer.php';
		}
	}
	
	$group_info = get_title_members($group_id);

	$page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), $lang_admin_common['Admin'], $lang_admin_common['User groups']);
	define('PUN_ACTIVE_PAGE', 'admin');
	require PUN_ROOT.'include/header.php';

	generate_admin_menu('groups');

	// Load the admin_groups.php view file
	require PUN_ROOT.'view/admin_groups/delete_group.php';

	require PUN_ROOT.'include/footer.php';
}


$page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), $lang_admin_common['Admin'], $lang_admin_common['User groups']);
define('PUN_ACTIVE_PAGE', 'admin');
require PUN_ROOT.'include/header.php';

generate_admin_menu('groups');

// Load the admin_groups.php view file
require PUN_ROOT.'view/admin_groups/admin_groups.php';

require PUN_ROOT.'include/footer.php';
