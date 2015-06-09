<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

if (isset($_GET['action'])) {
    define('PUN_QUIET_VISIT', 1);
}

define('PUN_ROOT', dirname(__FILE__).'/');
require PUN_ROOT.'include/common.php';


// Load the login.php language file
require PUN_ROOT.'lang/'.$pun_user['language'].'/login.php';

// Load the login.php model file
require PUN_ROOT.'model/login.php';

$action = isset($_GET['action']) ? $_GET['action'] : null;

if (isset($_POST['form_sent']) && $action == 'in') {
    login($_POST);
} elseif ($action == 'out') {
    logout($_GET);
} elseif ($action == 'forget' || $action == 'forget_2') {
    $errors = password_forgotten($_POST);

    $page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), $lang_login['Request pass']);
    $required_fields = array('req_email' => $lang_common['Email']);
    $focus_element = array('request_pass', 'req_email');

    define('PUN_ACTIVE_PAGE', 'login');
    require PUN_ROOT.'header.php';

    // Load the login view file
    require PUN_ROOT.'view/login/password_forgotten.php';

    require PUN_ROOT.'footer.php';
}


if (!$pun_user['is_guest']) {
    header('Location: index.php');
    exit;
}

// Try to determine if the data in HTTP_REFERER is valid (if not, we redirect to index.php after login)
$redirect_url = get_redirect_url($_SERVER);

$page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), $lang_common['Login']);
$required_fields = array('req_username' => $lang_common['Username'], 'req_password' => $lang_common['Password']);
$focus_element = array('login', 'req_username');

define('PUN_ACTIVE_PAGE', 'login');
require PUN_ROOT.'header.php';

// Load the login view file
require PUN_ROOT.'view/login/form.php';

require PUN_ROOT.'footer.php';
