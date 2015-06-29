<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace controller\admin{
    
    class Plugins{

        function display(){
			
			global $feather, $lang_common, $lang_admin_common, $pun_config, $pun_user, $pun_start, $db;

			require PUN_ROOT.'include/common_admin.php';

			if (!$pun_user['is_admmod']) {
				message($lang_common['No permission'], false, '403 Forbidden');
			}

            define('PUN_ADMIN_CONSOLE', 1);

            // The plugin to load should be supplied via GET
            $plugin = !empty($feather->request->get('plugin')) ? $feather->request->get('plugin') : '';
            if (!preg_match('%^AM?P_(\w*?)\.php$%i', $plugin)) {
                message($lang_common['Bad request'], false, '404 Not Found');
            }

            // AP_ == Admins only, AMP_ == admins and moderators
            $prefix = substr($plugin, 0, strpos($plugin, '_'));
            if ($pun_user['g_moderator'] == '1' && $prefix == 'AP') {
                message($lang_common['No permission'], false, '403 Forbidden');
            }

            // Make sure the file actually exists
            if (!file_exists(PUN_ROOT.'plugins/'.$plugin)) {
                message(sprintf($lang_admin_common['No plugin message'], $plugin));
            }

            // Construct REQUEST_URI if it isn't set
            if (!isset($_SERVER['REQUEST_URI'])) {
                $_SERVER['REQUEST_URI'] = (isset($_SERVER['PHP_SELF']) ? $_SERVER['PHP_SELF'] : '').'?'.(isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : '');
            }

            $page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), $lang_admin_common['Admin'], str_replace('_', ' ', substr($plugin, strpos($plugin, '_') + 1, -4)));
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

            // Attempt to load the plugin. We don't use @ here to suppress error messages,
            // because if we did and a parse error occurred in the plugin, we would only
            // get the "blank page of death"
            include PUN_ROOT.'plugins/'.$plugin;
            if (!defined('PUN_PLUGIN_LOADED')) {
                message(sprintf($lang_admin_common['Plugin failed message'], $plugin));
            }

            $feather->render('admin/loader.php');

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