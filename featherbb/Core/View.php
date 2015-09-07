<?php

/**
* Copyright (C) 2015 FeatherBB
* based on code by (C) 2008-2015 FluxBB
* and Rickard Andersson (C) 2002-2008 PunBB
* License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
*/

namespace FeatherBB\Core;

class View
{
    protected $templatesDirectory,
    $templates,
    $app,
    $data,
    $page,
    $assets,
    $validation = array(
        'page_number' => 'intval',
        'active_page' => 'strval',
        'is_indexed' => 'boolval',
        'admin_console' => 'boolval',
        'has_reports' => 'boolval',
        'paging_links' => 'strval',
        'footer_style' => 'strval',
        'fid' => 'intval',
        'pid' => 'intval',
        'tid' => 'intval');

    /**
    * Constructor
    */
    public function __construct()
    {
        $this->data = $this->page = new \Slim\Helper\Set();
        $this->app = \Slim\Slim::getInstance();
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
    * Return view data
    * @return array
    */
    public function all()
    {
        return $this->data->all();
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
    * Legacy data methods
    *******************************************************************************/

    /**
    * DEPRECATION WARNING! This method will be removed in the next major point release
    *
    * Get data from view
    */
    public function getData($key = null)
    {
        if (!is_null($key)) {
            return isset($this->data[$key]) ? $this->data[$key] : null;
        }

        return $this->data->all();
    }

    /**
    * DEPRECATION WARNING! This method will be removed in the next major point release
    *
    * Set data for view
    */
    public function setData()
    {
        $args = func_get_args();
        if (count($args) === 1 && is_array($args[0])) {
            $this->data->replace($args[0]);
        } elseif (count($args) === 2) {
            // Ensure original behavior is maintained. DO NOT invoke stored Closures.
            if (is_object($args[1]) && method_exists($args[1], '__invoke')) {
                $this->data->set($args[0], $this->data->protect($args[1]));
            } else {
                $this->data->set($args[0], $args[1]);
            }
        } else {
            throw new \InvalidArgumentException('Cannot set View data with provided arguments. Usage: `View::setData( $key, $value );` or `View::setData([ key => value, ... ]);`');
        }
    }

    /**
    * DEPRECATION WARNING! This method will be removed in the next major point release
    *
    * Append data to view
    * @param  array $data
    */
    public function appendData($data)
    {
        if (!is_array($data)) {
            throw new \InvalidArgumentException('Cannot append view data. Expected array argument.');
        }
        $this->data->replace($data);
    }

    /********************************************************************************
    * Resolve template paths
    *******************************************************************************/

    /**
    * Set the base directory that contains view templates
    * @param   string $directory
    * @throws  \InvalidArgumentException If directory is not a directory
    */
    public function setTemplatesDirectory($directory)
    {
        $this->templatesDirectory = rtrim($directory, DIRECTORY_SEPARATOR);
    }

    /**
    * Get templates base directory
    * @return string
    */
    public function getTemplatesDirectory()
    {
        return $this->templatesDirectory;
    }

    /**
    * Get fully qualified path to template file using templates base directory
    * @param  string $file The template file pathname relative to templates base directory
    * @return string
    */
    public function getTemplatePathname($file)
    {
        $pathname = $this->templatesDirectory . DIRECTORY_SEPARATOR . ltrim($file, DIRECTORY_SEPARATOR);
        if (!is_file($pathname)) {
            $pathname = $this->app->forum_env['FEATHER_ROOT'] . 'view/' . ltrim($file, DIRECTORY_SEPARATOR); // Fallback on default view
            if (!is_file($pathname)) {
                throw new \RuntimeException("View cannot add template `$file` to stack because the template does not exist");
            }
        }
        return (string) $pathname;
    }

    /********************************************************************************
    * Rendering
    *******************************************************************************/

    public function display($nested = true)
    {
        if ($this->app->user) {
            $this->setStyle($this->app->user->style);
        }
        echo $this->fetch($nested);
    }

    protected function fetch($nested = true)
    {
        $data = array();
        // Force flash messages
        if (isset($this->app->environment['slim.flash'])) {
            $this->data->set('flash', $this->app->environment['slim.flash']);
        }
        $data = array_merge($this->getDefaultPageInfo(), $this->page->all(), $this->data->all(), (array) $data);
        $data['feather'] = \Slim\Slim::getInstance();
        $data['assets'] = $this->getAssets();
        $data = $this->app->hooks->fire('view.alter_data', $data);
        return $this->render($data, $nested);
    }

    protected function render($data = null, $nested = true)
    {
        extract($data);
        ob_start();

        if ($nested) {
            require $this->getTemplatePathname('header.php');
        }
        foreach ($this->getTemplates() as $tpl) {
            require $tpl;
        }
        if ($nested) {
            require $this->getTemplatePathname('footer.php');
        }
        return ob_get_clean();
    }

    /********************************************************************************
    * Getters and setters
    *******************************************************************************/

    public function setStyle($style)
    {
        if (!is_dir($this->app->forum_env['FEATHER_ROOT'].'style/themes/'.$style.'/view/')) {
            throw new \InvalidArgumentException('The style '.$style.' doesn\'t exist');
        }
        $this->data->set('style', (string) $style);
        $this->setTemplatesDirectory($this->app->forum_env['FEATHER_ROOT'].'style/themes/'.$style.'/view');
        return $this;
    }

    public function getStyle()
    {
        return $this->data['style'];
    }

    public function setPageInfo(array $data)
    {
        foreach ($data as $key => $value) {
            list($key, $value) = $this->validate($key, $value);
            $this->page->set($key, $value);
        }
        return $this;
    }

    public function getPageInfo()
    {
        return $this->page->all();
    }

    protected function validate($key, $value)
    {
        $key = (string) $key;
        if (isset($this->validation[$key])) {
            if (function_exists($this->validation[$key])) {
                $value = $this->validation[$key]($value);
            }
        }
        return array($key, $value);
    }

    public function addAsset($type, $asset, $params = array())
    {
        $type = (string) $type;
        if (!in_array($type, array('js', 'css', 'feed', 'canonical', 'prev', 'next'))) {
            throw new \Exception('Invalid asset type : ' . $type);
        }
        if (in_array($type, array('js', 'css')) && !is_file($this->app->forum_env['FEATHER_ROOT'].$asset)) {
            throw new \Exception('The asset file ' . $asset . ' does not exist');
        }

        $params = array_merge(static::getDefaultParams($type), $params);
        if (isset($params['title'])) {
            $params['title'] = Utils::escape($params['title']);
        }
        $this->assets[$type][] = array(
            'file' => (string) $asset,
            'params' => $params
        );
    }

    public function getAssets()
    {
        return $this->assets;
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
        $output = array();
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
        if (isset($this->app->environment['slim.flash'])) {
            if (in_array($type, array('info', 'error'))) {
                $this->app->environment['slim.flash']->now($type, (string) $msg);
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
        $this->page->set($key, $value);
    }

    protected function getDefaultPageInfo()
    {
        // Check if config file exists to avoid error when installing forum
        if (!$this->app->cache->isCached('quickjump') && is_file($this->app->forum_env['FORUM_CONFIG_FILE'])) {
            $this->app->cache->store('quickjump', \FeatherBB\Model\Cache::get_quickjump());
        }

        $data = array(
            'title' => Utils::escape($this->app->forum_settings['o_board_title']),
            'page_number' => null,
            'active_page' => 'index',
            'focus_element' => null,
            'is_indexed' => true,
            'admin_console' => false,
            'page_head' => null,
            'paging_links' => null,
            'required_fields' => null,
            'footer_style' => null,
            'quickjump' => $this->app->cache->retrieve('quickjump'),
            'fid' => null,
            'pid' => null,
            'tid' => null,
        );

        if (is_object($this->app->user) && $this->app->user->is_admmod) {
            $data['has_reports'] = \FeatherBB\Model\Header::get_reports();
        }

        if ($this->app->forum_env['FEATHER_SHOW_INFO']) {
            $data['exec_info'] = \FeatherBB\Model\Debug::get_info();
            if ($this->app->forum_env['FEATHER_SHOW_QUERIES']) {
                $data['queries_info'] = \FeatherBB\Model\Debug::get_queries();
            }
        }

        return $data;
    }

    protected static function getDefaultParams($type)
    {
        switch($type) {
            case 'js':
                return array('type' => 'text/javascript');
            case 'css':
                return array('rel' => 'stylesheet', 'type' => 'text/css');
            case 'feed':
                return array('rel' => 'alternate', 'type' => 'application/atom+xml');
            case 'canonical':
                return array('rel' => 'canonical');
            case 'prev':
                return array('rel' => 'prev');
            case 'next':
                return array('rel' => 'next');
            default:
                return array();
        }
    }
}
