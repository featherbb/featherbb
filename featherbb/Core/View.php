<?php

/**
* Copyright (C) 2015-2016 FeatherBB
* based on code by (C) 2008-2015 FluxBB
* and Rickard Andersson (C) 2002-2008 PunBB
* License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
*/

namespace FeatherBB\Core;

use FeatherBB\Core\Interfaces\Container;
use FeatherBB\Core\Interfaces\Lang;

class View
{
    protected $directories = [];
    protected $templates;
    protected $app;
    protected $data;
    protected $assets;
    protected $validation = [
        'page_number' => 'intval',
        'active_page' => 'strval',
        'is_indexed' => 'boolval',
        'admin_console' => 'boolval',
        'has_reports' => 'boolval',
        'paging_links' => 'strval',
        'footer_style' => 'strval',
        'fid' => 'intval',
        'pid' => 'intval',
        'tid' => 'intval'
    ];

    public $twig;
    public $loader = null;

    /**
    * Constructor
    */
    public function __construct()
    {
        $this->data = new \FeatherBB\Core\Set();
        $this->loader = new \Twig_Loader_Filesystem();
        $this->twig = new \Twig_Environment($this->loader, [
            'cache' => ForumEnv::get('FEATHER_ROOT') . 'cache',
            'debug' => true,
        ]);
        // load extensions
        /*$this->twig->addExtension(new \Twig_Extension_Profiler(
            Container::get('twig_profile')
        ));*/
        if (ForumEnv::get('FEATHER_DEBUG')) {
            $this->twig->addExtension(new \Twig_Extension_Debug());
        }
        $this->twig->addExtension(new \FeatherBB\Core\RunBBTwig());

        return $this;
    }

    /********************************************************************************
    * Data methods
    *******************************************************************************/

    /**
    * Does view data have value with key?
    * @param  string  $key
    * @return boolean
    */
    public function has($key)
    {
        return $this->data->has($key);
    }

    /**
    * Return view data value with key
    * @param  string $key
    * @return mixed
    */
    public function get($key)
    {
        return $this->data->get($key);
    }

    /**
    * Set view data value with key
    * @param string $key
    * @param mixed $value
    */
    public function set($key, $value)
    {
        $this->data->set($key, $value);
    }

    /**
    * Set view data value as Closure with key
    * @param string $key
    * @param mixed $value
    */
    public function keep($key, \Closure $value)
    {
        $this->data->keep($key, $value);
    }

    /**
    * Replace view data
    * @param  array  $data
    */
    public function replace(array $data)
    {
        $this->data->replace($data);
    }

    /**
    * Clear view data
    */
    public function clear()
    {
        $this->data->clear();
    }

    /********************************************************************************
    * Resolve template paths
    *******************************************************************************/

    /**
     * @param string $dir
     * @param string $alias
     * @return $this
     */
    public function addTemplatesDirectory($dir = '', $alias = 'forum')
    {
        $this->loader->addPath($dir, $alias);
        return $this;
    }

    /********************************************************************************
    * Rendering
    *******************************************************************************/

    public function display($nested = true)
    {
        $data = [];
        $data = array_merge($this->getDefaultPageInfo(), $this->data->all(), (array) $data);
        $data = Container::get('hooks')->fire('view.alter_data', $data);
        $data['assets'] = $this->getAssets();

        $templates = $this->getTemplates();
        $style = $this->getStyle();
        $tpl = trim(array_pop($templates));// get last in array

        $data['nested'] = $nested;
        $data['pageTitle'] = Utils::generatePageTitle($data['title'], $data['page_number']);
        $data['flashMessages'] = Container::get('flash')->getMessages();
        $data['style'] = $style;
        $data['navlinks'] = $this->buildNavLinks($data['active_page']);
        $data['currentPage'] = '';

        if (file_exists(ForumEnv::get('FEATHER_ROOT').'style/themes/'.$style.'/base_admin.css')) {
            $admStyle = '<link rel="stylesheet" type="text/css" href="/themes/'.$style.'/base_admin.css" />';
        } else {
            $admStyle = '<link rel="stylesheet" type="text/css" href="/imports/base_admin.css" />';
        }
        $data['admStyle'] = $admStyle;

        Response::getBody()->write(
            $this->twig->render($tpl. '.html.twig', $data)
        );
        return Container::get('response');
    }
    /********************************************************************************
    * Getters and setters
    *******************************************************************************/

