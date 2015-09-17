<?php

/**
 * Copyright (C) 2015 FeatherBB
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
        $this->feather = \Slim\Slim::getInstance();
        $this->start = $this->feather->start;
        $this->config = $this->feather->config;
        $this->user = $this->feather->user;
        $this->request = $this->feather->request;
        $this->model = new \FeatherBB\Model\Admin\Statistics();
        load_textdomain('featherbb', $this->feather->forum_env['FEATHER_ROOT'].'featherbb/lang/'.$this->user->language.'/admin/index.mo');
    }

    public function display()
    {
        $this->feather->hooks->fire('controller.admin.statistics.display');

        AdminUtils::generateAdminMenu('index');

        $total = $this->model->get_total_size();

        $this->feather->template->setPageInfo(array(
                'title' => array(Utils::escape($this->config['o_board_title']), __('Admin'), __('Server statistics')),
                'active_page' => 'admin',
                'admin_console' => true,
                'server_load'    =>    $this->model->get_server_load(),
                'num_online'    =>    $this->model->get_num_online(),
                'total_size'    =>    $total['size'],
                'total_records'    =>    $total['records'],
                'php_accelerator'    =>    $this->model->get_php_accelerator(),
            )
        )->addTemplate('admin/statistics.php')->display();
    }


    public function phpinfo()
    {
        $this->feather->hooks->fire('controller.admin.statistics.phpinfo');

        // Show phpinfo() output
        // Is phpinfo() a disabled function?
        if (strpos(strtolower((string) ini_get('disable_functions')), 'phpinfo') !== false) {
            throw new Error(__('PHPinfo disabled message'), 404);
        }

        phpinfo();
        $this->feather->stop();
    }
}
