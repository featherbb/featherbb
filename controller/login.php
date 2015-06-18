<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace controller{
    
    class Login{

        function display(){
			
			global $feather, $lang_common, $pun_config, $pun_user, $pun_start, $db;
			
			if (!$pun_user['is_guest']) {
				header('Location: index.php');
				exit;
			}
			
			// Load the login.php language file
			require PUN_ROOT.'lang/'.$pun_user['language'].'/login.php';
			
			// Load the login.php model file
			require PUN_ROOT.'model/login.php';

			// TODO?: Try to determine if the data in HTTP_REFERER is valid (if not, we redirect to index.php after login)
			$redirect_url = get_redirect_url($_SERVER);

			$page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), $lang_common['Login']);
			$required_fields = array('req_username' => $lang_common['Username'], 'req_password' => $lang_common['Password']);
			$focus_element = array('login', 'req_username');

			if (!defined('PUN_ACTIVE_PAGE')) {
				define('PUN_ACTIVE_PAGE', 'login');
			}
			
			require PUN_ROOT.'header.php';
			
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
			
			$feather->render('login/form.php', array(
				'lang_common' => $lang_common,
				'lang_login' => $lang_login,
				'redirect_url'	=>	$redirect_url,
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
			
			require PUN_ROOT.'footer.php';
        }
		
		function logmein() {
			
			global $feather, $lang_common, $pun_config, $pun_user, $pun_start, $db;
			
			define('PUN_QUIET_VISIT', 1);
			
			// Load the login.php language file
			require PUN_ROOT.'lang/'.$pun_user['language'].'/login.php';
			
			// Load the login.php model file
			require PUN_ROOT.'model/login.php';
			
			login($feather);
		}
		
		function logmeout($id, $token) {
			
			global $feather, $lang_common, $pun_config, $pun_user, $pun_start, $db;
			
			define('PUN_QUIET_VISIT', 1);
			
			// Load the login.php language file
			require PUN_ROOT.'lang/'.$pun_user['language'].'/login.php';
			
			// Load the login.php model file
			require PUN_ROOT.'model/login.php';
			
			logout($id, $token);
		}
		
		function forget() {
			
			global $feather, $lang_common, $lang_login, $pun_config, $pun_user, $pun_start, $db;
			
			define('PUN_QUIET_VISIT', 1);
			
			// Load the login.php language file
			require PUN_ROOT.'lang/'.$pun_user['language'].'/login.php';
			
			// Load the login.php model file
			require PUN_ROOT.'model/login.php';
			
			$errors = password_forgotten($feather);

			$page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), $lang_login['Request pass']);
			$required_fields = array('req_email' => $lang_common['Email']);
			$focus_element = array('request_pass', 'req_email');

			if (!defined('PUN_ACTIVE_PAGE')) {
				define('PUN_ACTIVE_PAGE', 'login');
			}
			require PUN_ROOT.'header.php';
			
			$feather->render('header.php', array(
				'lang_common' => $lang_common,
				'page_title' => $page_title,
				'p' => $p,
				'pun_user' => $pun_user,
				'pun_config' => $pun_config,
				'_SERVER'	=>	$_SERVER,
				'required_fields'	=>	$required_fields,
				'page_head'		=>	'',
				'navlinks'		=>	$navlinks,
				'page_info'		=>	$page_info,
				'db'		=>	$db,
				)
			);

			$feather->render('login/password_forgotten.php', array(
				'errors'	=>	$errors,
				'lang_login'	=>	$lang_login,
				'lang_common'	=>	$lang_common,
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

			require PUN_ROOT.'footer.php';
		}
    }
}