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

// Load the admin_censoring.php language file
require PUN_ROOT.'lang/'.$admin_language.'/admin_censoring.php';

// Load the admin_censoring.php model file
require PUN_ROOT.'model/admin_censoring.php';

// Add a censor word
if (isset($_POST['add_word'])) {
    add_word($_POST);
}

// Update a censor word
elseif (isset($_POST['update'])) {
    update_word($_POST);
}

// Remove a censor word
elseif (isset($_POST['remove'])) {
    remove_word($_POST);
}

$page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), $lang_admin_common['Admin'], $lang_admin_common['Censoring']);
$focus_element = array('censoring', 'new_search_for');
define('PUN_ACTIVE_PAGE', 'admin');
require PUN_ROOT.'header.php';

generate_admin_menu('censoring');

$is_word = check_words();

if ($is_word) {
    $word_data = get_words();
}

// Load the admin_censoring.php view file
require PUN_ROOT.'view/admin_censoring.php';

require PUN_ROOT.'footer.php';
