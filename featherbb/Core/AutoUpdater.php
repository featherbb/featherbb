<?php namespace FeatherBB\Core;

/**
 * Copyright (C) 2015-2016 FeatherBB
 * based on code "VisualAppeal/PHP-Auto-Update" by Tim Helfensdörfer
 * https://github.com/VisualAppeal/PHP-Auto-Update
 * License: https://github.com/VisualAppeal/PHP-Auto-Update/blob/master/LICENSE.md Apache-2.0
 */

use vierbergenlars\SemVer\version;
use FeatherBB\Core\Lister;
use FeatherBB\Core\AdminUtils;

/**
 * Auto update class.
 */
class AutoUpdater
{
    /**
     * The latest version.
     *
     * @var vierbergenlars\SemVer\version
     */
    private $_latestVersion = null;

    /**
     * Updates not yet installed.
     *
     * @var array
     */
    private $_updates = null;

    /**
     * Result of simulated install.
     *
     * @var array
     */
    private $_simulationResults = array();

    /**
     * Temporary download directory.
     *
     * @var string
     */
    private $_tempDir = '';

    /**
     * Install directory.
     *
     * @var string
     */
    private $_installDir = '';

    /**
     * Root folder name.
     * Used to override root folder in archive
     *
     * @var string
     */
    private $_rootFolder = '';

    /**
     * Url to the update folder on the server.
     *
     * @var string
     */
    protected $_updateUrl = 'https://marketplace.featherbb.org/updates/';

    /**
     * Version filename on the server.
     *
     * @var string
     */
    protected $_updateFile = 'update.json';

    /**
     * Current version.
     *
     * @var vierbergenlars\SemVer\version
     */
    protected $_currentVersion = null;

    /**
     * Create new folders with this privileges.
     *
     * @var int
     */
    public $dirPermissions = 0755;

    /**
     * Update script filename.
     *
     * @var string
     */
    public $updateScriptName = '_upgrade.php';

    /**
     * No update available.
     */
    const NO_UPDATE_AVAILABLE = 0;

    /**
     * Zip file could not be opened.
     */
    const ERROR_INVALID_ZIP = 10;

    /**
     * Could not check for last version.
     */
    const ERROR_VERSION_CHECK = 20;

    /**
     * Temp directory does not exist or is not writable.
     */
    const ERROR_TEMP_DIR = 30;

    /**
     * Install directory does not exist or is not writable.
     */
    const ERROR_INSTALL_DIR = 35;

    /**
     * Could not download update.
     */
    const ERROR_DOWNLOAD_UPDATE = 40;

    /**
     * Could not delete zip update file.
     */
    const ERROR_DELETE_TEMP_UPDATE = 50;

    /**
     * Error while installing the update.
     */
    const ERROR_INSTALL = 60;

    /**
     * Error in simulated install.
     */
    const ERROR_SIMULATE = 70;

    /**
     * Create new instance
     *
     * @param string $tempDir
     * @param string $installDir
     * @param int    $maxExecutionTime
     */
    public function __construct($tempDir = null, $installDir = null, $maxExecutionTime = 60)
    {
        $this->setTempDir(($tempDir !== null) ? $tempDir : __DIR__ . '/temp/');
        $this->setInstallDir(($installDir !== null) ? $installDir : __DIR__ . '/../../');

        $this->_latestVersion = new version('0.0.0');
        $this->_currentVersion = new version('0.0.0');

        ini_set('max_execution_time', $maxExecutionTime);
    }

    /**
     * Set the temporary download directory.
     *
     * @param string $dir
     * @return $this|void
     */
    public function setTempDir($dir)
    {
        // Add slash at the end of the path
        if (substr($dir, -1 != '/'))
            $dir = $dir . '/';

        if (!is_dir($dir)) {
            if (!mkdir($dir, 0755, true)) {
                // $this->_log->addCritical(sprintf('Could not create temporary directory "%s"', $dir));

                return;
            }
        }

        $this->_tempDir = $dir;

        return $this;
    }

