<?php

/**
* Copyright (C) 2015-2016 FeatherBB
* based on code by (C) 2008-2015 FluxBB
* and Rickard Andersson (C) 2002-2008 PunBB
* License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
*/

namespace FeatherBB\Core;

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
        'tid' => 'intval'];

    /**
    * Constructor
    */
    public function __construct()
    {
        $this->data = new \FeatherBB\Helpers\Set();
        // Set default dir for view fallback
        $this->addTemplatesDirectory(ForumEnv::get('FEATHER_ROOT') . 'featherbb/View/', 10);
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


    public function addTemplatesDirectory($data, $priority = 10)
    {
        $directories = (array) $data;
        foreach ($directories as $key => $tpl_dir) {
            if (is_dir($tpl_dir)) {
                $this->directories[(int) $priority][] = rtrim((string) $tpl_dir, DIRECTORY_SEPARATOR);
            }
        }
        return $this;
    }

    /**
    * Get templates directories ordered by priority
    * @return string
    */
    public function getTemplatesDirectory()
    {
        if (User::get()) {
            $this->setStyle(User::getPref('style'));
        }

        $output = [];
        if (count($this->directories) > 1) {
            ksort($this->directories);
        }
        foreach ($this->directories as $priority) {
            if (!empty($priority)) {
                foreach ($priority as $tpl_dir) {
                    $output[] = $tpl_dir;
                }
            }
        }
        return $output;
    }

    /**
    * Get fully qualified path to template file using templates base directory
    * @param  string $file The template file pathname relative to templates base directory
    * @return string
    */
    public function getTemplatePathname($file)
    {
        foreach ($this->getTemplatesDirectory() as $tpl_dir) {
            $pathname = realpath($tpl_dir . DIRECTORY_SEPARATOR . ltrim($file, DIRECTORY_SEPARATOR));
            if (is_file($pathname)) {
                return (string) $pathname;
            }
        }
        throw new \RuntimeException("View cannot add template `$file` to stack because the template does not exist");
    }

    /********************************************************************************
    * Rendering
    *******************************************************************************/

    public function display($nested = true)
    {
        return $this->fetch($nested);
    }

    protected function fetch($nested = true)
    {
        $data = [];
        $data = array_merge($this->getDefaultPageInfo(), $this->data->all(), (array) $data);
        $data['feather'] = true;
        $data['assets'] = $this->getAssets();
        $data = Container::get('hooks')->fire('view.alter_data', $data);
        return $this->render($data, $nested);
    }

    protected function render($data = null, $nested = true)
    {
        extract($data);
        ob_start();
        // Include view files
        if ($nested) {
            include $this->getTemplatePathname('header.php');
        }
        foreach ($this->getTemplates() as $tpl) {
            include $tpl;
        }
        if ($nested) {
            include $this->getTemplatePathname('footer.php');
        }

        $output = ob_get_clean();
        Response::getBody()->write($output);

        return Container::get('response');
    }

    /********************************************************************************
    * Getters and setters
    *******************************************************************************/

    public function setStyle($style)
    {
        if (!is_dir(ForumEnv::get('FEATHER_ROOT').'style/themes/'.$style.'/')) {
            throw new \InvalidArgumentException('The style '.$style.' doesn\'t exist');
        }
        // Add theme main and admin panel (if needed) stylesheets
        $this->addAsset('css', 'style/themes/'.$style.'/style.css', ['rel' => 'stylesheet', 'type' => 'text/css']);
        if ($this->has('admin_console')) {
            if (file_exists(ForumEnv::get('FEATHER_ROOT').'style/themes/'.$style.'/base_admin.css')) {
                $this->addAsset('css', 'style/themes/'.$style.'/base_admin.css', ['rel' => 'stylesheet', 'type' => 'text/css']);
            } else {
                $this->addAsset('css', 'style/imports/base_admin.css', ['rel' => 'stylesheet', 'type' => 'text/css']);
            }
        }
        // Add javascript files in theme root
        foreach (glob(ForumEnv::get('FEATHER_ROOT').'style/themes/'.$style.'/*.js') as $script) {
            $parts = explode('/', $script);
            $this->addAsset('js', 'style/themes/'.$style.'/'.end($parts), ['type' => 'text/javascript']);
        }
        // Override default templates directory if file exists in theme
        $this->addTemplatesDirectory(ForumEnv::get('FEATHER_ROOT').'style/themes/'.$style.'/view', 9);
        return $this;
    }

    public function setPageInfo($data)
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
        if (!in_array($type, ['js', 'css', 'canonical', 'prev', 'next'])) {
            throw new \Exception('Invalid asset type : ' . $type);
        }
        if (in_array($type, ['js', 'css']) && !is_file(ForumEnv::get('FEATHER_ROOT').$asset)) {
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
        $assets = $this->assets;
        $assets = Container::get('hooks')->fire('view.alter_assets', $assets);
        return $assets;
    }

    public function addTemplate($tpl, $priority = 10)
    {
        $tpl = (array) $tpl;
        foreach ($tpl as $key => $tpl_file) {
            $this->templates[(int) $priority][] = $this->getTemplatePathname((string) $tpl_file);
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
            return "_" . strtolower($c[1]);
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
            Container::get('cache')->store('quickjump', \FeatherBB\Model\Cache::get_quickjump());
        }

        $title = Container::get('forum_settings') ? ForumSettings::get('o_board_title') : 'FeatherBB';

        $data = [
            'title' => Utils::escape($title),
            'page_number' => null,
            'active_page' => 'index',
            'is_indexed' => true,
            'admin_console' => false,
            'page_head' => null,
            'paging_links' => null,
            'footer_style' => null,
            'quickjump' => Container::get('cache')->retrieve('quickjump'),
            'fid' => null,
            'pid' => null,
            'tid' => null,
        ];

        if (is_object(User::get()) && User::isAdminMod()) {
            $data['has_reports'] = \FeatherBB\Model\Admin\Reports::has_reports();
        }

        if (ForumEnv::get('FEATHER_SHOW_INFO')) {
            $data['exec_info'] = \FeatherBB\Model\Debug::get_info();
            if (ForumEnv::get('FEATHER_SHOW_QUERIES')) {
                $data['queries_info'] = \FeatherBB\Model\Debug::get_queries();
            }
        }

        return $data;
    }

    protected static function getDefaultParams($type)
    {
        switch ($type) {
            case 'js':
                return ['type' => 'text/javascript'];
            case 'css':
                return ['rel' => 'stylesheet', 'type' => 'text/css'];
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
}
