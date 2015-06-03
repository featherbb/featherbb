<?php

/**
 * Copyright (C) 2008-2012 FluxBB
 * based on code by Rickard Andersson copyright (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

define('PUN_ROOT', dirname(__FILE__).'/');
require PUN_ROOT.'include/common.php';


if ($pun_user['g_read_board'] == '0')
	message($lang_common['No view'], false, '403 Forbidden');


// Load the index.php language file
require PUN_ROOT.'lang/'.$pun_user['language'].'/index.php';

// Load the index.php model file
require PUN_ROOT.'model/index.php';

$page_head = get_page_head();

$forum_actions = get_forum_actions();

$page_title = array(pun_htmlspecialchars($pun_config['o_board_title']));
define('PUN_ALLOW_INDEX', 1);
define('PUN_ACTIVE_PAGE', 'index');
require PUN_ROOT.'header.php';

$index_data = print_categories_forums();

$stats = collect_stats();

$online = fetch_users_online();

// Load the index.php view file
require PUN_ROOT.'view/index.php';

$footer_style = 'index';
require PUN_ROOT.'footer.php';