    /**
     * Initialise style, load assets for given style
     * @param $style
     * @throws FeatherBBException
     */
    public function setStyle($style)
    {
        $dir = ForumEnv::get('FEATHER_ROOT').'style/themes/'.$style.'/';
        if (!is_dir($dir)) {
            throw new \Exception('The style '.$style.' doesn\'t exist');
        }

        if (is_file($dir . 'bootstrap.php')) {
            $vars = include_once $dir . 'bootstrap.php';
            // file exist but return nothing
            if (!is_array($vars)) {
                $vars = [];
            }
            foreach ($vars as $key => $assets) {
                if ($key === 'jsraw' || !in_array($key, ['js', 'jshead', 'css'])) {
                    continue;
                }
                foreach ($assets as $asset) {
                    $params = ($key === 'css') ? ['type' => 'text/css', 'rel' => 'stylesheet'] : (
                    ($key === 'js' || $key === 'jshead') ? ['type' => 'text/javascript'] : []
                    );
                    $this->addAsset($key, $asset, $params);
                }
            }
            $this->set('jsraw', isset($vars['jsraw']) ? $vars['jsraw'] : '');
        }

        if (isset($vars['themeTemplates']) && $vars['themeTemplates'] == true) {
            $templatesDir = ForumEnv::get('FEATHER_ROOT') . 'style/themes/'.$style.'/view';
        } else {
            $templatesDir = ForumEnv::get('FEATHER_ROOT') . 'featherbb/View/';
        }

        $this->data->set('style', (string) $style);

        $this->addTemplatesDirectory($templatesDir);
    }

    public function getStyle()
    {
        return $this->data['style'];
    }

    public function setPageInfo(array $data)
    {
        foreach ($data as $key => $value) {
            list($key, $value) = $this->validate($key, $value);
            $this->data->set($key, $value);
        }
        return $this;
    }

    public function getPageInfo()
    {
        return $this->data->all();
    }

    protected function validate($key, $value)
    {
        $key = (string) $key;
        if (isset($this->validation[$key])) {
            if (function_exists($this->validation[$key])) {
                $value = $this->validation[$key]($value);
            }
        }
        return [$key, $value];
    }

    public function addAsset($type, $asset, $params = [])
    {
        $type = (string) $type;
        if (!in_array($type, ['js', 'jshead', 'css', 'feed', 'canonical', 'prev', 'next'])) {
            throw new \Exception('Invalid asset type : ' . $type);
        }
        if (in_array($type, ['js', 'jshead', 'css']) && !is_file(ForumEnv::get('WEB_ROOT').$asset)) {
            throw new \Exception('The asset file ' . $asset . ' does not exist');
        }

        $params = array_merge(static::getDefaultParams($type), $params);
        if (isset($params['title'])) {
            $params['title'] = Utils::escape($params['title']);
        }
        $this->assets[$type][] = [
            'file' => (string) $asset,
            'params' => $params
        ];
    }

    public function getAssets()
    {
        return $this->assets;
    }

    public function addTemplate($tpl, $priority = 10)
    {
        $tpl = (array) $tpl;
        foreach ($tpl as $key => $tpl_file) {
            $this->templates[(int) $priority][] = (string) $tpl_file;
        }
        return $this;
    }

    public function getTemplates()
    {
        $output = [];
        if (count($this->templates) > 1) {
            ksort($this->templates);
        }
        foreach ($this->templates as $priority) {
            if (!empty($priority)) {
                foreach ($priority as $tpl) {
                    $output[] = $tpl;
                }
            }
        }
        return $output;
    }

    public function addMessage($msg, $type = 'info')
    {
        if (Container::get('flash')) {
            if (in_array($type, ['info', 'error', 'warning', 'success'])) {
                Container::get('flash')->addMessage($type, (string) $msg);
            }
        }
    }

    public function __call($method, $args)
    {
        $method = mb_substr(preg_replace_callback('/([A-Z])/', function ($c) {
            return '_' . strtolower($c[1]);
        }, $method), 4);
        if (empty($args)) {
            $args = null;
        }
        list($key, $value) = $this->validate($method, $args);
        $this->data->set($key, $value);
    }

    protected function getDefaultPageInfo()
    {
        // Check if config file exists to avoid error when installing forum
        if (!Container::get('cache')->isCached('quickjump') && is_file(ForumEnv::get('FORUM_CONFIG_FILE'))) {
            Container::get('cache')->store('quickjump', \FeatherBB\Model\Cache::getQuickjump());
        }

        $title = Container::get('forum_settings') ? ForumSettings::get('o_board_title') : 'FeatherBB';

        $data = [
            'title' => Utils::escape($title),
            'page_number' => null,
            'active_page' => 'index',
            'focus_element' => null,
            'is_indexed' => true,
            'admin_console' => false,
            'page_head' => null,
            'paging_links' => null,
            'required_fields' => null,
            'footer_style' => null,
            'quickjump' => Container::get('cache')->retrieve('quickjump'),
            'fid' => null,
            'pid' => null,
            'tid' => null,
        ];

        if (User::get() !== null) {
            if (User::isAdminMod()) {
                $data['has_reports'] = \FeatherBB\Model\Admin\Reports::hasReports();
            }
            // check db configured
            if (Database::getConfig()['username'] !== null) {
                // guest user. for modal. load reg data from Register.php
                Lang::load('login');
                Lang::load('register');
                Lang::load('prof_reg');
                Lang::load('antispam');

                // FIXME rebuild
                // Antispam feature
                $lang_antispam_questions = require ForumEnv::get('FEATHER_ROOT').'featherbb/lang/'.User::getPref('language').'/antispam.php';
                $index_questions = rand(0, count($lang_antispam_questions) - 1);
                $data['index_questions'] = $index_questions;
                $data['question'] = array_keys($lang_antispam_questions);
                $data['qencoded'] = md5(array_keys($lang_antispam_questions)[$index_questions]);
                $data['logOutLink'] = Router::pathFor(
                    'logout',
                    ['token' => Random::hash(User::get()->id.Random::hash(Utils::getIp()))]
                );
            }
        }

        if (ForumEnv::get('FEATHER_SHOW_INFO')) {
            $data['exec_info'] = \FeatherBB\Model\Debug::getInfo();
            if (ForumEnv::get('FEATHER_SHOW_QUERIES')) {
                $data['queries_info'] = \FeatherBB\Model\Debug::getQueries();
            }
        }

        return $data;
    }

