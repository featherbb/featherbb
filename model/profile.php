<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace model;

use DB;

class profile
{
    public function __construct()
    {
        $this->feather = \Slim\Slim::getInstance();
        $this->start = $this->feather->start;
        $this->config = $this->feather->config;
        $this->user = $this->feather->user;
        $this->request = $this->feather->request;
    }
 
    public function change_pass($id)
    {
        global $lang_profile, $lang_common, $lang_prof_reg;

        if ($this->request->get('key')) {
            // If the user is already logged in we shouldn't be here :)
            if (!$this->user->is_guest) {
                header('Location: '.get_base_url());
                exit;
            }

            $key = $this->request->get('key');

            $cur_user = DB::for_table('users')
                ->where('id', $id)
                ->find_one();

            if ($key == '' || $key != $cur_user['activate_key']) {
                message($lang_profile['Pass key bad'].' <a href="mailto:'.feather_escape($this->config['o_admin_email']).'">'.feather_escape($this->config['o_admin_email']).'</a>.');
            } else {
                DB::for_table('users')
                    ->where('id', $id)
                    ->find_one()
                    ->set('password', $cur_user['activate_string'])
                    ->set_expr('activate_string', 'NULL')
                    ->set_expr('activate_key', 'NULL')
                    ->save();

                message($lang_profile['Pass updated'], true);
            }
        }

        // Make sure we are allowed to change this user's password
        if ($this->user->id != $id) {
            if (!$this->user->is_admmod) { // A regular user trying to change another user's password?
                message($lang_common['No permission'], '403');
            } elseif ($this->user->g_moderator == '1') {
                // A moderator trying to change a user's password?

                $select_change_password = array('u.group_id', 'g.g_moderator');

                $user = DB::for_table('users')
                    ->table_alias('u')
                    ->select_many($select_change_password)
                    ->inner_join('groups', array('g.g_id', '=', 'u.group_id'), 'g')
                    ->where('u.id', $id)
                    ->find_one();

                if (!$user) {
                    message($lang_common['Bad request'], '404');
                }

                if ($this->user->g_mod_edit_users == '0' || $this->user->g_mod_change_passwords == '0' || $user['group_id'] == FEATHER_ADMIN || $user['g_moderator'] == '1') {
                    message($lang_common['No permission'], '403');
                }
            }
        }

        if ($this->request->isPost()) {
            $old_password = $this->request->post('req_old_password') ? feather_trim($this->request->post('req_old_password')) : '';
            $new_password1 = feather_trim($this->request->post('req_new_password1'));
            $new_password2 = feather_trim($this->request->post('req_new_password2'));

            if ($new_password1 != $new_password2) {
                message($lang_prof_reg['Pass not match']);
            }
            if (feather_strlen($new_password1) < 6) {
                message($lang_prof_reg['Pass too short']);
            }

            $cur_user = DB::for_table('users')
                ->where('id', $id)
                ->find_one();

            $authorized = false;

            if (!empty($cur_user['password'])) {
                $old_password_hash = feather_hash($old_password);

                if ($cur_user['password'] == $old_password_hash || $this->user->is_admmod) {
                    $authorized = true;
                }
            }

            if (!$authorized) {
                message($lang_profile['Wrong pass']);
            }

            $new_password_hash = feather_hash($new_password1);

            DB::for_table('users')->where('id', $id)
                ->find_one()
                ->set('password', $new_password_hash)
                ->save();

            if ($this->user->id == $id) {
                feather_setcookie($this->user->id, $new_password_hash, time() + $this->config['o_timeout_visit']);
            }

            redirect(get_link('user/'.$id.'/section/essentials/'), $lang_profile['Pass updated redirect']);
        }
    }

