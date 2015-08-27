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
        $this->start = $this->feather->start;
        $this->config = $this->feather->config;
        $this->user = $this->feather->user;
        $this->request = $this->feather->request;


        $this->model = new \model\admin\maintenance();
        load_textdomain('featherbb', FEATHER_ROOT.'lang/'.$this->user->language.'/admin/maintenance.mo');
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

        $action = '';
        if ($this->request->post('action')) {
            $action = $this->request->post('action');
        } elseif ($this->request->get('action')) {
            $action = $this->request->get('action');
        }

        if ($action == 'rebuild') {
            $this->model->rebuild();

            $page_title = array(feather_escape($this->config['o_board_title']), __('Rebuilding search index'));

            $this->feather->render('admin/maintenance/rebuild.php', array(
                    'page_title'    =>    $page_title,
                )
            );

            $query_str = $this->model->get_query_str();

            exit('<script type="text/javascript">window.location="'.get_link('admin/maintenance/').$query_str.'"</script><hr /><p>'.sprintf(__('Javascript redirect failed'), '<a href="'.get_link('admin/maintenance/').$query_str.'">'.__('Click here').'</a>').'</p>');
        }

        if ($action == 'prune') {
            $prune_from = feather_trim($this->request->post('prune_from'));
            $prune_sticky = intval($this->request->post('prune_sticky'));

            generate_admin_menu('maintenance');

            if ($this->request->post('prune_comply')) {
                $this->model->prune_comply($prune_from, $prune_sticky);
            }

            $this->feather->view2->setPageInfo(array(
                    'title' => array(feather_escape($this->config['o_board_title']), __('Admin'), __('Prune')),
                    'active_page' => 'admin',
                    'admin_console' => true,
                    'prune_sticky'    =>    $prune_sticky,
                    'prune_from'    =>    $prune_from,
                    'prune' => $this->model->get_info_prune($prune_sticky, $prune_from),
                )
            )->addTemplate('admin/maintenance/prune.php')->display();
        }

        generate_admin_menu('maintenance');

        $this->feather->view2->setPageInfo(array(
                'title' => array(feather_escape($this->config['o_board_title']), __('Admin'), __('Maintenance')),
                'active_page' => 'admin',
                'admin_console' => true,
                'first_id' => $this->model->get_first_id(),
                'categories' => $this->model->get_categories(),
            )
        )->addTemplate('admin/maintenance/admin_maintenance.php')->display();
    }
}
