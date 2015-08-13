<?php
/**
 *
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 *
 * $app = new \Slim\Slim();
 * $app->add(new \Slim\Extras\Middleware\FeatherBB($params));
 *
 */

namespace Slim\Extras\Middleware;
use DB;

class FeatherBB extends \Slim\Middleware
{
    protected $data = array();

	public function __construct(array $user_forum_settings = array())
	{
        // Define forum env constants
        $this->data['forum_env'] = array(
                                    'FEATHER' => true,
                                    'FEATHER_ROOT' => dirname(__FILE__).'/../../../',
                                    'FORUM_VERSION' => '1.0.0',
                                    'FORUM_NAME' => 'FeatherBB',
                                    'FORUM_DB_REVISION' => 21,
                                    'FORUM_SI_REVISION' => 2,
                                    'FORUM_PARSER_REVISION' => 2,
                                    'FORUM_CACHE_DIR' => dirname(__FILE__).'/../../../cache/', // TODO : Move in user settings
                                    'FEATHER_UNVERIFIED' => 0,
                                    'FEATHER_ADMIN' => 1,
                                    'FEATHER_MOD' => 2,
                                    'FEATHER_GUEST' => 3,
                                    'FEATHER_MEMBER' => 4,
                                    'FEATHER_MAX_POSTSIZE' => 32768,
                                    'FEATHER_SEARCH_MIN_WORD' => 3,
                                    'FEATHER_SEARCH_MAX_WORD' => 20,
                                    'FORUM_MAX_COOKIE_SIZE' => 4048,
                                    'FEATHER_DEBUG' => 1,
                                    'FEATHER_SHOW_QUERIES' => 1,
                                    'FEATHER_CACHE_QUERIES' => 0,
                                    );

        // Define forum settings / TODO : handle settings with a class / User input overrides all previous settings
        $this->data['forum_settings'] = array_merge(self::load_default_settings(), $user_forum_settings);

        // Load DB settings
        $this->init_db();
	}

    public static function load_default_settings()
    {
        return array(
                // Database
                'db_type' => 'mysqli',
                'db_host' => '',
                'db_name' => '',
                'db_user' => '',
                'db_pass' => '',
                'db_prefix' => '',
                // Cookies
                'cookie_name' => 'feather_cookie',
                'cookie_seed' => 'changeme', // MUST BE CHANGED !!!
                // Debug
                'debug' => false,
                );
    }

