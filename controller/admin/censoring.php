<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace controller\admin;

class censoring
{
    public function __construct()
    {
        $this->feather = \Slim\Slim::getInstance();
        $this->start = $this->feather->start;
        $this->config = $this->feather->config;
        $this->user = $this->feather->user;
        $this->request = $this->feather->request;
        $this->header = new \controller\header();
        $this->footer = new \controller\footer();
        $this->model = new \model\admin\censoring();
    }

    public function __autoload($class_name)
    {
        require FEATHER_ROOT . $class_name . '.php';
    }
    
    public function display()
    {
        global $lang_common, $lang_admin_common, $lang_admin_censoring;

        require FEATHER_ROOT.'include/common_admin.php';

        if ($this->user->g_id != FEATHER_ADMIN) {
            message($lang_common['No permission'], '403');
        }

        define('FEATHER_ADMIN_CONSOLE', 1);

        // Load the admin_options.php language file
        require FEATHER_ROOT.'lang/'.$admin_language.'/censoring.php';

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

        $page_title = array(feather_escape($this->config['o_board_title']), $lang_admin_common['Admin'], $lang_admin_common['Censoring']);
        $focus_element = array('censoring', 'new_search_for');

        define('FEATHER_ACTIVE_PAGE', 'admin');

        $this->header->setTitle($page_title)->setFocusElement($focus_element)->display();

        generate_admin_menu('censoring');

        $this->feather->render('admin/censoring.php', array(
                'lang_admin_censoring'    =>    $lang_admin_censoring,
                'lang_admin_common'    =>    $lang_admin_common,
                'feather_config'    =>    $this->config,
                'word_data'    =>    $this->model->get_words(),
            )
        );

        $this->footer->display();
    }
}
