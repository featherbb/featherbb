<?php

/**
 * Copyright (C) 2015 FeatherBB
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
    if(!$feather->user->is_admmod) {
        throw new Error(__('No permission'), 403);
    }
};

// Index
$feather->get('/', $canReadBoard, '\FeatherBB\Controller\Index:display')->name('home');
$feather->get('/rules(/)', '\FeatherBB\Controller\Index:rules')->name('rules');
$feather->get('/mark-read(/)', $isGuest, '\FeatherBB\Controller\Index:markread')->name('markRead');

// Forum
$feather->group('/forum', $canReadBoard, function() use ($feather) {
    $isGuest = function () use ($feather) {
        if ($feather->user->is_guest) {
            throw new Error(__('No permission'), 403);
        }
    };
    $feather->get('/:id(/:name)(/)', '\FeatherBB\Controller\Forum:display')->conditions(array('id' => '[0-9]+', 'page' => '[0-9]+'))->name('Forum');
    $feather->get('/:id(/:name)(/page/:page)(/)', '\FeatherBB\Controller\Forum:display')->conditions(array('id' => '[0-9]+', 'page' => '[0-9]+'))->name('ForumPaginate');
    $feather->get('/mark-read/:id(/:name)(/)', $isGuest, '\FeatherBB\Controller\Forum:markread')->conditions(array('id' => '[0-9]+'))->name('markForumRead');
    $feather->get('/subscribe/:id(/:name)(/)', $isGuest, '\FeatherBB\Controller\Forum:subscribe')->conditions(array('id' => '[0-9]+'))->name('subscribeForum');
    $feather->get('/unsubscribe/:id(/:name)(/)', $isGuest, '\FeatherBB\Controller\Forum:unsubscribe')->conditions(array('id' => '[0-9]+'))->name('unsubscribeForum');
    $feather->get('/moderate/:fid/page/:page(/)', '\FeatherBB\Controller\Forum:moderate')->conditions(array('id' => '[0-9]+', 'page' => '[0-9]+'))->name('moderateForum');
    $feather->post('/moderate/:fid(/page/:page)(/)', '\FeatherBB\Controller\Forum:dealposts')->conditions(array('fid' => '[0-9]+', 'page' => '[0-9]+'))->name('dealPosts');
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
    $feather->get('/:id(/:name)(/)', '\FeatherBB\Controller\Topic:display')->conditions(array('id' => '[0-9]+', 'page' => '[0-9]+'))->name('Topic');
    $feather->get('/:id(/:name)(/page/:page)(/)', '\FeatherBB\Controller\Topic:display')->conditions(array('id' => '[0-9]+', 'page' => '[0-9]+'))->name('TopicPaginate');
    $feather->get('/subscribe/:id(/:name)(/)', $isGuest, '\FeatherBB\Controller\Topic:subscribe')->conditions(array('id' => '[0-9]+'))->name('subscribeTopic');
    $feather->get('/unsubscribe/:id(/:name)(/)', $isGuest, '\FeatherBB\Controller\Topic:unsubscribe')->conditions(array('id' => '[0-9]+'))->name('unsubscribeTopic');
    $feather->get('/close/:id(/:name)(/)', $isAdmmod, '\FeatherBB\Controller\Topic:close')->conditions(array('id' => '[0-9]+'))->name('closeTopic');
    $feather->get('/open/:id(/:name)(/)', $isAdmmod, '\FeatherBB\Controller\Topic:open')->conditions(array('id' => '[0-9]+'))->name('openTopic');
    $feather->get('/stick/:id(/:name)(/)', $isAdmmod, '\FeatherBB\Controller\Topic:stick')->conditions(array('id' => '[0-9]+'))->name('stickTopic');
    $feather->get('/unstick/:id(/:name)(/)', $isAdmmod, '\FeatherBB\Controller\Topic:unstick')->conditions(array('id' => '[0-9]+'))->name('unstickTopic');
    $feather->map('/move/:id(/:name)/forum/:fid(/)', $isAdmmod, '\FeatherBB\Controller\Topic:move')->conditions(array('id' => '[0-9]+', 'fid' => '[0-9]+'))->via('GET', 'POST')->name('moveTopic');
    $feather->map('/moderate/:id/forum/:fid(/page/:page)(/)', $isAdmmod, '\FeatherBB\Controller\Topic:moderate')->conditions(array('id' => '[0-9]+', 'fid' => '[0-9]+', 'page' => '[0-9]+'))->via('GET', 'POST')->name('moderateTopic');
    $feather->get('/:id/action/:action(/)', '\FeatherBB\Controller\Topic:action')->conditions(array('id' => '[0-9]+'))->name('topicAction');
});

// Post routes
$feather->group('/post', $canReadBoard, function() use ($feather) {
    $feather->get('/:pid(/)', '\FeatherBB\Controller\Topic:viewpost')->conditions(array('pid' => '[0-9]+'))->name('viewPost');
    $feather->map('/new-topic/:fid(/)', '\FeatherBB\Controller\Post:newpost')->conditions(array('fid' => '[0-9]+'))->via('GET', 'POST')->name('newTopic');
    $feather->map('/reply/:tid(/)', '\FeatherBB\Controller\Post:newreply')->conditions(array('tid' => '[0-9]+'))->via('GET', 'POST')->name('newReply');
    $feather->map('/reply/:tid(/)(/quote/:qid)(/)', '\FeatherBB\Controller\Post:newreply')->conditions(array('tid' => '[0-9]+', 'qid' => '[0-9]+'))->via('GET', 'POST')->name('newQuoteReply');
    $feather->map('/delete/:id(/)', '\FeatherBB\Controller\Post:delete')->conditions(array('id' => '[0-9]+'))->via('GET', 'POST')->name('deletePost');
    $feather->map('/edit/:id(/)', '\FeatherBB\Controller\Post:editpost')->conditions(array('id' => '[0-9]+'))->via('GET', 'POST')->name('editPost');
    $feather->map('/report/:id(/)', '\FeatherBB\Controller\Post:report')->conditions(array('id' => '[0-9]+'))->via('GET', 'POST')->name('report');
    $feather->get('/get-host/:pid(/)', '\FeatherBB\Controller\Post:gethost')->conditions(array('pid' => '[0-9]+'))->name('getPostHost');
});

// Userlist
$feather->get('/userlist(/)', $canReadBoard, '\FeatherBB\Controller\Userlist:display')->name('userList');

// Auth routes
$feather->group('/auth', function() use ($feather) {
    $feather->get('(/)', function () use ($feather) {
        if (!$feather->user->is_guest) {
            $feather->url->redirect($feather->urlFor('home'), 'Already logged');
        } else {
            $feather->redirect($feather->urlFor('login'));
        }
    });
    $feather->map('/login(/)', '\FeatherBB\Controller\Auth:login')->via('GET', 'POST')->name('login');
    $feather->map('/forget(/)', '\FeatherBB\Controller\Auth:forget')->via('GET', 'POST')->name('resetPassword');
    $feather->get('/logout/token/:token(/)', '\FeatherBB\Controller\Auth:logout')->name('logout');
});

// Register routes
$feather->group('/register', function() use ($feather) {
    $feather->get('(/)', '\FeatherBB\Controller\Register:rules')->name('registerRules');
    $feather->map('/agree(/)', '\FeatherBB\Controller\Register:display')->via('GET', 'POST')->name('register');
    $feather->get('/cancel(/)', '\FeatherBB\Controller\Register:cancel')->name('registerCancel');
});

// Search routes
$feather->group('/search', $canReadBoard, function() use ($feather) {
    $feather->get('(/)', '\FeatherBB\Controller\Search:display')->name('search');
    $feather->get('/show/:show(/)', '\FeatherBB\Controller\Search:quicksearches')->name('quickSearch');
});

// Help
$feather->get('/help(/)', $canReadBoard, '\FeatherBB\Controller\Help:display')->name('help');

// Profile routes
$feather->group('/user', $isGuest, function() use ($feather) {
    $feather->get('/:id(/)', '\FeatherBB\Controller\Profile:display')->conditions(array('id' => '[0-9]+'))->name('userProfile');
    $feather->map('/:id(/section/:section)(/)', '\FeatherBB\Controller\Profile:display')->conditions(array('id' => '[0-9]+'))->via('GET', 'POST')->name('profileSection');
    $feather->map('/:id(/action/:action)(/)', '\FeatherBB\Controller\Profile:action')->conditions(array('id' => '[0-9]+'))->via('GET', 'POST')->name('profileAction');
    $feather->map('/email/:id(/)', '\FeatherBB\Controller\Profile:email')->conditions(array('id' => '[0-9]+'))->via('GET', 'POST')->name('email');
    $feather->get('/get-host/:ip(/)', '\FeatherBB\Controller\Profile:gethostip')->name('getHostIp');
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
    $feather->get('(/action/:action)(/)', '\FeatherBB\Controller\Admin\Index:display')->name('adminAction');
    $feather->get('/index(/)', '\FeatherBB\Controller\Admin\Index:display')->name('adminIndex');

    // Admin bans
    $feather->group('/bans', function() use ($feather) {
        $feather->get('(/)', '\FeatherBB\Controller\Admin\Bans:display')->name('adminBans');
        $feather->get('/delete/:id(/)', '\FeatherBB\Controller\Admin\Bans:delete')->conditions(array('id' => '[0-9]+'))->name('deleteBan');
        $feather->map('/edit/:id(/)', '\FeatherBB\Controller\Admin\Bans:edit')->conditions(array('id' => '[0-9]+'))->via('GET', 'POST')->name('editBan');
        $feather->map('/add(/:id)(/)', '\FeatherBB\Controller\Admin\Bans:add')->via('GET', 'POST')->name('addBan');
    });

    // Admin options
    $feather->map('/options(/)', $isAdmin, '\FeatherBB\Controller\Admin\Options:display')->via('GET', 'POST')->name('adminOptions');

    // Admin categories
    $feather->group('/categories', $isAdmin, function() use ($feather) {
        $feather->get('(/)', '\FeatherBB\Controller\Admin\Categories:display')->name('adminCategories');
        $feather->post('/add(/)', '\FeatherBB\Controller\Admin\Categories:add_category')->name('addCategory');
        $feather->post('/edit(/)', '\FeatherBB\Controller\Admin\Categories:edit_categories')->name('editCategory');
        $feather->post('/delete(/)', '\FeatherBB\Controller\Admin\Categories:delete_category')->name('deleteCategory');
    });

    // Admin censoring
    $feather->map('/censoring(/)', $isAdmin, '\FeatherBB\Controller\Admin\Censoring:display')->via('GET', 'POST')->name('adminCensoring');

    // Admin reports
    $feather->map('/reports(/)', '\FeatherBB\Controller\Admin\Reports:display')->via('GET', 'POST')->name('adminReports');

    // Admin permissions
    $feather->map('/permissions(/)', $isAdmin, '\FeatherBB\Controller\Admin\Permissions:display')->via('GET', 'POST')->name('adminPermissions');

    // Admin statistics
    $feather->get('/statistics(/)', '\FeatherBB\Controller\Admin\Statistics:display')->name('statistics');
    $feather->get('/phpinfo(/)', '\FeatherBB\Controller\Admin\Statistics:phpinfo')->name('phpInfo');

    // Admin forums
    $feather->group('/forums', $isAdmin, function() use ($feather) {
        $feather->map('(/)', '\FeatherBB\Controller\Admin\Forums:display')->via('GET', 'POST')->name('adminForums');
        $feather->post('/add(/)', '\FeatherBB\Controller\Admin\Forums:add_forum')->name('addForum');
        $feather->map('/edit/:id(/)', '\FeatherBB\Controller\Admin\Forums:edit_forum')->conditions(array('id' => '[0-9]+'))->via('GET', 'POST')->name('editForum');
        $feather->map('/delete/:id(/)', '\FeatherBB\Controller\Admin\Forums:delete_forum')->conditions(array('id' => '[0-9]+'))->via('GET', 'POST')->name('deleteForum');
    });

    // Admin groups
    $feather->group('/groups', $isAdmin, function() use ($feather) {
        $feather->map('(/)', '\FeatherBB\Controller\Admin\Groups:display')->via('GET', 'POST')->name('adminGroups');
        $feather->map('/add(/)', '\FeatherBB\Controller\Admin\Groups:addedit')->via('GET', 'POST')->name('addGroup');
        $feather->map('/edit/:id(/)', '\FeatherBB\Controller\Admin\Groups:addedit')->conditions(array('id' => '[0-9]+'))->via('GET', 'POST')->name('editGroup');
        $feather->map('/delete/:id(/)', '\FeatherBB\Controller\Admin\Groups:delete')->conditions(array('id' => '[0-9]+'))->via('GET', 'POST')->name('deleteGroup');
    });

    // Admin plugins
    $feather->group('/plugins', function() use ($feather) {
        $feather->map('(/)', '\FeatherBB\Controller\Admin\Plugins:index')->via('GET', 'POST')->name('adminPlugins');
        $feather->map('/:name(/)', '\FeatherBB\Controller\Admin\Plugins:index')->via('GET', 'POST')->name('infoPlugin');
        $feather->get('/activate/:name(/)', '\FeatherBB\Controller\Admin\Plugins:activate')->conditions(array('name' => '[a-zA-Z\-]+'))->name('activatePlugin');
        $feather->get('/deactivate/:name(/)', '\FeatherBB\Controller\Admin\Plugins:deactivate')->conditions(array('name' => '[a-zA-Z\-]+'))->name('deactivatePlugin');
    });

    // Admin maintenance
    $feather->map('/maintenance(/)', $isAdmin, '\FeatherBB\Controller\Admin\Maintenance:display')->via('GET', 'POST')->name('adminMaintenance');

    // Admin parser
    $feather->map('/parser(/)', $isAdmin, '\FeatherBB\Controller\Admin\Parser:display')->via('GET', 'POST')->name('adminParser');

    // Admin users
    $feather->group('/users', function() use ($feather) {
        $feather->map('(/)', '\FeatherBB\Controller\Admin\Users:display')->via('GET', 'POST')->name('adminUsers');
        $feather->get('/ip-stats/id/:id(/)', '\FeatherBB\Controller\Admin\Users:ipstats')->conditions(array('id' => '[0-9]+'))->name('usersIpStats');
        $feather->get('/show-users/ip/:ip(/)', '\FeatherBB\Controller\Admin\Users:showusers')->name('usersIpShow');
    });

});

// 404 not found
$feather->notFound(function () use ($feather){
    throw new Error('Page not found', 404); // TODO : translation
});

$feather->error(function (\Exception $e) use ($feather) {
    $error = array(
        'code' => $e->getCode(),
        'message' => $e->getMessage(),
        'back' => true,
    );

    // Hide internal mechanism
    if (!in_array(get_class($e), array('FeatherBB\Core\Error'))) {
        $error['message'] = 'There was an internal error'; // TODO : translation
    }

    if (method_exists($e, 'hasBacklink')) {
        $error['back'] = $e->hasBacklink();
    }

    $feather->response->setStatus($e->getCode());
    $feather->response->setBody(''); // Reset buffer
    $feather->template->setPageInfo(array(
        'title' => array(\FeatherBB\Core\Utils::escape($feather->forum_settings['o_board_title']), __('Error')),
        'msg'    =>    $error['message'],
        'backlink'    => $error['back'],
    ))->addTemplate('error.php')->display();
    $feather->stop();
});