    /**
     * Set the install directory.
     *
     * @param string $dir
     * @return $this|void
     */
    public function setInstallDir($dir)
    {
        // Add slash at the end of the path
        if (substr($dir, -1 != '/'))
            $dir = $dir . '/';

        if (!is_dir($dir)) {
            if (!mkdir($dir, 0755, true)) {
                // $this->_log->addCritical(sprintf('Could not create temporary directory "%s"', $dir));

                return;
            }
        }

        $this->_installDir = $dir;

        return $this;
    }

    /**
     * Set the root folder name.
     *
     * @param string $folder
     * @return $this|void
     */
    public function setRootFolder($folder)
    {
        $this->_rootFolder = $folder;

        return $this;
    }

    /**
     * Set the update filename.
     *
     * @param string $updateFile
     * @return $this
     */
    public function setUpdateFile($updateFile)
    {
        $this->_updateFile = $updateFile;

        return $this;
    }

    /**
     * Set the update filename.
     *
     * @param string $updateUrl
     * @return $this
     */
    public function setUpdateUrl($updateUrl)
    {
        $this->_updateUrl = $updateUrl;

        return $this;
    }

    /**
     * Set the version of the current installed software.
     *
     * @param string $currentVersion
     *
     * @return bool
     */
    public function setCurrentVersion($currentVersion)
    {
        $version = new version($currentVersion);
        if ($version->valid() === null) {
            // $this->_log->addError(sprintf('Invalid current version "%s"', $currentVersion));

            return false;
        }

        $this->_currentVersion = $version;

        return $this;
    }

    /**
     * Get the name of the latest version.
     *
     * @return vierbergenlars\SemVer\version
     */
    public function getLatestVersion()
    {
        return $this->_latestVersion;
    }

    /**
     * Get an array of versions which will be installed.
     *
     * @return array
     */
    public function getVersionsToUpdate()
    {
        return array_map(function ($update) {
            return $update['version'];
        }, $this->_updates);
    }

    /**
     * Get the results of the last simulation.
     *
     * @return array
     */
    public function getSimulationResults()
    {
        return $this->_simulationResults;
    }

    /**
     * Remove directory recursively.
     *
     * @param string $dir
     *
     * @return void
     */
    private function _removeDir($dir)
    {
        // $this->_log->addDebug(sprintf('Remove directory "%s"', $dir));

        if (!is_dir($dir)) {
            // $this->_log->addWarning(sprintf('"%s" is not a directory!', $dir));

            return false;
        }

        $objects = array_diff(scandir($dir), array('.', '..'));
        foreach ($objects as $object) {
            if (is_dir($dir . '/' . $object))
                $this->_removeDir($dir . '/' . $object);
            else
                unlink($dir . '/' . $object);
        }

        return rmdir($dir);
    }

    /**
     * Skip auto-update process if needed
     *
     * @return bool
     */
    public function skipUpdate()
    {
        return $this->_skipUpdate === true;
    }

