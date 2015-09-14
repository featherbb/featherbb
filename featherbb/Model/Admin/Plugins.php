<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace FeatherBB\Model\Admin;

use DB;
use FeatherBB\Core\Plugin as PluginManager;

class Plugins
{
    protected $manager;

    public function __construct()
    {
        $this->feather = \Slim\Slim::getInstance();
        $this->hooks = $this->feather->hooks;
        $this->manager = new PluginManager();
    }

    public function activate($name)
    {
        $name = $this->hooks->fire('model.plugin.activate.name', $name);

        // Find or create plugin in DB...
        $plugin = DB::for_table('plugins')->where('name', $name)->find_one();
        if (!$plugin) {
            $plugin = DB::for_table('plugins')->create()->set('name', $name);
        }
        $plugin->set('active', 1);

        // ... Install it if needed ...
        $needInstall = ($plugin->installed) ? true : false;
        $this->manager->activate($name, $needInstall);

        // ... Save in DB ...
        $plugin->set('installed', 1);
        $plugin = $this->hooks->fireDB('model.plugin.activate', $plugin);
        $plugin->save();

        // ... And regenerate cache.
        $this->manager->setActivePlugins();

        return $plugin;
    }

    public function deactivate($name)
    {
        $name = $this->hooks->fire('model.plugin.deactivate.name', $name);

        $plugin = DB::for_table('plugins')->where('name', $name)->find_one();
        if (!$plugin) {
            $plugin = DB::for_table('plugins')->create()->set('name', $name);
        }
        $plugin->set('active', 0);

        $this->manager->deactivate($name);

        $plugin = $this->hooks->fireDB('model.plugin.deactivate', $plugin);
        $plugin->save();

        $this->manager->setActivePlugins();

        return $plugin;
    }

}
