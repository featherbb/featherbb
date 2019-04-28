<?php

/**
 * Copyright (C) 2015-2019 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace FeatherBB\Model;

use FeatherBB\Core\Database as DB;
use FeatherBB\Core\Email;
use FeatherBB\Core\Error;
use FeatherBB\Core\Interfaces\Cache as CacheInterface;
use FeatherBB\Core\Interfaces\Container;
use FeatherBB\Core\Interfaces\ForumEnv;
use FeatherBB\Core\Interfaces\ForumSettings;
use FeatherBB\Core\Interfaces\Hooks;
use FeatherBB\Core\Interfaces\Input;
use FeatherBB\Core\Interfaces\Lang;
use FeatherBB\Core\Interfaces\Parser;
use FeatherBB\Core\Interfaces\Perms;
use FeatherBB\Core\Interfaces\Prefs;
use FeatherBB\Core\Interfaces\Request;
use FeatherBB\Core\Interfaces\Router;
use FeatherBB\Core\Interfaces\User;
use FeatherBB\Core\Random;
use FeatherBB\Core\Url;
use FeatherBB\Core\Utils;
use FeatherBB\Model\Auth as AuthModel;

class Profile
{
    public function changePass($id)
    {
        $id = Hooks::fire('model.profile.change_pass_start', $id);

        $oldPassword = Input::post('req_old_password');
        $newPassword1 = Input::post('req_new_password1');
        $newPassword2 = Input::post('req_new_password2');

        if ($newPassword1 != $newPassword2) {
            throw new Error(__('Pass not match'), 400);
        }
        if (Utils::strlen($newPassword1) < 6) {
            throw new Error(__('Pass too short'), 400);
        }

        $curUser = DB::table('users')
            ->where('id', $id);
        $curUser = Hooks::fireDB('model.profile.change_pass_find_user', $curUser);
        $curUser = $curUser->findOne();

        $authorized = false;

        if (!empty($curUser['password'])) {
            $oldPasswordHash = Utils::passwordHash($oldPassword);

            if (Utils::passwordVerify($oldPassword, $curUser['password']) || User::isAdminMod()) {
                $authorized = true;
            }
        }

        if (!$authorized) {
            throw new Error(__('Wrong pass'), 403);
        }

        $newPasswordHash = Utils::passwordHash($newPassword1);

        $updatePassword = DB::table('users')
            ->where('id', $id)
            ->findOne()
            ->set('password', $newPasswordHash);
        $updatePassword = Hooks::fireDB('model.profile.change_pass_query', $updatePassword);
        $updatePassword = $updatePassword->save();

        if (User::get()->id == $id) {
            $expire = time() + ForumSettings::get('o_timeout_visit');
            $jwt = AuthModel::generateJwt(User::get(), $expire);
            AuthModel::setCookie('Bearer '.$jwt, $expire);
        }

        Hooks::fire('model.profile.change_pass');
        return Router::redirect(Router::pathFor('profileSection', ['id' => $id, 'section' => 'essentials']), __('Pass updated redirect'));
    }

    public function changeEmail($id)
    {
        $id = Hooks::fire('model.profile.change_email_start', $id);

        if (Input::query('key')) {
            $key = Input::query('key');
            $key = Hooks::fire('model.profile.change_email_key', $key);

            $newEmailKey = DB::table('users')
                ->where('id', $id);
            $newEmailKey = Hooks::fireDB('model.profile.change_email_key_query', $newEmailKey);
            $newEmailKey = $newEmailKey->findOneCol('activate_key');

            if ($key == '' || $key != $newEmailKey) {
                throw new Error(__('Email key bad').' <a href="mailto:'.Utils::escape(ForumSettings::get('o_admin_email')).'">'.Utils::escape(ForumSettings::get('o_admin_email')).'</a>.', 400, true, true);
            } else {
                $updateMail = DB::table('users')
                    ->where('id', $id)
                    ->findOne()
                    ->setExpr('email', 'activate_string')
                    ->setExpr('activate_string', 'NULL')
                    ->setExpr('activate_key', 'NULL');
                $updateMail = Hooks::fireDB('model.profile.change_email_query', $updateMail);
                $updateMail = $updateMail->save();

                Hooks::fire('model.profile.change_email_updated');

                return Router::redirect(Router::pathFor('home'), __('Email updated'));
            }
        } elseif (Request::isPost()) {
            Hooks::fire('model.profile.change_email_post');

            if (!Utils::passwordVerify(Input::post('req_password'), User::get()->password)) {
                throw new Error(__('Wrong pass'));
            }

            // Validate the email address
            $newEmail = strtolower(Utils::trim(Input::post('req_new_email')));
            $newEmail = Hooks::fire('model.profile.change_email_new_email', $newEmail);
            if (!Email::isValidEmail($newEmail)) {
                throw new Error(__('Invalid email'), 400);
            }

            // Check if it's a banned email address
            if (Email::isBannedEmail($newEmail)) {
                if (ForumSettings::get('p_allow_banned_email') == 0) {
                    throw new Error(__('Banned email'), 403);
                } elseif (ForumSettings::get('o_mailing_list') != '') {
                    // Load the "banned email change" template
                    $mailTpl = trim(file_get_contents(ForumEnv::get('FEATHER_ROOT').'featherbb/lang/'.User::getPref('language').'/mail_templates/banned_email_change.tpl'));
                    $mailTpl = Hooks::fire('model.profile.change_email_mail_tpl', $mailTpl);

                    // The first row contains the subject
                    $firstCrlf = strpos($mailTpl, "\n");
                    $mailSubject = trim(substr($mailTpl, 8, $firstCrlf-8));
                    $mailSubject = Hooks::fire('model.profile.change_email_mail_subject', $mailSubject);

                    $mailMessage = trim(substr($mailTpl, $firstCrlf));
                    $mailMessage = str_replace('<username>', User::get()->username, $mailMessage);
                    $mailMessage = str_replace('<email>', $newEmail, $mailMessage);
                    $mailMessage = str_replace('<profile_url>', Router::pathFor('userProfile', ['id' => $id]), $mailMessage);
                    $mailMessage = str_replace('<board_mailer>', ForumSettings::get('o_board_title'), $mailMessage);
                    $mailMessage = Hooks::fire('model.profile.change_email_mail_message', $mailMessage);

                    Email::send(ForumSettings::get('o_mailing_list'), $mailSubject, $mailMessage);
                }
            }

            // Check if someone else already has registered with that email address
            $result['select'] = ['id', 'username'];

            $result = DB::table('users')
                ->selectMany($result['select'])
                ->where('email', $newEmail);
            $result = Hooks::fireDB('model.profile.change_email_check_mail', $result);
            $result = $result->findMany();

            if ($result) {
                if (ForumSettings::get('p_allow_dupe_email') == 0) {
                    throw new Error(__('Dupe email'), 400);
                } elseif (ForumSettings::get('o_mailing_list') != '') {
                    foreach ($result as $curDupe) {
                        $dupeList[] = $curDupe['username'];
                    }

                    // Load the "dupe email change" template
                    $mailTpl = trim(file_get_contents(ForumEnv::get('FEATHER_ROOT').'featherbb/lang/'.User::getPref('language').'/mail_templates/dupe_email_change.tpl'));
                    $mailTpl = Hooks::fire('model.profile.change_email_mail_dupe_tpl', $mailTpl);

                    // The first row contains the subject
                    $firstCrlf = strpos($mailTpl, "\n");
                    $mailSubject = trim(substr($mailTpl, 8, $firstCrlf-8));
                    $mailSubject = Hooks::fire('model.profile.change_email_mail_dupe_subject', $mailSubject);

                    $mailMessage = trim(substr($mailTpl, $firstCrlf));
                    $mailMessage = str_replace('<username>', User::get()->username, $mailMessage);
                    $mailMessage = str_replace('<dupe_list>', implode(', ', $dupeList), $mailMessage);
                    $mailMessage = str_replace('<profile_url>', Router::pathFor('userProfile', ['id' => $id]), $mailMessage);
                    $mailMessage = str_replace('<board_mailer>', ForumSettings::get('o_board_title'), $mailMessage);
                    $mailMessage = Hooks::fire('model.profile.change_email_mail_dupe_message', $mailMessage);

                    Email::send(ForumSettings::get('o_mailing_list'), $mailSubject, $mailMessage);
                }
            }


            $newEmailKey = Random::pass(8);
            $newEmailKey = Hooks::fire('model.profile.change_email_new_email_key', $newEmailKey);

            // Update the user
            unset($user);
            $user['update'] = [
                'activate_string' => $newEmail,
                'activate_key'  => $newEmailKey,
            ];
            $user = DB::table('users')
                ->where('id', $id)
                ->findOne()
                ->set($user['update']);
            $user = Hooks::fireDB('model.profile.change_email_user_query', $user);
            $user = $user->save();

            // Load the "activate email" template
            $mailTpl = trim(file_get_contents(ForumEnv::get('FEATHER_ROOT').'featherbb/lang/'.User::getPref('language').'/mail_templates/activate_email.tpl'));
            $mailTpl = Hooks::fire('model.profile.change_email_mail_activate_tpl', $mailTpl);

            // The first row contains the subject
            $firstCrlf = strpos($mailTpl, "\n");
            $mailSubject = trim(substr($mailTpl, 8, $firstCrlf-8));
            $mailSubject = Hooks::fire('model.profile.change_email_mail_activate_subject', $mailSubject);

            $mailMessage = trim(substr($mailTpl, $firstCrlf));
            $mailMessage = str_replace('<username>', User::get()->username, $mailMessage);
            $mailMessage = str_replace('<base_url>', Url::base(), $mailMessage);
            $mailMessage = str_replace('<activation_url>', Router::pathFor('profileAction', ['id' => $id, 'action' => 'change_email'], ['key' => $newEmailKey]), $mailMessage);
            $mailMessage = str_replace('<board_mailer>', ForumSettings::get('o_board_title'), $mailMessage);
            $mailMessage = Hooks::fire('model.profile.change_email_mail_activate_message', $mailMessage);

            Email::send($newEmail, $mailSubject, $mailMessage);

            Hooks::fire('model.profile.change_email_sent');

            $message = __('Activate email sent').' <a href="mailto:'.Utils::escape(ForumSettings::get('o_admin_email')).'">'.Utils::escape(ForumSettings::get('o_admin_email')).'</a>.';
            return Router::redirect(Router::pathFor('userProfile', ['id' => $id]), $message);
        }
    }

    public function uploadAvatar($id, $filesData)
    {
        $filesData = Hooks::fire('model.profile.upload_avatar_start', $filesData, $id);

        if (!isset($filesData['req_file'])) {
            throw new Error(__('No file'));
        }

        $uploadedFile = $filesData['req_file'];

        // Make sure the upload went smooth
        if (isset($uploadedFile['error'])) {
            switch ($uploadedFile['error']) {
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
                    if ($uploadedFile['size'] == 0) {
                        throw new Error(__('No file'));
                    }
                    break;
            }
        }

        if (is_uploaded_file($uploadedFile['tmp_name'])) {
            $uploadedFile = Hooks::fire('model.profile.upload_avatar_is_uploaded_file', $uploadedFile);

            // Preliminary file check, adequate in most cases
            $allowedTypes = ['image/gif', 'image/jpeg', 'image/pjpeg', 'image/png', 'image/x-png'];
            if (!in_array($uploadedFile['type'], $allowedTypes)) {
                throw new Error(__('Bad type'));
            }

            // Make sure the file isn't too big
            if ($uploadedFile['size'] > ForumSettings::get('o_avatars_size')) {
                throw new Error(__('Too large').' '.Utils::forumNumberFormat(ForumSettings::get('o_avatars_size')).' '.__('bytes').'.');
            }

            // Move the file to the avatar directory. We do this before checking the width/height to circumvent open_basedir restrictions
            if (!@move_uploaded_file($uploadedFile['tmp_name'], ForumEnv::get('FEATHER_ROOT').ForumSettings::get('o_avatars_dir').'/'.$id.'.tmp')) {
                throw new Error(__('Move failed').' <a href="mailto:'.Utils::escape(ForumSettings::get('o_admin_email')).'">'.Utils::escape(ForumSettings::get('o_admin_email')).'</a>.', 400, true, true);
            }

            list($width, $height, $type, ) = @getimagesize(ForumEnv::get('FEATHER_ROOT').ForumSettings::get('o_avatars_dir').'/'.$id.'.tmp');

            // Determine type
            if ($type == IMAGETYPE_GIF) {
                $extension = '.gif';
            } elseif ($type == IMAGETYPE_JPEG) {
                $extension = '.jpg';
            } elseif ($type == IMAGETYPE_PNG) {
                $extension = '.png';
            } else {
                // Invalid type
                @unlink(ForumEnv::get('FEATHER_ROOT').ForumSettings::get('o_avatars_dir').'/'.$id.'.tmp');
                throw new Error(__('Bad type'));
            }

            // Now check the width/height
            if (empty($width) || empty($height) || $width > ForumSettings::get('o_avatars_width') || $height > ForumSettings::get('o_avatars_height')) {
                @unlink(ForumEnv::get('FEATHER_ROOT').ForumSettings::get('o_avatars_dir').'/'.$id.'.tmp');
                throw new Error(__('Too wide or high').' '.ForumSettings::get('o_avatars_width').'x'.ForumSettings::get('o_avatars_height').' '.__('pixels').'.');
            }

            // Delete any old avatars and put the new one in place
            $this->deleteAvatar($id);
            @rename(ForumEnv::get('FEATHER_ROOT').ForumSettings::get('o_avatars_dir').'/'.$id.'.tmp', ForumEnv::get('FEATHER_ROOT').ForumSettings::get('o_avatars_dir').'/'.$id.$extension);
            @chmod(ForumEnv::get('FEATHER_ROOT').ForumSettings::get('o_avatars_dir').'/'.$id.$extension, 0644);
        } else {
            throw new Error(__('Unknown failure'));
        }

        $uploadedFile = Hooks::fire('model.profile.upload_avatar', $uploadedFile);

        return Router::redirect(Router::pathFor('profileSection', ['id' => $id, 'section' => 'personality']), __('Avatar upload redirect'));
    }

    //
    // Deletes any avatars owned by the specified user ID
    //
    public function deleteAvatar($userId)
    {
        $filetypes = ['jpg', 'gif', 'png'];

        // Delete user avatar
        foreach ($filetypes as $curType) {
            if (file_exists(ForumEnv::get('FEATHER_ROOT').ForumSettings::get('o_avatars_dir').'/'.$userId.'.'.$curType)) {
                @unlink(ForumEnv::get('FEATHER_ROOT').ForumSettings::get('o_avatars_dir').'/'.$userId.'.'.$curType);
            }
        }
    }

    public function updateGroupMembership($id)
    {
        $id = Hooks::fire('model.profile.update_group_membership_start', $id);

        $newGroupId = intval(Input::post('group_id'));

        $oldGroupId = DB::table('users')
            ->where('id', $id);
        $oldGroupId = Hooks::fireDB('model.profile.update_group_membership_old_group', $oldGroupId);
        $oldGroupId = $oldGroupId->findOneCol('group_id');

        $updateGroup = DB::table('users')
            ->where('id', $id)
            ->findOne()
            ->set('group_id', $newGroupId);
        $updateGroup = Hooks::fireDB('model.profile.update_group_membership_update_group', $updateGroup);
        $updateGroup = $updateGroup->save();

        // Regenerate the users info cache
        if (!CacheInterface::isCached('users_info')) {
            CacheInterface::store('users_info', Cache::getUsersInfo());
        }

        $stats = CacheInterface::retrieve('users_info');

        if ($oldGroupId == ForumEnv::get('FEATHER_ADMIN') || $newGroupId == ForumEnv::get('FEATHER_ADMIN')) {
            CacheInterface::store('admin_ids', Cache::getAdminIds());
        }

        // If the user was a moderator or an administrator, we remove him/her from the moderator list in all forums as well
        if ($newGroupId != ForumEnv::get('FEATHER_ADMIN') && !Perms::getGroupPermissions($newGroupId, 'mod.is_mod')) {

            // Loop through all forums
            $result = $this->loopModForums();

            foreach ($result as $curForum) {
                $curModerators = ($curForum['moderators'] != '') ? unserialize($curForum['moderators']) : [];

                if (in_array($id, $curModerators)) {
                    $username = array_search($id, $curModerators);
                    unset($curModerators[$username]);

                    $updateForums = DB::table('forums')
                        ->where('id', $curForum['id'])
                        ->findOne();

                    if (!empty($curModerators)) {
                        $updateForums = $updateForums->set('moderators', serialize($curModerators));
                    } else {
                        $updateForums = $updateForums->setExpr('moderators', 'NULL');
                    }
                    $updateForums = Hooks::fireDB('model.profile.update_group_membership_mod_forums', $updateForums);
                    $updateForums = $updateForums->save();
                }
            }
        }

        $id = Hooks::fire('model.profile.update_group_membership', $id);

        return Router::redirect(Router::pathFor('profileSection', ['id' => $id, 'section' => 'admin']), __('Group membership redirect'));
    }

    public function getUsername($id)
    {
        // Get the username of the user we are processing
        $username = DB::table('users')
            ->where('id', $id)
            ->findOneCol('username');

        $username = Hooks::fire('model.profile.get_username', $username);

        return $username;
    }

    public function loopModForums()
    {
        $result['select'] = ['id', 'moderators'];

        $result = DB::table('forums')
            ->selectMany($result['select']);
        $result = Hooks::fireDB('model.profile.loop_mod_forums', $result);
        $result = $result->findMany();

        return $result;
    }

    public function updateModForums($id)
    {
        $username = $this->getUsername($id);

        $moderatorIn = (Input::post('moderator_in')) ? array_keys(Input::post('moderator_in')) : [];

        // Loop through all forums
        $result = $this->loopModForums();

        foreach ($result as $curForum) {
            $curModerators = ($curForum['moderators'] != '') ? unserialize($curForum['moderators']) : [];
            // If the user should have moderator access (and he/she doesn't already have it)
            if (in_array($curForum['id'], $moderatorIn) && !in_array($id, $curModerators)) {
                $curModerators[$username] = $id;
                uksort($curModerators, [$this, 'utf8_strcasecmp']);

                $updateForums = DB::table('forums')
                    ->where('id', $curForum['id'])
                    ->findOne()
                    ->set('moderators', serialize($curModerators));
                $updateForums = Hooks::fireDB('model.profile.update_mod_forums_query', $updateForums);
                $updateForums = $updateForums->save();
            }
            // If the user shouldn't have moderator access (and he/she already has it)
            elseif (!in_array($curForum['id'], $moderatorIn) && in_array($id, $curModerators)) {
                unset($curModerators[$username]);

                $updateForums = DB::table('forums')
                    ->where('id', $curForum['id'])
                    ->findOne();

                if (!empty($curModerators)) {
                    $updateForums = $updateForums->set('moderators', serialize($curModerators));
                } else {
                    $updateForums = $updateForums->setExpr('moderators', 'NULL');
                }
                $updateForums = Hooks::fireDB('model.profile.update_mod_forums_query', $updateForums);
                $updateForums = $updateForums->save();
            }
        }

        $id = Hooks::fire('model.profile.update_mod_forums', $id);

        return Router::redirect(Router::pathFor('profileSection', ['id' => $id, 'section' => 'admin']), __('Update forums redirect'));
    }

    public function banUser($id)
    {
        $id = Hooks::fire('model.profile.ban_user_start', $id);

        // Get the username of the user we are banning
        $username = $this->getUsername($id);

        // Check whether user is already banned
        $banId = DB::table('bans')
            ->where('username', $username)
            ->orderByExpr('expire IS NULL DESC')
            ->orderByDesc('expire');
        $banId = Hooks::fireDB('model.profile.ban_user_query', $banId);
        $banId = $banId->findOneCol('id');

        if ($banId) {
            return Router::redirect(Router::pathFor('editBan', ['id' => $banId]), __('Ban redirect'));
        } else {
            return Router::redirect(Router::pathFor('addBan', ['id' => $id]), __('Ban redirect'));
        }
    }

    public function promoteUser($id, $pid)
    {
        $id = Hooks::fire('model.profile.promote_user.user_id', $id);
        $pid = Hooks::fire('model.profile.promote_user.post_id', $pid);

        // Find the group ID to promote the user to
        $nextGroupId = Hooks::fire('model.profile.promote_user.next_group_id', User::getPref('promote.next_group', $id));

        if (!$nextGroupId) {
            throw new Error(__('Bad request'), 404);
        }

        // Update the user
        $updateUser = DB::table('users')
            ->where('id', $id)
            ->findOne()
            ->set('group_id', $nextGroupId);
        $updateUser = Hooks::fireDB('model.profile.promote_user_query', $updateUser);
        $updateUser = $updateUser->save();

        // Get topic infos to redirect to
        $topicInfos = DB::table('posts')
            ->tableAlias('p')
            ->selectMany(['t.subject', 't.id'])
            ->innerJoin('topics', ['t.id', '=', 'p.topic_id'], 't')
            ->where('p.id', $pid)
            ->findOne();

        return Router::redirect(Router::pathFor('viewPost', ['id' => $topicInfos->id, 'name' => Url::slug($topicInfos->subject), 'pid' => $pid]).'#p'.$pid, __('User promote redirect'));
    }

    public function deleteUser($id)
    {
        $id = Hooks::fire('model.profile.delete_user_start', $id);

        // Get the username and group of the user we are deleting
        $result['select'] = ['group_id', 'username'];

        $result = DB::table('users')
            ->where('id', $id)
            ->selectMany($result['select']);
        $result = Hooks::fireDB('model.profile.delete_user_username', $result);
        $result = $result->findOne();

        $groupId = $result['group_id'];
        $username = $result['username'];

        if ($groupId == ForumEnv::get('FEATHER_ADMIN')) {
            throw new Error(__('No delete admin message'));
        }

        if (Input::post('delete_user_comply')) {
            // If the user is a moderator or an administrator, we remove him/her from the moderator list in all forums as well
            if ($groupId == ForumEnv::get('FEATHER_ADMIN') || Perms::getGroupPermissions($groupId, 'mod.is_mod')) {

                // Loop through all forums
                $result = $this->loopModForums();

                foreach ($result as $curForum) {
                    $curModerators = ($curForum['moderators'] != '') ? unserialize($curForum['moderators']) : [];

                    if (in_array($id, $curModerators)) {
                        unset($curModerators[$username]);

                        $updateForums = DB::table('forums')
                            ->where('id', $curForum['id'])
                            ->findOne();

                        if (!empty($curModerators)) {
                            $updateForums = $updateForums->set('moderators', serialize($curModerators));
                        } else {
                            $updateForums = $updateForums->setExpr('moderators', 'NULL');
                        }
                        $updateForums = Hooks::fireDB('model.profile.update_mod_forums_query', $updateForums);
                        $updateForums = $updateForums->save();
                    }
                }
            }

            // Delete any subscriptions
            $deleteSubscriptions = DB::table('topic_subscriptions')
                ->where('user_id', $id);
            $deleteSubscriptions = Hooks::fireDB('model.profile.delete_user_subscriptions_topic', $deleteSubscriptions);
            $deleteSubscriptions = $deleteSubscriptions->deleteMany();
            unset($deleteSubscriptions);
            $deleteSubscriptions = DB::table('forum_subscriptions')
                ->where('user_id', $id);
            $deleteSubscriptions = Hooks::fireDB('model.profile.delete_user_subscriptions_forum', $deleteSubscriptions);
            $deleteSubscriptions = $deleteSubscriptions->deleteMany();

            // Remove him/her from the online list (if they happen to be logged in)
            $deleteOnline = DB::table('online')
                ->where('user_id', $id);
            $deleteOnline = Hooks::fireDB('model.profile.delete_user_online', $deleteOnline);
            $deleteOnline = $deleteOnline->deleteMany();

            // Should we delete all posts made by this user?
            if (Input::post('delete_posts')) {
                // Hold on, this could take some time!
                @set_time_limit(0);

                Hooks::fire('model.profile.delete_user_posts');

                // Find all posts made by this user
                unset($result);
                $result['select'] = ['p.id', 'p.topic_id', 't.forum_id'];

                $result = DB::table('posts')
                    ->tableAlias('p')
                    ->selectMany($result['select'])
                    ->innerJoin('topics', ['t.id', '=', 'p.topic_id'], 't')
                    ->innerJoin('forums', ['f.id', '=', 't.forum_id'], 'f')
                    ->where('p.poster_id', $id);
                $result = Hooks::fireDB('model.profile.delete_user_posts_first_query', $result);
                $result = $result->findMany();

                if ($result) {
                    foreach ($result as $curPost) {
                        // Determine whether this post is the "topic post" or not
                        $result2 = DB::table('posts')
                            ->where('topic_id', $curPost['topic_id'])
                            ->orderBy('posted');
                        $result2 = Hooks::fireDB('model.profile.delete_user_posts_second_query', $result2);
                        $result2 = $result2->findOneCol('id');

                        if ($result2 == $curPost['id']) {
                            Topic::delete($curPost['topic_id']);
                        } else {
                            Post::delete($curPost['id'], $curPost['topic_id']);
                        }

                        Forum::update($curPost['forum_id']);
                    }
                }
            } else {
                // Set all his/her posts to guest
                $updateGuest = DB::table('posts')
                    ->whereIn('poster_id', 1);
                $updateGuest = Hooks::fireDB('model.profile.delete_user_posts_guest_query', $updateGuest);
                $updateGuest = $updateGuest->updateMany('poster_id', $id);
            }

            // Delete the user
            $deleteUser = DB::table('users')
                            ->where('id', $id);
            $deleteUser = $deleteUser->deleteMany();

            // Delete user avatar
            $this->deleteAvatar($id);

            // Regenerate the users info cache
            CacheInterface::store('users_info', Cache::getUsersInfo());

            $stats = CacheInterface::retrieve('users_info');

            if ($groupId == ForumEnv::get('FEATHER_ADMIN')) {
                CacheInterface::store('admin_ids', Cache::getAdminIds());
            }

            Hooks::fire('model.profile.delete_user');

            return Router::redirect(Router::pathFor('home'), __('User delete redirect'));
        }
    }

    public function getUserGroup($id)
    {
        $info = [];

        $info['select'] = ['old_username' => 'u.username', 'group_id' => 'u.group_id'];

        $info = DB::table('users')
            ->tableAlias('u')
            ->selectMany($info['select'])
            ->leftOuterJoin('groups', ['g.g_id', '=', 'u.group_id'], 'g')
            ->where('u.id', $id);
        $info = Hooks::fireDB('model.profile.fetch_user_group', $info);
        $info = $info->findOne();

        if (!$info) {
            throw new Error(__('Bad request'), 404);
        }

        return $info;
    }

    public function updateProfile($id, $info, $section)
    {
        $info = Hooks::fire('model.profile.update_profile_start', $info, $id, $section);
        $section = Hooks::fire('model.profile.update_profile_section', $section, $id, $info);

        $usernameUpdated = false;
        $form = $prefs = [];

        // Validate input depending on section
        switch ($section) {
            case 'essentials':
            {
                $prefs = [
                    'timezone'        => floatval(Input::post('form_timezone')),
                    'dst'            => Input::post('form_dst') ? 1 : 0,
                    'time_format'    => Input::post('form_time_format'),
                    'date_format'    => Input::post('form_date_format'),
                ];

                // Make sure we got a valid language string
                if (Input::post('form_language')) {
                    $languages = \FeatherBB\Core\Lister::getLangs();
                    $prefs['language'] = Utils::trim(Input::post('form_language'));
                    if (!in_array($prefs['language'], $languages)) {
                        throw new Error(__('Bad request'), 404);
                    }
                }

                if (User::isAdminMod()) {
                    $form['admin_note'] = Utils::trim(Input::post('admin_note'));

                    // Are we allowed to change usernames?
                    if (User::isAdmin() || (User::isAdminMod() && User::can('mod.rename_users'))) {
                        $form['username'] = Utils::trim(Input::post('req_username'));

                        if ($form['username'] != $info['old_username']) {
                            $errors = [];
                            $errors = $this->checkUsername($form['username'], $errors, $id);
                            if (!empty($errors)) {
                                throw new Error($errors[0]);
                            }

                            $usernameUpdated = true;
                        }
                    }

                    // We only allow administrators to update the post count
                    if (User::isAdmin()) {
                        $form['num_posts'] = intval(Input::post('num_posts'));
                    }
                }

                if (ForumSettings::get('o_regs_verify') == 0 || User::isAdminMod()) {
                    // Validate the email address
                    $form['email'] = strtolower(Utils::trim(Input::post('req_email')));
                    if (!Email::isValidEmail($form['email'])) {
                        throw new Error(__('Invalid email'));
                    }
                }

                break;
            }

            case 'personal':
            {
                $form = [
                    'realname'        => Input::post('form_realname') ? Utils::trim(Input::post('form_realname')) : '',
                    'url'            => Input::post('form_url') ? Utils::trim(Input::post('form_url')) : '',
                    'location'        => Input::post('form_location') ? Utils::trim(Input::post('form_location')) : '',
                ];

                // Add http:// if the URL doesn't contain it already (while allowing https://, too)
                if (User::can('post.links')) {
                    if ($form['url'] != '') {
                        $url = Url::isValid($form['url']);

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

                if (User::isAdmin()) {
                    $form['title'] = Utils::trim(Input::post('title'));
                } elseif (User::can('user.set_title')) {
                    $form['title'] = Utils::trim(Input::post('title'));

                    if ($form['title'] != '') {
                        // A list of words that the title may not contain
                        // If the language is English, there will be some duplicates, but it's not the end of the world
                        $forbidden = ['member', 'moderator', 'administrator', 'banned', 'guest', \utf8\to_lower(__('Member')), \utf8\to_lower(__('Moderator')), \utf8\to_lower(__('Administrator')), \utf8\to_lower(__('Banned')), \utf8\to_lower(__('Guest'))];

                        if (in_array(\utf8\to_lower($form['title']), $forbidden)) {
                            throw new Error(__('Forbidden title'));
                        }
                    }
                }

                break;
            }

            case 'personality':
            {
                $form = [];

                // Clean up signature from POST
                if (ForumSettings::get('o_signatures') == 1) {
                    $form['signature'] = Utils::linebreaks(Utils::trim(Input::post('signature')));

                    // Validate signature
                    if (Utils::strlen($form['signature']) > ForumSettings::get('p_sig_length')) {
                        throw new Error(sprintf(__('Sig too long'), ForumSettings::get('p_sig_length'), Utils::strlen($form['signature']) - ForumSettings::get('p_sig_length')));
                    } elseif (substr_count($form['signature'], "\n") > (ForumSettings::get('p_sig_lines')-1)) {
                        throw new Error(sprintf(__('Sig too many lines'), ForumSettings::get('p_sig_lines')));
                    } elseif ($form['signature'] && ForumSettings::get('p_sig_all_caps') == 0 && Utils::isAllUppercase($form['signature']) && !User::isAdminMod()) {
                        $form['signature'] = \utf8\ucwords(\utf8\to_lower($form['signature']));
                    }

                    // Validate BBCode syntax
                    if (ForumSettings::get('p_sig_bbcode') == 1) {
                        $errors = [];

                        $form['signature'] = Parser::preparseBbcode($form['signature'], $errors, true);

                        if (count($errors) > 0) {
                            throw new Error('<ul><li>'.implode('</li><li>', $errors).'</li></ul>');
                        }
                    }
                }

                break;
            }

            case 'display':
            {
                $prefs = [
                    'disp.topics'         => Input::post('form_disp_topics'),
                    'disp.posts'          => Input::post('form_disp_posts'),
                    'show.smilies'        => Input::post('form_show_smilies') ? 1 : 0,
                    'show.img'            => Input::post('form_show_img') ? 1 : 0,
                    'show.img.sig'        => Input::post('form_show_img_sig') ? 1 : 0,
                    'show.avatars'        => Input::post('form_show_avatars') ? 1 : 0,
                    'show.sig'            => Input::post('form_show_sig') ? 1 : 0,
                ];

                if ($prefs['disp.topics'] != '') {
                    $prefs['disp.topics'] = intval($prefs['disp.topics']);
                    if ($prefs['disp.topics'] < 3) {
                        $prefs['disp.topics'] = 3;
                    } elseif ($prefs['disp.topics'] > 75) {
                        $prefs['disp.topics'] = 75;
                    }
                } else {
                    unset($prefs['disp.topics']);
                }

                if ($prefs['disp.posts'] != '') {
                    $prefs['disp.posts'] = intval($prefs['disp.posts']);
                    if ($prefs['disp.posts'] < 3) {
                        $prefs['disp.posts'] = 3;
                    } elseif ($prefs['disp.posts'] > 75) {
                        $prefs['disp.posts'] = 75;
                    }
                } else {
                    unset($prefs['disp.posts']);
                }

                // Make sure we got a valid style string
                if (Input::post('form_style')) {
                    $styles = \FeatherBB\Core\Lister::getStyles();
                    $prefs['style'] = Utils::trim(Input::post('form_style'));
                    if (!in_array($prefs['style'], $styles)) {
                        $prefs['style'] = ForumSettings::get('style');
                    }
                }

                break;
            }

            case 'privacy':
            {
                $prefs = [
                    'email.setting'            => intval(Input::post('form_email_setting')),
                    'notify_with_post'        => Input::post('form_notify_with_post') ? 1 : 0,
                    'auto_notify'            => Input::post('form_auto_notify') ? 1 : 0,
                ];

                if ($prefs['email.setting'] < 0 || $prefs['email.setting'] > 2) {
                    $prefs['email.setting'] = ForumSettings::get('email.setting');
                }

                break;
            }

            default:
                throw new Error(__('Bad request'), 404);
        }

        $form = Hooks::fire('model.profile.update_profile_form', $form, $section, $id, $info);

        // Single quotes around non-empty values and nothing for empty values
        $temp = [];
        foreach ($form as $key => $input) {
            $temp[$key] = $input;
        }

        if (empty($temp) && empty($prefs)) {
            throw new Error(__('Bad request'), 404);
        }

        // Update general user infos
        $updateUser = DB::table('users')
            ->where('id', $id)
            ->findOne()
            ->set($temp);
        $updateUser = Hooks::fireDB('model.profile.update_profile_query', $updateUser);
        $updateUser = $updateUser->save();

        // Update user prefs
        if (!empty($prefs)) {
            Prefs::setUser($id, $prefs);
        }

        // If we changed the username we have to update some stuff
        if ($usernameUpdated) {
            $bansUpdated = DB::table('bans')
                ->where('username', $info['old_username']);
            $bansUpdated = Hooks::fireDB('model.profile.update_profile_bans_updated', $bansUpdated);
            $bansUpdated = $bansUpdated->updateMany('username', $form['username']);

            $updatePosterId = DB::table('posts')
                ->where('poster_id', $id);
            $updatePosterId = Hooks::fireDB('model.profile.update_profile_poster_id', $updatePosterId);
            $updatePosterId = $updatePosterId->updateMany('poster', $form['username']);

            $updatePosts = DB::table('posts')
                ->where('edited_by', $info['old_username']);
            $updatePosts = Hooks::fireDB('model.profile.update_profile_posts', $updatePosts);
            $updatePosts = $updatePosts->updateMany('edited_by', $form['username']);

            $updateTopicsPoster = DB::table('topics')
                ->where('poster', $info['old_username']);
            $updateTopicsPoster = Hooks::fireDB('model.profile.update_profile_topics_poster', $updateTopicsPoster);
            $updateTopicsPoster = $updateTopicsPoster->updateMany('poster', $form['username']);

            $updateTopicsLastPoster = DB::table('topics')
                ->where('last_poster', $info['old_username']);
            $updateTopicsLastPoster = Hooks::fireDB('model.profile.update_profile_topics_last_poster', $updateTopicsLastPoster);
            $updateTopicsLastPoster = $updateTopicsLastPoster->updateMany('last_poster', $form['username']);

            $updateForums = DB::table('forums')
                ->where('last_poster', $info['old_username']);
            $updateForums = Hooks::fireDB('model.profile.update_profile_forums', $updateForums);
            $updateForums = $updateForums->updateMany('last_poster', $form['username']);

            $updateOnline = DB::table('online')
                ->where('ident', $info['old_username']);
            $updateOnline = Hooks::fireDB('model.profile.update_profile_online', $updateOnline);
            $updateOnline = $updateOnline->updateMany('ident', $form['username']);

            // If the user is a moderator or an administrator we have to update the moderator lists
            $groupId = DB::table('users')
                ->where('id', $id);
            $groupId = Hooks::fireDB('model.profile.update_profile_group_id', $groupId);
            $groupId = $groupId->findOneCol('group_id');

            if ($groupId == ForumEnv::get('FEATHER_ADMIN') || Perms::getGroupPermissions($groupId, 'mod.is_mod')) {

                // Loop through all forums
                $result = $this->loopModForums();

                foreach ($result as $curForum) {
                    $curModerators = ($curForum['moderators'] != '') ? unserialize($curForum['moderators']) : [];

                    if (in_array($id, $curModerators)) {
                        unset($curModerators[$info['old_username']]);
                        $curModerators[$form['username']] = $id;
                        uksort($curModerators, [$this, 'utf8_strcasecmp']);

                        $updateMods = DB::table('forums')
                            ->where('id', $curForum['id'])
                            ->findOne()
                            ->set('moderators', serialize($curModerators));
                        $updateMods = Hooks::fireDB('model.profile.update_profile_mods', $updateMods);
                        $updateMods = $updateMods->save();
                    }
                }
            }

            // Regenerate the users info cache
            if (!CacheInterface::isCached('users_info')) {
                CacheInterface::store('users_info', Cache::getUsersInfo());
            }

            $stats = CacheInterface::retrieve('users_info');

            // Check if the bans table was updated and regenerate the bans cache when needed
            if ($bansUpdated) {
                CacheInterface::store('bans', Cache::getBans());
            }
        }

        $section = Hooks::fireDB('model.profile.update_profile', $section, $id);

        return Router::redirect(Router::pathFor('profileSection', ['id' => $id, 'section' => $section]), __('Profile redirect'));
    }

    public function getUserInfo($id)
    {
        $user['select'] = ['u.id', 'u.group_id', 'u.username', 'u.email', 'u.title', 'u.realname', 'u.url', 'u.location', 'u.signature', 'u.num_posts', 'u.last_post', 'u.registered', 'u.registration_ip', 'u.admin_note', 'u.last_visit', 'g.g_id', 'g.g_user_title'];

        $user = DB::table('users')
            ->tableAlias('u')
            ->selectMany($user['select'])
            ->leftOuterJoin('groups', ['g.g_id', '=', 'u.group_id'], 'g')
            ->where('u.id', $id);
        $user = Hooks::fireDB('model.profile.get_user_info', $user);
        $user = $user->findOne();

        if (!$user) {
            throw new Error(__('Bad request'), 404);
        }

        $user['prefs'] = ($id == User::get()->id) ? User::get()->prefs : Prefs::loadPrefs($user);

        return $user;
    }

    public function parseUserInfo($user)
    {
        $userInfo = [];

        $userInfo = Hooks::fire('model.profile.parse_user_info_start', $userInfo, $user);

        $userInfo['personal'][] = '<dt>'.__('Username').'</dt>';
        $userInfo['personal'][] = '<dd>'.Utils::escape($user['username']).'</dd>';

        $userTitleField = Utils::getTitle($user);
        $userInfo['personal'][] = '<dt>'.__('Title').'</dt>';
        $userInfo['personal'][] = '<dd>'.((ForumSettings::get('o_censoring') == 1) ? Utils::censor($userTitleField) : $userTitleField).'</dd>';

        if ($user['realname'] != '') {
            $userInfo['personal'][] = '<dt>'.__('Realname').'</dt>';
            $userInfo['personal'][] = '<dd>'.Utils::escape((ForumSettings::get('o_censoring') == 1) ? Utils::censor($user['realname']) : $user['realname']).'</dd>';
        }

        if ($user['location'] != '') {
            $userInfo['personal'][] = '<dt>'.__('Location').'</dt>';
            $userInfo['personal'][] = '<dd>'.Utils::escape((ForumSettings::get('o_censoring') == 1) ? Utils::censor($user['location']) : $user['location']).'</dd>';
        }

        if ($user['url'] != '') {
            $user['url'] = Utils::escape((ForumSettings::get('o_censoring') == 1) ? Utils::censor($user['url']) : $user['url']);
            $userInfo['personal'][] = '<dt>'.__('Website').'</dt>';
            $userInfo['personal'][] = '<dd><span class="website"><a href="'.$user['url'].'" rel="nofollow">'.$user['url'].'</a></span></dd>';
        }

        if ($user['prefs']['email.setting'] == 0 && !User::get()->is_guest && User::can('email.send')) {
            $user['email_field'] = '<a href="mailto:'.Utils::escape($user['email']).'">'.Utils::escape($user['email']).'</a>';
        } elseif ($user['prefs']['email.setting'] == 1 && !User::get()->is_guest && User::can('email.send')) {
            $user['email_field'] = '<a href="'.Router::pathFor('email', ['id' => $user['id']]).'">'.__('Send email').'</a>';
        } else {
            $user['email_field'] = '';
        }
        if ($user['email_field'] != '') {
            $userInfo['personal'][] = '<dt>'.__('Email').'</dt>';
            $userInfo['personal'][] = '<dd><span class="email">'.$user['email_field'].'</span></dd>';
        }

        if (ForumSettings::get('o_avatars') == 1) {
            $avatarField = Utils::generateAvatarMarkup($user['id']);
            if ($avatarField != '') {
                $userInfo['personality'][] = '<dt>'.__('Avatar').'</dt>';
                $userInfo['personality'][] = '<dd>'.$avatarField.'</dd>';
            }
        }

        if (ForumSettings::get('o_signatures') == 1) {
            if (isset($parsedSignature)) {
                $userInfo['personality'][] = '<dt>'.__('Signature').'</dt>';
                $userInfo['personality'][] = '<dd><div class="postsignature postmsg">'.$parsedSignature.'</div></dd>';
            }
        }

        $postsField = '';
        if (ForumSettings::get('o_show_post_count') == 1 || User::isAdminMod()) {
            $postsField = Utils::forumNumberFormat($user['num_posts']);
        }
        if (User::can('search.topics')) {
            $quickSearches = [];
            if ($user['num_posts'] > 0) {
                $quickSearches[] = '<a href="'.Router::pathFor('search').'?action=show_user_topics&amp;user_id='.$user['id'].'">'.__('Show topics').'</a>';
                $quickSearches[] = '<a href="'.Router::pathFor('search').'?action=show_user_posts&amp;user_id='.$user['id'].'">'.__('Show posts').'</a>';
            }
            if (User::isAdminMod() && ForumSettings::get('o_topic_subscriptions') == 1) {
                $quickSearches[] = '<a href="'.Router::pathFor('search').'?action=show_subscriptions&amp;user_id='.$user['id'].'">'.__('Show subscriptions').'</a>';
            }

            if (!empty($quickSearches)) {
                $postsField .= (($postsField != '') ? ' - ' : '').implode(' - ', $quickSearches);
            }
        }
        if ($postsField != '') {
            $userInfo['activity'][] = '<dt>'.__('Posts').'</dt>';
            $userInfo['activity'][] = '<dd>'.$postsField.'</dd>';
        }

        if ($user['num_posts'] > 0) {
            $userInfo['activity'][] = '<dt>'.__('Last post').'</dt>';
            $userInfo['activity'][] = '<dd>'.Utils::formatTime($user['last_post']).'</dd>';
        }

        $userInfo['activity'][] = '<dt>'.__('Registered').'</dt>';
        $userInfo['activity'][] = '<dd>'.Utils::formatTime($user['registered'], true).'</dd>';

        $userInfo = Hooks::fire('model.profile.parse_user_info', $userInfo);

        return $userInfo;
    }

    public function editEssentials($id, $user)
    {
        $userDisp = [];

        $userDisp = Hooks::fire('model.profile.edit_essentials_start', $userDisp, $id, $user);

        if (User::isAdminMod()) {
            if (User::isAdmin() || User::can('mod.rename_users')) {
                $userDisp['username_field'] = '<label class="required"><strong>'.__('Username').' <span>'.__('Required').'</span></strong><br /><input type="text" name="req_username" value="'.Utils::escape($user['username']).'" size="25" maxlength="25" required /><br /></label>'."\n";
            } else {
                $userDisp['username_field'] = '<p>'.sprintf(__('Username info'), Utils::escape($user['username'])).'</p>'."\n";
            }

            $userDisp['email_field'] = '<label class="required"><strong>'.__('Email').' <span>'.__('Required').'</span></strong><br /><input type="text" name="req_email" value="'.Utils::escape($user['email']).'" size="40" maxlength="80" required /><br /></label><p><span class="email"><a href="'.Router::pathFor('email', ['id' => $id]).'">'.__('Send email').'</a></span></p>'."\n";
        } else {
            $userDisp['username_field'] = '<p>'.__('Username').': '.Utils::escape($user['username']).'</p>'."\n";

            if (ForumSettings::get('o_regs_verify') == 1) {
                $userDisp['email_field'] = '<p>'.sprintf(__('Email info'), Utils::escape($user['email']).' - <a href="'.Router::pathFor('profileAction', ['id' => $id, 'action' => 'change_email']).'">'.__('Change email').'</a>').'</p>'."\n";
            } else {
                $userDisp['email_field'] = '<label class="required"><strong>'.__('Email').' <span>'.__('Required').'</span></strong><br /><input type="text" name="req_email" value="'.$user['email'].'" size="40" maxlength="80" required /><br /></label>'."\n";
            }
        }

        $userDisp['posts_field'] = '';
        $postsActions = [];

        if (User::isAdmin()) {
            $userDisp['posts_field'] .= '<label>'.__('Posts').'<br /><input type="text" name="num_posts" value="'.$user['num_posts'].'" size="8" maxlength="8" /><br /></label>';
        } elseif (ForumSettings::get('o_show_post_count') == 1 || User::isAdminMod()) {
            $postsActions[] = sprintf(__('Posts info'), Utils::forumNumberFormat($user['num_posts']));
        }

        if (User::can('search.topics') || User::isAdmin()) {
            $postsActions[] = '<a href="'.Router::pathFor('search').'?action=show_user_topics&amp;user_id='.$id.'">'.__('Show topics').'</a>';
            $postsActions[] = '<a href="'.Router::pathFor('search').'?action=show_user_posts&amp;user_id='.$id.'">'.__('Show posts').'</a>';

            if (ForumSettings::get('o_topic_subscriptions') == 1) {
                $postsActions[] = '<a href="'.Router::pathFor('search').'?action=show_subscriptions&amp;user_id='.$id.'">'.__('Show subscriptions').'</a>';
            }
        }

        $userDisp['posts_field'] .= (!empty($postsActions) ? '<p class="actions">'.implode(' - ', $postsActions).'</p>' : '')."\n";

        $userDisp = Hooks::fire('model.profile.edit_essentials', $userDisp);

        return $userDisp;
    }

    public function groupList($user)
    {
        $output = '';

        $user = Hooks::fire('model.profile.get_group_list_start', $user);

        $result['select'] = ['g_id', 'g_title'];

        $result = DB::table('groups')
            ->selectMany($result['select'])
            ->whereNotEqual('g_id', ForumEnv::get('FEATHER_GUEST'))
            ->orderBy('g_title');
        $result = Hooks::fireDB('model.profile.get_group_list_query', $result);
        $result = $result->findMany();

        foreach ($result as $curGroup) {
            if ($curGroup['g_id'] == $user['g_id'] || ($curGroup['g_id'] == ForumSettings::get('o_default_user_group') && $user['g_id'] == '')) {
                $output .= "\t\t\t\t\t\t\t\t".'<option value="'.$curGroup['g_id'].'" selected="selected">'.Utils::escape($curGroup['g_title']).'</option>'."\n";
            } else {
                $output .= "\t\t\t\t\t\t\t\t".'<option value="'.$curGroup['g_id'].'">'.Utils::escape($curGroup['g_title']).'</option>'."\n";
            }
        }

        $output = Hooks::fire('model.profile.get_group_list', $output);

        return $output;
    }

    public function forumList($id)
    {
        $output = '';

        $id = Hooks::fire('model.profile.get_forum_list_start', $id);

        $result['select'] = ['cid' => 'c.id', 'c.cat_name', 'fid' => 'f.id', 'f.forum_name', 'f.moderators'];
        $result['order_by'] = ['c.disp_position', 'c.id', 'f.disp_position'];

        $result = DB::table('categories')
            ->tableAlias('c')
            ->selectMany($result['select'])
            ->innerJoin('forums', ['c.id', '=', 'f.cat_id'], 'f')
            ->whereNull('f.redirect_url')
            ->orderByMany($result['order_by']);
        $result = Hooks::fireDB('model.profile.get_forum_list', $result);
        $result = $result->findMany();

        $curCategory = 0;
        foreach ($result as $curForum) {
            if ($curForum['cid'] != $curCategory) {
                // A new category since last iteration?
                if ($curCategory) {
                    $output .= "\n\t\t\t\t\t\t\t\t".'</div>';
                }

                if ($curCategory != 0) {
                    $output .= "\n\t\t\t\t\t\t\t".'</div>'."\n";
                }

                $output .= "\t\t\t\t\t\t\t".'<div class="conl">'."\n\t\t\t\t\t\t\t\t".'<p><strong>'.Utils::escape($curForum['cat_name']).'</strong></p>'."\n\t\t\t\t\t\t\t\t".'<div class="rbox">';
                $curCategory = $curForum['cid'];
            }

            $moderators = ($curForum['moderators'] != '') ? unserialize($curForum['moderators']) : [];

            $output .= "\n\t\t\t\t\t\t\t\t\t".'<label><input type="checkbox" name="moderator_in['.$curForum['fid'].']" value="1"'.((in_array($id, $moderators)) ? ' checked="checked"' : '').' />'.Utils::escape($curForum['forum_name']).'<br /></label>'."\n";
        }

        $output = Hooks::fire('model.profile.get_forum_list', $output);

        return $output;
    }

    private static function utf8_strcasecmp($strX, $strY)
    {
        $strX = \utf8\to_lower($strX);
        $strY = \utf8\to_lower($strY);

        return strcmp($strX, $strY);
    }

    //
    // Check username
    //
    public function checkUsername($username, $errors, $excludeId = null)
    {
        Lang::load('register');
        Lang::load('prof_reg');

        // Convert multiple whitespace characters into one (to prevent people from registering with indistinguishable usernames)
        $username = preg_replace('%\s+%s', ' ', $username);

        // Validate username
        if (Utils::strlen($username) < 2) {
            $errors[] = __('Username too short');
        } elseif (Utils::strlen($username) > 25) { // This usually doesn't happen since the form element only accepts 25 characters
            $errors[] = __('Username too long');
        } elseif (!strcasecmp($username, 'Guest') || !self::utf8_strcasecmp($username, __('Guest'))) {
            $errors[] = __('Username guest');
        } elseif (filter_var($username, FILTER_VALIDATE_IP)) {
            $errors[] = __('Username IP');
        } elseif ((strpos($username, '[') !== false || strpos($username, ']') !== false) && strpos($username, '\'') !== false && strpos($username, '"') !== false) {
            $errors[] = __('Username reserved chars');
        } elseif (preg_match('%(?:\[/?(?:b|u|s|ins|del|em|i|h|colou?r|quote|code|img|url|email|list|\*|topic|post|forum|user)\]|\[(?:img|url|quote|list)=)%i', $username)) {
            $errors[] = __('Username BBCode');
        }

        // Check username for any censored words
        if (ForumSettings::get('o_censoring') == 1 && Utils::censor($username) != $username) {
            $errors[] = __('Username censor');
        }

        // Check that the username (or a too similar username) is not already registered
        $query = (!is_null($excludeId)) ? ' AND id!='.$excludeId : '';

        $result = DB::table('online')->rawQuery('SELECT username FROM '.ForumSettings::get('db_prefix').'users WHERE (UPPER(username)=UPPER(:username1) OR UPPER(username)=UPPER(:username2)) AND id>1'.$query, [':username1' => $username, ':username2' => Utils::ucpPregReplace('%[^\p{L}\p{N}]%u', '', $username)])->findOne();

        if ($result) {
            $busy = $result['username'];
            $errors[] = __('Username dupe 1').' '.Utils::escape($busy).'. '.__('Username dupe 2');
        }

        // Check username for any banned usernames
        foreach (Container::get('bans') as $curBan) {
            if ($curBan['username'] != '' && \utf8\to_lower($username) == \utf8\to_lower($curBan['username'])) {
                $errors[] = __('Banned username');
                break;
            }
        }

        return $errors;
    }

    public function getInfoMail($recipientId)
    {
        $recipientId = Hooks::fire('model.profile.get_info_mail_start', $recipientId);

        $mail = DB::table('users')
                ->select('username', 'recipient')
                ->select('email', 'recipient_email')
                ->select('id')
                ->select('group_id')
                ->where('id', $recipientId);
        $mail = Hooks::fireDB('model.profile.get_info_mail_query', $mail);
        $mail = $mail->findOne();

        if (!$mail) {
            throw new Error(__('Bad request'), 404);
        }

        $mail['recipient_id'] = $mail['id'];
        $mail['email_setting'] = User::getPref('email.setting', $mail);

        $mail = Hooks::fireDB('model.profile.get_info_mail', $mail);

        return $mail;
    }

    public function sendEmail($mail)
    {
        $mail = Hooks::fire('model.profile.send_email_start', $mail);

        // Clean up message and subject from POST
        $subject = Utils::trim(Input::post('req_subject'));
        $message = Utils::trim(Input::post('req_message'));

        if ($subject == '') {
            throw new Error(__('No email subject'), 400);
        } elseif ($message == '') {
            throw new Error(__('No email message'), 400);
        }
        // Here we use strlen() not Utils::strlen() as we want to limit the post to FEATHER_MAX_POSTSIZE bytes, not characters
        elseif (strlen($message) > ForumEnv::get('FEATHER_MAX_POSTSIZE')) {
            throw new Error(__('Too long email message'), 400);
        }

        if (User::get()->last_email_sent != '' && (time() - User::get()->last_email_sent) < User::getPref('email.min_interval') && (time() - User::get()->last_email_sent) >= 0) {
            throw new Error(sprintf(__('Email flood'), User::getPref('email.min_interval'), User::getPref('email.min_interval') - (time() - User::get()->last_email_sent)), 429);
        }

        // Load the "form email" template
        $mailTpl = trim(file_get_contents(ForumEnv::get('FEATHER_ROOT').'featherbb/lang/'.User::getPref('language', $mail['recipient_id']).'/mail_templates/form_email.tpl'));
        $mailTpl = Hooks::fire('model.profile.send_email_mail_tpl', $mailTpl);

        // The first row contains the subject
        $firstCrlf = strpos($mailTpl, "\n");
        $mailSubject = Utils::trim(substr($mailTpl, 8, $firstCrlf-8));
        $mailMessage = Utils::trim(substr($mailTpl, $firstCrlf));

        $mailSubject = str_replace('<mail_subject>', $subject, $mailSubject);
        $mailMessage = str_replace('<sender>', User::get()->username, $mailMessage);
        $mailMessage = str_replace('<board_title>', ForumSettings::get('o_board_title'), $mailMessage);
        $mailMessage = str_replace('<mail_message>', $message, $mailMessage);
        $mailMessage = str_replace('<board_mailer>', ForumSettings::get('o_board_title'), $mailMessage);

        $mailMessage = Hooks::fire('model.profile.send_email_mail_message', $mailMessage);

        Email::send($mail['recipient_email'], $mailSubject, $mailMessage, User::get()->email, User::get()->username);

        $updateLastMailSent = DB::table('users')->where('id', User::get()->id)
                                                  ->findOne()
                                                  ->set('last_email_sent', time());
        $updateLastMailSent = Hooks::fireDB('model.profile.send_email_update_last_mail_sent', $updateLastMailSent);
        $updateLastMailSent = $updateLastMailSent->save();

        return Router::redirect(Router::pathFor('userProfile', ['id' => $mail['recipient_id']]), __('Email sent redirect'));
    }

    public function displayIpInfo($ip)
    {
        $ip = Hooks::fire('model.profile.display_ip_info', $ip);
        throw new Error(sprintf(__('Host info 1'), $ip).'<br />'.sprintf(__('Host info 2'), @gethostbyaddr($ip)).'<br /><br /><a href="'.Router::pathFor('usersIpShow', ['ip' => $ip]).'">'.__('Show more users').'</a>', 400, true, true);
    }
}
