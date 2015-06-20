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
$feather->get('/topic/:id/action/:action(/)', '\controller\Viewtopic:action')->conditions(array('id' => '[0-9]+'));
$feather->get('/post/:pid(/)', '\controller\Viewtopic:viewpost')->conditions(array('pid' => '[0-9]+'));

// Userlist
$feather->get('/userlist(/)', '\controller\Userlist:display');

// Login
$feather->get('/login(/)', '\controller\Login:display');
$feather->post('/login/action/in(/)', '\controller\Login:logmein');
$feather->map('/login/action/forget(/)', '\controller\Login:forget')->via('GET', 'POST');
$feather->get('/logout/id/:id/token/:token(/)', '\controller\Login:logmeout')->conditions(array('id' => '[0-9]+'));

// Register
$feather->get('/register(/)', '\controller\Register:rules');
$feather->map('/register/agree(/)', '\controller\Register:display')->via('GET', 'POST');
$feather->get('/register/cancel(/)', '\controller\Register:cancel');

// Post
$feather->map('/post/new-topic/:fid(/)', '\controller\Post:newpost')->conditions(array('fid' => '[0-9]+'))->via('GET', 'POST');
$feather->map('/post/reply/:tid(/)(/quote/:qid)(/)', '\controller\Post:newreply')->conditions(array('tid' => '[0-9]+', 'qid' => '[0-9]+'))->via('GET', 'POST');

// Edit
$feather->map('/edit/:id(/)', '\controller\Edit:editpost')->conditions(array('id' => '[0-9]+'))->via('GET', 'POST');

// Delete
$feather->map('/delete/:id(/)', '\controller\Delete:deletepost')->conditions(array('id' => '[0-9]+'))->via('GET', 'POST');

// Search
$feather->get('/search(/)', '\controller\Search:display');
$feather->get('/search/show/:show(/)', '\controller\Search:quicksearches');

// Help
$feather->get('/help(/)', '\controller\Help:display');

// 404 not found
$feather->notFound(function () use ($lang_common) {
    message($lang_common['Bad request'], false, '404 Not Found');
});
