<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace controller;

class index
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
        $this->model = new \model\index();
    }
    
    public function __autoload($class_name)
    {
        require FEATHER_ROOT . $class_name . '.php';
    }
    
    public function display()
    {
        global $lang_common;

        if ($this->user->g_read_board == '0') {
            message($lang_common['No view'], '403');
        }

        // Load the index.php language file
        require FEATHER_ROOT.'lang/'.$this->user->language.'/index.php';

        $page_title = array(feather_escape($this->config['o_board_title']));
        define('FEATHER_ALLOW_INDEX', 1);

        define('FEATHER_ACTIVE_PAGE', 'index');

        $this->header->setTitle($page_title)->display();

        $this->feather->render('index.php', array(
                            'index_data' => $this->model->print_categories_forums(),
                            'lang_common' => $lang_common,
                            'lang_index' => $lang_index,
                            'stats' => $this->model->collect_stats(),
                            'feather_config' => $this->config,
                            'online'    =>    $this->model->fetch_users_online(),
                            'forum_actions'        =>    $this->model->get_forum_actions(),
                            'cur_cat'   => 0,
                            )
                    );
        
        $this->footer->display();
    }
}
