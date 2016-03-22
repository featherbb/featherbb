<?php

/**
 * Copyright (C) 2015-2016 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace FeatherBB\Plugins\Controller;

use FeatherBB\Core\Utils;
use FeatherBB\Core\Lister;
use FeatherBB\Core\Plugin as PluginManager;

class AssetsCompacter
{

    public function __construct()
    {
        translate('assets_compacter', 'featherbb', false, __DIR__.'/../lang');
        View::addTemplatesDirectory(dirname(dirname(__FILE__)).'/View', 5);
    }

    public function info($req, $res, $args)
    {
        $assetsManager = new \FeatherBB\Plugins\AssetsCompacter();
        $last_modified_style = 0;
        $last_modified_script = 0;
        $pluginsAssets = array();
        $themesData = array();

        if (Request::isPost()) {
            $result = $assetsManager->compactAssets(); // Returns array as ['success' => 'message']
            return Router::redirect(Router::pathFor('infoPlugin', ['name' => $args['name']]), $result);
        }

        // Get base assets from 'import' folder
        $pluginsAssets['stylesheets'] = $assetsManager->getAssets(ForumEnv::get('FEATHER_ROOT').'style'.DIRECTORY_SEPARATOR.'imports', 'css');
        $pluginsAssets['scripts'] = $assetsManager->getAssets(ForumEnv::get('FEATHER_ROOT').'style'.DIRECTORY_SEPARATOR.'imports', 'js');

        // Get assets from all active plugins
        foreach (PluginManager::getActivePlugins() as $plugin) {
            $pluginFolder = ForumEnv::get('FEATHER_ROOT').'plugins'.DIRECTORY_SEPARATOR.$plugin;
            $pluginsAssets['stylesheets'] = array_merge($pluginsAssets['stylesheets'], $assetsManager->getAssets($pluginFolder, 'css'));
            $pluginsAssets['scripts'] = array_merge($pluginsAssets['scripts'], $assetsManager->getAssets($pluginFolder, 'js'));
        }

        // Check minified assets state in each theme directory
        foreach (Lister::getStyles() as $theme) {
            $themeFolder = ForumEnv::get('FEATHER_ROOT').'style'.DIRECTORY_SEPARATOR.'themes'.DIRECTORY_SEPARATOR.$theme;
            // Check if theme directory is writable for minified assets
            $themesData[$theme]['directory'] = is_writable($themeFolder);
            // Merge plugins available assets with the ones in current theme
            $themesData[$theme]['stylesheets'] = array_merge($pluginsAssets['stylesheets'], $assetsManager->getAssets($themeFolder, 'css'));
            $themesData[$theme]['scripts'] = array_merge($pluginsAssets['scripts'], $assetsManager->getAssets($themeFolder, 'js'));

            // If below files don't exist, $themesData mtimes will be (bool) false
            $themesData[$theme]['stylesheets_mtime'] = is_file($themeFolder.DIRECTORY_SEPARATOR.'styles.min.css') ? filemtime($themeFolder.DIRECTORY_SEPARATOR.'styles.min.css') : false;
            $themesData[$theme]['scripts_mtime'] = is_file($themeFolder.DIRECTORY_SEPARATOR.'scripts.min.js') ? filemtime($themeFolder.DIRECTORY_SEPARATOR.'scripts.min.js') : false;
            // Check last modification date to see if minified assets need a refresh, and use relative paths in view
            foreach ($themesData[$theme]['stylesheets'] as $key => $style) {
                if (filemtime($style) > $last_modified_style) $last_modified_style = filemtime($style);
                $themesData[$theme]['stylesheets'][$key] = str_replace(ForumEnv::get('FEATHER_ROOT'), '', $style);
            }
            foreach ($themesData[$theme]['scripts'] as $key => $script) {
                if (filemtime($script) > $last_modified_script) $last_modified_script = filemtime($script);
                $themesData[$theme]['scripts'][$key] = str_replace(ForumEnv::get('FEATHER_ROOT'), '', $script);
            }
        }

        // Display view
        return View::setPageInfo(array(
            'title' => array(Utils::escape(ForumSettings::get('o_board_title')), __('Assets compacter', 'private_messages')),
            'admin_console' => true,
            'last_modified_style' => $last_modified_style,
            'last_modified_script' => $last_modified_script,
            'themes_data' => $themesData
            )
        )
        ->addTemplate('info.php')->display();
    }

}
