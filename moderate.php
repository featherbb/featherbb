<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

define('PUN_ROOT', dirname(__FILE__).'/');
require PUN_ROOT.'include/common.php';

// Load the moderate.php model file
require PUN_ROOT.'model/moderate.php';


// This particular function doesn't require forum-based moderator access. It can be used
// by all moderators and admins
if (isset($_GET['get_host'])) {
    if (!$pun_user['is_admmod']) {
        message($lang_common['No permission'], false, '403 Forbidden');
    }

    display_ip_address($_GET);
}


// All other functions require moderator/admin access
$fid = isset($_GET['fid']) ? intval($_GET['fid']) : 0;
if ($fid < 1) {
    message($lang_common['Bad request'], false, '404 Not Found');
}

$moderators = get_moderators($fid);
$mods_array = ($moderators != '') ? unserialize($moderators) : array();

if ($pun_user['g_id'] != PUN_ADMIN && ($pun_user['g_moderator'] == '0' || !array_key_exists($pun_user['username'], $mods_array))) {
    message($lang_common['No permission'], false, '403 Forbidden');
}

// Get topic/forum tracking data
if (!$pun_user['is_guest']) {
    $tracked_topics = get_tracked_topics();
}

// Load the misc.php language file
require PUN_ROOT.'lang/'.$pun_user['language'].'/misc.php';


// All other topic moderation features require a topic ID in GET
if (isset($_GET['tid'])) {
    $tid = intval($_GET['tid']);
    if ($tid < 1) {
        message($lang_common['Bad request'], false, '404 Not Found');
    }

    $cur_topic = get_topic_info($fid, $tid);

    // Delete one or more posts
    if (isset($_POST['delete_posts']) || isset($_POST['delete_posts_comply'])) {
        $posts = delete_posts($_POST, $tid, $fid);

        $page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), $lang_misc['Moderate']);
        define('PUN_ACTIVE_PAGE', 'index');
        require PUN_ROOT.'header.php';

        // Load the moderate.php view file
        require PUN_ROOT.'view/moderate/delete_posts.php';

        require PUN_ROOT.'footer.php';
    } elseif (isset($_POST['split_posts']) || isset($_POST['split_posts_comply'])) {
        $posts = split_posts($_POST, $tid, $fid);

        $page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), $lang_misc['Moderate']);
        $focus_element = array('subject','new_subject');
        define('PUN_ACTIVE_PAGE', 'index');
        require PUN_ROOT.'header.php';
        
        // Load the moderate.php view file
        require PUN_ROOT.'view/moderate/split_posts.php';

        require PUN_ROOT.'footer.php';
    }

    // Show the moderate posts view

    // Load the viewtopic.php language file
    require PUN_ROOT.'lang/'.$pun_user['language'].'/topic.php';

    // Used to disable the Move and Delete buttons if there are no replies to this topic
    $button_status = ($cur_topic['num_replies'] == 0) ? ' disabled="disabled"' : '';

    if (isset($_GET['action']) && $_GET['action'] == 'all') {
        $pun_user['disp_posts'] = $cur_topic['num_replies'] + 1;
    }

    // Determine the post offset (based on $_GET['p'])
    $num_pages = ceil(($cur_topic['num_replies'] + 1) / $pun_user['disp_posts']);

    $p = (!isset($_GET['p']) || $_GET['p'] <= 1 || $_GET['p'] > $num_pages) ? 1 : intval($_GET['p']);
    $start_from = $pun_user['disp_posts'] * ($p - 1);

    // Generate paging links
    $paging_links = '<span class="pages-label">'.$lang_common['Pages'].' </span>'.paginate($num_pages, $p, 'moderate.php?fid='.$fid.'&amp;tid='.$tid);

    if ($pun_config['o_censoring'] == '1') {
        $cur_topic['subject'] = censor_words($cur_topic['subject']);
    }

    $page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), pun_htmlspecialchars($cur_topic['forum_name']), pun_htmlspecialchars($cur_topic['subject']));
    define('PUN_ACTIVE_PAGE', 'index');
    require PUN_ROOT.'header.php';

    $post_data = display_posts_view($tid, $start_from);
    
    // Load the moderate.php view file
    require PUN_ROOT.'view/moderate/posts_view.php';

    require PUN_ROOT.'footer.php';
}


// Move one or more topics
if (isset($_REQUEST['move_topics']) || isset($_POST['move_topics_to'])) {
    if (isset($_POST['move_topics_to'])) {
        move_topics_to($_POST, $tid, $fid);
    }

    if (isset($_POST['move_topics'])) {
        $topics = isset($_POST['topics']) ? $_POST['topics'] : array();
        if (empty($topics)) {
            message($lang_misc['No topics selected']);
        }

        $topics = implode(',', array_map('intval', array_keys($topics)));
        $action = 'multi';
    } else {
        $topics = intval($_GET['move_topics']);
        if ($topics < 1) {
            message($lang_common['Bad request'], false, '404 Not Found');
        }

        $action = 'single';
    }

    // Check if there are enough forums to move the topic
    check_move_possible();

    $page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), $lang_misc['Moderate']);
    define('PUN_ACTIVE_PAGE', 'index');
    require PUN_ROOT.'header.php';
    
    // Load the moderate.php view file
    require PUN_ROOT.'view/moderate/move_topics.php';

    require PUN_ROOT.'footer.php';
}

