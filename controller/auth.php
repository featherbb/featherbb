<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace controller;
use DB;
class auth
{
    public function __construct()
    {
        $this->feather = \Slim\Slim::getInstance();
        load_textdomain('featherbb', FEATHER_ROOT.'lang/'.$this->feather->user->language.'/login.mo');
    }

    public function login()
    {
        if (!$this->feather->user->is_guest) {
            $this->feather->url->redirect($this->feather->url->get('/'), 'Already logged in');
        }

        if ($this->feather->request->isPost()) {
            $this->feather->hooks->fire('login_start');
            $form_username = $this->feather->utils->trim($this->feather->request->post('req_username'));
            $form_password = $this->feather->utils->trim($this->feather->request->post('req_password'));
            $save_pass = (bool) $this->feather->request->post('save_pass');

            $user = \model\auth::get_user_from_name($form_username);

            if (!empty($user->password)) {
                $form_password_hash = feather_hash($form_password); // Will result in a SHA-1 hash
                if ($user->password == $form_password_hash) {
                    if ($user->group_id == FEATHER_UNVERIFIED) {
                        \model\auth::update_group($user->id, $this->feather->forum_settings['o_default_user_group']);
                        if (!$this->feather->cache->isCached('users_info')) {
                            $this->feather->cache->store('users_info', \model\cache::get_users_info());
                        }
                    }

                    \model\auth::delete_online_by_ip($this->feather->request->getIp());
                    // Reset tracked topics
                    set_tracked_topics(null);

                    $expire = ($save_pass) ? $this->feather->now + 1209600 : $this->feather->now + $this->feather->forum_settings['o_timeout_visit'];
                    $expire = $this->feather->hooks->fire('expire_login', $expire);
                    \model\auth::feather_setcookie($user->id, $form_password_hash, $expire);

                    $this->feather->url->redirect($this->feather->url->base(), __('Login redirect'));
                }
            } else {
                throw new \FeatherBB\Error(__('Wrong user/pass').' <a href="'.$this->feather->url->get('login/action/forget/').'">'.__('Forgotten pass').'</a>', 403);
            }
        } else {
            $this->feather->view2->setPageInfo(array(
                                'active_page' => 'login',
                                'title' => array($this->feather->utils->escape($this->feather->forum_settings['o_board_title']), __('Login')),
                                'required_fields' => array('req_username' => __('Username'), 'req_password' => __('Password')),
                                'focus_element' => array('login', 'req_username'),
                                )
                        )->addTemplate('login/form.php')->display();
        }
    }

    public function logout($token)
    {
        $token = $this->feather->hooks->fire('logout_start', $token);

        if ($this->feather->user->is_guest || !isset($token) || $token != feather_hash($this->feather->user->id.feather_hash($this->feather->request->getIp()))) {
            $this->feather->url->redirect($this->feather->url->get('/'), 'Not logged in');
        }

        \model\auth::delete_online_by_id($this->feather->user->id);

        // Update last_visit (make sure there's something to update it with)
        if (isset($this->feather->user->logged)) {
            $update_last_visit = DB::for_table('users')->where('id', $this->feather->user->id)
                ->find_one()
                ->set('last_visit', $this->feather->user->logged);
            $update_last_visit = $this->feather->hooks->fireDB('update_online_logout', $update_last_visit);
            $update_last_visit = $update_last_visit->save();
        }

        \model\auth::feather_setcookie(1, feather_hash(uniqid(rand(), true)), time() + 31536000);
        $this->feather->hooks->fire('logout_end');
        redirect($this->feather->url->base(), __('Logout redirect'));
    }

    public function forget()
    {
        if (!$this->feather->user->is_guest) {
            $this->feather->url->redirect($this->feather->url->get('/'), 'Already logged in');
        }

        $this->feather->view2->setPageInfo(array(
//                'errors'    =>    $this->model->password_forgotten(),
                'active_page' => 'login',
                'title' => array($this->feather->utils->escape($this->feather->forum_settings['o_board_title']), __('Request pass')),
                'required_fields' => array('req_email' => __('Email')),
                'focus_element' => array('request_pass', 'req_email'),
            )
        )->addTemplate('login/password_forgotten.php')->display();
    }
}
