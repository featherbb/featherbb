<?php

/**
* Copyright (C) 2015-2016 FeatherBB
* based on code by (C) 2008-2015 FluxBB
* and Rickard Andersson (C) 2002-2008 PunBB
* License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
*/

namespace FeatherBB\Model;

use FeatherBB\Core\Database as DB;
use FeatherBB\Core\Utils;

class Debug
{
    protected static $feather;

    public static function get_queries()
    {
        if (empty(DB::get_query_log())) {
            return null;
        }
        $data = array();
        $data['raw'] = array_combine(DB::get_query_log()[0], DB::get_query_log()[1]);
        $data['total_time'] = array_sum(array_keys($data['raw']));
        return $data;
    }

    public static function get_info()
    {
        self::$feather = \Slim\Slim::getInstance();

        $data = array('exec_time' => (Utils::get_microtime() - self::$feather->start));
        $data['nb_queries'] = (isset(DB::get_query_log()[0])) ? count(DB::get_query_log()[0]) : 'N/A';
        $data['mem_usage'] = (function_exists('memory_get_usage')) ? Utils::file_size(memory_get_usage()) : 'N/A';
        $data['mem_peak_usage'] = (function_exists('memory_get_peak_usage')) ? Utils::file_size(memory_get_peak_usage()) : 'N/A';
        return $data;
    }
}
