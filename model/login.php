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
    }
 
    public function login()
    {
        global $db_type, $lang_login;

        $form_username = feather_trim($this->request->post('req_username'));
        $form_password = feather_trim($this->request->post('req_password'));
        $save_pass = $this->request->post('save_pass');

        $user = DB::for_table('users')->where('username', $form_username)->find_one();

        $authorized = false;

        if (!empty($user->password)) {
            $form_password_hash = feather_hash($form_password); // Will result in a SHA-1 hash

            // If the length isn't 40 then the password isn't using sha1, so it must be md5 from 1.2
            // Maybe this should be removed
            if (strlen($user->password) != 40) {
                if (md5($form_password) == $user->password) {
                    $authorized = true;

                    DB::for_table('users')->where('id', $user->id)
                                                              ->find_one()
                                                              ->set('password', $form_password_hash)
                                                              ->save();
                }
            }
            // Otherwise we should have a normal sha1 password
            else {
                $authorized = ($user->password == $form_password_hash);
            }
        }

        if (!$authorized) {
            message($lang_login['Wrong user/pass'].' <a href="'.get_link('login/action/forget/').'">'.$lang_login['Forgotten pass'].'</a>');
        }

        // Update the status if this is the first time the user logged in
        if ($user->group_id == FEATHER_UNVERIFIED) {
            DB::for_table('users')->where('id', $user->id)
                                                      ->find_one()
                                                      ->set('group_id', $this->config['o_default_user_group'])
                                                      ->save();

            // Regenerate the users info cache
            if (!defined('FORUM_CACHE_FUNCTIONS_LOADED')) {
                require FEATHER_ROOT.'include/cache.php';
            }

            generate_users_info_cache();
        }

        // Remove this user's guest entry from the online list
        DB::for_table('online')->where('ident', get_remote_address())
                                                   ->delete_many();

        $expire = ($save_pass == '1') ? time() + 1209600 : time() + $this->config['o_timeout_visit'];
        feather_setcookie($user->id, $form_password_hash, $expire);

        // Reset tracked topics
        set_tracked_topics(null);

        // Try to determine if the data in redirect_url is valid (if not, we redirect to index.php after login)
        $redirect_url = validate_redirect($this->request->post('redirect_url'), get_base_url());

        redirect(feather_escape($redirect_url), $lang_login['Login redirect']);
    }

    public function logout($id, $token)
    {
        global $lang_login;

        if ($this->user->is_guest || !isset($id) || $id != $this->user->id || !isset($token) || $token != feather_hash($this->user->id.feather_hash(get_remote_address()))) {
            header('Location: '.get_base_url());
            exit;
        }

        // Remove user from "users online" list
        DB::for_table('online')->where('user_id', $this->user->id)
                                                   ->delete_many();

        // Update last_visit (make sure there's something to update it with)
        if (isset($this->user->logged)) {
            DB::for_table('users')->where('id', $this->user->id)
                                                      ->find_one()
                                                      ->set('last_visit', $this->user->logged)
                                                      ->save();
        }

        feather_setcookie(1, feather_hash(uniqid(rand(), true)), time() + 31536000);

        redirect(get_base_url(), $lang_login['Logout redirect']);
    }

    public function password_forgotten()
    {
        global $lang_common, $lang_login;

        if (!$this->user->is_guest) {
            header('Location: '.get_base_url());
            exit;
        }
        // Start with a clean slate
        $errors = array();

        if ($this->feather->request()->isPost()) {
            require FEATHER_ROOT.'include/email.php';

            // Validate the email address
            $email = strtolower(feather_trim($this->request->post('req_email')));
            if (!is_valid_email($email)) {
                $errors[] = $lang_common['Invalid email'];
            }

            // Did everything go according to plan?
            if (empty($errors)) {
                $select_password_forgotten = array('id', 'username', 'last_email_sent');

                $result = DB::for_table('users')
                    ->select_many($select_password_forgotten)
                    ->where('email', $email)
                    ->find_many();

                if ($result) {
                    // Load the "activate password" template
                    $mail_tpl = trim(file_get_contents(FEATHER_ROOT.'lang/'.$this->user->language.'/mail_templates/activate_password.tpl'));

                    // The first row contains the subject
                    $first_crlf = strpos($mail_tpl, "\n");
                    $mail_subject = trim(substr($mail_tpl, 8, $first_crlf-8));
                    $mail_message = trim(substr($mail_tpl, $first_crlf));

                    // Do the generic replacements first (they apply to all emails sent out here)
                    $mail_message = str_replace('<base_url>', get_base_url().'/', $mail_message);
                    $mail_message = str_replace('<board_mailer>', $this->config['o_board_title'], $mail_message);

                    // Loop through users we found
                    foreach($result as $cur_hit) {
                        if ($cur_hit->last_email_sent != '' && (time() - $cur_hit->last_email_sent) < 3600 && (time() - $cur_hit->last_email_sent) >= 0) {
                            message(sprintf($lang_login['Email flood'], intval((3600 - (time() - $cur_hit->last_email_sent)) / 60)), true);
                        }

                        // Generate a new password and a new password activation code
                        $new_password = random_pass(12);
                        $new_password_key = random_pass(8);
 
                        $update_password = array(
                            'activate_string' => feather_hash($new_password),
                            'activate_key'    => $new_password_key,
                            'last_email_sent' => time()
                        );
                        
                        DB::for_table('users')->where('id', $cur_hit->id)
                                                                  ->find_one()
                                                                  ->set($update_password)
                                                                  ->save();

                        // Do the user specific replacements to the template
                        $cur_mail_message = str_replace('<username>', $cur_hit->username, $mail_message);
                        $cur_mail_message = str_replace('<activation_url>', get_link('user/'.$cur_hit->id.'/action/change_pass/?key='.$new_password_key), $cur_mail_message);
                        $cur_mail_message = str_replace('<new_password>', $new_password, $cur_mail_message);

                        pun_mail($email, $mail_subject, $cur_mail_message);
                    }

                    message($lang_login['Forget mail'].' <a href="mailto:'.feather_escape($this->config['o_admin_email']).'">'.feather_escape($this->config['o_admin_email']).'</a>.', true);
                } else {
                    $errors[] = $lang_login['No email match'].' '.htmlspecialchars($email).'.';
                }
            }
        }

        return $errors;
    }

    public function get_redirect_url($server_data)
    {
        if (!empty($server_data['HTTP_REFERER'])) {
            $redirect_url = validate_redirect($server_data['HTTP_REFERER'], null);
        }

        if (!isset($redirect_url)) {
            $redirect_url = get_base_url();
        } elseif (preg_match('%viewtopic\.php\?pid=(\d+)$%', $redirect_url, $matches)) { // TODO
            $redirect_url .= '#p'.$matches[1];
        }

        return $redirect_url;
    }
}