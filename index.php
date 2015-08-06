<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */
 
// Start a session for flash messages
session_cache_limiter(false);
session_start();

// Load Slim Framework
require 'Slim/Slim.php';
\Slim\Slim::registerAutoloader();

// Instantiate Slim
$feather = new \Slim\Slim();

// Load middlewares
$feather->add(new \Slim\Extras\Middleware\CsrfGuard('featherbb_csrf')); // CSRF
$feather->add(new \Slim\Extras\Middleware\FeatherBB()); // FeatherBB

// Cookie encryption
$feather->config('cookies.encrypt', true);

// Load FeatherBB common file
define('FEATHER_ROOT', dirname(__FILE__).'/');
require FEATHER_ROOT.'include/common.php';

// Load the routes
require FEATHER_ROOT.'include/routes.php';

// Specify where to load the views
$feather->config('templates.path', get_path_view());

$feather->config('debug', true); // As long as we're developing FeatherBB

// Run it, baby!
$feather->run();
