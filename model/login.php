<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace model;

class login
{
    public function __construct()
    {
        $this->feather = \Slim\Slim::getInstance();
        $this->db = $this->feather->db;
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

        $username_sql = ($db_type == 'mysql' || $db_type == 'mysqli' || $db_type == 'mysql_innodb' || $db_type == 'mysqli_innodb') ? 'username=\''.$this->db->escape($form_username).'\'' : 'LOWER(username)=LOWER(\''.$this->db->escape($form_username).'\')';

        $result = $this->db->query('SELECT * FROM '.$this->db->prefix.'users WHERE '.$username_sql) or error('Unable to fetch user info', __FILE__, __LINE__, $this->db->error());
        $cur_user = $this->db->fetch_assoc($result);

        $authorized = false;

        if (!empty($cur_user['password'])) {
            $form_password_hash = feather_hash($form_password); // Will result in a SHA-1 hash

            // If there is a salt in the database we have upgraded from 1.3-legacy though haven't yet logged in
            if (!empty($cur_user['salt'])) {
                if (sha1($cur_user['salt'].sha1($form_password)) == $cur_user['password']) {
                    // 1.3 used sha1(salt.sha1(pass))

                    $authorized = true;

                    $this->db->query('UPDATE '.$this->db->prefix.'users SET password=\''.$form_password_hash.'\', salt=NULL WHERE id='.$cur_user['id']) or error('Unable to update user password', __FILE__, __LINE__, $this->db->error());
                }
            }
            // If the length isn't 40 then the password isn't using sha1, so it must be md5 from 1.2
            elseif (strlen($cur_user['password']) != 40) {
                if (md5($form_password) == $cur_user['password']) {
                    $authorized = true;

                    $this->db->query('UPDATE '.$this->db->prefix.'users SET password=\''.$form_password_hash.'\' WHERE id='.$cur_user['id']) or error('Unable to update user password', __FILE__, __LINE__, $this->db->error());
                }
            }
            // Otherwise we should have a normal sha1 password
            else {
                $authorized = ($cur_user['password'] == $form_password_hash);
            }
        }

        if (!$authorized) {
            message($lang_login['Wrong user/pass'].' <a href="'.get_link('login/action/forget/').'">'.$lang_login['Forgotten pass'].'</a>');
        }

        // Update the status if this is the first time the user logged in
        if ($cur_user['group_id'] == FEATHER_UNVERIFIED) {
            $this->db->query('UPDATE '.$this->db->prefix.'users SET group_id='.$this->config['o_default_user_group'].' WHERE id='.$cur_user['id']) or error('Unable to update user status', __FILE__, __LINE__, $this->db->error());

            // Regenerate the users info cache
            if (!defined('FORUM_CACHE_FUNCTIONS_LOADED')) {
                require FEATHER_ROOT.'include/cache.php';
            }

            generate_users_info_cache();
        }

        // Remove this user's guest entry from the online list
        $this->db->query('DELETE FROM '.$this->db->prefix.'online WHERE ident=\''.$this->db->escape(get_remote_address()).'\'') or error('Unable to delete from online list', __FILE__, __LINE__, $this->db->error());

        $expire = ($save_pass == '1') ? time() + 1209600 : time() + $this->config['o_timeout_visit'];
        feather_setcookie($cur_user['id'], $form_password_hash, $expire);

        // Reset tracked topics
        set_tracked_topics(null);

        // Try to determine if the data in redirect_url is valid (if not, we redirect to index.php after login)
        $redirect_url = validate_redirect($this->request->post('redirect_url'), get_base_url());

        redirect(feather_escape($redirect_url), $lang_login['Login redirect']);
    }

    public function logout($id, $token)
    {
        global $lang_login;

        if ($this->user['is_guest'] || !isset($id) || $id != $this->user['id'] || !isset($token) || $token != feather_hash($this->user['id'].feather_hash(get_remote_address()))) {
            header('Location: '.get_base_url());
            exit;
        }

        // Remove user from "users online" list
        $this->db->query('DELETE FROM '.$this->db->prefix.'online WHERE user_id='.$this->user['id']) or error('Unable to delete from online list', __FILE__, __LINE__, $this->db->error());

        // Update last_visit (make sure there's something to update it with)
        if (isset($this->user['logged'])) {
            $this->db->query('UPDATE '.$this->db->prefix.'users SET last_visit='.$this->user['logged'].' WHERE id='.$this->user['id']) or error('Unable to update user visit data', __FILE__, __LINE__, $this->db->error());
        }

        feather_setcookie(1, feather_hash(uniqid(rand(), true)), time() + 31536000);

        redirect(get_base_url(), $lang_login['Logout redirect']);
    }

    public function password_forgotten()
    {
        global $lang_common, $lang_login;

        if (!$this->user['is_guest']) {
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
                $result = $this->db->query('SELECT id, username, last_email_sent FROM '.$this->db->prefix.'users WHERE email=\''.$this->db->escape($email).'\'') or error('Unable to fetch user info', __FILE__, __LINE__, $this->db->error());

                if ($this->db->num_rows($result)) {
                    // Load the "activate password" template
                    $mail_tpl = trim(file_get_contents(FEATHER_ROOT.'lang/'.$this->user['language'].'/mail_templates/activate_password.tpl'));

                    // The first row contains the subject
                    $first_crlf = strpos($mail_tpl, "\n");
                    $mail_subject = trim(substr($mail_tpl, 8, $first_crlf-8));
                    $mail_message = trim(substr($mail_tpl, $first_crlf));

                    // Do the generic replacements first (they apply to all emails sent out here)
                    $mail_message = str_replace('<base_url>', get_base_url().'/', $mail_message);
                    $mail_message = str_replace('<board_mailer>', $this->config['o_board_title'], $mail_message);

                    // Loop through users we found
                    while ($cur_hit = $this->db->fetch_assoc($result)) {
                        if ($cur_hit['last_email_sent'] != '' && (time() - $cur_hit['last_email_sent']) < 3600 && (time() - $cur_hit['last_email_sent']) >= 0) {
                            message(sprintf($lang_login['Email flood'], intval((3600 - (time() - $cur_hit['last_email_sent'])) / 60)), true);
                        }

                        // Generate a new password and a new password activation code
                        $new_password = random_pass(12);
                        $new_password_key = random_pass(8);

                        $this->db->query('UPDATE '.$this->db->prefix.'users SET activate_string=\''.feather_hash($new_password).'\', activate_key=\''.$new_password_key.'\', last_email_sent = '.time().' WHERE id='.$cur_hit['id']) or error('Unable to update activation data', __FILE__, __LINE__, $this->db->error());

                        // Do the user specific replacements to the template
                        $cur_mail_message = str_replace('<username>', $cur_hit['username'], $mail_message);
                        $cur_mail_message = str_replace('<activation_url>', get_link('user/'.$id.'/action/change_pass/?key='.$new_password_key), $cur_mail_message);
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