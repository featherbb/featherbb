<?php

/**
 * Copyright (C) 2015-2017 FeatherBB
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
        Lang::load('register');
        Lang::load('prof_reg');
        Lang::load('antispam');
    }

    public function display($req, $res, $args)
    {
        Container::get('hooks')->fire('controller.register.display');

        if (!User::get()->is_guest) {
            return Router::redirect(Router::pathFor('home'));
        }

        // Antispam feature
        $langAntispamQuestions = require ForumEnv::get('FEATHER_ROOT').'featherbb/lang/'.User::getPref('language').'/antispam.php';
        $indexQuestions = rand(0, count($langAntispamQuestions)-1);

        // Display an error message if new registrations are disabled
        // If $_rEQUEST['username'] or $_rEQUEST['password'] are filled, we are facing a bot
        if (ForumSettings::get('o_regs_allow') == '0' || Input::post('username') || Input::post('password')) {
            throw new Error(__('No new regs'), 403);
        }

        $user['errors'] = '';

        if (Request::isPost()) {
            $user = $this->model->checkErrors();

            // Did everything go according to plan? Insert the user
            if (empty($user['errors'])) {
                return $this->model->insertUser($user);
            }
        }

        View::setPageInfo([
                    'title' => [Utils::escape(ForumSettings::get('o_board_title')), __('Register')],
                    'active_page' => 'register',
                    'is_indexed' => true,
                    'errors' => $user['errors'],
                    'index_questions'    =>    $indexQuestions,
                    'languages' => \FeatherBB\Core\Lister::getLangs(),
                    'question' => array_keys($langAntispamQuestions),
                    'qencoded' => md5(array_keys($langAntispamQuestions)[$indexQuestions]),
            ]
        )->addTemplate('register/form.php')->display();
    }

    public function cancel($req, $res, $args)
    {
        Container::get('hooks')->fire('controller.register.cancel');

        return Router::redirect(Router::pathFor('home'));
    }

    public function rules($req, $res, $args)
    {
        Container::get('hooks')->fire('controller.register.rules');

        // If we are logged in, we shouldn't be here
        if (!User::get()->is_guest) {
            return Router::redirect(Router::pathFor('home'));
        }

        // Display an error message if new registrations are disabled
        if (ForumSettings::get('o_regs_allow') == '0') {
            throw new Error(__('No new regs'), 403);
        }

        if (ForumSettings::get('o_rules') != '1') {
            return Router::redirect(Router::pathFor('register'));
        }

        View::setPageInfo([
                'title' => [Utils::escape(ForumSettings::get('o_board_title')), __('Register'), __('Forum rules')],
                'active_page' => 'register',
            ]
        )->addTemplate('register/rules.php')->display();
    }
}
