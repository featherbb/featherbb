<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace controller{
    
    class Register{

        function display(){
			
			global $feather, $lang_common, $pun_config, $pun_user, $pun_start, $db, $lang_antispam_questions, $lang_antispam, $lang_register, $lang_prof_reg;
			
			if (!$pun_user['is_guest']) {
				header('Location: index.php');
				exit;
			}
			
			// Load the register.php language file
			require PUN_ROOT.'lang/'.$pun_user['language'].'/register.php';

			// Load the register.php/profile.php language file
			require PUN_ROOT.'lang/'.$pun_user['language'].'/prof_reg.php';
			
			// Antispam feature
			require PUN_ROOT.'lang/'.$pun_user['language'].'/antispam.php';
			$index_questions = rand(0,count($lang_antispam_questions)-1);
			
			// Load the register.php model file
			require PUN_ROOT.'model/register.php';

			// Display an error message if new registrations are disabled
			// If $_REQUEST['username'] or $_REQUEST['password'] are filled, we are facing a bot
			if ($pun_config['o_regs_allow'] == '0' || !empty($feather->request->post('username')) || !empty($feather->request->post('password'))) {
				message($lang_register['No new regs']);
			}

			$page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), $lang_register['Register']);
			$required_fields = array('req_user' => $lang_common['Username'], 'req_password1' => $lang_common['Password'], 'req_password2' => $lang_prof_reg['Confirm pass'], 'req_email1' => $lang_common['Email'], 'req_email2' => $lang_common['Email'].' 2', 'captcha' => $lang_antispam['Robot title']);
			$focus_element = array('register', 'req_user');

			if (!defined('PUN_ACTIVE_PAGE')) {
				define('PUN_ACTIVE_PAGE', 'register');
			}
			
			$user['timezone'] = isset($user['timezone']) ? $user['timezone'] : $pun_config['o_default_timezone'];
			$user['dst'] = isset($user['dst']) ? $user['dst'] : $pun_config['o_default_dst'];
			$user['email_setting'] = isset($user['email_setting']) ? $user['email_setting'] : $pun_config['o_default_email_setting'];
			$user['errors'] = '';

			if ($feather->request()->isPost()) {
				$user = check_for_errors($feather);

				// Did everything go according to plan? Insert the user
				if (empty($user['errors'])) {
					insert_user($user);
				}
			}
			
			require PUN_ROOT.'include/header.php';
			
			$feather->render('header.php', array(
				'lang_common' => $lang_common,
				'page_title' => $page_title,
				'pun_user' => $pun_user,
				'pun_config' => $pun_config,
				'_SERVER'	=>	$_SERVER,
				'navlinks'		=>	$navlinks,
				'page_info'		=>	$page_info,
				'db'		=>	$db,
				'required_fields'	=>	$required_fields,
				'p'		=>	'',
				)
			);
			
			$feather->render('register/form.php', array(
				'errors' => $user['errors'],
				'pun_config' => $pun_config,
				'lang_register' => $lang_register,
				'lang_common' => $lang_common,
				'lang_prof_reg' => $lang_prof_reg,
				'lang_antispam' => $lang_antispam,
				'lang_antispam_questions'	=>	$lang_antispam_questions,
				'index_questions'	=>	$index_questions,
				'feather'	=>	$feather,
				)
			);
			
			$feather->render('footer.php', array(
				'lang_common' => $lang_common,
				'pun_user' => $pun_user,
				'pun_config' => $pun_config,
				'pun_start' => $pun_start,
				'footer_style' => 'index',
				)
			);
			
			require PUN_ROOT.'include/footer.php';
        }
		
		function cancel() {
			
			global $feather;
			
			redirect(get_base_url());
		}
		
		function rules() { // TODO: fix $_GET w/ URL rewriting
			
			global $feather, $lang_common, $lang_login, $pun_config, $pun_user, $pun_start, $db;
			
			// If we are logged in, we shouldn't be here
			if (!$pun_user['is_guest']) {
				header('Location: index.php');
				exit;
			}
			
			// Display an error message if new registrations are disabled
			if ($pun_config['o_regs_allow'] == '0') {
				message($lang_register['No new regs']);
			}
			
			// Load the register.php language file
			require PUN_ROOT.'lang/'.$pun_user['language'].'/register.php';

			// Load the register.php/profile.php language file
			require PUN_ROOT.'lang/'.$pun_user['language'].'/prof_reg.php';
			
			if ($pun_config['o_rules'] != '1') {
				redirect(get_link('register/agree/'));
			}

			$page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), $lang_register['Register'], $lang_register['Forum rules']);

			if (!defined('PUN_ACTIVE_PAGE')) {
				define('PUN_ACTIVE_PAGE', 'register');
			}
			require PUN_ROOT.'include/header.php';
			
			$feather->render('header.php', array(
				'lang_common' => $lang_common,
				'page_title' => $page_title,
				'p' => $p,
				'pun_user' => $pun_user,
				'pun_config' => $pun_config,
				'_SERVER'	=>	$_SERVER,
				'required_fields'	=>	'',
				'page_head'		=>	'',
				'navlinks'		=>	$navlinks,
				'page_info'		=>	$page_info,
				'db'		=>	$db,
				)
			);

			$feather->render('register/rules.php', array(
				'lang_register'	=>	$lang_register,
				'pun_config'	=>	$pun_config,
				)
			);
			
			$feather->render('footer.php', array(
				'lang_common' => $lang_common,
				'pun_user' => $pun_user,
				'pun_config' => $pun_config,
				'pun_start' => $pun_start,
				'footer_style' => '',
				)
			);

			require PUN_ROOT.'include/footer.php';
		}
    }
}