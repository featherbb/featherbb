<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

 namespace FeatherBB;

 class View
 {
     protected $data,
               $templatesDirectory,
               $app,
               $page,
               $validation = array(
                   'page_number' => 'intval',
                   'active_page' => 'strval',
                   'focus_element' => 'strval',
                   'is_indexed' => 'boolval',
                   'page_head' => 'strval',
                   'paging_links' => 'strval',
                   'required_fields' => 'strval',
                   'has_reports' => 'boolval',
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
                  throw new \RuntimeException("View cannot render `$file` because the template does not exist");
              }
          }
          return $pathname;
      }

     /********************************************************************************
      * Rendering
      *******************************************************************************/

     /**
      * Display template
      *
      * This method echoes the rendered template to the current output buffer
      *
      * @param  string   $template   Pathname of template file relative to templates directory
      * @param  array    $data       Any additonal data to be passed to the template.
      */
     public function display($template, $data = null)
     {
         echo $this->fetch($template, $data);
     }

     /**
      * Return the contents of a rendered template file
      *
      * @param    string $template   The template pathname, relative to the template base directory
      * @param    array  $data       Any additonal data to be passed to the template.
      * @return string               The rendered template
      */
     public function fetch($template, $data = null)
     {
         return $this->render($template, $data);
     }

     /**
      * Render a template file
      *
      * NOTE: This method should be overridden by custom view subclasses
      *
      * @param  string $template     The template pathname, relative to the template base directory
      * @param  array  $data         Any additonal data to be passed to the template.
      * @return string               The rendered template
      * @throws \RuntimeException    If resolved template pathname is not a valid file
      */
     protected function render($template, $data = null)
     {
         $data = array_merge($this->getDefaultPageInfo(), $this->page->all(), $this->data->all(), (array) $data);
         $data = $this->app->hooks('view.alter_data', $data);
         extract($data);
         ob_start();
         require $this->getTemplatePathname($template);

         return ob_get_clean();
     }

     // Getters & Setters

     public function setStyle($style)
     {
         if (!is_dir($this->app->forum_env['FEATHER_ROOT'].'style/'.$style.'/view/')) {
             throw new \InvalidArgumentException('The style '.$style.' doesn\'t exist');
         }
         $this->data->set('style', (string) $style);
         $this->setTemplatesDirectory($this->app->forum_env['FEATHER_ROOT'].'style/'.$style.'/view');
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

     protected function getDefaultPageInfo()
     {
         if (!$this->app->cache->isCached('quickjump')) {
             $this->app->cache->store('quickjump', \model\cache::get_quickjump());
         }

         return array(
             'title' => $this->app->forum_settings['o_board_title'],
             'page_number' => null,
             'active_page' => 'index',
             'focus_element' => null,
             'is_indexed' => true,
             'page_head' => null,
             'paging_links' => null,
             'required_fields' => null,
             'has_reports' => \model\header::get_reports(),
             'footer_style' => null,
             'quickjump' => $this->app->cache->retrieve('quickjump'),
             'fid' => null,
             'pid' => null,
             'tid' => null,
         );
     }
 }
 ?>
