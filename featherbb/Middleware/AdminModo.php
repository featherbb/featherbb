<?php
/**
 *
 * Copyright (C) 2015-2016 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 *
 * $app = new \Slim\Slim();
 * $app->add(new \Slim\Extras\Middleware\FeatherBBAuth());
 *
 */

namespace FeatherBB\Middleware;

use FeatherBB\Core\Error;

/**
 * Middleware to check if user is logged and admin
 */
class AdminModo
{
    public function __invoke($request, $response, $next)
    {
        // Middleware to check if user is allowed to moderate, if he's not redirect to error page.
        if (!User::isAdminMod()) {
            throw new Error(__('No permission'), 403);
        }
        $response = $next($request, $response);
        return $response;
    }
}
