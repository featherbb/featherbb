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
$feather->get('/topic/:id(/:name)(/page/:page)(/)', '\controller\viewtopic:display')->conditions(array('id' => '[0-9]+', 'page' => '[0-9]+'));
$feather->get('/topic/:id/action/:action(/)', '\controller\viewtopic:action')->conditions(array('id' => '[0-9]+'));
$feather->get('/post/:pid(/)', '\controller\viewtopic:viewpost')->conditions(array('pid' => '[0-9]+'));

// Userlist
$feather->get('/userlist(/)', '\controller\userlist:display');

// Login
$feather->get('/login(/)', '\controller\login:display');
$feather->post('/login/action/in(/)', '\controller\login:logmein');
$feather->map('/login/action/forget(/)', '\controller\login:forget')->via('GET', 'POST');
$feather->get('/logout/id/:id/token/:token(/)', '\controller\login:logmeout')->conditions(array('id' => '[0-9]+'));

// Register
$feather->get('/register(/)', '\controller\register:rules');
$feather->map('/register/agree(/)', '\controller\register:display')->via('GET', 'POST');
$feather->get('/register/cancel(/)', '\controller\register:cancel');

// Post
$feather->map('/post/new-topic/:fid(/)', '\controller\post:newpost')->conditions(array('fid' => '[0-9]+'))->via('GET', 'POST');
$feather->map('/post/reply/:tid(/)(/quote/:qid)(/)', '\controller\post:newreply')->conditions(array('tid' => '[0-9]+', 'qid' => '[0-9]+'))->via('GET', 'POST');

// Edit
$feather->map('/edit/:id(/)', '\controller\edit:editpost')->conditions(array('id' => '[0-9]+'))->via('GET', 'POST');

// Delete
$feather->map('/delete/:id(/)', '\controller\delete:deletepost')->conditions(array('id' => '[0-9]+'))->via('GET', 'POST');

// Search
$feather->get('/search(/)', '\controller\search:display');
$feather->get('/search/show/:show(/)', '\controller\search:quicksearches');

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

// Profile
$feather->map('/user/:id(/section/:section)(/)', '\controller\profile:display')->conditions(array('id' => '[0-9]+'))->via('GET', 'POST');
$feather->map('/user/:id(/action/:action)(/)', '\controller\profile:action')->conditions(array('id' => '[0-9]+'))->via('GET', 'POST');

// Moderate
$feather->get('/moderate/forum/:id(/:name)(/page/:page)(/)', '\controller\moderate:display')->conditions(array('id' => '[0-9]+', 'page' => '[0-9]+'));
$feather->get('/moderate/get-host/post/:pid(/)', '\controller\moderate:gethostpost')->conditions(array('pid' => '[0-9]+'));
$feather->get('/moderate/get-host/ip/:ip(/)', '\controller\moderate:gethostip');
$feather->map('/moderate/topic/:id/forum/:fid/action/:action(/param/:param)(/)', '\controller\moderate:moderatetopic')->conditions(array('id' => '[0-9]+', 'fid' => '[0-9]+', 'param' => '[0-9]+'))->via('GET', 'POST');
$feather->map('/moderate/topic/:id/forum/:fid/action/:action(/page/:param)(/)', '\controller\moderate:moderatetopic')->conditions(array('id' => '[0-9]+', 'fid' => '[0-9]+', 'param' => '[0-9]+'))->via('GET', 'POST');
$feather->post('/moderate/forum/:fid(/page/:page)(/)', '\controller\moderate:dealposts')->conditions(array('fid' => '[0-9]+', 'page' => '[0-9]+'));

// Admin index
$feather->get('/admin(/action/:action)(/)', '\controller\admin\index:display');

// Admin bans
$feather->get('/admin/bans(/)', '\controller\admin\bans:display');
$feather->get('/admin/bans/delete/:id(/)', '\controller\admin\bans:delete')->conditions(array('id' => '[0-9]+'));
$feather->map('/admin/bans/edit/:id(/)', '\controller\admin\bans:edit')->conditions(array('id' => '[0-9]+'))->via('GET', 'POST');
$feather->map('/admin/bans/add(/)', '\controller\admin\bans:add')->via('GET', 'POST');

// Admin options
$feather->map('/admin/options(/)', '\controller\admin\options:display')->via('GET', 'POST');

// Admin categories
$feather->map('/admin/categories(/)', '\controller\admin\categories:display')->via('GET', 'POST');

// Admin censoring
$feather->map('/admin/censoring(/)', '\controller\admin\censoring:display')->via('GET', 'POST');

// Admin reports
$feather->map('/admin/reports(/)', '\controller\admin\reports:display')->via('GET', 'POST');

// Admin permissions
$feather->map('/admin/permissions(/)', '\controller\admin\permissions:display')->via('GET', 'POST');

// Admin statistics
$feather->get('/admin/statistics(/)', '\controller\admin\statistics:display');
$feather->get('/admin/phpinfo(/)', '\controller\admin\statistics:phpinfo');

// Admin forums
$feather->map('/admin/forums(/)', '\controller\admin\forums:display')->via('GET', 'POST');
$feather->map('/admin/forums/delete/:id(/)', '\controller\admin\forums:delete')->conditions(array('id' => '[0-9]+'))->via('GET', 'POST');
$feather->map('/admin/forums/edit/:id(/)', '\controller\admin\forums:edit')->conditions(array('id' => '[0-9]+'))->via('GET', 'POST');

// Admin groups
$feather->map('/admin/groups(/)', '\controller\admin\groups:display')->via('GET', 'POST');
$feather->map('/admin/groups/add(/)', '\controller\admin\groups:addedit')->via('GET', 'POST');
$feather->map('/admin/groups/edit/:id(/)', '\controller\admin\groups:addedit')->conditions(array('id' => '[0-9]+'))->via('GET', 'POST');
$feather->map('/admin/groups/delete/:id(/)', '\controller\admin\groups:delete')->conditions(array('id' => '[0-9]+'))->via('GET', 'POST');

// Admin plugins
$feather->map('/admin/loader(/)', '\controller\admin\plugins:display')->via('GET', 'POST');

// Admin maintenance
$feather->map('/admin/maintenance(/)', '\controller\admin\maintenance:display')->via('GET', 'POST');

// Admin parser
$feather->map('/admin/parser(/)', '\controller\admin\parser:display')->via('GET', 'POST');

// Admin users
$feather->map('/admin/users(/)', '\controller\admin\users:display')->via('GET', 'POST');
$feather->get('/admin/users/ip-stats/id/:id(/)', '\controller\admin\users:ipstats')->conditions(array('id' => '[0-9]+'));
$feather->get('/admin/users/show-users/ip/:ip(/)', '\controller\admin\users:showusers');

// 404 not found
$feather->notFound(function () use ($lang_common) {
    message($lang_common['Bad request'], false, '404 Not Found');
});
