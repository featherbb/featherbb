<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace controller;

class login
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
        $this->model = new \model\login();
        load_textdomain('featherbb', FEATHER_ROOT.'lang/'.$this->user->language.'/login.mo');
    }

    public function __autoload($class_name)
    {
        require FEATHER_ROOT . $class_name . '.php';
    }
    
    public function display()
    {
        if (!$this->user->is_guest) {
            header('Location: '.get_base_url());
            exit;
        }

        // TODO?: Try to determine if the data in HTTP_REFERER is valid (if not, we redirect to index.php after login)
        $redirect_url = $this->model->get_redirect_url($_SERVER);

        $page_title = array(feather_escape($this->config['o_board_title']), __('Login'));
        $required_fields = array('req_username' => __('Username'), 'req_password' => __('Password'));
        $focus_element = array('login', 'req_username');

        define('FEATHER_ACTIVE_PAGE', 'login');

        $this->header->setTitle($page_title)->setFocusElement($focus_element)->setRequiredFields($required_fields)->display();

        $this->feather->render('login/form.php', array(
                            'redirect_url'    =>    $redirect_url,
                            )
                    );

        $this->footer->display();
    }

    public function logmein()
    {
        define('FEATHER_QUIET_VISIT', 1);

        if (!$this->user->is_guest) {
            header('Location: '.get_base_url());
            exit;
        }

        $this->model->login();
    }

    public function logmeout($id, $token)
    {
        define('FEATHER_QUIET_VISIT', 1);

        $this->model->logout($id, $token);
    }

    public function forget()
    {
        define('FEATHER_QUIET_VISIT', 1);

        if (!$this->user->is_guest) {
            header('Location: '.get_base_url());
            exit;
        }

        $errors = $this->model->password_forgotten();

        $page_title = array(feather_escape($this->config['o_board_title']), __('Request pass'));
        $required_fields = array('req_email' => __('Email'));
        $focus_element = array('request_pass', 'req_email');

        define('FEATHER_ACTIVE_PAGE', 'login');

        $this->header->setTitle($page_title)->setFocusElement($focus_element)->setRequiredFields($required_fields)->display();

        $this->feather->render('login/password_forgotten.php', array(
                            'errors'    =>    $errors,
                            )
                    );

        $this->footer->display();
    }
}
