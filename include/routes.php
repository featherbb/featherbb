<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

// Index
$feather->get('/', '\App\Controller\index:display');

// Viewforum
$feather->get('/forum/:id(/:name)(/page/:page)(/)', '\App\Controller\viewforum:display')->conditions(array('id' => '[0-9]+', 'page' => '[0-9]+'));

// Viewtopic
$feather->group('/topic', function() use ($feather) {
    $feather->get('/:id(/:name)(/page/:page)(/)', '\App\Controller\viewtopic:display')->conditions(array('id' => '[0-9]+', 'page' => '[0-9]+'));
    $feather->get('/:id/action/:action(/)', '\App\Controller\viewtopic:action')->conditions(array('id' => '[0-9]+'));
});
$feather->get('/post/:pid(/)', '\App\Controller\viewtopic:viewpost')->conditions(array('pid' => '[0-9]+'));

// Userlist
$feather->get('/userlist(/)', '\App\Controller\userlist:display');

// Auth routes
$feather->group('/auth', function() use ($feather) {
    $feather->get('(/)', function () use ($feather) {
        if (!$feather->user->is_guest) {
            $this->feather->url->redirect($this->feather->url->get('/'));
        } else {
            $this->feather->url->redirect($this->feather->url->get('/auth/login'));
        }
    });
    $feather->map('/login(/)', '\App\Controller\auth:login')->via('GET', 'POST');
    $feather->map('/forget(/)', '\App\Controller\auth:forget')->via('GET', 'POST');
    $feather->get('/logout/token/:token(/)', '\App\Controller\auth:logout');
});

// Register routes
$feather->group('/register', function() use ($feather) {
    $feather->get('(/)', '\App\Controller\register:rules');
    $feather->map('/agree(/)', '\App\Controller\register:display')->via('GET', 'POST');
    $feather->get('/cancel(/)', '\App\Controller\register:cancel');
});

// Post routes
$feather->group('/post', function() use ($feather) {
    $feather->map('/new-topic/:fid(/)', '\App\Controller\post:newpost')->conditions(array('fid' => '[0-9]+'))->via('GET', 'POST');
    $feather->map('/reply/:tid(/)(/quote/:qid)(/)', '\App\Controller\post:newreply')->conditions(array('tid' => '[0-9]+', 'qid' => '[0-9]+'))->via('GET', 'POST');
});

// Edit
$feather->map('/edit/:id(/)', '\App\Controller\edit:editpost')->conditions(array('id' => '[0-9]+'))->via('GET', 'POST');

// Delete
$feather->map('/delete/:id(/)', '\App\Controller\delete:deletepost')->conditions(array('id' => '[0-9]+'))->via('GET', 'POST');

// Search routes
$feather->group('/search', function() use ($feather) {
    $feather->get('(/)', '\App\Controller\search:display');
    $feather->get('/show/:show(/)', '\App\Controller\search:quicksearches');
});

// Help
$feather->get('/help(/)', '\App\Controller\help:display');

// Misc
$feather->get('/rules(/)', '\App\Controller\misc:rules');
$feather->get('/mark-read(/)', '\App\Controller\misc:markread');
$feather->get('/mark-forum-read/:id(/)', '\App\Controller\misc:markforumread')->conditions(array('id' => '[0-9]+'));
$feather->map('/email/:id(/)', '\App\Controller\misc:email')->conditions(array('id' => '[0-9]+'))->via('GET', 'POST');
$feather->map('/report/:id(/)', '\App\Controller\misc:report')->conditions(array('id' => '[0-9]+'))->via('GET', 'POST');
$feather->get('/subscribe/forum/:id(/)', '\App\Controller\misc:subscribeforum')->conditions(array('id' => '[0-9]+'));
$feather->get('/unsubscribe/forum/:id(/)', '\App\Controller\misc:unsubscribeforum')->conditions(array('id' => '[0-9]+'));
$feather->get('/subscribe/topic/:id(/)', '\App\Controller\misc:subscribetopic')->conditions(array('id' => '[0-9]+'));
$feather->get('/unsubscribe/topic/:id(/)', '\App\Controller\misc:unsubscribetopic')->conditions(array('id' => '[0-9]+'));

// Profile routes
$feather->group('/user', function() use ($feather) {
    $feather->map('/:id(/section/:section)(/)', '\App\Controller\profile:display')->conditions(array('id' => '[0-9]+'))->via('GET', 'POST');
    $feather->map('/:id(/action/:action)(/)', '\App\Controller\profile:action')->conditions(array('id' => '[0-9]+'))->via('GET', 'POST');
});

/**
 * Middleware to check if user is allowed to moderate, if he's not redirect to homepage.
 */
$isAdmmod = function() use ($feather) {
    if(!$feather->user->is_admmod) {
        redirect($feather->url->base(), __('No permission'));
    }
};

// Moderate routes
$feather->group('/moderate', $isAdmmod, function() use ($feather) {
    $feather->get('/forum/:id(/:name)(/page/:page)(/)', '\App\Controller\moderate:display')->conditions(array('id' => '[0-9]+', 'page' => '[0-9]+'));
    $feather->get('/get-host/post/:pid(/)', '\App\Controller\moderate:gethostpost')->conditions(array('pid' => '[0-9]+'));
    $feather->get('/get-host/ip/:ip(/)', '\App\Controller\moderate:gethostip');
    $feather->map('/topic/:id/forum/:fid/action/:action(/param/:param)(/)', '\App\Controller\moderate:moderatetopic')->conditions(array('id' => '[0-9]+', 'fid' => '[0-9]+', 'param' => '[0-9]+'))->via('GET', 'POST');
    $feather->map('/topic/:id/forum/:fid/action/:action(/page/:param)(/)', '\App\Controller\moderate:moderatetopic')->conditions(array('id' => '[0-9]+', 'fid' => '[0-9]+', 'param' => '[0-9]+'))->via('GET', 'POST');
    $feather->post('/forum/:fid(/page/:page)(/)', '\App\Controller\moderate:dealposts')->conditions(array('fid' => '[0-9]+', 'page' => '[0-9]+'));
});

