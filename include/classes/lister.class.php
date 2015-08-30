<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace FeatherBB;

class Lister
{
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
     * Get available styles
     */
    public static function getStyles()
    {
        $feather = \Slim\Slim::getInstance();
        $styles = array();

        $iterator = new \DirectoryIterator($feather->forum_env['FEATHER_ROOT'].'style/themes');
        foreach ($iterator as $child) {
            if(!$child->isDot() && $child->isDir() && file_exists($child->getPathname().DIRECTORY_SEPARATOR.'style.css')) {
                // If the theme is well formed, add it to the list
                $styles[] = $child->getFileName();
            }
        }

        natcasesort($styles);
        return $styles;
    }

    /**
     * Get available langs
     */
    public static function getLangs($folder = '')
    {
        $feather = \Slim\Slim::getInstance();
        $langs = array();

        $iterator = new \DirectoryIterator($feather->forum_env['FEATHER_ROOT'].'lang');
        foreach ($iterator as $child) {
            if(!$child->isDot() && $child->isDir() && file_exists($child->getPathname().DIRECTORY_SEPARATOR.'common.po')) {
                // If the lang pack is well formed, add it to the list
                $langs[] = $child->getFileName();
            }
        }

        natcasesort($langs);
        return $langs;
    }

}