    public function init_db()
    {
        require $this->data['forum_env']['FEATHER_ROOT'].'include/idiorm.php';
        switch ($this->data['forum_settings']['db_type']) {
            case 'mysql':
            case 'mysqli':
            case 'mysql_innodb':
            case 'mysqli_innodb':
                DB::configure('mysql:host='.$this->data['forum_settings']['db_host'].';dbname='.$this->data['forum_settings']['db_name']);
                DB::configure('driver_options', array(\PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'));
                break;
            case 'sqlite';
            case 'sqlite3';
                DB::configure('sqlite:./'.$this->data['forum_settings']['db_name']);
                break;
            case 'pgsql':
                \DB::configure('pgsql:host='.$this->data['forum_settings']['db_host'].'dbname='.$this->data['forum_settings']['db_name']);
                break;
        }
        DB::configure('username', $this->data['forum_settings']['db_user']);
        DB::configure('password', $this->data['forum_settings']['db_pass']);
        if ($this->data['forum_env']['FEATHER_SHOW_QUERIES'] == 1) {
            DB::configure('logging', true);
        }
        if ($this->data['forum_env']['FEATHER_CACHE_QUERIES'] == 1) {
            DB::configure('caching', true);
        }
        DB::configure('id_column_overrides', array(
            $this->data['forum_settings']['db_prefix'].'groups' => 'g_id',
        ));
    }

    // Getters / setters for Slim container (avoid magic get error)

    public function set_forum_env($key, $value = null)
    {
        $tmp = (!is_array($key) && !is_null($value)) ? array($key, $value) : $key;
        foreach ($tmp as $key => $value) {
            $this->app->container->get('forum_env')[$key] = $value;
        }

    }

    public function set_forum_settings($key, $value = null)
    {
        $tmp = (!is_array($key) && !is_null($value)) ? array($key, $value) : $key;
        foreach ($tmp as $key => $value) {
            $this->app->container->get('forum_settings')[$key] = $value;
        }
    }

    public function hydrate($data)
    {
        foreach ($data as $key => $value) {
            $this->app->container[$key] = $value;
        }
    }

    // Legacy function, to ensure backward compatibility with globals
    public function env_to_globals(array $vars)
    {
        foreach ($vars as $key => $value) {
            define($key, $value);
        }
    }

    // Headers

    public function set_headers()
    {
        // No cache headers
        $this->app->response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate');
        $this->app->response->headers->set('Pragma', 'no-cache');
        $this->app->expires(0);

        // For servers which might be configured to send something else
        $this->app->response->headers->set('Content-type', 'text/html');
        // Prevent the site to be embedded in iFrame
        $this->app->response->headers->set('X-Frame-Options', 'deny');
        // Yeah !
        $this->app->response->headers->set('X-Powered-By', $this->app->forum_env['FORUM_NAME']);
    }

    // Auth

    public function authenticate()
    {
        $now = time();

        // Get FeatherBB cookie
        $cookie_raw = $this->app->getCookie($this->data['forum_settings']['cookie_name']);
        // Check if cookie exists and is valid (getCookie method returns false if the data has been tampered locally so it can't decrypt the cookie);
        if (isset($cookie_raw)) {
            $cookie = json_decode($cookie_raw, true);
            $checksum = hash_hmac('sha1', $cookie['user_id'].$cookie['expires'], $this->data['forum_settings']['cookie_seed'].'_checksum');
            // If cookie has a non-guest user, hasn't expired and is legit
            if ($cookie['user_id'] > 1 && $cookie['expires'] > $now && $checksum == $cookie['checksum']) {
                // Get user info from db
                $select_check_cookie = array('u.*', 'g.*', 'o.logged', 'o.idle');
                $where_check_cookie = array('u.id' => intval($cookie['user_id']));

                $result = DB::for_table('users')
                    ->table_alias('u')
                    ->select_many($select_check_cookie)
                    ->inner_join('groups', array('u.group_id', '=', 'g.g_id'), 'g')
                    ->left_outer_join('online', array('o.user_id', '=', 'u.id'), 'o')
                    ->where($where_check_cookie)
                    ->find_result_set();

                foreach ($result as $this->app->user);

                // Another security check, to prevent identity fraud by changing the user id in the cookie) (might be useless considering the strength of encryption)
                if (isset($this->app->user->id) && hash_hmac('sha1', $this->app->user->password, $this->data['forum_settings']['cookie_seed'].'_password_hash') === $cookie['password_hash']) {
                    $expires = ($cookie['expires'] > $now + $this->data['forum_settings']['o_timeout_visit']) ? $now + 1209600 : $now + $this->data['forum_settings']['o_timeout_visit'];
                    $this->app->user->is_guest = false;
                    $this->app->user->is_admmod = $this->app->user->g_id == $this->data['forum_env']['FEATHER_ADMIN'] || $this->app->g_moderator == '1';
                    if (!$this->app->user->disp_topics) {
                        $this->app->user->disp_topics = $this->data['forum_settings']['o_disp_topics_default'];
                    }
                    if (!$this->app->user->disp_posts) {
                        $this->app->user->disp_posts = $this->data['forum_settings']['o_disp_posts_default'];
                    }
                    if (!file_exists($this->data['forum_env']['FEATHER_ROOT'].'lang/'.$this->app->user->language)) {
                        $this->app->user->language = $this->data['forum_settings']['o_default_lang'];
                    }
                    if (!file_exists($this->data['forum_env']['FEATHER_ROOT'].'style/'.$this->app->user->style.'.css')) {
                        $this->app->user->style = $this->data['forum_settings']['o_default_style'];
                    }
                    feather_setcookie($this->app->user->id, $this->app->user->password, $expires);
                    $this->update_online();
                    return true;
                }
            }
        }

        // If there is no cookie, or cookie is guest or expired, let's reconnect.
        $expires = $now + 31536000; // The cookie expires after a year

        // Fetch guest user
        $select_set_default_user = array('u.*', 'g.*', 'o.logged', 'o.last_post', 'o.last_search');
        $where_set_default_user = array('u.id' => '1');

        $result = DB::for_table('users')
            ->table_alias('u')
            ->select_many($select_set_default_user)
            ->inner_join('groups', array('u.group_id', '=', 'g.g_id'), 'g')
            ->left_outer_join('online', array('o.ident', '=', $this->app->request->getIp()), 'o', true)
            ->where($where_set_default_user)
            ->find_result_set();

        if (!$result) {
            exit('Unable to fetch guest information. Your database must contain both a guest user and a guest user group.');
        }

        foreach ($result as $this->app->user);

        $this->app->user->disp_topics = $this->data['forum_settings']['o_disp_topics_default'];
        $this->app->user->disp_posts = $this->data['forum_settings']['o_disp_posts_default'];
        $this->app->user->timezone = $this->data['forum_settings']['o_default_timezone'];
        $this->app->user->dst = $this->data['forum_settings']['o_default_dst'];
        $this->app->user->language = $this->data['forum_settings']['o_default_lang'];
        $this->app->user->style = $this->data['forum_settings']['o_default_style'];
        $this->app->user->is_guest = true;
        $this->app->user->is_admmod = false;

        // Update online list
        if (!$this->app->user->logged) {
            $this->app->user->logged = time();

            // With MySQL/MySQLi/SQLite, REPLACE INTO avoids a user having two rows in the online table
            switch ($this->data['forum_settings']['db_type']) {
                case 'mysql':
                case 'mysqli':
                case 'mysql_innodb':
                case 'mysqli_innodb':
                case 'sqlite':
                case 'sqlite3':
                DB::for_table('online')->raw_execute('REPLACE INTO '.$this->data['forum_settings']['db_prefix'].'online (user_id, ident, logged) VALUES(1, :ident, :logged)', array(':ident' => $this->app->request->getIp(), ':logged' => $this->app->user->logged));
                    break;

                default:
                    DB::for_table('online')->raw_execute('INSERT INTO '.$this->data['forum_settings']['db_prefix'].'online (user_id, ident, logged) SELECT 1, :ident, :logged WHERE NOT EXISTS (SELECT 1 FROM '.$this->app->db->prefix.'online WHERE ident=:ident)', array(':ident' => $this->app->request->getIp(), ':logged' => $this->app->user->logged));
                    break;
            }
        } else {
            DB::for_table('online')->where('ident', $this->app->request->getIp())
                 ->update_many('logged', time());
        }

        feather_setcookie(1, feather_hash(uniqid(rand(), true)), $expires);
        return true;
    }

    public function update_online()
    {
        $now = time();

        // Define this if you want this visit to affect the online list and the users last visit data
        if (!defined('FEATHER_QUIET_VISIT')) {
            // Update the online list
            if (!$this->app->user->logged) {
                $this->app->user->logged = $now;

                // With MySQL/MySQLi/SQLite, REPLACE INTO avoids a user having two rows in the online table
                switch ($this->data['forum_settings']['db_type']) {
                    case 'mysql':
                    case 'mysqli':
                    case 'mysql_innodb':
                    case 'mysqli_innodb':
                    case 'sqlite':
                    case 'sqlite3':
                        DB::for_table('online')->raw_execute('REPLACE INTO '.$this->data['forum_settings']['db_prefix'].'online (user_id, ident, logged) VALUES(:user_id, :ident, :logged)', array(':user_id' => $this->app->user->id, ':ident' => $this->app->user->username, ':logged' => $this->app->user->logged));
                        break;

                    default:
                        DB::for_table('online')->raw_execute('INSERT INTO '.$this->data['forum_settings']['db_prefix'].'online (user_id, ident, logged) SELECT :user_id, :ident, :logged WHERE NOT EXISTS (SELECT 1 FROM '.$this->app->db->prefix.'online WHERE user_id=:user_id)', array(':user_id' => $this->app->user->id, ':ident' => $this->app->user->username, ':logged' => $this->app->user->logged));
                        break;
                }

                // Reset tracked topics
                set_tracked_topics(null);

            } else {
                // Special case: We've timed out, but no other user has browsed the forums since we timed out
                if ($this->app->user->logged < ($now-$this->data['forum_settings']['o_timeout_visit'])) {
                    DB::for_table('users')->where('id', $this->app->user->id)
                        ->find_one()
                        ->set('last_visit', $this->app->user->logged)
                        ->save();
                    $this->app->user->last_visit = $this->app->user->logged;
                }

                $idle_sql = ($this->app->user->idle == '1') ? ', idle=0' : '';

                DB::for_table('online')->raw_execute('UPDATE '.$this->data['forum_settings']['db_prefix'].'online SET logged='.$now.$idle_sql.' WHERE user_id=:user_id', array(':user_id' => $this->app->user->id));

                // Update tracked topics with the current expire time
                $cookie_tracked_topics = $this->app->getCookie($this->data['forum_settings']['cookie_name'].'_track');
                if (isset($cookie_tracked_topics)) {
                    set_tracked_topics(json_decode($cookie_tracked_topics, true));
                }
            }
        } else {
            if (!$this->app->user->logged) {
                $this->app->user->logged = $this->app->user->last_visit;
            }
        }
    }

    //

    public function call()
    {
        global $feather_bans, $cookie_name, $cookie_seed, $forum_time_formats, $forum_date_formats, $feather_config; // Legacy

        if ((isset($this->app->environment['HTTP_X_MOZ'])) && ($this->app->environment['HTTP_X_MOZ'] == 'prefetch')) { // Block prefetch requests
            $this->set_headers();
            $this->app->response->setStatus(403);
        } else {
            $this->env_to_globals($this->data['forum_env']); // Legacy : define globals from forum_env


            require $this->data['forum_env']['FEATHER_ROOT'].'include/utf8/utf8.php';
            require $this->data['forum_env']['FEATHER_ROOT'].'include/functions.php';

            // Record the start time (will be used to calculate the generation time for the page)
            $this->app->start = get_microtime();

            // Get forum config and load it into forum_settings array
            if (file_exists($this->data['forum_env']['FORUM_CACHE_DIR'].'cache_config.php')) {
                include $this->data['forum_env']['FORUM_CACHE_DIR'].'cache_config.php';
            } else {
                require $this->data['forum_env']['FEATHER_ROOT'].'include/cache.php';
                generate_config_cache();
                require $this->data['forum_env']['FORUM_CACHE_DIR'].'cache_config.php';
            }
            $this->data['forum_settings'] = array_merge($feather_config, $this->data['forum_settings']);
            // Define time formats
            $forum_time_formats = array($this->data['forum_settings']['o_time_format'], 'H:i:s', 'H:i', 'g:i:s a', 'g:i a');
            $forum_date_formats = array($this->data['forum_settings']['o_date_format'], 'Y-m-d', 'Y-d-m', 'd-m-Y', 'm-d-Y', 'M j Y', 'jS M Y');
            // Populate Feather object (Slim instance)
            $this->hydrate($this->data);

            $this->app->config = $this->data['forum_settings']; // Legacy
            extract($this->data['forum_settings']); // Legacy

            $this->set_headers();

            // Force POSIX locale (to prevent functions such as strtolower() from messing up UTF-8 strings)
            setlocale(LC_CTYPE, 'C');

            $this->authenticate();

            // Attempt to load the common language file
            // Load l10n
            require $this->data['forum_env']['FEATHER_ROOT'].'include/pomo/MO.php';
            require $this->data['forum_env']['FEATHER_ROOT'].'include/l10n.php';

            // Attempt to load the language file
            if (file_exists($this->data['forum_env']['FEATHER_ROOT'].'lang/'.$this->app->user->language.'/common.mo')) {
                load_textdomain('featherbb', $this->data['forum_env']['FEATHER_ROOT'].'lang/'.$this->app->user->language.'/common.mo');
            }
            else {
                die('There is no valid language pack \''.feather_escape($this->app->user->language).'\' installed. Please reinstall a language of that name');
            }

            // Check if we are to display a maintenance message
            if ($this->data['forum_settings']['o_maintenance'] && $this->app->user->g_id > $this->data['forum_env']['FEATHER_ADMIN'] && !defined('FEATHER_TURN_OFF_MAINT')) {
                maintenance_message();
            }

            // Load cached bans
            if (file_exists($this->data['forum_env']['FORUM_CACHE_DIR'].'cache_bans.php')) {
                include $this->data['forum_env']['FORUM_CACHE_DIR'].'cache_bans.php';
            }

            if (!defined('FEATHER_BANS_LOADED')) {
                if (!defined('FORUM_CACHE_FUNCTIONS_LOADED')) {
                    require $this->data['forum_env']['FEATHER_ROOT'].'include/cache.php';
                }

                generate_bans_cache();
                require $this->data['forum_env']['FORUM_CACHE_DIR'].'cache_bans.php';
            }

            // Check if current user is banned
            check_bans();

            // Update online list
            update_users_online();

            // Configure Slim
            $this->app->config('templates.path', (is_dir('style/'.$this->app->user->style.'/view')) ? FEATHER_ROOT.'style/'.$this->app->user->style.'/view' : FEATHER_ROOT.'view');
            $this->next->call();
        }
    }
}
