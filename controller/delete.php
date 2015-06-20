<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace controller{
    
    class Delete{

        function deletepost($id) {
			
			global $feather, $lang_common, $pun_config, $pun_user, $pun_start, $db, $lang_post, $pd;
			
			if ($pun_user['g_read_board'] == '0') {
				message($lang_common['No view'], false, '403 Forbidden');
			}

			// Load the delete.php model file
			require PUN_ROOT.'model/delete.php';

			// Fetch some informations about the post, the topic and the forum
			$cur_post = get_info_delete($id);

			if ($pun_config['o_censoring'] == '1') {
				$cur_post['subject'] = censor_words($cur_post['subject']);
			}

			// Sort out who the moderators are and if we are currently a moderator (or an admin)
			$mods_array = ($cur_post['moderators'] != '') ? unserialize($cur_post['moderators']) : array();
			$is_admmod = ($pun_user['g_id'] == PUN_ADMIN || ($pun_user['g_moderator'] == '1' && array_key_exists($pun_user['username'], $mods_array))) ? true : false;

			$is_topic_post = ($id == $cur_post['first_post_id']) ? true : false;

			// Do we have permission to edit this post?
			if (($pun_user['g_delete_posts'] == '0' ||
				($pun_user['g_delete_topics'] == '0' && $is_topic_post) ||
				$cur_post['poster_id'] != $pun_user['id'] ||
				$cur_post['closed'] == '1') &&
				!$is_admmod) {
				message($lang_common['No permission'], false, '403 Forbidden');
			}

			if ($is_admmod && $pun_user['g_id'] != PUN_ADMIN && in_array($cur_post['poster_id'], get_admin_ids())) {
				message($lang_common['No permission'], false, '403 Forbidden');
			}

			// Load the delete.php language file
			require PUN_ROOT.'lang/'.$pun_user['language'].'/delete.php';


			if ($feather->request()->isPost()) {
				handle_deletion($is_topic_post, $id, $cur_post['tid'], $cur_post['fid']);
			}


			$page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), $lang_delete['Delete post']);
			
			if (!defined('PUN_ACTIVE_PAGE')) {
				define('PUN_ACTIVE_PAGE', 'delete');
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
				'focus_element'	=>	'',
				'p'		=>	'',
				)
			);

			require PUN_ROOT.'include/parser.php';
			$cur_post['message'] = parse_message($cur_post['message'], $cur_post['hide_smilies']);

			$feather->render('delete.php', array(
				'lang_common' => $lang_common,
				'lang_delete' => $lang_delete,
				'cur_post' => $cur_post,
				'id' => $id,
				'is_topic_post' => $is_topic_post,
				)
			);
			
			$feather->render('footer.php', array(
				'lang_common' => $lang_common,
				'pun_user' => $pun_user,
				'pun_config' => $pun_config,
				'pun_start' => $pun_start,
				'footer_style' => 'post',
				)
			);

			require PUN_ROOT.'footer.php';

        }
    }
}