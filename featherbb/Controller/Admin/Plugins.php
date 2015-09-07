<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace FeatherBB\Controller\Admin;

use FeatherBB\Core\Error;
use FeatherBB\Core\Url;
use FeatherBB\Core\Utils;

class Plugins
{
    public function __construct()
    {
        $this->feather = \Slim\Slim::getInstance();
        $this->start = $this->feather->start;
        $this->config = $this->feather->config;
        $this->user = $this->feather->user;
        $this->request = $this->feather->request;
    }

    public function index()
    {
        // Update permissions
        // if ($this->feather->request->isPost()) {
        //     $this->model->update_permissions();
        // }

        // AdminUtils::generateAdminMenu('plugins');
        $this->feather->template->addAsset('js', 'style/imports/common.js', array('type' => 'text/javascript'));

        $pluginsList = \FeatherBB\Core\Lister::getValidPlugins();
        // var_dump($pluginsList);
        $activePlugins = $this->feather->cache->isCached('active_plugins') ? $this->feather->cache->retrieve('active_plugins') : array();
        // var_dump($activePlugins);

        $this->feather->template->setPageInfo(array(
                'admin_console' => true,
                'active_page' => 'admin',
                'pluginsList'    =>    $pluginsList,
                'activePlugins'    =>    $activePlugins,
                'title' => array(Utils::escape($this->config['o_board_title']), __('Admin'), 'Plugins'),
            )
        )->addTemplate('admin/plugins.php')->display();
    }

    public function activate($pluginName = null)
    {
        // The plugin to load should be supplied via GET
        // $pluginName = $this->request->get('plugin') ? $this->request->get('plugin') : null;
        if (!$pluginName) {
            throw new Error(__('Bad request'), 400);
        }

        // Check if plugin follows PSR-4 conventions and extends base forum plugin
        if (!class_exists($pluginName) || !property_exists($pluginName, 'isValidFBPlugin')) {
            throw new Error(sprintf(__('No plugin message'), Utils::escape($pluginName)), 400);
        }

        $plugin = new $pluginName;
        try {
            $plugin->activate($pluginName);
        } catch (\Exception $e) {
            Url::redirect($this->feather->urlFor('adminPlugins'), $e->getMessage());
        }
        // Plugin has been activated, confirm and redirect
        Url::redirect($this->feather->urlFor('adminPlugins'), 'Plugin "'.$pluginName::$name.'" activated!');
    }

    public function deactivate()
    {
        // The plugin to load should be supplied via GET
        $class = $this->request->get('plugin') ? $this->request->get('plugin') : null;
        if (!$class) {
            throw new Error(__('Bad request'), 400);
        }

        $plugin = new $class;
        try {
            $plugin->deactivate($class);
        } catch (\Exception $e) {
            Url::redirect($this->feather->urlFor('adminPlugins'), $this->feather->utils->escape($e->getMessage()));
        }
        // Plugin has been activated, confirm and redirect
        Url::redirect($this->feather->urlFor('adminPlugins'), array('warning', 'Plugin "'.$class::$name.'" deactivated!'));
    }

    public function display()
    {
        // The plugin to load should be supplied via GET
        $plugin = $this->request->get('plugin') ? $this->request->get('plugin') : '';
        if (!preg_match('%^AM?P_(\w*?)\.php$%i', $plugin)) {
            throw new Error(__('Bad request'), 400);
        }

        // AP_ == Admins only, AMP_ == admins and moderators
        $prefix = substr($plugin, 0, strpos($plugin, '_'));
        if ($this->user->g_moderator == '1' && $prefix == 'AP') {
            throw new Error(__('No permission'), 403);
        }

        // Make sure the file actually exists
        if (!file_exists($this->feather->forum_env['FEATHER_ROOT'].'plugins/'.$plugin)) {
            throw new Error(sprintf(__('No plugin message'), Utils::escape($plugin)), 400);
        }

        // Construct REQUEST_URI if it isn't set TODO?
        if (!isset($_SERVER['REQUEST_URI'])) {
            $_SERVER['REQUEST_URI'] = (isset($_SERVER['PHP_SELF']) ? $_SERVER['PHP_SELF'] : '').'?'.(isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : '');
        }

        // Attempt to load the plugin. We don't use @ here to suppress error messages,
        // because if we did and a parse error occurred in the plugin, we would only
        // get the "blank page of death"
        include $this->feather->forum_env['FEATHER_ROOT'].'plugins/'.$plugin;
        if (!defined('FEATHER_PLUGIN_LOADED')) {
            throw new Error(sprintf(__('Plugin failed message'), Utils::escape($plugin)));
        }

        $this->feather->template->setPageInfo(array(
                'title' => array(Utils::escape($this->config['o_board_title']), __('Admin'), str_replace('_', ' ', substr($plugin, strpos($plugin, '_') + 1, -4))),
                'active_page' => 'admin',
                'admin_console' => true,
            )
        )->addTemplate('admin/loader.php')->display();
    }
}
