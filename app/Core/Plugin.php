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
     * Ensure daughter plugin classes (users ones) are extending
     * this default class
     *
     * @var boolval
     */
    public static $isValidFBPlugin = true;

    /**
     * @var string
     */
    public static $name;

    /**
     * @var string
     */
    public static $version;

    /**
     * @var string
     */
    public static $author;

    /**
     * @var string
     */
    public static $description;

    /**
     * @var string
     */
    public static $updateUrl;

    /**
     * Structure of database table to create.
     *
     * @var array
     */
    protected $databaseScheme = array();

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

    /**
     * Execute hooks of activated plugins
     */
    public static function runActivePlugins()
    {
        $feather = \Slim\Slim::getInstance();
        $activePlugins = $feather->cache->isCached('active_plugins') ? $feather->cache->retrieve('active_plugins') : array();
        // $activePlugins = array();
        // var_dump($activePlugins);

        foreach ($activePlugins as $name => $plugin) {
            if (class_exists($name)) {
                $newPlugin = new $name();
                $newPlugin->run();
            }
        }
        return true;
    }

    /**
     * Activate a plugin based on class name
     */
    public function activate($pluginName)
    {
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

        $activePlugins = $this->feather->cache->isCached('active_plugins') ? $this->feather->cache->retrieve('active_plugins') : array();

        // Check if plugin isn't already activated
        if (!array_key_exists($pluginName, $activePlugins)) {
            $activePlugins[$pluginName] = true;
            $this->feather->cache->store('active_plugins', $activePlugins);
        } else {
            throw new \Exception('Plugin "'.$pluginName::$name.'" already activated', 1);
        }
    }

    /**
     * Deactivate a plugin based on class name
     */
    public function deactivate($pluginName)
    {
        $activePlugins = $this->feather->cache->isCached('active_plugins') ? $this->feather->cache->retrieve('active_plugins') : array();

        // Check if plugin is actually activated
        if (array_key_exists($pluginName, $activePlugins)) {
            unset($activePlugins[$pluginName]);
            $this->feather->cache->store('active_plugins', $activePlugins);
        } else {
            throw new \Exception("Plugin ".$pluginName::$name." not yet activated", 1);
        }
    }

    /**
     * Run an activated plugin
     */
    public function run()
    {
        $this->runHooks();
    }

    protected function runHooks()
    {
        // Daughter classes may configure hooks in this function
    }

    /**
     * Getters
     */

    public function getInfos()
    {
        return array(
            'name' => $this->name,
            'version' => $this->version,
            'author' => $this->author,
            'description' => $this->description,
        );
    }

    public static function getName()
    {
        return self::$name;
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
