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

class Censoring
{
    public function __construct()
    {
        $this->model = new \FeatherBB\Model\Admin\Censoring();
        load_textdomain('featherbb', Config::get('forum_env')['FEATHER_ROOT'].'featherbb/lang/'.Container::get('user')->language.'/admin/censoring.mo');
    }

    public function display($req, $res, $args)
    {
        Container::get('hooks')->fire('controller.admin.censoring.display');

        // Add a censor word
        if (Input::post('add_word')) {
            $this->model->add_word();
        }

        // Update a censor word
        elseif (Input::post('update')) {
            $this->model->update_word();
        }

        // Remove a censor word
        elseif (Input::post('remove')) {
            $this->model->remove_word();
        }

        AdminUtils::generateAdminMenu('censoring');

        View::setPageInfo(array(
                'title'    =>    array(Utils::escape(ForumSettings::get('o_board_title')), __('Admin'), __('Censoring')),
                'focus_element'    =>    array('censoring', 'new_search_for'),
                'active_page'    =>    'admin',
                'admin_console'    =>    true,
                'word_data'    =>    $this->model->get_words(),
            )
        )->addTemplate('admin/censoring.php')->display();
    }
}
