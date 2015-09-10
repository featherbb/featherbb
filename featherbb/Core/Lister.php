<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace FeatherBB\Core;

class Lister
{
    /**
     * Get all valid plugin files.
     */
    public static function getPlugins()
    {
        $plugins = array();
        $feather = \Slim\Slim::getInstance();

        foreach (glob($feather->forum_env['FEATHER_ROOT'].'plugins/*/featherbb.json') as $plugin_file)
		{
            $plugins[] =  json_decode(file_get_contents($plugin_file));
		}

        return $plugins;
    }

    /**
     * Get available styles
     */
    public static function getStyles()
    {
        $feather = \Slim\Slim::getInstance();
        $styles = array();

        $iterator = new \DirectoryIterator($feather->forum_env['FEATHER_ROOT'].'style/themes/');
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

        $iterator = new \DirectoryIterator($feather->forum_env['FEATHER_ROOT'].'featherbb/lang/');
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