    /**
     * Check for a new version
     *
     * @return int|bool
     *         true: New version is available
     *         false: Error while checking for update
     *         int: Status code (i.e. AutoUpdate::NO_UPDATE_AVAILABLE)
     */
    public function checkUpdate()
    {
        // $this->_log->addNotice('Checking for a new update...');

        // Reset previous updates
        $this->_latestVersion = new version('0.0.0');
        $this->_updates = [];

        // $releases = $this->_cache->get('update-versions');

        // Check if cache is empty
        // if ($releases === false) {
            // Create absolute url to update file
            // $updateFile = $this->_updateUrl . '/' . $this->_updateFile;
            $updateFile = $this->_updateUrl;

            // $this->_log->addDebug(sprintf('Get new updates from %s', $updateFile));

            // Read update file from update server
            $update = AdminUtils::get_content($updateFile);
            if ($update === false) {
                // $this->_log->addInfo(sprintf('Could not download update file "%s"!', $updateFile));
                // echo sprintf('Could not download update file "%s"!', $updateFile);

                return false;
            }

            $releases = (array)@json_decode($update);
            if (!is_array($releases)) {
                // $this->_log->addInfo('Unable to parse json update file!');

                return false;
            }

            // $this->_cache->set('update-versions', $releases);
        // } else {
            // $this->_log->addDebug('Got updates from cache');
        // }

        // Check for latest version
        foreach ($releases as $releaseContent) {
            $versionRaw = $releaseContent->tag_name;
            $updateUrl = $releaseContent->zipball_url;
            $version = new version($versionRaw);
            if ($version->valid() === null) {
                // $this->_log->addInfo(sprintf('Could not parse version "%s" from update server "%s"', $versionRaw, $updateFile));
                continue;
            }

            if (version::gt($version, $this->_currentVersion)) {
                if (version::gt($version, $this->_latestVersion))
                    $this->_latestVersion = $version;

                $this->_updates[] = [
                    'version' => $version,
                    'url'     => $updateUrl
                ];
            }
        }

        // Sort versions to install
        usort($this->_updates, function ($a, $b) {
            return version::compare($a['version'], $b['version']);
        });

        if ($this->newVersionAvailable()) {
            // $this->_log->addDebug(sprintf('New version "%s" available', $this->_latestVersion));

            return true;
        } else {
            // $this->_log->addDebug('No new version available');

            return self::NO_UPDATE_AVAILABLE;
        }
    }

    /**
     * Check if a new version is available.
     *
     * @return bool
     */
    public function newVersionAvailable()
    {
        return version::gt($this->_latestVersion, $this->_currentVersion);
    }

    /**
     * Download the update
     *
     * @param string $updateUrl Url where to download from
     * @param string $updateFile Path where to save the download
     *
     * @return bool
     */
    protected function _downloadUpdate($updateUrl, $updateFile)
    {
        // $this->_log->addInfo(sprintf('Downloading update "%s" to "%s"', $updateUrl, $updateFile));
        $update = AdminUtils::get_content($updateUrl);

        if ($update === false) {
            // $this->_log->addError(sprintf('Could not download update "%s"!', $updateUrl));

            return false;
        }

        $handle = fopen($updateFile, 'w');

        if (!$handle) {
            // $this->_log->addError(sprintf('Could not open file handle to save update to "%s"!', $updateFile));

            return false;
        }

        if (!fwrite($handle, $update)) {
            // $this->_log->addError(sprintf('Could not write update to file "%s"!', $updateFile));
            fclose($handle);

            return false;
        }

        fclose($handle);

        return true;
    }

