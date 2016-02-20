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

class Permissions
{
    public function __construct()
    {
        $this->model = new \FeatherBB\Model\Admin\Permissions();
        load_textdomain('featherbb', ForumEnv::get('FEATHER_ROOT').'featherbb/lang/'.Container::get('user')->language.'/admin/permissions.mo');
    }

    public function display($req, $res, $args)
    {
        Container::get('hooks')->fire('controller.admin.permissions.display');

        // Update permissions
        if (Request::isPost()) {
            return $this->model->update_permissions();
        }

        AdminUtils::generateAdminMenu('permissions');

        return View::setPageInfo(array(
                'title' => array(Utils::escape(ForumSettings::get('o_board_title')), __('Admin'), __('Permissions')),
                'active_page' => 'admin',
                'admin_console' => true,
            )
        )->addTemplate('admin/permissions.php')->display();
    }
}
