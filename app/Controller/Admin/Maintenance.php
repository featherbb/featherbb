<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace App\Controller\Admin;

class Maintenance
{
    public function __construct()
    {
        $this->feather = \Slim\Slim::getInstance();
        $this->model = new \App\Model\Admin\maintenance();
        load_textdomain('featherbb', $this->feather->forum_env['FEATHER_ROOT'].'app/lang/'.$this->feather->user->language.'/admin/maintenance.mo');
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
                    'page_title'    =>    array($this->feather->utils->escape($this->feather->forum_settings['o_board_title']), __('Rebuilding search index')),
                    'query_str' => $this->model->get_query_str()
                )
            )->addTemplate('admin/maintenance/rebuild.php')->display();
        }

        if ($action == 'prune') {
            $prune_from = $this->feather->utils->trim($this->feather->request->post('prune_from'));
            $prune_sticky = intval($this->feather->request->post('prune_sticky'));

            \FeatherBB\AdminUtils::generateAdminMenu('maintenance');

            if ($this->feather->request->post('prune_comply')) {
                $this->model->prune_comply($prune_from, $prune_sticky);
            }

            $this->feather->view2->setPageInfo(array(
                    'title' => array($this->feather->utils->escape($this->feather->forum_settings['o_board_title']), __('Admin'), __('Prune')),
                    'active_page' => 'admin',
                    'admin_console' => true,
                    'prune_sticky'    =>    $prune_sticky,
                    'prune_from'    =>    $prune_from,
                    'prune' => $this->model->get_info_prune($prune_sticky, $prune_from),
                )
            )->addTemplate('admin/maintenance/prune.php')->display();
        }

        \FeatherBB\AdminUtils::generateAdminMenu('maintenance');

        $this->feather->view2->setPageInfo(array(
                'title' => array($this->feather->utils->escape($this->feather->forum_settings['o_board_title']), __('Admin'), __('Maintenance')),
                'active_page' => 'admin',
                'admin_console' => true,
                'first_id' => $this->model->get_first_id(),
                'categories' => $this->model->get_categories(),
            )
        )->addTemplate('admin/maintenance/admin_maintenance.php')->display();
    }
}
