<?php

/**
 * Copyright (C) 2015-2016 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace FeatherBB\Controller;

use FeatherBB\Core\Error;
use FeatherBB\Core\Url;
use FeatherBB\Core\Utils;

class Register
{
    public function __construct()
    {
        $this->model = new \FeatherBB\Model\Register();
        load_textdomain('featherbb', Config::get('forum_env')['FEATHER_ROOT'].'featherbb/lang/'.Container::get('user')->language.'/register.mo');
        load_textdomain('featherbb', Config::get('forum_env')['FEATHER_ROOT'].'featherbb/lang/'.Container::get('user')->language.'/prof_reg.mo');
        load_textdomain('featherbb', Config::get('forum_env')['FEATHER_ROOT'].'featherbb/lang/'.Container::get('user')->language.'/antispam.mo');
    }

    public function display($req, $res, $args)
    {
        Container::get('hooks')->fire('controller.register.display');

        if (!Container::get('user')->is_guest) {
            Router::redirect(Router::pathFor('home'));
        }

        // Antispam feature
        require Config::get('forum_env')['FEATHER_ROOT'].'featherbb/lang/'.Container::get('user')->language.'/antispam.php';
        $index_questions = rand(0, count($lang_antispam_questions)-1);

        // Display an error message if new registrations are disabled
        // If $_REQUEST['username'] or $_REQUEST['password'] are filled, we are facing a bot
        if (Config::get('forum_settings')['o_regs_allow'] == '0' || Input::post('username') || Input::post('password')) {
            throw new Error(__('No new regs'), 403);
        }

        $user['timezone'] = isset($user['timezone']) ? $user['timezone'] : Config::get('forum_settings')['o_default_timezone'];
        $user['dst'] = isset($user['dst']) ? $user['dst'] : Config::get('forum_settings')['o_default_dst'];
        $user['email_setting'] = isset($user['email_setting']) ? $user['email_setting'] : Config::get('forum_settings')['o_default_email_setting'];
        $user['errors'] = '';

        if (Request::isPost()) {
            $user = $this->model->check_for_errors();

            // Did everything go according to plan? Insert the user
            if (empty($user['errors'])) {
                $this->model->insert_user($user);
            }
        }

            View::setPageInfo(array(
                        'title' => array(Utils::escape(Config::get('forum_settings')['o_board_title']), __('Register')),
                        'focus_element' => array('register', 'req_user'),
                        'required_fields' => array('req_user' => __('Username'), 'req_password1' => __('Password'), 'req_password2' => __('Confirm pass'), 'req_email1' => __('Email'), 'req_email2' => __('Email').' 2', 'captcha' => __('Robot title')),
                        'active_page' => 'register',
                        'is_indexed' => true,
                        'errors' => $user['errors'],
                        'index_questions'    =>    $index_questions,
                        'languages' => \FeatherBB\Core\Lister::getLangs(),
                        'question' => array_keys($lang_antispam_questions),
                        'qencoded' => md5(array_keys($lang_antispam_questions)[$index_questions]),
                            )
                    )->addTemplate('register/form.php')->display();
    }

    public function cancel($req, $res, $args)
    {
        Container::get('hooks')->fire('controller.register.cancel');

        Router::redirect(Router::pathFor('home'));
    }

    public function rules($req, $res, $args)
    {
        Container::get('hooks')->fire('controller.register.rules');

        // If we are logged in, we shouldn't be here
        if (!Container::get('user')->is_guest) {
            Router::redirect(Router::pathFor('home'));
        }

        // Display an error message if new registrations are disabled
        if (Config::get('forum_settings')['o_regs_allow'] == '0') {
            throw new Error(__('No new regs'), 403);
        }

        if (Config::get('forum_settings')['o_rules'] != '1') {
            Router::redirect(Router::pathFor('register'));
        }

        View::setPageInfo(array(
                            'title' => array(Utils::escape(Config::get('forum_settings')['o_board_title']), __('Register'), __('Forum rules')),
                            'active_page' => 'register',
                            )
                    )->addTemplate('register/rules.php')->display();
    }
}
