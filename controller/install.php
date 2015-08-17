<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace controller;

class install
{
    protected $supported_dbs = array('mysql' => 'MySQL',
                                     'pgsql' => 'PostgreSQL',
                                     'sqlite' => 'SQLite',
                                     'sqlite3' => 'SQLite3',
                                    );
    protected $optional_fields = array('db_user', 'db_pass', 'db_prefix');
    protected $default_style = 'FeatherBB';
    protected $config_keys = array('db_type', 'db_host', 'db_name', 'db_user', 'db_pass', 'db_prefix');
    protected $default_config_path = 'include/config.php';
    protected $errors = array();

    public function __construct()
    {
        $this->feather = \Slim\Slim::getInstance();
        $this->model = new \model\install();

        // // Check to see whether FeatherBB is already installed
        // if (!is_null($this->feather->forum_env['CONFIG_PATH'])) {
        //     $config = @json_decode(file_get_contents($this->feather->forum_env['FEATHER_ROOT'].$this->feather->forum_env['CONFIG_PATH']), true);
        //     if (is_array($config)) {
        //         redirect(get_link(''), __('Already installed'));
        //     }
        // }
        load_textdomain('featherbb', $this->feather->forum_env['FEATHER_ROOT'].'lang/English/install.mo');
    }

    public function run()
    {
        if ($this->feather->request->isPost()) {
            $missing_fields = array();
            $data = array_map(function ($item) {
                return feather_escape(feather_trim($item));
            }, $this->feather->request->post('install'));

            foreach ($data as $field => $value) {
                // Handle empty fields
                if (empty($value)) {
                    // If the field is required, or if user and pass are missing even though mysql or pgsql are selected as DB
                    if (!in_array($field, $this->optional_fields) || (in_array($field, array('db_user', 'db_pass')) && in_array($data['db_type'], array('mysql', 'pgsql')))) {
                        $missing_fields[] = $field;
                    }
                }
            }

            if (!empty($missing_fields)) {
                $this->errors = 'The following fields are required but are missing : '.implode(', ', $missing_fields);
            } else { // Missing fields, so we don't need to validate the others
                // VALIDATION
                // Make sure base_url doesn't end with a slash
                if (substr($data['base_url'], -1) == '/') {
                    $data['base_url'] = substr($data['base_url'], 0, -1);
                }

                // Validate username and passwords
                if (feather_strlen($data['username']) < 2) {
                    $this->errors[] = __('Username 1');
                } elseif (feather_strlen($data['username']) > 25) { // This usually doesn't happen since the form element only accepts 25 characters
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

                if (feather_strlen($data['password']) < 6) {
                    $this->errors[] = __('Short password');
                } elseif ($data['password'] != $data['password_conf']) {
                    $this->errors[] = __('Passwords not match');
                }

                // Validate email
                if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                    $this->errors[] = __('Wrong email');
                }

                // Validate language
                if (!in_array($data['default_lang'], forum_list_langs())) {
                    $this->errors[] = __('Error default language');
                }

                // Check if the cache directory is writable
                if (!is_writable($this->feather->forum_env['FORUM_CACHE_DIR'])) {
                    $this->errors[] = sprintf(__('Alert cache'), $this->feather->forum_env['FORUM_CACHE_DIR']);
                }

                // Check if default avatar directory is writable
                if (!is_writable($this->feather->forum_env['FEATHER_ROOT'].'img/avatars/')) {
                    $this->errors[] = sprintf(__('Alert avatar'), $this->feather->forum_env['FEATHER_ROOT'].'img/avatars/');
                }

                // Validate db_prefix if existing
                if (!empty($data['db_prefix']) && ((strlen($data['db_prefix']) > 0 && (!preg_match('%^[a-zA-Z_][a-zA-Z0-9_]*$%', $data['db_prefix']) || strlen($data['db_prefix']) > 40)))) {
                    $this->errors[] = sprintf(__('Table prefix error'), $data['db_prefix']);
                }
            }

            // End validation and check errors
            if (!empty($this->errors)) {
                $this->feather->view()->setTemplatesDirectory($this->feather->forum_env['FEATHER_ROOT'].'style/FeatherBB/view');
                $this->feather->view()->display('install.php', array(
                                                        'feather' => $this->feather,
                                                        'languages' => forum_list_langs(),
                                                        'supported_dbs' => $this->supported_dbs,
                                                        'data' => $data,
                                                        'errors' => $this->errors,
                                                    ));
            } else {
                $data['default_style'] = $this->default_style;
                $data['avatars'] = in_array(strtolower(@ini_get('file_uploads')), array('on', 'true', '1')) ? 1 : 0;
                $this->create_config($data);
            }
        } else {
            $data = array('title' => __('My FeatherBB Forum'),
                          'description' => '<p><span>'.__('Description').'</span></p>',
                          'base_url' => $this->feather->request->getUrl().$this->feather->request->getRootUri(),
                          'default_lang' => 'English');
            if (isset($this->environment['slim.flash'])) {
                $this->feather->view()->set('flash', $this->environment['slim.flash']);
            }
            $this->feather->view()->setTemplatesDirectory($this->feather->forum_env['FEATHER_ROOT'].'style/FeatherBB/view');
            $this->feather->view()->display('install.php', array(
                                                'feather' => $this->feather,
                                                'languages' => forum_list_langs(),
                                                'supported_dbs' => $this->supported_dbs,
                                                'data' => $data,
                                                'alerts' => array()));
        }
    }

