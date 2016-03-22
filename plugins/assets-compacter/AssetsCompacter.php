<?php

/**
 * Copyright (C) 2015-2016 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace FeatherBB\Plugins;

use FeatherBB\Core\Plugin as BasePlugin;
use FeatherBB\Core\Lister;

require 'Controller/AssetsCompacter.php';

class AssetsCompacter extends BasePlugin
{

    public static $_compactedStyles = 'cmpct-styles.min.css';
    public static $_compactedScripts = 'cmpct-scripts.min.js';

    public function run()
    {
        // Remove css and js files from "$data" var sent to views, and replace it with minified assets
        // Container::get('hooks')->bind('view.alter_data', [$this, 'sendMinifiedAssets']);
        Container::get('hooks')->bind('admin.plugin.menu', [$this, 'getName']);
    }

    public function sendMinifiedAssets($data)
    {
        var_dump($data['assets']);
        // translate('bbeditor', 'featherbb', false, __DIR__.'/lang');
        // var_dump(View::getAssets());
        // var_dump($data['assets']);
        // $data = $data;
        return true;
        return $data;
    }

    public static function getAssets($base_dir, $extension = 'css')
    {
        $files = array();
        foreach(scandir($base_dir) as $entry) {
            // Skip dots and asset files already minified using this plugin
            if($entry == '.' || $entry == '..' || $entry == self::$_compactedStyles || $entry == self::$_compactedScripts) continue;
            $file_ext = substr(strrchr($entry,'.'), 1);
            $absolute_path = $base_dir.DIRECTORY_SEPARATOR.$entry;
            // Recursive iteration if entry is directory
            if(is_dir($absolute_path)) {
                // Skip entry if already in minified folder
                if($entry === 'min') continue;
                $files = array_merge($files, self::getAssets($absolute_path, $extension));
            }
            if ($file_ext === $extension) {
                // Add file to returned array if extension matches
                $files[] = $absolute_path;
            }
        }

        natcasesort($files);
        return $files;
    }

    public function compactAssets()
    {
        // TODO: Include each file in desired order if specified in view

        // if ($error = $this->checkErrors()) {
        //     return ['error', $error];
        // }
        foreach (Input::post('themes') as $theme => $assets) {
            $themeFolder = ForumEnv::get('FEATHER_ROOT').'style'.DIRECTORY_SEPARATOR.'themes'.DIRECTORY_SEPARATOR.$theme;
            // Make sure theme directory exists and is writable
            if (!is_dir($themeFolder) || !is_writable($themeFolder)) continue;

            // Merge and minify checked stylesheets
            $minifiedCss = '';
            foreach ($assets['stylesheets'] as $stylesheet) {
                $minifiedCss .= $this->minifyCss(file_get_contents(ForumEnv::get('FEATHER_ROOT').$stylesheet));
            }
            // Merge and minify checked javascripts
            $minifiedJs = '';
            foreach ($assets['scripts'] as $script) {
                $minifiedJs .= $this->minifyJs(file_get_contents(ForumEnv::get('FEATHER_ROOT').$script));
            }

            // Write minified assets in destination folder
            file_put_contents($themeFolder.DIRECTORY_SEPARATOR.self::$_compactedStyles, $minifiedCss);
            file_put_contents($themeFolder.DIRECTORY_SEPARATOR.self::$_compactedScripts, $minifiedJs);
        }

        return array('success', 'test');
    }

    /**
     * Minify stylesheet
     * Tribute to https://gist.github.com/tovic/d7b310dea3b33e4732c0
     */
    public function minifyCss($input = '') {
        if(trim($input) === '') return $input;
        return preg_replace(
            array(
                // Remove comment(s)
                '#("(?:[^"\\\]++|\\\.)*+"|\'(?:[^\'\\\\]++|\\\.)*+\')|\/\*(?!\!)(?>.*?\*\/)|^\s*|\s*$#s',
                // Remove unused white-space(s)
                '#("(?:[^"\\\]++|\\\.)*+"|\'(?:[^\'\\\\]++|\\\.)*+\'|\/\*(?>.*?\*\/))|\s*+;\s*+(})\s*+|\s*+([*$~^|]?+=|[{};,>~+]|\s*+-(?![0-9\.])|!important\b)\s*+|([[(:])\s++|\s++([])])|\s++(:)\s*+(?!(?>[^{}"\']++|"(?:[^"\\\]++|\\\.)*+"|\'(?:[^\'\\\\]++|\\\.)*+\')*+{)|^\s++|\s++\z|(\s)\s+#si',
                // Replace `0(cm|em|ex|in|mm|pc|pt|px|vh|vw|%)` with `0`
                '#(?<=[\s:])(0)(cm|em|ex|in|mm|pc|pt|px|vh|vw|%)#si',
                // Replace `:0 0 0 0` with `:0`
                '#:(0\s+0|0\s+0\s+0\s+0)(?=[;\}]|\!important)#i',
                // Replace `background-position:0` with `background-position:0 0`
                '#(background-position):0(?=[;\}])#si',
                // Replace `0.6` with `.6`, but only when preceded by `:`, `,`, `-` or a white-space
                '#(?<=[\s:,\-])0+\.(\d+)#s',
                // Minify string value
                '#(\/\*(?>.*?\*\/))|(?<!content\:)([\'"])([a-z_][a-z0-9\-_]*?)\2(?=[\s\{\}\];,])#si',
                '#(\/\*(?>.*?\*\/))|(\burl\()([\'"])([^\s]+?)\3(\))#si',
                // Minify HEX color code
                '#(?<=[\s:,\-]\#)([a-f0-6]+)\1([a-f0-6]+)\2([a-f0-6]+)\3#i',
                // Replace `(border|outline):none` with `(border|outline):0`
                '#(?<=[\{;])(border|outline):none(?=[;\}\!])#',
                // Remove empty selector(s)
                '#(\/\*(?>.*?\*\/))|(^|[\{\}])(?:[^\s\{\}]+)\{\}#s'
                ),
            array(
                '$1',
                '$1$2$3$4$5$6$7',
                '$1',
                ':0',
                '$1:0 0',
                '.$1',
                '$1$3',
                '$1$2$4$5',
                '$1$2$3',
                '$1:0',
                '$1$2'
            ),
            $input
        );
    }

    /**
     * Minify javascript code
     * Tribute to https://gist.github.com/tovic/d7b310dea3b33e4732c0
     */
    public function minifyJs($input = '') {
        if(trim($input) === '') return $input;
        return preg_replace(
            array(
                // Remove comment(s)
                '#\s*("(?:[^"\\\]++|\\\.)*+"|\'(?:[^\'\\\\]++|\\\.)*+\')\s*|\s*\/\*(?!\!|@cc_on)(?>[\s\S]*?\*\/)\s*|\s*(?<![\:\=])\/\/.*(?=[\n\r]|$)|^\s*|\s*$#',
                // Remove white-space(s) outside the string and regex
                '#("(?:[^"\\\]++|\\\.)*+"|\'(?:[^\'\\\\]++|\\\.)*+\'|\/\*(?>.*?\*\/)|\/(?!\/)[^\n\r]*?\/(?=[\s.,;]|[gimuy]|$))|\s*([!%&*\(\)\-=+\[\]\{\}|;:,.<>?\/])\s*#s',
                // Remove the last semicolon
                '#;+\}#',
                // Minify object attribute(s) except JSON attribute(s). From `{'foo':'bar'}` to `{foo:'bar'}`
                '#([\{,])([\'])(\d+|[a-z_][a-z0-9_]*)\2(?=\:)#i',
                // --ibid. From `foo['bar']` to `foo.bar`
                '#([a-z0-9_\)\]])\[([\'"])([a-z_][a-z0-9_]*)\2\]#i'
            ),
            array(
                '$1',
                '$1$2',
                '}',
                '$1$3',
                '$1.$3'
            ),
        $input);
    }

}
