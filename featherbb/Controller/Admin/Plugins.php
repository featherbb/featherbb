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
        $this->feather = \Slim\Slim::getInstance();
        $this->config = $this->feather->config;
        $this->user = Container::get('user');
        $this->model = new \FeatherBB\Model\Admin\Plugins();
        load_textdomain('featherbb', Config::get('forum_env')['FEATHER_ROOT'].'featherbb/lang/'.$this->user->language.'/admin/plugins.mo');
    }

    /**
     * Download a plugin, unzip it and rename it
     */
    public function download($name, $version)
    {
        $zipFile = Config::get('forum_env')['FEATHER_ROOT'].'plugins'.DIRECTORY_SEPARATOR.$name."-".$version.'.zip';
        $zipResource = fopen($zipFile, "w");

        // Get the zip file straight from GitHub
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://codeload.github.com/featherbb/' . $name . '/zip/'.$version);
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
            unlink(Config::get('forum_env')['FEATHER_ROOT'].'plugins'.DIRECTORY_SEPARATOR.$name."-".$version.'.zip');
            throw new Error(__('Bad request'), 400);
        }

        $zip = new ZipArchive;

        if($zip->open($zipFile) != true){
            throw new Error(__('Bad request'), 400);
        }

        $zip->extractTo(Config::get('forum_env')['FEATHER_ROOT'].'plugins');
        $zip->close();

        if (file_exists(Config::get('forum_env')['FEATHER_ROOT'].'plugins'.DIRECTORY_SEPARATOR.$name)) {
            AdminUtils::delete_folder(Config::get('forum_env')['FEATHER_ROOT'].'plugins'.DIRECTORY_SEPARATOR.$name);
        }
        rename(Config::get('forum_env')['FEATHER_ROOT'].'plugins'.DIRECTORY_SEPARATOR.$name."-".$version, Config::get('forum_env')['FEATHER_ROOT'].'plugins'.DIRECTORY_SEPARATOR.$name);
        unlink(Config::get('forum_env')['FEATHER_ROOT'].'plugins'.DIRECTORY_SEPARATOR.$name."-".$version.'.zip');
        Router::redirect(Router::pathFor('adminPlugins'), 'Plugin downloaded!');
    }

    public function index()
    {
        Container::get('hooks')->fire('controller.admin.plugins.index');

        View::addAsset('js', 'style/imports/common.js', array('type' => 'text/javascript'));

        $availablePlugins = Lister::getPlugins();
        $activePlugins = $this->feather->cache->isCached('activePlugins') ? $this->feather->cache->retrieve('activePlugins') : array();

        $officialPlugins = Lister::getOfficialPlugins();

        AdminUtils::generateAdminMenu('plugins');

        View::setPageInfo(array(
            'admin_console' => true,
            'active_page' => 'admin',
            'availablePlugins'    =>    $availablePlugins,
            'activePlugins'    =>    $activePlugins,
            'officialPlugins'    =>    $officialPlugins,
            'title' => array(Utils::escape($this->config['o_board_title']), __('Admin'), __('Extension')),
            )
        )->addTemplate('admin/plugins.php')->display();
    }

    public function activate($plugin = null)
    {
        Container::get('hooks')->fire('controller.admin.plugins.activate');

        if (!$plugin) {
            throw new Error(__('Bad request'), 400);
        }

        $this->model->activate($plugin);
        // Plugin has been activated, confirm and redirect
        Router::redirect(Router::pathFor('adminPlugins'), 'Plugin activated!');
    }

    public function deactivate($plugin = null)
    {
        Container::get('hooks')->fire('controller.admin.plugins.deactivate');

        if (!$plugin) {
            throw new Error(__('Bad request'), 400);
        }

        $this->model->deactivate($plugin);
        // // Plugin has been deactivated, confirm and redirect
        Router::redirect(Router::pathFor('adminPlugins'), array('warning', 'Plugin deactivated!'));
    }

    public function uninstall($plugin = null)
    {
        Container::get('hooks')->fire('controller.admin.plugins.uninstall');

        if (!$plugin) {
            throw new Error(__('Bad request'), 400);
        }

        $this->model->uninstall($plugin);
        // Plugin has been deactivated, confirm and redirect
        Router::redirect(Router::pathFor('adminPlugins'), array('warning', 'Plugin uninstalled!'));
    }

    /**
     * Load plugin info if it exists
     * @param null $pluginName
     * @throws Error
     */
    public function info($pluginName = null)
    {
        $formattedPluginName =  str_replace('-', '', $pluginName);
        $new = "\FeatherBB\Plugins\Controller\\".$formattedPluginName;
        if (class_exists($new)) {
            $plugin = new $new;
            if (method_exists($plugin, 'info')) {
                AdminUtils::generateAdminMenu($pluginName);
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
