<?php

/**
 * Copyright (C) 2015-2016 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace FeatherBB\Controller\Admin;

use FeatherBB\Core\AdminUtils;
use FeatherBB\Core\Utils;

class Maintenance
{
    public function __construct()
    {
        $this->feather = \Slim\Slim::getInstance();
        $this->model = new \FeatherBB\Model\Admin\Maintenance();
        load_textdomain('featherbb', Container::get('forum_env')['FEATHER_ROOT'].'featherbb/lang/'.Container::get('user')->language.'/admin/maintenance.mo');
    }

    public function display()
    {
        Container::get('hooks')->fire('controller.admin.maintenance.display');

        $action = '';
        if ($this->feather->request->post('action')) {
            $action = $this->feather->request->post('action');
        } elseif ($this->feather->request->get('action')) {
            $action = $this->feather->request->get('action');
        }

        if ($action == 'rebuild') {
            $this->model->rebuild();

            $this->feather->template->setPageInfo(array(
                    'page_title'    =>    array(Utils::escape($this->feather->forum_settings['o_board_title']), __('Rebuilding search index')),
                    'query_str' => $this->model->get_query_str()
                )
            )->addTemplate('admin/maintenance/rebuild.php')->display();
        }

        if ($action == 'prune') {
            $prune_from = Utils::trim($this->feather->request->post('prune_from'));
            $prune_sticky = intval($this->feather->request->post('prune_sticky'));

            AdminUtils::generateAdminMenu('maintenance');

            if ($this->feather->request->post('prune_comply')) {
                $this->model->prune_comply($prune_from, $prune_sticky);
            }

            $this->feather->template->setPageInfo(array(
                    'title' => array(Utils::escape($this->feather->forum_settings['o_board_title']), __('Admin'), __('Prune')),
                    'active_page' => 'admin',
                    'admin_console' => true,
                    'prune_sticky'    =>    $prune_sticky,
                    'prune_from'    =>    $prune_from,
                    'prune' => $this->model->get_info_prune($prune_sticky, $prune_from),
                )
            )->addTemplate('admin/maintenance/prune.php')->display();
        }

        AdminUtils::generateAdminMenu('maintenance');

        $this->feather->template->setPageInfo(array(
                'title' => array(Utils::escape($this->feather->forum_settings['o_board_title']), __('Admin'), __('Maintenance')),
                'active_page' => 'admin',
                'admin_console' => true,
                'first_id' => $this->model->get_first_id(),
                'categories' => $this->model->get_categories(),
            )
        )->addTemplate('admin/maintenance/admin_maintenance.php')->display();
    }
}
