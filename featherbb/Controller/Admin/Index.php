<?php

/**
 * Copyright (C) 2015-2016 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace FeatherBB\Controller\Admin;

use FeatherBB\Core\AdminUtils;
use FeatherBB\Core\Error;
use FeatherBB\Core\Url;
use FeatherBB\Core\Utils;

class Index
{
    public function __construct()
    {
        $this->feather = \Slim\Slim::getInstance();
        $this->start = $this->feather->start;
        $this->config = $this->feather->config;
        $this->user = Container::get('user');
        $this->request = $this->feather->request;
        load_textdomain('featherbb', $this->feather->forum_env['FEATHER_ROOT'].'featherbb/lang/'.$this->user->language.'/admin/index.mo');
    }

    public function display($action = null)
    {
        Container::get('hooks')->fire('controller.admin.index.display');

        // Check for upgrade
        if ($action == 'check_upgrade') {
            if (!ini_get('allow_url_fopen')) {
                throw new Error(__('fopen disabled message'), 500);
            }

            $latest_version = trim(@file_get_contents('http://featherbb.org/latest_version.html'));
            if (empty($latest_version)) {
                throw new Error(__('Upgrade check failed message'), 500);
            }

            if (version_compare($this->config['o_cur_version'], $latest_version, '>=')) {
                Router::redirect(Router::pathFor('adminIndex'), __('Running latest version message'));
            } else {
                Router::redirect(Router::pathFor('adminIndex'), sprintf(__('New version available message'), '<a href="http://featherbb.org/">FeatherBB.org</a>'));
            }
        }

        AdminUtils::generateAdminMenu('index');

        $this->feather->template->setPageInfo(array(
                'title' => array(Utils::escape($this->config['o_board_title']), __('Admin'), __('Index')),
                'active_page' => 'admin',
                'admin_console' => true
            )
        )->addTemplate('admin/index.php')->display();
    }
}
