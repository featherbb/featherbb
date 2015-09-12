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
        $this->feather->template->addAsset('js', 'style/imports/common.js', array('type' => 'text/javascript'));

        $availablePlugins = Lister::getPlugins();
        $activePlugins = $this->feather->cache->isCached('active_plugins') ? $this->feather->cache->retrieve('active_plugins') : array();
        // var_dump($availablePlugins, $activePlugins);
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

        // Plugin has been deactivated, confirm and redirect
        Url::redirect($this->feather->urlFor('adminPlugins'), array('warning', 'Plugin deactivated!'));
    }

    public function info($pluginName = null)
    {
        if (!$pluginName) throw new Error(__('Bad request'), 400);

        // $manager = new PluginManager();
        // $plugin = $manager->displayInfos($pluginName);
        // echo $plugin->name;


        AdminUtils::generateAdminMenu($pluginName);

        $this->feather->template->setPageInfo(array(
                'admin_console' => true,
                'active_page' => 'admin',
                'availablePlugins'    =>    [],
                'activePlugins'    =>    [],
                'page' => $pluginName,
                'title' => array(Utils::escape($this->config['o_board_title']), __('Admin'), __('Extension')),
            )
        )->addTemplate('admin/plugins.php')->display();
    }



}
