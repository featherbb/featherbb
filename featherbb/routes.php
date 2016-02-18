<?php

/**
 * Copyright (C) 2015-2016 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

use FeatherBB\Core\Error;

/**
 * Middleware to check if user is allowed to read the board.
 */
$canReadBoard = function () use ($feather) {
    if ($feather->user->g_read_board == '0') {
        throw new Error(__('No view'), 403);
    }
};

/**
 * Middleware to check if user is allowed to read the board.
 */
$isGuest = function () use ($feather) {
    if ($feather->user->is_guest) {
        throw new Error(__('No permission'), 403);
    }
};

/**
 * Middleware to check if user is allowed to moderate, if he's not redirect to homepage.
 */
$isAdmmod = function() use ($feather) {
    // if(!$feather->user->is_admmod) {
    //     throw new Error(__('No permission'), 403);
    // }
};

// Index
$feather->get('/', $canReadBoard, '\FeatherBB\Controller\Index:display')->setName('home');
$feather->get('/rules(/)', '\FeatherBB\Controller\Index:rules')->setName('rules');
$feather->get('/mark-read(/)', $isGuest, '\FeatherBB\Controller\Index:markread')->setName('markRead');

// Forum
$feather->group('/forum', $canReadBoard, function() use ($feather) {
    $isGuest = function () use ($feather) {
        if ($feather->user->is_guest) {
            throw new Error(__('No permission'), 403);
        }
    };
    $feather->get('/:id(/:name)(/)', '\FeatherBB\Controller\Forum:display')->conditions(array('id' => '[0-9]+', 'page' => '[0-9]+'))->setName('Forum');
    $feather->get('/:id(/:name)(/page/:page)(/)', '\FeatherBB\Controller\Forum:display')->conditions(array('id' => '[0-9]+', 'page' => '[0-9]+'))->setName('ForumPaginate');
    $feather->get('/mark-read/:id(/:name)(/)', $isGuest, '\FeatherBB\Controller\Forum:markread')->conditions(array('id' => '[0-9]+'))->setName('markForumRead');
    $feather->get('/subscribe/:id(/:name)(/)', $isGuest, '\FeatherBB\Controller\Forum:subscribe')->conditions(array('id' => '[0-9]+'))->setName('subscribeForum');
    $feather->get('/unsubscribe/:id(/:name)(/)', $isGuest, '\FeatherBB\Controller\Forum:unsubscribe')->conditions(array('id' => '[0-9]+'))->setName('unsubscribeForum');
    $feather->get('/moderate/:fid/page/:page(/)', '\FeatherBB\Controller\Forum:moderate')->conditions(array('id' => '[0-9]+', 'page' => '[0-9]+'))->setName('moderateForum');
    $feather->post('/moderate/:fid(/page/:page)(/)', '\FeatherBB\Controller\Forum:dealposts')->conditions(array('fid' => '[0-9]+', 'page' => '[0-9]+'))->setName('dealPosts');
});

// Topic
$feather->group('/topic', $canReadBoard, function() use ($feather) {
    $isGuest = function () use ($feather) {
        if ($feather->user->is_guest) {
            throw new Error(__('No permission'), 403);
        }
    };
    $isAdmmod = function() use ($feather) {
        if(!$feather->user->is_admmod) {
            throw new Error(__('No permission'), 403);
        }
    };
    $feather->get('/:id(/:name)(/)', '\FeatherBB\Controller\Topic:display')->conditions(array('id' => '[0-9]+', 'page' => '[0-9]+'))->setName('Topic');
    $feather->get('/:id(/:name)(/page/:page)(/)', '\FeatherBB\Controller\Topic:display')->conditions(array('id' => '[0-9]+', 'page' => '[0-9]+'))->setName('TopicPaginate');
    $feather->get('/subscribe/:id(/:name)(/)', $isGuest, '\FeatherBB\Controller\Topic:subscribe')->conditions(array('id' => '[0-9]+'))->setName('subscribeTopic');
    $feather->get('/unsubscribe/:id(/:name)(/)', $isGuest, '\FeatherBB\Controller\Topic:unsubscribe')->conditions(array('id' => '[0-9]+'))->setName('unsubscribeTopic');
    $feather->get('/close/:id(/:name)(/)', $isAdmmod, '\FeatherBB\Controller\Topic:close')->conditions(array('id' => '[0-9]+'))->setName('closeTopic');
    $feather->get('/open/:id(/:name)(/)', $isAdmmod, '\FeatherBB\Controller\Topic:open')->conditions(array('id' => '[0-9]+'))->setName('openTopic');
    $feather->get('/stick/:id(/:name)(/)', $isAdmmod, '\FeatherBB\Controller\Topic:stick')->conditions(array('id' => '[0-9]+'))->setName('stickTopic');
    $feather->get('/unstick/:id(/:name)(/)', $isAdmmod, '\FeatherBB\Controller\Topic:unstick')->conditions(array('id' => '[0-9]+'))->setName('unstickTopic');
    $feather->map('/move/:id(/:name)/forum/:fid(/)', $isAdmmod, '\FeatherBB\Controller\Topic:move')->conditions(array('id' => '[0-9]+', 'fid' => '[0-9]+'))->via('GET', 'POST')->setName('moveTopic');
    $feather->map('/moderate/:id/forum/:fid(/page/:page)(/)', $isAdmmod, '\FeatherBB\Controller\Topic:moderate')->conditions(array('id' => '[0-9]+', 'fid' => '[0-9]+', 'page' => '[0-9]+'))->via('GET', 'POST')->setName('moderateTopic');
    $feather->get('/:id/action/:action(/)', '\FeatherBB\Controller\Topic:action')->conditions(array('id' => '[0-9]+'))->setName('topicAction');
});

