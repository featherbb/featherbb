<?php
/**
 *
 * Copyright (C) 2015-2017 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 *
 * $app = new \Slim\Slim();
 * $app->add(new \Slim\Extras\Middleware\FeatherBBAuth());
 *
 */

namespace FeatherBB\Middleware;

/**
 * Middleware to change the header to Json
 */
class JsonHeader
{
    public function __invoke($request, $response, $next)
    {
        $response = $response->withHeader('Content-type', 'application/json');

        $response = $next($request, $response);

        return $response;
    }
}
