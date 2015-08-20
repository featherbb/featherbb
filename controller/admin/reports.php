<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace controller\admin;

class reports
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
        $this->model = new \model\admin\reports();
        load_textdomain('featherbb', FEATHER_ROOT.'lang/'.$this->user->language.'/admin/reports.mo');
        require FEATHER_ROOT . 'include/common_admin.php';
    }

    public function __autoload($class_name)
    {
        require FEATHER_ROOT . $class_name . '.php';
    }

    public function display()
    {
        if (!$this->user->is_admmod) {
            message(__('No permission'), '403');
        }

        define('FEATHER_ADMIN_CONSOLE', 1);

        // Zap a report
        if ($this->feather->request->isPost()) {
            $this->model->zap_report();
        }

        $page_title = array(feather_escape($this->config['o_board_title']), __('Admin'), __('Reports'));

        $this->header->setTitle($page_title)->setActivePage('admin')->display();

        generate_admin_menu('reports');

        $this->feather->render('admin/reports.php', array(
                'report_data'   =>  $this->model->get_reports(),
                'report_zapped_data'   =>  $this->model->get_zapped_reports(),
            )
        );

        $this->footer->display();
    }
}
