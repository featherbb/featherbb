<?php

/**
 * Copyright (C) 2015-2019 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace FeatherBB\Core;

use FeatherBB\Core\Interfaces\Container;
use FeatherBB\Core\Interfaces\ForumSettings;

class Track
{
    //
    // Save array of tracked topics in cookie
    //
    public static function setTrackedTopics($trackedTopics = null)
    {
        if (!empty($trackedTopics)) {
            // Sort the arrays (latest read first)
            arsort($trackedTopics['topics'], SORT_NUMERIC);
            arsort($trackedTopics['forums'], SORT_NUMERIC);
        } else {
            $trackedTopics = ['topics' => [], 'forums' => []];
        }

        return setcookie(ForumEnv::get('COOKIE_NAME') . '_track', json_encode($trackedTopics), time() + ForumSettings::get('o_timeout_visit'), '/', '', false, true);
    }


    //
    // Extract array of tracked topics from cookie
    //
    public static function getTrackedTopics()
    {
        $cookieRaw = Container::get('cookie')->get(ForumEnv::get('COOKIE_NAME').'_track');

        if (isset($cookieRaw)) {
            $cookieData = json_decode($cookieRaw, true);
            return $cookieData;
        }
        return ['topics' => [], 'forums' => []];
    }
}
