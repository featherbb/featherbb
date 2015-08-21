<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace controller;

class profile
{
    public function __construct()
    {
        $this->feather = \Slim\Slim::getInstance();
        $this->start = $this->feather->start;
        $this->config = $this->feather->config;
        $this->user = $this->feather->user;
        $this->request = $this->feather->request;
        $this->header = new \controller\header();
        $this->footer = new \controller\footer();
        $this->model = new \model\profile();
        load_textdomain('featherbb', FEATHER_ROOT.'lang/'.$this->user->language.'/profile.mo');
        load_textdomain('featherbb', FEATHER_ROOT.'lang/'.$this->user->language.'/register.mo');
        load_textdomain('featherbb', FEATHER_ROOT.'lang/'.$this->user->language.'/prof_reg.mo');
    }

    public function __autoload($class_name)
    {
        require FEATHER_ROOT . $class_name . '.php';
    }

    public function display($id, $section = null)
    {
        global $pd, $forum_time_formats, $forum_date_formats;

        // Include UTF-8 function
        require FEATHER_ROOT.'include/utf8/substr_replace.php';
        require FEATHER_ROOT.'include/utf8/ucwords.php'; // utf8_ucwords needs utf8_substr_replace
        require FEATHER_ROOT.'include/utf8/strcasecmp.php';

        if ($this->request->post('update_group_membership')) {
            if ($this->user->g_id > FEATHER_ADMIN) {
                message(__('No permission'), '403');
            }

            $this->model->update_group_membership($id, $this->feather);
        } elseif ($this->request->post('update_forums')) {
            if ($this->user->g_id > FEATHER_ADMIN) {
                message(__('No permission'), '403');
            }

            $this->model->update_mod_forums($id, $this->feather);
        } elseif ($this->request->post('ban')) {
            if ($this->user->g_id != FEATHER_ADMIN && ($this->user->g_moderator != '1' || $this->user->g_mod_ban_users == '0')) {
                message(__('No permission'), '403');
            }

            $this->model->ban_user($id);
        } elseif ($this->request->post('delete_user') || $this->request->post('delete_user_comply')) {
            if ($this->user->g_id > FEATHER_ADMIN) {
                message(__('No permission'), '403');
            }

            $this->model->delete_user($id, $this->feather);

            $page_title = array(feather_escape($this->config['o_board_title']), __('Profile'), __('Confirm delete user'));

            $this->header->setTitle($page_title)->setActivePage('profile')->display();

            $this->feather->render('profile/delete_user.php', array(
                                    'username' => $this->model->get_username($id),
                                    'id' => $id,
                                    )
                            );

            $this->footer->display();

        } elseif ($this->request->post('form_sent')) {

            // Fetch the user group of the user we are editing
            $info = $this->model->fetch_user_group($id);

            if ($this->user->id != $id &&                                                            // If we aren't the user (i.e. editing your own profile)
                                    (!$this->user->is_admmod ||                                      // and we are not an admin or mod
                                    ($this->user->g_id != FEATHER_ADMIN &&                           // or we aren't an admin and ...
                                    ($this->user->g_mod_edit_users == '0' ||                         // mods aren't allowed to edit users
                                    $info['group_id'] == FEATHER_ADMIN ||                            // or the user is an admin
                                    $info['is_moderator'])))) {                                      // or the user is another mod
                                    message(__('No permission'), '403');
            }

            $this->model->update_profile($id, $info, $section, $this->feather);
        }

        $user = $this->model->get_user_info($id);

        $last_post = format_time($user['last_post']);

        if ($user['signature'] != '') {
            require FEATHER_ROOT.'include/parser.php';
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

            $page_title = array(feather_escape($this->config['o_board_title']), sprintf(__('Users profile'), feather_escape($user['username'])));

            $this->header->setTitle($page_title)->setActivePage('profile')->allowIndex()->display();

            $this->feather->render('profile/view_profile.php', array(
                        'user_info' => $user_info,
                        )
                );

            $this->footer->display();
        } else {
            if (!$section || $section == 'essentials') {
                $user_disp = $this->model->edit_essentials($id, $user);

                $page_title = array(feather_escape($this->config['o_board_title']), __('Profile'), __('Section essentials'));
                $required_fields = array('req_username' => __('Username'), 'req_email' => __('Email'));

                $this->header->setTitle($page_title)->setActivePage('profile')->setRequiredFields($required_fields)->display();

                $this->model->generate_profile_menu('essentials', $id);

                $this->feather->render('profile/section_essentials.php', array(
                                'feather' => $this->feather,
                                'id' => $id,
                                'user' => $user,
                                'user_disp' => $user_disp,
                                'forum_time_formats' => $forum_time_formats,
                                'forum_date_formats' => $forum_date_formats,
                                )
                        );
            } elseif ($section == 'personal') {
                if ($this->user->g_set_title == '1') {
                    $title_field = '<label>'.__('Title').' <em>('.__('Leave blank').')</em><br /><input type="text" name="title" value="'.feather_escape($user['title']).'" size="30" maxlength="50" /><br /></label>'."\n";
                }

                $page_title = array(feather_escape($this->config['o_board_title']), __('Profile'), __('Section personal'));

                $this->header->setTitle($page_title)->setActivePage('profile')->display();

                $this->model->generate_profile_menu('personal', $id);

                $this->feather->render('profile/section_personal.php', array(
                                'user' => $user,
                                'feather' => $this->feather,
                                )
                        );

            } elseif ($section == 'messaging') {
                $page_title = array(feather_escape($this->config['o_board_title']), __('Profile'), __('Section messaging'));

                $this->header->setTitle($page_title)->setActivePage('profile')->display();

                $this->model->generate_profile_menu('messaging', $id);

                $this->feather->render('profile/section_messaging.php', array(
                                'user' => $user,
                                )
                        );

            } elseif ($section == 'personality') {
                if ($this->config['o_avatars'] == '0' && $this->config['o_signatures'] == '0') {
                    message(__('Bad request'), '404');
                }

                $avatar_field = '<span><a href="'.get_link('user/'.$id.'/action/upload_avatar/').'">'.__('Change avatar').'</a></span>';

                $user_avatar = generate_avatar_markup($id);
                if ($user_avatar) {
                    $avatar_field .= ' <span><a href="'.get_link('user/'.$id.'/action/delete_avatar/').'">'.__('Delete avatar').'</a></span>';
                } else {
                    $avatar_field = '<span><a href="'.get_link('user/'.$id.'/action/upload_avatar/').'">'.__('Upload avatar').'</a></span>';
                }

                if ($user['signature'] != '') {
                    $signature_preview = '<p>'.__('Sig preview').'</p>'."\n\t\t\t\t\t\t\t".'<div class="postsignature postmsg">'."\n\t\t\t\t\t\t\t\t".'<hr />'."\n\t\t\t\t\t\t\t\t".$parsed_signature."\n\t\t\t\t\t\t\t".'</div>'."\n";
                } else {
                    $signature_preview = '<p>'.__('No sig').'</p>'."\n";
                }

                $page_title = array(feather_escape($this->config['o_board_title']), __('Profile'), __('Section personality'));

                $this->header->setTitle($page_title)->setActivePage('profile')->display();

                $this->model->generate_profile_menu('personality', $id);

                $this->feather->render('profile/section_personality.php', array(
                                'user_avatar' => $user_avatar,
                                'avatar_field' => $avatar_field,
                                'signature_preview' => $signature_preview,
                                'user' => $user,
                                'feather' => $this->feather,
                                )
                        );

            } elseif ($section == 'display') {
                $page_title = array(feather_escape($this->config['o_board_title']), __('Profile'), __('Section display'));

                $this->header->setTitle($page_title)->setActivePage('profile')->display();

                $this->model->generate_profile_menu('display', $id);

                $this->feather->render('profile/section_display.php', array(
                                'user' => $user,
                                )
                        );

            } elseif ($section == 'privacy') {
                $page_title = array(feather_escape($this->config['o_board_title']), __('Profile'), __('Section privacy'));

                $this->header->setTitle($page_title)->setActivePage('profile')->display();

                $this->model->generate_profile_menu('privacy', $id);

                $this->feather->render('profile/section_privacy.php', array(
                                'user' => $user,
                                )
                        );

            } elseif ($section == 'admin') {
                if (!$this->user->is_admmod || ($this->user->g_moderator == '1' && $this->user->g_mod_ban_users == '0')) {
                    message(__('Bad request'), false, '403 Forbidden');
                }

                $page_title = array(feather_escape($this->config['o_board_title']), __('Profile'), __('Section admin'));

                $this->header->setTitle($page_title)->setActivePage('profile')->display();

                $this->model->generate_profile_menu('admin', $id);

                $this->feather->render('profile/section_admin.php', array(
                                'user' => $user,
                                'forum_list' => $this->model->get_forum_list($id),
                                'group_list' => $this->model->get_group_list($user),
                                'feather' => $this->feather,
                                )
                        );

            } else {
                message(__('Bad request'), '404');
            }

            $this->footer->display();
        }
    }

