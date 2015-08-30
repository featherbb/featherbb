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

    /**
     * Get all php files in plugin folder.
     */
    public static function getPluginFiles($folder = '')
    {
        $plugin_files = array();

        $plugins_dir = new \RecursiveDirectoryIterator(FEATHER_ROOT.'plugins/'.$folder);
        $iterator = new \RecursiveIteratorIterator($plugins_dir);
        $iterator->setMaxDepth(1);
        $php_files = new \RegexIterator($iterator, '/.+\.php$/i', \RecursiveRegexIterator::GET_MATCH);

        foreach ($php_files as $file) {
            $plugin_files[] = $file[0];
        }

        return $plugin_files;
    }

    /**
     * Get all valid plugin files.
     */
    public static function getValidPlugins($folder = '')
    {
        $valid_plugins = array();

        $plugin_files = self::getPluginFiles($folder);

        $feather = \Slim\Slim::getInstance();
        $feather_root = $feather->forum_env['FEATHER_ROOT'];

        foreach ($plugin_files as $key => $file_path) {
            // Remove forum base path
            $relative_path = DIRECTORY_SEPARATOR.preg_replace("/" . preg_quote($feather_root, "/") . "/", '', $file_path);
            preg_match('/^(.+)\.php$/i', $relative_path, $class_name);
            $parts = explode(DIRECTORY_SEPARATOR, $class_name[1]);
            $name_space = join($parts, "\\");
            // Check if plugin follows PSR-4 conventions and extends base forum plugin
            if (class_exists($name_space) && property_exists($name_space, 'isValidFBPlugin')) {
                $class = new $name_space;
                $valid_plugins[end($parts)] =  $name_space;
            }
        }

        ksort($valid_plugins);
        return $valid_plugins;
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
            $this->feather->cache->store('admin_ids', \model\cache::get_admin_ids());
        }

        return $this->feather->cache->retrieve('admin_ids');
    }
}
