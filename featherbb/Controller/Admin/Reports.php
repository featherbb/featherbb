<?php

/**
 * Copyright (C) 2015-2017 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace FeatherBB\Controller\Admin;

use FeatherBB\Core\AdminUtils;
use FeatherBB\Core\Interfaces\ForumSettings;
use FeatherBB\Core\Interfaces\Hooks;
use FeatherBB\Core\Interfaces\Input;
use FeatherBB\Core\Interfaces\Lang;
use FeatherBB\Core\Interfaces\Request;
use FeatherBB\Core\Interfaces\Router;
use FeatherBB\Core\Interfaces\View;
use FeatherBB\Core\Utils;

class Reports
{
    public function __construct()
    {
        $this->model = new \FeatherBB\Model\Admin\Reports();
        Lang::load('admin/reports');
    }

    public function display($req, $res, $args)
    {
        Container::get('hooks')->fire('controller.admin.reports.display');

        // Zap a report
        if (Request::isPost()) {
            $zapId = intval(key(Input::post('zap_id')));
            $this->model->zap($zapId);
            return Router::redirect(Router::pathFor('adminReports'), __('Report zapped redirect'));
        }

        AdminUtils::generateAdminMenu('reports');

        return View::setPageInfo([
                'title' => [Utils::escape(ForumSettings::get('o_board_title')), __('Admin'), __('Reports')],
                'active_page' => 'admin',
                'admin_console' => true,
                'report_data'   =>  $this->model->reports(),
                'report_zapped_data'   =>  $this->model->zappedReports(),
            ]
        )->addTemplate('@forum/admin/reports')->display();
    }
}
