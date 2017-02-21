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
use FeatherBB\Core\Interfaces\ForumSettings;
use FeatherBB\Core\Interfaces\Hooks;
use FeatherBB\Core\Interfaces\Input;
use FeatherBB\Core\Interfaces\Lang;
use FeatherBB\Core\Interfaces\User;
use FeatherBB\Core\Interfaces\View;
use FeatherBB\Core\Utils;

class Censoring
{
    public function __construct()
    {
        $this->model = new \FeatherBB\Model\Admin\Censoring();
        Lang::load('admin/censoring');
        if (!User::isAdmin()) {
            throw new Error(__('No permission'), '403');
        }
    }

    public function display($req, $res, $args)
    {
        Hooks::fire('controller.admin.censoring.display');

        // Add a censor word
        if (Input::post('add_word')) {
            return $this->model->addWord();
        }

        // Update a censor word
        elseif (Input::post('update')) {
            return $this->model->updateWord();
        }

        // Remove a censor word
        elseif (Input::post('remove')) {
            return $this->model->removeWord();
        }

        AdminUtils::generateAdminMenu('censoring');

        return View::setPageInfo([
                'title'    =>    [Utils::escape(ForumSettings::get('o_board_title')), __('Admin'), __('Censoring')],
                'active_page'    =>    'admin',
                'admin_console'    =>    true,
                'word_data'    =>    $this->model->getWords(),
            ]
        )->addTemplate('@forum/admin/censoring')->display();
    }
}
