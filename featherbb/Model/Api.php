<?php

/**
 * Copyright (C) 2015-2016 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace FeatherBB\Model;

use FeatherBB\Core\Error;
use FeatherBB\Core\Database as DB;
use FeatherBB\Core\Interfaces\User;
use FeatherBB\Core\Random;
use FeatherBB\Core\Utils;

class Api
{
    private $errorMessage = array("error" => "Not Found");

    private $connected = false;

    private $isAdMod = false;

    private $tmpUser;

    private $user;

    /**
     * Api constructor.
     * Perform the authentication using given token, id, username or nothing at all.
     * If an username or ID is given, compare to the provided token.
     * Otherwise, use current session based on cookies.
     */
    public function __construct()
    {
        // Get the user ID from its name
        if (Input::query('username')) {
            $user_id = DB::for_table('users')
                ->where_like('username', Input::query('username'))
                ->find_one_col('id');
            if ($user_id) {
                $this->tmpUser = User::get($user_id);
            }
        }
        // Load the user object from its ID directly
        elseif (Input::query('id')) {
            $this->tmpUser = User::get(Input::query('id'));
        }

        // Validate authentication using the token...
        if (Input::query('token') &&                                                         // We have a token
            (Input::query('username') || Input::query('id')) &&                              // User's ID or username are provided
            is_object($this->tmpUser) &&                                                     // The user loaded above exists
            Utils::hash_equals(self::getToken($this->tmpUser), Input::query('token'))) {     // Provided token is correct
            $this->connected = true;
            $this->user = $this->tmpUser;
        }
        // ... or use current session if neither username nor id have been provided, or the token is invalid
        else {
            $this->user = User::get();
        }

        // If he is admin or moderator
        if ($this->user->g_id == ForumEnv::get('FEATHER_ADMIN') || $this->user->g_moderator == '1') {
            $this->isAdMod = true;
        }
    }

    /**
     * Get token for the given user
     * @param $user User instance to give the token of
     * @return string Token wanted
     */
    public static function getToken($user)
    {
        return Random::hash($user->password.$user->registered.$user->username);
    }

    public function user($id)
    {
        $user = new \FeatherBB\Model\Profile();

        // Remove sensitive fields for regular users
        if (!$this->isAdMod) {
            Container::get('hooks')->bind('model.profile.get_user_info', function ($user) {
                $user = $user->select_delete_many(array('u.email', 'u.jabber', 'u.icq', 'u.msn', 'u.aim', 'u.yahoo', 'u.registration_ip', 'u.disp_topics', 'u.disp_posts', 'u.email_setting', 'u.notify_with_post', 'u.auto_notify', 'u.show_smilies', 'u.show_img', 'u.show_img_sig', 'u.show_avatars', 'u.show_sig', 'u.timezone', 'u.dst', 'u.language', 'u.style', 'u.admin_note', 'u.date_format', 'u.time_format', 'u.last_visit'));
                return $user;
            });
        }

        try {
            $data = $user->get_user_info($id);
        } catch (Error $e) {
            return $this->errorMessage;
        }

        $data = $data->as_array();

        return $data;
    }

    public function forum($id)
    {
        $forum = new \FeatherBB\Model\Forum();

        try {
            $data = $forum->get_forum_info($id);
        } catch (Error $e) {
            return $this->errorMessage;
        }

        $data = $data->as_array();

        return $data;
    }

    public function topic($id)
    {
        $topic = new \FeatherBB\Model\Topic();

        try {
            $data = $topic->get_info_topic($id);
        } catch (Error $e) {
            return $this->errorMessage;
        }

        $data = $data->as_array();

        return $data;
    }

    public function post($id)
    {
        $post = new \FeatherBB\Model\Post();

        try {
            $data = $post->get_info_edit($id);
        } catch (Error $e) {
            return $this->errorMessage;
        }

        $data = $data->as_array();

        return $data;
    }
}