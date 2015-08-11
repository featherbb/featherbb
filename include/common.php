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
    header('Location: '.$feather->request->getPath().'install/index.php');
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

// Inject DB prefix to SlimFramework
$feather->prefix = $db_prefix;

// Include Idiorm and set it up
require FEATHER_ROOT.'include/idiorm.php';

switch ($db_type) {
    case 'mysql':
    case 'mysqli':
    case 'mysql_innodb':
    case 'mysqli_innodb':
        \DB::configure('mysql:host='.$db_host.';dbname='.$db_name);
        \DB::configure('driver_options', array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'));
        break;
    case 'sqlite';
    case 'sqlite3';
        \DB::configure('sqlite:./'.$db_name);
        break;
    case 'pgsql':
        \DB::configure('pgsql:host='.$db_host.'dbname='.$db_name);
        break;
}
\DB::configure('username', $db_username);
\DB::configure('password', $db_password);
\DB::configure('id_column_overrides', array(
    $db_prefix.'groups' => 'g_id',
));

// Log queries if needed
if (defined('FEATHER_SHOW_QUERIES')) {
    \DB::configure('logging', true);
}

// Cache queries if needed
if (defined('FEATHER_CACHE_QUERIES')) {
    \DB::configure('caching', true);
}

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
check_cookie();

// Attempt to load the common language file
if (file_exists(FEATHER_ROOT.'lang/'.$feather->user->language.'/common.php')) {
    include FEATHER_ROOT.'lang/'.$feather->user->language.'/common.php';
} else {
    die('There is no valid language pack \''.feather_escape($feather->user->language).'\' installed. Please reinstall a language of that name');
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
