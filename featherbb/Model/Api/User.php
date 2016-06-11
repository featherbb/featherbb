<?php

/**
 * Copyright (C) 2015-2016 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace FeatherBB\Model\Api;

use FeatherBB\Core\Error;

class User extends Api
{
    public function display($id)
    {
        $user = new \FeatherBB\Model\Profile();

        // Remove sensitive fields for regular users
        if (!$this->isAdMod) {
            Container::get('hooks')->bind('model.profile.get_user_info', function ($user) {
                $user = $user->select_delete_many(array('u.email', 'u.registration_ip', 'u.auto_notify', 'u.admin_note', 'u.last_visit'));
                return $user;
            });
        }

        try {
            $data = $user->get_user_info($id);
        } catch (Error $e) {
            return $this->errorMessage;
        }

        $data = $data->as_array();

        if (!$this->isAdMod) {
            unset($data['prefs']);
        }


        return $data;
    }
}
