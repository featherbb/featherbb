<?php

/**
* Copyright (C) 2015-2017 FeatherBB
* based on code by (C) 2008-2015 FluxBB
* and Rickard Andersson (C) 2002-2008 PunBB
* License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
*/

namespace FeatherBB\Model;

use FeatherBB\Core\Database as DB;
use FeatherBB\Core\Interfaces\Container;
use FeatherBB\Core\Utils;

class Debug
{
    public static function getQueries()
    {
        if (empty(DB::getQueryLog())) {
            return null;
        }
        $data = [];
        $data['raw'] = array_combine(DB::getQueryLog()[0], DB::getQueryLog()[1]);
        $data['total_time'] = array_sum(array_keys($data['raw']));
        return $data;
    }

    public static function getInfo()
    {
        $data = ['exec_time' => (Utils::getMicrotime() - Container::get('start'))];
        $data['nb_queries'] = (isset(DB::getQueryLog()[0])) ? count(DB::getQueryLog()[0]) : 'N/A';
        $data['mem_usage'] = (function_exists('memory_get_usage')) ? Utils::fileSize(memory_get_usage()) : 'N/A';
        $data['mem_peak_usage'] = (function_exists('memory_get_peak_usage')) ? Utils::fileSize(memory_get_peak_usage()) : 'N/A';
        return $data;
    }
}
