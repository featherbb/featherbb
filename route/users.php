<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */
 

// Index
$feather->get('/', '\controller\Index:display');

// Viewforum
$feather->get('/forum/:id/:name/page/:page(/)', '\controller\Viewforum:display')->conditions(array('id' => '[0-9]+', 'page' => '[0-9]+'));
$feather->get('/forum/:id/:name(/)', '\controller\Viewforum:display')->conditions(array('id' => '[0-9]+'));
$feather->get('/forum/:id(/)', '\controller\Viewforum:display')->conditions(array('id' => '[0-9]+'));

// Viewtopic
$feather->get('/topic/:id/:name/page/:page(/)', '\controller\Viewtopic:display')->conditions(array('id' => '[0-9]+', 'page' => '[0-9]+'));
$feather->get('/topic/:id/:name(/)', '\controller\Viewtopic:display')->conditions(array('id' => '[0-9]+'));
$feather->get('/topic/:id(/)', '\controller\Viewtopic:display')->conditions(array('id' => '[0-9]+'));

//$feather->get('/topic/:id/:name/action/:action(/)', '\controller\Viewtopic:action');
$feather->get('/topic/:id/action/:action(/)', '\controller\Viewtopic:action')->conditions(array('id' => '[0-9]+'));

$feather->get('/post/:pid(/)', '\controller\Viewtopic:viewpost')->conditions(array('pid' => '[0-9]+'));

// Userlist
$feather->get('/userlist(/)', '\controller\Userlist:display');
$feather->get('/userlist/username/:username/group/:group/sort/:sort/dir/:dir(/)', '\controller\Userlist:display')->conditions(array('group' => '[0-9]+'));
$feather->get('/userlist/username/:username/group/:group/sort/:sort/dir/:dir/page/:page(/)', '\controller\Userlist:display')->conditions(array('group' => '[0-9]+'));