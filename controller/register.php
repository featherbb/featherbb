<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace controller;

class register
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
        $this->model = new \model\register();
    }

    public function __autoload($class_name)
    {
        require FEATHER_ROOT . $class_name . '.php';
    }
    
    public function display()
    {
        global $lang_common, $lang_antispam_questions, $lang_antispam, $lang_register, $lang_prof_reg;

        if (!$this->user->is_guest) {
            header('Location: '.get_base_url());
            exit;
        }

        // Load the register.php language file
        require FEATHER_ROOT.'lang/'.$this->user->language.'/register.php';

        // Load the register.php/profile.php language file
        require FEATHER_ROOT.'lang/'.$this->user->language.'/prof_reg.php';

        // Antispam feature
        require FEATHER_ROOT.'lang/'.$this->user->language.'/antispam.php';
        $index_questions = rand(0, count($lang_antispam_questions)-1);

        // Display an error message if new registrations are disabled
        // If $_REQUEST['username'] or $_REQUEST['password'] are filled, we are facing a bot
        if ($this->config['o_regs_allow'] == '0' || $this->request->post('username') || $this->request->post('password')) {
            message($lang_register['No new regs']);
        }

        $page_title = array(feather_escape($this->config['o_board_title']), $lang_register['Register']);
        $required_fields = array('req_user' => $lang_common['Username'], 'req_password1' => $lang_common['Password'], 'req_password2' => $lang_prof_reg['Confirm pass'], 'req_email1' => $lang_common['Email'], 'req_email2' => $lang_common['Email'].' 2', 'captcha' => $lang_antispam['Robot title']);
        $focus_element = array('register', 'req_user');

        define('FEATHER_ACTIVE_PAGE', 'register');

        $user['timezone'] = isset($user['timezone']) ? $user['timezone'] : $this->config['o_default_timezone'];
        $user['dst'] = isset($user['dst']) ? $user['dst'] : $this->config['o_default_dst'];
        $user['email_setting'] = isset($user['email_setting']) ? $user['email_setting'] : $this->config['o_default_email_setting'];
        $user['errors'] = '';

        if ($this->feather->request()->isPost()) {
            $user = $this->model->check_for_errors();

            // Did everything go according to plan? Insert the user
            if (empty($user['errors'])) {
                $this->model->insert_user($user);
            }
        }

        $this->header->setTitle($page_title)->setFocusElement($focus_element)->setRequiredFields($required_fields)->display();

        $this->feather->render('register/form.php', array(
                            'errors' => $user['errors'],
                            'feather_config' => $this->config,
                            'lang_register' => $lang_register,
                            'lang_common' => $lang_common,
                            'lang_prof_reg' => $lang_prof_reg,
                            'lang_antispam' => $lang_antispam,
                            'lang_antispam_questions'    =>    $lang_antispam_questions,
                            'index_questions'    =>    $index_questions,
                            'feather'    =>    $this->feather,
                            'languages' => forum_list_langs(),
                            'question' => array_keys($lang_antispam_questions),
                            'qencoded' => md5(array_keys($lang_antispam_questions)[$index_questions]),
                            )
                    );

        $this->footer->display();
    }

    public function cancel()
    {
        redirect(get_base_url());
    }

    public function rules()
    { // TODO: fix $_GET w/ URL rewriting

        global $lang_common, $lang_login, $lang_register;

        // If we are logged in, we shouldn't be here
        if (!$this->user->is_guest) {
            header('Location: '.get_base_url());
            exit;
        }

        // Display an error message if new registrations are disabled
        if ($this->config['o_regs_allow'] == '0') {
            message($lang_register['No new regs']);
        }

        // Load the register.php language file
        require FEATHER_ROOT.'lang/'.$this->user->language.'/register.php';

        // Load the register.php/profile.php language file
        require FEATHER_ROOT.'lang/'.$this->user->language.'/prof_reg.php';

        if ($this->config['o_rules'] != '1') {
            redirect(get_link('register/agree/'));
        }

        $page_title = array(feather_escape($this->config['o_board_title']), $lang_register['Register'], $lang_register['Forum rules']);

        define('FEATHER_ACTIVE_PAGE', 'register');

        $this->header->setTitle($page_title)->display();

        $this->feather->render('register/rules.php', array(
                            'lang_register'    =>    $lang_register,
                            'feather_config'    =>    $this->config,
                            )
                    );

        $this->footer->display();
    }
}
