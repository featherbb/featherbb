<?php

namespace FeatherBB;

use FeatherBB\Core\Interfaces\SlimStatic;

/**
 * Copyright (C) 2015-2016 FeatherBB
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

$feather_settings = array('config_file' => 'featherbb/config.php',
    'cache_dir' => 'cache/',
    'debug' => 'all'); // 3 levels : false, info (only execution time and number of queries), and all (display info + queries)

// $container = $feather->getContainer();
// $container['csrf'] = function () {
//     return new \FeatherBB\Middleware\Csrf;
// };
// $container['auth'] = function () {
//     return new \FeatherBB\Middleware\Auth();
// };
// $container['core'] = function () use ($feather_settings) {
//     return new \FeatherBB\Middleware\Core($feather_settings);
// };

// $feather->add($container->get('csrf'));
// $feather->add($container->get('auth'));
// $feather->add($container->get('core'));


Feather::add(new \FeatherBB\Middleware\Csrf);
Feather::add(new \FeatherBB\Middleware\Auth);
Feather::add(new \FeatherBB\Middleware\Core($feather_settings));
Feather::add(new \RKA\Middleware\IpAddress);

// Load the routes
require 'featherbb/routes.php';

// Run it, baby!
Feather::run();
