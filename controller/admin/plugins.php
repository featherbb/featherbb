<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace controller\admin;

class plugins
{
    public function __construct()
    {
        $this->feather = \Slim\Slim::getInstance();
        $this->start = $this->feather->start;
        $this->config = $this->feather->config;
        $this->user = $this->feather->user;
        $this->request = $this->feather->request;
        require FEATHER_ROOT . 'include/common_admin.php';
    }

    public function __autoload($class_name)
    {
        require FEATHER_ROOT . $class_name . '.php';
    }

    public function index()
    {
        // Update permissions
        // if ($this->feather->request->isPost()) {
        //     $this->model->update_permissions();
        // }

        generate_admin_menu('plugins');

        $pluginsList = \FeatherBB\AdminUtils::getValidPlugins();
        // var_dump($pluginsList);
        $activePlugins = $this->feather->cache->isCached('active_plugins') ? $this->feather->cache->retrieve('active_plugins') : array();
        // var_dump($activePlugins);

        $this->feather->view2->setPageInfo(array(
                'admin_console' => true,
                'active_page' => 'admin',
                'pluginsList'    =>    $pluginsList,
                'activePlugins'    =>    $activePlugins,
                'title' => array(feather_escape($this->config['o_board_title']), __('Admin'), 'Plugins'),
            )
        )->addTemplate('admin/plugins.php')->display();
    }

    public function activate()
    {
        // The plugin to load should be supplied via GET
        $class = $this->request->get('plugin') ? $this->request->get('plugin') : null;
        if (!$class) {
            message(__('Bad request'), '404');
        }

        // Check if plugin follows PSR-4 conventions and extends base forum plugin
        if (!class_exists($class) || !property_exists($class, 'isValidFBPlugin')) {
            message(sprintf(__('No plugin message'), feather_escape($class)));
        }

        $plugin = new $class;
        try {
            $plugin->activate($class);
        } catch (\Exception $e) {
            redirect(get_link('admin/plugins/'), feather_escape($e->getMessage()));
        }
        // Plugin has been activated, confirm and redirect
        redirect(get_link('admin/plugins/'), 'Plugin "'.$class::$name.'" activated!');
    }

    public function deactivate()
    {
        // The plugin to load should be supplied via GET
        $class = $this->request->get('plugin') ? $this->request->get('plugin') : null;
        if (!$class) {
            message(__('Bad request'), '404');
        }

        $plugin = new $class;
        try {
            $plugin->deactivate($class);
        } catch (\Exception $e) {
            redirect(get_link('admin/plugins/'), feather_escape($e->getMessage()));
        }
        // Plugin has been activated, confirm and redirect
        redirect(get_link('admin/plugins/'), 'Plugin "'.$class::$name.'" deactivated!');
    }

    public function display()
    {
        // The plugin to load should be supplied via GET
        $plugin = $this->request->get('plugin') ? $this->request->get('plugin') : '';
        if (!preg_match('%^AM?P_(\w*?)\.php$%i', $plugin)) {
            message(__('Bad request'), '404');
        }

        // AP_ == Admins only, AMP_ == admins and moderators
        $prefix = substr($plugin, 0, strpos($plugin, '_'));
        if ($this->user->g_moderator == '1' && $prefix == 'AP') {
            message(__('No permission'), '403');
        }

        // Make sure the file actually exists
        if (!file_exists(FEATHER_ROOT.'plugins/'.$plugin)) {
            message(sprintf(__('No plugin message'), feather_escape($plugin)));
        }

        // Construct REQUEST_URI if it isn't set TODO?
        if (!isset($_SERVER['REQUEST_URI'])) {
            $_SERVER['REQUEST_URI'] = (isset($_SERVER['PHP_SELF']) ? $_SERVER['PHP_SELF'] : '').'?'.(isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : '');
        }

        // Attempt to load the plugin. We don't use @ here to suppress error messages,
        // because if we did and a parse error occurred in the plugin, we would only
        // get the "blank page of death"
        include FEATHER_ROOT.'plugins/'.$plugin;
        if (!defined('FEATHER_PLUGIN_LOADED')) {
            message(sprintf(__('Plugin failed message'), feather_escape($plugin)));
        }

        $this->feather->view2->setPageInfo(array(
                'title' => array(feather_escape($this->config['o_board_title']), __('Admin'), str_replace('_', ' ', substr($plugin, strpos($plugin, '_') + 1, -4))),
                'active_page' => 'admin',
                'admin_console' => true,
            )
        )->addTemplate('admin/loader.php')->display();
    }
}
