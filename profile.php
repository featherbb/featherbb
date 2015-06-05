<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

define('PUN_ROOT', dirname(__FILE__).'/');
require PUN_ROOT.'include/common.php';

// Include UTF-8 function
require PUN_ROOT.'include/utf8/substr_replace.php';
require PUN_ROOT.'include/utf8/ucwords.php'; // utf8_ucwords needs utf8_substr_replace
require PUN_ROOT.'include/utf8/strcasecmp.php';

$action = isset($_GET['action']) ? $_GET['action'] : null;
$section = isset($_GET['section']) ? $_GET['section'] : null;
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id < 2)
	message($lang_common['Bad request'], false, '404 Not Found');

if ($action != 'change_pass' || !isset($_GET['key']))
{
	if ($pun_user['g_read_board'] == '0')
		message($lang_common['No view'], false, '403 Forbidden');
	else if ($pun_user['g_view_users'] == '0' && ($pun_user['is_guest'] || $pun_user['id'] != $id))
		message($lang_common['No permission'], false, '403 Forbidden');
}

// Load the prof_reg.php language file
require PUN_ROOT.'lang/'.$pun_user['language'].'/prof_reg.php';

// Load the profile.php language file
require PUN_ROOT.'lang/'.$pun_user['language'].'/profile.php';

// Load the profile.php model file
require PUN_ROOT.'model/profile.php';


if ($action == 'change_pass')
{
	change_pass($id, $_POST, $_GET);

	$page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), $lang_common['Profile'], $lang_profile['Change pass']);
	$required_fields = array('req_old_password' => $lang_profile['Old pass'], 'req_new_password1' => $lang_profile['New pass'], 'req_new_password2' => $lang_profile['Confirm new pass']);
	$focus_element = array('change_pass', ((!$pun_user['is_admmod']) ? 'req_old_password' : 'req_new_password1'));
	define('PUN_ACTIVE_PAGE', 'profile');
	require PUN_ROOT.'header.php';
	
	// Load the view.php view file
	require PUN_ROOT.'view/profile.php';

	require PUN_ROOT.'footer.php';
}


else if ($action == 'change_email')
{
	change_email($id, $_POST, $_GET);

	$page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), $lang_common['Profile'], $lang_profile['Change email']);
	$required_fields = array('req_new_email' => $lang_profile['New email'], 'req_password' => $lang_common['Password']);
	$focus_element = array('change_email', 'req_new_email');
	define('PUN_ACTIVE_PAGE', 'profile');
	require PUN_ROOT.'header.php';
	
	// Load the view.php view file
	require PUN_ROOT.'view/profile.php';

	require PUN_ROOT.'footer.php';
}


else if ($action == 'upload_avatar' || $action == 'upload_avatar2')
{
	if ($pun_config['o_avatars'] == '0')
		message($lang_profile['Avatars disabled']);

	if ($pun_user['id'] != $id && !$pun_user['is_admmod'])
		message($lang_common['No permission'], false, '403 Forbidden');

	if (isset($_POST['form_sent']))
		upload_avatar($id, $_FILES);

	$page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), $lang_common['Profile'], $lang_profile['Upload avatar']);
	$required_fields = array('req_file' => $lang_profile['File']);
	$focus_element = array('upload_avatar', 'req_file');
	define('PUN_ACTIVE_PAGE', 'profile');
	require PUN_ROOT.'header.php';
	
	// Load the view.php view file
	require PUN_ROOT.'view/profile.php';

	require PUN_ROOT.'footer.php';
}


else if ($action == 'delete_avatar')
{
	if ($pun_user['id'] != $id && !$pun_user['is_admmod'])
		message($lang_common['No permission'], false, '403 Forbidden');

	confirm_referrer('profile.php');

	delete_avatar($id);

	redirect('profile.php?section=personality&amp;id='.$id, $lang_profile['Avatar deleted redirect']);
}


else if (isset($_POST['update_group_membership']))
{
	if ($pun_user['g_id'] > PUN_ADMIN)
		message($lang_common['No permission'], false, '403 Forbidden');

	update_group_membership($id, $_POST);
}


