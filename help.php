<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

// Tell header.php to use the help template
define('PUN_HELP', 1);

define('PUN_ROOT', dirname(__FILE__).'/');
require PUN_ROOT.'include/common.php';


if ($pun_user['g_read_board'] == '0') {
    message($lang_common['No view'], false, '403 Forbidden');
}


// Load the help.php language file
require PUN_ROOT.'lang/'.$pun_user['language'].'/help.php';


$page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), $lang_help['Help']);
define('PUN_ACTIVE_PAGE', 'help');
require PUN_ROOT.'header.php';

// Load the help.php view file
require PUN_ROOT.'view/help.php';

require PUN_ROOT.'footer.php';
