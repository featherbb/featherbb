<?php

/**
 * Copyright (C) 2015-2016 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace FeatherBB\Model;

use FeatherBB\Core\Error;

class Api
{
    private $errorMessage = array("error" => "Not Found");

    public function user($id)
    {
        $user = new \FeatherBB\Model\Profile();

        // Remove sensitive fields
        Container::get('hooks')->bind('model.profile.get_user_info', function ($user) {
            $user = $user->select_delete_many(array('u.email', 'u.jabber', 'u.icq', 'u.msn', 'u.aim', 'u.yahoo', 'u.registration_ip', 'u.disp_topics', 'u.disp_posts', 'u.email_setting', 'u.notify_with_post', 'u.auto_notify', 'u.show_smilies', 'u.show_img', 'u.show_img_sig', 'u.show_avatars', 'u.show_sig', 'u.timezone', 'u.dst', 'u.language', 'u.style', 'u.admin_note', 'u.date_format', 'u.time_format', 'u.last_visit'));
            return $user;
        });

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