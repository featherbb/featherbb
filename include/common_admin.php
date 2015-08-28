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

// TODO: refactor
$feather = \Slim\Slim::getInstance();

// Load the admin common lang file
if (file_exists(FEATHER_ROOT.'lang/'.$feather->user->language.'/admin/common.mo')) {
    load_textdomain('featherbb', FEATHER_ROOT.'lang/'.$feather->user->language.'/admin/common.mo');
} else {
    die('There is no valid language pack \''.feather_escape($feather->user->language).'\' installed. Please reinstall a language of that name');
}

// To be removed
$admin_language = 'English/admin';

//
// Display the admin navigation menu
//
function generate_admin_menu($page = '')
{
    $feather = \Slim\Slim::getInstance();

    $is_admin = $feather->user->g_id == FEATHER_ADMIN ? true : false;

    // See if there are any plugins
    $plugins = forum_list_plugins($is_admin);

    $feather->view2->setPageInfo(array(
        'page'    =>    $page,
        'is_admin'    =>    $is_admin,
        'plugins'    =>    $plugins,
        ), 1
    )->addTemplate('admin/menu.php');
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
