<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace controller{
    
    class Edit{

        function editpost($id) {
			
			global $feather, $lang_common, $lang_prof_reg, $pun_config, $pun_user, $pun_start, $db, $lang_post, $lang_register;
			
			if ($pun_user['g_read_board'] == '0') {
				message($lang_common['No view'], false, '403 Forbidden');
			}

			// Load the edit.php model file
			require PUN_ROOT.'model/edit.php';

			// Fetch some informations about the post, the topic and the forum
			$cur_post = get_info_edit($id);

			// Sort out who the moderators are and if we are currently a moderator (or an admin)
			$mods_array = ($cur_post['moderators'] != '') ? unserialize($cur_post['moderators']) : array();
			$is_admmod = ($pun_user['g_id'] == PUN_ADMIN || ($pun_user['g_moderator'] == '1' && array_key_exists($pun_user['username'], $mods_array))) ? true : false;

			$can_edit_subject = $id == $cur_post['first_post_id'];

			if ($pun_config['o_censoring'] == '1') {
				$cur_post['subject'] = censor_words($cur_post['subject']);
				$cur_post['message'] = censor_words($cur_post['message']);
			}

			// Do we have permission to edit this post?
			if (($pun_user['g_edit_posts'] == '0' ||
				$cur_post['poster_id'] != $pun_user['id'] ||
				$cur_post['closed'] == '1') &&
				!$is_admmod) {
				message($lang_common['No permission'], false, '403 Forbidden');
			}

			if ($is_admmod && $pun_user['g_id'] != PUN_ADMIN && in_array($cur_post['poster_id'], get_admin_ids())) {
				message($lang_common['No permission'], false, '403 Forbidden');
			}

			// Load the post.php language file
			require PUN_ROOT.'lang/'.$pun_user['language'].'/post.php';

			// Start with a clean slate
			$errors = array();


			if ($feather->request()->isPost()) {
				// Let's see if everything went right
				$errors = check_errors_before_edit($id, $feather, $can_edit_subject, $errors);
				
				// Setup some variables before post
				$post = setup_variables($feather, $cur_post, $is_admmod, $can_edit_subject, $errors);

				// Did everything go according to plan?
				if (empty($errors) && empty($feather->request->post('preview'))) {
					// Edit the post
					edit_post($id, $can_edit_subject, $post, $cur_post, $feather, $is_admmod);

					redirect(get_link('post/'.$id.'/#p'.$id), $lang_post['Post redirect']);
				}
			}
			else {
				$post = '';
			}


			$page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), $lang_post['Edit post']);
			$required_fields = array('req_subject' => $lang_common['Subject'], 'req_message' => $lang_common['Message']);
			$focus_element = array('edit', 'req_message');
			
			if (!defined('PUN_ACTIVE_PAGE')) {
				define('PUN_ACTIVE_PAGE', 'edit');
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
				'focus_element'	=>	$focus_element,
				'p'		=>	'',
				)
			);

			if (!empty($feather->request->post('preview'))) {
				require_once PUN_ROOT.'include/parser.php';
				$preview_message = parse_message($post['message'], $post['hide_smilies']);
			}
			else {
				$preview_message = '';
			}

			$checkboxes = get_checkboxes($can_edit_subject, $is_admmod, $cur_post, $feather, 1);

			$feather->render('edit.php', array(
				'lang_common' => $lang_common,
				'cur_post' => $cur_post,
				'lang_post' => $lang_post,
				'errors' => $errors,
				'preview_message' => $preview_message,
				'id' => $id,
				'pun_config' => $pun_config,
				'pun_user' => $pun_user,
				'checkboxes' => $checkboxes,
				'feather' => $feather,
				'can_edit_subject' => $can_edit_subject,
				'post' => $post,
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