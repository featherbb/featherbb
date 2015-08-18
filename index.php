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
error_reporting(E_ALL); // Let's report everything for development
ini_set('display_errors', 1);

// Load Slim Framework
require 'Slim/Slim.php';
\Slim\Slim::registerAutoloader();

// Instantiate Slim
$feather = new \Slim\Slim();
$feather_settings = array('config_file' => 'include/config.php',
                          'cache_dir' => 'cache/',
                          'debug' => 'all'); // 3 levels : false, info (only execution time and number of queries), and all (display info + queries)

// Load middlewares
$feather->add(new \Slim\Extras\Middleware\CsrfGuard('featherbb_csrf')); // CSRF
$feather->add(new \Slim\Extras\Middleware\FeatherBBAuth());
$feather->add(new \Slim\Extras\Middleware\FeatherBBLoader($feather_settings)); // FeatherBB
 // FeatherBB

// Load the routes
require 'include/routes.php';

// Run it, baby!
$feather->run();
