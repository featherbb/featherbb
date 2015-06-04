<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

define('PUN_ROOT', dirname(__FILE__).'/');
require PUN_ROOT.'include/common.php';


if ($pun_user['g_read_board'] == '0')
	message($lang_common['No view'], false, '403 Forbidden');


$tid = isset($_GET['tid']) ? intval($_GET['tid']) : 0;
$fid = isset($_GET['fid']) ? intval($_GET['fid']) : 0;
if ($tid < 1 && $fid < 1 || $tid > 0 && $fid > 0)
	message($lang_common['Bad request'], false, '404 Not Found');

// Load the post.php model file
require PUN_ROOT.'model/post.php';

// Fetch some info about the topic and/or the forum
$cur_posting = get_info_post($tid, $fid);

$is_subscribed = $tid && $cur_posting['is_subscribed'];

// Is someone trying to post into a redirect forum?
if ($cur_posting['redirect_url'] != '')
	message($lang_common['Bad request'], false, '404 Not Found');

// Sort out who the moderators are and if we are currently a moderator (or an admin)
$mods_array = ($cur_posting['moderators'] != '') ? unserialize($cur_posting['moderators']) : array();
$is_admmod = ($pun_user['g_id'] == PUN_ADMIN || ($pun_user['g_moderator'] == '1' && array_key_exists($pun_user['username'], $mods_array))) ? true : false;

if ($tid && $pun_config['o_censoring'] == '1')
	$cur_posting['subject'] = censor_words($cur_posting['subject']);

// Do we have permission to post?
if ((($tid && (($cur_posting['post_replies'] == '' && $pun_user['g_post_replies'] == '0') || $cur_posting['post_replies'] == '0')) ||
	($fid && (($cur_posting['post_topics'] == '' && $pun_user['g_post_topics'] == '0') || $cur_posting['post_topics'] == '0')) ||
	(isset($cur_posting['closed']) && $cur_posting['closed'] == '1')) &&
	!$is_admmod)
	message($lang_common['No permission'], false, '403 Forbidden');

// Load the post.php language file
require PUN_ROOT.'lang/'.$pun_user['language'].'/post.php';

// Start with a clean slate
$errors = array();


// Did someone just hit "Submit" or "Preview"?
if (isset($_POST['form_sent']))
{
	// Let's see if everything went right
	$errors = check_errors_before_post($fid, $_POST, $errors);
	
	// Setup some variables before post
	$post = setup_variables($_POST, $errors, $is_admmod);

	// Did everything go according to plan?
	if (empty($errors) && !isset($_POST['preview']))
	{
		require PUN_ROOT.'include/search_idx.php';

		// If it's a reply
		if ($tid)
		{
			// Insert the reply, get the new_pid
			$new_pid = insert_reply($post, $tid, $cur_posting);

			// Should we send out notifications?
			if ($pun_config['o_topic_subscriptions'] == '1')
				send_notifications_reply($tid, $cur_posting);
		}
		// If it's a new topic
		else if ($fid)
		{
			// Insert the topic, get the new_pid
			$new_pid = insert_topic($post, $fid);

			// Should we send out notifications?
			if ($pun_config['o_forum_subscriptions'] == '1')
				send_notifications_new_topic($post, $cur_posting);
		}

		// If we previously found out that the email was banned
		if ($pun_user['is_guest'] && $banned_email && $pun_config['o_mailing_list'] != '')
			warn_banned_user($post, $new_pid);

		// If the posting user is logged in, increment his/her post count
		increment_post_count($post);

		redirect('viewtopic.php?pid='.$new_pid.'#p'.$new_pid, $lang_post['Post redirect']);
	}
}


// If a topic ID was specified in the url (it's a reply)
if ($tid)
{
	$action = $lang_post['Post a reply'];
	$form = '<form id="post" method="post" action="post.php?action=post&amp;tid='.$tid.'" onsubmit="this.submit.disabled=true;if(process_form(this)){return true;}else{this.submit.disabled=false;return false;}">';

	// If a quote ID was specified in the url
	if (isset($_GET['qid']))
		$quote = get_quote_message($_GET['qid'], $tid);
}
// If a forum ID was specified in the url (new topic)
else if ($fid)
{
	$action = $lang_post['Post new topic'];
	$form = '<form id="post" method="post" action="post.php?action=post&amp;fid='.$fid.'" onsubmit="return process_form(this)">';
}
else
	message($lang_common['Bad request'], false, '404 Not Found');


$page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), $action);
$required_fields = array('req_email' => $lang_common['Email'], 'req_subject' => $lang_common['Subject'], 'req_message' => $lang_common['Message']);
$focus_element = array('post');

if (!$pun_user['is_guest'])
	$focus_element[] = ($fid) ? 'req_subject' : 'req_message';
else
{
	$required_fields['req_username'] = $lang_post['Guest name'];
	$focus_element[] = 'req_username';
}

define('PUN_ACTIVE_PAGE', 'index');
require PUN_ROOT.'header.php';

// Get the current state of checkboxes
$checkboxes = get_checkboxes($_POST, $fid, $is_admmod);

// Check to see if the topic review is to be displayed
if ($tid && $pun_config['o_topic_review'] != '0')
	$post_data = topic_review($tid);

// Load the post.php view file
require PUN_ROOT.'view/post.php';

require PUN_ROOT.'footer.php';
