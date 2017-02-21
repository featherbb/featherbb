<?php

/**
 * Copyright (C) 2015-2017 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace FeatherBB\Controller\Admin;

use FeatherBB\Core\AdminUtils;
use FeatherBB\Core\Error;
use FeatherBB\Core\Utils;

class Statistics
{
    public function __construct()
    {
        $this->model = new \FeatherBB\Model\Admin\Statistics();
        Lang::load('admin/index');
    }

    public function display($req, $res, $args)
    {
        Container::get('hooks')->fire('controller.admin.statistics.display');

        AdminUtils::generateAdminMenu('index');

        $total = $this->model->totalSize();

        return View::setPageInfo([
                'title' => [Utils::escape(ForumSettings::get('o_board_title')), __('Admin'), __('Server statistics')],
                'active_page' => 'admin',
                'admin_console' => true,
                'server_load'    =>    $this->model->serverLoad(),
                'num_online'    =>    $this->model->numOnline(),
                'total_size'    =>    $total['size'],
                'total_records'    =>    $total['records'],
                'php_accelerator'    =>    $this->model->phpAccelerator(),
                'php_os'        =>      PHP_OS,
                'php_version'        =>      phpversion(),
            ]
        )->addTemplate('@forum/admin/statistics')->display();
    }


    public function phpinfo($req, $res, $args)
    {
        Container::get('hooks')->fire('controller.admin.statistics.phpinfo');

        // Show phpinfo() output
        // Is phpinfo() a disabled function?
        if (strpos(strtolower((string) ini_get('disable_functions')), 'phpinfo') !== false) {
            throw new Error(__('PHPinfo disabled message'), 404);
        }

        phpinfo();
    }
}
