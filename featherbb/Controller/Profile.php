<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace FeatherBB\Controller;

class Profile
{
    public function __construct()
    {
        $this->feather = \Slim\Slim::getInstance();
        $this->start = $this->feather->start;
        $this->config = $this->feather->config;
        $this->user = $this->feather->user;
        $this->request = $this->feather->request;
        $this->model = new \FeatherBB\Model\Profile();
        load_textdomain('featherbb', FEATHER_ROOT.'featherbb/lang/'.$this->user->language.'/profile.mo');
        load_textdomain('featherbb', FEATHER_ROOT.'featherbb/lang/'.$this->user->language.'/register.mo');
        load_textdomain('featherbb', FEATHER_ROOT.'featherbb/lang/'.$this->user->language.'/prof_reg.mo');
    }

    public function __autoload($class_name)
    {
        require FEATHER_ROOT . $class_name . '.php';
    }

    public function display($id, $section = null)
    {
        global $pd, $forum_time_formats, $forum_date_formats;

        // Include UTF-8 function
        require FEATHER_ROOT.'featherbb/Helpers/utf8/substr_replace.php';
        require FEATHER_ROOT.'featherbb/Helpers/utf8/ucwords.php'; // utf8_ucwords needs utf8_substr_replace
        require FEATHER_ROOT.'featherbb/Helpers/utf8/strcasecmp.php';

        if ($this->request->post('update_group_membership')) {
            if ($this->user->g_id > FEATHER_ADMIN) {
                throw new \FeatherBB\Error(__('No permission'), 403);
            }

            $this->model->update_group_membership($id, $this->feather);
        } elseif ($this->request->post('update_forums')) {
            if ($this->user->g_id > FEATHER_ADMIN) {
                throw new \FeatherBB\Error(__('No permission'), 403);
            }

            $this->model->update_mod_forums($id, $this->feather);
        } elseif ($this->request->post('ban')) {
            if ($this->user->g_id != FEATHER_ADMIN && ($this->user->g_moderator != '1' || $this->user->g_mod_ban_users == '0')) {
                throw new \FeatherBB\Error(__('No permission'), 403);
            }

            $this->model->ban_user($id);
        } elseif ($this->request->post('delete_user') || $this->request->post('delete_user_comply')) {
            if ($this->user->g_id > FEATHER_ADMIN) {
                throw new \FeatherBB\Error(__('No permission'), 403);
            }

            $this->model->delete_user($id, $this->feather);

            $this->feather->view2->setPageInfo(array(
                'title' => array($this->feather->utils->escape($this->config['o_board_title']), __('Profile'), __('Confirm delete user')),
                'active_page' => 'profile',
                'username' => $this->model->get_username($id),
                'id' => $id,
            ));

            $this->feather->view2->addTemplate('profile/delete_user.php');
            $this->feather->view2->display();

        } elseif ($this->request->post('form_sent')) {

            // Fetch the user group of the user we are editing
            $info = $this->model->fetch_user_group($id);

            if ($this->user->id != $id &&                                                            // If we aren't the user (i.e. editing your own profile)
                                    (!$this->user->is_admmod ||                                      // and we are not an admin or mod
                                    ($this->user->g_id != FEATHER_ADMIN &&                           // or we aren't an admin and ...
                                    ($this->user->g_mod_edit_users == '0' ||                         // mods aren't allowed to edit users
                                    $info['group_id'] == FEATHER_ADMIN ||                            // or the user is an admin
                                    $info['is_moderator'])))) {                                      // or the user is another mod
                                    throw new \FeatherBB\Error(__('No permission'), 403);
            }

            $this->model->update_profile($id, $info, $section, $this->feather);
        }

        $user = $this->model->get_user_info($id);

        if ($user['signature'] != '') {
            require FEATHER_ROOT.'featherbb/Helpers/parser.php';
            $parsed_signature = parse_signature($user['signature']);
        }

        // View or edit?
        if ($this->user->id != $id &&                                 // If we aren't the user (i.e. editing your own profile)
                (!$this->user->is_admmod ||                           // and we are not an admin or mod
                ($this->user->g_id != FEATHER_ADMIN &&                // or we aren't an admin and ...
                ($this->user->g_mod_edit_users == '0' ||              // mods aren't allowed to edit users
                $user['g_id'] == FEATHER_ADMIN ||                     // or the user is an admin
                $user['g_moderator'] == '1')))) {
            // or the user is another mod
                $user_info = $this->model->parse_user_info($user);

            $this->feather->view2->setPageInfo(array(
                'title' => array($this->feather->utils->escape($this->config['o_board_title']), sprintf(__('Users profile'), $this->feather->utils->escape($user['username']))),
                'active_page' => 'profile',
                'user_info' => $user_info,
                'id' => $id
            ));

            $this->feather->view2->addTemplate('profile/view_profile.php');
        } else {
            if (!$section || $section == 'essentials') {
                $user_disp = $this->model->edit_essentials($id, $user);

                $this->feather->view2->setPageInfo(array(
                    'title' => array($this->feather->utils->escape($this->config['o_board_title']), __('Profile'), __('Section essentials')),
                    'required_fields' => array('req_username' => __('Username'), 'req_email' => __('Email')),
                    'active_page' => 'profile',
                    'id' => $id,
                    'page' => 'essentials',
                    'user' => $user,
                    'user_disp' => $user_disp,
                    'forum_time_formats' => $forum_time_formats,
                    'forum_date_formats' => $forum_date_formats,
                ));

                $this->feather->view2->addTemplate('profile/menu.php', 5)->addTemplate('profile/section_essentials.php')->display();

            } elseif ($section == 'personal') {
                if ($this->user->g_set_title == '1') {
                    $title_field = '<label>'.__('Title').' <em>('.__('Leave blank').')</em><br /><input type="text" name="title" value="'.$this->feather->utils->escape($user['title']).'" size="30" maxlength="50" /><br /></label>'."\n";
                }

                $this->feather->view2->setPageInfo(array(
                    'title' => array($this->feather->utils->escape($this->config['o_board_title']), __('Profile'), __('Section personal')),
                    'active_page' => 'profile',
                    'id' => $id,
                    'page' => 'personal',
                    'user' => $user,
                    'title_field' => $title_field,
                ));

                $this->feather->view2->addTemplate('profile/menu.php', 5)->addTemplate('profile/section_personal.php')->display();

            } elseif ($section == 'messaging') {

                $this->feather->view2->setPageInfo(array(
                    'title' => array($this->feather->utils->escape($this->config['o_board_title']), __('Profile'), __('Section messaging')),
                    'active_page' => 'profile',
                    'page' => 'messaging',
                    'user' => $user,
                    'id' => $id
                ));

                $this->feather->view2->addTemplate('profile/menu.php', 5)->addTemplate('profile/section_messaging.php')->display();

            } elseif ($section == 'personality') {
                if ($this->config['o_avatars'] == '0' && $this->config['o_signatures'] == '0') {
                    throw new \FeatherBB\Error(__('Bad request'), 404);
                }

                $avatar_field = '<span><a href="'.$this->feather->url->get('user/'.$id.'/action/upload_avatar/').'">'.__('Change avatar').'</a></span>';

                $user_avatar = generate_avatar_markup($id);
                if ($user_avatar) {
                    $avatar_field .= ' <span><a href="'.$this->feather->url->get('user/'.$id.'/action/delete_avatar/').'">'.__('Delete avatar').'</a></span>';
                } else {
                    $avatar_field = '<span><a href="'.$this->feather->url->get('user/'.$id.'/action/upload_avatar/').'">'.__('Upload avatar').'</a></span>';
                }

                if ($user['signature'] != '') {
                    $signature_preview = '<p>'.__('Sig preview').'</p>'."\n\t\t\t\t\t\t\t".'<div class="postsignature postmsg">'."\n\t\t\t\t\t\t\t\t".'<hr />'."\n\t\t\t\t\t\t\t\t".$parsed_signature."\n\t\t\t\t\t\t\t".'</div>'."\n";
                } else {
                    $signature_preview = '<p>'.__('No sig').'</p>'."\n";
                }

                $this->feather->view2->setPageInfo(array(
                    'title' => array($this->feather->utils->escape($this->config['o_board_title']), __('Profile'), __('Section personality')),
                    'active_page' => 'profile',
                    'user_avatar' => $user_avatar,
                    'avatar_field' => $avatar_field,
                    'signature_preview' => $signature_preview,
                    'page' => 'personality',
                    'user' => $user,
                    'id' => $id,
                ));

                $this->feather->view2->addTemplate('profile/menu.php', 5)->addTemplate('profile/section_personality.php')->display();

            } elseif ($section == 'display') {

                $this->feather->view2->setPageInfo(array(
                    'title' => array($this->feather->utils->escape($this->config['o_board_title']), __('Profile'), __('Section display')),
                    'active_page' => 'profile',
                    'page' => 'display',
                    'user' => $user,
                    'id' => $id
                ));

                $this->feather->view2->addTemplate('profile/menu.php', 5)->addTemplate('profile/section_display.php')->display();

            } elseif ($section == 'privacy') {

                $this->feather->view2->setPageInfo(array(
                    'title' => array($this->feather->utils->escape($this->config['o_board_title']), __('Profile'), __('Section privacy')),
                    'active_page' => 'profile',
                    'page' => 'privacy',
                    'user' => $user,
                    'id' => $id
                ));

                $this->feather->view2->addTemplate('profile/menu.php', 5)->addTemplate('profile/section_privacy.php')->display();

            } elseif ($section == 'admin') {

                if (!$this->user->is_admmod || ($this->user->g_moderator == '1' && $this->user->g_mod_ban_users == '0')) {
                    throw new \FeatherBB\Error(__('Bad request'), 404);
                }

                $this->feather->view2->setPageInfo(array(
                    'title' => array($this->feather->utils->escape($this->config['o_board_title']), __('Profile'), __('Section admin')),
                    'active_page' => 'profile',
                    'page' => 'admin',
                    'user' => $user,
                    'forum_list' => $this->model->get_forum_list($id),
                    'group_list' => $this->model->get_group_list($user),
                    'id' => $id
                ));

                $this->feather->view2->addTemplate('profile/menu.php', 5)->addTemplate('profile/section_admin.php')->display();
            } else {
                throw new \FeatherBB\Error(__('Bad request'), 404);
            }
        }
    }

