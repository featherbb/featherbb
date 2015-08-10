<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */
 

// Index
$feather->get('/', '\controller\index:display');

// Viewforum
$feather->get('/forum/:id(/:name)(/page/:page)(/)', '\controller\viewforum:display')->conditions(array('id' => '[0-9]+', 'page' => '[0-9]+'));

// Viewtopic
$feather->group('/topic', function() use ($feather) {
    $feather->get('/:id(/:name)(/page/:page)(/)', '\controller\viewtopic:display')->conditions(array('id' => '[0-9]+', 'page' => '[0-9]+'));
    $feather->get('/:id/action/:action(/)', '\controller\viewtopic:action')->conditions(array('id' => '[0-9]+'));
});
$feather->get('/post/:pid(/)', '\controller\viewtopic:viewpost')->conditions(array('pid' => '[0-9]+'));

// Userlist
$feather->get('/userlist(/)', '\controller\userlist:display');

// Login routes
$feather->group('/login', function() use ($feather) {
    $feather->get('(/)', '\controller\login:display');
    $feather->post('/action/in(/)', '\controller\login:logmein');
    $feather->map('/action/forget(/)', '\controller\login:forget')->via('GET', 'POST');
});
$feather->get('/logout/id/:id/token/:token(/)', '\controller\login:logmeout')->conditions(array('id' => '[0-9]+'));

// Register routes
$feather->group('/register', function() use ($feather) {
    $feather->get('(/)', '\controller\register:rules');
    $feather->map('/agree(/)', '\controller\register:display')->via('GET', 'POST');
    $feather->get('/cancel(/)', '\controller\register:cancel');
});

// Post routes
$feather->group('/post', function() use ($feather) {
    $feather->map('/new-topic/:fid(/)', '\controller\post:newpost')->conditions(array('fid' => '[0-9]+'))->via('GET', 'POST');
    $feather->map('/reply/:tid(/)(/quote/:qid)(/)', '\controller\post:newreply')->conditions(array('tid' => '[0-9]+', 'qid' => '[0-9]+'))->via('GET', 'POST');
});

// Edit
$feather->map('/edit/:id(/)', '\controller\edit:editpost')->conditions(array('id' => '[0-9]+'))->via('GET', 'POST');

// Delete
$feather->map('/delete/:id(/)', '\controller\delete:deletepost')->conditions(array('id' => '[0-9]+'))->via('GET', 'POST');

// Search routes
$feather->group('/search', function() use ($feather) {
    $feather->get('(/)', '\controller\search:display');
    $feather->get('/show/:show(/)', '\controller\search:quicksearches');
});

// Help
$feather->get('/help(/)', '\controller\help:display');

// Misc
$feather->get('/rules(/)', '\controller\misc:rules');
$feather->get('/mark-read(/)', '\controller\misc:markread');
$feather->get('/mark-forum-read/:id(/)', '\controller\misc:markforumread')->conditions(array('id' => '[0-9]+'));
$feather->map('/email/:id(/)', '\controller\misc:email')->conditions(array('id' => '[0-9]+'))->via('GET', 'POST');
$feather->map('/report/:id(/)', '\controller\misc:report')->conditions(array('id' => '[0-9]+'))->via('GET', 'POST');
$feather->get('/subscribe/forum/:id(/)', '\controller\misc:subscribeforum')->conditions(array('id' => '[0-9]+'));
$feather->get('/unsubscribe/forum/:id(/)', '\controller\misc:unsubscribeforum')->conditions(array('id' => '[0-9]+'));
$feather->get('/subscribe/topic/:id(/)', '\controller\misc:subscribetopic')->conditions(array('id' => '[0-9]+'));
$feather->get('/unsubscribe/topic/:id(/)', '\controller\misc:unsubscribetopic')->conditions(array('id' => '[0-9]+'));

// Profile routes
$feather->group('/user', function() use ($feather) {
    $feather->map('/:id(/section/:section)(/)', '\controller\profile:display')->conditions(array('id' => '[0-9]+'))->via('GET', 'POST');
    $feather->map('/:id(/action/:action)(/)', '\controller\profile:action')->conditions(array('id' => '[0-9]+'))->via('GET', 'POST');
});