// Post routes
$feather->group('/post', $canReadBoard, function() use ($feather) {
    $feather->get('/:pid(/)', '\FeatherBB\Controller\Topic:viewpost')->conditions(array('pid' => '[0-9]+'))->setName('viewPost');
    $feather->map('/new-topic/:fid(/)', '\FeatherBB\Controller\Post:newpost')->conditions(array('fid' => '[0-9]+'))->via('GET', 'POST')->setName('newTopic');
    $feather->map('/reply/:tid(/)', '\FeatherBB\Controller\Post:newreply')->conditions(array('tid' => '[0-9]+'))->via('GET', 'POST')->setName('newReply');
    $feather->map('/reply/:tid(/quote/:qid)(/)', '\FeatherBB\Controller\Post:newreply')->conditions(array('tid' => '[0-9]+', 'qid' => '[0-9]+'))->via('GET', 'POST')->setName('newQuoteReply');
    $feather->map('/delete/:id(/)', '\FeatherBB\Controller\Post:delete')->conditions(array('id' => '[0-9]+'))->via('GET', 'POST')->setName('deletePost');
    $feather->map('/edit/:id(/)', '\FeatherBB\Controller\Post:editpost')->conditions(array('id' => '[0-9]+'))->via('GET', 'POST')->setName('editPost');
    $feather->map('/report/:id(/)', '\FeatherBB\Controller\Post:report')->conditions(array('id' => '[0-9]+'))->via('GET', 'POST')->setName('report');
    $feather->get('/get-host/:pid(/)', '\FeatherBB\Controller\Post:gethost')->conditions(array('pid' => '[0-9]+'))->setName('getPostHost');
});

// Userlist
$feather->get('/userlist(/)', $canReadBoard, '\FeatherBB\Controller\Userlist:display')->setName('userList');

// Auth routes
$feather->group('/auth', function() use ($feather) {
    $feather->get('(/)', function () use ($feather) {
        if (!$feather->user->is_guest) {
            $feather->url->redirect($feather->urlFor('home'), 'Already logged');
        } else {
            $feather->redirect($feather->urlFor('login'));
        }
    });
    $feather->map(['GET', 'POST'], '/login(/)', '\FeatherBB\Controller\Auth:login')->setName('login');
    $feather->map(['GET', 'POST'], '/forget(/)', '\FeatherBB\Controller\Auth:forget')->setName('resetPassword');
    $feather->get('/logout/token/:token(/)', '\FeatherBB\Controller\Auth:logout')->setName('logout');
});

// Register routes
$feather->group('/register', function() use ($feather) {
    $feather->get('(/)', '\FeatherBB\Controller\Register:rules')->setName('registerRules');
    $feather->map(['GET', 'POST'], '/agree(/)', '\FeatherBB\Controller\Register:display')->setName('register');
    $feather->get('/cancel(/)', '\FeatherBB\Controller\Register:cancel')->setName('registerCancel');
});

// Search routes
$feather->group('/search', $canReadBoard, function() use ($feather) {
    $feather->get('(/)', '\FeatherBB\Controller\Search:display')->setName('search');
    $feather->get('/show/:show(/)', '\FeatherBB\Controller\Search:quicksearches')->setName('quickSearch');
});

// Help
$feather->get('/help(/)', $canReadBoard, '\FeatherBB\Controller\Help:display')->setName('help');

