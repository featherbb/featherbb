<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace controller{
    
    class Index{

        function display(){
			
			global $feather, $lang_common, $pun_config, $pun_user, $pun_start, $db;
			
			if ($pun_user['g_read_board'] == '0') {
				message($lang_common['No view'], false, '403 Forbidden');
			}
			
			// Load the index.php language file
			require PUN_ROOT.'lang/'.$pun_user['language'].'/index.php';
			
			$page_title = array(pun_htmlspecialchars($pun_config['o_board_title']));
			define('PUN_ALLOW_INDEX', 1);
			
			if (!defined('PUN_ACTIVE_PAGE')) {
				define('PUN_ACTIVE_PAGE', 'index');
			}
			
			require PUN_ROOT.'header.php';
			
			// Load the index.php model file
			require PUN_ROOT.'model/index.php';

			$feather->render('header.php', array(
				'lang_common' => $lang_common,
				'page_title' => $page_title,
				'p' => $p,
				'pun_user' => $pun_user,
				'pun_config' => $pun_config,
				'_SERVER'	=>	$_SERVER,
				'page_head'		=>	get_page_head(),
				'navlinks'		=>	$navlinks,
				'page_info'		=>	$page_info,
				'db'		=>	$db,
				)
			);
			
			$feather->render('index.php', array(
				'index_data' => print_categories_forums(),
				'lang_common' => $lang_common,
				'lang_index' => $lang_index,
				'stats' => collect_stats(),
				'pun_config' => $pun_config,
				'online'	=>	fetch_users_online(),
				'forum_actions'		=>	get_forum_actions(),
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