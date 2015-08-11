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
    }

    public function __autoload($class_name)
    {
        require FEATHER_ROOT . $class_name . '.php';
    }
    
    public function display()
    {
        global $lang_common;

        if (!$this->user->is_guest) {
            header('Location: '.get_base_url());
            exit;
        }

        // Load the login.php language file
        require FEATHER_ROOT.'lang/'.$this->user->language.'/login.php';

        // TODO?: Try to determine if the data in HTTP_REFERER is valid (if not, we redirect to index.php after login)
        $redirect_url = $this->model->get_redirect_url($_SERVER);

        $page_title = array(feather_escape($this->config['o_board_title']), $lang_common['Login']);
        $required_fields = array('req_username' => $lang_common['Username'], 'req_password' => $lang_common['Password']);
        $focus_element = array('login', 'req_username');

        define('FEATHER_ACTIVE_PAGE', 'login');

        $this->header->setTitle($page_title)->setFocusElement($focus_element)->setRequiredFields($required_fields)->display();

        $this->feather->render('login/form.php', array(
                            'lang_common' => $lang_common,
                            'lang_login' => $lang_login,
                            'redirect_url'    =>    $redirect_url,
                            )
                    );

        $this->footer->display();
    }

    public function logmein()
    {
        global $lang_common, $lang_login;

        define('FEATHER_QUIET_VISIT', 1);

        if (!$this->user->is_guest) {
            header('Location: '.get_base_url());
            exit;
        }

        // Load the login.php language file
        require FEATHER_ROOT.'lang/'.$this->user->language.'/login.php';

        $this->model->login();
    }

    public function logmeout($id, $token)
    {
        global $lang_common;

        define('FEATHER_QUIET_VISIT', 1);

        // Load the login.php language file
        require FEATHER_ROOT.'lang/'.$this->user->language.'/login.php';

        $this->model->logout($id, $token);
    }

    public function forget()
    {
        global $lang_common, $lang_login;

        define('FEATHER_QUIET_VISIT', 1);

        if (!$this->user->is_guest) {
            header('Location: '.get_base_url());
            exit;
        }

        // Load the login.php language file
        require FEATHER_ROOT.'lang/'.$this->user->language.'/login.php';

        $errors = $this->model->password_forgotten();

        $page_title = array(feather_escape($this->config['o_board_title']), $lang_login['Request pass']);
        $required_fields = array('req_email' => $lang_common['Email']);
        $focus_element = array('request_pass', 'req_email');

        define('FEATHER_ACTIVE_PAGE', 'login');

        $this->header->setTitle($page_title)->setFocusElement($focus_element)->setRequiredFields($required_fields)->display();

        $this->feather->render('login/password_forgotten.php', array(
                            'errors'    =>    $errors,
                            'lang_login'    =>    $lang_login,
                            'lang_common'    =>    $lang_common,
                            )
                    );

        $this->footer->display();
    }
}