    public function change_email($id)
    {
        global $lang_profile, $lang_common, $lang_prof_reg;

        // Make sure we are allowed to change this user's email
        if ($this->user->id != $id) {
            if (!$this->user->is_admmod) { // A regular user trying to change another user's email?
                message($lang_common['No permission'], '403');
            } elseif ($this->user->g_moderator == '1') {
                // A moderator trying to change a user's email?

                $select_change_mail = array('u.group_id', 'g.g_moderator');

                $user = DB::for_table('users')
                    ->table_alias('u')
                    ->select_many($select_change_mail)
                    ->inner_join('groups', array('g.g_id', '=', 'u.group_id'), 'g')
                    ->where('u.id', $id)
                    ->find_one();

                if (!$user) {
                    message($lang_common['Bad request'], '404');
                }

                if ($this->user->g_mod_edit_users == '0' || $this->user->g_mod_change_passwords == '0' || $user['group_id'] == FEATHER_ADMIN || $user['g_moderator'] == '1') {
                    message($lang_common['No permission'], '403');
                }
            }
        }

        if ($this->request->get('key')) {
            $key = $this->request->get('key');

            $new_email_key = DB::for_table('users')
                ->where('id', $id)
                ->find_one_col('activate_key');

            if ($key == '' || $key != $new_email_key) {
                message($lang_profile['Email key bad'].' <a href="mailto:'.feather_escape($this->config['o_admin_email']).'">'.feather_escape($this->config['o_admin_email']).'</a>.');
            } else {
                DB::for_table('users')
                    ->where('id', $id)
                    ->find_one()
                    ->set_expr('email', 'activate_string')
                    ->set_expr('activate_string', 'NULL')
                    ->set_expr('activate_key', 'NULL')
                    ->save();

                message($lang_profile['Email updated'], true);
            }
        } elseif ($this->request->isPost()) {
            if (feather_hash($this->request->post('req_password')) !== $this->user->password) {
                message($lang_profile['Wrong pass']);
            }

            require FEATHER_ROOT.'include/email.php';

            // Validate the email address
            $new_email = strtolower(feather_trim($this->request->post('req_new_email')));
            if (!is_valid_email($new_email)) {
                message($lang_common['Invalid email']);
            }

            // Check if it's a banned email address
            if (is_banned_email($new_email)) {
                if ($this->config['p_allow_banned_email'] == '0') {
                    message($lang_prof_reg['Banned email']);
                } elseif ($this->config['o_mailing_list'] != '') {
                    // Load the "banned email change" template
                    $mail_tpl = trim(file_get_contents(FEATHER_ROOT.'lang/'.$this->user->language.'/mail_templates/banned_email_change.tpl'));

                    // The first row contains the subject
                    $first_crlf = strpos($mail_tpl, "\n");
                    $mail_subject = trim(substr($mail_tpl, 8, $first_crlf-8));
                    $mail_message = trim(substr($mail_tpl, $first_crlf));

                    $mail_message = str_replace('<username>', $this->user->username, $mail_message);
                    $mail_message = str_replace('<email>', $new_email, $mail_message);
                    $mail_message = str_replace('<profile_url>', get_link('user/'.$id.'/'), $mail_message);
                    $mail_message = str_replace('<board_mailer>', $this->config['o_board_title'], $mail_message);

                    pun_mail($this->config['o_mailing_list'], $mail_subject, $mail_message);
                }
            }

            // Check if someone else already has registered with that email address
            $select_change_mail = array('id', 'username');

            $result = DB::for_table('users')
                ->select_many($select_change_mail)
                ->where('email', $new_email)
                ->find_many();

            if ($result) {
                if ($this->config['p_allow_dupe_email'] == '0') {
                    message($lang_prof_reg['Dupe email']);
                } elseif ($this->config['o_mailing_list'] != '') {
                    foreach($result as $cur_dupe) {
                        $dupe_list[] = $cur_dupe['username'];
                    }

                    // Load the "dupe email change" template
                    $mail_tpl = trim(file_get_contents(FEATHER_ROOT.'lang/'.$this->user->language.'/mail_templates/dupe_email_change.tpl'));

                    // The first row contains the subject
                    $first_crlf = strpos($mail_tpl, "\n");
                    $mail_subject = trim(substr($mail_tpl, 8, $first_crlf-8));
                    $mail_message = trim(substr($mail_tpl, $first_crlf));

                    $mail_message = str_replace('<username>', $this->user->username, $mail_message);
                    $mail_message = str_replace('<dupe_list>', implode(', ', $dupe_list), $mail_message);
                    $mail_message = str_replace('<profile_url>', get_link('user/'.$id.'/'), $mail_message);
                    $mail_message = str_replace('<board_mailer>', $this->config['o_board_title'], $mail_message);

                    pun_mail($this->config['o_mailing_list'], $mail_subject, $mail_message);
                }
            }


            $new_email_key = random_pass(8);

            // Update the user
            $update_user = array(
                'activate_string' => $new_email,
                'activate_key'  => $new_email_key,
            );

            DB::for_table('users')->where('id', tid)
                ->find_one()
                ->set($update_user)
                ->save();

            // Load the "activate email" template
            $mail_tpl = trim(file_get_contents(FEATHER_ROOT.'lang/'.$this->user->language.'/mail_templates/activate_email.tpl'));

            // The first row contains the subject
            $first_crlf = strpos($mail_tpl, "\n");
            $mail_subject = trim(substr($mail_tpl, 8, $first_crlf-8));
            $mail_message = trim(substr($mail_tpl, $first_crlf));

            $mail_message = str_replace('<username>', $this->user->username, $mail_message);
            $mail_message = str_replace('<base_url>', get_base_url(), $mail_message);
            $mail_message = str_replace('<activation_url>', get_link('user/'.$id.'/action/change_email/?key='.$new_email_key), $mail_message);
            $mail_message = str_replace('<board_mailer>', $this->config['o_board_title'], $mail_message);

            pun_mail($new_email, $mail_subject, $mail_message);

            message($lang_profile['Activate email sent'].' <a href="mailto:'.feather_escape($this->config['o_admin_email']).'">'.feather_escape($this->config['o_admin_email']).'</a>.', true);
        }
    }

