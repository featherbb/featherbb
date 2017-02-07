<?php

/**
 * Copyright (C) 2015-2017 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace FeatherBB\Model\Admin;

use FeatherBB\Core\AdminUtils;
use FeatherBB\Core\Database as DB;
use FeatherBB\Core\Error;
use FeatherBB\Core\Plugin as PluginManager;
use FeatherBB\Core\Utils;
use ZipArchive;

class Plugins
{
    protected $manager;

    public function __construct()
    {
        $this->manager = new PluginManager();
    }

    public function activate($name)
    {
        $name = Container::get('hooks')->fire('model.plugin.activate.name', $name);

        // Check if plugin name is valid
        if ($class = $this->manager->load($name)) {
            $activePlugins = $this->manager->getActivePlugins();

            // Check if plugin is not yet activated...
            if (!in_array($name, $activePlugins)) {
                // Find or create plugin in DB...
                $plugin = DB::for_table('plugins')->where('name', $name)->find_one();
                if (!$plugin) {
                    $plugin = DB::for_table('plugins')->create()->set('name', $name);
                }

                // ... Install it if needed ...
                if ($plugin->installed != 1) {
                    if (method_exists($class, 'install')) {
                        $class->install();
                    }
                    $plugin->set('installed', 1);
                }

                // ... Save in DB ...
                $plugin->set('active', 1);
                $plugin = Container::get('hooks')->fireDB('model.plugin.activate', $plugin);
                $plugin->save();

                // ... And regenerate cache.
                $this->manager->setActivePlugins();

                return $plugin;
            }
            return true;
        }
        return false;
    }

    /**
     * Deactivate a plugin
     */
    public function deactivate($name)
    {
        $name = Container::get('hooks')->fire('model.plugin.deactivate.name', $name);

        // Check if plugin name is valid
        if ($class = $this->manager->load($name)) {
            $activePlugins = $this->manager->getActivePlugins();

            // Check if plugin is actually activated
            if (($k = array_search($name, $activePlugins)) !== false) {
                $plugin = DB::for_table('plugins')->where('name', $name)->find_one();
                if (!$plugin) {
                    $plugin = DB::for_table('plugins')->create()->set('name', $name);
                }

                // Do we need to run extra code for deactivation ?
                if (method_exists($class, 'pause')) {
                    $class->pause();
                }

                $plugin->set('active', 0);
                $plugin = Container::get('hooks')->fireDB('model.plugin.deactivate', $plugin);
                $plugin->save();

                // Regenerate cache
                $this->manager->setActivePlugins();

                return $plugin;
            }
            return true;
        }
        return false;
    }

    /**
     * Uninstall a plugin after deactivated
     */
    public function uninstall($name)
    {
        $name = Container::get('hooks')->fire('model.plugin.uninstall.name', $name);

        // Check if plugin name is valid
        if ($class = $this->manager->load($name)) {
            $activePlugins = $this->manager->getActivePlugins();

            // Check if plugin is disabled, for security
            if (!in_array($name, $activePlugins)) {
                $plugin = DB::for_table('plugins')->where('name', $name)->find_one();

                if ($plugin) {
                    $plugin->delete();
                }

                // Do we need to run extra code for uninstallation ?
                if (method_exists($class, 'remove')) {
                    $class->remove();
                }

                if (file_exists(ForumEnv::get('FEATHER_ROOT').'plugins'.DIRECTORY_SEPARATOR.$name)) {
                    AdminUtils::delete_folder(ForumEnv::get('FEATHER_ROOT').'plugins'.DIRECTORY_SEPARATOR.$name);
                }

                // Regenerate cache
                $this->manager->setActivePlugins();
            }
            return true;
        }
        return false;
    }

    /**
     * Download a plugin, unzip it and rename it
     */
    public function download($args)
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
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
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

        if ($zip->open($zipFile) != true) {
            throw new Error(__('Bad request'), 400);
        }

        $zip->extractTo(ForumEnv::get('FEATHER_ROOT').'plugins');
        $zip->close();

        if (file_exists(ForumEnv::get('FEATHER_ROOT').'plugins'.DIRECTORY_SEPARATOR.$args['name'])) {
            AdminUtils::delete_folder(ForumEnv::get('FEATHER_ROOT').'plugins'.DIRECTORY_SEPARATOR.$args['name']);
        }
        rename(ForumEnv::get('FEATHER_ROOT').'plugins'.DIRECTORY_SEPARATOR.$args['name']."-".$args['version'], ForumEnv::get('FEATHER_ROOT').'plugins'.DIRECTORY_SEPARATOR.$args['name']);
        unlink(ForumEnv::get('FEATHER_ROOT').'plugins'.DIRECTORY_SEPARATOR.$args['name']."-".$args['version'].'.zip');
        return Router::redirect(Router::pathFor('adminPlugins'), __('Plugin downloaded'));
    }

    /**
     * Upload a plugin manually
     */
    public function upload_plugin($files_data)
    {
        if (!isset($files_data['req_file'])) {
            throw new Error(__('No file'));
        }

        $uploaded_file = $files_data['req_file'];

        // Make sure the upload went smooth
        if (isset($uploaded_file['error'])) {
            switch ($uploaded_file['error']) {
                case 1: // UPLOAD_ERR_INI_SIZE
                case 2: // UPLOAD_ERR_FORM_SIZE
                    throw new Error(__('Too large ini'));
                    break;

                case 3: // UPLOAD_ERR_PARTIAL
                    throw new Error(__('Partial upload'));
                    break;

                case 4: // UPLOAD_ERR_NO_FILE
                    throw new Error(__('No file'));
                    break;

                case 6: // UPLOAD_ERR_NO_TMP_DIR
                    throw new Error(__('No tmp directory'));
                    break;

                default:
                    // No error occured, but was something actually uploaded?
                    if ($uploaded_file['size'] == 0) {
                        throw new Error(__('No file'));
                    }
                    break;
            }
        }

        $name = $uploaded_file['name'];

        if (is_uploaded_file($uploaded_file['tmp_name'])) {
            // Preliminary file check, adequate in most cases
            $allowed_types = ['application/zip', 'application/x-compressed', 'application/x-zip-compressed', 'application/download'];
            if (!in_array($uploaded_file['type'], $allowed_types)) {
                throw new Error(__('Bad type'));
            }

            // Move the file to the plugin directory
            if (!@move_uploaded_file($uploaded_file['tmp_name'], ForumEnv::get('FEATHER_ROOT').'plugins'.'/'.$name)) {
                throw new Error(__('Move failed'));
            }

            @chmod(ForumEnv::get('FEATHER_ROOT').'plugins'.'/'.$name, 0644);
        } else {
            throw new Error(__('Unknown failure'));
        }

        $zip = new ZipArchive;

        if ($zip->open(ForumEnv::get('FEATHER_ROOT').'plugins'.'/'.$name) != true) {
            throw new Error(__('Bad request'), 400);
        }

        $zip->extractTo(ForumEnv::get('FEATHER_ROOT').'plugins');
        $zip->close();

        $cleaned_name = preg_replace('/[0-9]+/', '', $name);
        $cleaned_name = str_replace('.', '', $cleaned_name);
        $cleaned_name = str_replace('-zip', '', $cleaned_name);
        $cleaned_name = str_replace('zip', '', $cleaned_name);

        if (file_exists(ForumEnv::get('FEATHER_ROOT').'plugins'.DIRECTORY_SEPARATOR.$cleaned_name)) {
            AdminUtils::delete_folder(ForumEnv::get('FEATHER_ROOT').'plugins'.DIRECTORY_SEPARATOR.$name);
        }

        $name_nozip = str_replace('-zip', '', $name);
        $name_nozip = str_replace('zip', '', $name_nozip);

        rename(ForumEnv::get('FEATHER_ROOT').'plugins'.DIRECTORY_SEPARATOR.$name_nozip, ForumEnv::get('FEATHER_ROOT').'plugins'.DIRECTORY_SEPARATOR.$cleaned_name);
        unlink(ForumEnv::get('FEATHER_ROOT').'plugins'.DIRECTORY_SEPARATOR.$name);
        return Router::redirect(Router::pathFor('adminPlugins'), __('Plugin downloaded'));
    }
}
