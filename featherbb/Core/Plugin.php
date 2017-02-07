<?php

/**
 * Copyright (C) 2015-2017 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher.
 */

namespace FeatherBB\Core;

use FeatherBB\Core\Database as DB;

class Plugin
{
    public static function getActivePlugins()
    {
        $activePlugins = Container::get('cache')->isCached('activePlugins') ? Container::get('cache')->retrieve('activePlugins') : [];

        return $activePlugins;
    }

    public function setActivePlugins()
    {
        $activePlugins = [];
        $results = DB::for_table('plugins')->select('name')->where('active', 1)->find_array();
        foreach ($results as $plugin) {
            $activePlugins[] = $plugin['name'];
        }
        Container::get('cache')->store('activePlugins', $activePlugins);

        return $activePlugins;
    }

    /**
     * Run activated plugins
     */
    public function loadPlugins()
    {
        $activePlugins = $this->getActivePlugins();
        // var_dump($activePlugins);

        foreach ($activePlugins as $plugin) {
            if ($class = $this->load($plugin)) {
                $class->run();
            }
        }
    }

    /**
     * Child classes may use this method to quickly get plugin name added in admin menu
     *
     * @param  array $items Array of plugin names to display in admin panel sidebar
     * @return array        Pushed initial array above
     */
    public function getName($items)
    {
        // Split name
        $classNamespace = explode('\\', get_class($this));
        $className = end($classNamespace);

        // Prettify and return name of child class
        preg_match_all('%[A-Z]*[a-z]+%m', $className, $result, PREG_PATTERN_ORDER);

        $items[] = Utils::escape(implode(' ', $result[0]));

        return $items;
    }

    /**
     * Try to load a plugin class based on URI/folder-name
     * @param  string $plugin the name of the plugin to load
     * @return mixed         Return an instance of the plugin class or (bool) false if it could not be loaded
     */
    public function load($plugin)
    {
        // Plugins loaded via composer ($this->getNamespace should return valid namespace)
        if (class_exists($className = '\FeatherBB\Plugins\\'.$this->getNamespace($plugin))) {
            $class = new $className();
            return $class;
        }
        // "Complex" plugins which need to register namespace via bootstrap.php
        if (file_exists($file = ForumEnv::get('FEATHER_ROOT').'plugins/'.$plugin.'/bootstrap.php')) {
            $className = require $file;
            $class = new $className();
            return $class;
        }
        // Simple plugins, only a featherbb.json and the main class
        if (file_exists($file = $this->checkSimple($plugin))) {
            require_once $file;
            $className = '\FeatherBB\Plugins\\'.$this->getNamespace($plugin);
            $class = new $className();
            return $class;
        }
        // Invalid plugin
        return false;
    }

    // Clean a Simple Plugin's parent folder name to load it
    protected function getNamespace($path)
    {
        return str_replace(" ", "", str_replace("/", "\\", ucwords(str_replace('-', ' ', str_replace('/ ', '/', ucwords(str_replace('/', '/ ', $path)))))));
    }

    // For plugins that don't need to provide a Composer autoloader, check if it can be loaded
    protected function checkSimple($plugin)
    {
        return ForumEnv::get('FEATHER_ROOT').'plugins' . DIRECTORY_SEPARATOR . $plugin . DIRECTORY_SEPARATOR . $this->getNamespace($plugin) . '.php';
    }
}
