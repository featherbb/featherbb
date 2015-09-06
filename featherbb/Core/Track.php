<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace FeatherBB\Core;

class Track
{
    //
    // Save array of tracked topics in cookie
    //
    public static function set_tracked_topics($tracked_topics = null)
    {
        global $cookie_name;

        // Get Slim current session
        $feather = \Slim\Slim::getInstance();

        if (!empty($tracked_topics)) {
            // Sort the arrays (latest read first)
            arsort($tracked_topics['topics'], SORT_NUMERIC);
            arsort($tracked_topics['forums'], SORT_NUMERIC);
        } else {
            $tracked_topics = array('topics' => array(), 'forums' => array());
        }

        return $feather->setCookie($cookie_name . '_track', json_encode($tracked_topics), time() + $feather->config['o_timeout_visit']);
    }


    //
    // Extract array of tracked topics from cookie
    //
    public static function get_tracked_topics()
    {
        global $cookie_name;

        // Get Slim current session
        $feather = \Slim\Slim::getInstance();

        $cookie_raw = $feather->getCookie($cookie_name.'_track');

        if (isset($cookie_raw)) {
            $cookie_data = json_decode($cookie_raw, true);
            return $cookie_data;
        }
        return array('topics' => array(), 'forums' => array());
    }
}