else if (isset($_POST['update_forums']))
{
	if ($pun_user['g_id'] > PUN_ADMIN)
		message($lang_common['No permission'], false, '403 Forbidden');

	update_mod_forums($id, $_POST);
}


else if (isset($_POST['ban']))
{
	if ($pun_user['g_id'] != PUN_ADMIN && ($pun_user['g_moderator'] != '1' || $pun_user['g_mod_ban_users'] == '0'))
		message($lang_common['No permission'], false, '403 Forbidden');

	ban_user($id);
}


else if ($action == 'promote')
{
	if ($pun_user['g_id'] != PUN_ADMIN && ($pun_user['g_moderator'] != '1' || $pun_user['g_mod_promote_users'] == '0'))
		message($lang_common['No permission'], false, '403 Forbidden');

	promote_user($id, $_GET['pid']);
}


else if (isset($_POST['delete_user']) || isset($_POST['delete_user_comply']))
{
	if ($pun_user['g_id'] > PUN_ADMIN)
		message($lang_common['No permission'], false, '403 Forbidden');
	
	delete_user($id, $_POST);

	$page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), $lang_common['Profile'], $lang_profile['Confirm delete user']);
	define('PUN_ACTIVE_PAGE', 'profile');
	require PUN_ROOT.'header.php';
	
	// Load the view.php view file
	require PUN_ROOT.'view/profile.php';

	require PUN_ROOT.'footer.php';
}


else if (isset($_POST['form_sent']))
{
	// Fetch the user group of the user we are editing
	$info = fetch_user_group($id);

	if ($pun_user['id'] != $id &&																	// If we aren't the user (i.e. editing your own profile)
		(!$pun_user['is_admmod'] ||																	// and we are not an admin or mod
		($pun_user['g_id'] != PUN_ADMIN &&															// or we aren't an admin and ...
		($pun_user['g_mod_edit_users'] == '0' ||													// mods aren't allowed to edit users
		$info['group_id'] == PUN_ADMIN ||																	// or the user is an admin
		$info['is_moderator']))))																			// or the user is another mod
		message($lang_common['No permission'], false, '403 Forbidden');

	update_profile($id, $info, $section, $_POST);
}

$user = get_user_info($id);

$last_post = format_time($user['last_post']);

if ($user['signature'] != '')
{
	require PUN_ROOT.'include/parser.php';
	$parsed_signature = parse_signature($user['signature']);
}