    public function action($id, $action)
    {
        // Include UTF-8 function
        require FEATHER_ROOT.'featherbb/Helpers/utf8/substr_replace.php';
        require FEATHER_ROOT.'featherbb/Helpers/utf8/ucwords.php'; // utf8_ucwords needs utf8_substr_replace
        require FEATHER_ROOT.'featherbb/Helpers/utf8/strcasecmp.php';

        if ($action != 'change_pass' || !$this->request->get('key')) {
            if ($this->user->g_read_board == '0') {
                throw new \FeatherBB\Error(__('No view'), 403);
            } elseif ($this->user->g_view_users == '0' && ($this->user->is_guest || $this->user->id != $id)) {
                throw new \FeatherBB\Error(__('No permission'), 403);
            }
        }

        if ($action == 'change_pass') {
            $this->model->change_pass($id, $this->feather);

            $this->feather->view2->setPageInfo(array(
                'title' => array($this->feather->utils->escape($this->config['o_board_title']), __('Profile'), __('Change pass')),
                'active_page' => 'profile',
                'id' => $id,
                'required_fields' => array('req_old_password' => __('Old pass'), 'req_new_password1' => __('New pass'), 'req_new_password2' => __('Confirm new pass')),
                'focus_element' => array('change_pass', ((!$this->user->is_admmod) ? 'req_old_password' : 'req_new_password1')),
            ));

            $this->feather->view2->addTemplate('profile/change_pass.php')->display();

        } elseif ($action == 'change_email') {
            $this->model->change_email($id, $this->feather);

            $this->feather->view2->setPageInfo(array(
                'title' => array($this->feather->utils->escape($this->config['o_board_title']), __('Profile'), __('Change email')),
                'active_page' => 'profile',
                'required_fields' => array('req_new_email' => __('New email'), 'req_password' => __('Password')),
                'focus_element' => array('change_email', 'req_new_email'),
                'id' => $id,
            ));

            $this->feather->view2->addTemplate('profile/change_mail.php')->display();

        } elseif ($action == 'upload_avatar' || $action == 'upload_avatar2') {
            if ($this->config['o_avatars'] == '0') {
                throw new \FeatherBB\Error(__('Avatars disabled'), 400);
            }

            if ($this->user->id != $id && !$this->user->is_admmod) {
                throw new \FeatherBB\Error(__('No permission'), 403);
            }

            if ($this->feather->request()->isPost()) {
                $this->model->upload_avatar($id, $_FILES);
            }

            $this->feather->view2->setPageInfo(array(
                'title' => array($this->feather->utils->escape($this->config['o_board_title']), __('Profile'), __('Upload avatar')),
                'active_page' => 'profile',
                'required_fields' =>  array('req_file' => __('File')),
                'focus_element' => array('upload_avatar', 'req_file'),
                'id' => $id,
            ));

            $this->feather->view2->addTemplate('profile/upload_avatar.php')->display();

        } elseif ($action == 'delete_avatar') {
            if ($this->user->id != $id && !$this->user->is_admmod) {
                throw new \FeatherBB\Error(__('No permission'), 403);
            }

            $this->model->delete_avatar($id);

            redirect($this->feather->url->get('user/'.$id.'/section/personality/'), __('Avatar deleted redirect'));
        } elseif ($action == 'promote') {
            if ($this->user->g_id != FEATHER_ADMIN && ($this->user->g_moderator != '1' || $this->user->g_mod_promote_users == '0')) {
                throw new \FeatherBB\Error(__('No permission'), 403);
            }

            $this->model->promote_user($id, $this->feather);
        } else {
            throw new \FeatherBB\Error(__('Bad request'), 404);
        }
    }
}
