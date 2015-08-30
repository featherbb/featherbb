<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

// Index
$feather->get('/', '\app\controller\index:display');

// Viewforum
$feather->get('/forum/:id(/:name)(/page/:page)(/)', '\app\controller\viewforum:display')->conditions(array('id' => '[0-9]+', 'page' => '[0-9]+'));

// Viewtopic
$feather->group('/topic', function() use ($feather) {
    $feather->get('/:id(/:name)(/page/:page)(/)', '\app\controller\viewtopic:display')->conditions(array('id' => '[0-9]+', 'page' => '[0-9]+'));
    $feather->get('/:id/action/:action(/)', '\app\controller\viewtopic:action')->conditions(array('id' => '[0-9]+'));
});
$feather->get('/post/:pid(/)', '\app\controller\viewtopic:viewpost')->conditions(array('pid' => '[0-9]+'));

// Userlist
$feather->get('/userlist(/)', '\app\controller\userlist:display');

// Auth routes
$feather->group('/auth', function() use ($feather) {
    $feather->get('(/)', function () use ($feather) {
        if (!$feather->user->is_guest) {
            $this->feather->url->redirect($this->feather->url->get('/'));
        } else {
            $this->feather->url->redirect($this->feather->url->get('/auth/login'));
        }
    });
    $feather->map('/login(/)', '\app\controller\auth:login')->via('GET', 'POST');
    $feather->map('/forget(/)', '\app\controller\auth:forget')->via('GET', 'POST');
    $feather->get('/logout/token/:token(/)', '\app\controller\auth:logout');
});

// Register routes
$feather->group('/register', function() use ($feather) {
    $feather->get('(/)', '\app\controller\register:rules');
    $feather->map('/agree(/)', '\app\controller\register:display')->via('GET', 'POST');
    $feather->get('/cancel(/)', '\app\controller\register:cancel');
});

// Post routes
$feather->group('/post', function() use ($feather) {
    $feather->map('/new-topic/:fid(/)', '\app\controller\post:newpost')->conditions(array('fid' => '[0-9]+'))->via('GET', 'POST');
    $feather->map('/reply/:tid(/)(/quote/:qid)(/)', '\app\controller\post:newreply')->conditions(array('tid' => '[0-9]+', 'qid' => '[0-9]+'))->via('GET', 'POST');
});

// Edit
$feather->map('/edit/:id(/)', '\app\controller\edit:editpost')->conditions(array('id' => '[0-9]+'))->via('GET', 'POST');

// Delete
$feather->map('/delete/:id(/)', '\app\controller\delete:deletepost')->conditions(array('id' => '[0-9]+'))->via('GET', 'POST');

// Search routes
$feather->group('/search', function() use ($feather) {
    $feather->get('(/)', '\app\controller\search:display');
    $feather->get('/show/:show(/)', '\app\controller\search:quicksearches');
});

// Help
$feather->get('/help(/)', '\app\controller\help:display');

// Misc
$feather->get('/rules(/)', '\app\controller\misc:rules');
$feather->get('/mark-read(/)', '\app\controller\misc:markread');
$feather->get('/mark-forum-read/:id(/)', '\app\controller\misc:markforumread')->conditions(array('id' => '[0-9]+'));
$feather->map('/email/:id(/)', '\app\controller\misc:email')->conditions(array('id' => '[0-9]+'))->via('GET', 'POST');
$feather->map('/report/:id(/)', '\app\controller\misc:report')->conditions(array('id' => '[0-9]+'))->via('GET', 'POST');
$feather->get('/subscribe/forum/:id(/)', '\app\controller\misc:subscribeforum')->conditions(array('id' => '[0-9]+'));
$feather->get('/unsubscribe/forum/:id(/)', '\app\controller\misc:unsubscribeforum')->conditions(array('id' => '[0-9]+'));
$feather->get('/subscribe/topic/:id(/)', '\app\controller\misc:subscribetopic')->conditions(array('id' => '[0-9]+'));
$feather->get('/unsubscribe/topic/:id(/)', '\app\controller\misc:unsubscribetopic')->conditions(array('id' => '[0-9]+'));

