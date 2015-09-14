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

class Plugins
{
    public function __construct()
    {
        $this->feather = \Slim\Slim::getInstance();
        $this->config = $this->feather->config;
        $this->user = $this->feather->user;
        $this->model = new \FeatherBB\Model\Admin\Plugins();
        load_textdomain('featherbb', $this->feather->forum_env['FEATHER_ROOT'].'featherbb/lang/'.$this->user->language.'/admin/plugins.mo');
    }

    public function index()
    {
        $this->feather->template->addAsset('js', 'style/imports/common.js', array('type' => 'text/javascript'));

        $availablePlugins = Lister::getPlugins();
        $activePlugins = $this->feather->cache->isCached('activePlugins') ? $this->feather->cache->retrieve('activePlugins') : array();
        // var_dump($availablePlugins, $activePlugins);
        // $this->feather->cache->delete('activePlugins');

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
        $this->model->activate($plugin);
        // Plugin has been activated, confirm and redirect
        Url::redirect($this->feather->urlFor('adminPlugins'), 'Plugin activated!');
    }

    public function deactivate($plugin = null)
    {
        if (!$plugin) throw new Error(__('Bad request'), 400);
        $this->model->deactivate($plugin);
        // // Plugin has been deactivated, confirm and redirect
        Url::redirect($this->feather->urlFor('adminPlugins'), array('warning', 'Plugin deactivated!'));
    }

    public function uninstall($plugin = null)
    {
        if (!$plugin) throw new Error(__('Bad request'), 400);
        $this->model->uninstall($plugin);
        // // Plugin has been deactivated, confirm and redirect
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
                'title' => array(Utils::escape($this->config['o_board_title']), __('Admin'), __('Extension'), Utils::escape($pluginName)),
            )
        )->addTemplate('admin/plugins.php')->display();
    }



}
