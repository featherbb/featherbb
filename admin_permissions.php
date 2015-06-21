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

// Load the admin_permissions.php language file
require PUN_ROOT.'lang/'.$admin_language.'/admin_permissions.php';

// Load the admin_permissions.php model file
require PUN_ROOT.'model/admin_permissions.php';

if (isset($_POST['form_sent'])) {
	update_permissions($_POST);
}

$page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), $lang_admin_common['Admin'], $lang_admin_common['Permissions']);
define('PUN_ACTIVE_PAGE', 'admin');
require PUN_ROOT.'include/header.php';

generate_admin_menu('permissions');

// Load the admin_options.php view file
require PUN_ROOT.'view/admin_permissions.php';

require PUN_ROOT.'include/footer.php';
