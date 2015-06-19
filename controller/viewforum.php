<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace controller{
    
    class Viewforum{

        function display($id, $name = null, $page = null){
			
			global $feather, $lang_common, $pun_config, $pun_user, $pun_start, $db;
			
			if ($pun_user['g_read_board'] == '0') {
				message($lang_common['No view'], false, '403 Forbidden');
			}

			if ($id < 1) {
				message($lang_common['Bad request'], false, '404 Not Found');
			}

			// Load the viewforum.php language file
			require PUN_ROOT.'lang/'.$pun_user['language'].'/forum.php';

			// Load the viewforum.php model file
			require PUN_ROOT.'model/viewforum.php';

			// Fetch some informations about the forum
			$cur_forum = get_info_forum($id);

			// Is this a redirect forum? In that case, redirect!
			if ($cur_forum['redirect_url'] != '') {
				header('Location: '.$cur_forum['redirect_url']);
				exit;
			}

			// Sort out who the moderators are and if we are currently a moderator (or an admin)
			$mods_array = ($cur_forum['moderators'] != '') ? unserialize($cur_forum['moderators']) : array();
			$is_admmod = ($pun_user['g_id'] == PUN_ADMIN || ($pun_user['g_moderator'] == '1' && array_key_exists($pun_user['username'], $mods_array))) ? true : false;

			$sort_by = sort_forum_by($cur_forum['sort_by']);

			// Can we or can we not post new topics?
			if (($cur_forum['post_topics'] == '' && $pun_user['g_post_topics'] == '1') || $cur_forum['post_topics'] == '1' || $is_admmod) {
				$post_link = "\t\t\t".'<p class="postlink conr"><a href="'.get_link('post/new-topic/'.$id.'/').'">'.$lang_forum['Post topic'].'</a></p>'."\n";
			} else {
				$post_link = '';
			}

			// Determine the topic offset (based on $page)
			$num_pages = ceil($cur_forum['num_topics'] / $pun_user['disp_topics']);

			$p = (!isset($page) || $page <= 1 || $page > $num_pages) ? 1 : intval($page);
			$start_from = $pun_user['disp_topics'] * ($p - 1);
			$url_forum = url_friendly($cur_forum['forum_name']);

			// Generate paging links
			$paging_links = '<span class="pages-label">'.$lang_common['Pages'].' </span>'.paginate($num_pages, $p, 'forum/'.$id.'/'.$url_forum.'/#');

			// Add relationship meta tags
			$page_head = get_page_head($id, $num_pages, $p, $url_forum);

			$forum_actions = get_forum_actions($id, $pun_config['o_forum_subscriptions'], $cur_forum['is_subscribed']);
			

			$page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), pun_htmlspecialchars($cur_forum['forum_name']));
			define('PUN_ALLOW_INDEX', 1);
			if (!defined('PUN_ACTIVE_PAGE')) {
				define('PUN_ACTIVE_PAGE', 'viewforum');
			}
			
			require PUN_ROOT.'header.php';
			
			$feather->render('header.php', array(
				'lang_common' => $lang_common,
				'page_title' => $page_title,
				'p' => $p,
				'pun_user' => $pun_user,
				'pun_config' => $pun_config,
				'_SERVER'	=>	$_SERVER,
				//'required_fields'	=>	$required_fields,
				'page_head'		=>	$page_head,
				'navlinks'		=>	$navlinks,
				'page_info'		=>	$page_info,
				'db'		=>	$db,
				)
			);

			// Print topics
			$forum_data = print_topics($id, $sort_by, $start_from);

			$feather->render('viewforum.php', array(
				'id' => $id,
				'forum_data' => $forum_data,
				'lang_common' => $lang_common,
				'lang_forum' => $lang_forum,
				'cur_forum' => $cur_forum,
				'paging_links' => $paging_links,
				'post_link' => $post_link,
				'is_admmod' => $is_admmod,
				'start_from' => $start_from,
				'url_forum' => $url_forum,
				)
			);

			$forum_id = $id;
			
			$feather->render('footer.php', array(
				'lang_common' => $lang_common,
				'pun_user' => $pun_user,
				'pun_config' => $pun_config,
				'pun_start' => $pun_start,
				'footer_style' => 'viewforum',
				)
			);
			
			require PUN_ROOT.'footer.php';
        }
    }
}