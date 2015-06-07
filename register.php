<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

define('PUN_ROOT', dirname(__FILE__).'/');
require PUN_ROOT.'include/common.php';


// If we are logged in, we shouldn't be here
if (!$pun_user['is_guest'])
{
	header('Location: index.php');
	exit;
}

// Load the register.php language file
require PUN_ROOT.'lang/'.$pun_user['language'].'/register.php';

// Load the register.php/profile.php language file
require PUN_ROOT.'lang/'.$pun_user['language'].'/prof_reg.php';

if ($pun_config['o_regs_allow'] == '0')
	message($lang_register['No new regs']);

// Load the register.php model file
require PUN_ROOT.'model/register.php';


// User pressed the cancel button
if (isset($_GET['cancel']))
	redirect('index.php', $lang_register['Reg cancel redirect']);


else if ($pun_config['o_rules'] == '1' && !isset($_GET['agree']) && !isset($_POST['form_sent']))
{
	$page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), $lang_register['Register'], $lang_register['Forum rules']);
	define('PUN_ACTIVE_PAGE', 'register');
	require PUN_ROOT.'header.php';
	
	// Load the register.php view file
	require PUN_ROOT.'view/register/rules.php';

	require PUN_ROOT.'footer.php';
}

// Start with a clean slate
$errors = array();

if (isset($_POST['form_sent']))
{
	$user = check_for_errors($_POST);

	// Did everything go according to plan? Insert the user
	if (empty($errors))
		insert_user($user);
}


$page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), $lang_register['Register']);
$required_fields = array('req_user' => $lang_common['Username'], 'req_password1' => $lang_common['Password'], 'req_password2' => $lang_prof_reg['Confirm pass'], 'req_email1' => $lang_common['Email'], 'req_email2' => $lang_common['Email'].' 2');
$focus_element = array('register', 'req_user');

define('PUN_ACTIVE_PAGE', 'register');
require PUN_ROOT.'header.php';

$user['timezone'] = isset($user['timezone']) ? $user['timezone'] : $pun_config['o_default_timezone'];
$user['dst'] = isset($user['dst']) ? $user['dst'] : $pun_config['o_default_dst'];
$user['email_setting'] = isset($user['email_setting']) ? $user['email_setting'] : $pun_config['o_default_email_setting'];

// Load the register.php view file
require PUN_ROOT.'view/register/form.php';

require PUN_ROOT.'footer.php';