    /**
     * Simulate update process.
     *
     * @param string $updateFile
     *
     * @return bool
     */
    protected function _simulateInstall($updateFile)
    {
        // $this->_log->addNotice('[SIMULATE] Install new version');
        clearstatcache();

        // Check if zip file could be opened
        $zip = zip_open($updateFile);
        if (!is_resource($zip)) {
            // $this->_log->addError(sprintf('Could not open zip file "%s", error: %d', $updateFile, $zip));
            // echo sprintf('Could not open zip file "%s", error: %d', $updateFile, $zip);

            return false;
        }

        $i = -1;
        $files = [];
        $simulateSuccess = true;

        while ($file = zip_read($zip)) {
            $i++;

            $filename = zip_entry_name($file);
            // Override first part of archive path
            $parts = explode('/', $filename);
            $parts[0] = $this->_rootFolder;
            $filename = implode('/', $parts);
            $foldername = $this->_installDir . dirname($filename);
            $absoluteFilename = $this->_installDir . $filename;

            $files[$i] = [
                'filename'          => $filename,
                'foldername'        => $foldername,
                'absolute_filename' => $absoluteFilename,
            ];

            // $this->_log->addDebug(sprintf('[SIMULATE] Updating file "%s"', $filename));
            echo sprintf('[SIMULATE] Updating file "%s"<br>', $filename);

            // Check if parent directory is writable
            if (!is_dir($foldername)) {
                // $this->_log->addDebug(sprintf('[SIMULATE] Create directory "%s"', $foldername));
                echo sprintf('[SIMULATE] Create directory "%s"<br>', $foldername);
                $files[$i]['parent_folder_exists'] = false;

                $parent = dirname($foldername);
                if (!is_writable($parent)) {
                    $files[$i]['parent_folder_writable'] = false;

                    $simulateSuccess = false;
                    // $this->_log->addWarning(sprintf('[SIMULATE] Directory "%s" has to be writeable!', $parent));
                    echo sprintf('[SIMULATE] Directory "%s" has to be writeable!<br>', $parent);
                } else {
                    $files[$i]['parent_folder_writable'] = true;
                }
            }

            // Skip if entry is a directory
            if (substr($filename, -1, 1) == '/')
                continue;

            // Read file contents from archive
            $contents = zip_entry_read($file, zip_entry_filesize($file));
            if ($contents === false) {
                $files[$i]['extractable'] = false;

                $simulateSuccess = false;
                // $this->_log->addWarning(sprintf('[SIMULATE] Coud not read contents of file "%s" from zip file!', $filename));
                echo sprintf('[SIMULATE] Coud not read contents of file "%s" from zip file!<br>', $filename);
            }

            // Write to file
            if (file_exists($absoluteFilename)) {
                $files[$i]['file_exists'] = true;
                if (!is_writable($absoluteFilename)) {
                    $files[$i]['file_writable'] = false;

                    $simulateSuccess = false;
                    // $this->_log->addWarning('[SIMULATE] Could not overwrite "%s"!', $absoluteFilename);
                    echo sprintf('[SIMULATE] Could not overwrite "%s"!<br>', $absoluteFilename);
                }
            } else {
                $files[$i]['file_exists'] = false;

                if (is_dir($foldername)) {
                    if (!is_writable($foldername)) {
                        $files[$i]['file_writable'] = false;

                        $simulateSuccess = false;
                        // $this->_log->addWarning(sprintf('[SIMULATE] The file "%s" could not be created!', $absoluteFilename));
                        echo sprintf('[SIMULATE] The file "%s" could not be created!<br>', $absoluteFilename);
                    } else {
                        $files[$i]['file_writable'] = true;
                    }
                } else {
                    $files[$i]['file_writable'] = true;

                    // $this->_log->addDebug(sprintf('[SIMULATE] The file "%s" could be created', $absoluteFilename));
                    echo sprintf('[SIMULATE] The file "%s" could be created<br>', $absoluteFilename);
                }
            }

            if ($filename == $this->updateScriptName) {
                // $this->_log->addDebug(sprintf('[SIMULATE] Update script "%s" found', $absoluteFilename));
                echo sprintf('[SIMULATE] Update script "%s" found<br>', $absoluteFilename);
                $files[$i]['update_script'] = true;
            } else {
                $files[$i]['update_script'] = false;
            }
        }

        $this->_simulationResults = $files;

        return $simulateSuccess;
    }

