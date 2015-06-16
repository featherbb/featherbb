<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */
 

// Load Slim Framework
require 'Slim/Slim.php';
\Slim\Slim::registerAutoloader();

// Load FeatherBB
define('PUN_ROOT', dirname(__FILE__).'/');
require PUN_ROOT.'include/common.php';

// Instantiate Slim and specify where to load the views 
$feather = new \Slim\Slim([
    'templates.path' => get_path_view(),
	'debug' => true, // As long as we're developing FeatherBB
]);

// Load the routes
require PUN_ROOT.'route/users.php';

// Run it, baby!
$feather->run();