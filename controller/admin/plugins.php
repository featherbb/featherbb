<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace controller\admin;

class plugins
{
    public function __construct()
    {
        $this->feather = \Slim\Slim::getInstance();
    }
    
    public function display()
    {
        global $lang_common, $lang_admin_common, $feather_config, $feather_user, $feather_start, $db;

        require FEATHER_ROOT.'include/common_admin.php';

        if (!$feather_user['is_admmod']) {
            message($lang_common['No permission'], false, '403 Forbidden');
        }

        define('PUN_ADMIN_CONSOLE', 1);

            // The plugin to load should be supplied via GET
            $plugin = $this->feather->request->get('plugin') ? $this->feather->request->get('plugin') : '';
        if (!preg_match('%^AM?P_(\w*?)\.php$%i', $plugin)) {
            message($lang_common['Bad request'], false, '404 Not Found');
        }

            // AP_ == Admins only, AMP_ == admins and moderators
            $prefix = substr($plugin, 0, strpos($plugin, '_'));
        if ($feather_user['g_moderator'] == '1' && $prefix == 'AP') {
            message($lang_common['No permission'], false, '403 Forbidden');
        }

            // Make sure the file actually exists
            if (!file_exists(FEATHER_ROOT.'plugins/'.$plugin)) {
                message(sprintf($lang_admin_common['No plugin message'], $plugin));
            }

            // Construct REQUEST_URI if it isn't set
            if (!isset($_SERVER['REQUEST_URI'])) {
                $_SERVER['REQUEST_URI'] = (isset($_SERVER['PHP_SELF']) ? $_SERVER['PHP_SELF'] : '').'?'.(isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : '');
            }

        $page_title = array(pun_htmlspecialchars($feather_config['o_board_title']), $lang_admin_common['Admin'], str_replace('_', ' ', substr($plugin, strpos($plugin, '_') + 1, -4)));
        if (!defined('PUN_ACTIVE_PAGE')) {
            define('PUN_ACTIVE_PAGE', 'admin');
        }
        require FEATHER_ROOT.'include/header.php';

        $this->feather->render('header.php', array(
                    'lang_common' => $lang_common,
                    'page_title' => $page_title,
                    'feather_user' => $feather_user,
                    'feather_config' => $feather_config,
                    '_SERVER'    =>    $_SERVER,
                    'navlinks'        =>    $navlinks,
                    'page_info'        =>    $page_info,
                    'db'        =>    $db,
                    'p'        =>    '',
                )
            );

            // Attempt to load the plugin. We don't use @ here to suppress error messages,
            // because if we did and a parse error occurred in the plugin, we would only
            // get the "blank page of death"
            include FEATHER_ROOT.'plugins/'.$plugin;
        if (!defined('PUN_PLUGIN_LOADED')) {
            message(sprintf($lang_admin_common['Plugin failed message'], $plugin));
        }

        $this->feather->render('admin/loader.php');

        $this->feather->render('footer.php', array(
                    'lang_common' => $lang_common,
                    'feather_user' => $feather_user,
                    'feather_config' => $feather_config,
                    'feather_start' => $feather_start,
                    'footer_style' => 'index',
                )
            );

        require FEATHER_ROOT.'include/footer.php';
    }
}
