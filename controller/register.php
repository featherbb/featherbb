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
    }
    
    public function display()
    {
        global $lang_common, $feather_config, $feather_user, $feather_start, $db, $lang_antispam_questions, $lang_antispam, $lang_register, $lang_prof_reg;

        if (!$feather_user['is_guest']) {
            header('Location: index.php');
            exit;
        }

        // Load the register.php language file
        require FEATHER_ROOT.'lang/'.$feather_user['language'].'/register.php';

        // Load the register.php/profile.php language file
        require FEATHER_ROOT.'lang/'.$feather_user['language'].'/prof_reg.php';

        // Antispam feature
        require FEATHER_ROOT.'lang/'.$feather_user['language'].'/antispam.php';
$index_questions = rand(0, count($lang_antispam_questions)-1);

        // Load the register.php model file
        require FEATHER_ROOT.'model/register.php';

        // Display an error message if new registrations are disabled
        // If $_REQUEST['username'] or $_REQUEST['password'] are filled, we are facing a bot
        if ($feather_config['o_regs_allow'] == '0' || $this->feather->request->post('username') || $this->feather->request->post('password')) {
            message($lang_register['No new regs']);
        }

        $page_title = array(pun_htmlspecialchars($feather_config['o_board_title']), $lang_register['Register']);
        $required_fields = array('req_user' => $lang_common['Username'], 'req_password1' => $lang_common['Password'], 'req_password2' => $lang_prof_reg['Confirm pass'], 'req_email1' => $lang_common['Email'], 'req_email2' => $lang_common['Email'].' 2', 'captcha' => $lang_antispam['Robot title']);
        $focus_element = array('register', 'req_user');

        if (!defined('PUN_ACTIVE_PAGE')) {
            define('PUN_ACTIVE_PAGE', 'register');
        }

        $user['timezone'] = isset($user['timezone']) ? $user['timezone'] : $feather_config['o_default_timezone'];
        $user['dst'] = isset($user['dst']) ? $user['dst'] : $feather_config['o_default_dst'];
        $user['email_setting'] = isset($user['email_setting']) ? $user['email_setting'] : $feather_config['o_default_email_setting'];
        $user['errors'] = '';

        if ($this->feather->request()->isPost()) {
            $user = check_for_errors($this->feather);

            // Did everything go according to plan? Insert the user
            if (empty($user['errors'])) {
                insert_user($user);
            }
        }

        require FEATHER_ROOT.'include/header.php';

        $this->feather->render('header.php', array(
                            'lang_common' => $lang_common,
                            'page_title' => $page_title,
                            'feather_user' => $feather_user,
                            'feather_config' => $feather_config,
                            '_SERVER'    =>    $_SERVER,
                            'navlinks'        =>    $navlinks,
                            'page_info'        =>    $page_info,
                            'db'        =>    $db,
                            'required_fields'    =>    $required_fields,
                            'p'        =>    '',
                            )
                    );

        $this->feather->render('register/form.php', array(
                            'errors' => $user['errors'],
                            'feather_config' => $feather_config,
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

        $this->feather->render('footer.php', array(
                            'lang_common' => $lang_common,
                            'feather_user' => $feather_user,
                            'feather_config' => $feather_config,
                            'feather_start' => $feather_start,
                            'footer_style' => 'index',
                            )
                    );

        require FEATHER_ROOT.'include/footer.php';
    }

    public function cancel()
    {
        redirect(get_base_url());
    }

    public function rules()
    { // TODO: fix $_GET w/ URL rewriting

                    global $lang_common, $lang_login, $feather_config, $feather_user, $feather_start, $db;

                    // If we are logged in, we shouldn't be here
                    if (!$feather_user['is_guest']) {
                        header('Location: index.php');
                        exit;
                    }

                    // Display an error message if new registrations are disabled
                    if ($feather_config['o_regs_allow'] == '0') {
                        message($lang_register['No new regs']);
                    }

                    // Load the register.php language file
                    require FEATHER_ROOT.'lang/'.$feather_user['language'].'/register.php';

                    // Load the register.php/profile.php language file
                    require FEATHER_ROOT.'lang/'.$feather_user['language'].'/prof_reg.php';

        if ($feather_config['o_rules'] != '1') {
            redirect(get_link('register/agree/'));
        }

        $page_title = array(pun_htmlspecialchars($feather_config['o_board_title']), $lang_register['Register'], $lang_register['Forum rules']);

        if (!defined('PUN_ACTIVE_PAGE')) {
            define('PUN_ACTIVE_PAGE', 'register');
        }
        require FEATHER_ROOT.'include/header.php';

        $this->feather->render('header.php', array(
                            'lang_common' => $lang_common,
                            'page_title' => $page_title,
                            'p' => $p,
                            'feather_user' => $feather_user,
                            'feather_config' => $feather_config,
                            '_SERVER'    =>    $_SERVER,
                            'required_fields'    =>    '',
                            'page_head'        =>    '',
                            'navlinks'        =>    $navlinks,
                            'page_info'        =>    $page_info,
                            'db'        =>    $db,
                            )
                    );

        $this->feather->render('register/rules.php', array(
                            'lang_register'    =>    $lang_register,
                            'feather_config'    =>    $feather_config,
                            )
                    );

        $this->feather->render('footer.php', array(
                            'lang_common' => $lang_common,
                            'feather_user' => $feather_user,
                            'feather_config' => $feather_config,
                            'feather_start' => $feather_start,
                            'footer_style' => '',
                            )
                    );

        require FEATHER_ROOT.'include/footer.php';
    }
}
