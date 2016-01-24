<?php

/**
 * Copyright (C) 2015-2016 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace FeatherBB\Core;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class AdminUtils
{
    protected static $feather;

    public static function generateAdminMenu($page = '')
    {
        self::$feather = \Slim\Slim::getInstance();

        $is_admin = (self::$feather->user->g_id == self::$feather->forum_env['FEATHER_ADMIN']) ? true : false;

        // See if there are any plugins that want to display in the menu
        $plugins = self::adminPluginsMenu($is_admin);

        self::$feather->template->setPageInfo(array(
            'page'    =>    $page,
            'is_admin'    =>    $is_admin,
            'plugins'    =>    $plugins,
            ), 1
        )->addTemplate('admin/menu.php');
    }

    /**
     * Add plugin options to menu if needed
     */
    public static function adminPluginsMenu($isAdmin = false)
    {
        self::$feather = \Slim\Slim::getInstance();

        $menuItems = [];
        $menuItems = self::$feather->hooks->fire('admin.plugin.menu', $menuItems);

        return $menuItems;
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
     * Delete a folder and all its content
     */
    public static function delete_folder($dirPath) {
        $it = new RecursiveDirectoryIterator($dirPath, RecursiveDirectoryIterator::SKIP_DOTS);
        $files = new RecursiveIteratorIterator($it,
            RecursiveIteratorIterator::CHILD_FIRST);
        foreach($files as $file) {
            if ($file->isDir()){
                rmdir($file->getRealPath());
            } else {
                unlink($file->getRealPath());
            }
        }
        rmdir($dirPath);
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

    /**
     * Wrapper for cURL
     */
    public static function get_content($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_USERAGENT, "FeatherBB Marketplace");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_HTTPGET, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

        $content = curl_exec($ch);

        curl_close($ch);

        return $content;
    }
}
