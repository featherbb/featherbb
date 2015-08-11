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
    }

    public function __autoload($class_name)
    {
        require FEATHER_ROOT . $class_name . '.php';
    }
    
    public function display($id, $section = null)
    {
        global $lang_common, $lang_prof_reg, $lang_profile, $pd, $forum_time_formats, $forum_date_formats;

        // Include UTF-8 function
        require FEATHER_ROOT.'include/utf8/substr_replace.php';
        require FEATHER_ROOT.'include/utf8/ucwords.php'; // utf8_ucwords needs utf8_substr_replace
        require FEATHER_ROOT.'include/utf8/strcasecmp.php';

        // Load the prof_reg.php language file
        require FEATHER_ROOT.'lang/'.$this->user->language.'/prof_reg.php';

        // Load the profile.php language file
        require FEATHER_ROOT.'lang/'.$this->user->language.'/profile.php';

        if ($this->request->post('update_group_membership')) {
            if ($this->user->g_id > FEATHER_ADMIN) {
                message($lang_common['No permission'], '403');
            }

            $this->model->update_group_membership($id, $this->feather);
        } elseif ($this->request->post('update_forums')) {
            if ($this->user->g_id > FEATHER_ADMIN) {
                message($lang_common['No permission'], '403');
            }

            $this->model->update_mod_forums($id, $this->feather);
        } elseif ($this->request->post('ban')) {
            if ($this->user->g_id != FEATHER_ADMIN && ($this->user->g_moderator != '1' || $this->user->g_mod_ban_users == '0')) {
                message($lang_common['No permission'], '403');
            }

            $this->model->ban_user($id);
        } elseif ($this->request->post('delete_user') || $this->request->post('delete_user_comply')) {
            if ($this->user->g_id > FEATHER_ADMIN) {
                message($lang_common['No permission'], '403');
            }

            $this->model->delete_user($id, $this->feather);

            $page_title = array(feather_escape($this->config['o_board_title']), $lang_common['Profile'], $lang_profile['Confirm delete user']);

            define('FEATHER_ACTIVE_PAGE', 'profile');

            $this->header->setTitle($page_title)->display();

            $this->feather->render('profile/delete_user.php', array(
                                    'lang_common' => $lang_common,
                                    'username' => $this->model->get_username($id),
                                    'lang_profile' => $lang_profile,
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
                                    message($lang_common['No permission'], '403');
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

            $page_title = array(feather_escape($this->config['o_board_title']), sprintf($lang_profile['Users profile'], feather_escape($user['username'])));
            define('FEATHER_ALLOW_INDEX', 1);

            define('FEATHER_ACTIVE_PAGE', 'profile');

            $this->header->setTitle($page_title)->display();

            $this->feather->render('profile/view_profile.php', array(
                        'lang_common' => $lang_common,
                        'lang_profile' => $lang_profile,
                        'user_info' => $user_info,
                        )
                );

            $this->footer->display();
        } else {
            if (!$section || $section == 'essentials') {
                $user_disp = $this->model->edit_essentials($id, $user);

                $page_title = array(feather_escape($this->config['o_board_title']), $lang_common['Profile'], $lang_profile['Section essentials']);
                $required_fields = array('req_username' => $lang_common['Username'], 'req_email' => $lang_common['Email']);

                define('FEATHER_ACTIVE_PAGE', 'profile');

                $this->header->setTitle($page_title)->setRequiredFields($required_fields)->display();

                $this->model->generate_profile_menu('essentials', $id);

                $this->feather->render('profile/section_essentials.php', array(
                                'lang_common' => $lang_common,
                                'lang_profile' => $lang_profile,
                                'lang_prof_reg' => $lang_prof_reg,
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
                    $title_field = '<label>'.$lang_common['Title'].' <em>('.$lang_profile['Leave blank'].')</em><br /><input type="text" name="title" value="'.feather_escape($user['title']).'" size="30" maxlength="50" /><br /></label>'."\n";
                }

                $page_title = array(feather_escape($this->config['o_board_title']), $lang_common['Profile'], $lang_profile['Section personal']);

                define('FEATHER_ACTIVE_PAGE', 'profile');

                $this->header->setTitle($page_title)->display();

                $this->model->generate_profile_menu('personal', $id);

                $this->feather->render('profile/section_personal.php', array(
                                'lang_common' => $lang_common,
                                'lang_profile' => $lang_profile,
                                'user' => $user,
                                'feather' => $this->feather,
                                )
                        );
                
            } elseif ($section == 'messaging') {
                $page_title = array(feather_escape($this->config['o_board_title']), $lang_common['Profile'], $lang_profile['Section messaging']);

                define('FEATHER_ACTIVE_PAGE', 'profile');

                $this->header->setTitle($page_title)->display();

                $this->model->generate_profile_menu('messaging', $id);

                $this->feather->render('profile/section_messaging.php', array(
                                'lang_common' => $lang_common,
                                'lang_profile' => $lang_profile,
                                'user' => $user,
                                )
                        );
                
            } elseif ($section == 'personality') {
                if ($this->config['o_avatars'] == '0' && $this->config['o_signatures'] == '0') {
                    message($lang_common['Bad request'], '404');
                }

                $avatar_field = '<span><a href="'.get_link('user/'.$id.'/action/upload_avatar/').'">'.$lang_profile['Change avatar'].'</a></span>';

                $user_avatar = generate_avatar_markup($id);
                if ($user_avatar) {
                    $avatar_field .= ' <span><a href="'.get_link('user/'.$id.'/action/delete_avatar/').'">'.$lang_profile['Delete avatar'].'</a></span>';
                } else {
                    $avatar_field = '<span><a href="'.get_link('user/'.$id.'/action/upload_avatar/').'">'.$lang_profile['Upload avatar'].'</a></span>';
                }

                if ($user['signature'] != '') {
                    $signature_preview = '<p>'.$lang_profile['Sig preview'].'</p>'."\n\t\t\t\t\t\t\t".'<div class="postsignature postmsg">'."\n\t\t\t\t\t\t\t\t".'<hr />'."\n\t\t\t\t\t\t\t\t".$parsed_signature."\n\t\t\t\t\t\t\t".'</div>'."\n";
                } else {
                    $signature_preview = '<p>'.$lang_profile['No sig'].'</p>'."\n";
                }

                $page_title = array(feather_escape($this->config['o_board_title']), $lang_common['Profile'], $lang_profile['Section personality']);

                define('FEATHER_ACTIVE_PAGE', 'profile');

                $this->header->setTitle($page_title)->display();

                $this->model->generate_profile_menu('personality', $id);

                $this->feather->render('profile/section_personality.php', array(
                                'lang_common' => $lang_common,
                                'lang_profile' => $lang_profile,
                                'user_avatar' => $user_avatar,
                                'avatar_field' => $avatar_field,
                                'signature_preview' => $signature_preview,
                                'user' => $user,
                                'feather' => $this->feather,
                                )
                        );
                
            } elseif ($section == 'display') {
                $page_title = array(feather_escape($this->config['o_board_title']), $lang_common['Profile'], $lang_profile['Section display']);

                define('FEATHER_ACTIVE_PAGE', 'profile');

                $this->header->setTitle($page_title)->display();

                $this->model->generate_profile_menu('display', $id);

                $this->feather->render('profile/section_display.php', array(
                                'lang_common' => $lang_common,
                                'lang_profile' => $lang_profile,
                                'user' => $user,
                                )
                        );
                
            } elseif ($section == 'privacy') {
                $page_title = array(feather_escape($this->config['o_board_title']), $lang_common['Profile'], $lang_profile['Section privacy']);

                define('FEATHER_ACTIVE_PAGE', 'profile');

                $this->header->setTitle($page_title)->display();

                $this->model->generate_profile_menu('privacy', $id);

                $this->feather->render('profile/section_privacy.php', array(
                                'lang_common' => $lang_common,
                                'lang_profile' => $lang_profile,
                                'lang_prof_reg' => $lang_prof_reg,
                                'user' => $user,
                                )
                        );
                
            } elseif ($section == 'admin') {
                if (!$this->user->is_admmod || ($this->user->g_moderator == '1' && $this->user->g_mod_ban_users == '0')) {
                    message($lang_common['Bad request'], false, '403 Forbidden');
                }

                $page_title = array(feather_escape($this->config['o_board_title']), $lang_common['Profile'], $lang_profile['Section admin']);

                define('FEATHER_ACTIVE_PAGE', 'profile');

                $this->header->setTitle($page_title)->display();

                $this->model->generate_profile_menu('admin', $id);

                $this->feather->render('profile/section_admin.php', array(
                                'lang_common' => $lang_common,
                                'lang_profile' => $lang_profile,
                                'user' => $user,
                                'forum_list' => $this->model->get_forum_list($id),
                                'group_list' => $this->model->get_group_list($user),
                                'feather' => $this->feather,
                                )
                        );
                
            } else {
                message($lang_common['Bad request'], '404');
            }

            $this->footer->display();
        }
    }

    public function action($id, $action)
    {
        global $lang_common, $lang_prof_reg, $lang_profile;

        // Include UTF-8 function
        require FEATHER_ROOT.'include/utf8/substr_replace.php';
        require FEATHER_ROOT.'include/utf8/ucwords.php'; // utf8_ucwords needs utf8_substr_replace
        require FEATHER_ROOT.'include/utf8/strcasecmp.php';

        // Load the prof_reg.php language file
        require FEATHER_ROOT.'lang/'.$this->user->language.'/prof_reg.php';

        // Load the profile.php language file
        require FEATHER_ROOT.'lang/'.$this->user->language.'/profile.php';

        if ($action != 'change_pass' || !$this->request->get('key')) {
            if ($this->user->g_read_board == '0') {
                message($lang_common['No view'], '403');
            } elseif ($this->user->g_view_users == '0' && ($this->user->is_guest || $this->user->id != $id)) {
                message($lang_common['No permission'], '403');
            }
        }

        if ($action == 'change_pass') {
            $this->model->change_pass($id, $this->feather);

            $page_title = array(feather_escape($this->config['o_board_title']), $lang_common['Profile'], $lang_profile['Change pass']);
            $required_fields = array('req_old_password' => $lang_profile['Old pass'], 'req_new_password1' => $lang_profile['New pass'], 'req_new_password2' => $lang_profile['Confirm new pass']);
            $focus_element = array('change_pass', ((!$this->user->is_admmod) ? 'req_old_password' : 'req_new_password1'));

            define('FEATHER_ACTIVE_PAGE', 'profile');

            $this->header->setTitle($page_title)->setFocusElement($focus_element)->setRequiredFields($required_fields)->display();

            $this->feather->render('profile/change_pass.php', array(
                                    'lang_common' => $lang_common,
                                    'feather' => $this->feather,
                                    'lang_profile' => $lang_profile,
                                    'id' => $id,
                                    )
                            );

            $this->footer->display();
        } elseif ($action == 'change_email') {
            $this->model->change_email($id, $this->feather);

            $page_title = array(feather_escape($this->config['o_board_title']), $lang_common['Profile'], $lang_profile['Change email']);
            $required_fields = array('req_new_email' => $lang_profile['New email'], 'req_password' => $lang_common['Password']);
            $focus_element = array('change_email', 'req_new_email');

            define('FEATHER_ACTIVE_PAGE', 'profile');

            $this->header->setTitle($page_title)->setFocusElement($focus_element)->setRequiredFields($required_fields)->display();

            $this->feather->render('profile/change_mail.php', array(
                                    'lang_common' => $lang_common,
                                    'lang_profile' => $lang_profile,
                                    'id' => $id,
                                    )
                            );

            $this->footer->display();
        } elseif ($action == 'upload_avatar' || $action == 'upload_avatar2') {
            if ($this->config['o_avatars'] == '0') {
                message($lang_profile['Avatars disabled']);
            }

            if ($this->user->id != $id && !$this->user->is_admmod) {
                message($lang_common['No permission'], '403');
            }

            if ($this->feather->request()->isPost()) {
                $this->model->upload_avatar($id, $_FILES);
            }

            $page_title = array(feather_escape($this->config['o_board_title']), $lang_common['Profile'], $lang_profile['Upload avatar']);
            $required_fields = array('req_file' => $lang_profile['File']);
            $focus_element = array('upload_avatar', 'req_file');

            define('FEATHER_ACTIVE_PAGE', 'profile');

            $this->header->setTitle($page_title)->setFocusElement($focus_element)->setRequiredFields($required_fields)->display();

            $this->feather->render('profile/upload_avatar.php', array(
                                    'lang_common' => $lang_common,
                                    'lang_profile' => $lang_profile,
                                    'feather_config' => $this->config,
                                    'lang_profile' => $lang_profile,
                                    'id' => $id,
                                    )
                            );

            $this->footer->display();
            
        } elseif ($action == 'delete_avatar') {
            if ($this->user->id != $id && !$this->user->is_admmod) {
                message($lang_common['No permission'], '403');
            }

            

            $this->model->delete_avatar($id);

            redirect(get_link('user/'.$id.'/section/personality/'), $lang_profile['Avatar deleted redirect']);
        } elseif ($action == 'promote') {
            if ($this->user->g_id != FEATHER_ADMIN && ($this->user->g_moderator != '1' || $this->user->g_mod_promote_users == '0')) {
                message($lang_common['No permission'], '403');
            }

            $this->model->promote_user($id, $this->feather);
        } else {
            message($lang_common['Bad request'], '404');
        }
    }
}
