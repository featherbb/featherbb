<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace FeatherBB\Controller;

use FeatherBB\Core\Utils;
use FeatherBB\Core\Url;

class Login
{
    public function __construct()
    {
        $this->feather = \Slim\Slim::getInstance();
        $this->start = $this->feather->start;
        $this->config = $this->feather->config;
        $this->user = $this->feather->user;
        $this->request = $this->feather->request;


        $this->model = new \FeatherBB\Model\Login();
        load_textdomain('featherbb', $this->feather->forum_env['FEATHER_ROOT'].'featherbb/lang/'.$this->user->language.'/login.mo');
    }

    public function display()
    {
        if (!$this->user->is_guest) {
            $this->feather->url->redirect(Url::get('/'), 'Already logged in');
        }

        $this->feather->template->setPageInfo(array(
                            'redirect_url'    =>    $redirect_url,
                            'active_page' => 'login',
                            'title' => array(Utils::escape($this->config['o_board_title']), __('Login')),
                            'required_fields' => array('req_username' => __('Username'), 'req_password' => __('Password')),
                            'focus_element' => array('login', 'req_username'),
                            )
                    )->addTemplate('login/form.php')->display();
    }

    public function logmein()
    {
        define('FEATHER_QUIET_VISIT', 1);

        if (!$this->user->is_guest) {
            header('Location: '.Url::base());
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
            header('Location: '.Url::base());
            exit;
        }

        $this->feather->template->setPageInfo(array(
                'errors'    =>    $this->model->password_forgotten(),
                'active_page' => 'login',
                'title' => array(Utils::escape($this->config['o_board_title']), __('Request pass')),
                'required_fields' => array('req_email' => __('Email')),
                'focus_element' => array('request_pass', 'req_email'),
            )
        )->addTemplate('login/password_forgotten.php')->display();
    }
}