    public function action($id, $action)
    {
        // Include UTF-8 function
        require FEATHER_ROOT.'include/utf8/substr_replace.php';
        require FEATHER_ROOT.'include/utf8/ucwords.php'; // utf8_ucwords needs utf8_substr_replace
        require FEATHER_ROOT.'include/utf8/strcasecmp.php';

        if ($action != 'change_pass' || !$this->request->get('key')) {
            if ($this->user->g_read_board == '0') {
                message(__('No view'), '403');
            } elseif ($this->user->g_view_users == '0' && ($this->user->is_guest || $this->user->id != $id)) {
                message(__('No permission'), '403');
            }
        }

        if ($action == 'change_pass') {
            $this->model->change_pass($id, $this->feather);

            $page_title = array(feather_escape($this->config['o_board_title']), __('Profile'), __('Change pass'));
            $required_fields = array('req_old_password' => __('Old pass'), 'req_new_password1' => __('New pass'), 'req_new_password2' => __('Confirm new pass'));
            $focus_element = array('change_pass', ((!$this->user->is_admmod) ? 'req_old_password' : 'req_new_password1'));

            $this->header->setTitle($page_title)->setActivePage('profile')->setFocusElement($focus_element)->setRequiredFields($required_fields)->display();

            $this->feather->render('profile/change_pass.php', array(
                                    'feather' => $this->feather,
                                    'id' => $id,
                                    )
                            );

            $this->footer->display();
        } elseif ($action == 'change_email') {
            $this->model->change_email($id, $this->feather);

            $page_title = array(feather_escape($this->config['o_board_title']), __('Profile'), __('Change email'));
            $required_fields = array('req_new_email' => __('New email'), 'req_password' => __('Password'));
            $focus_element = array('change_email', 'req_new_email');

            $this->header->setTitle($page_title)->setActivePage('profile')->setFocusElement($focus_element)->setRequiredFields($required_fields)->display();

            $this->feather->render('profile/change_mail.php', array(
                                    'id' => $id,
                                    )
                            );

            $this->footer->display();
        } elseif ($action == 'upload_avatar' || $action == 'upload_avatar2') {
            if ($this->config['o_avatars'] == '0') {
                message(__('Avatars disabled'));
            }

            if ($this->user->id != $id && !$this->user->is_admmod) {
                message(__('No permission'), '403');
            }

            if ($this->feather->request()->isPost()) {
                $this->model->upload_avatar($id, $_FILES);
            }

            $page_title = array(feather_escape($this->config['o_board_title']), __('Profile'), __('Upload avatar'));
            $required_fields = array('req_file' => __('File'));
            $focus_element = array('upload_avatar', 'req_file');

            $this->header->setTitle($page_title)->setActivePage('profile')->setFocusElement($focus_element)->setRequiredFields($required_fields)->display();

            $this->feather->render('profile/upload_avatar.php', array(
                                    'feather_config' => $this->config,
                                    'id' => $id,
                                    )
                            );

            $this->footer->display();

        } elseif ($action == 'delete_avatar') {
            if ($this->user->id != $id && !$this->user->is_admmod) {
                message(__('No permission'), '403');
            }



            $this->model->delete_avatar($id);

            redirect(get_link('user/'.$id.'/section/personality/'), __('Avatar deleted redirect'));
        } elseif ($action == 'promote') {
            if ($this->user->g_id != FEATHER_ADMIN && ($this->user->g_moderator != '1' || $this->user->g_mod_promote_users == '0')) {
                message(__('No permission'), '403');
            }

            $this->model->promote_user($id, $this->feather);
        } else {
            message(__('Bad request'), '404');
        }
    }
}
