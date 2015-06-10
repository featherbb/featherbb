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

// Load the admin_options.php language file
require PUN_ROOT.'lang/'.$admin_language.'/admin_options.php';

// Load the admin_options.php model file
require PUN_ROOT.'model/admin_options.php';

if (isset($_POST['form_sent'])) {
	update_options($_POST);
}

$page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), $lang_admin_common['Admin'], $lang_admin_common['Options']);
define('PUN_ACTIVE_PAGE', 'admin');
require PUN_ROOT.'header.php';

generate_admin_menu('options');

// Load the admin_options.php view file
require PUN_ROOT.'view/admin_options.php';

require PUN_ROOT.'footer.php';
