<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace controller\admin{
    
    class Statistics{

        function display(){
			
			global $feather, $lang_common, $lang_admin_common, $lang_admin_index, $pun_config, $pun_user, $pun_start, $db;

			require PUN_ROOT.'include/common_admin.php';

			if (!$pun_user['is_admmod']) {
				message($lang_common['No permission'], false, '403 Forbidden');
			}

            define('PUN_ADMIN_CONSOLE', 1);

            // Load the admin_index.php language file
            require PUN_ROOT.'lang/'.$admin_language.'/index.php';

            // Load the statistics.php model file
            require PUN_ROOT.'model/admin/statistics.php';

			$page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), $lang_admin_common['Admin'], $lang_admin_common['Server statistics']);
            if (!defined('PUN_ACTIVE_PAGE')) {
                define('PUN_ACTIVE_PAGE', 'admin');
            }
            require PUN_ROOT.'include/header.php';

            $feather->render('header.php', array(
                    'lang_common' => $lang_common,
                    'page_title' => $page_title,
                    'pun_user' => $pun_user,
                    'pun_config' => $pun_config,
                    '_SERVER'	=>	$_SERVER,
                    'navlinks'		=>	$navlinks,
                    'page_info'		=>	$page_info,
                    'db'		=>	$db,
                    'p'		=>	'',
                )
            );

			generate_admin_menu('index');
			
            $feather->render('admin/statistics.php', array(
                    'lang_admin_common'	=>	$lang_admin_common,
                    'lang_admin_index'	=>	$lang_admin_index,
                    'pun_config'	=>	$pun_config,
                    'server_load'	=>	get_server_load(),
                    'num_online'	=>	get_num_online(),
                    'total_size'	=>	get_total_size(),
                    'php_accelerator'	=>	get_php_accelerator(),
                )
            );

            $feather->render('footer.php', array(
                    'lang_common' => $lang_common,
                    'pun_user' => $pun_user,
                    'pun_config' => $pun_config,
                    'pun_start' => $pun_start,
                    'footer_style' => 'index',
                )
            );

            require PUN_ROOT.'include/footer.php';

		}
		
		
        function phpinfo(){
			
			global $feather, $lang_common, $lang_admin_common, $lang_admin_index, $pun_config, $pun_user, $pun_start, $db;

			require PUN_ROOT.'include/common_admin.php';
			
            // Load the admin_index.php language file
            require PUN_ROOT.'lang/'.$admin_language.'/index.php';

			if ($pun_user['g_id'] != PUN_ADMIN) {
				message($lang_common['No permission'], false, '403 Forbidden');
			}
			
			// Show phpinfo() output
			// Is phpinfo() a disabled function?
			if (strpos(strtolower((string) ini_get('disable_functions')), 'phpinfo') !== false) {
				message($lang_admin_index['PHPinfo disabled message']);
			}

			phpinfo();
			$feather->stop();
		}
    }
}