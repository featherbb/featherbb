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
        load_textdomain('featherbb', FEATHER_ROOT.'lang/'.$this->user->language.'/admin/options.mo');
        require FEATHER_ROOT . 'include/common_admin.php';
    }

    public function __autoload($class_name)
    {
        require FEATHER_ROOT . $class_name . '.php';
    }
    
    public function display()
    {
        if ($this->user->g_id != FEATHER_ADMIN) {
            message(__('No permission'), '403');
        }

        define('FEATHER_ADMIN_CONSOLE', 1);

        if ($this->feather->request->isPost()) {
            $this->model->update_options();
        }

        $page_title = array(feather_escape($this->config['o_board_title']), __('Admin'), __('Options'));

        define('FEATHER_ACTIVE_PAGE', 'admin');

        $this->header->setTitle($page_title)->display();

        generate_admin_menu('options');

        $this->feather->render('admin/options.php', array(
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
