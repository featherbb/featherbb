<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

// Make sure no one attempts to run this script "directly"
if (!defined('FEATHER')) {
    exit;
}

// Make sure we have a usable language pack for admin.
if (file_exists(FEATHER_ROOT.'lang/'.$feather_user['language'].'/admin_common.php')) {
    $admin_language = $feather_user['language'].'/admin/';
} elseif (file_exists(FEATHER_ROOT.'lang/'.$feather_config['o_default_lang'].'/admin_common.php')) {
    $admin_language = $feather_config['o_default_lang'].'/admin/';
} else {
    $admin_language = 'English/admin/';
}

// Attempt to load the admin_common language file
require FEATHER_ROOT.'lang/'.$admin_language.'/common.php';

//
// Display the admin navigation menu
//
function generate_admin_menu($page = '')
{
    global $feather_config, $feather_user, $lang_admin_common;

    $is_admin = $feather_user['g_id'] == FEATHER_ADMIN ? true : false;
    
    // See if there are any plugins
    $plugins = forum_list_plugins($is_admin);
    
    $feather = \Slim\Slim::getInstance();

    $feather->render('admin/menu.php', array(
        'page'    =>    $page,
        'is_admin'    =>    $is_admin,
        'lang_admin_common'    =>    $lang_admin_common,
        'feather_config'    =>    $feather_config,
        'feather_user'    =>    $feather_user,
        'plugins'    =>    $plugins,
        )
    );
}

//
// Fetch a list of available admin plugins
//
function forum_list_plugins($is_admin)
{
    $plugins = array();

    $d = dir(FEATHER_ROOT.'plugins');
    if (!$d) {
        return $plugins;
    }

    while (($entry = $d->read()) !== false) {
        if (!is_dir(FEATHER_ROOT.'plugins/'.$entry) && preg_match('%^AM?P_(\w+)\.php$%i', $entry)) {
            $prefix = substr($entry, 0, strpos($entry, '_'));

            if ($prefix == 'AMP' || ($is_admin && $prefix == 'AP')) {
                $plugins[$entry] = substr($entry, strlen($prefix) + 1, -4);
            }
        }
    }
    $d->close();

    natcasesort($plugins);

    return $plugins;
}
