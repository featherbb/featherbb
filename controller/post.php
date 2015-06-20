<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace controller{
    
    class Post{
		
		function newreply($fid = null, $tid = null, $qid = null) {
			Post::newpost('', $fid, $tid);
		}

        function newpost($fid = null, $tid = null, $qid = null) {
			
			global $feather, $lang_common, $lang_prof_reg, $pun_config, $pun_user, $pun_start, $db, $lang_antispam_questions, $lang_antispam, $lang_post, $lang_register;
			
			// Load the post.php model file
			require PUN_ROOT.'model/post.php';
			
			// Load the register.php/profile.php language file
			require PUN_ROOT.'lang/'.$pun_user['language'].'/prof_reg.php';
			
			// Load the register.php/profile.php language file
			require PUN_ROOT.'lang/'.$pun_user['language'].'/register.php';

			// Antispam feature
			require PUN_ROOT.'lang/'.$pun_user['language'].'/antispam.php';
			$index_questions = rand(0,count($lang_antispam_questions)-1);

			// If $_POST['username'] is filled, we are facing a bot
			if (!empty($feather->request->post('username'))) {
				message($lang_common['Bad request'], false, '404 Not Found');
			}

			// Fetch some info about the topic and/or the forum
			$cur_posting = get_info_post($tid, $fid);
			
			$is_subscribed = $tid && $cur_posting['is_subscribed'];

			// Is someone trying to post into a redirect forum?
			if ($cur_posting['redirect_url'] != '') {
				message($lang_common['Bad request'], false, '404 Not Found');
			}

			// Sort out who the moderators are and if we are currently a moderator (or an admin)
			$mods_array = ($cur_posting['moderators'] != '') ? unserialize($cur_posting['moderators']) : array();
			$is_admmod = ($pun_user['g_id'] == PUN_ADMIN || ($pun_user['g_moderator'] == '1' && array_key_exists($pun_user['username'], $mods_array))) ? true : false;

			// Do we have permission to post?
			if ((($tid && (($cur_posting['post_replies'] == '' && $pun_user['g_post_replies'] == '0') || $cur_posting['post_replies'] == '0')) ||
				($fid && (($cur_posting['post_topics'] == '' && $pun_user['g_post_topics'] == '0') || $cur_posting['post_topics'] == '0')) ||
				(isset($cur_posting['closed']) && $cur_posting['closed'] == '1')) &&
				!$is_admmod) {
				message($lang_common['No permission'], false, '403 Forbidden');
			}

			// Load the post.php language file
			require PUN_ROOT.'lang/'.$pun_user['language'].'/post.php';

			// Start with a clean slate
			$errors = array();
			
			$post = '';
			
			if (!$pun_user['is_guest']) {
				$focus_element[] = ($fid) ? 'req_subject' : 'req_message';
			} else {
				$required_fields['req_username'] = $lang_post['Guest name'];
				$focus_element[] = 'req_username';
			}

			// Did someone just hit "Submit" or "Preview"?
			if ($feather->request()->isPost()) {
				
				// Include $pid and $page if needed for confirm_referrer function called in check_errors_before_post()
				if (!empty($feather->request->post('pid'))) {
					$pid = $feather->request->post('pid');
				}
				else {
					$pid = '';
				}
				
				if (!empty($feather->request->post('page'))) {
					$page = $feather->request->post('page');
				}
				else {
					$page = '';
				}
				
				// Let's see if everything went right
				$errors = check_errors_before_post($fid, $tid, $qid, $pid, $page, $feather, $errors);
				
				// Setup some variables before post
				$post = setup_variables($feather, $errors, $is_admmod);

				// Did everything go according to plan?
				if (empty($errors) && empty($feather->request->post('preview'))) {
					require PUN_ROOT.'include/search_idx.php';

					// If it's a reply
					if ($tid) {
						// Insert the reply, get the new_pid
						$new = insert_reply($post, $tid, $cur_posting, $is_subscribed);

						// Should we send out notifications?
						if ($pun_config['o_topic_subscriptions'] == '1') {
							send_notifications_reply($tid, $cur_posting, $new['pid']);
						}
					}
					// If it's a new topic
					elseif ($fid) {
						// Insert the topic, get the new_pid
						$new = insert_topic($post, $fid);

						// Should we send out notifications?
						if ($pun_config['o_forum_subscriptions'] == '1') {
							send_notifications_new_topic($post, $cur_posting, $new['tid']);
						}
					}

					// If we previously found out that the email was banned
					if ($pun_user['is_guest'] && isset($errors['banned_email']) && $pun_config['o_mailing_list'] != '') {
						warn_banned_user($post, $new['pid']);
					}

					// If the posting user is logged in, increment his/her post count
					if (!$pun_user['is_guest']) {
						increment_post_count($post, $new['tid']);
					}

					redirect(get_link('post/'.$new['pid'].'/#p'.$new['pid']), $lang_post['Post redirect']);
				}
			}
			
			$quote = '';
			
			// If a topic ID was specified in the url (it's a reply)
			if ($tid) {
				$action = $lang_post['Post a reply'];
				$form = '<form id="post" method="post" action="'.get_link('post/reply/'.$tid.'/').'" onsubmit="this.submit.disabled=true;if(process_form(this)){return true;}else{this.submit.disabled=false;return false;}">';
	
				// If a quote ID was specified in the url
				if (isset($qid)) {
					$quote = get_quote_message($qid, $tid);
					$form = '<form id="post" method="post" action="'.get_link('post/reply/'.$tid.'/quote/'.$qid.'/').'" onsubmit="this.submit.disabled=true;if(process_form(this)){return true;}else{this.submit.disabled=false;return false;}">';
				}
			}
			// If a forum ID was specified in the url (new topic)
			elseif ($fid) {
				$action = $lang_post['Post new topic'];
				$form = '<form id="post" method="post" action="'.get_link('post/new-topic/'.$fid.'/').'" onsubmit="return process_form(this)">';
			} else {
				message($lang_common['Bad request'], false, '404 Not Found');
			}

			$url_forum = url_friendly($cur_posting['forum_name']);
			
			$is_subscribed = $tid && $cur_posting['is_subscribed'];
			
			if (isset($cur_posting['subject'])) {
				$url_topic = url_friendly($cur_posting['subject']);
			}
			else {
				$url_topic = '';
			}
			
			$page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), $action);
			$required_fields = array('req_email' => $lang_common['Email'], 'req_subject' => $lang_common['Subject'], 'req_message' => $lang_common['Message']);
			if ($pun_user['is_guest']) {
				$required_fields['captcha'] = $lang_antispam['Robot title'];
			}
			$focus_element = array('post');

			if (!defined('PUN_ACTIVE_PAGE')) {
				define('PUN_ACTIVE_PAGE', 'post');
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

			// Get the current state of checkboxes
			$checkboxes = get_checkboxes($feather, $fid, $is_admmod, $is_subscribed);
			
			// Check to see if the topic review is to be displayed
			if ($tid && $pun_config['o_topic_review'] != '0') {
				$post_data = topic_review($tid);
			}
			else {
				$post_data = '';
			}

			$feather->render('post.php', array(
				'post' => $post,
				'tid' => $tid,
				'fid' => $fid,
				'pun_config' => $pun_config,
				'pun_user' => $pun_user,
				'cur_posting' => $cur_posting,
				'lang_common' => $lang_common,
				'lang_post' => $lang_post,
				'lang_antispam' => $lang_antispam,
				'lang_antispam_questions' => $lang_antispam_questions,
				'index_questions' => $index_questions,
				'checkboxes' => $checkboxes,
				'cur_posting' => $cur_posting,
				'feather' => $feather,
				'action' => $action,
				'form' => $form,
				'post_data' => $post_data,
				'url_forum' => $url_forum,
				'url_topic' => $url_topic,
				'quote' => $quote,
				'errors'	=>	$errors,
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