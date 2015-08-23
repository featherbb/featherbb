<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace model;

use DB;

class header
{
    public function __construct()
    {
        $this->feather = \Slim\Slim::getInstance();
        $this->start = $this->feather->start;
        $this->config = $this->feather->config;
        $this->user = $this->feather->user;
        $this->request = $this->feather->request;
        $this->hook = $this->feather->hooks;
    }

    public function get_reports()
    {
        $this->hook->fire('get_reports_start');

        $result_header = DB::for_table('reports')->where_null('zapped');

        $result_header = $this->hook->fireDB('get_reports_query', $result_header);

        $result_header = $result_header->find_one();

        if ($result_header) {
            return true;
        }

        return false;
    }
}