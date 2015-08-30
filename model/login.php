<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace model;

use DB;

class login
{
    public function __construct()
    {
        $this->feather = \Slim\Slim::getInstance();
        $this->start = $this->feather->start;
        $this->config = $this->feather->config;
        $this->user = $this->feather->user;
        $this->request = $this->feather->request;
        $this->hook = $this->feather->hooks;
        $this->email = $this->feather->email;
        $this->auth = new \model\auth();
    }

    public function login()
    {
        $this->hook->fire('login_start');

        $form_username = $this->feather->utils->trim($this->request->post('req_username'));
        $form_password = $this->feather->utils->trim($this->request->post('req_password'));
        $save_pass = $this->request->post('save_pass');

        $user = DB::for_table('users')->where('username', $form_username);

        $user = $this->hook->fireDB('find_user_login', $user);

        $user = $user->find_one();

        $authorized = false;

        if (!empty($user->password)) {
            $form_password_hash = \FeatherBB\Utils::feather_hash($form_password); // Will result in a SHA-1 hash
            $authorized = ($user->password == $form_password_hash);
        }

        $authorized = $this->hook->fire('authorized_login', $authorized);

        if (!$authorized) {
            throw new \FeatherBB\Error(__('Wrong user/pass').' <a href="'.$this->feather->url->get('login/action/forget/').'">'.__('Forgotten pass').'</a>', 403);
        }

        // Update the status if this is the first time the user logged in
        if ($user->group_id == FEATHER_UNVERIFIED) {
            $update_usergroup = DB::for_table('users')->where('id', $user->id)
                ->find_one()
                ->set('group_id', $this->config['o_default_user_group']);
            $update_usergroup = $this->hook->fireDB('update_usergroup_login', $update_usergroup);
            $update_usergroup = $update_usergroup->save();

            // Regenerate the users info cache
            if (!$this->feather->cache->isCached('users_info')) {
                $this->feather->cache->store('users_info', \model\cache::get_users_info());
            }

            $stats = $this->feather->cache->retrieve('users_info');
        }

        // Remove this user's guest entry from the online list
        $delete_online = DB::for_table('online')->where('ident', $this->request->getIp());
        $delete_online = $this->hook->fireDB('delete_online_login', $delete_online);
        $delete_online = $delete_online->delete_many();

        $expire = ($save_pass == '1') ? time() + 1209600 : time() + $this->config['o_timeout_visit'];
        $expire = $this->hook->fire('expire_login', $expire);
        $this->auth->feather_setcookie($user->id, $form_password_hash, $expire);

        // Reset tracked topics
        set_tracked_topics(null);

        // Try to determine if the data in redirect_url is valid (if not, we redirect to index.php after login)
        $redirect_url = $this->request->post('redirect_url');
        $redirect_url = $this->hook->fire('redirect_url_login', $redirect_url);

        redirect($this->feather->utils->escape($redirect_url), __('Login redirect'));
    }

    public function logout($id, $token)
    {
        $token = $this->hook->fire('logout_start', $token, $id);

        if ($this->user->is_guest || !isset($id) || $id != $this->user->id || !isset($token) || $token != \FeatherBB\Utils::feather_hash($this->user->id.\FeatherBB\Utils::feather_hash($this->request->getIp()))) {
            header('Location: '.$this->feather->url->base());
            exit;
        }

        // Remove user from "users online" list
        $delete_online = DB::for_table('online')->where('user_id', $this->user->id);
        $delete_online = $this->hook->fireDB('delete_online_logout', $delete_online);
        $delete_online = $delete_online->delete_many();

        // Update last_visit (make sure there's something to update it with)
        if (isset($this->user->logged)) {
            $update_last_visit = DB::for_table('users')->where('id', $this->user->id)
                ->find_one()
                ->set('last_visit', $this->user->logged);
            $update_last_visit = $this->hook->fireDB('update_online_logout', $update_last_visit);
            $update_last_visit = $update_last_visit->save();
        }

        $this->hook->fire('logout_end');

        $this->auth->feather_setcookie(1, \FeatherBB\Utils::feather_hash(uniqid(rand(), true)), time() + 31536000);

        redirect($this->feather->url->base(), __('Logout redirect'));
    }

