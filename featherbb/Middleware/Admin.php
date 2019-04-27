<?php
/**
 *
 * Copyright (C) 2015-2019 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 *
 * $app = new \Slim\Slim();
 * $app->add(new \Slim\Extras\Middleware\FeatherBBAuth());
 *
 */

namespace FeatherBB\Middleware;
use FeatherBB\Core\Interfaces\ForumEnv;
use FeatherBB\Core\Interfaces\Router;
use FeatherBB\Core\Interfaces\User;

/**
 * Middleware to check if user is logged and admin
 */
class Admin
{
    public function __invoke($request, $response, $next)
    {
        // Redirect user to home page if not admin
        if (User::get()->g_id != ForumEnv::get('FEATHER_ADMIN')) {
            return Router::redirect(Router::pathFor('home'), __('No permission'));
        }

        $response = $next($request, $response);
        return $response;
    }
}
