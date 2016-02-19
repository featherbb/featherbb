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
        load_textdomain('featherbb', ForumEnv::get('FEATHER_ROOT').'featherbb/lang/'.Container::get('user')->language.'/admin/plugins.mo');
    }

    /**
     * Download a plugin, unzip it and rename it
     */
    public function download($req, $res, $args)
    {
        $zipFile = ForumEnv::get('FEATHER_ROOT').'plugins'.DIRECTORY_SEPARATOR.$args['name']."-".$args['version'].'.zip';
        $zipResource = fopen($zipFile, "w");

        // Get the zip file straight from GitHub
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://codeload.github.com/featherbb/' . $args['name'] . '/zip/'.$args['version']);
        curl_setopt($ch, CURLOPT_FAILONERROR, true);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_AUTOREFERER, true);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER,true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_FILE, $zipResource);
        $page = curl_exec($ch);
        curl_close($ch);
        fclose($zipResource);

        if (!$page) {
            unlink(ForumEnv::get('FEATHER_ROOT').'plugins'.DIRECTORY_SEPARATOR.$args['name']."-".$args['version'].'.zip');
            throw new Error(__('Bad request'), 400);
        }

        $zip = new ZipArchive;

        if($zip->open($zipFile) != true){
            throw new Error(__('Bad request'), 400);
        }

        $zip->extractTo(ForumEnv::get('FEATHER_ROOT').'plugins');
        $zip->close();

        if (file_exists(ForumEnv::get('FEATHER_ROOT').'plugins'.DIRECTORY_SEPARATOR.$args['name'])) {
            AdminUtils::delete_folder(ForumEnv::get('FEATHER_ROOT').'plugins'.DIRECTORY_SEPARATOR.$args['name']);
        }
        rename(ForumEnv::get('FEATHER_ROOT').'plugins'.DIRECTORY_SEPARATOR.$args['name']."-".$args['version'], ForumEnv::get('FEATHER_ROOT').'plugins'.DIRECTORY_SEPARATOR.$args['name']);
        unlink(ForumEnv::get('FEATHER_ROOT').'plugins'.DIRECTORY_SEPARATOR.$args['name']."-".$args['version'].'.zip');
        return Router::redirect(Router::pathFor('adminPlugins'), 'Plugin downloaded!');
    }

    public function index($req, $res, $args)
    {
        Container::get('hooks')->fire('controller.admin.plugins.index');

        View::addAsset('js', 'style/imports/common.js', array('type' => 'text/javascript'));

        $availablePlugins = Lister::getPlugins();
        $activePlugins = Container::get('cache')->isCached('activePlugins') ? Container::get('cache')->retrieve('activePlugins') : array();

        $officialPlugins = Lister::getOfficialPlugins();

        AdminUtils::generateAdminMenu('plugins');

        View::setPageInfo(array(
            'admin_console' => true,
            'active_page' => 'admin',
            'availablePlugins'    =>    $availablePlugins,
            'activePlugins'    =>    $activePlugins,
            'officialPlugins'    =>    $officialPlugins,
            'title' => array(Utils::escape(ForumSettings::get('o_board_title')), __('Admin'), __('Extension')),
            )
        )->addTemplate('admin/plugins.php')->display();
    }

    public function activate($req, $res, $args)
    {
        Container::get('hooks')->fire('controller.admin.plugins.activate');

        if (!$args['plugin']) {
            throw new Error(__('Bad request'), 400);
        }

        $this->model->activate($args['plugin']);
        // Plugin has been activated, confirm and redirect
        return Router::redirect(Router::pathFor('adminPlugins'), 'Plugin activated!');
    }

    public function deactivate($req, $res, $args)
    {
        Container::get('hooks')->fire('controller.admin.plugins.deactivate');

        if (!$args['plugin']) {
            throw new Error(__('Bad request'), 400);
        }

        $this->model->deactivate($args['plugin']);
        // // Plugin has been deactivated, confirm and redirect
        return Router::redirect(Router::pathFor('adminPlugins'), array('warning', 'Plugin deactivated!'));
    }

    public function uninstall($req, $res, $args)
    {
        Container::get('hooks')->fire('controller.admin.plugins.uninstall');

        if (!$args['plugin']) {
            throw new Error(__('Bad request'), 400);
        }

        $this->model->uninstall($args['plugin']);
        // Plugin has been deactivated, confirm and redirect
        return Router::redirect(Router::pathFor('adminPlugins'), array('warning', 'Plugin uninstalled!'));
    }

    /**
     * Load plugin info if it exists
     * @param null $pluginName
     * @throws Error
     */
    public function info($req, $res, $args)
    {
        $formattedPluginName =  str_replace('-', '', $args['plugin']);
        $new = "\FeatherBB\Plugins\Controller\\".$formattedPluginName;
        if (class_exists($new)) {
            $plugin = new $new;
            if (method_exists($plugin, 'info')) {
                AdminUtils::generateAdminMenu($args['plugin']);
                $plugin->info();
            }
            else {
                throw new Error(__('Bad request'), 400);
            }
        }
        else {
            throw new Error(__('Bad request'), 400);
        }

    }

}
