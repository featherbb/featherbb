<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

define('PUN_ROOT', dirname(__FILE__).'/');
require PUN_ROOT.'include/common.php';

// Include UTF-8 function
require PUN_ROOT.'include/utf8/substr_replace.php';
require PUN_ROOT.'include/utf8/ucwords.php'; // utf8_ucwords needs utf8_substr_replace
require PUN_ROOT.'include/utf8/strcasecmp.php';

$action = isset($_GET['action']) ? $_GET['action'] : null;
$section = isset($_GET['section']) ? $_GET['section'] : null;
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id < 2) {
    message($lang_common['Bad request'], false, '404 Not Found');
}

if ($action != 'change_pass' || !isset($_GET['key'])) {
    if ($pun_user['g_read_board'] == '0') {
        message($lang_common['No view'], false, '403 Forbidden');
    } elseif ($pun_user['g_view_users'] == '0' && ($pun_user['is_guest'] || $pun_user['id'] != $id)) {
        message($lang_common['No permission'], false, '403 Forbidden');
    }
}

// Load the prof_reg.php language file
require PUN_ROOT.'lang/'.$pun_user['language'].'/prof_reg.php';

// Load the profile.php language file
require PUN_ROOT.'lang/'.$pun_user['language'].'/profile.php';

// Load the profile.php model file
require PUN_ROOT.'model/profile.php';


 elseif (isset($_POST['update_group_membership'])) {
    if ($pun_user['g_id'] > PUN_ADMIN) {
        message($lang_common['No permission'], false, '403 Forbidden');
    }

    update_group_membership($id, $_POST);
} elseif (isset($_POST['update_forums'])) {
    if ($pun_user['g_id'] > PUN_ADMIN) {
        message($lang_common['No permission'], false, '403 Forbidden');
    }

    update_mod_forums($id, $feather);
} elseif (isset($_POST['ban'])) {
    if ($pun_user['g_id'] != PUN_ADMIN && ($pun_user['g_moderator'] != '1' || $pun_user['g_mod_ban_users'] == '0')) {
        message($lang_common['No permission'], false, '403 Forbidden');
    }

    ban_user($id);
} elseif (isset($_POST['delete_user']) || isset($_POST['delete_user_comply'])) {
    if ($pun_user['g_id'] > PUN_ADMIN) {
        message($lang_common['No permission'], false, '403 Forbidden');
    }
    
    delete_user($id, $feather);

    $page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), $lang_common['Profile'], $lang_profile['Confirm delete user']);
    define('PUN_ACTIVE_PAGE', 'profile');
    require PUN_ROOT.'header.php';
    
    // Load the view.php view file
    require PUN_ROOT.'view/profile/delete_user.php';

    require PUN_ROOT.'footer.php';
} elseif (isset($_POST['form_sent'])) {
    // Fetch the user group of the user we are editing
    $info = fetch_user_group($id);

    if ($pun_user['id'] != $id &&                                                                    // If we aren't the user (i.e. editing your own profile)
        (!$pun_user['is_admmod'] ||                                                                    // and we are not an admin or mod
        ($pun_user['g_id'] != PUN_ADMIN &&                                                            // or we aren't an admin and ...
        ($pun_user['g_mod_edit_users'] == '0' ||                                                    // mods aren't allowed to edit users
        $info['group_id'] == PUN_ADMIN ||                                                                    // or the user is an admin
        $info['is_moderator'])))) {                                                                            // or the user is another mod
        message($lang_common['No permission'], false, '403 Forbidden');
    }

    update_profile($id, $info, $section, $feather);
}