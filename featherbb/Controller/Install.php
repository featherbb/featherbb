<?php

/**
 * Copyright (C) 2015-2017 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace FeatherBB\Controller;

use FeatherBB\Core\Interfaces\Cache;
use FeatherBB\Core\Interfaces\Container;
use FeatherBB\Core\Interfaces\ForumEnv;
use FeatherBB\Core\Interfaces\Hooks;
use FeatherBB\Core\Interfaces\Input;
use FeatherBB\Core\Interfaces\Lang;
use FeatherBB\Core\Interfaces\Perms;
use FeatherBB\Core\Interfaces\Prefs;
use FeatherBB\Core\Interfaces\Request;
use FeatherBB\Core\Interfaces\Router;
use FeatherBB\Core\Interfaces\View;
use FeatherBB\Core\Lister;
use FeatherBB\Core\Random;
use FeatherBB\Core\Url;
use FeatherBB\Core\Utils;
use FeatherBB\Middleware\Core;

class Install
{
    protected $supportedDbs = ['mysql' => 'MySQL',
        'pgsql' => 'PostgreSQL',
        'sqlite' => 'SQLite',
        'sqlite3' => 'SQLite3',
    ];
    protected $availableLangs;
    protected $optionalFields = ['db_user', 'db_pass', 'db_prefix'];
    protected $installLang = 'English';
    protected $defaultStyle = 'FeatherBB';
    protected $configKeys = ['db_type', 'db_host', 'db_name', 'db_user', 'db_pass', 'db_prefix'];
    protected $errors = [];

    public function __construct()
    {
        $this->model = new \FeatherBB\Model\Install();
        $this->availableLangs = Lister::getLangs();
        Container::set('user', null);
        View::setStyle('FeatherBB');
        Lang::construct();
    }

    public function run()
    {
        Cache::flush();
        Hooks::fire('controller.install.run_install');

        // First form has been submitted to change default language
        if (Input::post('choose_lang')) {
            if (in_array(Utils::trim(Input::post('install_lang')), $this->availableLangs)) {
                $this->installLang = Input::post('install_lang');
            }
        }

        $csrf = new \FeatherBB\Middleware\Csrf();
        $csrf->generateNewToken(Container::get('request'));

        Lang::load('install', 'FeatherBB', false, $this->installLang);

        // Second form has been submitted to start install
        if (Request::isPost() && !Input::post('choose_lang')) {
            $missingFields = [];
            $data = array_map(function ($item) {
                return Utils::escape(Utils::trim($item));
            }, Input::post('install'));

            foreach ($data as $field => $value) {
                // Handle empty fields
                if (empty($value)) {
                    // If the field is required, or if user and pass are missing even though mysql or pgsql are selected as DB
                    if (!in_array($field, $this->optionalFields) || (in_array($field, ['db_user']) && in_array($data['db_type'], ['mysql', 'pgsql']))) {
                        $missingFields[] = $field;
                    }
                }
            }

            if (!empty($missingFields)) {
                $this->errors = 'The following fields are required but are missing : '.implode(', ', $missingFields);
            } else { // Missing fields, so we don't need to validate the others
                // VALIDATION
                // Make sure base_url doesn't end with a slash
                if (substr($data['base_url'], -1) == '/') {
                    $data['base_url'] = substr($data['base_url'], 0, -1);
                }

                // Validate username and passwords
                if (Utils::strlen($data['username']) < 2) {
                    $this->errors[] = __('Username 1');
                } elseif (Utils::strlen($data['username']) > 25) { // This usually doesn't happen since the form element only accepts 25 characters
                    $this->errors[] = __('Username 2');
                } elseif (!strcasecmp($data['username'], 'Guest')) {
                    $this->errors[] = __('Username 3');
                } elseif (preg_match('%[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}%', $data['username']) || preg_match('%((([0-9A-Fa-f]{1,4}:){7}[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){6}:[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){5}:([0-9A-Fa-f]{1,4}:)?[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){4}:([0-9A-Fa-f]{1,4}:){0,2}[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){3}:([0-9A-Fa-f]{1,4}:){0,3}[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){2}:([0-9A-Fa-f]{1,4}:){0,4}[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){6}((\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b)\.){3}(\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b))|(([0-9A-Fa-f]{1,4}:){0,5}:((\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b)\.){3}(\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b))|(::([0-9A-Fa-f]{1,4}:){0,5}((\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b)\.){3}(\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b))|([0-9A-Fa-f]{1,4}::([0-9A-Fa-f]{1,4}:){0,5}[0-9A-Fa-f]{1,4})|(::([0-9A-Fa-f]{1,4}:){0,6}[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){1,7}:))%', $data['username'])) {
                    $this->errors[] = __('Username 4');
                } elseif ((strpos($data['username'], '[') !== false || strpos($data['username'], ']') !== false) && strpos($data['username'], '\'') !== false && strpos($data['username'], '"') !== false) {
                    $this->errors[] = __('Username 5');
                } elseif (preg_match('%(?:\[/?(?:b|u|i|h|colou?r|quote|code|img|url|email|list)\]|\[(?:code|quote|list)=)%i', $data['username'])) {
                    $this->errors[] = __('Username 6');
                }

                if (Utils::strlen($data['password']) < 6) {
                    $this->errors[] = __('Short password');
                } elseif ($data['password'] != $data['password_conf']) {
                    $this->errors[] = __('Passwords not match');
                }

                // Validate email
                if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                    $this->errors[] = __('Wrong email');
                }

                // Validate language
                if (!in_array($data['language'], Lister::getLangs())) {
                    $this->errors[] = __('Error default language');
                }

                // Check if the cache directory is writable
                if (!is_writable(ForumEnv::get('FORUM_CACHE_DIR'))) {
                    $this->errors[] = sprintf(__('Alert cache'), ForumEnv::get('FORUM_CACHE_DIR'));
                }

                // Check if default avatar directory is writable
                if (!is_writable(ForumEnv::get('FEATHER_ROOT').'style/img/avatars/')) {
                    $this->errors[] = sprintf(__('Alert avatar'), ForumEnv::get('FEATHER_ROOT').'style/img/avatars/');
                }

                // Validate db_prefix if existing
                if (!empty($data['db_prefix']) && ((strlen($data['db_prefix']) > 0 && (!preg_match('%^[a-zA-Z_][a-zA-Z0-9_]*$%', $data['db_prefix']) || strlen($data['db_prefix']) > 40)))) {
                    $this->errors[] = sprintf(__('Table prefix error'), $data['db_prefix']);
                }
            }

            // End validation and check errors
            if (!empty($this->errors)) {
                return View::setPageInfo([
                    'languages' => $this->availableLangs,
                    'supported_dbs' => $this->supportedDbs,
                    'data' => $data,
                    'errors' => $this->errors,
                ])->addTemplate('@forum/install.php')->display(false);
            } else {
                $data['style'] = $this->defaultStyle;
                $data['avatars'] = in_array(strtolower(@ini_get('file_uploads')), ['on', 'true', '1']) ? 1 : 0;
                return $this->createConfig($data);
            }
        } else {
            $baseUrl = str_replace('index.php', '', Url::base());
            $data = ['title' => __('My FeatherBB Forum'),
                'description' => __('Description'),
                'base_url' => $baseUrl,
                'language' => $this->installLang];
            return View::setPageInfo([
                'languages' => $this->availableLangs,
                'supported_dbs' => $this->supportedDbs,
                'data' => $data,
                'alerts' => [],
            ])->addTemplate('@forum/install.php')->display(false);
        }
    }

    public function createConfig(array $data)
    {
        Hooks::fire('controller.install.create_config');

        // Generate config ...
        $config = [];
        foreach ($data as $key => $value) {
            if (in_array($key, $this->configKeys)) {
                $config[$key] = $value;
            }
        }

        $config = array_merge($config, [
            'cookie_name' => mb_strtolower(ForumEnv::get('FORUM_NAME')).'_cookie_'.Random::key(7, false, true),
            'jwt_token' => base64_encode(Random::secureRandomBytes(64)),
            'jwt_algorithm' => 'HS512'
        ]);

        // ... And write it on disk
        if ($this->writeConfig($config)) {
            return $this->createDb($data);
        } else {
            // TODO: Translate
            return Router::redirect(Router::pathFor('install'), ['error', 'Error while writing config file']);
        }
    }

    public function createDb(array $data)
    {
        Hooks::fire('controller.install.create_db');

        // Handle db prefix
        $data['db_prefix'] = (!empty($data['db_prefix'])) ? $data['db_prefix'] : '';
        // Init DB
        Core::initDb($data);
        // Load appropriate language
        Lang::load('install', 'featherbb', false, $data['language']);

        // Create tables
        foreach ($this->model->getDatabaseScheme() as $table => $sql) {
            if (!$this->model->createTable($data['db_prefix'].$table, $sql)) {
                // Error handling
                $this->errors[] = 'A problem was encountered while creating table '.$table;
            }
        }

        // Populate group table with default values
        foreach ($this->model->loadDefaultGroups() as $groupName => $groupData) {
            $this->model->addData('groups', $groupData);
        }

        // Init permissions
        // TODO: Reuse groups inheritance later ?
        // Perms::addParent(4, array(3));
        // Perms::addParent(2, array(3,4));
        // Perms::addParent(1, array(2,3,4));
        // Perms::allowGroup(3, array('board.read', 'users.view', 'search.topics', 'search.users'));
        // Perms::allowGroup(4, array('topic.reply', 'topic.post', 'topic.delete', 'post.delete', 'post.edit', 'post.links', 'email.send'));
        // Perms::allowGroup(2, array('mod.is_mod', 'mod.edit_users', 'mod.rename_users', 'mod.change_passwords', 'mod.promote_users', 'mod.ban_users', 'user.set_title'));

        Perms::allowGroup(3, ['board.read', 'users.view', 'search.topics', 'search.users']);
        Perms::allowGroup(4, ['board.read', 'users.view', 'search.topics', 'search.users', 'topic.reply', 'topic.post', 'topic.delete', 'post.delete', 'post.edit', 'post.links', 'email.send']);
        Perms::allowGroup(2, ['board.read', 'users.view', 'user.set_title', 'search.topics', 'search.users', 'topic.reply', 'topic.post', 'topic.delete', 'post.delete', 'post.edit', 'post.links', 'email.send', 'mod.is_mod', 'mod.edit_users', 'mod.rename_users', 'mod.change_passwords', 'mod.promote_users', 'mod.ban_users']);
        Perms::allowGroup(1, ['*']);
        Cache::store('permissions', \FeatherBB\Model\Cache::getPermissions());
        // Init preferences
        Prefs::set([
            'disp.topics' => 30,
            'disp.posts' => 25,
            'post.min_interval' => 60,
            'search.min_interval' => 30,
            'email.min_interval' => 60,
            'report.min_interval' => 60,
            'promote.min_posts' => 0,
            'promote.next_group' => 0,
            'timezone' => 0,
            'dst' => 0,
            'time_format' => 'H:i:s',
            'date_format' => 'Y-m-d',
            'language' => $data['language'],
            'style' => $data['style'],
            'show.smilies.sig' => 1,
            'show.smilies' => 1,
            'show.img' => 1,
            'show.img.sig' => 1,
            'show.avatars' => 1,
            'show.sig' => 1,
            'email.setting' => 1,
            'notify_with_post' => 0,
            'auto_notify' => 0,
        ]);
        Prefs::setGroup(2, [
            'post.min_interval' => 0,
            'search.min_interval' => 0,
            'email.min_interval' => 0,
            'report.min_interval' => 0
        ]);
        Prefs::setGroup(1, [
            'post.min_interval' => 0,
            'search.min_interval' => 0,
            'email.min_interval' => 0,
            'report.min_interval' => 0
        ]);


        // Populate user table with default values
        $this->model->addData('users', $this->model->loadDefaultUser());
        $this->model->addData('users', $this->model->loadAdminUser($data));
        // Populate categories, forums, topics, posts
        $this->model->addMockForum($this->model->loadMockForumData($data));
        // Store config in DB
        $this->model->saveConfig($this->loadDefaultConfig($data));
        // Add smilies
        $this->model->addSmilies($this->model->loadSmilies());

        // Handle .htaccess
        if (function_exists('apache_get_modules') && in_array('mod_rewrite', apache_get_modules())) {
            $this->writeHtaccess();
        }

        // Redirect to homepage with success message
        return Router::redirect(Router::pathFor('home'), ['success', __('Message')]);
    }

    public function writeConfig($array)
    {
        Hooks::fire('controller.install.write_config');

        return file_put_contents(ForumEnv::get('FORUM_CONFIG_FILE'), '<?php'."\n".'$featherbbConfig = '.var_export($array, true).';');
    }

    public function writeHtaccess()
    {
        Hooks::fire('controller.install.write_htaccess');

        $data = file_get_contents(ForumEnv::get('FEATHER_ROOT').'.htaccess.dist');
        return file_put_contents(ForumEnv::get('FEATHER_ROOT').'.htaccess', $data);
    }

    public function loadDefaultConfig(array $data)
    {
        Hooks::fire('controller.install.load_default_config');

        return [
            'o_cur_version'                => ForumEnv::get('FORUM_VERSION'),
            'o_database_revision'        => ForumEnv::get('FORUM_DB_REVISION'),
            'o_searchindex_revision'    => ForumEnv::get('FORUM_SI_REVISION'),
            'o_parser_revision'            => ForumEnv::get('FORUM_PARSER_REVISION'),
            'o_board_title'                => $data['title'],
            'o_board_desc'                => $data['description'],
            // 'o_default_timezone'        => 0,
            // 'o_time_format'                => 'H:i:s',
            // 'o_date_format'                => 'Y-m-d',
            'o_timeout_visit'            => 1800,
            'o_timeout_online'            => 300,
            'o_show_version'            => 0,
            'o_show_user_info'            => 1,
            'o_show_post_count'            => 1,
            'o_signatures'                => 1,
            'o_smilies'                    => 1,
            'o_smilies_sig'                => 1,
            'o_make_links'                => 1,
            // 'o_default_lang'            => $data['default_lang'],
            // 'o_default_style'            => $data['default_style'],
            'o_default_user_group'        => 4,
            'o_topic_review'            => 15,
            // 'o_disp_topics_default'        => 30,
            // 'o_disp_posts_default'        => 25,
            'o_indent_num_spaces'        => 4,
            'o_quote_depth'                => 3,
            'o_quickpost'                => 1,
            'o_users_online'            => 1,
            'o_censoring'                => 0,
            'o_show_dot'                => 0,
            'o_topic_views'                => 1,
            'o_quickjump'                => 1,
            'o_gzip'                    => 0,
            'o_additional_navlinks'        => '',
            'o_report_method'            => 0,
            'o_regs_report'                => 0,
            // 'o_default_email_setting'    => 1,
            'o_mailing_list'            => $data['email'],
            'o_avatars'                    => $data['avatars'],
            'o_avatars_dir'                => 'style/img/avatars',
            'o_avatars_width'            => 60,
            'o_avatars_height'            => 60,
            'o_avatars_size'            => 10240,
            'o_search_all_forums'        => 1,
            'o_base_url'                => $data['base_url'],
            'o_admin_email'                => $data['email'],
            'o_webmaster_email'            => $data['email'],
            'o_forum_subscriptions'        => 1,
            'o_topic_subscriptions'        => 1,
            'o_smtp_host'                => null,
            'o_smtp_user'                => null,
            'o_smtp_pass'                => null,
            'o_smtp_ssl'                => 0,
            'o_regs_allow'                => 1,
            'o_regs_verify'                => 0,
            'o_announcement'            => 0,
            'o_announcement_message'    => __('Announcement'),
            'o_rules'                    => 0,
            'o_rules_message'            => __('Rules'),
            'o_maintenance'                => 0,
            'o_maintenance_message'        => __('Maintenance message'),
            // 'o_default_dst'                => 0,
            'p_message_bbcode'            => 1,
            'p_message_img_tag'            => 1,
            'p_message_all_caps'        => 1,
            'p_subject_all_caps'        => 1,
            'p_sig_all_caps'            => 1,
            'p_sig_bbcode'                => 1,
            'p_sig_img_tag'                => 0,
            'p_sig_length'                => 400,
            'p_sig_lines'                => 4,
            'p_allow_banned_email'        => 1,
            'p_allow_dupe_email'        => 0,
            'p_force_guest_email'        => 1
        ];
    }
}
