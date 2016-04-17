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
use FeatherBB\Core\Error;

class Censoring
{
    public function __construct()
    {
        $this->model = new \FeatherBB\Model\Admin\Censoring();
        translate('admin/censoring');
        if (!User::isAdmin()) {
            throw new Error(__('No permission'), '403');
        }
    }

    public function display($req, $res, $args)
    {
        Container::get('hooks')->fire('controller.admin.censoring.display');

        // Add a censor word
        if (Input::post('add_word')) {
            return $this->model->add_word();
        }

        // Update a censor word
        elseif (Input::post('update')) {
            return $this->model->update_word();
        }

        // Remove a censor word
        elseif (Input::post('remove')) {
            return $this->model->remove_word();
        }

        AdminUtils::generateAdminMenu('censoring');

        return View::setPageInfo(array(
                'title'    =>    array(Utils::escape(ForumSettings::get('o_board_title')), __('Admin'), __('Censoring')),
                'active_page'    =>    'admin',
                'admin_console'    =>    true,
                'word_data'    =>    $this->model->get_words(),
            )
        )->addTemplate('admin/censoring.php')->display();
    }
}