    public function upload_avatar($id, $files_data)
    {
        global $lang_profile;

        if (!isset($files_data['req_file'])) {
            message($lang_profile['No file']);
        }

        $uploaded_file = $files_data['req_file'];

        // Make sure the upload went smooth
        if (isset($uploaded_file['error'])) {
            switch ($uploaded_file['error']) {
                case 1: // UPLOAD_ERR_INI_SIZE
                case 2: // UPLOAD_ERR_FORM_SIZE
                    message($lang_profile['Too large ini']);
                    break;

                case 3: // UPLOAD_ERR_PARTIAL
                    message($lang_profile['Partial upload']);
                    break;

                case 4: // UPLOAD_ERR_NO_FILE
                    message($lang_profile['No file']);
                    break;

                case 6: // UPLOAD_ERR_NO_TMP_DIR
                    message($lang_profile['No tmp directory']);
                    break;

                default:
                    // No error occured, but was something actually uploaded?
                    if ($uploaded_file['size'] == 0) {
                        message($lang_profile['No file']);
                    }
                    break;
            }
        }

        if (is_uploaded_file($uploaded_file['tmp_name'])) {
            // Preliminary file check, adequate in most cases
            $allowed_types = array('image/gif', 'image/jpeg', 'image/pjpeg', 'image/png', 'image/x-png');
            if (!in_array($uploaded_file['type'], $allowed_types)) {
                message($lang_profile['Bad type']);
            }

            // Make sure the file isn't too big
            if ($uploaded_file['size'] > $this->config['o_avatars_size']) {
                message($lang_profile['Too large'].' '.forum_number_format($this->config['o_avatars_size']).' '.$lang_profile['bytes'].'.');
            }

            // Move the file to the avatar directory. We do this before checking the width/height to circumvent open_basedir restrictions
            if (!@move_uploaded_file($uploaded_file['tmp_name'], FEATHER_ROOT.$this->config['o_avatars_dir'].'/'.$id.'.tmp')) {
                message($lang_profile['Move failed'].' <a href="mailto:'.feather_escape($this->config['o_admin_email']).'">'.feather_escape($this->config['o_admin_email']).'</a>.');
            }

            list($width, $height, $type, ) = @getimagesize(FEATHER_ROOT.$this->config['o_avatars_dir'].'/'.$id.'.tmp');

            // Determine type
            if ($type == IMAGETYPE_GIF) {
                $extension = '.gif';
            } elseif ($type == IMAGETYPE_JPEG) {
                $extension = '.jpg';
            } elseif ($type == IMAGETYPE_PNG) {
                $extension = '.png';
            } else {
                // Invalid type
                @unlink(FEATHER_ROOT.$this->config['o_avatars_dir'].'/'.$id.'.tmp');
                message($lang_profile['Bad type']);
            }

            // Now check the width/height
            if (empty($width) || empty($height) || $width > $this->config['o_avatars_width'] || $height > $this->config['o_avatars_height']) {
                @unlink(FEATHER_ROOT.$this->config['o_avatars_dir'].'/'.$id.'.tmp');
                message($lang_profile['Too wide or high'].' '.$this->config['o_avatars_width'].'x'.$this->config['o_avatars_height'].' '.$lang_profile['pixels'].'.');
            }

            // Delete any old avatars and put the new one in place
            delete_avatar($id);
            @rename(FEATHER_ROOT.$this->config['o_avatars_dir'].'/'.$id.'.tmp', FEATHER_ROOT.$this->config['o_avatars_dir'].'/'.$id.$extension);
            @chmod(FEATHER_ROOT.$this->config['o_avatars_dir'].'/'.$id.$extension, 0644);
        } else {
            message($lang_profile['Unknown failure']);
        }

        redirect(get_link('user/'.$id.'/section/personality/'), $lang_profile['Avatar upload redirect']);
    }

    public function update_group_membership($id)
    {
        global $lang_profile;

        $new_group_id = intval($this->request->post('group_id'));

        $old_group_id = DB::for_table('users')
            ->where('id', $id)
            ->find_one_col('group_id');

        DB::for_table('users')->where('id', $id)
            ->find_one()
            ->set('group_id', $new_group_id)
            ->save();

        // Regenerate the users info cache
        if (!defined('FORUM_CACHE_FUNCTIONS_LOADED')) {
            require FEATHER_ROOT.'include/cache.php';
        }

        generate_users_info_cache();

        if ($old_group_id == FEATHER_ADMIN || $new_group_id == FEATHER_ADMIN) {
            generate_admins_cache();
        }

        $new_group_mod = DB::for_table('groups')
            ->where('g_id', $new_group_id)
            ->find_one_col('g_moderator');

        // If the user was a moderator or an administrator, we remove him/her from the moderator list in all forums as well
        if ($new_group_id != FEATHER_ADMIN && $new_group_mod != '1') {

            $select_mods = array('id', 'moderators');

            $result = DB::for_table('forums')
                ->select_many($select_mods)
                ->find_many();

            foreach($result as $cur_forum) {
                $cur_moderators = ($cur_forum['moderators'] != '') ? unserialize($cur_forum['moderators']) : array();

                if (in_array($id, $cur_moderators)) {
                    $username = array_search($id, $cur_moderators);
                    unset($cur_moderators[$username]);

                    if (!empty($cur_moderators)) {
                        DB::for_table('forums')->where('id', $cur_forum['id'])
                            ->find_one()
                            ->set('moderators', serialize($cur_moderators))
                            ->save();
                    } else {
                        DB::for_table('forums')->where('id', $cur_forum['id'])
                            ->find_one()
                            ->set_expr('moderators', 'NULL')
                            ->save();
                    }
                }
            }
        }

        redirect(get_link('user/'.$id.'/section/admin/'), $lang_profile['Group membership redirect']);
    }

    public function get_username($id)
    {
        // Get the username of the user we are processing
        $username = DB::for_table('users')
            ->where('id', $id)
            ->find_one_col('username');

        return $username;
    }

    public function update_mod_forums($id)
    {
        global $lang_profile;

        $username = $this->get_username($id);

        $moderator_in = ($this->request->post('moderator_in')) ? array_keys($this->request->post('moderator_in')) : array();

        // Loop through all forums
        $select_mods = array('id', 'moderators');

        $result = DB::for_table('forums')
            ->select_many($select_mods)
            ->find_many();

        foreach($result as $cur_forum) {
            $cur_moderators = ($cur_forum['moderators'] != '') ? unserialize($cur_forum['moderators']) : array();
            // If the user should have moderator access (and he/she doesn't already have it)
            if (in_array($cur_forum['id'], $moderator_in) && !in_array($id, $cur_moderators)) {
                $cur_moderators[$username] = $id;
                uksort($cur_moderators, 'utf8_strcasecmp');

                DB::for_table('forums')->where('id', $cur_forum['id'])
                    ->find_one()
                    ->set('moderators', serialize($cur_moderators))
                    ->save();
            }
            // If the user shouldn't have moderator access (and he/she already has it)
            elseif (!in_array($cur_forum['id'], $moderator_in) && in_array($id, $cur_moderators)) {
                unset($cur_moderators[$username]);

                if (!empty($cur_moderators)) {
                    DB::for_table('forums')->where('id', $cur_forum['id'])
                        ->find_one()
                        ->set('moderators', serialize($cur_moderators))
                        ->save();
                } else {
                    DB::for_table('forums')->where('id', $cur_forum['id'])
                        ->find_one()
                        ->set_expr('moderators', 'NULL')
                        ->save();
                }
            }
        }

        redirect(get_link('user/'.$id.'/section/admin/'), $lang_profile['Update forums redirect']);
    }

