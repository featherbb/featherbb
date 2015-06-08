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

// Load the admin_categories.php language file
require PUN_ROOT.'lang/'.$admin_language.'/admin_categories.php';

// Load the admin_categories.php model file
require PUN_ROOT.'model/admin_categories.php';

// Add a new category
if (isset($_POST['add_cat'])) {
    add_category($_POST);
}

// Delete a category
elseif (isset($_POST['del_cat']) || isset($_POST['del_cat_comply'])) {
    confirm_referrer('admin_categories.php');

    $cat_to_delete = intval($_POST['cat_to_delete']);
    if ($cat_to_delete < 1) {
        message($lang_common['Bad request'], false, '404 Not Found');
    }

    if (isset($_POST['del_cat_comply'])) { // Delete a category with all forums and posts
        delete_category($cat_to_delete);
    } else {
        // If the user hasn't confirmed the delete

        $cat_name = get_category_name($cat_to_delete);

        $page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), $lang_admin_common['Admin'], $lang_admin_common['Categories']);
        define('PUN_ACTIVE_PAGE', 'admin');
        require PUN_ROOT.'header.php';

        generate_admin_menu('categories');

        // Load the admin_categories.php view file
        require PUN_ROOT.'view/admin_categories/delete_category.php';

        require PUN_ROOT.'footer.php';
    }
} elseif (isset($_POST['update'])) {// Change position and name of the categories
    confirm_referrer('admin_categories.php');

    $categories = $_POST['cat'];
    if (empty($categories)) {
        message($lang_common['Bad request'], false, '404 Not Found');
    }

    update_categories($categories);
}

// Generate an array with all categories
$cat_list = get_cat_list();

$page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), $lang_admin_common['Admin'], $lang_admin_common['Categories']);
define('PUN_ACTIVE_PAGE', 'admin');
require PUN_ROOT.'header.php';

generate_admin_menu('categories');

// Load the admin_categories.php view file
require PUN_ROOT.'view/admin_categories/admin_categories.php';

require PUN_ROOT.'footer.php';
