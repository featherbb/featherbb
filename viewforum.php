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


$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id < 1)
	message($lang_common['Bad request'], false, '404 Not Found');

// Load the viewforum.php language file
require PUN_ROOT.'lang/'.$pun_user['language'].'/forum.php';

// Load the viewforum.php model file
require PUN_ROOT.'model/viewforum.php';

// Fetch some informations about the forum
$cur_forum = get_info_forum($id);

// Is this a redirect forum? In that case, redirect!
if ($cur_forum['redirect_url'] != '')
{
	header('Location: '.$cur_forum['redirect_url']);
	exit;
}

// Sort out who the moderators are and if we are currently a moderator (or an admin)
$mods_array = ($cur_forum['moderators'] != '') ? unserialize($cur_forum['moderators']) : array();
$is_admmod = ($pun_user['g_id'] == PUN_ADMIN || ($pun_user['g_moderator'] == '1' && array_key_exists($pun_user['username'], $mods_array))) ? true : false;

$sort_by = sort_forum_by($cur_forum['sort_by']);

// Can we or can we not post new topics?
if (($cur_forum['post_topics'] == '' && $pun_user['g_post_topics'] == '1') || $cur_forum['post_topics'] == '1' || $is_admmod)
	$post_link = "\t\t\t".'<p class="postlink conr"><a href="post.php?fid='.$id.'">'.$lang_forum['Post topic'].'</a></p>'."\n";
else
	$post_link = '';

// Determine the topic offset (based on $_GET['p'])
$num_pages = ceil($cur_forum['num_topics'] / $pun_user['disp_topics']);

$p = (!isset($_GET['p']) || $_GET['p'] <= 1 || $_GET['p'] > $num_pages) ? 1 : intval($_GET['p']);
$start_from = $pun_user['disp_topics'] * ($p - 1);

// Generate paging links
$paging_links = '<span class="pages-label">'.$lang_common['Pages'].' </span>'.paginate($num_pages, $p, 'viewforum.php?id='.$id);

// Add relationship meta tags
$page_head = get_page_head($id, $num_pages, $p);

$forum_actions = get_forum_actions($id, $pun_config['o_forum_subscriptions'], $cur_forum['is_subscribed']);

$page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), pun_htmlspecialchars($cur_forum['forum_name']));
define('PUN_ALLOW_INDEX', 1);
define('PUN_ACTIVE_PAGE', 'index');
require PUN_ROOT.'header.php';

// Print topics
$forum_data = print_topics($id, $sort_by, $start_from);

// Load the index.php view file
require PUN_ROOT.'view/viewforum.php';

$forum_id = $id;
$footer_style = 'viewforum';
require PUN_ROOT.'footer.php';