    public function ban_user($id)
    {
        global $lang_profile;

        // Get the username of the user we are banning
        $username = $this->get_username($id);

        // Check whether user is already banned
        $ban_id = DB::for_table('bans')
            ->where('username', $username)
            ->order_by_expr('expire IS NULL DESC')
            ->order_by_desc('expire')
            ->find_one_col('id');

        if ($ban_id) {
            redirect(get_link('admin/bans/edit/'.$ban_id.'/'), $lang_profile['Ban redirect']);
        } else {
            redirect(get_link('admin/bans/add/'.$id.'/'), $lang_profile['Ban redirect']);
        }
    }

    public function promote_user($id)
    {
        global $lang_profile, $lang_common;

        $pid = $this->request->get('pid') ? intval($this->request->get('pid')) : 0;

        // Find the group ID to promote the user to
        $next_group_id = DB::for_table('groups')
            ->table_alias('g')
            ->inner_join('users', array('u.group_id', '=', 'g.g_id'), 'u')
            ->where('u.id', $id)
            ->find_one_col('g.g_promote_next_group');
        
        if (!$next_group_id) {
            message($lang_common['Bad request'], '404');
        }

        // Update the user
        DB::for_table('users')->where('id', $id)
            ->find_one()
            ->set('group_id', $next_group_id)
            ->save();
        
        redirect(get_link('post/'.$pid.'/#p'.$pid), $lang_profile['User promote redirect']);
    }

    public function delete_user($id)
    {
        global $lang_profile;

        // Get the username and group of the user we are deleting
        $select_info_delete_user = array('group_id', 'username');

        $result = DB::for_table('users')->where('id', $id)
            ->select_many($select_info_delete_user)
            ->find_one();
        
        $group_id = $result['group_id'];
        $username = $result['username'];
        
        if ($group_id == FEATHER_ADMIN) {
            message($lang_profile['No delete admin message']);
        }

        if ($this->request->post('delete_user_comply')) {
            // If the user is a moderator or an administrator, we remove him/her from the moderator list in all forums as well
            $group_mod = DB::for_table('groups')
                ->where('g_id', $group_id)
                ->find_one_col('g_moderator');

            if ($group_id == FEATHER_ADMIN || $group_mod == '1') {
                $select_info_delete_moderators = array('id', 'moderators');

                $result = DB::for_table('forums')
                    ->select_many($select_info_delete_moderators)
                    ->find_many();
                
                foreach($result as $cur_forum) {
                    $cur_moderators = ($cur_forum['moderators'] != '') ? unserialize($cur_forum['moderators']) : array();

                    if (in_array($id, $cur_moderators)) {
                        unset($cur_moderators[$username]);
                        
                        if (!empty($cur_moderators)) {
                            DB::for_table('forums')->where('id', $cur_forum['id'])
                                ->find_one()
                                ->set('moderators', serialize($cur_moderators))
                                ->save();
                        } else {
                            DB::for_table('forums')->where('id', $cur_forum['id'])
                                ->find_one()
                                ->set_expr('moderators', 'NULL')
                                ->save();
                        }
                    }
                }
            }

            // Delete any subscriptions
            DB::for_table('topic_subscriptions')
                ->where('user_id', $id)
                ->delete_many();
            DB::for_table('forum_subscriptions')
                ->where('user_id', $id)
                ->delete_many();

            // Remove him/her from the online list (if they happen to be logged in)
            DB::for_table('online')
                ->where('user_id', $id)
                ->delete_many();

            // Should we delete all posts made by this user?
            if ($this->request->post('delete_posts')) {
                require FEATHER_ROOT.'include/search_idx.php';
                // Hold on, this could take some time!
                @set_time_limit(0);

                // Find all posts made by this user
                $select_user_posts = array('p.id', 'p.topic_id', 't.forum_id');
                
                $result = DB::for_table('posts')
                    ->table_alias('p')
                    ->select_many($select_user_posts)
                    ->inner_join('topics', array('t.id', '=', 'p.topic_id'), 't')
                    ->inner_join('forums', array('f.id', '=', 't.forum_id'), 'f')
                    ->where('p.poster_id', $id)
                    ->find_many();
                
                if ($result) {
                    foreach($result as $cur_post) {
                        // Determine whether this post is the "topic post" or not
                        $result2 = DB::for_table('posts')
                            ->where('topic_id', $cur_post['topic_id'])
                            ->order_by('posted')
                            ->find_one_col('id');
                        
                        if ($this->db->result($result2) == $cur_post['id']) {
                            delete_topic($cur_post['topic_id']);
                        } else {
                            delete_post($cur_post['id'], $cur_post['topic_id']);
                        }

                        update_forum($cur_post['forum_id']);
                    }
                }
            } else {
                // Set all his/her posts to guest
                DB::for_table('posts')
                    ->where_in('poster_id', '1')
                    ->update_many('poster_id', $id);
            }

            // Delete the user
            DB::for_table('users')
                ->where('id', $id)
                ->delete_many();

            // Delete user avatar
            delete_avatar($id);

            // Regenerate the users info cache
            if (!defined('FORUM_CACHE_FUNCTIONS_LOADED')) {
                require FEATHER_ROOT.'include/cache.php';
            }

            generate_users_info_cache();

            if ($group_id == FEATHER_ADMIN) {
                generate_admins_cache();
            }

            redirect(get_base_url(), $lang_profile['User delete redirect']);
        }
    }

