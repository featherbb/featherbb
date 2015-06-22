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
$feather->get('/forum/:id(/:name)(/page/:page)(/)', '\controller\Viewforum:display')->conditions(array('id' => '[0-9]+', 'page' => '[0-9]+'));

// Viewtopic
$feather->get('/topic/:id(/:name)(/page/:page)(/)', '\controller\Viewtopic:display')->conditions(array('id' => '[0-9]+', 'page' => '[0-9]+'));
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

// Misc
$feather->get('/rules(/)', '\controller\Misc:rules');
$feather->get('/mark-read(/)', '\controller\Misc:markread');
$feather->get('/mark-forum-read/:id(/)', '\controller\Misc:markforumread')->conditions(array('id' => '[0-9]+'));
$feather->map('/email/:id(/)', '\controller\Misc:email')->conditions(array('id' => '[0-9]+'))->via('GET', 'POST');
$feather->map('/report/:id(/)', '\controller\Misc:report')->conditions(array('id' => '[0-9]+'))->via('GET', 'POST');
$feather->get('/subscribe/forum/:id(/)', '\controller\Misc:subscribeforum')->conditions(array('id' => '[0-9]+'));
$feather->get('/unsubscribe/forum/:id(/)', '\controller\Misc:unsubscribeforum')->conditions(array('id' => '[0-9]+'));
$feather->get('/subscribe/topic/:id(/)', '\controller\Misc:subscribetopic')->conditions(array('id' => '[0-9]+'));
$feather->get('/unsubscribe/topic/:id(/)', '\controller\Misc:unsubscribetopic')->conditions(array('id' => '[0-9]+'));

// Profile
$feather->map('/user/:id(/section/:section)(/)', '\controller\Profile:display')->conditions(array('id' => '[0-9]+'))->via('GET', 'POST');
$feather->map('/user/:id(/action/:action)(/)', '\controller\Profile:action')->conditions(array('id' => '[0-9]+'))->via('GET', 'POST');

// Moderate
$feather->get('/moderate/forum/:id(/:name)(/page/:page)(/)', '\controller\Moderate:display')->conditions(array('id' => '[0-9]+', 'page' => '[0-9]+'));
$feather->get('/moderate/get-host/post/:pid(/)', '\controller\Moderate:gethostpost')->conditions(array('pid' => '[0-9]+'));
$feather->map('/moderate/topic/:id/forum/:fid/action/:action(/param/:param)(/)', '\controller\Moderate:moderatetopic')->conditions(array('id' => '[0-9]+', 'fid' => '[0-9]+', 'param' => '[0-9]+'))->via('GET', 'POST');
$feather->map('/moderate/topic/:id/forum/:fid/action/:action(/page/:param)(/)', '\controller\Moderate:moderatetopic')->conditions(array('id' => '[0-9]+', 'fid' => '[0-9]+', 'param' => '[0-9]+'))->via('GET', 'POST');
$feather->post('/moderate/forum/:fid(/page/:page)(/)', '\controller\Moderate:dealposts')->conditions(array('fid' => '[0-9]+', 'page' => '[0-9]+'));

// Admin index
$feather->get('/admin(/action/:action)(/)', '\controller\admin\Index:display');

// 404 not found
$feather->notFound(function () use ($lang_common) {
    message($lang_common['Bad request'], false, '404 Not Found');
});
