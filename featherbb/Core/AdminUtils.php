<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace FeatherBB;

// Make sure no one attempts to run this script "directly"
if (!defined('FEATHER'))
    exit;

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
        if (!$this->feather->cache->isCached('admin_ids')) {
            $this->feather->cache->store('admin_ids', \FeatherBB\Model\Cache::get_admin_ids());
        }

        return $this->feather->cache->retrieve('admin_ids');
    }
}
