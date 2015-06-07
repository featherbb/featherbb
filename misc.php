<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

if (isset($_GET['action']))
	define('PUN_QUIET_VISIT', 1);

define('PUN_ROOT', dirname(__FILE__).'/');
require PUN_ROOT.'include/common.php';


// Load the misc.php language file
require PUN_ROOT.'lang/'.$pun_user['language'].'/misc.php';

// Load the misc.php model file
require PUN_ROOT.'model/misc.php';

$action = isset($_GET['action']) ? $_GET['action'] : null;


if ($action == 'rules')
{
	if ($pun_config['o_rules'] == '0' || ($pun_user['is_guest'] && $pun_user['g_read_board'] == '0' && $pun_config['o_regs_allow'] == '0'))
		message($lang_common['Bad request'], false, '404 Not Found');

	// Load the register.php language file
	require PUN_ROOT.'lang/'.$pun_user['language'].'/register.php';

	$page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), $lang_register['Forum rules']);
	define('PUN_ACTIVE_PAGE', 'rules');
	require PUN_ROOT.'header.php';

	// Load the misc view file
	require PUN_ROOT.'view/misc/rules.php';
	
	require PUN_ROOT.'footer.php';
}


else if ($action == 'markread')
{
	if ($pun_user['is_guest'])
		message($lang_common['No permission'], false, '403 Forbidden');

	update_last_visit();

	// Reset tracked topics
	set_tracked_topics(null);

	redirect('index.php', $lang_misc['Mark read redirect']);
}


// Mark the topics/posts in a forum as read?
else if ($action == 'markforumread')
{
	if ($pun_user['is_guest'])
		message($lang_common['No permission'], false, '403 Forbidden');

	$fid = isset($_GET['fid']) ? intval($_GET['fid']) : 0;
	if ($fid < 1)
		message($lang_common['Bad request'], false, '404 Not Found');

	$tracked_topics = get_tracked_topics();
	$tracked_topics['forums'][$fid] = time();
	set_tracked_topics($tracked_topics);

	redirect('viewforum.php?id='.$fid, $lang_misc['Mark forum read redirect']);
}


else if (isset($_GET['email']))
{
	if ($pun_user['is_guest'] || $pun_user['g_send_email'] == '0')
		message($lang_common['No permission'], false, '403 Forbidden');

	$recipient_id = intval($_GET['email']);
	if ($recipient_id < 2)
		message($lang_common['Bad request'], false, '404 Not Found');

	$mail = get_info_mail($recipient_id);

	if ($mail['email_setting'] == 2 && !$pun_user['is_admmod'])
		message($lang_misc['Form email disabled']);


	if (isset($_POST['form_sent']))
		send_email($_POST);

	$redirect_url = get_redirect_url($_SERVER, $recipient_id);

	$page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), $lang_misc['Send email to'].' '.pun_htmlspecialchars($recipient));
	$required_fields = array('req_subject' => $lang_misc['Email subject'], 'req_message' => $lang_misc['Email message']);
	$focus_element = array('email', 'req_subject');
	define('PUN_ACTIVE_PAGE', 'index');
	require PUN_ROOT.'header.php';
	
	// Load the misc view file
	require PUN_ROOT.'view/misc/email.php';

	require PUN_ROOT.'footer.php';
}


else if (isset($_GET['report']))
{
	if ($pun_user['is_guest'])
		message($lang_common['No permission'], false, '403 Forbidden');

	$post_id = intval($_GET['report']);
	if ($post_id < 1)
		message($lang_common['Bad request'], false, '404 Not Found');

	if (isset($_POST['form_sent']))
		insert_report($_POST);

	// Fetch some info about the post, the topic and the forum
	$cur_post = get_info_report();

	if ($pun_config['o_censoring'] == '1')
		$cur_post['subject'] = censor_words($cur_post['subject']);

	$page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), $lang_misc['Report post']);
	$required_fields = array('req_reason' => $lang_misc['Reason']);
	$focus_element = array('report', 'req_reason');
	define('PUN_ACTIVE_PAGE', 'index');
	require PUN_ROOT.'header.php';

	// Load the misc view file
	require PUN_ROOT.'view/misc/report.php';

	require PUN_ROOT.'footer.php';
}


else if ($action == 'subscribe')
{
	if ($pun_user['is_guest'])
		message($lang_common['No permission'], false, '403 Forbidden');

	$topic_id = isset($_GET['tid']) ? intval($_GET['tid']) : 0;
	$forum_id = isset($_GET['fid']) ? intval($_GET['fid']) : 0;
	if ($topic_id < 1 && $forum_id < 1)
		message($lang_common['Bad request'], false, '404 Not Found');

	if ($topic_id)
		subscribe_topic($topic_id);

	if ($forum_id)
		subscribe_forum($forum_id);
}


else if ($action == 'unsubscribe')
{
	if ($pun_user['is_guest'])
		message($lang_common['No permission'], false, '403 Forbidden');

	$topic_id = isset($_GET['tid']) ? intval($_GET['tid']) : 0;
	$forum_id = isset($_GET['fid']) ? intval($_GET['fid']) : 0;
	if ($topic_id < 1 && $forum_id < 1)
		message($lang_common['Bad request'], false, '404 Not Found');

	if ($topic_id)
		unsubscribe_topic($topic_id);

	if ($forum_id)
		unsubscribe_forum($forum_id);
}


else
	message($lang_common['Bad request'], false, '404 Not Found');
