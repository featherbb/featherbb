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
        translate('admin/index');
        if (!Container::get('perms')->can(User::get(), 'mod.view')) {
            throw new Error(__('No permission'), '403');
        }
    }

    public function display($req, $res, $args)
    {
        if (!isset($args['action'])) {
            $args['action'] = null;
        }

        Container::get('hooks')->fire('controller.admin.index.display');

        // Check for upgrade
        if ($args['action'] == 'check_upgrade') {
            if (!ini_get('allow_url_fopen')) {
                throw new Error(__('fopen disabled message'), 500);
            }

            $latest_version = trim(@file_get_contents('http://featherbb.org/latest_version.html'));
            if (empty($latest_version)) {
                throw new Error(__('Upgrade check failed message'), 500);
            }

            if (version_compare(ForumSettings::get('o_cur_version'), $latest_version, '>=')) {
                return Router::redirect(Router::pathFor('adminIndex'), __('Running latest version message'));
            } else {
                return Router::redirect(Router::pathFor('adminIndex'), sprintf(__('New version available message'), '<a href="http://featherbb.org/">FeatherBB.org</a>'));
            }
        }

        AdminUtils::generateAdminMenu('index');

        return View::setPageInfo(array(
                'title' => array(Utils::escape(ForumSettings::get('o_board_title')), __('Admin'), __('Index')),
                'active_page' => 'admin',
                'admin_console' => true
            )
        )->addTemplate('admin/index.php')->display();
    }
}