    public function create_config(array $data)
    {
        // Generate config ...
        $config = array();
        foreach ($data as $key => $value) {
            if (in_array($key, $this->config_keys)) {
                $config[$key] = $value;
            }
        }

        $config = array_merge($config, array('cookie_name' => mb_strtolower($this->feather->forum_env['FORUM_NAME']).'_cookie_'.random_key(7, false, true),
                                             'cookie_seed' => random_key(16, false, true)));

        // ... And write it on disk
        if ($this->write_config(json_encode($config, JSON_PRETTY_PRINT))) {
            $this->create_db($data);
        }
    }

    public function create_db(array $data)
    {
        \Slim\Extras\Middleware\FeatherBBLoader::init_db($data);

        // Check if FeatherBB DB isn't installed already
        if ($this->model->is_installed()) {
            redirect(get_link(''), 'DB already installed');
        }

        // Create tables
        foreach ($this->model->get_database_scheme() as $table => $sql) {
            $table = (!empty($data['db_prefix'])) ? $data['db_prefix'].$table : $table;
            if (!$this->model->create_table($table, $sql)) {
                // Error handling
                $this->errors[] = 'A problem was encountered while creating table '.$table;
            }
        }

        // Populate group table with default values
        foreach ($this->model->load_default_groups() as $group_name => $group_data) {
            $this->model->add_group($group_data);
        }
        // Populate user table with default values
        $this->model->add_user($this->model->load_default_user());
        $this->model->add_user($this->model->load_admin_user($data));
        // Populate categories, forums, topics, posts
        $this->model->add_mock_forum($this->model->load_mock_forum_data($data));
    }

    public function write_config($json)
    {
        return file_put_contents($this->feather->forum_env['FEATHER_ROOT'].$this->default_config_path, $json);
    }

    public function load_default_config(array $data)
    {
        $config = array(
            'o_cur_version'                => $this->feather->forum_env['FORUM_VERSION'],
            'o_database_revision'        => $this->feather->forum_env['FORUM_DB_REVISION'],
            'o_searchindex_revision'    => $this->feather->forum_env['FORUM_SI_REVISION'],
            'o_parser_revision'            => $this->feather->forum_env['FORUM_PARSER_REVISION'],
            'o_board_title'                => $data['title'],
            'o_board_desc'                => $data['description'],
            'o_default_timezone'        => 0,
            'o_time_format'                => 'H:i:s',
            'o_date_format'                => 'Y-m-d',
            'o_timeout_visit'            => 1800,
            'o_timeout_online'            => 300,
            'o_redirect_delay'            => 1,
            'o_show_version'            => 0,
            'o_show_user_info'            => 1,
            'o_show_post_count'            => 1,
            'o_signatures'                => 1,
            'o_smilies'                    => 1,
            'o_smilies_sig'                => 1,
            'o_make_links'                => 1,
            'o_default_lang'            => $data['default_lang'],
            'o_default_style'            => $data['default_style'],
            'o_default_user_group'        => 4,
            'o_topic_review'            => 15,
            'o_disp_topics_default'        => 30,
            'o_disp_posts_default'        => 25,
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
            'o_default_email_setting'    => 1,
            'o_mailing_list'            => $data['email'],
            'o_avatars'                    => $data['avatars'],
            'o_avatars_dir'                => 'img/avatars',
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
            'o_default_dst'                => 0,
            'o_feed_type'                => 2,
            'o_feed_ttl'                => 0,
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
        );
        return $config;
    }
}