// View or edit?
if ($pun_user['id'] != $id &&																	// If we aren't the user (i.e. editing your own profile)
	(!$pun_user['is_admmod'] ||																	// and we are not an admin or mod
	($pun_user['g_id'] != PUN_ADMIN &&															// or we aren't an admin and ...
	($pun_user['g_mod_edit_users'] == '0' ||													// mods aren't allowed to edit users
	$user['g_id'] == PUN_ADMIN ||																// or the user is an admin
	$user['g_moderator'] == '1'))))																// or the user is another mod
{
	$user_info = parse_user_info($user);

	$page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), sprintf($lang_profile['Users profile'], pun_htmlspecialchars($user['username'])));
	define('PUN_ALLOW_INDEX', 1);
	define('PUN_ACTIVE_PAGE', 'index');
	require PUN_ROOT.'header.php';

	$view_profile = true;

	// Load the view.php view file
	require PUN_ROOT.'view/profile.php';

	require PUN_ROOT.'footer.php';
}
else
{
	if (!$section || $section == 'essentials')
	{
		$user_disp = edit_essentials($id, $user);

		$page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), $lang_common['Profile'], $lang_profile['Section essentials']);
		$required_fields = array('req_username' => $lang_common['Username'], 'req_email' => $lang_common['Email']);
		define('PUN_ACTIVE_PAGE', 'profile');
		require PUN_ROOT.'header.php';

		generate_profile_menu('essentials');
		
		// Load the view.php view file
		require PUN_ROOT.'view/profile.php';
	}
	else if ($section == 'personal')
	{
		if ($pun_user['g_set_title'] == '1')
			$title_field = '<label>'.$lang_common['Title'].' <em>('.$lang_profile['Leave blank'].')</em><br /><input type="text" name="title" value="'.pun_htmlspecialchars($user['title']).'" size="30" maxlength="50" /><br /></label>'."\n";

		$page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), $lang_common['Profile'], $lang_profile['Section personal']);
		define('PUN_ACTIVE_PAGE', 'profile');
		require PUN_ROOT.'header.php';

		generate_profile_menu('personal');
		
		// Load the view.php view file
		require PUN_ROOT.'view/profile.php';
	}
	else if ($section == 'messaging')
	{

		$page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), $lang_common['Profile'], $lang_profile['Section messaging']);
		define('PUN_ACTIVE_PAGE', 'profile');
		require PUN_ROOT.'header.php';

		generate_profile_menu('messaging');
		
		// Load the view.php view file
		require PUN_ROOT.'view/profile.php';
	}
	else if ($section == 'personality')
	{
		if ($pun_config['o_avatars'] == '0' && $pun_config['o_signatures'] == '0')
			message($lang_common['Bad request'], false, '404 Not Found');

		$avatar_field = '<span><a href="profile.php?action=upload_avatar&amp;id='.$id.'">'.$lang_profile['Change avatar'].'</a></span>';

		$user_avatar = generate_avatar_markup($id);
		if ($user_avatar)
			$avatar_field .= ' <span><a href="profile.php?action=delete_avatar&amp;id='.$id.'">'.$lang_profile['Delete avatar'].'</a></span>';
		else
			$avatar_field = '<span><a href="profile.php?action=upload_avatar&amp;id='.$id.'">'.$lang_profile['Upload avatar'].'</a></span>';

		if ($user['signature'] != '')
			$signature_preview = '<p>'.$lang_profile['Sig preview'].'</p>'."\n\t\t\t\t\t\t\t".'<div class="postsignature postmsg">'."\n\t\t\t\t\t\t\t\t".'<hr />'."\n\t\t\t\t\t\t\t\t".$parsed_signature."\n\t\t\t\t\t\t\t".'</div>'."\n";
		else
			$signature_preview = '<p>'.$lang_profile['No sig'].'</p>'."\n";

		$page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), $lang_common['Profile'], $lang_profile['Section personality']);
		define('PUN_ACTIVE_PAGE', 'profile');
		require PUN_ROOT.'header.php';

		generate_profile_menu('personality');
		
		// Load the view.php view file
		require PUN_ROOT.'view/profile.php';

	}
	else if ($section == 'display')
	{
		$page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), $lang_common['Profile'], $lang_profile['Section display']);
		define('PUN_ACTIVE_PAGE', 'profile');
		require PUN_ROOT.'header.php';

		generate_profile_menu('display');

		// Load the view.php view file
		require PUN_ROOT.'view/profile.php';
	}
	else if ($section == 'privacy')
	{
		$page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), $lang_common['Profile'], $lang_profile['Section privacy']);
		define('PUN_ACTIVE_PAGE', 'profile');
		require PUN_ROOT.'header.php';

		generate_profile_menu('privacy');
		
		// Load the view.php view file
		require PUN_ROOT.'view/profile.php';
	}
	else if ($section == 'admin')
	{
		if (!$pun_user['is_admmod'] || ($pun_user['g_moderator'] == '1' && $pun_user['g_mod_ban_users'] == '0'))
			message($lang_common['Bad request'], false, '403 Forbidden');

		$page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), $lang_common['Profile'], $lang_profile['Section admin']);

		define('PUN_ACTIVE_PAGE', 'profile');
		require PUN_ROOT.'header.php';

		generate_profile_menu('admin');
		
		// Load the view.php view file
		require PUN_ROOT.'view/profile.php';
	}
	else
		message($lang_common['Bad request'], false, '404 Not Found');

	require PUN_ROOT.'footer.php';
}
