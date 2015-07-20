<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

if (!defined('FEATHER_ROOT')) {
    exit('The constant FEATHER_ROOT must be defined and point to a valid FeatherBB installation root directory.');
}

// Define the version and database revision that this code was written for
define('FORUM_VERSION', '1.0.0');

define('FORUM_DB_REVISION', 21);
define('FORUM_SI_REVISION', 2);
define('FORUM_PARSER_REVISION', 2);

// Block prefetch requests
if (isset($_SERVER['HTTP_X_MOZ']) && $_SERVER['HTTP_X_MOZ'] == 'prefetch') {
    header('HTTP/1.1 403 Prefetching Forbidden');

    // Send no-cache headers
    header('Expires: Thu, 21 Jul 1977 07:30:00 GMT'); // When yours truly first set eyes on this world! :)
    header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
    header('Cache-Control: post-check=0, pre-check=0', false);
    header('Pragma: no-cache'); // For HTTP/1.0 compatibility

    exit;
}

// Attempt to load the configuration file config.php
if (file_exists(FEATHER_ROOT.'include/config.php')) {
    require FEATHER_ROOT.'include/config.php';
}

// Load the functions script
require FEATHER_ROOT.'include/functions.php';

// Load UTF-8 functions
require FEATHER_ROOT.'include/utf8/utf8.php';

// Strip out "bad" UTF-8 characters
forum_remove_bad_characters();

// Reverse the effect of register_globals
forum_unregister_globals();

// If FEATHER isn't defined, config.php is missing or corrupt
if (!defined('FEATHER')) {
    header('Location: install.php');
    exit;
}

// Record the start time (will be used to calculate the generation time for the page)
$feather->start = get_microtime();

// Make sure PHP reports all errors except E_NOTICE. FluxBB supports E_ALL, but a lot of scripts it may interact with, do not
//error_reporting(E_ALL ^ E_NOTICE);
error_reporting(E_ALL); // Let's report everything for development

// Force POSIX locale (to prevent functions such as strtolower() from messing up UTF-8 strings)
setlocale(LC_CTYPE, 'C');

// Turn off magic_quotes_runtime
if (get_magic_quotes_runtime()) {
    set_magic_quotes_runtime(0);
}

// Strip slashes from GET/POST/COOKIE/REQUEST/FILES (if magic_quotes_gpc is enabled)
if (!defined('FORUM_DISABLE_STRIPSLASHES') && get_magic_quotes_gpc()) {
    function stripslashes_array($array)
    {
        return is_array($array) ? array_map('stripslashes_array', $array) : stripslashes($array);
    }

    $_GET = stripslashes_array($_GET);
    $_POST = stripslashes_array($_POST);
    $_COOKIE = stripslashes_array($_COOKIE);
    $_REQUEST = stripslashes_array($_REQUEST);
    if (is_array($_FILES)) {
        // Don't strip valid slashes from tmp_name path on Windows
        foreach ($_FILES as $key => $value) {
            $_FILES[$key]['tmp_name'] = str_replace('\\', '\\\\', $value['tmp_name']);
        }
        $_FILES = stripslashes_array($_FILES);
    }
}

// If a cookie name is not specified in config.php, we use the default (pun_cookie)
if (empty($cookie_name)) {
    $cookie_name = 'feather_cookie';
}

// If the cache directory is not specified, we use the default setting
if (!defined('FORUM_CACHE_DIR')) {
    define('FORUM_CACHE_DIR', FEATHER_ROOT.'cache/');
}

// Define a few commonly used constants
define('FEATHER_UNVERIFIED', 0);
define('FEATHER_ADMIN', 1);
define('FEATHER_MOD', 2);
define('FEATHER_GUEST', 3);
define('FEATHER_MEMBER', 4);

// Load DB abstraction layer and connect
require FEATHER_ROOT.'include/dblayer/common_db.php';

 // Inject DB dependency into SlimFramework
$feather->container->singleton('db', function () use ($db_host, $db_username, $db_password, $db_name, $db_prefix, $p_connect) {
    // Create the database adapter object (and open/connect to/select db)
    return new DBLayer($db_host, $db_username, $db_password, $db_name, $db_prefix, $p_connect);
});

// Backward compatibility - to be removed soon
$db = $feather->db;

