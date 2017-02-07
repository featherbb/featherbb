<?php

/**
 * Copyright (C) 2015-2017 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace FeatherBB\Controller\Admin;

use FeatherBB\Core\AdminUtils;
use FeatherBB\Core\Utils;
use FeatherBB\Core\Error;

class Options
{
    public function __construct()
    {
        $this->model = new \FeatherBB\Model\Admin\Options();
        Lang::load('admin/options');
        if (!User::isAdmin()) {
            throw new Error(__('No permission'), '403');
        }
    }

    public function display($req, $res, $args)
    {
        Container::get('hooks')->fire('controller.admin.options.display');

        if (Request::isPost()) {
            return $this->model->update_options();
        }

        AdminUtils::generateAdminMenu('admin options');

        View::setPageInfo([
                'title' => [Utils::escape(ForumSettings::get('o_board_title')), __('Admin'), __('Admin options')],
                'active_page' => 'admin',
                'admin_console' => true,
                'languages' => $this->model->get_langs(),
                'styles' => $this->model->get_styles(),
                'times' => $this->model->get_times(),
            ]
        )->addTemplate('admin/options.php')->display();
    }
}
