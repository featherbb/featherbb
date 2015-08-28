<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher.
 */

namespace FeatherBB;

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
     * Ensure daughter plugin classes (users ones) are extending
     * this default class
     *
     * @var boolval
     */
    protected $isFeatherPlugin;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $version;

    /**
     * @var string
     */
    protected $author;

    /**
     * @var string
     */
    protected $description;

    /**
     * @var string
     */
    protected $updateUrl;

    /**
     * Structure of database table to create.
     *
     * @var array
     */
    protected $databaseScheme = array();

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->feather = \Slim\Slim::getInstance();
        $this->user = $this->feather->user;
        $this->hook = $this->feather->hooks;
    }

    /**
     * Get a list of all available plugins.
     */
    public static function getPluginsList()
    {
        $plugins = array();

        // Get all files declaring a new plugin
        $directory = new \RecursiveDirectoryIterator('plugins');
        $iterator = new \RecursiveIteratorIterator($directory);
        $pluginClasses = new \RegexIterator($iterator, '/^.+\/(\\w+)\.plugin\.php$/i', \RecursiveRegexIterator::GET_MATCH);

        // Require these files to access their properties and display them in view
        foreach ($pluginClasses as $pluginClass) {
            require $pluginClass[0];
            $className = "\plugin\\".$pluginClass[1];
            $plugins[$pluginClass[1]] = new $className();
        }

        return $plugins;
    }

    /**
     * Execute hooks of activated plugins
     */
    public static function runActivePlugins()
    {
        // Get all files declaring a new plugin
        $directory = new \RecursiveDirectoryIterator('plugins');
        $iterator = new \RecursiveIteratorIterator($directory);
        $pluginClasses = new \RegexIterator($iterator, '/^.+\/(\\w+)\.plugin\.php$/i', \RecursiveRegexIterator::GET_MATCH);

        $feather = \Slim\Slim::getInstance();
        $activePlugins = $feather->cache->retrieve('active_plugins') ? $feather->cache->retrieve('active_plugins') : array();

        // Require these files to access their properties and display them in view
        foreach ($pluginClasses as $pluginClass) {
            require $pluginClass[0];
            $className = "\plugin\\".$pluginClass[1];
            $plugin = new $className();
            $plugin->runHooks();
        }

        return true;
    }

    /**
     * Activate a plugin based on class name
     */
    public static function activate($pluginName)
    {
        // Instantiate the plugin
        $className = "\plugin\\".$pluginName;
        $plugin = new $className;

        // TODO: Allow creation of new tables in database
        // \FeatherBB\Core::init_db($data);;
        // $feather = \Slim\Slim::getInstance();
        // // Handle db prefix
        // $db_prefix = $feather->forum_settings['db_prefix']
        // // Create tables
        // foreach ($this->getDatabaseScheme() as $table => $sql) {
        //     if (!$this->createTable($db_prefix.$table, $sql)) {
        //         // Error handling
        //         $this->errors[] = 'A problem was encountered while creating table '.$table;
        //     }
        // }
        // // Populate tables with default values
        // foreach ($this->loadMockData() as $group_name => $group_data) {
        //     $this->model->addData('groups', $group_data);
        // }

        $feather = \Slim\Slim::getInstance();
        $activePlugins = $feather->cache->isCached('active_plugins') ? $feather->cache->retrieve('active_plugins') : array();

        // $feather->cache->delete('active_plugins');
        // Check if plugin isn't already activated
        if (!array_key_exists($pluginName, $activePlugins)) {
            $activePlugins[$pluginName] = true;
            $feather->cache->store('active_plugins', $activePlugins);
            return true;
        }
        throw new \Exception("Plugin $pluginName already activated", 1);
    }

    protected function runHooks()
    {
        // Daughter classes may configure hooks in this function
    }

    /**
     * Getters
     */

    public function getName()
    {
        return $this->name;
    }

    public function getVersion()
    {
        return $this->version;
    }

    public function getAuthor()
    {
        return $this->author;
    }

    public function getDescription()
    {
        return $this->description;
    }

    protected function getDatabaseScheme()
    {
        return $this->databaseScheme;
    }


    protected function createTable($table_name, $sql)
    {
        $db = DB::get_db();
        $req = preg_replace('/%t%/', '`'.$table_name.'`', $sql);
        return $db->exec($req);
    }

    // protected function addData($table_name, array $data)
    // {
    //     return (bool) DB::for_table($table_name)
    //                     ->create()
    //                     ->set($data)
    //                     ->save();
    // }


}