// Merge two or more topics
elseif (isset($_POST['merge_topics']) || isset($_POST['merge_topics_comply'])) {
    if (isset($_POST['merge_topics_comply'])) {
        merge_topics($_POST, $fid);
    }

    $topics = isset($_POST['topics']) ? $_POST['topics'] : array();
    if (count($topics) < 2) {
        message($lang_misc['Not enough topics selected']);
    }

    $page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), $lang_misc['Moderate']);
    define('PUN_ACTIVE_PAGE', 'index');
    require PUN_ROOT.'header.php';
    
    // Load the moderate.php view file
    require PUN_ROOT.'view/moderate/merge_topics.php';
    
    require PUN_ROOT.'footer.php';
}

// Delete one or more topics
elseif (isset($_POST['delete_topics']) || isset($_POST['delete_topics_comply'])) {
    $topics = isset($_POST['topics']) ? $_POST['topics'] : array();
    if (empty($topics)) {
        message($lang_misc['No topics selected']);
    }

    if (isset($_POST['delete_topics_comply'])) {
        delete_topics($topics, $fid);
    }

    $page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), $lang_misc['Moderate']);
    define('PUN_ACTIVE_PAGE', 'index');
    require PUN_ROOT.'header.php';

    // Load the moderate.php view file
    require PUN_ROOT.'view/moderate/delete_topics.php';

    require PUN_ROOT.'footer.php';
}


// Open or close one or more topics
elseif (isset($_REQUEST['open']) || isset($_REQUEST['close'])) {
    $action = (isset($_REQUEST['open'])) ? 0 : 1;

    // There could be an array of topic IDs in $_POST
    if (isset($_POST['open']) || isset($_POST['close'])) {
        confirm_referrer('moderate.php');

        $topics = isset($_POST['topics']) ? @array_map('intval', @array_keys($_POST['topics'])) : array();
        if (empty($topics)) {
            message($lang_misc['No topics selected']);
        }

        $db->query('UPDATE '.$db->prefix.'topics SET closed='.$action.' WHERE id IN('.implode(',', $topics).') AND forum_id='.$fid) or error('Unable to close topics', __FILE__, __LINE__, $db->error());

        $redirect_msg = ($action) ? $lang_misc['Close topics redirect'] : $lang_misc['Open topics redirect'];
        redirect('moderate.php?fid='.$fid, $redirect_msg);
    }
    // Or just one in $_GET
    else {
        confirm_referrer('viewtopic.php');

        $topic_id = ($action) ? intval($_GET['close']) : intval($_GET['open']);
        if ($topic_id < 1) {
            message($lang_common['Bad request'], false, '404 Not Found');
        }

        $db->query('UPDATE '.$db->prefix.'topics SET closed='.$action.' WHERE id='.$topic_id.' AND forum_id='.$fid) or error('Unable to close topic', __FILE__, __LINE__, $db->error());

        $redirect_msg = ($action) ? $lang_misc['Close topic redirect'] : $lang_misc['Open topic redirect'];
        redirect('viewtopic.php?id='.$topic_id, $redirect_msg);
    }
}


// Stick a topic
elseif (isset($_GET['stick'])) {
    confirm_referrer('viewtopic.php');

    $stick = intval($_GET['stick']);
    if ($stick < 1) {
        message($lang_common['Bad request'], false, '404 Not Found');
    }

    $db->query('UPDATE '.$db->prefix.'topics SET sticky=\'1\' WHERE id='.$stick.' AND forum_id='.$fid) or error('Unable to stick topic', __FILE__, __LINE__, $db->error());

    redirect('viewtopic.php?id='.$stick, $lang_misc['Stick topic redirect']);
}


// Unstick a topic
elseif (isset($_GET['unstick'])) {
    confirm_referrer('viewtopic.php');

    $unstick = intval($_GET['unstick']);
    if ($unstick < 1) {
        message($lang_common['Bad request'], false, '404 Not Found');
    }

    $db->query('UPDATE '.$db->prefix.'topics SET sticky=\'0\' WHERE id='.$unstick.' AND forum_id='.$fid) or error('Unable to unstick topic', __FILE__, __LINE__, $db->error());

    redirect('viewtopic.php?id='.$unstick, $lang_misc['Unstick topic redirect']);
}


// No specific forum moderation action was specified in the query string, so we'll display the moderator forum

// Load the viewforum.php language file
require PUN_ROOT.'lang/'.$pun_user['language'].'/forum.php';

// Fetch some info about the forum
$cur_forum = get_forum_info($fid);

// Is this a redirect forum? In that case, abort!
if ($cur_forum['redirect_url'] != '') {
    message($lang_common['Bad request'], false, '404 Not Found');
}

$sort_by = forum_sort_by($cur_forum['sort_by']);

// Determine the topic offset (based on $_GET['p'])
$num_pages = ceil($cur_forum['num_topics'] / $pun_user['disp_topics']);

$p = (!isset($_GET['p']) || $_GET['p'] <= 1 || $_GET['p'] > $num_pages) ? 1 : intval($_GET['p']);
$start_from = $pun_user['disp_topics'] * ($p - 1);

// Generate paging links
$paging_links = '<span class="pages-label">'.$lang_common['Pages'].' </span>'.paginate($num_pages, $p, 'moderate.php?fid='.$fid);

$page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), pun_htmlspecialchars($cur_forum['forum_name']));
define('PUN_ACTIVE_PAGE', 'index');
require PUN_ROOT.'header.php';

$topic_data = display_topics($fid, $sort_by, $start_from);

// Load the moderate.php view file
require PUN_ROOT.'view/moderate/moderator_forum.php';

require PUN_ROOT.'footer.php';
