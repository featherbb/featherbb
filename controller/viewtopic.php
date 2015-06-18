<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace controller{
    
    class Viewtopic{
		
        function display($id = null, $name = null, $page = null){
			
			global $feather, $lang_common, $lang_post, $lang_topic, $pun_config, $pun_user, $pun_start, $db, $pd;
			
			if ($pun_user['g_read_board'] == '0') {
				message($lang_common['No view'], false, '403 Forbidden');
			}
			
			if ($id < 1) {
				message($lang_common['Bad request'], false, '404 Not Found');
			}

			// Load the viewtopic.php language file
			require PUN_ROOT.'lang/'.$pun_user['language'].'/topic.php';

			// Load the post.php language file
			require PUN_ROOT.'lang/'.$pun_user['language'].'/post.php';

			// Antispam feature
			require PUN_ROOT.'lang/'.$pun_user['language'].'/antispam.php';
			$index_questions = rand(0,count($lang_antispam_questions)-1);

			// Load the viewtopic.php model file
			require_once PUN_ROOT.'model/viewtopic.php';
			
			// Fetch some informations about the topic TODO
			$cur_topic = get_info_topic($id);

			// Sort out who the moderators are and if we are currently a moderator (or an admin)
			$mods_array = ($cur_topic['moderators'] != '') ? unserialize($cur_topic['moderators']) : array();
			$is_admmod = ($pun_user['g_id'] == PUN_ADMIN || ($pun_user['g_moderator'] == '1' && array_key_exists($pun_user['username'], $mods_array))) ? true : false;
			if ($is_admmod) {
				$admin_ids = get_admin_ids();
			}

			// Can we or can we not post replies?
			$post_link = get_post_link($id, $cur_topic['closed'], $cur_topic['post_replies'], $is_admmod);

			// Add/update this topic in our list of tracked topics
			if (!$pun_user['is_guest']) {
				$tracked_topics = get_tracked_topics();
				$tracked_topics['topics'][$id] = time();
				set_tracked_topics($tracked_topics);
			}

			// Determine the post offset (based on $_GET['p'])
			$num_pages = ceil(($cur_topic['num_replies'] + 1) / $pun_user['disp_posts']);

			$p = (!isset($page) || $page <= 1 || $page > $num_pages) ? 1 : intval($page);
			$start_from = $pun_user['disp_posts'] * ($p - 1);
			
			$url_topic = url_friendly($cur_topic['subject']);
			$url_forum = url_friendly($cur_topic['forum_name']);

			// Generate paging links
			$paging_links = '<span class="pages-label">'.$lang_common['Pages'].' </span>'.paginate($num_pages, $p, 'topic/'.$id.'/'.$url_topic.'/#');


			if ($pun_config['o_censoring'] == '1') {
				$cur_topic['subject'] = censor_words($cur_topic['subject']);
			}

			$quickpost = is_quickpost($cur_topic['post_replies'], $cur_topic['closed'], $is_admmod);

			$subscraction = get_subscraction($cur_topic['is_subscribed'], $id);

			// Add relationship meta tags
			$page_head = get_page_head($id, $num_pages, $p, $url_topic);

			$page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), pun_htmlspecialchars($cur_topic['forum_name']), pun_htmlspecialchars($cur_topic['subject']));
			define('PUN_ALLOW_INDEX', 1);
			if (!defined('PUN_ACTIVE_PAGE')) {
				define('PUN_ACTIVE_PAGE', 'viewtopic');
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

			$forum_id = $cur_topic['forum_id'];
			
			require PUN_ROOT.'include/parser.php';
			
			$post_data = print_posts($id, $start_from, $cur_topic, $is_admmod);
			
			session_start();
			
			$feather->render('viewtopic.php', array(
				'id' => $id,
				'post_data' => $post_data,
				'lang_common' => $lang_common,
				'lang_topic' => $lang_topic,
				'lang_post' => $lang_post,
				'cur_topic'	=>	$cur_topic,
				'subscraction'	=>	$subscraction,
				'is_admmod'	=>	$is_admmod,
				'pun_config' => $pun_config,
				'paging_links' => $paging_links,
				'post_link' => $post_link,
				'start_from' => $start_from,
				'session' => $_SESSION,
				'lang_antispam' => $lang_antispam,
				'quickpost'		=>	$quickpost,
				'index_questions'		=>	$index_questions,
				'lang_antispam_questions'		=>	$lang_antispam_questions,
				'url_forum'		=>	$url_forum,
				'url_topic'		=>	$url_topic,
				)
			);
			
			$feather->render('footer.php', array(
				'lang_common' => $lang_common,
				'pun_user' => $pun_user,
				'pun_config' => $pun_config,
				'pun_start' => $pun_start,
				'footer_style' => 'viewtopic',
				)
			);
			
			// Increment "num_views" for topic
			if ($pun_config['o_topic_views'] == '1') {
				$db->query('UPDATE '.$db->prefix.'topics SET num_views=num_views+1 WHERE id='.$id) or error('Unable to update topic', __FILE__, __LINE__, $db->error());
			}
			
			require PUN_ROOT.'footer.php';
        }
		
		function viewpost($pid) {
			
			global $feather, $lang_common, $pun_config, $pun_user, $pun_start, $db;
			
			// Load the viewtopic.php model file
			require PUN_ROOT.'model/viewtopic.php';
			
			$post = redirect_to_post($pid);
			
			return Viewtopic::display($post['topic_id'], null, $post['get_p']); // TODO: $this->
		}
		
		function action($id, $action) {
			
			global $feather, $lang_common, $pun_config, $pun_user, $pun_start, $db;
			
			// Load the viewtopic.php model file
			require PUN_ROOT.'model/viewtopic.php';
			
			handle_actions($id, $action);
		}
    }
}