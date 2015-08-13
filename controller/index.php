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
        load_textdomain('featherbb', FEATHER_ROOT.'lang/'.$this->user->language.'/index.mo');
    }
    
    public function __autoload($class_name)
    {
        require FEATHER_ROOT . $class_name . '.php';
    }
    
    public function display()
    {
        if ($this->user->g_read_board == '0') {
            message(__('No view'), '403');
        }

        $page_title = array(feather_escape($this->config['o_board_title']));
        define('FEATHER_ALLOW_INDEX', 1);

        define('FEATHER_ACTIVE_PAGE', 'index');

        $this->header->setTitle($page_title)->display();

        $this->feather->render('index.php', array(
                            'index_data' => $this->model->print_categories_forums(),
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
