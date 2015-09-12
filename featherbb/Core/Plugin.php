<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher.
 */

namespace FeatherBB\Core;

use DB;

class Plugin
{
    /**
     * @var object
     */
    protected $feather;

    /**
     * @var object
     */
    protected $hooks;


    /**
     * Constructor
     */
    public function __construct()
    {
        $this->feather = \Slim\Slim::getInstance();
        $this->hooks = $this->feather->hooks;
    }

    public function getActivePlugins()
    {
        $activePlugins = $this->feather->cache->isCached('active_plugins') ? $this->feather->cache->retrieve('active_plugins') : array();

        return $activePlugins;
    }

    /**
     * Run activated plugins
     */
    public function loadPlugins()
    {
        $activePlugins = $this->getActivePlugins();

        foreach ($activePlugins as $plugin) {
            if ($class = $this->load($plugin)) {
                $class->run();
            }
        }
    }

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
     * Activate a plugin
     */
    public function activate($plugin)
    {
        $activePlugins = $this->getActivePlugins();

        // Check if plugin is not yet activated
        if (!in_array($plugin, $activePlugins)) {
            $activePlugins[] = $plugin;

            $class = $this->load($plugin);
            $class->install();

            $this->feather->cache->store('active_plugins', $activePlugins);
        }
    }

    public function install()
    {
        // Daughter classes may override this method for custom install
    }

    /**
     * Deactivate a plugin
     */
    public function deactivate($plugin)
    {
        $activePlugins = $this->getActivePlugins();

        // Check if plugin is actually activated
        if (($k = array_search($plugin, $activePlugins)) !== false) {
            unset($activePlugins[$k]);
            $this->feather->cache->store('active_plugins', $activePlugins);
        }
    }

    public function uninstall($plugin)
    {
        // Daughter classes may override this method for custom uninstall
        $this->deactivate($plugin);

        $class = $this->load($plugin);
        $class->uninstall();
    }

    public function run()
    {
        // Daughter classes may override this method for custom run
    }

    protected function load($plugin)
    {
        if (file_exists($file = $this->feather->forum_env['FEATHER_ROOT'].'plugins/'.$plugin.'/bootstrap.php')) {
            $className = require $file;
            $class = new $className();
            return $class;
        }
        return false;
    }

}