    /**
     * Install update.
     *
     * @param string $updateFile Path to the update file
     * @param bool   $simulateInstall Check for directory and file permissions before copying files
     *
     * @return bool
     */
    protected function _install($updateFile, $simulateInstall, $version)
    {
        // $this->_log->addNotice(sprintf('Trying to install update "%s"', $updateFile));

        // Check if install should be simulated
        if ($simulateInstall && !$this->_simulateInstall($updateFile)) {
            // $this->_log->addCritical('Simulation of update process failed!');
            // zip_close($zip);

            return self::ERROR_SIMULATE;
        }

        clearstatcache();

        // Check if zip file could be opened
        $zip = zip_open($updateFile);
        if (!is_resource($zip)) {
            // $this->_log->addError(sprintf('Could not open zip file "%s", error: %d', $updateFile, $zip));

            return false;
        }

        // Read every file from archive
        while ($file = zip_read($zip)) {
            $filename = zip_entry_name($file);
            // Override first part of archive path
            $parts = explode('/', $filename);
            $parts[0] = $this->_rootFolder;
            $filename = implode('/', $parts);
            $foldername = $this->_installDir . dirname($filename);
            $absoluteFilename = $this->_installDir . $filename;

            // $this->_log->addDebug(sprintf('Updating file "%s"', $filename));

            if (!is_dir($foldername)) {
                if (!mkdir($foldername, $this->dirPermissions, true)) {
                    // $this->_log->addError(sprintf('Directory "%s" has to be writeable!', $parent));

                    return false;
                }
            }

            // Skip if entry is a directory
            if (substr($filename, -1, 1) == '/')
                continue;

            // Read file contents from archive
            $contents = zip_entry_read($file, zip_entry_filesize($file));

            if ($contents === false) {
                // $this->_log->addError(sprintf('Coud not read zip entry "%s"', $file));
                continue;
            }

            // Write to file
            if (file_exists($absoluteFilename)) {
                if (!is_writable($absoluteFilename)) {
                    // $this->_log->addError('Could not overwrite "%s"!', $absoluteFilename);

                    zip_close($zip);

                    return false;
                }
            } else {
                if (!touch($absoluteFilename)) {
                    // $this->_log->addError(sprintf('[SIMULATE] The file "%s" could not be created!', $absoluteFilename));
                    zip_close($zip);

                    return false;
                }

                // $this->_log->addDebug(sprintf('File "%s" created', $absoluteFilename));
            }

            $updateHandle = @fopen($absoluteFilename, 'w');

            if (!$updateHandle) {
                // $this->_log->addError(sprintf('Could not open file "%s"!', $absoluteFilename));
                zip_close($zip);

                return false;
            }

            if (!fwrite($updateHandle, $contents)) {
                // $this->_log->addError(sprintf('Could not write to file "%s"!', $absoluteFilename));
                zip_close($zip);

                return false;
            }

            fclose($updateHandle);

            //If file is a update script, include
            if ($filename == $this->updateScriptName) {
                // $this->_log->addDebug(sprintf('Try to include update script "%s"', $absoluteFilename));
                require($absoluteFilename);

                // $this->_log->addInfo(sprintf('Update script "%s" included!', $absoluteFilename));
                if (!unlink($absoluteFilename)) {
                    // $this->_log->addWarning(sprintf('Could not delete update script "%s"!', $absoluteFilename));
                }
            }
        }

        zip_close($zip);

        // TODO
        // $this->_log->addNotice(sprintf('Update "%s" successfully installed', $version));

        return true;
    }


