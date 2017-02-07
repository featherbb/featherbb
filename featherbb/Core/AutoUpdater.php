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
    private $_simulationResults = [];

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
     * Content of errors occurred when updating.
     *
     * @var array
     */
    protected $_errors = [];

    /**
     * Content of warnings fired when updating.
     *
     * @var array
     */
    protected $_warnings = [];

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
        $this->_errors = [];
        $this->_warnings = [];

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
        if (substr($dir, -1 != '/')) {
            $dir = $dir . '/';
        }

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
        if (substr($dir, -1 != '/')) {
            $dir = $dir . '/';
        }

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
     * Get the results of errors encountered.
     *
     * @return array
     */
    public function getErrors()
    {
        return $this->_errors;
    }

    /**
     * Get the results of warnings encountered.
     *
     * @return array
     */
    public function getWarnings()
    {
        return $this->_warnings;
    }

    /**
     * Add a warning
     *
     * @param $warning warning to add
     */
    public function addWarning($warning)
    {
        $this->_warnings[] = $warning;
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

        $objects = array_diff(scandir($dir), ['.', '..']);
        foreach ($objects as $object) {
            if (is_dir($dir . '/' . $object)) {
                $this->_removeDir($dir . '/' . $object);
            } else {
                unlink($dir . '/' . $object);
            }
        }

        return rmdir($dir);
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
        // Reset previous updates
        $this->_latestVersion = new version('0.0.0');
        $this->_updates = [];

        // $releases = $this->_cache->get('update-versions');

        // Check if cache is empty
        // if ($releases === false) {
            // Create absolute url to update file
            // $updateFile = $this->_updateUrl . '/' . $this->_updateFile;
            $updateFile = $this->_updateUrl;

            // Read update file from update server
            $update = AdminUtils::get_content($updateFile);
        if ($update === false) {
            $this->_errors[] = sprintf(__('Could not check for updates'), $updateFile);

            return false;
        }

        $releases = (array)@json_decode($update);
        if (!is_array($releases)) {
            $this->_errors[] = __('Unable to parse json update file');

            return false;
        }

            // $this->_cache->set('update-versions', $releases);
        // }

        // Check for latest version
        foreach ($releases as $releaseContent) {
            $versionRaw = $releaseContent->tag_name;
            $updateUrl = $releaseContent->zipball_url;
            $version = new version($versionRaw);
            if ($version->valid() === null) {
                $this->_errors[] = sprintf(__('Could not parse version'), $versionRaw, $updateFile);
                continue;
            }

            if (version::gt($version, $this->_currentVersion)) {
                if (version::gt($version, $this->_latestVersion)) {
                    $this->_latestVersion = $version;
                }

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
            return true;
        } else {
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
        $update = AdminUtils::get_content($updateUrl);

        if ($update === false) {
            $this->_errors[] = sprintf(__('Could not download update'), $updateUrl);

            return false;
        }

        $handle = fopen($updateFile, 'w');

        if (!$handle) {
            $this->_errors[] = sprintf(__('Could not open file handle'), $updateFile);

            return false;
        }

        if (!fwrite($handle, $update)) {
            $this->_errors[] = sprintf(__('Could not write update to file'), $updateFile);
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
        clearstatcache();

        // Check if zip file could be opened
        $zip = zip_open($updateFile);
        if (!is_resource($zip)) {
            $this->_errors[] = sprintf(__('Could not open zip file'), $updateFile, $zip);

            return false;
        }

        $i = -1;
        $files = [];
        $simulateSuccess = true;

        while ($file = zip_read($zip)) {
            $i++;

            $filename = zip_entry_name($file);
            // Manipulate and do verifications on file path
            $parts = explode('/', $filename);
            // If we are upgrading core
            array_shift($parts);

            if ($parts[0] == '') {
                continue;
            }

            if ($this->_rootFolder == 'featherbb') {
                // // Skip if entry is not in targetted files
                if ($parts[0] == 'cache' || $parts[0] == 'style') {
                    continue;
                }
            }
            $filename = implode('/', $parts);
            $foldername = $this->_installDir . dirname($filename);
            $absoluteFilename = $this->_installDir . $filename;

            $files[$i] = [
                'filename'          => $filename,
                'foldername'        => $foldername,
                'absolute_filename' => $absoluteFilename,
            ];

            // Check if parent directory is writable
            if (!is_dir($foldername)) {
                // $this->_errors[] = sprintf('[SIMULATE] Create directory "%s"', $foldername);
                $files[$i]['parent_folder_exists'] = false;

                $parent = dirname($foldername);
                if (!mkdir($foldername, $this->dirPermissions, true) && !is_writable($parent)) {
                    $files[$i]['parent_folder_writable'] = false;

                    $simulateSuccess = false;
                    $this->_errors[] = sprintf(__('Directory not writable'), $parent);
                } else {
                    $files[$i]['parent_folder_writable'] = true;
                }
            }

            // Skip if entry is a directory
            if (substr($filename, -1, 1) == '/') {
                continue;
            }

            // Read file contents from archive
            $contents = zip_entry_read($file, zip_entry_filesize($file));
            if ($contents === false) {
                $files[$i]['extractable'] = false;

                $simulateSuccess = false;
                $this->_errors[] = sprintf(__('Coud not read zip entry'), $filename);
            }

            // Write to file
            if (file_exists($absoluteFilename)) {
                $files[$i]['file_exists'] = true;
                if (!is_writable($absoluteFilename)) {
                    $files[$i]['file_writable'] = false;

                    $simulateSuccess = false;
                    $this->_errors[] = sprintf(__('Could not overwrite file'), $absoluteFilename);
                }
            } else {
                $files[$i]['file_exists'] = false;

                if (is_dir($foldername)) {
                    if (!is_writable($foldername)) {
                        if (!mkdir($foldername, $this->dirPermissions, true)) {
                            $files[$i]['file_writable'] = false;

                            $simulateSuccess = false;
                            $this->_errors[] = sprintf(__('File could not be created'), $absoluteFilename);
                        }
                    } else {
                        $files[$i]['file_writable'] = true;
                    }
                } else {
                    $files[$i]['file_writable'] = true;
                }
            }

            if ($filename == $this->updateScriptName) {
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
        // Check if install should be simulated
        if ($simulateInstall && !$this->_simulateInstall($updateFile)) {
            // $this->_errors[] = 'Simulation of update process failed!';

            return self::ERROR_SIMULATE;
        }

        clearstatcache();

        // Check if zip file could be opened
        $zip = zip_open($updateFile);
        if (!is_resource($zip)) {
            $this->_errors[] = sprintf(__('Could not open zip file'), $updateFile, $zip);

            return false;
        }

        // Read every file from archive
        while ($file = zip_read($zip)) {
            $filename = zip_entry_name($file);
            // Remove first part of archive path
            $parts = explode('/', $filename);
            array_shift($parts);

            if ($parts[0] == '') {
                continue;
            }

            if ($this->_rootFolder == 'featherbb') {
                // // Skip if entry is not in targetted files
                if ($parts[0] == 'cache' || $parts[0] == 'style') {
                    continue;
                }
            }
            $filename = implode('/', $parts);
            $foldername = $this->_installDir . dirname($filename);
            $absoluteFilename = $this->_installDir . $filename;

            if (!is_dir($foldername)) {
                if (!mkdir($foldername, $this->dirPermissions, true)) {
                    $this->_errors[] = sprintf(__('Directory not writable'), $parent);

                    return false;
                }
            }

            // Skip if entry is a directory
            if (substr($filename, -1, 1) == '/') {
                continue;
            }

            // Read file contents from archive
            $contents = zip_entry_read($file, zip_entry_filesize($file));

            if ($contents === false) {
                $this->_errors[] = sprintf(__('Coud not read zip entry'), $file);
                continue;
            }

            // Write to file
            if (file_exists($absoluteFilename)) {
                if (!is_writable($absoluteFilename)) {
                    $this->_errors[] = sprintf(__('Could not overwrite file'), $absoluteFilename);

                    zip_close($zip);

                    return false;
                }
            } else {
                if (!touch($absoluteFilename)) {
                    $this->_errors[] = sprintf(__('File could not be created'), $absoluteFilename);
                    zip_close($zip);

                    return false;
                }
            }

            $updateHandle = @fopen($absoluteFilename, 'w');

            if (!$updateHandle) {
                $this->_errors[] = sprintf(__('Could not open file'), $absoluteFilename);
                zip_close($zip);

                return false;
            }

            if (!fwrite($updateHandle, $contents)) {
                $this->_errors[] = sprintf(__('Could not write to file'), $absoluteFilename);
                zip_close($zip);

                return false;
            }

            fclose($updateHandle);

            //If file is a update script, include
            if ($filename == $this->updateScriptName) {
                $upgrade_script = true;
                require($absoluteFilename);

                if (!unlink($absoluteFilename)) {
                    $this->_warnings[] = sprintf(__('Could not delete update script'), $absoluteFilename);
                }
            }
        }

        zip_close($zip);

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
        // Check for latest version
        if ($this->_latestVersion === null || count($this->_updates) === 0) {
            $this->checkUpdate();
        }

        if ($this->_latestVersion === null || count($this->_updates) === 0) {
            $this->_errors[] = __('Could not get latest version');

            return self::ERROR_VERSION_CHECK;
        }

        // Check if current version is up to date
        if (!$this->newVersionAvailable()) {
            // $this->_log->addWarning('No update available!');

            return self::NO_UPDATE_AVAILABLE;
        }

        foreach ($this->_updates as $update) {
            // Check for temp directory
            if (empty($this->_tempDir) || !is_dir($this->_tempDir) || !is_writable($this->_tempDir)) {
                $this->_errors[] = sprintf(__('Temporary directory not writable'), $this->_tempDir);

                return self::ERROR_TEMP_DIR;
            }

            // Check for install directory
            if (empty($this->_installDir) || !is_dir($this->_installDir) || !is_writable($this->_installDir)) {
                $this->_errors[] = sprintf(__('Install directory not writable'), $this->_installDir);

                return self::ERROR_INSTALL_DIR;
            }

            $updateFile = $this->_tempDir . $this->_rootFolder .'-v' . $update['version'] . '.zip';

            // Download update
            if (!is_file($updateFile)) {
                if (!$this->_downloadUpdate($update['url'], $updateFile)) {
                    $this->_errors[] = sprintf(__('Failed to download update'), $update['url'], $updateFile);

                    return self::ERROR_DOWNLOAD_UPDATE;
                }
            }

            // Install update
            $result = $this->_install($updateFile, $simulateInstall, $update['version']);
            if ($result === true) {
                if ($deleteDownload) {
                    if (!@unlink($updateFile)) {
                        $this->_warnings[] = sprintf(__('Could not delete update file'), $updateFile);

                        // return self::ERROR_DELETE_TEMP_UPDATE;
                    }
                }
            } else {
                if ($deleteDownload) {
                    if (!@unlink($updateFile)) {
                        $this->_warnings[] = sprintf(__('Could not delete update file'), $updateFile);
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

class PluginAutoUpdater extends AutoUpdater
{
    public function __construct($plugin)
    {
        // Construct parent class
        parent::__construct(getcwd().'/temp', getcwd().'/plugins/'.$plugin->name);

        // Set plugin informations
        $this->setRootFolder($plugin->name);
        $this->setCurrentVersion($plugin->version);
        $this->setUpdateUrl(isset($plugin->update_url) ? $plugin->update_url : 'https://api.github.com/repos/featherbb/'.$plugin->name.'/releases');
    }
}

class CoreAutoUpdater extends AutoUpdater
{
    public function __construct()
    {
        // Construct parent class
        parent::__construct(getcwd().'/temp', getcwd());

        // Set plugin informations
        $this->setRootFolder('featherbb');
        $this->setCurrentVersion(ForumEnv::get('FORUM_VERSION'));
        $this->setUpdateUrl('https://api.github.com/repos/featherbb/featherbb/releases');
    }
}