// Profile routes
$feather->group('/user', function() use ($feather) {
    $feather->map('/:id(/section/:section)(/)', '\app\controller\profile:display')->conditions(array('id' => '[0-9]+'))->via('GET', 'POST');
    $feather->map('/:id(/action/:action)(/)', '\app\controller\profile:action')->conditions(array('id' => '[0-9]+'))->via('GET', 'POST');
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
    $feather->get('/forum/:id(/:name)(/page/:page)(/)', '\app\controller\moderate:display')->conditions(array('id' => '[0-9]+', 'page' => '[0-9]+'));
    $feather->get('/get-host/post/:pid(/)', '\app\controller\moderate:gethostpost')->conditions(array('pid' => '[0-9]+'));
    $feather->get('/get-host/ip/:ip(/)', '\app\controller\moderate:gethostip');
    $feather->map('/topic/:id/forum/:fid/action/:action(/param/:param)(/)', '\app\controller\moderate:moderatetopic')->conditions(array('id' => '[0-9]+', 'fid' => '[0-9]+', 'param' => '[0-9]+'))->via('GET', 'POST');
    $feather->map('/topic/:id/forum/:fid/action/:action(/page/:param)(/)', '\app\controller\moderate:moderatetopic')->conditions(array('id' => '[0-9]+', 'fid' => '[0-9]+', 'param' => '[0-9]+'))->via('GET', 'POST');
    $feather->post('/forum/:fid(/page/:page)(/)', '\app\controller\moderate:dealposts')->conditions(array('fid' => '[0-9]+', 'page' => '[0-9]+'));
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
    $feather->get('(/action/:action)(/)', '\app\controller\admin\index:display');
    $feather->get('/index(/)', '\app\controller\admin\index:display');

    // Admin bans
    $feather->group('/bans', function() use ($feather) {
        $feather->get('(/)', '\app\controller\admin\bans:display');
        $feather->get('/delete/:id(/)', '\app\controller\admin\bans:delete')->conditions(array('id' => '[0-9]+'));
        $feather->map('/edit/:id(/)', '\app\controller\admin\bans:edit')->conditions(array('id' => '[0-9]+'))->via('GET', 'POST');
        $feather->map('/add(/:id)(/)', '\app\controller\admin\bans:add')->via('GET', 'POST');
    });

    // Admin options
    $feather->map('/options(/)', $isAdmin, '\app\controller\admin\options:display')->via('GET', 'POST');

    // Admin categories
    $feather->group('/categories', $isAdmin, function() use ($feather) {
        $feather->get('(/)', '\app\controller\admin\categories:display');
        $feather->post('/add(/)', '\app\controller\admin\categories:add_category');
        $feather->post('/edit(/)', '\app\controller\admin\categories:edit_categories');
        $feather->post('/delete(/)', '\app\controller\admin\categories:delete_category');
    });

    // Admin censoring
    $feather->map('/censoring(/)', $isAdmin, '\app\controller\admin\censoring:display')->via('GET', 'POST');

    // Admin reports
    $feather->map('/reports(/)', '\app\controller\admin\reports:display')->via('GET', 'POST');

    // Admin permissions
    $feather->map('/permissions(/)', $isAdmin, '\app\controller\admin\permissions:display')->via('GET', 'POST');

    // Admin statistics
    $feather->get('/statistics(/)', '\app\controller\admin\statistics:display');
    $feather->get('/phpinfo(/)', '\app\controller\admin\statistics:phpinfo');

    // Admin forums
    $feather->group('/forums', $isAdmin, function() use ($feather) {
        $feather->map('(/)', '\app\controller\admin\forums:display')->via('GET', 'POST');
        $feather->post('/add(/)', '\app\controller\admin\forums:add_forum');
        $feather->map('/edit/:id(/)', '\app\controller\admin\forums:edit_forum')->conditions(array('id' => '[0-9]+'))->via('GET', 'POST');
        $feather->map('/delete/:id(/)', '\app\controller\admin\forums:delete_forum')->conditions(array('id' => '[0-9]+'))->via('GET', 'POST');
    });

    // Admin groups
    $feather->group('/groups', $isAdmin, function() use ($feather) {
        $feather->map('(/)', '\app\controller\admin\groups:display')->via('GET', 'POST');
        $feather->map('/add(/)', '\app\controller\admin\groups:addedit')->via('GET', 'POST');
        $feather->map('/edit/:id(/)', '\app\controller\admin\groups:addedit')->conditions(array('id' => '[0-9]+'))->via('GET', 'POST');
        $feather->map('/delete/:id(/)', '\app\controller\admin\groups:delete')->conditions(array('id' => '[0-9]+'))->via('GET', 'POST');
    });

    // Admin plugins
    $feather->group('/plugins', function() use ($feather) {
        $feather->map('/(/)', '\app\controller\admin\plugins:index')->via('GET', 'POST');
        $feather->map('/activate(/)', '\app\controller\admin\plugins:activate')->via('GET');
        $feather->map('/deactivate(/)', '\app\controller\admin\plugins:deactivate')->via('GET');
        // $feather->map('/loader(/)', '\app\controller\admin\plugins:display')->via('GET', 'POST');
    });

    // Admin maintenance
    $feather->map('/maintenance(/)', $isAdmin, '\app\controller\admin\maintenance:display')->via('GET', 'POST');

    // Admin parser
    $feather->map('/parser(/)', $isAdmin, '\app\controller\admin\parser:display')->via('GET', 'POST');

    // Admin users
    $feather->group('/users', function() use ($feather) {
        $feather->map('(/)', '\app\controller\admin\users:display')->via('GET', 'POST');
        $feather->get('/ip-stats/id/:id(/)', '\app\controller\admin\users:ipstats')->conditions(array('id' => '[0-9]+'));
        $feather->get('/show-users/ip/:ip(/)', '\app\controller\admin\users:showusers');
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
