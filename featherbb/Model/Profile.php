<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace FeatherBB\Model;

use FeatherBB\Core\Error;
use FeatherBB\Core\Utils;
use FeatherBB\Core\Url;
use FeatherBB\Core\Random;
use DB;

class Profile
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
        $this->auth = new \FeatherBB\Model\Auth();
    }

    public function change_pass($id)
    {
        $id = $this->hook->fire('change_pass_start', $id);

        if ($this->request->get('key')) {

            $key = $this->request->get('key');
            $key = $this->hook->fire('change_pass_key', $key);

            // If the user is already logged in we shouldn't be here :)
            if (!$this->user->is_guest) {
                header('Location: '.Url::base());
                exit;
            }

            $cur_user = DB::for_table('users')
                ->where('id', $id);
            $cur_user = $this->hook->fireDB('change_pass_user_query', $cur_user);
            $cur_user = $cur_user->find_one();

            if ($key == '' || $key != $cur_user['activate_key']) {
                throw new Error(__('Pass key bad').' <a href="mailto:'.Utils::escape($this->config['o_admin_email']).'">'.Utils::escape($this->config['o_admin_email']).'</a>.', 400);
            } else {
                $query = DB::for_table('users')
                    ->where('id', $id)
                    ->find_one()
                    ->set('password', $cur_user['activate_string'])
                    ->set_expr('activate_string', 'NULL')
                    ->set_expr('activate_key', 'NULL');
                $query = $this->hook->fireDB('change_pass_activate_query', $query);
                $query = $query->save();

                Url::redirect($this->feather->urlFor('home'), __('Pass updated'));
            }
        }

        // Make sure we are allowed to change this user's password
        if ($this->user->id != $id) {
            $id = $this->hook->fire('change_pass_key_not_id', $id);

            if (!$this->user->is_admmod) { // A regular user trying to change another user's password?
                throw new Error(__('No permission'), 403);
            } elseif ($this->user->g_moderator == '1') {
                // A moderator trying to change a user's password?

                $user['select'] = array('u.group_id', 'g.g_moderator');

                $user = DB::for_table('users')
                    ->table_alias('u')
                    ->select_many($user['select'])
                    ->inner_join('groups', array('g.g_id', '=', 'u.group_id'), 'g')
                    ->where('u.id', $id);
                $user = $this->hook->fireDB('change_pass_user_query', $user);
                $user = $user->find_one();

                if (!$user) {
                    throw new Error(__('Bad request'), 404);
                }

                if ($this->user->g_mod_edit_users == '0' || $this->user->g_mod_change_passwords == '0' || $user['group_id'] == FEATHER_ADMIN || $user['g_moderator'] == '1') {
                    throw new Error(__('No permission'), 403);
                }
            }
        }

        if ($this->request->isPost()) {
            $old_password = $this->request->post('req_old_password') ? Utils::trim($this->request->post('req_old_password')) : '';
            $new_password1 = Utils::trim($this->request->post('req_new_password1'));
            $new_password2 = Utils::trim($this->request->post('req_new_password2'));

            if ($new_password1 != $new_password2) {
                throw new Error(__('Pass not match'), 400);
            }
            if (Utils::strlen($new_password1) < 6) {
                throw new Error(__('Pass too short'), 400);
            }

            $cur_user = DB::for_table('users')
                ->where('id', $id);
            $cur_user = $this->hook->fireDB('change_pass_find_user', $cur_user);
            $cur_user = $cur_user->find_one();

            $authorized = false;

            if (!empty($cur_user['password'])) {
                $old_password_hash = Random::hash($old_password);

                if ($cur_user['password'] == $old_password_hash || $this->user->is_admmod) {
                    $authorized = true;
                }
            }

            if (!$authorized) {
                throw new Error(__('Wrong pass'), 403);
            }

            $new_password_hash = Random::hash($new_password1);

            $update_password = DB::for_table('users')
                ->where('id', $id)
                ->find_one()
                ->set('password', $new_password_hash);
            $update_password = $this->hook->fireDB('change_pass_query', $update_password);
            $update_password = $update_password->save();

            if ($this->user->id == $id) {
                $this->auth->feather_setcookie($this->user->id, $new_password_hash, time() + $this->config['o_timeout_visit']);
            }

            $this->hook->fire('change_pass');
            Url::redirect($this->feather->urlFor('profileSection', array('id' => $id, 'section' => 'essentials')), __('Pass updated redirect'));
        }
    }

    public function change_email($id)
    {
        $id = $this->hook->fire('change_email_start', $id);

        // Make sure we are allowed to change this user's email
        if ($this->user->id != $id) {
            $id = $this->hook->fire('change_email_not_id', $id);

            if (!$this->user->is_admmod) { // A regular user trying to change another user's email?
                throw new Error(__('No permission'), 403);
            } elseif ($this->user->g_moderator == '1') {
                // A moderator trying to change a user's email?
                $user['select'] = array('u.group_id', 'g.g_moderator');

                $user = DB::for_table('users')
                    ->table_alias('u')
                    ->select_many($user['select'])
                    ->inner_join('groups', array('g.g_id', '=', 'u.group_id'), 'g')
                    ->where('u.id', $id);
                $user = $this->hook->fireDB('change_email_not_id_query', $user);
                $user = $user->find_one();

                if (!$user) {
                    throw new Error(__('Bad request'), 404);
                }

                if ($this->user->g_mod_edit_users == '0' || $this->user->g_mod_change_passwords == '0' || $user['group_id'] == FEATHER_ADMIN || $user['g_moderator'] == '1') {
                    throw new Error(__('No permission'), 403);
                }
            }
        }

        if ($this->request->get('key')) {
            $key = $this->request->get('key');
            $key = $this->hook->fire('change_email_key', $key);

            $new_email_key = DB::for_table('users')
                ->where('id', $id);
            $new_email_key = $this->hook->fireDB('change_email_key_query', $new_email_key);
            $new_email_key = $new_email_key->find_one_col('activate_key');

            if ($key == '' || $key != $new_email_key) {
                throw new Error(__('Email key bad').' <a href="mailto:'.Utils::escape($this->config['o_admin_email']).'">'.Utils::escape($this->config['o_admin_email']).'</a>.', 400);
            } else {
                $update_mail = DB::for_table('users')
                    ->where('id', $id)
                    ->find_one()
                    ->set_expr('email', 'activate_string')
                    ->set_expr('activate_string', 'NULL')
                    ->set_expr('activate_key', 'NULL');
                $update_mail = $this->hook->fireDB('change_email_query', $update_mail);
                $update_mail = $update_mail->save();

                Url::redirect($this->feather->urlFor('home'), __('Email updated'));
            }
        } elseif ($this->request->isPost()) {
            $this->hook->fire('change_email_post');

            if (Random::hash($this->request->post('req_password')) !== $this->user->password) {
                throw new Error(__('Wrong pass'));
            }

            // Validate the email address
            $new_email = strtolower(Utils::trim($this->request->post('req_new_email')));
            $new_email = $this->hook->fire('change_email_new_email', $new_email);
            if (!$this->email->is_valid_email($new_email)) {
                throw new Error(__('Invalid email'), 400);
            }

            // Check if it's a banned email address
            if ($this->email->is_banned_email($new_email)) {
                if ($this->config['p_allow_banned_email'] == '0') {
                    throw new Error(__('Banned email'), 403);
                } elseif ($this->config['o_mailing_list'] != '') {
                    // Load the "banned email change" template
                    $mail_tpl = trim(file_get_contents($this->feather->forum_env['FEATHER_ROOT'].'featherbb/lang/'.$this->user->language.'/mail_templates/banned_email_change.tpl'));
                    $mail_tpl = $this->hook->fire('change_email_mail_tpl', $mail_tpl);

                    // The first row contains the subject
                    $first_crlf = strpos($mail_tpl, "\n");
                    $mail_subject = trim(substr($mail_tpl, 8, $first_crlf-8));
                    $mail_subject = $this->hook->fire('change_email_mail_subject', $mail_subject);

                    $mail_message = trim(substr($mail_tpl, $first_crlf));
                    $mail_message = str_replace('<username>', $this->user->username, $mail_message);
                    $mail_message = str_replace('<email>', $new_email, $mail_message);
                    $mail_message = str_replace('<profile_url>', Url::get('user/'.$id.'/'), $mail_message);
                    $mail_message = str_replace('<board_mailer>', $this->config['o_board_title'], $mail_message);
                    $mail_message = $this->hook->fire('change_email_mail_message', $mail_message);

                    $this->email->feather_mail($this->config['o_mailing_list'], $mail_subject, $mail_message);
                }
            }

            // Check if someone else already has registered with that email address
            $result['select'] = array('id', 'username');

            $result = DB::for_table('users')
                ->select_many($result['select'])
                ->where('email', $new_email);
            $result = $this->hook->fireDB('change_email_check_mail', $result);
            $result = $result->find_many();

            if ($result) {
                if ($this->config['p_allow_dupe_email'] == '0') {
                    throw new Error(__('Dupe email'), 400);
                } elseif ($this->config['o_mailing_list'] != '') {
                    foreach($result as $cur_dupe) {
                        $dupe_list[] = $cur_dupe['username'];
                    }

                    // Load the "dupe email change" template
                    $mail_tpl = trim(file_get_contents($this->feather->forum_env['FEATHER_ROOT'].'featherbb/lang/'.$this->user->language.'/mail_templates/dupe_email_change.tpl'));
                    $mail_tpl = $this->hook->fire('change_email_mail_dupe_tpl', $mail_tpl);

                    // The first row contains the subject
                    $first_crlf = strpos($mail_tpl, "\n");
                    $mail_subject = trim(substr($mail_tpl, 8, $first_crlf-8));
                    $mail_subject = $this->hook->fire('change_email_mail_dupe_subject', $mail_subject);

                    $mail_message = trim(substr($mail_tpl, $first_crlf));
                    $mail_message = str_replace('<username>', $this->user->username, $mail_message);
                    $mail_message = str_replace('<dupe_list>', implode(', ', $dupe_list), $mail_message);
                    $mail_message = str_replace('<profile_url>', Url::get('user/'.$id.'/'), $mail_message);
                    $mail_message = str_replace('<board_mailer>', $this->config['o_board_title'], $mail_message);
                    $mail_message = $this->hook->fire('change_email_mail_dupe_message', $mail_message);

                    $this->email->feather_mail($this->config['o_mailing_list'], $mail_subject, $mail_message);
                }
            }


            $new_email_key = Random::pass(8);
            $new_email_key = $this->hook->fire('change_email_new_email_key', $new_email_key);

            // Update the user
            unset($user);
            $user['update'] = array(
                'activate_string' => $new_email,
                'activate_key'  => $new_email_key,
            );
            $user = DB::for_table('users')
                ->where('id', tid)
                ->find_one()
                ->set($user['update']);
            $user = $this->hook->fireDB('change_email_user_query', $user);
            $user = $user->save();

            // Load the "activate email" template
            $mail_tpl = trim(file_get_contents($this->feather->forum_env['FEATHER_ROOT'].'featherbb/lang/'.$this->user->language.'/mail_templates/activate_email.tpl'));
            $mail_tpl = $this->hook->fire('change_email_mail_activate_tpl', $mail_tpl);

            // The first row contains the subject
            $first_crlf = strpos($mail_tpl, "\n");
            $mail_subject = trim(substr($mail_tpl, 8, $first_crlf-8));
            $mail_subject = $this->hook->fire('change_email_mail_activate_subject', $mail_subject);

            $mail_message = trim(substr($mail_tpl, $first_crlf));
            $mail_message = str_replace('<username>', $this->user->username, $mail_message);
            $mail_message = str_replace('<base_url>', Url::base(), $mail_message);
            $mail_message = str_replace('<activation_url>', Url::get('user/'.$id.'/action/change_email/?key='.$new_email_key), $mail_message);
            $mail_message = str_replace('<board_mailer>', $this->config['o_board_title'], $mail_message);
            $mail_message = $this->hook->fire('change_email_mail_activate_message', $mail_message);

            $this->email->feather_mail($new_email, $mail_subject, $mail_message);

            $this->hook->fire('change_email_sent');

            throw new Error(__('Activate email sent').' <a href="mailto:'.Utils::escape($this->config['o_admin_email']).'">'.Utils::escape($this->config['o_admin_email']).'</a>.', true);
        }
        $this->hook->fire('change_email');
    }

    public function upload_avatar($id, $files_data)
    {
        $files_data = $this->hook->fire('upload_avatar_start', $files_data, $id);

        if (!isset($files_data['req_file'])) {
            throw new Error(__('No file'));
        }

        $uploaded_file = $files_data['req_file'];

        // Make sure the upload went smooth
        if (isset($uploaded_file['error'])) {
            switch ($uploaded_file['error']) {
                case 1: // UPLOAD_ERR_INI_SIZE
                case 2: // UPLOAD_ERR_FORM_SIZE
                    throw new Error(__('Too large ini'));
                    break;

                case 3: // UPLOAD_ERR_PARTIAL
                    throw new Error(__('Partial upload'));
                    break;

                case 4: // UPLOAD_ERR_NO_FILE
                    throw new Error(__('No file'));
                    break;

                case 6: // UPLOAD_ERR_NO_TMP_DIR
                    throw new Error(__('No tmp directory'));
                    break;

                default:
                    // No error occured, but was something actually uploaded?
                    if ($uploaded_file['size'] == 0) {
                        throw new Error(__('No file'));
                    }
                    break;
            }
        }

        if (is_uploaded_file($uploaded_file['tmp_name'])) {
            $uploaded_file = $this->hook->fire('upload_avatar_is_uploaded_file', $uploaded_file);

            // Preliminary file check, adequate in most cases
            $allowed_types = array('image/gif', 'image/jpeg', 'image/pjpeg', 'image/png', 'image/x-png');
            if (!in_array($uploaded_file['type'], $allowed_types)) {
                throw new Error(__('Bad type'));
            }

            // Make sure the file isn't too big
            if ($uploaded_file['size'] > $this->config['o_avatars_size']) {
                throw new Error(__('Too large').' '.Utils::forum_number_format($this->config['o_avatars_size']).' '.__('bytes').'.');
            }

            // Move the file to the avatar directory. We do this before checking the width/height to circumvent open_basedir restrictions
            if (!@move_uploaded_file($uploaded_file['tmp_name'], $this->feather->forum_env['FEATHER_ROOT'].$this->config['o_avatars_dir'].'/'.$id.'.tmp')) {
                throw new Error(__('Move failed').' <a href="mailto:'.Utils::escape($this->config['o_admin_email']).'">'.Utils::escape($this->config['o_admin_email']).'</a>.');
            }

            list($width, $height, $type, ) = @getimagesize($this->feather->forum_env['FEATHER_ROOT'].$this->config['o_avatars_dir'].'/'.$id.'.tmp');

            // Determine type
            if ($type == IMAGETYPE_GIF) {
                $extension = '.gif';
            } elseif ($type == IMAGETYPE_JPEG) {
                $extension = '.jpg';
            } elseif ($type == IMAGETYPE_PNG) {
                $extension = '.png';
            } else {
                // Invalid type
                @unlink($this->feather->forum_env['FEATHER_ROOT'].$this->config['o_avatars_dir'].'/'.$id.'.tmp');
                throw new Error(__('Bad type'));
            }

            // Now check the width/height
            if (empty($width) || empty($height) || $width > $this->config['o_avatars_width'] || $height > $this->config['o_avatars_height']) {
                @unlink($this->feather->forum_env['FEATHER_ROOT'].$this->config['o_avatars_dir'].'/'.$id.'.tmp');
                throw new Error(__('Too wide or high').' '.$this->config['o_avatars_width'].'x'.$this->config['o_avatars_height'].' '.__('pixels').'.');
            }

            // Delete any old avatars and put the new one in place
            Delete::avatar($id);
            @rename($this->feather->forum_env['FEATHER_ROOT'].$this->config['o_avatars_dir'].'/'.$id.'.tmp', $this->feather->forum_env['FEATHER_ROOT'].$this->config['o_avatars_dir'].'/'.$id.$extension);
            @chmod($this->feather->forum_env['FEATHER_ROOT'].$this->config['o_avatars_dir'].'/'.$id.$extension, 0644);
        } else {
            throw new Error(__('Unknown failure'));
        }

        $uploaded_file = $this->hook->fire('upload_avatar', $uploaded_file);

        Url::redirect($this->feather->urlFor('profileSection', array('id' => $id, 'section' => 'personality')), __('Avatar upload redirect'));
    }

    public function update_group_membership($id)
    {
        $id = $this->hook->fire('update_group_membership_start', $id);

        $new_group_id = intval($this->request->post('group_id'));

        $old_group_id = DB::for_table('users')
            ->where('id', $id);
        $old_group_id = $this->hook->fireDB('update_group_membership_old_group', $old_group_id);
        $old_group_id = $old_group_id->find_one_col('group_id');

        $update_group = DB::for_table('users')
            ->where('id', $id)
            ->find_one()
            ->set('group_id', $new_group_id);
        $update_group = $this->hook->fireDB('update_group_membership_update_group', $update_group);
        $update_group = $update_group->save();

        // Regenerate the users info cache
        if (!$this->feather->cache->isCached('users_info')) {
            $this->feather->cache->store('users_info', Cache::get_users_info());
        }

        $stats = $this->feather->cache->retrieve('users_info');

        if ($old_group_id == FEATHER_ADMIN || $new_group_id == FEATHER_ADMIN) {
            $this->feather->cache->store('admin_ids', Cache::get_admin_ids());
        }

        $new_group_mod = DB::for_table('groups')
            ->where('g_id', $new_group_id);
        $new_group_mod = $this->hook->fireDB('update_group_membership_new_mod', $new_group_mod);
        $new_group_mod = $new_group_mod->find_one_col('g_moderator');

        // If the user was a moderator or an administrator, we remove him/her from the moderator list in all forums as well
        if ($new_group_id != FEATHER_ADMIN && $new_group_mod != '1') {

            // Loop through all forums
            $result = $this->loop_mod_forums();

            foreach($result as $cur_forum) {
                $cur_moderators = ($cur_forum['moderators'] != '') ? unserialize($cur_forum['moderators']) : array();

                if (in_array($id, $cur_moderators)) {
                    $username = array_search($id, $cur_moderators);
                    unset($cur_moderators[$username]);

                    $update_forums = DB::for_table('forums')
                        ->where('id', $cur_forum['id'])
                        ->find_one();

                    if (!empty($cur_moderators)) {
                        $update_forums = $update_forums->set('moderators', serialize($cur_moderators));
                    } else {
                        $update_forums = $update_forums->set_expr('moderators', 'NULL');
                    }
                    $update_forums = $this->hook->fireDB('update_group_membership_mod_forums', $update_forums);
                    $update_forums = $update_forums->save();
                }
            }
        }

        $id = $this->hook->fire('update_group_membership', $id);

        Url::redirect($this->feather->urlFor('profileSection', array('id' => $id, 'section' => 'admin')), __('Group membership redirect'));
    }

    public function get_username($id)
    {
        // Get the username of the user we are processing
        $username = DB::for_table('users')
            ->where('id', $id)
            ->find_one_col('username');

        $username = $this->hook->fire('get_username', $username);

        return $username;
    }

    public function loop_mod_forums()
    {
        $result['select'] = array('id', 'moderators');

        $result = DB::for_table('forums')
            ->select_many($result['select']);
        $result = $this->hook->fireDB('loop_mod_forums', $result);
        $result = $result->find_many();

        return $result;
    }

    public function update_mod_forums($id)
    {
        $username = $this->get_username($id);

        $moderator_in = ($this->request->post('moderator_in')) ? array_keys($this->request->post('moderator_in')) : array();

        // Loop through all forums
        $result = $this->loop_mod_forums();

        foreach($result as $cur_forum) {
            $cur_moderators = ($cur_forum['moderators'] != '') ? unserialize($cur_forum['moderators']) : array();
            // If the user should have moderator access (and he/she doesn't already have it)
            if (in_array($cur_forum['id'], $moderator_in) && !in_array($id, $cur_moderators)) {
                $cur_moderators[$username] = $id;
                uksort($cur_moderators, 'utf8_strcasecmp');

                $update_forums = DB::for_table('forums')
                    ->where('id', $cur_forum['id'])
                    ->find_one()
                    ->set('moderators', serialize($cur_moderators));
                $update_forums = $this->hook->fireDB('update_mod_forums_query', $update_forums);
                $update_forums = $update_forums->save();
            }
            // If the user shouldn't have moderator access (and he/she already has it)
            elseif (!in_array($cur_forum['id'], $moderator_in) && in_array($id, $cur_moderators)) {
                unset($cur_moderators[$username]);

                $update_forums = DB::for_table('forums')
                    ->where('id', $cur_forum['id'])
                    ->find_one();

                if (!empty($cur_moderators)) {
                    $update_forums = $update_forums->set('moderators', serialize($cur_moderators));
                } else {
                    $update_forums = $update_forums->set_expr('moderators', 'NULL');
                }
                $update_forums = $this->hook->fireDB('update_mod_forums_query', $update_forums);
                $update_forums = $update_forums->save();
            }
        }

        $id = $this->hook->fire('update_mod_forums', $id);

        Url::redirect($this->feather->urlFor('profileSection', array('id' => $id, 'section' => 'admin')), __('Update forums redirect'));
    }

    public function ban_user($id)
    {
        $id = $this->hook->fire('ban_user_start', $id);

        // Get the username of the user we are banning
        $username = $this->get_username($id);

        // Check whether user is already banned
        $ban_id = DB::for_table('bans')
            ->where('username', $username)
            ->order_by_expr('expire IS NULL DESC')
            ->order_by_desc('expire');
        $ban_id = $this->hook->fireDB('ban_user_query', $ban_id);
        $ban_id = $ban_id->find_one_col('id');

        if ($ban_id) {
            Url::redirect($this->feather->urlFor('editBan', array('id' => $ban_id)), __('Ban redirect'));
        } else {
            Url::redirect($this->feather->urlFor('addBan', array('id' => $id)), __('Ban redirect'));
        }
    }

    public function promote_user($id)
    {
        $id = $this->hook->fire('promote_user_start', $id);

        $pid = $this->request->get('pid') ? intval($this->request->get('pid')) : 0;

        // Find the group ID to promote the user to
        $next_group_id = DB::for_table('groups')
            ->table_alias('g')
            ->inner_join('users', array('u.group_id', '=', 'g.g_id'), 'u')
            ->where('u.id', $id);
        $next_group_id = $this->hook->fireDB('promote_user_group_id', $next_group_id);
        $next_group_id = $next_group_id->find_one_col('g.g_promote_next_group');

        if (!$next_group_id) {
            throw new Error(__('Bad request'), 404);
        }

        // Update the user
        $update_user = DB::for_table('users')
            ->where('id', $id)
            ->find_one()
            ->set('group_id', $next_group_id);
        $update_user = $this->hook->fireDB('promote_user_query', $update_user);
        $update_user = $update_user->save();

        $pid = $this->hook->fire('promote_user', $pid);

        Url::redirect($this->feather->url->get('post/'.$pid.'/#p'.$pid), __('User promote redirect'));
    }

    public function delete_user($id)
    {
        $id = $this->hook->fire('delete_user_start', $id);

        // Get the username and group of the user we are deleting
        $result['select'] = array('group_id', 'username');

        $result = DB::for_table('users')
            ->where('id', $id)
            ->select_many($result['select']);
        $result = $this->hook->fireDB('delete_user_username', $result);
        $result = $result->find_one();

        $group_id = $result['group_id'];
        $username = $result['username'];

        if ($group_id == FEATHER_ADMIN) {
            throw new Error(__('No delete admin message'));
        }

        if ($this->request->post('delete_user_comply')) {
            // If the user is a moderator or an administrator, we remove him/her from the moderator list in all forums as well
            $group_mod = DB::for_table('groups')
                ->where('g_id', $group_id);
            $group_mod = $this->hook->fireDB('delete_user_group_mod', $group_mod);
            $group_mod = $group_mod->find_one_col('g_moderator');

            if ($group_id == FEATHER_ADMIN || $group_mod == '1') {

                // Loop through all forums
                $result = $this->loop_mod_forums();

                foreach($result as $cur_forum) {
                    $cur_moderators = ($cur_forum['moderators'] != '') ? unserialize($cur_forum['moderators']) : array();

                    if (in_array($id, $cur_moderators)) {
                        unset($cur_moderators[$username]);

                        $update_forums = DB::for_table('forums')
                            ->where('id', $cur_forum['id'])
                            ->find_one();

                        if (!empty($cur_moderators)) {
                            $update_forums = $update_forums->set('moderators', serialize($cur_moderators));
                        } else {
                            $update_forums = $update_forums->set_expr('moderators', 'NULL');
                        }
                        $update_forums = $this->hook->fireDB('update_mod_forums_query', $update_forums);
                        $update_forums = $update_forums->save();
                    }
                }
            }

            // Delete any subscriptions
            $delete_subscriptions = DB::for_table('topic_subscriptions')
                ->where('user_id', $id);
            $delete_subscriptions = $this->hook->fireDB('delete_user_subscriptions_topic', $delete_subscriptions);
            $delete_subscriptions = $delete_subscriptions->delete_many();
            unset($delete_subscriptions);
            $delete_subscriptions = DB::for_table('forum_subscriptions')
                ->where('user_id', $id);
            $delete_subscriptions = $this->hook->fireDB('delete_user_subscriptions_forum', $delete_subscriptions);
            $delete_subscriptions = $delete_subscriptions->delete_many();

            // Remove him/her from the online list (if they happen to be logged in)
            $delete_online = DB::for_table('online')
                ->where('user_id', $id);
            $delete_online = $this->hook->fireDB('delete_user_online', $delete_online);
            $delete_online = $delete_online->delete_many();

            // Should we delete all posts made by this user?
            if ($this->request->post('delete_posts')) {
                // Hold on, this could take some time!
                @set_time_limit(0);

                $this->hook->fire('delete_user_posts');

                // Find all posts made by this user
                unset($result);
                $result['select'] = array('p.id', 'p.topic_id', 't.forum_id');

                $result = DB::for_table('posts')
                    ->table_alias('p')
                    ->select_many($result['select'])
                    ->inner_join('topics', array('t.id', '=', 'p.topic_id'), 't')
                    ->inner_join('forums', array('f.id', '=', 't.forum_id'), 'f')
                    ->where('p.poster_id', $id);
                $result = $this->hook->fireDB('delete_user_posts_first_query', $result);
                $result = $result->find_many();

                if ($result) {
                    foreach($result as $cur_post) {
                        // Determine whether this post is the "topic post" or not
                        $result2 = DB::for_table('posts')
                            ->where('topic_id', $cur_post['topic_id'])
                            ->order_by('posted');
                        $result2 = $this->hook->fireDB('delete_user_posts_second_query', $result2);
                        $result2 = $result2->find_one_col('id');

                        if ($result2 == $cur_post['id']) {
                            Delete::topic($cur_post['topic_id']);
                        } else {
                            Delete::post($cur_post['id'], $cur_post['topic_id']);
                        }

                        Forum::update($cur_post['forum_id']);
                    }
                }
            } else {
                // Set all his/her posts to guest
                $update_guest = DB::for_table('posts')
                    ->where_in('poster_id', '1');
                $update_guest = $this->hook->fireDB('delete_user_posts_guest_query', $update_guest);
                $update_guest = $update_guest->update_many('poster_id', $id);
            }

            // Delete the user
            $delete_user = DB::for_table('users')
                            ->where('id', $id);
            $delete_user = $delete_user->delete_many();

            // Delete user avatar
            Delete::avatar($id);

            // Regenerate the users info cache
            if (!$this->feather->cache->isCached('users_info')) {
                $this->feather->cache->store('users_info', Cache::get_users_info());
            }

            $stats = $this->feather->cache->retrieve('users_info');

            if ($group_id == FEATHER_ADMIN) {
                $this->feather->cache->store('admin_ids', Cache::get_admin_ids());
            }

            $this->hook->fire('delete_user');

            Url::redirect($this->feather->urlFor('home'), __('User delete redirect'));
        }
    }

    public function fetch_user_group($id)
    {
        $info = array();

        $info['select'] = array('old_username' => 'u.username', 'group_id' => 'u.group_id', 'is_moderator' => 'g.g_moderator');

        $info = DB::for_table('users')
            ->table_alias('u')
            ->select_many($info['select'])
            ->left_outer_join('groups', array('g.g_id', '=', 'u.group_id'), 'g')
            ->where('u.id', $id);
        $info = $this->hook->fireDB('fetch_user_group', $info);
        $info = $info->find_one();

        if (!$info) {
            throw new Error(__('Bad request'), 404);
        }

        return $info;
    }

    public function update_profile($id, $info, $section)
    {
        $info = $this->hook->fire('update_profile_start', $info, $id, $section);

        $username_updated = false;

        $section = $this->hook->fire('update_profile_section', $section, $id, $info);

        // Validate input depending on section
        switch ($section) {
            case 'essentials':
            {
                $form = array(
                    'timezone'        => floatval($this->request->post('form_timezone')),
                    'dst'            => $this->request->post('form_dst') ? '1' : '0',
                    'time_format'    => intval($this->request->post('form_time_format')),
                    'date_format'    => intval($this->request->post('form_date_format')),
                );

                // Make sure we got a valid language string
                if ($this->request->post('form_language')) {
                    $languages = \FeatherBB\Core\Lister::getLangs();
                    $form['language'] = Utils::trim($this->request->post('form_language'));
                    if (!in_array($form['language'], $languages)) {
                        throw new Error(__('Bad request'), 404);
                    }
                }

                if ($this->user->is_admmod) {
                    $form['admin_note'] = Utils::trim($this->request->post('admin_note'));

                    // Are we allowed to change usernames?
                    if ($this->user->g_id == FEATHER_ADMIN || ($this->user->g_moderator == '1' && $this->user->g_mod_rename_users == '1')) {
                        $form['username'] = Utils::trim($this->request->post('req_username'));

                        if ($form['username'] != $info['old_username']) {
                            $errors = '';
                            $errors = $this->check_username($form['username'], $errors, $id);
                            if (!empty($errors)) {
                                throw new Error($errors[0]);
                            }

                            $username_updated = true;
                        }
                    }

                    // We only allow administrators to update the post count
                    if ($this->user->g_id == FEATHER_ADMIN) {
                        $form['num_posts'] = intval($this->request->post('num_posts'));
                    }
                }

                if ($this->config['o_regs_verify'] == '0' || $this->user->is_admmod) {
                    // Validate the email address
                    $form['email'] = strtolower(Utils::trim($this->request->post('req_email')));
                    if (!$this->email->is_valid_email($form['email'])) {
                        throw new Error(__('Invalid email'));
                    }
                }

                break;
            }

            case 'personal':
            {
                $form = array(
                    'realname'        => $this->request->post('form_realname') ? Utils::trim($this->request->post('form_realname')) : '',
                    'url'            => $this->request->post('form_url') ? Utils::trim($this->request->post('form_url')) : '',
                    'location'        => $this->request->post('form_location') ? Utils::trim($this->request->post('form_location')) : '',
                );

                // Add http:// if the URL doesn't contain it already (while allowing https://, too)
                if ($this->user->g_post_links == '1') {
                    if ($form['url'] != '') {
                        $url = Url::is_valid($form['url']);

                        if ($url === false) {
                            throw new Error(__('Invalid website URL'));
                        }

                        $form['url'] = $url['url'];
                    }
                } else {
                    if (!empty($form['url'])) {
                        throw new Error(__('Website not allowed'));
                    }

                    $form['url'] = '';
                }

                if ($this->user->g_id == FEATHER_ADMIN) {
                    $form['title'] = Utils::trim($this->request->post('title'));
                } elseif ($this->user->g_set_title == '1') {
                    $form['title'] = Utils::trim($this->request->post('title'));

                    if ($form['title'] != '') {
                        // A list of words that the title may not contain
                        // If the language is English, there will be some duplicates, but it's not the end of the world
                        $forbidden = array('member', 'moderator', 'administrator', 'banned', 'guest', utf8_strtolower(__('Member')), utf8_strtolower(__('Moderator')), utf8_strtolower(__('Administrator')), utf8_strtolower(__('Banned')), utf8_strtolower(__('Guest')));

                        if (in_array(utf8_strtolower($form['title']), $forbidden)) {
                            throw new Error(__('Forbidden title'));
                        }
                    }
                }

                break;
            }

            case 'messaging':
            {
                $form = array(
                    'jabber'        => Utils::trim($this->request->post('form_jabber')),
                    'icq'            => Utils::trim($this->request->post('form_icq')),
                    'msn'            => Utils::trim($this->request->post('form_msn')),
                    'aim'            => Utils::trim($this->request->post('form_aim')),
                    'yahoo'            => Utils::trim($this->request->post('form_yahoo')),
                );

                // If the ICQ UIN contains anything other than digits it's invalid
                if (preg_match('%[^0-9]%', $form['icq'])) {
                    throw new Error(__('Bad ICQ'));
                }

                break;
            }

            case 'personality':
            {
                $form = array();

                // Clean up signature from POST
                if ($this->config['o_signatures'] == '1') {
                    $form['signature'] = Utils::linebreaks(Utils::trim($this->request->post('signature')));

                    // Validate signature
                    if (Utils::strlen($form['signature']) > $this->config['p_sig_length']) {
                        throw new Error(sprintf(__('Sig too long'), $this->config['p_sig_length'], Utils::strlen($form['signature']) - $this->config['p_sig_length']));
                    } elseif (substr_count($form['signature'], "\n") > ($this->config['p_sig_lines']-1)) {
                        throw new Error(sprintf(__('Sig too many lines'), $this->config['p_sig_lines']));
                    } elseif ($form['signature'] && $this->config['p_sig_all_caps'] == '0' && Utils::is_all_uppercase($form['signature']) && !$this->user->is_admmod) {
                        $form['signature'] = utf8_ucwords(utf8_strtolower($form['signature']));
                    }

                    // Validate BBCode syntax
                    if ($this->config['p_sig_bbcode'] == '1') {
                        $errors = array();

                        $form['signature'] = $this->feather->parser->preparse_bbcode($form['signature'], $errors, true);

                        if (count($errors) > 0) {
                            throw new Error('<ul><li>'.implode('</li><li>', $errors).'</li></ul>');
                        }
                    }
                }

                break;
            }

            case 'display':
            {
                $form = array(
                    'disp_topics'        => Utils::trim($this->request->post('form_disp_topics')),
                    'disp_posts'        => Utils::trim($this->request->post('form_disp_posts')),
                    'show_smilies'        => $this->request->post('form_show_smilies') ? '1' : '0',
                    'show_img'            => $this->request->post('form_show_img') ? '1' : '0',
                    'show_img_sig'        => $this->request->post('form_show_img_sig') ? '1' : '0',
                    'show_avatars'        => $this->request->post('form_show_avatars') ? '1' : '0',
                    'show_sig'            => $this->request->post('form_show_sig') ? '1' : '0',
                );

                if ($form['disp_topics'] != '') {
                    $form['disp_topics'] = intval($form['disp_topics']);
                    if ($form['disp_topics'] < 3) {
                        $form['disp_topics'] = 3;
                    } elseif ($form['disp_topics'] > 75) {
                        $form['disp_topics'] = 75;
                    }
                }

                if ($form['disp_posts'] != '') {
                    $form['disp_posts'] = intval($form['disp_posts']);
                    if ($form['disp_posts'] < 3) {
                        $form['disp_posts'] = 3;
                    } elseif ($form['disp_posts'] > 75) {
                        $form['disp_posts'] = 75;
                    }
                }

                // Make sure we got a valid style string
                if ($this->request->post('form_style')) {
                    $styles = \FeatherBB\Core\Lister::getStyles();
                    $form['style'] = Utils::trim($this->request->post('form_style'));
                    if (!in_array($form['style'], $styles)) {
                        throw new Error(__('Bad request'), 404);
                    }
                }

                break;
            }

            case 'privacy':
            {
                $form = array(
                    'email_setting'            => intval($this->request->post('form_email_setting')),
                    'notify_with_post'        => $this->request->post('form_notify_with_post') ? '1' : '0',
                    'auto_notify'            => $this->request->post('form_auto_notify') ? '1' : '0',
                );

                if ($form['email_setting'] < 0 || $form['email_setting'] > 2) {
                    $form['email_setting'] = $this->config['o_default_email_setting'];
                }

                break;
            }

            default:
                throw new Error(__('Bad request'), 404);
        }

        $form = $this->hook->fire('update_profile_form', $form, $section, $id, $info);

        // Single quotes around non-empty values and nothing for empty values
        $temp = array();
        foreach ($form as $key => $input) {
            $temp[$key] = $input;
        }

        if (empty($temp)) {
            throw new Error(__('Bad request'), 404);
        }

        $update_user = DB::for_table('users')
            ->where('id', $id)
            ->find_one()
            ->set($temp);
        $update_user = $this->hook->fireDB('update_profile_query', $update_user);
        $update_user = $update_user->save();

        // If we changed the username we have to update some stuff
        if ($username_updated) {
            $bans_updated = DB::for_table('bans')
                ->where('username', $info['old_username']);
            $bans_updated = $this->hook->fireDB('update_profile_bans_updated', $bans_updated);
            $bans_updated = $bans_updated->update_many('username', $form['username']);

            $update_poster_id = DB::for_table('posts')
                ->where('poster_id', $id);
            $update_poster_id = $this->hook->fireDB('update_profile_poster_id', $update_poster_id);
            $update_poster_id = $update_poster_id->update_many('poster', $form['username']);

            $update_posts = DB::for_table('posts')
                ->where('edited_by', $info['old_username']);
            $update_posts = $this->hook->fireDB('update_profile_posts', $update_posts);
            $update_posts = $update_posts->update_many('edited_by', $form['username']);

            $update_topics_poster = DB::for_table('topics')
                ->where('poster', $info['old_username']);
            $update_topics_poster = $this->hook->fireDB('update_profile_topics_poster', $update_topics_poster);
            $update_topics_poster = $update_topics_poster->update_many('poster', $form['username']);

            $update_topics_last_poster = DB::for_table('topics')
                ->where('last_poster', $info['old_username']);
            $update_topics_last_poster = $this->hook->fireDB('update_profile_topics_last_poster', $update_topics_last_poster);
            $update_topics_last_poster = $update_topics_last_poster->update_many('last_poster', $form['username']);

            $update_forums = DB::for_table('forums')
                ->where('last_poster', $info['old_username']);
            $update_forums = $this->hook->fireDB('update_profile_forums', $update_forums);
            $update_forums = $update_forums->update_many('last_poster', $form['username']);

            $update_online = DB::for_table('online')
                ->where('ident', $info['old_username']);
            $update_online = $this->hook->fireDB('update_profile_online', $update_online);
            $update_online = $update_online->update_many('ident', $form['username']);

            // If the user is a moderator or an administrator we have to update the moderator lists
            $group_id = DB::for_table('users')
                ->where('id', $id);
            // TODO: restore hook
            // $group_id = $this->hook->fireDB('update_profile_group_id', $update_online);
            $group_id = $group_id->find_one_col('group_id');

            $group_mod = DB::for_table('groups')
                ->where('g_id', $group_id);
            $group_mod = $this->hook->fireDB('update_profile_group_mod', $group_mod);
            $group_mod = $group_mod->find_one_col('g_moderator');

            if ($group_id == FEATHER_ADMIN || $group_mod == '1') {

                // Loop through all forums
                $result = $this->loop_mod_forums();

                foreach($result as $cur_forum) {
                    $cur_moderators = ($cur_forum['moderators'] != '') ? unserialize($cur_forum['moderators']) : array();

                    if (in_array($id, $cur_moderators)) {
                        unset($cur_moderators[$info['old_username']]);
                        $cur_moderators[$form['username']] = $id;
                        uksort($cur_moderators, 'utf8_strcasecmp');

                        $update_mods = DB::for_table('forums')
                            ->where('id', $cur_forum['id'])
                            ->find_one()
                            ->set('moderators', serialize($cur_moderators));
                        $update_mods = $this->hook->fireDB('update_profile_mods', $update_mods);
                        $update_mods = $update_mods->save();
                    }
                }
            }

            // Regenerate the users info cache
            if (!$this->feather->cache->isCached('users_info')) {
                $this->feather->cache->store('users_info', Cache::get_users_info());
            }

            $stats = $this->feather->cache->retrieve('users_info');

            // Check if the bans table was updated and regenerate the bans cache when needed
            if ($bans_updated) {
                $this->feather->cache->store('bans', Cache::get_bans());
            }
        }

        $section = $this->hook->fireDB('update_profile', $section, $id);

        Url::redirect($this->feather->urlFor('profileSection', array('id' => $id, 'section' => $section)), __('Profile redirect'));
    }

    public function get_user_info($id)
    {
        $user['select'] = array('u.id', 'u.username', 'u.email', 'u.title', 'u.realname', 'u.url', 'u.jabber', 'u.icq', 'u.msn', 'u.aim', 'u.yahoo', 'u.location', 'u.signature', 'u.disp_topics', 'u.disp_posts', 'u.email_setting', 'u.notify_with_post', 'u.auto_notify', 'u.show_smilies', 'u.show_img', 'u.show_img_sig', 'u.show_avatars', 'u.show_sig', 'u.timezone', 'u.dst', 'u.language', 'u.style', 'u.num_posts', 'u.last_post', 'u.registered', 'u.registration_ip', 'u.admin_note', 'u.date_format', 'u.time_format', 'u.last_visit', 'g.g_id', 'g.g_user_title', 'g.g_moderator');

        $user = DB::for_table('users')
            ->table_alias('u')
            ->select_many($user['select'])
            ->left_outer_join('groups', array('g.g_id', '=', 'u.group_id'), 'g')
            ->where('u.id', $id);
        $user = $this->hook->fireDB('get_user_info', $user);
        $user = $user->find_one();

        if (!$user) {
            throw new Error(__('Bad request'), 404);
        }

        return $user;
    }

    public function parse_user_info($user)
    {
        $user_info = array();

        $user_info = $this->hook->fire('parse_user_info_start', $user_info, $user);

        $user_info['personal'][] = '<dt>'.__('Username').'</dt>';
        $user_info['personal'][] = '<dd>'.Utils::escape($user['username']).'</dd>';

        $user_title_field = Utils::get_title($user);
        $user_info['personal'][] = '<dt>'.__('Title').'</dt>';
        $user_info['personal'][] = '<dd>'.(($this->config['o_censoring'] == '1') ? Utils::censor($user_title_field) : $user_title_field).'</dd>';

        if ($user['realname'] != '') {
            $user_info['personal'][] = '<dt>'.__('Realname').'</dt>';
            $user_info['personal'][] = '<dd>'.Utils::escape(($this->config['o_censoring'] == '1') ? Utils::censor($user['realname']) : $user['realname']).'</dd>';
        }

        if ($user['location'] != '') {
            $user_info['personal'][] = '<dt>'.__('Location').'</dt>';
            $user_info['personal'][] = '<dd>'.Utils::escape(($this->config['o_censoring'] == '1') ? Utils::censor($user['location']) : $user['location']).'</dd>';
        }

        if ($user['url'] != '') {
            $user['url'] = Utils::escape(($this->config['o_censoring'] == '1') ? Utils::censor($user['url']) : $user['url']);
            $user_info['personal'][] = '<dt>'.__('Website').'</dt>';
            $user_info['personal'][] = '<dd><span class="website"><a href="'.$user['url'].'" rel="nofollow">'.$user['url'].'</a></span></dd>';
        }

        if ($user['email_setting'] == '0' && !$this->user->is_guest && $this->user->g_send_email == '1') {
            $user['email_field'] = '<a href="mailto:'.Utils::escape($user['email']).'">'.Utils::escape($user['email']).'</a>';
        } elseif ($user['email_setting'] == '1' && !$this->user->is_guest && $this->user->g_send_email == '1') {
            $user['email_field'] = '<a href="'.Url::get('email/'.$user['id'].'/').'">'.__('Send email').'</a>';
        } else {
            $user['email_field'] = '';
        }
        if ($user['email_field'] != '') {
            $user_info['personal'][] = '<dt>'.__('Email').'</dt>';
            $user_info['personal'][] = '<dd><span class="email">'.$user['email_field'].'</span></dd>';
        }

        if ($user['jabber'] != '') {
            $user_info['messaging'][] = '<dt>'.__('Jabber').'</dt>';
            $user_info['messaging'][] = '<dd>'.Utils::escape(($this->config['o_censoring'] == '1') ? Utils::censor($user['jabber']) : $user['jabber']).'</dd>';
        }

        if ($user['icq'] != '') {
            $user_info['messaging'][] = '<dt>'.__('ICQ').'</dt>';
            $user_info['messaging'][] = '<dd>'.$user['icq'].'</dd>';
        }

        if ($user['msn'] != '') {
            $user_info['messaging'][] = '<dt>'.__('MSN').'</dt>';
            $user_info['messaging'][] = '<dd>'.Utils::escape(($this->config['o_censoring'] == '1') ? Utils::censor($user['msn']) : $user['msn']).'</dd>';
        }

        if ($user['aim'] != '') {
            $user_info['messaging'][] = '<dt>'.__('AOL IM').'</dt>';
            $user_info['messaging'][] = '<dd>'.Utils::escape(($this->config['o_censoring'] == '1') ? Utils::censor($user['aim']) : $user['aim']).'</dd>';
        }

        if ($user['yahoo'] != '') {
            $user_info['messaging'][] = '<dt>'.__('Yahoo').'</dt>';
            $user_info['messaging'][] = '<dd>'.Utils::escape(($this->config['o_censoring'] == '1') ? Utils::censor($user['yahoo']) : $user['yahoo']).'</dd>';
        }

        if ($this->config['o_avatars'] == '1') {
            $avatar_field = Utils::generate_avatar_markup($user['id']);
            if ($avatar_field != '') {
                $user_info['personality'][] = '<dt>'.__('Avatar').'</dt>';
                $user_info['personality'][] = '<dd>'.$avatar_field.'</dd>';
            }
        }

        if ($this->config['o_signatures'] == '1') {
            if (isset($parsed_signature)) {
                $user_info['personality'][] = '<dt>'.__('Signature').'</dt>';
                $user_info['personality'][] = '<dd><div class="postsignature postmsg">'.$parsed_signature.'</div></dd>';
            }
        }

        $posts_field = '';
        if ($this->config['o_show_post_count'] == '1' || $this->user->is_admmod) {
            $posts_field = Utils::forum_number_format($user['num_posts']);
        }
        if ($this->user->g_search == '1') {
            $quick_searches = array();
            if ($user['num_posts'] > 0) {
                $quick_searches[] = '<a href="'.Url::get('search/?action=show_user_topics&amp;user_id='.$user['id']).'">'.__('Show topics').'</a>';
                $quick_searches[] = '<a href="'.Url::get('search/?action=show_user_posts&amp;user_id='.$user['id']).'">'.__('Show posts').'</a>';
            }
            if ($this->user->is_admmod && $this->config['o_topic_subscriptions'] == '1') {
                $quick_searches[] = '<a href="'.Url::get('search/?action=show_subscriptions&amp;user_id='.$user['id']).'">'.__('Show subscriptions').'</a>';
            }

            if (!empty($quick_searches)) {
                $posts_field .= (($posts_field != '') ? ' - ' : '').implode(' - ', $quick_searches);
            }
        }
        if ($posts_field != '') {
            $user_info['activity'][] = '<dt>'.__('Posts').'</dt>';
            $user_info['activity'][] = '<dd>'.$posts_field.'</dd>';
        }

        if ($user['num_posts'] > 0) {
            $user_info['activity'][] = '<dt>'.__('Last post').'</dt>';
            $user_info['activity'][] = '<dd>'.$this->feather->utils->format_time($user['last_post']).'</dd>';
        }

        $user_info['activity'][] = '<dt>'.__('Registered').'</dt>';
        $user_info['activity'][] = '<dd>'.$this->feather->utils->format_time($user['registered'], true).'</dd>';

        $user_info = $this->hook->fire('parse_user_info', $user_info);

        return $user_info;
    }

    public function edit_essentials($id, $user)
    {
        $user_disp = array();

        $user_disp = $this->hook->fire('edit_essentials_start', $user_disp, $id, $user);

        if ($this->user->is_admmod) {
            if ($this->user->g_id == FEATHER_ADMIN || $this->user->g_mod_rename_users == '1') {
                $user_disp['username_field'] = '<label class="required"><strong>'.__('Username').' <span>'.__('Required').'</span></strong><br /><input type="text" name="req_username" value="'.Utils::escape($user['username']).'" size="25" maxlength="25" /><br /></label>'."\n";
            } else {
                $user_disp['username_field'] = '<p>'.sprintf(__('Username info'), Utils::escape($user['username'])).'</p>'."\n";
            }

            $user_disp['email_field'] = '<label class="required"><strong>'.__('Email').' <span>'.__('Required').'</span></strong><br /><input type="text" name="req_email" value="'.Utils::escape($user['email']).'" size="40" maxlength="80" /><br /></label><p><span class="email"><a href="'.Url::get('email/'.$id.'/').'">'.__('Send email').'</a></span></p>'."\n";
        } else {
            $user_disp['username_field'] = '<p>'.__('Username').': '.Utils::escape($user['username']).'</p>'."\n";

            if ($this->config['o_regs_verify'] == '1') {
                $user_disp['email_field'] = '<p>'.sprintf(__('Email info'), Utils::escape($user['email']).' - <a href="'.Url::get('user/'.$id.'/action/change_email/').'">'.__('Change email').'</a>').'</p>'."\n";
            } else {
                $user_disp['email_field'] = '<label class="required"><strong>'.__('Email').' <span>'.__('Required').'</span></strong><br /><input type="text" name="req_email" value="'.$user['email'].'" size="40" maxlength="80" /><br /></label>'."\n";
            }
        }

        $user_disp['posts_field'] = '';
        $posts_actions = array();

        if ($this->user->g_id == FEATHER_ADMIN) {
            $user_disp['posts_field'] .= '<label>'.__('Posts').'<br /><input type="text" name="num_posts" value="'.$user['num_posts'].'" size="8" maxlength="8" /><br /></label>';
        } elseif ($this->config['o_show_post_count'] == '1' || $this->user->is_admmod) {
            $posts_actions[] = sprintf(__('Posts info'), Utils::forum_number_format($user['num_posts']));
        }

        if ($this->user->g_search == '1' || $this->user->g_id == FEATHER_ADMIN) {
            $posts_actions[] = '<a href="'.Url::get('search/?action=show_user_topics&amp;user_id='.$id).'">'.__('Show topics').'</a>';
            $posts_actions[] = '<a href="'.Url::get('search/?action=show_user_posts&amp;user_id='.$id).'">'.__('Show posts').'</a>';

            if ($this->config['o_topic_subscriptions'] == '1') {
                $posts_actions[] = '<a href="'.Url::get('search/?action=show_subscriptions&amp;user_id='.$id).'">'.__('Show subscriptions').'</a>';
            }
        }

        $user_disp['posts_field'] .= (!empty($posts_actions) ? '<p class="actions">'.implode(' - ', $posts_actions).'</p>' : '')."\n";

        $user_disp = $this->hook->fire('edit_essentials', $user_disp);

        return $user_disp;
    }

    public function get_group_list($user)
    {
        $output = '';

        $user = $this->hook->fire('get_group_list_start', $user);

        $result['select'] = array('g_id', 'g_title');

        $result = DB::for_table('groups')
            ->select_many($result['select'])
            ->where_not_equal('g_id', FEATHER_GUEST)
            ->order_by('g_title');
        $result = $this->hook->fireDB('get_group_list_query', $result);
        $result = $result->find_many();

        foreach ($result as $cur_group) {
            if ($cur_group['g_id'] == $user['g_id'] || ($cur_group['g_id'] == $this->config['o_default_user_group'] && $user['g_id'] == '')) {
                $output .= "\t\t\t\t\t\t\t\t".'<option value="'.$cur_group['g_id'].'" selected="selected">'.Utils::escape($cur_group['g_title']).'</option>'."\n";
            } else {
                $output .= "\t\t\t\t\t\t\t\t".'<option value="'.$cur_group['g_id'].'">'.Utils::escape($cur_group['g_title']).'</option>'."\n";
            }
        }

        $output = $this->hook->fire('get_group_list', $output);

        return $output;
    }

    public function get_forum_list($id)
    {
        $output = '';

        $id = $this->hook->fire('get_forum_list_start', $id);

        $result['select'] = array('cid' => 'c.id', 'c.cat_name', 'fid' => 'f.id', 'f.forum_name', 'f.moderators');
        $result['order_by'] = array('c.disp_position', 'c.id', 'f.disp_position');

        $result = DB::for_table('categories')
            ->table_alias('c')
            ->select_many($result['select'])
            ->inner_join('forums', array('c.id', '=', 'f.cat_id'), 'f')
            ->where_null('f.redirect_url')
            ->order_by_many($result['order_by']);
        $result = $this->hook->fireDB('get_forum_list', $result);
        $result = $result->find_many();

        $cur_category = 0;
        foreach($result as $cur_forum) {
            if ($cur_forum['cid'] != $cur_category) {
                // A new category since last iteration?
                if ($cur_category) {
                    $output .= "\n\t\t\t\t\t\t\t\t".'</div>';
                }

                if ($cur_category != 0) {
                    $output .= "\n\t\t\t\t\t\t\t".'</div>'."\n";
                }

                $output .= "\t\t\t\t\t\t\t".'<div class="conl">'."\n\t\t\t\t\t\t\t\t".'<p><strong>'.Utils::escape($cur_forum['cat_name']).'</strong></p>'."\n\t\t\t\t\t\t\t\t".'<div class="rbox">';
                $cur_category = $cur_forum['cid'];
            }

            $moderators = ($cur_forum['moderators'] != '') ? unserialize($cur_forum['moderators']) : array();

            $output .= "\n\t\t\t\t\t\t\t\t\t".'<label><input type="checkbox" name="moderator_in['.$cur_forum['fid'].']" value="1"'.((in_array($id, $moderators)) ? ' checked="checked"' : '').' />'.Utils::escape($cur_forum['forum_name']).'<br /></label>'."\n";
        }

        $output = $this->hook->fire('get_forum_list', $output);

        return $output;
    }

    //
    // Check username
    //
    public function check_username($username, $errors, $exclude_id = null)
    {
        global $errors, $feather_bans;

        // Include UTF-8 function
        require_once $this->feather->forum_env['FEATHER_ROOT'].'featherbb/Helpers/utf8/strcasecmp.php';

        load_textdomain('featherbb', $this->feather->forum_env['FEATHER_ROOT'].'featherbb/lang/'.$this->feather->user->language.'/register.mo');
        load_textdomain('featherbb', $this->feather->forum_env['FEATHER_ROOT'].'featherbb/lang/'.$this->feather->user->language.'/prof_reg.mo');

        // Convert multiple whitespace characters into one (to prevent people from registering with indistinguishable usernames)
        $username = preg_replace('%\s+%s', ' ', $username);

        // Validate username
        if (Utils::strlen($username) < 2) {
            $errors[] = __('Username too short');
        } elseif (Utils::strlen($username) > 25) { // This usually doesn't happen since the form element only accepts 25 characters
            $errors[] = __('Username too long');
        } elseif (!strcasecmp($username, 'Guest') || !utf8_strcasecmp($username, __('Guest'))) {
            $errors[] = __('Username guest');
        } elseif (filter_var($username, FILTER_VALIDATE_IP)) {
            $errors[] = __('Username IP');
        } elseif ((strpos($username, '[') !== false || strpos($username, ']') !== false) && strpos($username, '\'') !== false && strpos($username, '"') !== false) {
            $errors[] = __('Username reserved chars');
        } elseif (preg_match('%(?:\[/?(?:b|u|s|ins|del|em|i|h|colou?r|quote|code|img|url|email|list|\*|topic|post|forum|user)\]|\[(?:img|url|quote|list)=)%i', $username)) {
            $errors[] = __('Username BBCode');
        }

        // Check username for any censored words
        if ($this->feather->forum_settings['o_censoring'] == '1' && Utils::censor($username) != $username) {
            $errors[] = __('Username censor');
        }

        // Check that the username (or a too similar username) is not already registered
        $query = (!is_null($exclude_id)) ? ' AND id!='.$exclude_id : '';

        $result = \DB::for_table('online')->raw_query('SELECT username FROM '.$this->feather->forum_settings['db_prefix'].'users WHERE (UPPER(username)=UPPER(:username1) OR UPPER(username)=UPPER(:username2)) AND id>1'.$query, array(':username1' => $username, ':username2' => Utils::ucp_preg_replace('%[^\p{L}\p{N}]%u', '', $username)))->find_one();

        if ($result) {
            $busy = $result['username'];
            $errors[] = __('Username dupe 1').' '.Utils::escape($busy).'. '.__('Username dupe 2');
        }

        // Check username for any banned usernames
        foreach ($feather_bans as $cur_ban) {
            if ($cur_ban['username'] != '' && utf8_strtolower($username) == utf8_strtolower($cur_ban['username'])) {
                $errors[] = __('Banned username');
                break;
            }
        }

        return $errors;
    }
}