    public function password_forgotten()
    {
        $this->hook->fire('password_forgotten_start');

        if (!$this->user->is_guest) {
            header('Location: '.$this->feather->url->base());
            exit;
        }
        // Start with a clean slate
        $errors = array();

        if ($this->feather->request()->isPost()) {
            // Validate the email address
            $email = strtolower($this->feather->utils->trim($this->request->post('req_email')));
            if (!$this->email->is_valid_email($email)) {
                $errors[] = __('Invalid email');
            }

            // Did everything go according to plan?
            if (empty($errors)) {
                $result['select'] = array('id', 'username', 'last_email_sent');

                $result = DB::for_table('users')
                    ->select_many($result['select'])
                    ->where('email', $email);
                $result = $this->hook->fireDB('password_forgotten_query', $result);
                $result = $result->find_many();

                if ($result) {
                    // Load the "activate password" template
                    $mail_tpl = trim(file_get_contents(FEATHER_ROOT.'lang/'.$this->user->language.'/mail_templates/activate_password.tpl'));
                    $mail_tpl = $this->hook->fire('mail_tpl_password_forgotten', $mail_tpl);

                    // The first row contains the subject
                    $first_crlf = strpos($mail_tpl, "\n");
                    $mail_subject = trim(substr($mail_tpl, 8, $first_crlf-8));
                    $mail_message = trim(substr($mail_tpl, $first_crlf));

                    // Do the generic replacements first (they apply to all emails sent out here)
                    $mail_message = str_replace('<base_url>', $this->feather->url->base().'/', $mail_message);
                    $mail_message = str_replace('<board_mailer>', $this->config['o_board_title'], $mail_message);

                    $mail_message = $this->hook->fire('mail_message_password_forgotten', $mail_message);

                    // Loop through users we found
                    foreach($result as $cur_hit) {
                        if ($cur_hit->last_email_sent != '' && (time() - $cur_hit->last_email_sent) < 3600 && (time() - $cur_hit->last_email_sent) >= 0) {
                            throw new \FeatherBB\Error(sprintf(__('Email flood'), intval((3600 - (time() - $cur_hit->last_email_sent)) / 60)), 429);
                        }

                        // Generate a new password and a new password activation code
                        $new_password = random_pass(12);
                        $new_password_key = random_pass(8);

                        $query['update'] = array(
                            'activate_string' => \FeatherBB\Utils::feather_hash($new_password),
                            'activate_key'    => $new_password_key,
                            'last_email_sent' => time()
                        );

                        $query = DB::for_table('users')
                                    ->where('id', $cur_hit->id)
                                    ->find_one()
                                    ->set($query['update']);
                        $query = $this->hook->fireDB('password_forgotten_mail_query', $query);
                        $query = $query->save();

                        // Do the user specific replacements to the template
                        $cur_mail_message = str_replace('<username>', $cur_hit->username, $mail_message);
                        $cur_mail_message = str_replace('<activation_url>', $this->feather->url->get('user/'.$cur_hit->id.'/action/change_pass/?key='.$new_password_key), $cur_mail_message);
                        $cur_mail_message = str_replace('<new_password>', $new_password, $cur_mail_message);
                        $cur_mail_message = $this->hook->fire('cur_mail_message_password_forgotten', $cur_mail_message);

                        $this->email->feather_mail($email, $mail_subject, $cur_mail_message);
                    }

                    throw new \FeatherBB\Error(__('Forget mail').' <a href="mailto:'.$this->feather->utils->escape($this->config['o_admin_email']).'">'.$this->feather->utils->escape($this->config['o_admin_email']).'</a>.', 400);
                } else {
                    $errors[] = __('No email match').' '.$this->feather->utils->escape($email).'.';
                }
            }
        }

        $errors = $this->hook->fire('password_forgotten', $errors);

        return $errors;
    }

    public function get_redirect_url()
    {
        $this->hook->fire('get_redirect_url_start');

        if (!empty($this->request->getReferrer())) {
            $redirect_url = $this->request->getReferrer();
        }

        if (!isset($redirect_url)) {
            $redirect_url = $this->feather->url->base();
        } elseif (preg_match('%viewtopic\.php\?pid=(\d+)$%', $redirect_url, $matches)) { // TODO
            $redirect_url .= '#p'.$matches[1];
        }

        $redirect_url = $this->hook->fire('get_redirect_url', $redirect_url);

        return $redirect_url;
    }
}
