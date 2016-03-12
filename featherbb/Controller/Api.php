<?php

/**
 * Copyright (C) 2015-2016 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace FeatherBB\Controller;

class Api
{
    public function __construct()
    {
        $this->model = new \FeatherBB\Model\Api();
    }

    public function user($req, $res, $args)
    {
        return json_encode($this->model->user($args['id']), JSON_PRETTY_PRINT);
    }

    public function forum($req, $res, $args)
    {
        return json_encode($this->model->forum($args['id']), JSON_PRETTY_PRINT);
    }

    public function topic($req, $res, $args)
    {
        return json_encode($this->model->topic($args['id']), JSON_PRETTY_PRINT);
    }

    public function post($req, $res, $args)
    {
        return json_encode($this->model->post($args['id']), JSON_PRETTY_PRINT);
    }
}