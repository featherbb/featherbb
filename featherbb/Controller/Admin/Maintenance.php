<?php

/**
 * Copyright (C) 2015-2019 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace FeatherBB\Controller\Admin;

use FeatherBB\Core\AdminUtils;
use FeatherBB\Core\Error;
use FeatherBB\Core\Interfaces\ForumSettings;
use FeatherBB\Core\Interfaces\Hooks;
use FeatherBB\Core\Interfaces\Input;
use FeatherBB\Core\Interfaces\Lang;
use FeatherBB\Core\Interfaces\Router;
use FeatherBB\Core\Interfaces\User;
use FeatherBB\Core\Interfaces\View;
use FeatherBB\Core\Utils;

class Maintenance
{
    public function __construct()
    {
        $this->model = new \FeatherBB\Model\Admin\Maintenance();
        Lang::load('admin/maintenance');
        if (!User::isAdmin()) {
            throw new Error(__('No permission'), '403');
        }
    }

    public function display($req, $res, $args)
    {
        Hooks::fire('controller.admin.maintenance.display');

        $action = '';
        if (Input::post('action')) {
            $action = Input::post('action');
        } elseif (Input::query('action')) {
            $action = Input::query('action');
        }

        AdminUtils::generateAdminMenu('maintenance');

        if ($action == 'rebuild') {
            $this->model->rebuild();

            $queryStr = $this->model->getQueryString();

            View::setPageInfo([
                    'page_title'    =>    [Utils::escape(ForumSettings::get('o_board_title')), __('Rebuilding search index')],
                    'query_str' => $queryStr['str'],
                    'id'        =>  $queryStr['id'],
                    'active_page' => 'admin',
                    'admin_console' => true,
                    'link'      => '<a href="'. Router::pathFor('adminMaintenance').$queryStr['str'].'">'.__('Click here').'</a>'
                ]
            )->addTemplate('@forum/admin/maintenance/rebuild')->display();
        }

        else if ($action == 'prune') {
            $pruneFrom = Utils::trim(Input::post('prune_from'));
            $pruneSticky = intval(Input::post('prune_sticky'));

            if (Input::post('prune_comply')) {
                $this->model->pruneComply($pruneFrom, $pruneSticky);
            }

            View::setPageInfo([
                    'title' => [Utils::escape(ForumSettings::get('o_board_title')), __('Admin'), __('Prune')],
                    'active_page' => 'admin',
                    'admin_console' => true,
                    'prune_sticky'    =>    $pruneSticky,
                    'prune_from'    =>    $pruneFrom,
                    'prune' => $this->model->getInfoPrune($pruneSticky, $pruneFrom),
                ]
            )->addTemplate('@forum/admin/maintenance/prune')->display();
        }

        else {
            View::setPageInfo([
                    'title' => [Utils::escape(ForumSettings::get('o_board_title')), __('Admin'), __('Maintenance')],
                    'active_page' => 'admin',
                    'admin_console' => true,
                    'first_id' => $this->model->getFirstId(),
                    'categories' => $this->model->getCategories(),
                ]
            )->addTemplate('@forum/admin/maintenance/admin_maintenance')->display();
        }
    }
}
