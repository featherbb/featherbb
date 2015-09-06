<?php

/**
 * Copyright (C) 2015 FeatherBB
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
        $this->feather = \Slim\Slim::getInstance();
        $this->start = $this->feather->start;
        $this->config = $this->feather->config;
        $this->user = $this->feather->user;
        $this->request = $this->feather->request;
        $this->model = new \FeatherBB\Model\Admin\Censoring();
        load_textdomain('featherbb', $this->feather->forum_env['FEATHER_ROOT'].'featherbb/lang/'.$this->user->language.'/admin/censoring.mo');
    }

    public function __autoload($class_name)
    {
        require $this->feather->forum_env['FEATHER_ROOT'] . $class_name . '.php';
    }

    public function display()
    {
        // Add a censor word
        if ($this->request->post('add_word')) {
            $this->model->add_word();
        }

        // Update a censor word
        elseif ($this->request->post('update')) {
            $this->model->update_word();
        }

        // Remove a censor word
        elseif ($this->request->post('remove')) {
            $this->model->remove_word();
        }

        AdminUtils::generateAdminMenu('censoring');

        $this->feather->template->setPageInfo(array(
                'title'    =>    array(Utils::escape($this->config['o_board_title']), __('Admin'), __('Censoring')),
                'focus_element'    =>    array('censoring', 'new_search_for'),
                'active_page'    =>    'admin',
                'admin_console'    =>    true,
                'word_data'    =>    $this->model->get_words(),
            )
        )->addTemplate('admin/censoring.php')->display();
    }
}