    public function fetch_user_group($id)
    {
        global $lang_common;

        $info = array();
        
        $select_fetch_user_group = array('old_username' => 'u.username', 'group_id' => 'u.group_id', 'is_moderator' => 'g.g_moderator');

        $info = DB::for_table('users')
            ->table_alias('u')
            ->select_many($select_fetch_user_group)
            ->left_outer_join('groups', array('g.g_id', '=', 'u.group_id'), 'g')
            ->where('u.id', $id)
            ->find_one();

        if (!$info) {
            message($lang_common['Bad request'], '404');
        }

        return $info;
    }

    public function update_profile($id, $info, $section)
    {
        global $lang_common, $lang_profile, $lang_prof_reg, $pd;

        $username_updated = false;

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
                    $languages = forum_list_langs();
                    $form['language'] = feather_trim($this->request->post('form_language'));
                    if (!in_array($form['language'], $languages)) {
                        message($lang_common['Bad request'], '404');
                    }
                }

                if ($this->user->is_admmod) {
                    $form['admin_note'] = feather_trim($this->request->post('admin_note'));

                    // Are we allowed to change usernames?
                    if ($this->user->g_id == FEATHER_ADMIN || ($this->user->g_moderator == '1' && $this->user->g_mod_rename_users == '1')) {
                        $form['username'] = feather_trim($this->request->post('req_username'));

                        if ($form['username'] != $info['old_username']) {
                            // Check username
                            require FEATHER_ROOT.'lang/'.$this->user->language.'/register.php';

                            $errors = '';
                            $errors = check_username($form['username'], $errors, $id);
                            if (!empty($errors)) {
                                message($errors[0]);
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
                    require FEATHER_ROOT.'include/email.php';

                    // Validate the email address
                    $form['email'] = strtolower(feather_trim($this->request->post('req_email')));
                    if (!is_valid_email($form['email'])) {
                        message($lang_common['Invalid email']);
                    }
                }

                break;
            }

            case 'personal':
            {
                $form = array(
                    'realname'        => $this->request->post('form_realname') ? feather_trim($this->request->post('form_realname')) : '',
                    'url'            => $this->request->post('form_url') ? feather_trim($this->request->post('form_url')) : '',
                    'location'        => $this->request->post('form_location') ? feather_trim($this->request->post('form_location')) : '',
                );

                // Add http:// if the URL doesn't contain it already (while allowing https://, too)
                if ($this->user->g_post_links == '1') {
                    if ($form['url'] != '') {
                        $url = url_valid($form['url']);

                        if ($url === false) {
                            message($lang_profile['Invalid website URL']);
                        }

                        $form['url'] = $url['url'];
                    }
                } else {
                    if (!empty($form['url'])) {
                        message($lang_profile['Website not allowed']);
                    }

                    $form['url'] = '';
                }

                if ($this->user->g_id == FEATHER_ADMIN) {
                    $form['title'] = feather_trim($this->request->post('title'));
                } elseif ($this->user->g_set_title == '1') {
                    $form['title'] = feather_trim($this->request->post('title'));

                    if ($form['title'] != '') {
                        // A list of words that the title may not contain
                        // If the language is English, there will be some duplicates, but it's not the end of the world
                        $forbidden = array('member', 'moderator', 'administrator', 'banned', 'guest', utf8_strtolower($lang_common['Member']), utf8_strtolower($lang_common['Moderator']), utf8_strtolower($lang_common['Administrator']), utf8_strtolower($lang_common['Banned']), utf8_strtolower($lang_common['Guest']));

                        if (in_array(utf8_strtolower($form['title']), $forbidden)) {
                            message($lang_profile['Forbidden title']);
                        }
                    }
                }

                break;
            }

            case 'messaging':
            {
                $form = array(
                    'jabber'        => feather_trim($this->request->post('form_jabber')),
                    'icq'            => feather_trim($this->request->post('form_icq')),
                    'msn'            => feather_trim($this->request->post('form_msn')),
                    'aim'            => feather_trim($this->request->post('form_aim')),
                    'yahoo'            => feather_trim($this->request->post('form_yahoo')),
                );

                // If the ICQ UIN contains anything other than digits it's invalid
                if (preg_match('%[^0-9]%', $form['icq'])) {
                    message($lang_prof_reg['Bad ICQ']);
                }

                break;
            }

            case 'personality':
            {
                $form = array();

                // Clean up signature from POST
                if ($this->config['o_signatures'] == '1') {
                    $form['signature'] = feather_linebreaks(feather_trim($this->request->post('signature')));

                    // Validate signature
                    if (feather_strlen($form['signature']) > $this->config['p_sig_length']) {
                        message(sprintf($lang_prof_reg['Sig too long'], $this->config['p_sig_length'], feather_strlen($form['signature']) - $this->config['p_sig_length']));
                    } elseif (substr_count($form['signature'], "\n") > ($this->config['p_sig_lines']-1)) {
                        message(sprintf($lang_prof_reg['Sig too many lines'], $this->config['p_sig_lines']));
                    } elseif ($form['signature'] && $this->config['p_sig_all_caps'] == '0' && is_all_uppercase($form['signature']) && !$this->user->is_admmod) {
                        $form['signature'] = utf8_ucwords(utf8_strtolower($form['signature']));
                    }

                    // Validate BBCode syntax
                    if ($this->config['p_sig_bbcode'] == '1') {
                        require FEATHER_ROOT.'include/parser.php';

                        $errors = array();

                        $form['signature'] = preparse_bbcode($form['signature'], $errors, true);

                        if (count($errors) > 0) {
                            message('<ul><li>'.implode('</li><li>', $errors).'</li></ul>');
                        }
                    }
                }

                break;
            }

            case 'display':
            {
                $form = array(
                    'disp_topics'        => feather_trim($this->request->post('form_disp_topics')),
                    'disp_posts'        => feather_trim($this->request->post('form_disp_posts')),
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
                    $styles = forum_list_styles();
                    $form['style'] = feather_trim($this->request->post('form_style'));
                    if (!in_array($form['style'], $styles)) {
                        message($lang_common['Bad request'], '404');
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
                message($lang_common['Bad request'], '404');
        }


        // Single quotes around non-empty values and nothing for empty values
        $temp = array();
        foreach ($form as $key => $input) {
            $temp[$key] = $input;
        }

        if (empty($temp)) {
            message($lang_common['Bad request'], '404');
        }

        DB::for_table('users')->where('id', $id)
            ->find_one()
            ->set($temp)
            ->save();

        // If we changed the username we have to update some stuff
        if ($username_updated) {
            $bans_updated = DB::for_table('bans')->where('username', $info['old_username'])
                                ->update_many('username', $form['username']);

            DB::for_table('posts')
                ->where('poster_id', $id)
                ->update_many('poster', $form['username']);

            DB::for_table('posts')
                ->where('edited_by', $info['old_username'])
                ->update_many('edited_by', $form['username']);

            DB::for_table('topics')
                ->where('poster', $info['old_username'])
                ->update_many('poster', $form['username']);

            DB::for_table('topics')
                ->where('last_poster', $info['old_username'])
                ->update_many('last_poster', $form['username']);

            DB::for_table('forums')
                ->where('last_poster', $info['old_username'])
                ->update_many('last_poster', $form['username']);

            DB::for_table('online')
                ->where('ident', $info['old_username'])
                ->update_many('ident', $form['username']);

            // If the user is a moderator or an administrator we have to update the moderator lists
            $group_id = DB::for_table('users')
                ->where('id', $id)
                ->find_one_col('group_id');

            $group_mod = DB::for_table('groups')
                ->where('g_id', $group_id)
                ->find_one_col('g_moderator');

            if ($group_id == FEATHER_ADMIN || $group_mod == '1') {
                $select_mods = array('id', 'moderators');

                $result = DB::for_table('forums')
                    ->select_many($select_mods)
                    ->find_many();

                foreach($result as $cur_forum) {
                    $cur_moderators = ($cur_forum['moderators'] != '') ? unserialize($cur_forum['moderators']) : array();

                    if (in_array($id, $cur_moderators)) {
                        unset($cur_moderators[$info['old_username']]);
                        $cur_moderators[$form['username']] = $id;
                        uksort($cur_moderators, 'utf8_strcasecmp');

                        DB::for_table('forums')->where('id', $cur_forum['id'])
                            ->find_one()
                            ->set('moderators', serialize($cur_moderators))
                            ->save();
                    }
                }
            }

            // Regenerate the users info cache
            if (!defined('FORUM_CACHE_FUNCTIONS_LOADED')) {
                require FEATHER_ROOT.'include/cache.php';
            }

            generate_users_info_cache();

            // Check if the bans table was updated and regenerate the bans cache when needed
            if ($bans_updated) {
                generate_bans_cache();
            }
        }

        redirect(get_link('user/'.$id.'/section/'.$section.'/'), $lang_profile['Profile redirect']);
    }

    public function get_user_info($id)
    {
        global $lang_common;

        $select_get_user_info = array('u.id', 'u.username', 'u.email', 'u.title', 'u.realname', 'u.url', 'u.jabber', 'u.icq', 'u.msn', 'u.aim', 'u.yahoo', 'u.location', 'u.signature', 'u.disp_topics', 'u.disp_posts', 'u.email_setting', 'u.notify_with_post', 'u.auto_notify', 'u.show_smilies', 'u.show_img', 'u.show_img_sig', 'u.show_avatars', 'u.show_sig', 'u.timezone', 'u.dst', 'u.language', 'u.style', 'u.num_posts', 'u.last_post', 'u.registered', 'u.registration_ip', 'u.admin_note', 'u.date_format', 'u.time_format', 'u.last_visit', 'g.g_id', 'g.g_user_title', 'g.g_moderator');

        $user = DB::for_table('users')
            ->table_alias('u')
            ->select_many($select_get_user_info)
            ->left_outer_join('groups', array('g.g_id', '=', 'u.group_id'), 'g')
            ->where('u.id', $id)
            ->find_one();

        if (!$user) {
            message($lang_common['Bad request'], '404');
        }

        return $user;
    }

    public function parse_user_info($user)
    {
        global $lang_common, $lang_profile;

        $user_info = array();

        $user_info['personal'][] = '<dt>'.$lang_common['Username'].'</dt>';
        $user_info['personal'][] = '<dd>'.feather_escape($user['username']).'</dd>';

        $user_title_field = get_title($user);
        $user_info['personal'][] = '<dt>'.$lang_common['Title'].'</dt>';
        $user_info['personal'][] = '<dd>'.(($this->config['o_censoring'] == '1') ? censor_words($user_title_field) : $user_title_field).'</dd>';

        if ($user['realname'] != '') {
            $user_info['personal'][] = '<dt>'.$lang_profile['Realname'].'</dt>';
            $user_info['personal'][] = '<dd>'.feather_escape(($this->config['o_censoring'] == '1') ? censor_words($user['realname']) : $user['realname']).'</dd>';
        }

        if ($user['location'] != '') {
            $user_info['personal'][] = '<dt>'.$lang_profile['Location'].'</dt>';
            $user_info['personal'][] = '<dd>'.feather_escape(($this->config['o_censoring'] == '1') ? censor_words($user['location']) : $user['location']).'</dd>';
        }

        if ($user['url'] != '') {
            $user['url'] = feather_escape(($this->config['o_censoring'] == '1') ? censor_words($user['url']) : $user['url']);
            $user_info['personal'][] = '<dt>'.$lang_profile['Website'].'</dt>';
            $user_info['personal'][] = '<dd><span class="website"><a href="'.$user['url'].'" rel="nofollow">'.$user['url'].'</a></span></dd>';
        }

        if ($user['email_setting'] == '0' && !$this->user->is_guest && $this->user->g_send_email == '1') {
            $user['email_field'] = '<a href="mailto:'.feather_escape($user['email']).'">'.feather_escape($user['email']).'</a>';
        } elseif ($user['email_setting'] == '1' && !$this->user->is_guest && $this->user->g_send_email == '1') {
            $user['email_field'] = '<a href="'.get_link('email/'.$user['id'].'/').'">'.$lang_common['Send email'].'</a>';
        } else {
            $user['email_field'] = '';
        }
        if ($user['email_field'] != '') {
            $user_info['personal'][] = '<dt>'.$lang_common['Email'].'</dt>';
            $user_info['personal'][] = '<dd><span class="email">'.$user['email_field'].'</span></dd>';
        }

        if ($user['jabber'] != '') {
            $user_info['messaging'][] = '<dt>'.$lang_profile['Jabber'].'</dt>';
            $user_info['messaging'][] = '<dd>'.feather_escape(($this->config['o_censoring'] == '1') ? censor_words($user['jabber']) : $user['jabber']).'</dd>';
        }

        if ($user['icq'] != '') {
            $user_info['messaging'][] = '<dt>'.$lang_profile['ICQ'].'</dt>';
            $user_info['messaging'][] = '<dd>'.$user['icq'].'</dd>';
        }

        if ($user['msn'] != '') {
            $user_info['messaging'][] = '<dt>'.$lang_profile['MSN'].'</dt>';
            $user_info['messaging'][] = '<dd>'.feather_escape(($this->config['o_censoring'] == '1') ? censor_words($user['msn']) : $user['msn']).'</dd>';
        }

        if ($user['aim'] != '') {
            $user_info['messaging'][] = '<dt>'.$lang_profile['AOL IM'].'</dt>';
            $user_info['messaging'][] = '<dd>'.feather_escape(($this->config['o_censoring'] == '1') ? censor_words($user['aim']) : $user['aim']).'</dd>';
        }

        if ($user['yahoo'] != '') {
            $user_info['messaging'][] = '<dt>'.$lang_profile['Yahoo'].'</dt>';
            $user_info['messaging'][] = '<dd>'.feather_escape(($this->config['o_censoring'] == '1') ? censor_words($user['yahoo']) : $user['yahoo']).'</dd>';
        }

        if ($this->config['o_avatars'] == '1') {
            $avatar_field = generate_avatar_markup($user['id']);
            if ($avatar_field != '') {
                $user_info['personality'][] = '<dt>'.$lang_profile['Avatar'].'</dt>';
                $user_info['personality'][] = '<dd>'.$avatar_field.'</dd>';
            }
        }

        if ($this->config['o_signatures'] == '1') {
            if (isset($parsed_signature)) {
                $user_info['personality'][] = '<dt>'.$lang_profile['Signature'].'</dt>';
                $user_info['personality'][] = '<dd><div class="postsignature postmsg">'.$parsed_signature.'</div></dd>';
            }
        }

        $posts_field = '';
        if ($this->config['o_show_post_count'] == '1' || $this->user->is_admmod) {
            $posts_field = forum_number_format($user['num_posts']);
        }
        if ($this->user->g_search == '1') {
            $quick_searches = array();
            if ($user['num_posts'] > 0) {
                $quick_searches[] = '<a href="'.get_link('search/?action=show_user_topics&amp;user_id='.$user['id']).'">'.$lang_profile['Show topics'].'</a>';
                $quick_searches[] = '<a href="'.get_link('search/?action=show_user_posts&amp;user_id='.$user['id']).'">'.$lang_profile['Show posts'].'</a>';
            }
            if ($this->user->is_admmod && $this->config['o_topic_subscriptions'] == '1') {
                $quick_searches[] = '<a href="'.get_link('search/?action=show_subscriptions&amp;user_id='.$user['id']).'">'.$lang_profile['Show subscriptions'].'</a>';
            }

            if (!empty($quick_searches)) {
                $posts_field .= (($posts_field != '') ? ' - ' : '').implode(' - ', $quick_searches);
            }
        }
        if ($posts_field != '') {
            $user_info['activity'][] = '<dt>'.$lang_common['Posts'].'</dt>';
            $user_info['activity'][] = '<dd>'.$posts_field.'</dd>';
        }

        if ($user['num_posts'] > 0) {
            $user_info['activity'][] = '<dt>'.$lang_common['Last post'].'</dt>';
            $user_info['activity'][] = '<dd>'.format_time($user['last_post']).'</dd>';
        }

        $user_info['activity'][] = '<dt>'.$lang_common['Registered'].'</dt>';
        $user_info['activity'][] = '<dd>'.format_time($user['registered'], true).'</dd>';

        return $user_info;
    }

    public function edit_essentials($id, $user)
    {
        global $lang_profile, $lang_common;

        $user_disp = array();

        if ($this->user->is_admmod) {
            if ($this->user->g_id == FEATHER_ADMIN || $this->user->g_mod_rename_users == '1') {
                $user_disp['username_field'] = '<label class="required"><strong>'.$lang_common['Username'].' <span>'.$lang_common['Required'].'</span></strong><br /><input type="text" name="req_username" value="'.feather_escape($user['username']).'" size="25" maxlength="25" /><br /></label>'."\n";
            } else {
                $user_disp['username_field'] = '<p>'.sprintf($lang_profile['Username info'], feather_escape($user['username'])).'</p>'."\n";
            }

            $user_disp['email_field'] = '<label class="required"><strong>'.$lang_common['Email'].' <span>'.$lang_common['Required'].'</span></strong><br /><input type="text" name="req_email" value="'.feather_escape($user['email']).'" size="40" maxlength="80" /><br /></label><p><span class="email"><a href="'.get_link('email/'.$id.'/').'">'.$lang_common['Send email'].'</a></span></p>'."\n";
        } else {
            $user_disp['username_field'] = '<p>'.$lang_common['Username'].': '.feather_escape($user['username']).'</p>'."\n";

            if ($this->config['o_regs_verify'] == '1') {
                $user_disp['email_field'] = '<p>'.sprintf($lang_profile['Email info'], feather_escape($user['email']).' - <a href="'.get_link('user/'.$id.'/action/change_email/').'">'.$lang_profile['Change email'].'</a>').'</p>'."\n";
            } else {
                $user_disp['email_field'] = '<label class="required"><strong>'.$lang_common['Email'].' <span>'.$lang_common['Required'].'</span></strong><br /><input type="text" name="req_email" value="'.$user['email'].'" size="40" maxlength="80" /><br /></label>'."\n";
            }
        }

        $user_disp['posts_field'] = '';
        $posts_actions = array();

        if ($this->user->g_id == FEATHER_ADMIN) {
            $user_disp['posts_field'] .= '<label>'.$lang_common['Posts'].'<br /><input type="text" name="num_posts" value="'.$user['num_posts'].'" size="8" maxlength="8" /><br /></label>';
        } elseif ($this->config['o_show_post_count'] == '1' || $this->user->is_admmod) {
            $posts_actions[] = sprintf($lang_profile['Posts info'], forum_number_format($user['num_posts']));
        }

        if ($this->user->g_search == '1' || $this->user->g_id == FEATHER_ADMIN) {
            $posts_actions[] = '<a href="'.get_link('search/?action=show_user_topics&amp;user_id='.$id).'">'.$lang_profile['Show topics'].'</a>';
            $posts_actions[] = '<a href="'.get_link('search/?action=show_user_posts&amp;user_id='.$id).'">'.$lang_profile['Show posts'].'</a>';

            if ($this->config['o_topic_subscriptions'] == '1') {
                $posts_actions[] = '<a href="'.get_link('search/?action=show_subscriptions&amp;user_id='.$id).'">'.$lang_profile['Show subscriptions'].'</a>';
            }
        }

        $user_disp['posts_field'] .= (!empty($posts_actions) ? '<p class="actions">'.implode(' - ', $posts_actions).'</p>' : '')."\n";

        return $user_disp;
    }

    public function get_group_list($user)
    {
        $output = '';

        $select_group_list = array('g_id', 'g_title');

        $result = DB::for_table('groups')->select_many($select_group_list)
                      ->where_not_equal('g_id', FEATHER_GUEST)
                      ->order_by('g_title')
                      ->find_many();

        foreach ($result as $cur_group) {
            if ($cur_group['g_id'] == $user['g_id'] || ($cur_group['g_id'] == $this->config['o_default_user_group'] && $user['g_id'] == '')) {
                $output .= "\t\t\t\t\t\t\t\t".'<option value="'.$cur_group['g_id'].'" selected="selected">'.feather_escape($cur_group['g_title']).'</option>'."\n";
            } else {
                $output .= "\t\t\t\t\t\t\t\t".'<option value="'.$cur_group['g_id'].'">'.feather_escape($cur_group['g_title']).'</option>'."\n";
            }
        }
        
        return $output;
    }

    public function get_forum_list($id)
    {
        $output = '';

        $select_get_forum_list = array('cid' => 'c.id', 'c.cat_name', 'fid' => 'f.id', 'f.forum_name', 'f.moderators');
        $order_by_get_forum_list = array('c.disp_position', 'c.id', 'f.disp_position');

        $result = DB::for_table('categories')
            ->table_alias('c')
            ->select_many($select_get_forum_list)
            ->inner_join('forums', array('c.id', '=', 'f.cat_id'), 'f')
            ->where_null('f.redirect_url')
            ->order_by_many($order_by_get_forum_list)
            ->find_many();

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

                $output .= "\t\t\t\t\t\t\t".'<div class="conl">'."\n\t\t\t\t\t\t\t\t".'<p><strong>'.feather_escape($cur_forum['cat_name']).'</strong></p>'."\n\t\t\t\t\t\t\t\t".'<div class="rbox">';
                $cur_category = $cur_forum['cid'];
            }

            $moderators = ($cur_forum['moderators'] != '') ? unserialize($cur_forum['moderators']) : array();

            $output .= "\n\t\t\t\t\t\t\t\t\t".'<label><input type="checkbox" name="moderator_in['.$cur_forum['fid'].']" value="1"'.((in_array($id, $moderators)) ? ' checked="checked"' : '').' />'.feather_escape($cur_forum['forum_name']).'<br /></label>'."\n";
        }
        
        return $output;
    }

    //
    // Display the profile navigation menu
    //
    public function generate_profile_menu($page = '', $id)
    {
        global $lang_profile;

        $this->feather->render('profile/menu.php', array(
            'lang_profile' => $lang_profile,
            'id' => $id,
            'feather_config' => $this->config,
            'feather_user' => $this->user,
            'page' => $page,
            )
        );
    }
}
