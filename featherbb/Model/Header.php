<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace FeatherBB\Model;

use FeatherBB\Utils;
use DB;

class Header
{
    protected static $app;

    public static function get_reports()
    {
        self::$app = \Slim\Slim::getInstance();

        self::$app->hooks->fire('get_reports_start');

        $result_header = DB::for_table('reports')->where_null('zapped');

        $result_header = self::$app->hooks->fireDB('get_reports_query', $result_header);

        return (bool) $result_header->find_one();
    }
}
