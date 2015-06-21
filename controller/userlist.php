<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace controller{
    
    class Userlist{

        function display(){
			
			global $feather, $lang_common, $pun_config, $pun_user, $pun_start, $db;
			
			if ($pun_user['g_read_board'] == '0') {
				message($lang_common['No view'], false, '403 Forbidden');
			} elseif ($pun_user['g_view_users'] == '0') {
				message($lang_common['No permission'], false, '403 Forbidden');
			}

			// Load the userlist.php language file
			require PUN_ROOT.'lang/'.$pun_user['language'].'/userlist.php';

			// Load the search.php language file
			require PUN_ROOT.'lang/'.$pun_user['language'].'/search.php';

			// Load the userlist.php model file
			require PUN_ROOT.'model/userlist.php';


			// Determine if we are allowed to view post counts
			$show_post_count = ($pun_config['o_show_post_count'] == '1' || $pun_user['is_admmod']) ? true : false;

			$username = !empty($feather->request->get('username')) && $pun_user['g_search_users'] == '1' ? pun_trim($feather->request->get('username')) : '';
			$show_group = !empty($feather->request->get('show_group')) ? intval($feather->request->get('show_group')) : -1;
			$sort_by = !empty($feather->request->get('sort_by')) && (in_array($feather->request->get('sort_by'), array('username', 'registered')) || ($feather->request->get('sort_by') == 'num_posts' && $show_post_count)) ? $feather->request->get('sort_by') : 'username';
			$sort_dir = !empty($feather->request->get('sort_dir')) && $feather->request->get('sort_dir') == 'DESC' ? 'DESC' : 'ASC';

			$num_users = fetch_user_count($username, $show_group);

			// Determine the user offset (based on $page)
			$num_pages = ceil($num_users / 50);

			$p = (empty($feather->request->get('page')) || $page <= 1 || $page > $num_pages) ? 1 : intval($page);
			$start_from = 50 * ($p - 1);

			$page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), $lang_common['User list']);
			if ($pun_user['g_search_users'] == '1') {
				$focus_element = array('userlist', 'username');
			}

			// Generate paging links
			$paging_links = '<span class="pages-label">'.$lang_common['Pages'].' </span>'.paginate($num_pages, $p, 'userlist.php?username='.urlencode($username).'&amp;show_group='.$show_group.'&amp;sort_by='.$sort_by.'&amp;sort_dir='.$sort_dir);


			define('PUN_ALLOW_INDEX', 1);
			if (!defined('PUN_ACTIVE_PAGE')) {
				define('PUN_ACTIVE_PAGE', 'userlist');
			}
			
			require PUN_ROOT.'include/header.php';
			
			$feather->render('header.php', array(
				'lang_common' => $lang_common,
				'page_title' => $page_title,
				'p' => $p,
				'pun_user' => $pun_user,
				'pun_config' => $pun_config,
				'_SERVER'	=>	$_SERVER,
				//'required_fields'	=>	$required_fields,
				//'page_head'		=>	get_page_head($id, $num_pages, $p),
				'navlinks'		=>	$navlinks,
				'page_info'		=>	$page_info,
				'db'		=>	$db,
				)
			);

			// Print the users
			$userlist_data = print_users($username, $start_from, $sort_by, $sort_dir, $show_post_count, $show_group);

			$feather->render('userlist.php', array(
				'lang_common' => $lang_common,
				'lang_search' => $lang_search,
				'lang_ul' => $lang_ul,
				'pun_user' => $pun_user,
				'username' => $username,
				'show_group' => $show_group,
				'sort_by' => $sort_by,
				'sort_dir' => $sort_dir,
				'show_post_count' => $show_post_count,
				'paging_links' => $paging_links,
				'pun_config' => $pun_config,
				'userlist_data' => $userlist_data,
				)
			);
			
			$feather->render('footer.php', array(
				'lang_common' => $lang_common,
				'pun_user' => $pun_user,
				'pun_config' => $pun_config,
				'pun_start' => $pun_start,
				'footer_style' => 'userlist',
				)
			);

			require PUN_ROOT.'include/footer.php';
        }
    }
}