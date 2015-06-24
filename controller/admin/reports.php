<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace controller\admin{
    
    class Reports{

        function display(){
			
			global $feather, $lang_common, $lang_admin_common, $lang_admin_censoring, $pun_config, $pun_user, $pun_start, $db;

			require PUN_ROOT.'include/common_admin.php';

			if (!$pun_user['is_admmod']) {
				message($lang_common['No permission'], false, '403 Forbidden');
			}

            define('PUN_ADMIN_CONSOLE', 1);

            // Load the admin_options.php language file
            require PUN_ROOT.'lang/'.$admin_language.'/reports.php';

            // Load the report.php model file
            require PUN_ROOT.'model/admin/reports.php';
			
			// Zap a report
			if ($feather->request->isPost()) {
				zap_report($feather);
			}

			$page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), $lang_admin_common['Admin'], $lang_admin_common['Reports']);
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

			generate_admin_menu('reports');
			
            $feather->render('admin/reports.php', array(
                    'lang_admin_reports'	=>	$lang_admin_reports,
                    'lang_admin_common'	=>	$lang_admin_common,
					'is_report'	=>	check_reports(),
					'is_report_zapped'	=>	check_zapped_reports(),
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
    }
}