<?php

/**
 * Copyright (C) 2015-2016 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace FeatherBB\Controller\Admin;

use FeatherBB\Core\AutoUpdater;
use FeatherBB\Core\PluginAutoUpdater;
use FeatherBB\Core\CoreAutoUpdater;
use FeatherBB\Core\AdminUtils;
use FeatherBB\Core\Error;
use FeatherBB\Core\Url;
use FeatherBB\Core\Utils;
use FeatherBB\Core\Lister;

class Updates
{
    public function __construct()
    {
        translate('admin/index');
        translate('admin/updates');
        translate('admin/plugins');
    }

    public function display($req, $res, $args)
    {
        Container::get('hooks')->fire('controller.admin.updates.display');

        $core_updates = false;

        $plugin_updates = array();
        $all_plugins = Lister::getPlugins();

        // Check FeatherBB core updates
        $coreUpdater = new CoreAutoUpdater();
        if ($coreUpdater->checkUpdate() === false) {
            // TODO: add error message to view
        } else {
            // If update available, add plugin to display in view
            if ($coreUpdater->newVersionAvailable()) {
                $core_updates = $coreUpdater->getLatestVersion();
            }
        }

        // Check plugins uavailable versions
        foreach ($all_plugins as $plugin) {
            // If plugin isn't well formed or doesn't want to be auto-updated, skip it
            if (!isset($plugin->name) || !isset($plugin->version) || (isset($plugin->skip_update) && $plugin->skip_update == true)) {
                continue;
            }
            $pluginsUpdater = new PluginAutoUpdater($plugin);
            // If check fails, go to next item
            if ($pluginsUpdater->checkUpdate() === false) {
                // TODO: handle errors
                continue;
            }
            // If update available, add plugin to display in view
            if ($pluginsUpdater->newVersionAvailable()) {
                $plugin->last_version = $pluginsUpdater->getLatestVersion();
                $plugin_updates[] = $plugin;
            }
        }

        AdminUtils::generateAdminMenu('updates');

        return View::setPageInfo(array(
                'title' => array(Utils::escape(ForumSettings::get('o_board_title')), __('Admin'), __('Updates')),
                'active_page' => 'admin',
                'admin_console' => true,
                'plugin_updates' => $plugin_updates,
                'core_updates' => $core_updates
            )
        )->addTemplate('admin/updates.php')->display();
    }

    public function check($req, $res, $args)
    {
        Container::get('hooks')->fire('controller.admin.updates.check');

        // Check for upgrade
        if ($args['action'] == 'check_upgrade') {
            if (!ini_get('allow_url_fopen')) {
                throw new Error(__('fopen disabled message'), 500);
            }

            $latest_version = trim(@file_get_contents('http://featherbb.org/latest_version.html'));
            if (empty($latest_version)) {
                throw new Error(__('Upgrade check failed message'), 500);
            }

            if (version_compare(ForumSettings::get('o_cur_version'), $latest_version, '>=')) {
                return Router::redirect(Router::pathFor('adminIndex'), __('Running latest version message'));
            } else {
                return Router::redirect(Router::pathFor('adminIndex'), sprintf(__('New version available message'), '<a href="http://featherbb.org/">FeatherBB.org</a>'));
            }
        }

        AdminUtils::generateAdminMenu('updates');

        return View::setPageInfo(array(
                'title' => array(Utils::escape(ForumSettings::get('o_board_title')), __('Admin'), __('Updates')),
                'active_page' => 'admin',
                'admin_console' => true
            )
        )->addTemplate('admin/updates.php')->display();
    }

    public function upgradePlugins($req, $res, $args)
    {
        Container::get('hooks')->fire('controller.admin.updates.upgradePlugins');

        // Check submit button has been clicked
        if (!Input::post('upgrade-plugins')) {
            throw new Error(__('Wrong form values'), 500);
        }

        $upgrade_results = [];

        foreach (Input::post('plugin_updates') as $plugin => $version) {
            if ($plugin = Lister::loadPlugin($plugin)) {
                // If plugin isn't well formed or doesn't want to be auto-updated, skip it
                if (!isset($plugin->name) || !isset($plugin->version) || (isset($plugin->skip_update) && $plugin->skip_update == true)) {
                    continue;
                }
                $upgrade_results[$plugin->title] = [];
                $pluginsUpdater = new PluginAutoUpdater($plugin);
                $result = $pluginsUpdater->update();
                if ($result !== true) {
                    $upgrade_results[$plugin->title]['message'] = sprintf('Plugin %s failed update %s', $plugin->version, $pluginsUpdater->getLatestVersion());
                    $upgrade_results[$plugin->title]['errors'] = $pluginsUpdater->getErrors();
                } else {
                    $upgrade_results[$plugin->title]['message'] = sprintf('Plugin %s successfull update %s', $plugin->version, $pluginsUpdater->getLatestVersion());
                }
                // Will not be empty if upgrade has warnings (zip archive or _upgrade.php file could not be deleted)
                $upgrade_results[$plugin->title]['warnings'] = $pluginsUpdater->getWarnings();
            } else {
                continue;
            }
        }

        // Display upgrade results
        AdminUtils::generateAdminMenu('updates');

        return View::setPageInfo(array(
                'title'           => array(Utils::escape(ForumSettings::get('o_board_title')), __('Admin'), __('Updates')),
                'active_page'     => 'admin',
                'admin_console'   => true,
                'upgrade_results' => $upgrade_results
            )
        )->addTemplate('admin/updates.php')->display();
        // return Router::redirect(Router::pathFor('adminUpdates'), sprintf(__('Plugins upgraded'), sizeof($upgrade_results)));
    }

    public function upgradeCore($req, $res, $args)
    {
        Container::get('hooks')->fire('controller.admin.updates.upgradeCore');

        // Check submit button has been clicked
        if (!Input::post('upgrade-core')) {
            throw new Error(__('Wrong form values'), 500);
        }

        $coreUpdater = new CoreAutoUpdater();
        $result = $coreUpdater->update();
        if ($result !== true) {
            return Router::redirect(Router::pathFor('adminUpdates'), __('Core upgraded'));
        }
        // TODO: handle errors
    }
}
