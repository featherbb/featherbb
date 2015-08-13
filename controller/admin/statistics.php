<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace controller\admin;

class statistics
{
    public function __construct()
    {
        $this->feather = \Slim\Slim::getInstance();
        $this->start = $this->feather->start;
        $this->config = $this->feather->config;
        $this->user = $this->feather->user;
        $this->request = $this->feather->request;
        $this->header = new \controller\header();
        $this->footer = new \controller\footer();
        $this->model = new \model\admin\statistics();
    }

    public function __autoload($class_name)
    {
        require FEATHER_ROOT . $class_name . '.php';
    }
    
    public function display()
    {
        global $lang_common, $lang_admin_common, $lang_admin_index;

        require FEATHER_ROOT.'include/common_admin.php';

        if (!$this->user->is_admmod) {
            message($lang_common['No permission'], '403');
        }

        define('FEATHER_ADMIN_CONSOLE', 1);

        // Load the admin_index.php language file
        require FEATHER_ROOT.'lang/'.$admin_language.'/index.php';

        $page_title = array(feather_escape($this->config['o_board_title']), $lang_admin_common['Admin'], $lang_admin_common['Server statistics']);

        define('FEATHER_ACTIVE_PAGE', 'admin');

        $this->header->setTitle($page_title)->display();

        generate_admin_menu('index');

        $total = $this->model->get_total_size();

        $this->feather->render('admin/statistics.php', array(
                'lang_admin_common'    =>    $lang_admin_common,
                'lang_admin_index'    =>    $lang_admin_index,
                'feather_config'    =>    $this->config,
                'server_load'    =>    $this->model->get_server_load(),
                'num_online'    =>    $this->model->get_num_online(),
                'total_size'    =>    $total['size'],
                'total_records'    =>    $total['records'],
                'php_accelerator'    =>    $this->model->get_php_accelerator(),
                'feather'    =>    $this->feather,
            )
        );

        $this->footer->display();
    }


    public function phpinfo()
    {
        global $lang_common, $lang_admin_common, $lang_admin_index;

        require FEATHER_ROOT.'include/common_admin.php';

        // Load the admin_index.php language file
        require FEATHER_ROOT.'lang/'.$admin_language.'/index.php';

        if ($this->user->g_id != FEATHER_ADMIN) {
            message($lang_common['No permission'], '403');
        }

        // Show phpinfo() output
        // Is phpinfo() a disabled function?
        if (strpos(strtolower((string) ini_get('disable_functions')), 'phpinfo') !== false) {
            message($lang_admin_index['PHPinfo disabled message']);
        }

        phpinfo();
        $this->feather->stop();
    }
}