// Moderate routes
$feather->group('/moderate', function() use ($feather) {
    $feather->get('/forum/:id(/:name)(/page/:page)(/)', '\controller\moderate:display')->conditions(array('id' => '[0-9]+', 'page' => '[0-9]+'));
    $feather->get('/get-host/post/:pid(/)', '\controller\moderate:gethostpost')->conditions(array('pid' => '[0-9]+'));
    $feather->get('/get-host/ip/:ip(/)', '\controller\moderate:gethostip');
    $feather->map('/topic/:id/forum/:fid/action/:action(/param/:param)(/)', '\controller\moderate:moderatetopic')->conditions(array('id' => '[0-9]+', 'fid' => '[0-9]+', 'param' => '[0-9]+'))->via('GET', 'POST');
    $feather->map('/topic/:id/forum/:fid/action/:action(/page/:param)(/)', '\controller\moderate:moderatetopic')->conditions(array('id' => '[0-9]+', 'fid' => '[0-9]+', 'param' => '[0-9]+'))->via('GET', 'POST');
    $feather->post('/forum/:fid(/page/:page)(/)', '\controller\moderate:dealposts')->conditions(array('fid' => '[0-9]+', 'page' => '[0-9]+'));
});

// Admin routes
$feather->group('/admin', function() use ($feather) {

    // Admin index
    $feather->get('(/action/:action)(/)', '\controller\admin\index:display');
    $feather->get('/index(/)', '\controller\admin\index:display');

    // Admin bans
    $feather->group('/bans', function() use ($feather) {
        $feather->get('(/)', '\controller\admin\bans:display');
        $feather->get('/delete/:id(/)', '\controller\admin\bans:delete')->conditions(array('id' => '[0-9]+'));
        $feather->map('/edit/:id(/)', '\controller\admin\bans:edit')->conditions(array('id' => '[0-9]+'))->via('GET', 'POST');
        $feather->map('/add(/:id)(/)', '\controller\admin\bans:add')->via('GET', 'POST');
    });

    // Admin options
    $feather->map('/options(/)', '\controller\admin\options:display')->via('GET', 'POST');

    // Admin categories
    $feather->group('/categories', function() use ($feather) {
        $feather->get('(/)', '\controller\admin\categories:display');
        $feather->post('/add(/)', '\controller\admin\categories:add_category');
        $feather->post('/edit(/)', '\controller\admin\categories:edit_categories');
        $feather->post('/delete(/)', '\controller\admin\categories:delete_category');
    });

    // Admin censoring
    $feather->map('/censoring(/)', '\controller\admin\censoring:display')->via('GET', 'POST');

    // Admin reports
    $feather->map('/reports(/)', '\controller\admin\reports:display')->via('GET', 'POST');

    // Admin permissions
    $feather->map('/permissions(/)', '\controller\admin\permissions:display')->via('GET', 'POST');

    // Admin statistics
    $feather->get('/statistics(/)', '\controller\admin\statistics:display');
    $feather->get('/phpinfo(/)', '\controller\admin\statistics:phpinfo');

    // Admin forums
    $feather->group('/forums', function() use ($feather) {
        $feather->map('(/)', '\controller\admin\forums:display')->via('GET', 'POST');
        $feather->post('/add(/)', '\controller\admin\forums:add_forum');
        $feather->map('/edit/:id(/)', '\controller\admin\forums:edit_forum')->conditions(array('id' => '[0-9]+'))->via('GET', 'POST');
        $feather->map('/delete/:id(/)', '\controller\admin\forums:delete_forum')->conditions(array('id' => '[0-9]+'))->via('GET', 'POST');
    });

    // Admin groups
    $feather->group('/groups', function() use ($feather) {
        $feather->map('(/)', '\controller\admin\groups:display')->via('GET', 'POST');
        $feather->map('/add(/)', '\controller\admin\groups:addedit')->via('GET', 'POST');
        $feather->map('/edit/:id(/)', '\controller\admin\groups:addedit')->conditions(array('id' => '[0-9]+'))->via('GET', 'POST');
        $feather->map('/delete/:id(/)', '\controller\admin\groups:delete')->conditions(array('id' => '[0-9]+'))->via('GET', 'POST');
    });

    // Admin plugins
    $feather->map('/loader(/)', '\controller\admin\plugins:display')->via('GET', 'POST');

    // Admin maintenance
    $feather->map('/maintenance(/)', '\controller\admin\maintenance:display')->via('GET', 'POST');

    // Admin parser
    $feather->map('/parser(/)', '\controller\admin\parser:display')->via('GET', 'POST');

    // Admin users
    $feather->group('/users', function() use ($feather) {
        $feather->map('(/)', '\controller\admin\users:display')->via('GET', 'POST');
        $feather->get('/ip-stats/id/:id(/)', '\controller\admin\users:ipstats')->conditions(array('id' => '[0-9]+'));
        $feather->get('/show-users/ip/:ip(/)', '\controller\admin\users:showusers');
    });
    
});

// 404 not found
$feather->notFound(function () use ($lang_common) {
    message($lang_common['Bad request'], '404');
});
