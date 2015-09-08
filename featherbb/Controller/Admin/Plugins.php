<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace FeatherBB\Controller\Admin;

use FeatherBB\Core\AdminUtils;
use FeatherBB\Core\Lister;
use FeatherBB\Core\Error;
use FeatherBB\Core\Utils;
use FeatherBB\Core\Url;
use FeatherBB\Core\Plugin as PluginManager;

class Plugins
{
    public function __construct()
    {
        $this->feather = \Slim\Slim::getInstance();
        $this->start = $this->feather->start;
        $this->config = $this->feather->config;
        $this->user = $this->feather->user;
        $this->request = $this->feather->request;
        load_textdomain('featherbb', $this->feather->forum_env['FEATHER_ROOT'].'featherbb/lang/'.$this->user->language.'/admin/plugins.mo');
    }

    public function index()
    {
        // AdminUtils::generateAdminMenu('plugins');
        $this->feather->template->addAsset('js', 'style/imports/common.js', array('type' => 'text/javascript'));

        $availablePlugins = Lister::getPlugins();
        // var_dump($availablePlugins);
        $activePlugins = $this->feather->cache->isCached('active_plugins') ? $this->feather->cache->retrieve('active_plugins') : array();
        // var_dump($activePlugins);
        // $this->feather->cache->delete('active_plugins');

        AdminUtils::generateAdminMenu('plugins');

        $this->feather->template->setPageInfo(array(
            'admin_console' => true,
            'active_page' => 'admin',
            'availablePlugins'    =>    $availablePlugins,
            'activePlugins'    =>    $activePlugins,
            'title' => array(Utils::escape($this->config['o_board_title']), __('Admin'), __('Extension')),
            )
        )->addTemplate('admin/plugins.php')->display();
    }

    public function activate($plugin = null)
    {
        if (!$plugin) throw new Error(__('Bad request'), 400);

        $manager = new PluginManager();
        $manager->activate($plugin);

        // Plugin has been activated, confirm and redirect
        Url::redirect($this->feather->urlFor('adminPlugins'), 'Plugin activated!');
    }

    public function deactivate($plugin = null)
    {
        if (!$plugin) throw new Error(__('Bad request'), 400);

        $manager = new PluginManager();
        $manager->deactivate($plugin);

        // Plugin has been activated, confirm and redirect
        Url::redirect($this->feather->urlFor('adminPlugins'), array('warning', 'Plugin deactivated!'));
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
