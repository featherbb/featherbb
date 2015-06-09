<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

// The contents of this file are very much inspired by the file search.php
// from the phpBB Group forum software phpBB2 (http://www.phpbb.com)

define('PUN_ROOT', dirname(__FILE__).'/');
require PUN_ROOT.'include/common.php';

// Load the search.php language file
require PUN_ROOT.'lang/'.$pun_user['language'].'/search.php';
require PUN_ROOT.'lang/'.$pun_user['language'].'/forum.php';


if ($pun_user['g_read_board'] == '0') {
    message($lang_common['No view'], false, '403 Forbidden');
} elseif ($pun_user['g_search'] == '0') {
    message($lang_search['No search permission'], false, '403 Forbidden');
}

// Load the search.php model file
require PUN_ROOT.'model/search.php';

require PUN_ROOT.'include/search_idx.php';

// Figure out what to do :-)
if (isset($_GET['action']) || isset($_GET['search_id'])) {
    $search = get_search_results($_GET);
    
    // We have results to display
    if ($search['is_result']) {
        $page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), $lang_search['Search results']);
        define('PUN_ACTIVE_PAGE', 'search');
        require PUN_ROOT.'header.php';
        
        // Load the search.php header file
        require PUN_ROOT.'view/search/header.php';

        if ($search['show_as'] == 'posts') {
            require PUN_ROOT.'lang/'.$pun_user['language'].'/topic.php';
            require PUN_ROOT.'include/parser.php';
        }

        display_search_results($search);

        // Load the search.php view footer file
        require PUN_ROOT.'view/search/footer.php';
        
        require PUN_ROOT.'footer.php';
    } else {
        message($lang_search['No hits']);
    }
}


$page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), $lang_search['Search']);
$focus_element = array('search', 'keywords');
define('PUN_ACTIVE_PAGE', 'search');
require PUN_ROOT.'header.php';

// Load the search.php view form file
require PUN_ROOT.'view/search/form.php';

require PUN_ROOT.'footer.php';
