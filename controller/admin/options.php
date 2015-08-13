<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace controller\admin;

class options
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
        $this->model = new \model\admin\options();
    }

    public function __autoload($class_name)
    {
        require FEATHER_ROOT . $class_name . '.php';
    }
    
    public function display()
    {
        global $lang_common, $lang_admin_common;

        require FEATHER_ROOT.'include/common_admin.php';

        if ($this->user->g_id != FEATHER_ADMIN) {
            message($lang_common['No permission'], '403');
        }

        define('FEATHER_ADMIN_CONSOLE', 1);

        // Load the admin_options.php language file
        require FEATHER_ROOT.'lang/'.$admin_language.'/options.php';

        if ($this->feather->request->isPost()) {
            $this->model->update_options();
        }

        $page_title = array(feather_escape($this->config['o_board_title']), $lang_admin_common['Admin'], $lang_admin_common['Options']);

        define('FEATHER_ACTIVE_PAGE', 'admin');

        $this->header->setTitle($page_title)->display();

        generate_admin_menu('options');

        $this->feather->render('admin/options.php', array(
                'lang_admin_options'    =>    $lang_admin_options,
                'feather_config'    =>    $this->config,
                'feather_user'    =>    $this->user,
                'languages' => forum_list_langs(),
                'styles' => $this->model->get_styles(),
                'times' => $this->model->get_times(),
                'feather'    =>    $this->feather,
            )
        );

        $this->footer->display();
    }
}
