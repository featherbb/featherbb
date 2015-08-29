<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace controller;

class help
{
    public function __construct()
    {
        $this->feather = \Slim\Slim::getInstance();
        $this->start = $this->feather->start;
        $this->config = $this->feather->config;
        $this->user = $this->feather->user;
        $this->request = $this->feather->request;
        load_textdomain('featherbb', FEATHER_ROOT.'lang/'.$this->user->language.'/help.mo');
    }

    public function __autoload($class_name)
    {
        require FEATHER_ROOT . $class_name . '.php';
    }

    public function display()
    {
        if ($this->user->g_read_board == '0') {
            throw new \FeatherBB\Error(__('No view'), 403);
        }

        $this->feather->view2->setPageInfo(array(
            'title' => array($this->feather->utils->escape($this->config['o_board_title']), __('Help')),
            'active_page' => 'help',
        ))->addTemplate('help.php')->display();
    }
}
