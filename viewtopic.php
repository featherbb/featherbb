<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

define('PUN_ROOT', dirname(__FILE__).'/');
require PUN_ROOT.'include/common.php';


if ($pun_user['g_read_board'] == '0') {
    message($lang_common['No view'], false, '403 Forbidden');
}


$action = isset($_GET['action']) ? $_GET['action'] : null;
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$pid = isset($_GET['pid']) ? intval($_GET['pid']) : 0;
if ($id < 1 && $pid < 1) {
    message($lang_common['Bad request'], false, '404 Not Found');
}

// Load the viewtopic.php language file
require PUN_ROOT.'lang/'.$pun_user['language'].'/topic.php';

// Load the post.php language file
require PUN_ROOT.'lang/'.$pun_user['language'].'/post.php';

// Load the viewtopic.php model file
require PUN_ROOT.'model/viewtopic.php';


// If a post ID is specified we determine topic ID and page number so we can redirect to the correct message
if ($pid) {
    $post = redirect_to_post($pid);
    $id = $post['topic_id'];
    $_GET['p'] = $post['get_p'];
} else {
    handle_actions($action, $id);
}


// Fetch some informations about the topic
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

$p = (!isset($_GET['p']) || $_GET['p'] <= 1 || $_GET['p'] > $num_pages) ? 1 : intval($_GET['p']);
$start_from = $pun_user['disp_posts'] * ($p - 1);

// Generate paging links
$paging_links = '<span class="pages-label">'.$lang_common['Pages'].' </span>'.paginate($num_pages, $p, 'viewtopic.php?id='.$id);


if ($pun_config['o_censoring'] == '1') {
    $cur_topic['subject'] = censor_words($cur_topic['subject']);
}

$quickpost = is_quickpost($cur_topic['post_replies'], $cur_topic['closed'], $is_admmod);

$subscraction = get_subscraction($cur_topic['is_subscribed'], $id);

// Add relationship meta tags
$page_head = get_page_head($id, $num_pages, $p);

$page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), pun_htmlspecialchars($cur_topic['forum_name']), pun_htmlspecialchars($cur_topic['subject']));
define('PUN_ALLOW_INDEX', 1);
define('PUN_ACTIVE_PAGE', 'index');
require PUN_ROOT.'header.php';

require PUN_ROOT.'include/parser.php';

// Print the topic itself
$post_data = print_posts($id, $start_from, $cur_topic, $is_admmod);

// Load the viewtopic.php view file
require PUN_ROOT.'view/viewtopic.php';

// Increment "num_views" for topic
if ($pun_config['o_topic_views'] == '1') {
    $db->query('UPDATE '.$db->prefix.'topics SET num_views=num_views+1 WHERE id='.$id) or error('Unable to update topic', __FILE__, __LINE__, $db->error());
}

$forum_id = $cur_topic['forum_id'];
$footer_style = 'viewtopic';
require PUN_ROOT.'footer.php';