// Profile routes
$feather->group('/user', $isGuest, function() use ($feather) {
    $feather->get('/:id(/)', '\FeatherBB\Controller\Profile:display')->conditions(array('id' => '[0-9]+'))->setName('userProfile');
    $feather->map(['GET', 'POST'], '/:id(/section/:section)(/)', '\FeatherBB\Controller\Profile:display')->conditions(array('id' => '[0-9]+'))->setName('profileSection');
    $feather->map(['GET', 'POST'], '/:id(/action/:action)(/)', '\FeatherBB\Controller\Profile:action')->conditions(array('id' => '[0-9]+'))->setName('profileAction');
    $feather->map(['GET', 'POST'], '/email/:id(/)', '\FeatherBB\Controller\Profile:email')->conditions(array('id' => '[0-9]+'))->setName('email');
    $feather->get('/get-host/:ip(/)', '\FeatherBB\Controller\Profile:gethostip')->setName('getHostIp');
});

// Admin routes
$feather->group('/admin', $isAdmmod, function() use ($feather) {

    /**
     * Middleware to check if user is admin.
     */
    $isAdmin = function() use ($feather) {
        if($feather->user->g_id != $feather->forum_env['FEATHER_ADMIN']) {
            $feather->url->redirect($feather->urlFor('home'), __('No permission'));
        }
    };

    // Admin index
    $feather->get('(/action/:action)(/)', '\FeatherBB\Controller\Admin\Index:display')->setName('adminAction');
    $feather->get('/index(/)', '\FeatherBB\Controller\Admin\Index:display')->setName('adminIndex');

    // Admin bans
    $feather->group('/bans', function() use ($feather) {
        $feather->get('(/)', '\FeatherBB\Controller\Admin\Bans:display')->setName('adminBans');
        $feather->get('/delete/:id(/)', '\FeatherBB\Controller\Admin\Bans:delete')->conditions(array('id' => '[0-9]+'))->setName('deleteBan');
        $feather->map('/edit/:id(/)', '\FeatherBB\Controller\Admin\Bans:edit')->conditions(array('id' => '[0-9]+'))->via('GET', 'POST')->setName('editBan');
        $feather->map('/add(/:id)(/)', '\FeatherBB\Controller\Admin\Bans:add')->via('GET', 'POST')->setName('addBan');
    });

    // Admin options
    $feather->map('/options(/)', $isAdmin, '\FeatherBB\Controller\Admin\Options:display')->via('GET', 'POST')->setName('adminOptions');

    // Admin categories
    $feather->group('/categories', $isAdmin, function() use ($feather) {
        $feather->get('(/)', '\FeatherBB\Controller\Admin\Categories:display')->setName('adminCategories');
        $feather->post('/add(/)', '\FeatherBB\Controller\Admin\Categories:add')->setName('addCategory');
        $feather->post('/edit(/)', '\FeatherBB\Controller\Admin\Categories:edit')->setName('editCategory');
        $feather->post('/delete(/)', '\FeatherBB\Controller\Admin\Categories:delete')->setName('deleteCategory');
    });

    // Admin censoring
    $feather->map('/censoring(/)', $isAdmin, '\FeatherBB\Controller\Admin\Censoring:display')->via('GET', 'POST')->setName('adminCensoring');

    // Admin reports
    $feather->map('/reports(/)', '\FeatherBB\Controller\Admin\Reports:display')->via('GET', 'POST')->setName('adminReports');

    // Admin permissions
    $feather->map('/permissions(/)', $isAdmin, '\FeatherBB\Controller\Admin\Permissions:display')->via('GET', 'POST')->setName('adminPermissions');

    // Admin statistics
    $feather->get('/statistics(/)', '\FeatherBB\Controller\Admin\Statistics:display')->setName('statistics');
    $feather->get('/phpinfo(/)', '\FeatherBB\Controller\Admin\Statistics:phpinfo')->setName('phpInfo');

    // Admin forums
    $feather->group('/forums', $isAdmin, function() use ($feather) {
        $feather->map('(/)', '\FeatherBB\Controller\Admin\Forums:display')->via('GET', 'POST')->setName('adminForums');
        $feather->post('/add(/)', '\FeatherBB\Controller\Admin\Forums:add')->setName('addForum');
        $feather->map('/edit/:id(/)', '\FeatherBB\Controller\Admin\Forums:edit')->conditions(array('id' => '[0-9]+'))->via('GET', 'POST')->setName('editForum');
        $feather->map('/delete/:id(/)', '\FeatherBB\Controller\Admin\Forums:delete')->conditions(array('id' => '[0-9]+'))->via('GET', 'POST')->setName('deleteForum');
    });

    // Admin groups
    $feather->group('/groups', $isAdmin, function() use ($feather) {
        $feather->map('(/)', '\FeatherBB\Controller\Admin\Groups:display')->via('GET', 'POST')->setName('adminGroups');
        $feather->map('/add(/)', '\FeatherBB\Controller\Admin\Groups:addedit')->via('GET', 'POST')->setName('addGroup');
        $feather->map('/edit/:id(/)', '\FeatherBB\Controller\Admin\Groups:addedit')->conditions(array('id' => '[0-9]+'))->via('GET', 'POST')->setName('editGroup');
        $feather->map('/delete/:id(/)', '\FeatherBB\Controller\Admin\Groups:delete')->conditions(array('id' => '[0-9]+'))->via('GET', 'POST')->setName('deleteGroup');
    });

    // Admin plugins
    $feather->group('/plugins', function() use ($feather) {
        $feather->map('(/)', '\FeatherBB\Controller\Admin\Plugins:index')->via('GET', 'POST')->setName('adminPlugins');
        $feather->map('/info/:name(/)', '\FeatherBB\Controller\Admin\Plugins:info')->via('GET', 'POST')->conditions(array('name' => '[a-zA-Z\-]+'))->setName('infoPlugin');
        $feather->get('/activate/:name(/)', '\FeatherBB\Controller\Admin\Plugins:activate')->conditions(array('name' => '[a-zA-Z\-]+'))->setName('activatePlugin');
        $feather->get('/download/:name/:version(/)', '\FeatherBB\Controller\Admin\Plugins:download')->conditions(array('name' => '[a-zA-Z\-]+'))->setName('downloadPlugin');
        $feather->get('/deactivate/:name(/)', '\FeatherBB\Controller\Admin\Plugins:deactivate')->conditions(array('name' => '[a-zA-Z\-]+'))->setName('deactivatePlugin');
        $feather->get('/uninstall/:name(/)', '\FeatherBB\Controller\Admin\Plugins:uninstall')->conditions(array('name' => '[a-zA-Z\-]+'))->setName('uninstallPlugin');
    });

    // Admin maintenance
    $feather->map('/maintenance(/)', $isAdmin, '\FeatherBB\Controller\Admin\Maintenance:display')->via('GET', 'POST')->setName('adminMaintenance');

    // Admin parser
    $feather->map('/parser(/)', $isAdmin, '\FeatherBB\Controller\Admin\Parser:display')->via('GET', 'POST')->setName('adminParser');

    // Admin users
    $feather->group('/users', function() use ($feather) {
        $feather->map('(/)', '\FeatherBB\Controller\Admin\Users:display')->via('GET', 'POST')->setName('adminUsers');
        $feather->get('/ip-stats/id/:id(/)', '\FeatherBB\Controller\Admin\Users:ipstats')->conditions(array('id' => '[0-9]+'))->setName('usersIpStats');
        $feather->get('/show-users/ip/:ip(/)', '\FeatherBB\Controller\Admin\Users:showusers')->setName('usersIpShow');
    });

});