    /**
     * Update to the latest version
     *
     * @param bool $simulateInstall Check for directory and file permissions before copying files (Default: true)
     * @param bool $deleteDownload Delete download after update (Default: true)
     *
     * @return mixed integer|bool
     */
    public function update($simulateInstall = true, $deleteDownload = true)
    {
        // $this->_log->addInfo('Trying to perform update');

        // Check for latest version
        if ($this->_latestVersion === null || count($this->_updates) === 0)
            $this->checkUpdate();

        if ($this->_latestVersion === null || count($this->_updates) === 0) {
            // $this->_log->addError('Could not get latest version from server!');

            return self::ERROR_VERSION_CHECK;
        }

        // Check if current version is up to date
        if (!$this->newVersionAvailable()) {
            // $this->_log->addWarning('No update available!');

            return self::NO_UPDATE_AVAILABLE;
        }

        foreach ($this->_updates as $update) {
            // $this->_log->addDebug(sprintf('Update to version "%s"', $update['version']));

            // Check for temp directory
            if (empty($this->_tempDir) || !is_dir($this->_tempDir) || !is_writable($this->_tempDir)) {
                // $this->_log->addCritical(sprintf('Temporary directory "%s" does not exist or is not writeable!', $this->_tempDir));

                return self::ERROR_TEMP_DIR;
            }

            // Check for install directory
            if (empty($this->_installDir) || !is_dir($this->_installDir) || !is_writable($this->_installDir)) {
                // $this->_log->addCritical(sprintf('Install directory "%s" does not exist or is not writeable!', $this->_installDir));

                return self::ERROR_INSTALL_DIR;
            }

            $updateFile = $this->_tempDir . $update['version'] . '.zip';

            // Download update
            if (!is_file($updateFile)) {
                if (!$this->_downloadUpdate($update['url'], $updateFile)) {
                    // $this->_log->addCritical(sprintf('Failed to download update from "%s" to "%s"!', $update['url'], $updateFile));

                    return self::ERROR_DOWNLOAD_UPDATE;
                }

                // $this->_log->addDebug(sprintf('Latest update downloaded to "%s"', $updateFile));
            } else {
                // $this->_log->addInfo(sprintf('Latest update already downloaded to "%s"', $updateFile));
            }

            // Install update
            $result = $this->_install($updateFile, $simulateInstall, $update['version']);
            if ($result === true) {
                if ($deleteDownload) {
                    // $this->_log->addDebug(sprintf('Trying to delete update file "%s" after successfull update', $updateFile));
                    if (@unlink($updateFile)) {
                        // $this->_log->addInfo(sprintf('Update file "%s" deleted after successfull update', $updateFile));
                    } else {
                        // $this->_log->addError(sprintf('Could not delete update file "%s" after successfull update!', $updateFile));

                        return self::ERROR_DELETE_TEMP_UPDATE;
                    }
                }
            } else {
                if ($deleteDownload) {
                    // $this->_log->addDebug(sprintf('Trying to delete update file "%s" after failed update', $updateFile));
                    if (@unlink($updateFile)) {
                        // $this->_log->addInfo(sprintf('Update file "%s" deleted after failed update', $updateFile));
                    } else {
                        // $this->_log->addError(sprintf('Could not delete update file "%s" after failed update!', $updateFile));
                    }
                }

                return $result;
            }
        }

        return true;
    }

    /**
     * Bulk cURL requests
     *
     * @param array $urls URLs to connect to
     *
     * @return array Array of [url => body content]
     */
    // protected function _bulkRequests($urls = array())
    // {
    //     $multi = curl_multi_init();
    //     $channels = array();
    //     // Loop through the URLs so request, create curl-handles,
    //     // attach the handle to our multi-request
    //     foreach ($urls as $url) {
    //         $ch = curl_init();
    //         curl_setopt($ch, CURLOPT_URL, $url);
    //         curl_setopt($ch, CURLOPT_HEADER, false);
    //         curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    //         curl_multi_add_handle($multi, $ch);
    //         $channels[$url] = $ch;
    //     }
    //     // While we're still active, execute curl
    //     $active = null;
    //     do {
    //         $mrc = curl_multi_exec($multi, $active);
    //     } while ($mrc == CURLM_CALL_MULTI_PERFORM);
    //     while ($active && $mrc == CURLM_OK) {
    //         // Wait for activity on any curl-connection
    //         if (curl_multi_select($multi) == -1) {
    //             continue;
    //         }
    //         // Continue to exec until curl is ready to
    //         // give us more data
    //         do {
    //             $mrc = curl_multi_exec($multi, $active);
    //         } while ($mrc == CURLM_CALL_MULTI_PERFORM);
    //     }
    //     // Loop through the channels and retrieve the received
    //     // content, then remove the handle from the multi-handle
    //     $results = array();
    //     foreach ($channels as $url => $channel) {
    //         $results[$url] = curl_multi_getcontent($channel);
    //         curl_multi_remove_handle($multi, $channel);
    //     }
    //     // Close the multi-handle and return our results
    //     curl_multi_close($multi);
    //     return $results;
    // }
}

class PluginAutoUpdater extends AutoUpdater {

    public function __construct($plugin)
    {
        // Construct parent class
        parent::__construct(getcwd().'/temp/plugin-'.$plugin->name, getcwd().'/plugins');

        // Set plugin informations
        $this->setRootFolder($plugin->name);
        $this->setCurrentVersion($plugin->version);
        $this->setUpdateUrl(isset($plugin->update_url) ? $plugin->update_url : 'https://api.github.com/repos/featherbb/'.$plugin->name.'/releases');
    }
}
