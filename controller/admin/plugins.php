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
        $this->header = new \controller\header();
        $this->footer = new \controller\footer();
        $this->model = new \model\admin\plugins();
    }

    public function __autoload($class_name)
    {
        require FEATHER_ROOT . $class_name . '.php';
    }
    
    public function display()
    {
        global $lang_common, $lang_admin_common;

        require FEATHER_ROOT.'include/common_admin.php';

        if (!$this->user->is_admmod) {
            message($lang_common['No permission'], '403');
        }

        define('FEATHER_ADMIN_CONSOLE', 1);

        // The plugin to load should be supplied via GET
        $plugin = $this->request->get('plugin') ? $this->request->get('plugin') : '';
        if (!preg_match('%^AM?P_(\w*?)\.php$%i', $plugin)) {
            message($lang_common['Bad request'], '404');
        }

        // AP_ == Admins only, AMP_ == admins and moderators
        $prefix = substr($plugin, 0, strpos($plugin, '_'));
        if ($this->user->g_moderator == '1' && $prefix == 'AP') {
            message($lang_common['No permission'], '403');
        }

        // Make sure the file actually exists
        if (!file_exists(FEATHER_ROOT.'plugins/'.$plugin)) {
            message(sprintf($lang_admin_common['No plugin message'], $plugin));
        }

        // Construct REQUEST_URI if it isn't set TODO?
        if (!isset($_SERVER['REQUEST_URI'])) {
            $_SERVER['REQUEST_URI'] = (isset($_SERVER['PHP_SELF']) ? $_SERVER['PHP_SELF'] : '').'?'.(isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : '');
        }

        $page_title = array(feather_escape($this->config['o_board_title']), $lang_admin_common['Admin'], str_replace('_', ' ', substr($plugin, strpos($plugin, '_') + 1, -4)));

        define('FEATHER_ACTIVE_PAGE', 'admin');

        $this->header->setTitle($page_title)->display();

        // Attempt to load the plugin. We don't use @ here to suppress error messages,
        // because if we did and a parse error occurred in the plugin, we would only
        // get the "blank page of death"
        include FEATHER_ROOT.'plugins/'.$plugin;
        if (!defined('FEATHER_PLUGIN_LOADED')) {
            message(sprintf($lang_admin_common['Plugin failed message'], $plugin));
        }

        $this->feather->render('admin/loader.php');

        $this->footer->display();
    }
}
