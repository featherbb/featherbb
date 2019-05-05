<?php
/**
 *
 * Copyright (C) 2015-2019 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 *
 * $app = new \Slim\Slim();
 * $app->add(new \Slim\Extras\Middleware\FeatherBBLoader(array $config));
 *
 */

namespace FeatherBB\Middleware;

use Dotenv\Dotenv;
use FeatherBB\Core\Database as DB;
use FeatherBB\Core\Error;
use FeatherBB\Core\Hooks;
use FeatherBB\Core\Interfaces\Cache;
use FeatherBB\Core\Interfaces\Config;
use FeatherBB\Core\Interfaces\Container;
use FeatherBB\Core\Interfaces\ForumEnv;
use FeatherBB\Core\Interfaces\ForumSettings;
use FeatherBB\Core\Interfaces\Lang;
use FeatherBB\Core\Parser;
use FeatherBB\Core\Plugin as PluginManager;
use FeatherBB\Core\Url;
use FeatherBB\Core\Utils;
use FeatherBB\Core\View;

class Core
{
    protected $forumEnv;
    protected $forumSettings;
    protected $headers = [
        'Cache-Control' => 'no-cache, no-store, must-revalidate',
        'Pragma' => 'no-cache',
        'Content-type' => 'text/html',
        'X-Frame-Options' => 'deny'];

    public function __construct()
    {
        // Define some core variables
        $this->forumEnv['FEATHER_ROOT'] = realpath(dirname(__FILE__).'/../../').'/';
        try {
            $dotenv = Dotenv::create($this->forumEnv['FEATHER_ROOT']);
            $dotenv->load();
        } catch (\Dotenv\Exception\InvalidPathException $e) {}
        $this->forumEnv['FORUM_CACHE_DIR'] = is_writable($this->forumEnv['FEATHER_ROOT'].'cache') ? realpath($this->forumEnv['FEATHER_ROOT'].'cache').'/' : null;
        $this->forumEnv['FORUM_CONFIG_FILE'] = $this->forumEnv['FEATHER_ROOT'].'.env';
        $this->forumEnv['FEATHER_DEBUG'] = $this->forumEnv['FEATHER_SHOW_QUERIES'] = (getenv('DEBUG') == 'all' || filter_var(getenv('DEBUG'), FILTER_VALIDATE_BOOLEAN) == true);
        $this->forumEnv['FEATHER_SHOW_INFO'] = (getenv('DEBUG') == 'info' || getenv('DEBUG') == 'all');

        if ($this->forumEnv['FEATHER_DEBUG']) {
            error_reporting(E_ALL); // Let's report everything for development
            ini_set('display_errors', 1);
        }

        // Populate forum_env
        $this->forumEnv = array_merge(self::loadDefaultForumEnv(), $this->forumEnv);

        // Force POSIX locale (to prevent functions such as strtolower() from messing up UTF-8 strings)
        setlocale(LC_CTYPE, 'C');
    }

    public static function loadDefaultForumEnv()
    {
        return [
                'FEATHER_ROOT' => '',
                'FORUM_CONFIG_FILE' => '.env',
                'FORUM_CACHE_DIR' => 'cache/',
                'FORUM_VERSION' => '1.0.0-beta.5',
                'FORUM_NAME' => 'FeatherBB',
                'FORUM_DB_REVISION' => 21,
                'FORUM_SI_REVISION' => 2,
                'FORUM_PARSER_REVISION' => 2,
                'FEATHER_UNVERIFIED' => 0,
                'FEATHER_ADMIN' => 1,
                'FEATHER_MOD' => 2,
                'FEATHER_GUEST' => 3,
                'FEATHER_MEMBER' => 4,
                'FEATHER_MAX_POSTSIZE' => 32768,
                'FEATHER_SEARCH_MIN_WORD' => 3,
                'FEATHER_SEARCH_MAX_WORD' => 20,
                'FEATHER_DEBUG' => false,
                'FEATHER_SHOW_QUERIES' => false,
                'FEATHER_SHOW_INFO' => false,
                'DB_TYPE' => getenv('DB_TYPE'),
                'DB_HOST' => getenv('DB_HOST'),
                'DB_NAME' => getenv('DB_NAME'),
                'DB_PREFIX' => getenv('DB_PREFIX'),
                'DB_USER' => getenv('DB_USER'),
                'DB_PASS' => getenv('DB_PASS'),
                'COOKIE_NAME' => getenv('COOKIE_NAME'),
                'JWT_TOKEN' => getenv('JWT_TOKEN'),
                'JWT_ALGORITHM' => getenv('JWT_ALGORITHM')
        ];
    }