// Override the default Not Found Handler
Container::set('notFoundHandler', function ($c) {
    return function ($request, $response) use ($c) {
        // throw new Error('Page not found', 404); // TODO : translation
        return $c['response']
            ->withStatus(404)
            ->withHeader('Content-Type', 'text/html')
            ->write('Page not found');
    };
});

Container::set('errorHandler', function ($c) {
    return function ($request, $response, $exception) use ($c) {
        return $c['response']->withStatus(500)
                         ->withHeader('Content-Type', 'text/html')
                         ->write('Something went wrong!');
    };
});
// $feather->error(function (\Exception $e) use ($feather) {
//     $error = array(
//         'code' => $e->getCode(),
//         'message' => $e->getMessage(),
//         'back' => true,
//     );
//
//     // Hide internal mechanism
//     if (!in_array(get_class($e), array('FeatherBB\Core\Error'))) {
//         $error['message'] = 'There was an internal error'; // TODO : translation
//     }
//
//     if (method_exists($e, 'hasBacklink')) {
//         $error['back'] = $e->hasBacklink();
//     }
//
//     $feather->response->setStatus($e->getCode());
//     $feather->response->setBody(''); // Reset buffer
//     $feather->template->setPageInfo(array(
//         'title' => array(\FeatherBB\Core\Utils::escape($feather->forum_settings['o_board_title']), __('Error')),
//         'msg'    =>    $error['message'],
//         'backlink'    => $error['back'],
//     ))->addTemplate('error.php')->display();
//     $feather->stop();
// });
