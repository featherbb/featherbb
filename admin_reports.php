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


if (!$pun_user['is_admmod']) {
    message($lang_common['No permission'], false, '403 Forbidden');
}

// Load the admin_reports.php language file
require PUN_ROOT.'lang/'.$admin_language.'/admin_reports.php';

// Load the admin_reports.php model file
require PUN_ROOT.'model/admin_reports.php';

// Zap a report
if (isset($_POST['zap_id'])) {
	zap_report($_POST);
}


$page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), $lang_admin_common['Admin'], $lang_admin_common['Reports']);
define('PUN_ACTIVE_PAGE', 'admin');
require PUN_ROOT.'header.php';

generate_admin_menu('reports');

// Load the admin_index.php view file
require PUN_ROOT.'view/admin_reports.php';

require PUN_ROOT.'footer.php';