    protected static function getDefaultParams($type)
    {
        switch ($type) {
            case 'js':
                return ['type' => 'text/javascript'];
            case 'jshead':
                return ['type' => 'text/javascript'];
            case 'css':
                return ['rel' => 'stylesheet', 'type' => 'text/css'];
            case 'feed':
                return ['rel' => 'alternate', 'type' => 'application/atom+xml'];
            case 'canonical':
                return ['rel' => 'canonical'];
            case 'prev':
                return ['rel' => 'prev'];
            case 'next':
                return ['rel' => 'next'];
            default:
                return [];
        }
    }

    protected function buildNavLinks($active_page = '')
    {
        $navlinks = [];
        // user not initialized, possible we in install
        if (User::get() === null) {
            return $navlinks;
        }

        $navlinks[] = [
            'id' => 'navindex',
            'active' => ($active_page == 'index') ? ' class="isactive"' : '',
            'href' => Router::pathFor('home'),
            'text' => __('Index')
        ];

        if (User::can('board.read') && User::can('users.view')) {
            $navlinks[] = [
                'id' => 'navuserlist',
                'active' => ($active_page == 'userlist') ? ' class="isactive"' : '',
                'href' => Router::pathFor('userList'),
                'text' => __('User list')
            ];
        }

        if (ForumSettings::get('o_rules') == '1' && (!User::get()->is_guest || User::can('board.read') || ForumSettings::get('o_regs_allow') == '1')) {
            $navlinks[] = [
                'id' => 'navrules',
                'active' => ($active_page == 'rules') ? ' class="isactive"' : '',
                'href' => Router::pathFor('rules'),
                'text' => __('Rules')
            ];
        }

        if (User::can('board.read') && User::can('search.topics')) {
            $navlinks[] = [
                'id' => 'navsearch',
                'active' => ($active_page == 'search') ? ' class="isactive"' : '',
                'href' => Router::pathFor('search'),
                'text' => __('Search')
            ];
        }

        if (User::get()->is_guest) {
            $navlinks[] = [
                'id' => 'navregister',
                'active' => ($active_page == 'register') ? ' class="isactive"' : '',
                'href' => Router::pathFor('register'),
                'text' => __('Register')
            ];
            $navlinks[] = [
                'id' => 'navlogin',
                'active' => ($active_page == 'login') ? ' class="isactive"' : '',
                'href' => Router::pathFor('login'),
                'text' => __('Login')
            ];
        } else {
            $navlinks[] = [
                'id' => 'navprofile',
                'active' => ($active_page == 'profile') ? ' class="isactive"' : '',
                'href' => Router::pathFor('userProfile', ['id' => User::get()->id]),
                'text' => __('Profile')
            ];

            if (User::isAdminMod()) {
                $navlinks[] = [
                    'id' => 'navadmin',
                    'active' => ($active_page == 'admin') ? ' class="isactive"' : '',
                    'href' => Router::pathFor('adminIndex'),
                    'text' => __('Admin')
                ];
            }

            $navlinks[] = [
                'id' => 'navlogout',
                'active' => '',
                'href' => Router::pathFor('logout', ['token' => Random::hash(User::get()->id.Random::hash(Utils::getIp()))]),
                'text' => __('Logout')
            ];
        }

        // Are there any additional navlinks we should insert into the array before imploding it?
        $hooksLinks = Container::get('hooks')->fire('view.header.navlinks', []);
        $extraLinks = ForumSettings::get('o_additional_navlinks')."\n".implode("\n", $hooksLinks);
        if (User::can('board.read') && ($extraLinks != '')) {
            if (preg_match_all('%([0-9]+)\s*=\s*(.*?)\n%s', $extraLinks."\n", $results)) {
                // Insert any additional links into the $links array (at the correct index)
                $num_links = count($results[1]);
                for ($i = 0; $i < $num_links; ++$i) {
                    array_splice(
                        $navlinks,
                        $results[1][$i],
                        0,
                        ['<li id="navextra'.($i + 1).'"'.
                        (($active_page == 'navextra'.($i + 1)) ? ' class="isactive"' : '').'>'.
                        $results[2][$i].'</li>']
                    );
                }
            }
        }

        return $navlinks;
    }
}
