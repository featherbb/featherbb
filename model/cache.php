<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace model;
use DB;

class cache
{
    public function __construct()
    {
        $this->feather = \Slim\Slim::getInstance();
    }

    public static function get_config()
    {
        $result = DB::for_table('config')
                    ->find_array();
        $config = array();
        foreach ($result as $item) {
            $config[$item['conf_name']] = $item['conf_value'];
        }
        return $config;
    }
}
