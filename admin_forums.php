<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

// Tell header.php to use the admin template
define('PUN_ADMIN_CONSOLE', 1);

define('PUN_ROOT', dirname(__FILE__).'/');
require PUN_ROOT.'include/common.php';
require PUN_ROOT.'include/common_admin.php';


if ($pun_user['g_id'] != PUN_ADMIN) {
    message($lang_common['No permission'], false, '403 Forbidden');
}

// Load the admin_forums.php language file
require PUN_ROOT.'lang/'.$admin_language.'/admin_forums.php';

// Load the admin_forums.php model file
require PUN_ROOT.'model/admin_forums.php';

// Add a "default" forum
if (isset($_POST['add_forum'])) {
    add_forum($_POST);
}

// Delete a forum
elseif (isset($_GET['del_forum'])) {
    confirm_referrer('admin_forums.php');

    $forum_id = intval($_GET['del_forum']);
    if ($forum_id < 1) {
        message($lang_common['Bad request'], false, '404 Not Found');
    }

    if (isset($_POST['del_forum_comply'])) { // Delete a forum with all posts
        delete_forum($_POST, $forum_id);
    } else {
        // If the user hasn't confirmed the delete

        $forum_name = get_forum_name($forum_id);

        $page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), $lang_admin_common['Admin'], $lang_admin_common['Forums']);
        define('PUN_ACTIVE_PAGE', 'admin');
        require PUN_ROOT.'header.php';

        generate_admin_menu('forums');

        // Load the admin_forums.php view file
        require PUN_ROOT.'view/admin_forums/delete_forum.php';

        require PUN_ROOT.'footer.php';
    }
}

// Update forum positions
elseif (isset($_POST['update_positions'])) {
    update_positions($_POST);
} elseif (isset($_GET['edit_forum'])) {
    $forum_id = intval($_GET['edit_forum']);
    if ($forum_id < 1) {
        message($lang_common['Bad request'], false, '404 Not Found');
    }

    // Update group permissions for $forum_id
    if (isset($_POST['save'])) {
        update_permissions($_POST, $forum_id);
    } elseif (isset($_POST['revert_perms'])) {
        revert_permissions($forum_id);
    }

    // Fetch forum info
    $cur_forum = get_forum_info($forum_id);

    $page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), $lang_admin_common['Admin'], $lang_admin_common['Forums']);
    define('PUN_ACTIVE_PAGE', 'admin');
    require PUN_ROOT.'header.php';

    generate_admin_menu('forums');

    // Load the admin_forums.php view file
    require PUN_ROOT.'view/admin_forums/permissions.php';

    require PUN_ROOT.'footer.php';
}

$page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), $lang_admin_common['Admin'], $lang_admin_common['Forums']);
define('PUN_ACTIVE_PAGE', 'admin');
require PUN_ROOT.'header.php';

generate_admin_menu('forums');

// Load the admin_forums.php view file
require PUN_ROOT.'view/admin_forums/admin_forums.php';

require PUN_ROOT.'footer.php';