// Start a transaction
$feather->db->start_transaction();

// Include Idiorm
require FEATHER_ROOT.'include/idiorm.php';

// TODO: handle drivers
ORM::configure('mysql:host='.$db_host.';dbname='.$db_name);
ORM::configure('username', $db_username);
ORM::configure('password', $db_password);
ORM::configure('logging', true);
ORM::configure('driver_options', array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'));

// Inject DB prefix to SlimFramework
$feather->prefix = $db_prefix;

// Load cached config
if (file_exists(FORUM_CACHE_DIR.'cache_config.php')) {
    include FORUM_CACHE_DIR.'cache_config.php';
}

if (!defined('FEATHER_CONFIG_LOADED')) {
    if (!defined('FORUM_CACHE_FUNCTIONS_LOADED')) {
        require FEATHER_ROOT.'include/cache.php';
    }

    generate_config_cache();
    require FORUM_CACHE_DIR.'cache_config.php';
}

// Inject config to SlimFramework
$feather->config = $feather_config;

// Verify that we are running the proper database schema revision
if (!isset($feather->config['o_database_revision']) || $feather->config['o_database_revision'] < FORUM_DB_REVISION ||
    !isset($feather->config['o_searchindex_revision']) || $feather->config['o_searchindex_revision'] < FORUM_SI_REVISION ||
    !isset($feather->config['o_parser_revision']) || $feather->config['o_parser_revision'] < FORUM_PARSER_REVISION ||
    version_compare($feather->config['o_cur_version'], FORUM_VERSION, '<')) {
    header('Location: db_update.php');
    exit;
}

// Enable output buffering
if (!defined('FEATHER_DISABLE_BUFFERING')) {
    // Should we use gzip output compression?
    if ($feather->config['o_gzip'] && extension_loaded('zlib')) {
        ob_start('ob_gzhandler');
    } else {
        ob_start();
    }
}

// Define standard date/time formats
$forum_time_formats = array($feather->config['o_time_format'], 'H:i:s', 'H:i', 'g:i:s a', 'g:i a');
$forum_date_formats = array($feather->config['o_date_format'], 'Y-m-d', 'Y-d-m', 'd-m-Y', 'm-d-Y', 'M j Y', 'jS M Y');

// Check/update/set cookie and fetch user info
$feather_user = array();
check_cookie($feather_user);

// Attempt to load the common language file
if (file_exists(FEATHER_ROOT.'lang/'.$feather->user->language.'/common.php')) {
    include FEATHER_ROOT.'lang/'.$feather->user->language.'/common.php';
} else {
    error('There is no valid language pack \''.feather_escape($feather->user->language).'\' installed. Please reinstall a language of that name');
}

// Check if we are to display a maintenance message
if ($feather->config['o_maintenance'] && $feather->user->g_id > FEATHER_ADMIN && !defined('FEATHER_TURN_OFF_MAINT')) {
    maintenance_message();
}

// Load cached bans
if (file_exists(FORUM_CACHE_DIR.'cache_bans.php')) {
    include FORUM_CACHE_DIR.'cache_bans.php';
}

if (!defined('FEATHER_BANS_LOADED')) {
    if (!defined('FORUM_CACHE_FUNCTIONS_LOADED')) {
        require FEATHER_ROOT.'include/cache.php';
    }

    generate_bans_cache();
    require FORUM_CACHE_DIR.'cache_bans.php';
}

// Check if current user is banned
check_bans();

// Update online list
update_users_online();

// Check to see if we logged in without a cookie being set
if ($feather->user->is_guest && isset($_GET['login'])) {
    message($lang_common['No cookie']);
}

// 32kb should be more than enough for forum posts
if (!defined('FEATHER_MAX_POSTSIZE')) {
    define('FEATHER_MAX_POSTSIZE', 32768);
}

if (!defined('FEATHER_SEARCH_MIN_WORD')) {
    define('FEATHER_SEARCH_MIN_WORD', 3);
}
if (!defined('FEATHER_SEARCH_MAX_WORD')) {
    define('FEATHER_SEARCH_MAX_WORD', 20);
}

if (!defined('FORUM_MAX_COOKIE_SIZE')) {
    define('FORUM_MAX_COOKIE_SIZE', 4048);
}