// Admin routes
$feather->group('/admin', $isAdmmod, function() use ($feather) {

    /**
     * Middleware to check if user is admin.
     */
    $isAdmin = function() use ($feather) {
        if($feather->user->g_id != FEATHER_ADMIN) {
            redirect($feather->url->base(), __('No permission'));
        }
    };

    // Admin index
    $feather->get('(/action/:action)(/)', '\App\Controller\Admin\index:display');
    $feather->get('/index(/)', '\App\Controller\Admin\index:display');

    // Admin bans
    $feather->group('/bans', function() use ($feather) {
        $feather->get('(/)', '\App\Controller\Admin\Bans:display');
        $feather->get('/delete/:id(/)', '\App\Controller\Admin\Bans:delete')->conditions(array('id' => '[0-9]+'));
        $feather->map('/edit/:id(/)', '\App\Controller\Admin\Bans:edit')->conditions(array('id' => '[0-9]+'))->via('GET', 'POST');
        $feather->map('/add(/:id)(/)', '\App\Controller\Admin\Bans:add')->via('GET', 'POST');
    });

    // Admin options
    $feather->map('/options(/)', $isAdmin, '\App\Controller\Admin\options:display')->via('GET', 'POST');

    // Admin categories
    $feather->group('/categories', $isAdmin, function() use ($feather) {
        $feather->get('(/)', '\App\Controller\Admin\categories:display');
        $feather->post('/add(/)', '\App\Controller\Admin\categories:add_category');
        $feather->post('/edit(/)', '\App\Controller\Admin\categories:edit_categories');
        $feather->post('/delete(/)', '\App\Controller\Admin\categories:delete_category');
    });

    // Admin censoring
    $feather->map('/censoring(/)', $isAdmin, '\App\Controller\Admin\censoring:display')->via('GET', 'POST');

    // Admin reports
    $feather->map('/reports(/)', '\App\Controller\Admin\reports:display')->via('GET', 'POST');

    // Admin permissions
    $feather->map('/permissions(/)', $isAdmin, '\App\Controller\Admin\permissions:display')->via('GET', 'POST');

    // Admin statistics
    $feather->get('/statistics(/)', '\App\Controller\Admin\statistics:display');
    $feather->get('/phpinfo(/)', '\App\Controller\Admin\statistics:phpinfo');

    // Admin forums
    $feather->group('/forums', $isAdmin, function() use ($feather) {
        $feather->map('(/)', '\App\Controller\Admin\forums:display')->via('GET', 'POST');
        $feather->post('/add(/)', '\App\Controller\Admin\forums:add_forum');
        $feather->map('/edit/:id(/)', '\App\Controller\Admin\forums:edit_forum')->conditions(array('id' => '[0-9]+'))->via('GET', 'POST');
        $feather->map('/delete/:id(/)', '\App\Controller\Admin\forums:delete_forum')->conditions(array('id' => '[0-9]+'))->via('GET', 'POST');
    });

    // Admin groups
    $feather->group('/groups', $isAdmin, function() use ($feather) {
        $feather->map('(/)', '\App\Controller\Admin\groups:display')->via('GET', 'POST');
        $feather->map('/add(/)', '\App\Controller\Admin\groups:addedit')->via('GET', 'POST');
        $feather->map('/edit/:id(/)', '\App\Controller\Admin\groups:addedit')->conditions(array('id' => '[0-9]+'))->via('GET', 'POST');
        $feather->map('/delete/:id(/)', '\App\Controller\Admin\groups:delete')->conditions(array('id' => '[0-9]+'))->via('GET', 'POST');
    });

    // Admin plugins
    $feather->group('/plugins', function() use ($feather) {
        $feather->map('/(/)', '\App\Controller\Admin\plugins:index')->via('GET', 'POST');
        $feather->map('/activate(/)', '\App\Controller\Admin\plugins:activate')->via('GET');
        $feather->map('/deactivate(/)', '\App\Controller\Admin\plugins:deactivate')->via('GET');
        // $feather->map('/loader(/)', '\App\Controller\Admin\plugins:display')->via('GET', 'POST');
    });

    // Admin maintenance
    $feather->map('/maintenance(/)', $isAdmin, '\App\Controller\Admin\maintenance:display')->via('GET', 'POST');

    // Admin parser
    $feather->map('/parser(/)', $isAdmin, '\App\Controller\Admin\parser:display')->via('GET', 'POST');

    // Admin users
    $feather->group('/users', function() use ($feather) {
        $feather->map('(/)', '\App\Controller\Admin\users:display')->via('GET', 'POST');
        $feather->get('/ip-stats/id/:id(/)', '\App\Controller\Admin\users:ipstats')->conditions(array('id' => '[0-9]+'));
        $feather->get('/show-users/ip/:ip(/)', '\App\Controller\Admin\users:showusers');
    });

});

// 404 not found
$feather->notFound(function () use ($feather){
    throw new \FeatherBB\Error('Page not found', 404);
});

$feather->error(function (\Exception $e) use ($feather) {
    $feather->response->setStatus($e->getCode());
    $feather->view2->setPageInfo(array(
        'title' => array($feather->utils->escape($feather->config['o_board_title']), __('Error')),
        'msg_title' => __('Error'),
        'msg'    =>   $e->getMessage(),
        'no_back_link'    => false,
        ))->addTemplate('error.php')->display();
    $feather->stop();
});
