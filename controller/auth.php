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
                $form_password_hash = \FeatherBB\Utils::feather_hash($form_password); // Will result in a SHA-1 hash
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
            }
            throw new \FeatherBB\Error(__('Wrong user/pass').' <a href="'.$this->feather->url->get('login/action/forget/').'">'.__('Forgotten pass').'</a>', 403);
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

        if ($this->feather->user->is_guest || !isset($token) || $token != \FeatherBB\Utils::feather_hash($this->feather->user->id.\FeatherBB\Utils::feather_hash($this->feather->request->getIp()))) {
            $this->feather->url->redirect($this->feather->url->get('/'), 'Not logged in');
        }

        \model\auth::delete_online_by_id($this->feather->user->id);

        // Update last_visit (make sure there's something to update it with)
        if (isset($this->feather->user->logged)) {
            \model\auth::set_last_visit($this->feather->user->id, $this->feather->user->logged);
        }

        \model\auth::feather_setcookie(1, \FeatherBB\Utils::feather_hash(uniqid(rand(), true)), time() + 31536000);
        $this->feather->hooks->fire('logout_end');

        redirect($this->feather->url->base(), __('Logout redirect'));
    }

    public function forget()
    {
        if (!$this->feather->user->is_guest) {
            $this->feather->url->redirect($this->feather->url->get('/'), 'Already logged in');
        }

        if ($this->feather->request->isPost()) {
            // Validate the email address
            $email = strtolower($this->feather->utils->trim($this->feather->request->post('req_email')));
            if (!$this->feather->email->is_valid_email($email)) {
                throw new \FeatherBB\Error(__('Invalid email'), 400);
            }
            $user = \model\auth::get_user_from_email($email);

            if ($user) {
                // Load the "activate password" template
                $mail_tpl = trim(file_get_contents(FEATHER_ROOT.'lang/'.$this->feather->user->language.'/mail_templates/activate_password.tpl'));
                $mail_tpl = $this->feather->hooks->fire('mail_tpl_password_forgotten', $mail_tpl);

                // The first row contains the subject
                $first_crlf = strpos($mail_tpl, "\n");
                $mail_subject = trim(substr($mail_tpl, 8, $first_crlf-8));
                $mail_message = trim(substr($mail_tpl, $first_crlf));

                // Do the generic replacements first (they apply to all emails sent out here)
                $mail_message = str_replace('<base_url>', $this->feather->url->base().'/', $mail_message);
                $mail_message = str_replace('<board_mailer>', $this->feather->forum_settings['o_board_title'], $mail_message);

                $mail_message = $this->feather->hooks->fire('mail_message_password_forgotten', $mail_message);

                if ($user->last_email_sent != '' && (time() - $user->last_email_sent) < 3600 && (time() - $user->last_email_sent) >= 0) {
                    throw new \FeatherBB\Error(sprintf(__('Email flood'), intval((3600 - (time() - $user->last_email_sent)) / 60)), 429);
                }

                // Generate a new password and a new password activation code
                $new_password = random_pass(12);
                $new_password_key = random_pass(8);

                \model\auth::set_new_password($new_password, $new_password_key, $user->id);

                // Do the user specific replacements to the template
                $cur_mail_message = str_replace('<username>', $user->username, $mail_message);
                $cur_mail_message = str_replace('<activation_url>', $this->feather->url->get('user/'.$user->id.'/action/change_pass/?key='.$new_password_key), $cur_mail_message);
                $cur_mail_message = str_replace('<new_password>', $new_password, $cur_mail_message);
                $cur_mail_message = $this->feather->hooks->fire('cur_mail_message_password_forgotten', $cur_mail_message);

                $this->feather->email->feather_mail($email, $mail_subject, $cur_mail_message);

                $this->feather->url->redirect($this->feather->url->get('/'), __('Forget mail').' <a href="mailto:'.$this->feather->utils->escape($this->feather->forum_settings['o_admin_email']).'">'.$this->feather->utils->escape($this->feather->forum_settings['o_admin_email']).'</a>.', 200);
            } else {
                throw new \FeatherBB\Error(__('No email match').' '.$this->feather->utils->escape($email).'.', 400);
            }
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
