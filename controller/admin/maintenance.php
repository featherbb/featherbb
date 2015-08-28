<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace controller\admin;

class maintenance
{
    public function __construct()
    {
        $this->feather = \Slim\Slim::getInstance();
<<<<<<< HEAD
        $this->start = $this->feather->start;
        $this->config = $this->feather->config;
        $this->user = $this->feather->user;
        $this->request = $this->feather->request;


=======
>>>>>>> development
        $this->model = new \model\admin\maintenance();
        load_textdomain('featherbb', $this->feather->forum_env['FEATHER_ROOT'].'lang/'.$this->feather->user->language.'/admin/maintenance.mo');
    }

    public function display()
    {
        $action = '';
        if ($this->feather->request->post('action')) {
            $action = $this->feather->request->post('action');
        } elseif ($this->feather->request->get('action')) {
            $action = $this->feather->request->get('action');
        }

        if ($action == 'rebuild') {
            $this->model->rebuild();

            $this->feather->view2->setPageInfo(array(
                    'page_title'    =>    array(feather_escape($this->feather->forum_settings['o_board_title']), __('Rebuilding search index')),
                    'query_str' => $this->model->get_query_str()
                )
            )->addTemplate('admin/maintenance/rebuild.php')->display();
        }

        if ($action == 'prune') {
<<<<<<< HEAD
            $prune_from = feather_trim($this->request->post('prune_from'));
            $prune_sticky = intval($this->request->post('prune_sticky'));

            generate_admin_menu('maintenance');

            if ($this->request->post('prune_comply')) {
=======
            $prune_from = feather_trim($this->feather->request->post('prune_from'));
            $prune_sticky = intval($this->feather->request->post('prune_sticky'));

            \FeatherBB\AdminUtils::generateAdminMenu('maintenance');

            if ($this->feather->request->post('prune_comply')) {
>>>>>>> development
                $this->model->prune_comply($prune_from, $prune_sticky);
            }

            $this->feather->view2->setPageInfo(array(
<<<<<<< HEAD
                    'title' => array(feather_escape($this->config['o_board_title']), __('Admin'), __('Prune')),
=======
                    'title' => array(feather_escape($this->feather->forum_settings['o_board_title']), __('Admin'), __('Prune')),
>>>>>>> development
                    'active_page' => 'admin',
                    'admin_console' => true,
                    'prune_sticky'    =>    $prune_sticky,
                    'prune_from'    =>    $prune_from,
                    'prune' => $this->model->get_info_prune($prune_sticky, $prune_from),
                )
            )->addTemplate('admin/maintenance/prune.php')->display();
        }

<<<<<<< HEAD
        generate_admin_menu('maintenance');

        $this->feather->view2->setPageInfo(array(
                'title' => array(feather_escape($this->config['o_board_title']), __('Admin'), __('Maintenance')),
=======
        \FeatherBB\AdminUtils::generateAdminMenu('maintenance');

        $this->feather->view2->setPageInfo(array(
                'title' => array(feather_escape($this->feather->forum_settings['o_board_title']), __('Admin'), __('Maintenance')),
>>>>>>> development
                'active_page' => 'admin',
                'admin_console' => true,
                'first_id' => $this->model->get_first_id(),
                'categories' => $this->model->get_categories(),
            )
        )->addTemplate('admin/maintenance/admin_maintenance.php')->display();
    }
}
