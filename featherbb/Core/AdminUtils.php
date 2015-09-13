<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace FeatherBB\Core;

class AdminUtils
{
    protected static $feather;

    public static function generateAdminMenu($page = '')
    {
        self::$feather = \Slim\Slim::getInstance();

        $is_admin = (self::$feather->user->g_id == self::$feather->forum_env['FEATHER_ADMIN']) ? true : false;

        // See if there are any plugins
        // $plugins = forum_list_plugins($is_admin);
        $plugins = array();

        self::$feather->template->setPageInfo(array(
            'page'    =>    $page,
            'is_admin'    =>    $is_admin,
            'plugins'    =>    $plugins,
            ), 1
        )->addTemplate('admin/menu.php');
    }

    /**
     * Generate breadcrumbs from an array of name and URLs
     */
    public static function breadcrumbs_admin(array $links)
    {
        foreach ($links as $name => $url) {
            if ($name != '' && $url != '') {
                $tmp[] = '<span><a href="' . $url . '">'.Utils::escape($name).'</a></span>';
            } else {
                $tmp[] = '<span>'.__('Deleted').'</span>';
                return implode(' » ', $tmp);
            }
        }
        return implode(' » ', $tmp);
    }


    /**
     * Fetch admin IDs
     */
    public static function get_admin_ids()
    {
        self::$feather = \Slim\Slim::getInstance();

        if (!self::$feather->cache->isCached('admin_ids')) {
            self::$feather->cache->store('admin_ids', \FeatherBB\Model\Cache::get_admin_ids());
        }

        return self::$feather->cache->retrieve('admin_ids');
    }
}
