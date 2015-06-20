<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace controller{
    
    class Help{

        function display(){
			
			global $feather, $lang_common, $pun_config, $pun_user, $pun_start, $db;
			
			if ($pun_user['g_read_board'] == '0') {
				message($lang_common['No view'], false, '403 Forbidden');
			}


			// Load the help.php language file
			require PUN_ROOT.'lang/'.$pun_user['language'].'/help.php';

			
			$page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), $lang_help['Help']);
			
			if (!defined('PUN_ACTIVE_PAGE')) {
				define('PUN_ACTIVE_PAGE', 'help');
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
				'p'		=>	'',
				)
			);

			$feather->render('help.php', array(
				'lang_help' => $lang_help,
				'lang_common' => $lang_common,
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
    }
}