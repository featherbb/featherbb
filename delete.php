<?php

/**
 * Copyright (C) 2008-2012 FluxBB
 * based on code by Rickard Andersson copyright (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

define('PUN_ROOT', dirname(__FILE__).'/');
require PUN_ROOT.'include/common.php';


if ($pun_user['g_read_board'] == '0')
	message($lang_common['No view'], false, '403 Forbidden');


$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id < 1)
	message($lang_common['Bad request'], false, '404 Not Found');

// Load the delete.php model file
require PUN_ROOT.'model/delete.php';

// Fetch some informations about the post, the topic and the forum
$cur_post = get_info_delete($id);

if ($pun_config['o_censoring'] == '1')
	$cur_post['subject'] = censor_words($cur_post['subject']);

// Sort out who the moderators are and if we are currently a moderator (or an admin)
$mods_array = ($cur_post['moderators'] != '') ? unserialize($cur_post['moderators']) : array();
$is_admmod = ($pun_user['g_id'] == PUN_ADMIN || ($pun_user['g_moderator'] == '1' && array_key_exists($pun_user['username'], $mods_array))) ? true : false;

$is_topic_post = ($id == $cur_post['first_post_id']) ? true : false;

// Do we have permission to edit this post?
if (($pun_user['g_delete_posts'] == '0' ||
	($pun_user['g_delete_topics'] == '0' && $is_topic_post) ||
	$cur_post['poster_id'] != $pun_user['id'] ||
	$cur_post['closed'] == '1') &&
	!$is_admmod)
	message($lang_common['No permission'], false, '403 Forbidden');

if ($is_admmod && $pun_user['g_id'] != PUN_ADMIN && in_array($cur_post['poster_id'], get_admin_ids()))
	message($lang_common['No permission'], false, '403 Forbidden');

// Load the delete.php language file
require PUN_ROOT.'lang/'.$pun_user['language'].'/delete.php';


if (isset($_POST['delete']))
	handle_deletion($is_topic_post, $id, $cur_post['tid'], $cur_post['fid']);


$page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), $lang_delete['Delete post']);
define ('PUN_ACTIVE_PAGE', 'index');
require PUN_ROOT.'header.php';

require PUN_ROOT.'include/parser.php';
$cur_post['message'] = parse_message($cur_post['message'], $cur_post['hide_smilies']);

// Load the delete.php view file
require PUN_ROOT.'view/delete.php';

require PUN_ROOT.'footer.php';
