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

class AssetsCompacter
{

    public function __construct()
    {
        translate('assets_compacter', 'featherbb', false, __DIR__.'/../lang');
        View::addTemplatesDirectory(dirname(dirname(__FILE__)).'/View', 5);
    }

    public function info($req, $res, $args)
    {
        $manager = new \FeatherBB\Plugins\AssetsCompacter();

        if (Request::isPost()) {
            // return var_dump(Input::post('stylesheets'));
            $result = $manager->compactAssets(); // Returns array as ['success' => 'message']
            return Router::redirect(Router::pathFor('infoPlugin', ['name' => $args['name']]), $result);
        }

        // Check minified assets state in each theme directory
        $themesState = array();
        foreach (Lister::getStyles() as $theme) {
            $minFolder = ForumEnv::get('FEATHER_ROOT').'style'.DIRECTORY_SEPARATOR.'themes'.DIRECTORY_SEPARATOR.$theme.DIRECTORY_SEPARATOR.'min';
            // Try to create 'min' directory in theme if don't exist
            $themesState[$theme]['directory'] = true;
            if (!is_dir($minFolder)) {
                if (!mkdir($minFolder, 0755, true)) {
                    $themesState[$theme]['directory'] = false;
                }
            } elseif (!is_writable($minFolder)) {
                $themesState[$theme]['directory'] = false;
            }
            // If below files don't exist, $themesState key will be (bool) false
            $themesState[$theme]['stylesheets'] = is_file($minFolder.DIRECTORY_SEPARATOR.'styles.min.css') ? filemtime($minFolder.DIRECTORY_SEPARATOR.'styles.min.css') : false;
            $themesState[$theme]['scripts'] = is_file($minFolder.DIRECTORY_SEPARATOR.'scripts.min.js') ? filemtime($minFolder.DIRECTORY_SEPARATOR.'scripts.min.js') : false;
        }

        // Define folders where we can find assets
        $pluginsFolder = ForumEnv::get('FEATHER_ROOT').'plugins';
        $styleFolder = ForumEnv::get('FEATHER_ROOT').'style';
        // Get all stylesheets
        $pluginStyles = $manager->getAssets($pluginsFolder, 'css');
        $styleStyles = $manager->getAssets($styleFolder, 'css');
        $stylesheets = array_merge($pluginStyles, $styleStyles);
        // Get all javascript files
        $pluginScripts = $manager->getAssets($pluginsFolder, 'js');
        $styleScripts = $manager->getAssets($styleFolder, 'js');
        $scripts = array_merge($pluginScripts, $styleScripts);

        // Set last modification date to newer modified files and use relative path
        $last_modified_style = 0;
        $last_modified_script = 0;
        foreach ($stylesheets as $key => $style) {
            if (filemtime($style) > $last_modified_style) $last_modified_style = filemtime($style);
            $stylesheets[$key] = str_replace(ForumEnv::get('FEATHER_ROOT'), '', $style);
        }
        foreach ($scripts as $key => $script) {
            if (filemtime($script) > $last_modified_script) $last_modified_script = filemtime($script);
            $scripts[$key] = str_replace(ForumEnv::get('FEATHER_ROOT'), '', $script);
        }

        // Display view
        return View::setPageInfo(array(
            'title' => array(Utils::escape(ForumSettings::get('o_board_title')), __('Assets compacter', 'private_messages')),
            'admin_console' => true,
            'stylesheets' => $stylesheets,
            'scripts' => $scripts,
            'last_modified_style' => $last_modified_style,
            'last_modified_script' => $last_modified_script,
            'themes_state' => $themesState
            )
        )
        ->addTemplate('info.php')->display();
    }

}
