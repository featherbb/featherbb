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

class Options
{
    public function __construct()
    {
        $this->feather = \Slim\Slim::getInstance();
        $this->start = $this->feather->start;
        $this->config = $this->feather->config;
        $this->user = $this->feather->user;
        $this->request = $this->feather->request;
        $this->model = new \FeatherBB\Model\Admin\Options();
        load_textdomain('featherbb', $this->feather->forum_env['FEATHER_ROOT'].'featherbb/lang/'.$this->user->language.'/admin/options.mo');
    }

    public function display()
    {
        $this->feather->hooks->fire('controller.admin.options.display');

        if ($this->feather->request->isPost()) {
            $this->model->update_options();
        }

        AdminUtils::generateAdminMenu('options');

        $this->feather->template->setPageInfo(array(
                'title' => array(Utils::escape($this->config['o_board_title']), __('Admin'), __('Options')),
                'active_page' => 'admin',
                'admin_console' => true,
                'languages' => $this->model->get_langs(),
                'styles' => $this->model->get_styles(),
                'times' => $this->model->get_times(),
            )
        )->addTemplate('admin/options.php')->display();
    }
}
