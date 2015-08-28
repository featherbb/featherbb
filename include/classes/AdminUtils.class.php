<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace FeatherBB;

class AdminUtils
{

    public static function generateAdminMenu($page = '')
    {
        $feather = \Slim\Slim::getInstance();

        $is_admin = ($feather->user->g_id == $feather->forum_env['FEATHER_ADMIN']) ? true : false;

        // See if there are any plugins
        // $plugins = forum_list_plugins($is_admin);
        $plugins = array();

        $feather->view2->setPageInfo(array(
            'page'    =>    $page,
            'is_admin'    =>    $is_admin,
            'plugins'    =>    $plugins,
            ), 1
        )->addTemplate('admin/menu.php');
    }

    // Will be removed when plugins branch is finished

    // public static function forum_list_plugins($is_admin)
    // {
    //     $plugins = array();
    //
    //     $d = dir(FEATHER_ROOT.'plugins');
    //     if (!$d) {
    //         return $plugins;
    //     }
    //
    //     while (($entry = $d->read()) !== false) {
    //         if (!is_dir(FEATHER_ROOT.'plugins/'.$entry) && preg_match('%^AM?P_(\w+)\.php$%i', $entry)) {
    //             $prefix = substr($entry, 0, strpos($entry, '_'));
    //
    //             if ($prefix == 'AMP' || ($is_admin && $prefix == 'AP')) {
    //                 $plugins[$entry] = substr($entry, strlen($prefix) + 1, -4);
    //             }
    //         }
    //     }
    //     $d->close();
    //
    //     natcasesort($plugins);
    //
    //     return $plugins;
    // }
}
