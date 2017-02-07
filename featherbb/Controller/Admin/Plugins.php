<?php

/**
 * Copyright (C) 2015-2016 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace FeatherBB\Controller\Admin;

use FeatherBB\Core\AdminUtils;
use FeatherBB\Core\Error;
use FeatherBB\Core\Lister;
use FeatherBB\Core\Url;
use FeatherBB\Core\Utils;
use ZipArchive;

class Plugins
{
    public function __construct()
    {
        $this->model = new \FeatherBB\Model\Admin\Plugins();
        translate('admin/plugins');
        if (!User::isAdmin()) {
            throw new Error(__('No permission'), '403');
        }
    }

    /**
     * Download a plugin, unzip it and rename it
     */
    public function download($req, $res, $args)
    {
        Container::get('hooks')->fire('controller.admin.plugins.download');

        return $this->model->download($args);
    }

    public function index($req, $res, $args)
    {
        Container::get('hooks')->fire('controller.admin.plugins.index');

        if (Request::isPost()) {
            return $this->model->upload_plugin($_FILES);
        }

        View::addAsset('js', 'style/imports/common.js', ['type' => 'text/javascript']);

        $availablePlugins = Lister::getPlugins();
        $activePlugins = Container::get('cache')->isCached('activePlugins') ? Container::get('cache')->retrieve('activePlugins') : [];

        $officialPlugins = Lister::getOfficialPlugins();

        AdminUtils::generateAdminMenu('plugins');

        View::setPageInfo([
            'admin_console' => true,
            'active_page' => 'admin',
            'availablePlugins'    =>    $availablePlugins,
            'activePlugins'    =>    $activePlugins,
            'officialPlugins'    =>    $officialPlugins,
            'title' => [Utils::escape(ForumSettings::get('o_board_title')), __('Admin'), __('Extension')],
            ]
        )->addTemplate('admin/plugins.php')->display();
    }

    public function activate($req, $res, $args)
    {
        Container::get('hooks')->fire('controller.admin.plugins.activate');

        if (!$args['name'] || !is_dir(ForumEnv::get('FEATHER_ROOT').'plugins'.DIRECTORY_SEPARATOR.$args['name'])) {
            throw new Error(__('Bad request'), 400);
        }

        $this->model->activate($args['name']);
        // Plugin has been activated, confirm and redirect
        return Router::redirect(Router::pathFor('adminPlugins'), sprintf(__('Plugin activated'), $args['name']));
    }

    public function deactivate($req, $res, $args)
    {
        Container::get('hooks')->fire('controller.admin.plugins.deactivate');

        if (!$args['name'] || !is_dir(ForumEnv::get('FEATHER_ROOT').'plugins'.DIRECTORY_SEPARATOR.$args['name'])) {
            throw new Error(__('Bad request'), 400);
        }

        $this->model->deactivate($args['name']);
        // Plugin has been deactivated, confirm and redirect
        return Router::redirect(Router::pathFor('adminPlugins'), ['warning', sprintf(__('Plugin deactivated'), $args['name'])]);
    }

    public function uninstall($req, $res, $args)
    {
        Container::get('hooks')->fire('controller.admin.plugins.uninstall');

        if (!$args['name'] || !is_dir(ForumEnv::get('FEATHER_ROOT').'plugins'.DIRECTORY_SEPARATOR.$args['name'])) {
            throw new Error(__('Bad request'), 400);
        }

        $this->model->uninstall($args['name']);
        // Plugin has been uninstalled, confirm and redirect
        return Router::redirect(Router::pathFor('adminPlugins'), ['warning', sprintf(__('Plugin uninstalled'), $args['name'])]);
    }

    /**
     * Load plugin info if it exists
     * @param null $pluginName
     * @throws Error
     */
    public function info($req, $res, $args)
    {
        // $formattedPluginName =  str_replace('-', '', $args['name']);
        $formattedPluginName = str_replace(' ', '', ucwords(str_replace('-', ' ', $args['name'])));
        $new = '\FeatherBB\Plugins\Controller\\'.$formattedPluginName;
        if (class_exists($new)) {
            $plugin = new $new;
            if (method_exists($plugin, 'info')) {
                AdminUtils::generateAdminMenu($args['name']);
                return $plugin->info($req, $res, $args);
            } else {
                throw new Error(__('Bad request'), 400);
            }
        } else {
            throw new Error(__('Bad request'), 400);
        }
    }
}
