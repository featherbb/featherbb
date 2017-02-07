<?php

namespace FeatherBB;

use FeatherBB\Core\Interfaces\SlimStatic;

/**
 * Copyright (C) 2015-2017 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

// Start a session for flash messages
session_cache_limiter(false);
session_start();
error_reporting(E_ALL); // Let's report everything for development
ini_set('display_errors', 1);

// Load Slim Framework
require 'vendor/autoload.php';

// Instantiate Slim and add CSRF
$feather = new \Slim\App();
SlimStatic::boot($feather);
// Allow static proxies to be called from anywhere in App
Statical::addNamespace('*', __NAMESPACE__.'\\*');

$feather_settings = [
    // 'config_file' => 'featherbb/config.php',
    // 'cache_dir' => 'cache/',
    'debug' => 'all'  // 3 levels : false, info (only execution time and number of queries), and all (display info + queries)
];

Feather::add(new \FeatherBB\Middleware\Csrf);
Feather::add(new \FeatherBB\Middleware\Auth);
Feather::add(new \FeatherBB\Middleware\Core($feather_settings));
// Permanently redirect paths with a trailing slash
// to their non-trailing counterpart
Feather::add(function ($req, $res, $next) {
    $uri = $req->getUri();
    $path = $uri->getPath();
    if ($path != '/' && substr($path, -1) == '/') {
        $uri = $uri->withPath(substr($path, 0, -1));
        return $res->withRedirect((string)$uri, 301);
    }

    return $next($req, $res);
});

// Load the routes
require 'featherbb/routes.php';

// Run it, baby!
Feather::run();
