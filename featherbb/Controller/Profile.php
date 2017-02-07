<?php

/**
 * Copyright (C) 2015-2016 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace FeatherBB\Controller;

use FeatherBB\Core\Error;
use FeatherBB\Core\Url;
use FeatherBB\Core\Utils;
use FeatherBB\Core\Database as DB;
use FeatherBB\Model\Delete;

class Profile
{
    public function __construct()
    {
        $this->model = new \FeatherBB\Model\Profile();
        translate('profile');
        translate('register');
        translate('prof_reg');
        translate('misc');
    }

    public function display($req, $res, $args)
    {
        if ($args['id'] < 2) {
            throw new Error(__('Bad request'), 400);
        }

        // Include UTF-8 function
        require ForumEnv::get('FEATHER_ROOT').'featherbb/Helpers/utf8/substr_replace.php';
        require ForumEnv::get('FEATHER_ROOT').'featherbb/Helpers/utf8/ucwords.php'; // utf8_ucwords needs utf8_substr_replace
        require ForumEnv::get('FEATHER_ROOT').'featherbb/Helpers/utf8/strcasecmp.php';

        $args['id'] = Container::get('hooks')->fire('controller.profile.display', $args['id']);

        if (Input::post('update_group_membership')) {
            if (User::get()->g_id > ForumEnv::get('FEATHER_ADMIN')) {
                throw new Error(__('No permission'), 403);
            }

            return $this->model->update_group_membership($args['id']);
        } elseif (Input::post('update_forums')) {
            if (User::get()->g_id > ForumEnv::get('FEATHER_ADMIN')) {
                throw new Error(__('No permission'), 403);
            }

            return $this->model->update_mod_forums($args['id']);
        } elseif (Input::post('ban')) {
            if (!User::isAdmin() && (!User::isAdminMod() || !User::can('mod.ban_users'))) {
                throw new Error(__('No permission'), 403);
            }

            return $this->model->ban_user($args['id']);
        } elseif (Input::post('delete_user') || Input::post('delete_user_comply')) {
            if (User::get()->g_id > ForumEnv::get('FEATHER_ADMIN')) {
                throw new Error(__('No permission'), 403);
            }

            if (Input::post('delete_user_comply')) {
                return $this->model->delete_user($args['id']);
            } else {
                return View::setPageInfo([
                    'title' => [Utils::escape(ForumSettings::get('o_board_title')), __('Profile'), __('Confirm delete user')],
                    'active_page' => 'profile',
                    'username' => $this->model->get_username($args['id']),
                    'id' => $args['id'],
                ])->addTemplate('profile/delete_user.php')->display();
            }
        } elseif (Input::post('form_sent')) {

            // Fetch the user group of the user we are editing
            $info = $this->model->fetch_user_group($args['id']);

            if (User::get()->id != $args['id'] &&                                                            // If we aren't the user (i.e. editing your own profile)
                                    (!User::isAdminMod() ||                                      // and we are not an admin or mod
                                    (!User::isAdmin() &&                           // or we aren't an admin and ...
                                    (!User::can('mod.edit_users') ||                         // mods aren't allowed to edit users
                                    $info['group_id'] == ForumEnv::get('FEATHER_ADMIN') ||                            // or the user is an admin
                                    Container::get('perms')->getGroupPermissions($info['group_id'], 'mod.is_mod'))))) {                                      // or the user is another mod
                                    throw new Error(__('No permission'), 403);
            }

            return $this->model->update_profile($args['id'], $info, $args['section']);
        }

        $user = $this->model->get_user_info($args['id']);

        if ($user['signature'] != '') {
            $parsed_signature = Container::get('parser')->parse_signature($user['signature']);
        }

        // View or edit?
        if (User::get()->id != $args['id'] &&                     // If we aren't the user (i.e. editing your own profile)
                (!User::isAdminMod() ||                           // and we are not an admin or mod
                (!User::isAdmin() &&                              // or we aren't an admin and ...
                (!User::can('mod.edit_users') ||                  // mods aren't allowed to edit users
                User::isAdmin($user) ||                     // or the user is an admin
                User::isAdminMod($user))))                  // or the user is another mod
            )
        {
            $user_info = $this->model->parse_user_info($user);

            View::setPageInfo([
                'title' => [Utils::escape(ForumSettings::get('o_board_title')), sprintf(__('Users profile'), Utils::escape($user['username']))],
                'active_page' => 'profile',
                'user_info' => $user_info,
                'id' => $args['id']
            ]);

            View::addTemplate('profile/view_profile.php')->display();
        } else {
            if (!isset($args['section']) || $args['section'] == 'essentials') {
                $user_disp = $this->model->edit_essentials($args['id'], $user);

                View::setPageInfo([
                    'title' => [Utils::escape(ForumSettings::get('o_board_title')), __('Profile'), __('Section essentials')],
                    'active_page' => 'profile',
                    'id' => $args['id'],
                    'page' => 'essentials',
                    'user' => $user,
                    'user_disp' => $user_disp,
                    'forum_time_formats' => Container::get('forum_time_formats'),
                    'forum_date_formats' => Container::get('forum_date_formats')
                ]);

                View::addTemplate('profile/menu.php', 5)->addTemplate('profile/section_essentials.php')->display();

            } elseif ($args['section'] == 'personal') {
                if (User::can('user.set_title')) {
                    $title_field = '<label>'.__('Title').' <em>('.__('Leave blank').')</em><br /><input type="text" name="title" value="'.Utils::escape($user['title']).'" size="30" maxlength="50" /><br /></label>'."\n";
                }

                View::setPageInfo([
                    'title' => [Utils::escape(ForumSettings::get('o_board_title')), __('Profile'), __('Section personal')],
                    'active_page' => 'profile',
                    'id' => $args['id'],
                    'page' => 'personal',
                    'user' => $user,
                    'title_field' => $title_field,
                ]);

                View::addTemplate('profile/menu.php', 5)->addTemplate('profile/section_personal.php')->display();

            } elseif ($args['section'] == 'personality') {
                if (ForumSettings::get('o_avatars') == '0' && ForumSettings::get('o_signatures') == '0') {
                    throw new Error(__('Bad request'), 404);
                }

                $avatar_field = '<span><a href="'.Router::pathFor('profileAction', ['id' => $args['id'], 'action' => 'upload_avatar']).'">'.__('Change avatar').'</a></span>';

                $user_avatar = Utils::generate_avatar_markup($args['id']);
                if ($user_avatar) {
                    $avatar_field .= ' <span><a href="'.Router::pathFor('profileAction', ['id' => $args['id'], 'action' => 'delete_avatar']).'">'.__('Delete avatar').'</a></span>';
                } else {
                    $avatar_field = '<span><a href="'.Router::pathFor('profileAction', ['id' => $args['id'], 'action' => 'upload_avatar']).'">'.__('Upload avatar').'</a></span>';
                }

                if ($user['signature'] != '') {
                    $signature_preview = '<p>'.__('Sig preview').'</p>'."\n\t\t\t\t\t\t\t".'<div class="postsignature postmsg">'."\n\t\t\t\t\t\t\t\t".'<hr />'."\n\t\t\t\t\t\t\t\t".$parsed_signature."\n\t\t\t\t\t\t\t".'</div>'."\n";
                } else {
                    $signature_preview = '<p>'.__('No sig').'</p>'."\n";
                }

                View::setPageInfo([
                    'title' => [Utils::escape(ForumSettings::get('o_board_title')), __('Profile'), __('Section personality')],
                    'active_page' => 'profile',
                    'user_avatar' => $user_avatar,
                    'avatar_field' => $avatar_field,
                    'signature_preview' => $signature_preview,
                    'page' => 'personality',
                    'user' => $user,
                    'id' => $args['id'],
                ]);

                View::addTemplate('profile/menu.php', 5)->addTemplate('profile/section_personality.php')->display();

            } elseif ($args['section'] == 'display') {

                View::setPageInfo([
                    'title' => [Utils::escape(ForumSettings::get('o_board_title')), __('Profile'), __('Section display')],
                    'active_page' => 'profile',
                    'page' => 'display',
                    'user' => $user,
                    'id' => $args['id']
                ]);

                View::addTemplate('profile/menu.php', 5)->addTemplate('profile/section_display.php')->display();

            } elseif ($args['section'] == 'privacy') {

                View::setPageInfo([
                    'title' => [Utils::escape(ForumSettings::get('o_board_title')), __('Profile'), __('Section privacy')],
                    'active_page' => 'profile',
                    'page' => 'privacy',
                    'user' => $user,
                    'id' => $args['id']
                ]);

                View::addTemplate('profile/menu.php', 5)->addTemplate('profile/section_privacy.php')->display();

            } elseif ($args['section'] == 'admin') {

                if (!User::isAdminMod() || (User::isAdminMod() && !User::can('mod.ban_users'))) {
                    throw new Error(__('Bad request'), 404);
                }

                View::setPageInfo([
                    'title' => [Utils::escape(ForumSettings::get('o_board_title')), __('Profile'), __('Section admin')],
                    'active_page' => 'profile',
                    'page' => 'admin',
                    'user' => $user,
                    'forum_list' => $this->model->get_forum_list($args['id']),
                    'group_list' => $this->model->get_group_list($user),
                    'id' => $args['id']
                ]);

                return View::addTemplate('profile/menu.php', 5)->addTemplate('profile/section_admin.php')->display();
            } else {
                throw new Error(__('Bad request'), 404);
            }
        }
    }

    public function action($req, $res, $args)
    {
        // Include UTF-8 function
        require ForumEnv::get('FEATHER_ROOT').'featherbb/Helpers/utf8/substr_replace.php';
        require ForumEnv::get('FEATHER_ROOT').'featherbb/Helpers/utf8/ucwords.php'; // utf8_ucwords needs utf8_substr_replace
        require ForumEnv::get('FEATHER_ROOT').'featherbb/Helpers/utf8/strcasecmp.php';

        $args['id'] = Container::get('hooks')->fire('controller.profile.action', $args['id']);

        if ($args['action'] != 'change_pass' || !Input::query('key')) {
            if (!User::can('board.read')) {
                throw new Error(__('No view'), 403);
            } elseif (!User::can('users.view') && (User::get()->is_guest || User::get()->id != $args['id'])) {
                throw new Error(__('No permission'), 403);
            }
        }

        // Make sure user exists
        if (!DB::for_table('users')->find_one($args['id']) || $args['id'] < 2) {
            throw new Error(__('Bad request'), 404);
        }

        if ($args['action'] == 'change_pass') {
            // Make sure we are allowed to change this user's password
            if (User::get()->id != $args['id']) {
                $args['id'] = Container::get('hooks')->fire('controller.profile.change_pass_key_not_id', $args['id']);

                if (!User::isAdminMod()) { // A regular user trying to change another user's password?
                    throw new Error(__('No permission'), 403);
                } elseif (User::isAdminMod()) {
                    // A moderator trying to change a user's password?
                    if (!User::can('mod.edit_users') || !User::can('mod.change_passwords') || User::isAdminMod($args['id'])) {
                        throw new Error(__('No permission'), 403);
                    }
                }
            }

            // User is allowed to change pass and has submitted the forum, do it
            if (Request::isPost()) {
                return $this->model->change_pass($args['id']);
            }

            return View::setPageInfo([
                    'title' => [Utils::escape(ForumSettings::get('o_board_title')), __('Profile'), __('Change pass')],
                    'active_page' => 'profile',
                    'id' => $args['id']
                ]
            )->addTemplate('profile/change_pass.php')->display();

        } elseif ($args['action'] == 'change_email') {
            // Make sure we are allowed to change this user's email
            if (User::get()->id != $args['id']) {
                $args['id'] = Container::get('hooks')->fire('controller.profile.change_email_not_id', $args['id']);

                if (!User::isAdminMod()) { // A regular user trying to change another user's email?
                    throw new Error(__('No permission'), 403);
                } elseif (User::isAdminMod()) {
                    // A moderator trying to change a user's email?
                    if (!User::can('mod.edit_users') || !User::can('mod.change_passwords') || User::isAdminMod($args['id'])) {
                        throw new Error(__('No permission'), 403);
                    }
                }
            }

            // User is allowed to change email and has submitted the forum, do it
            if (Request::isPost() || Input::query('key')) {
                return $this->model->change_email($args['id']);
            }

            return View::setPageInfo([
                    'title' => [Utils::escape(ForumSettings::get('o_board_title')), __('Profile'), __('Change email')],
                    'active_page' => 'profile',
                    'id' => $args['id'],
                ]
            )->addTemplate('profile/change_mail.php')->display();

        } elseif ($args['action'] == 'upload_avatar' || $args['action'] == 'upload_avatar2') {
            if (ForumSettings::get('o_avatars') == '0') {
                throw new Error(__('Avatars disabled'), 400);
            }

            if (User::get()->id != $args['id'] && !User::isAdminMod()) {
                throw new Error(__('No permission'), 403);
            }

            if (Request::isPost()) {
                return $this->model->upload_avatar($args['id'], $_FILES);
            }

            return View::setPageInfo([
                    'title' => [Utils::escape(ForumSettings::get('o_board_title')), __('Profile'), __('Upload avatar')],
                    'active_page' => 'profile',
                    'id' => $args['id'],
                ]
            )->addTemplate('profile/upload_avatar.php')->display();

        } elseif ($args['action'] == 'delete_avatar') {
            if (User::get()->id != $args['id'] && !User::isAdminMod()) {
                throw new Error(__('No permission'), 403);
            }

            $this->model->delete_avatar($args['id']);

            return Router::redirect(Router::pathFor('profileSection', ['id' => $args['id'], 'section' => 'personality']), __('Avatar deleted redirect'));
        } elseif ($args['action'] == 'promote') {
            if (!User::isAdmin() && (!User::isAdminMod() || !User::can('mod.promote_users'))) {
                throw new Error(__('No permission'), 403);
            }

            return $this->model->promote_user($args['id'], $args['pid']);
        } else {
            throw new Error(__('Bad request'), 404);
        }
    }

    public function email($req, $res, $args)
    {
        $args['id'] = Container::get('hooks')->fire('controller.profile.email', $args['id']);

        if (!User::can('email.send')) {
            throw new Error(__('No permission'), 403);
        }

        if ($args['id'] < 2) {
            throw new Error(__('Bad request'), 400);
        }

        $mail = $this->model->get_info_mail($args['id']);

        if ($mail['email_setting'] == 2 && !User::isAdminMod()) {
            throw new Error(__('Form email disabled'), 403);
        }

        if (Request::isPost()) {
            return $this->model->send_email($mail);
        }

        return View::setPageInfo([
            'title' => [Utils::escape(ForumSettings::get('o_board_title')), __('Send email to').' '.Utils::escape($mail['recipient'])],
            'active_page' => 'email',
            'mail' => $mail
        ])->addTemplate('misc/email.php')->display();
    }

    public function gethostip($req, $res, $args)
    {
        $args['ip'] = Container::get('hooks')->fire('controller.profile.gethostip', $args['ip']);

        $this->model->display_ip_info($args['ip']);
    }
}
