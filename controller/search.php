<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace controller{
    
    class Search{

        function display(){
			
			global $feather, $lang_common, $lang_search, $pun_config, $pun_user, $pun_start, $db, $pd;
			
			// Load the search.php language file
			require PUN_ROOT.'lang/'.$pun_user['language'].'/search.php';
			require PUN_ROOT.'lang/'.$pun_user['language'].'/forum.php';
			
			if ($pun_user['g_read_board'] == '0') {
				message($lang_common['No view'], false, '403 Forbidden');
			} elseif ($pun_user['g_search'] == '0') {
				message($lang_search['No search permission'], false, '403 Forbidden');
			}

			// Load the search.php model file
			require PUN_ROOT.'model/search.php';
			
			require PUN_ROOT.'include/search_idx.php';

			// Figure out what to do :-)
			if (!empty($feather->request->get('action')) || !empty($feather->request->get('search_id'))) {
				$search = get_search_results($feather);
				
				// We have results to display
				if ($search['is_result']) {
					$page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), $lang_search['Search results']);
					
					if (!defined('PUN_ACTIVE_PAGE')) {
						define('PUN_ACTIVE_PAGE', 'search');
					}
					
					require PUN_ROOT.'header.php';
					
					$feather->render('header.php', array(
						'lang_common' => $lang_common,
						'page_title' => $page_title,
						'p' => $p,
						'pun_user' => $pun_user,
						'pun_config' => $pun_config,
						'_SERVER'	=>	$_SERVER,
						'page_head'		=>	'',
						'navlinks'		=>	$navlinks,
						'page_info'		=>	$page_info,
						'focus_element'	=>	'',
						'db'		=>	$db,
						)
					);
					
					$feather->render('search/header.php', array(
						'lang_common' => $lang_common,
						'lang_search' => $lang_search,
						'search' => $search,
						)
					);

					if ($search['show_as'] == 'posts') {
						require PUN_ROOT.'lang/'.$pun_user['language'].'/topic.php';
						require PUN_ROOT.'include/parser.php';
					}

					display_search_results($search, $feather);

					$feather->render('search/footer.php', array(
						'search' => $search,
						)
					);
					
					$feather->render('footer.php', array(
						'lang_common' => $lang_common,
						'pun_user' => $pun_user,
						'pun_config' => $pun_config,
						'pun_start' => $pun_start,
						'footer_style' => 'search',
						'db' => $db,
						)
					);

					require PUN_ROOT.'footer.php';
					
					// Stop the current instance to prevent the code below to be executed
					$feather->stop();
					
				} else {
					message($lang_search['No hits']);
				}
			}
			
			$page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), $lang_search['Search']);
			$focus_element = array('search', 'keywords');
			
			if (!defined('PUN_ACTIVE_PAGE')) {
				define('PUN_ACTIVE_PAGE', 'search');
			}
				
			require PUN_ROOT.'header.php';
			
			$feather->render('header.php', array(
				'lang_common' => $lang_common,
				'page_title' => $page_title,
				'p' => $p,
				'pun_user' => $pun_user,
				'pun_config' => $pun_config,
				'_SERVER'	=>	$_SERVER,
				'page_head'		=>	'',
				'navlinks'		=>	$navlinks,
				'page_info'		=>	$page_info,
				'focus_element'	=>	$focus_element,
				'db'		=>	$db,
				)
			);

			$feather->render('search/form.php', array(
				'lang_common' => $lang_common,
				'lang_search' => $lang_search,
				'pun_config' => $pun_config,
				'pun_user' => $pun_user,
				)
			);
			
			$feather->render('footer.php', array(
				'lang_common' => $lang_common,
				'pun_user' => $pun_user,
				'pun_config' => $pun_config,
				'pun_start' => $pun_start,
				'footer_style' => 'search',
				)
			);

			require PUN_ROOT.'footer.php';
        }
		
		function quicksearches($show) {
			redirect(get_link('search/?action=show_'.$show));
		}
    }
}