    public static function initDb($log_queries = false)
    {
        switch (ForumEnv::get('DB_TYPE')) {
            case 'mysql':
                if (!extension_loaded('pdo_mysql')) {
                    throw new Error('Driver pdo_mysql not installed.', 500, false, false, true);
                }
                DB::configure('mysql:host='.ForumEnv::get('DB_HOST').';dbname='.ForumEnv::get('DB_NAME'));
                DB::configure('driver_options', [\PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8']);
                break;
            case 'sqlite':
            case 'sqlite3':
                if (!extension_loaded('pdo_sqlite')) {
                    throw new Error('Driver pdo_mysql not installed.', 500, false, false, true);
                }
                DB::configure('sqlite:./'.ForumEnv::get('DB_NAME'));
                break;
            case 'pgsql':
                if (!extension_loaded('pdo_pgsql')) {
                    throw new Error('Driver pdo_mysql not installed.', 500, false, false, true);
                }
                DB::configure('pgsql:host='.ForumEnv::get('DB_HOST').'dbname='.ForumEnv::get('DB_NAME'));
                break;
        }
        DB::configure('username', ForumEnv::get('DB_USER'));
        DB::configure('password', ForumEnv::get('DB_PASS'));
        DB::configure('prefix', ForumEnv::get('DB_PREFIX'));
        if ($log_queries) {
            DB::configure('logging', true);
        }
        DB::configure('id_column_overrides', [
            ForumEnv::get('DB_PREFIX').'groups' => 'g_id',
        ]);
    }

    public static function loadPlugins()
    {
        $manager = new PluginManager();
        $manager->loadPlugins();
    }

    // Headers

    protected function setHeaders($res)
    {
        foreach ($this->headers as $label => $value) {
            $res = $res->withHeader($label, $value);
        }
        $res = $res->withHeader('X-Powered-By', $this->forumEnv['FORUM_NAME']);

        return $res;
    }

    public function __invoke($req, $res, $next)
    {
        // Set headers
        $res = $this->setHeaders($res);

        // Block prefetch requests
        if ((isset($_SERVER['HTTP_X_MOZ'])) && ($_SERVER['HTTP_X_MOZ'] == 'prefetch')) {
            $res = $res->withStatus(403);
            return $next($req, $res);
        }
        // Populate Slim object with forum_env vars
        Container::set('forum_env', $this->forumEnv);
        // Load FeatherBB utils class
        Container::set('utils', function ($container) {
            return new Utils();
        });
        // Record start time
        Container::set('start', Utils::getMicrotime());
        // Define now var
        Container::set('now', function () {
            return time();
        });
        // Load FeatherBB cache
        Container::set('cache', function ($container) {
            $path = $this->forumEnv['FORUM_CACHE_DIR'];
            return new \FeatherBB\Core\Cache(['name' => 'feather',
                                               'path' => $path,
                                               'extension' => '.cache.php']);
        });
        // Load FeatherBB permissions
        Container::set('perms', function ($container) {
            return new \FeatherBB\Core\Permissions();
        });
        // Load FeatherBB preferences
        Container::set('prefs', function ($container) {
            return new \FeatherBB\Core\Preferences();
        });
        // Load FeatherBB view
        Container::set('template', function ($container) {
            return new View();
        });
        // Load FeatherBB url class
        Container::set('url', function ($container) {
            return new Url();
        });
        // Load FeatherBB hooks
        Container::set('hooks', function ($container) {
            return new Hooks();
        });
        Container::set('parser', function ($container) {
            return new Parser();
        });
        // Set cookies
        Container::set('cookie', function ($container) {
            $request = $container->get('request');
            return new \Slim\Http\Cookies($request->getCookieParams());
        });
        Container::set('flash', function ($c) {
            return new \Slim\Flash\Messages;
        });

        // This is the very first hook fired
        \FeatherBB\Core\Interfaces\Hooks::fire('core.start');

        if (!is_file(ForumEnv::get('FORUM_CONFIG_FILE'))) {
            // Reset cache
            Cache::flush();
            $installer = new \FeatherBB\Controller\Install();
            return $installer->run();
        }

        // Init DB and configure Slim
        self::initDb(ForumEnv::get('FEATHER_SHOW_INFO'));
        Config::set('displayErrorDetails', ForumEnv::get('FEATHER_DEBUG'));

        // Ensure cached forum data exist
        if (!Cache::isCached('config')) {
            $config = array_merge(\FeatherBB\Model\Cache::getConfig(), \FeatherBB\Model\Cache::getPreferences());
            Cache::store('config', $config);
        }
        if (!Cache::isCached('permissions')) {
            Cache::store('permissions', \FeatherBB\Model\Cache::getPermissions());
        }
        if (!Cache::isCached('group_preferences')) {
            Cache::store('group_preferences', \FeatherBB\Model\Cache::getGroupPreferences());
        }

        // Finalize forum_settings array
        $this->forumSettings = Cache::retrieve('config');
        Container::set('forum_settings', $this->forumSettings);

        Lang::construct();

        // Run activated plugins
        self::loadPlugins();

        // Define time formats and add them to the container
        Container::set('forum_time_formats', [ForumSettings::get('time_format'), 'H:i:s', 'H:i', 'g:i:s a', 'g:i a']);
        Container::set('forum_date_formats', [ForumSettings::get('date_format'), 'Y-m-d', 'Y-d-m', 'd-m-Y', 'm-d-Y', 'M j Y', 'jS M Y']);

        // Check if we have DOM support (not installed by default in PHP >= 7.0, results in utf8_decode not defined
        if (!function_exists('utf8_decode')) {
            throw new Error('Please install the php7.0-xml package.', 500, false, false, true);
        }
        
        return $next($req, $res);
    }
}
