<?php

/**
 * Copyright (C) 2015-2016 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

use \FeatherBB\Middleware\Logged as IsLogged;
use \FeatherBB\Middleware\ReadBoard as CanReadBoard;
use \FeatherBB\Middleware\Admin as IsAdmin;
use \FeatherBB\Middleware\AdminModo as IsAdmMod;
use \FeatherBB\Middleware\JsonHeader;
use FeatherBB\Core\Error;


Route::map(['GET', 'POST'], '/install', '\FeatherBB\Controller\Install:run')->setName('install');

// Index
Route::get('/', '\FeatherBB\Controller\Index:display')->add(new CanReadBoard)->setName('home');
Route::get('/rules', '\FeatherBB\Controller\Index:rules')->setName('rules');
Route::get('/mark-read', '\FeatherBB\Controller\Index:markread')->add(new IsLogged)->setName('markRead');

// Forum
Route::group('/forum', function() {
    Route::get('/{id:[0-9]+}[/{name:[\w\-]+}]', '\FeatherBB\Controller\Forum:display')->setName('Forum');
    Route::get('/{id:[0-9]+}/{name:[\w\-]+}/page/{page:[0-9]+}', '\FeatherBB\Controller\Forum:display')->setName('ForumPaginate');
    Route::get('/mark-read/{id:[0-9]+}[/{name:[\w\-]+}]', '\FeatherBB\Controller\Forum:markread')->add(new IsLogged)->setName('markForumRead');
    Route::get('/subscribe/{id:[0-9]+}[/{name:[\w\-]+}]', '\FeatherBB\Controller\Forum:subscribe')->add(new IsLogged)->setName('subscribeForum');
    Route::get('/unsubscribe/{id:[0-9]+}[/{name:[\w\-]+}]', '\FeatherBB\Controller\Forum:unsubscribe')->add(new IsLogged)->setName('unsubscribeForum');
    Route::get('/moderate/{fid:[0-9]+}/page/{page:[0-9]+}', '\FeatherBB\Controller\Forum:moderate')->setName('moderateForum');
    Route::post('/moderate/{fid:[0-9]+}[/page/{page:[0-9]+}]', '\FeatherBB\Controller\Forum:dealposts')->setName('dealPosts');
})->add(new CanReadBoard);

// Topic
Route::group('/topic', function() {
    Route::get('/{id:[0-9]+}[/{name:[\w\-]+}]', '\FeatherBB\Controller\Topic:display')->setName('Topic');
    Route::get('/{id:[0-9]+}/{name:[\w\-]+}/page/{page:[0-9]+}', '\FeatherBB\Controller\Topic:display')->setName('TopicPaginate');
    Route::get('/{id:[0-9]+}/action/{action:[\w\-]+}', '\FeatherBB\Controller\Topic:action')->setName('topicAction');
    Route::get('/subscribe/{id:[0-9]+}[/{name:[\w\-]+}]', '\FeatherBB\Controller\Topic:subscribe')->add(new IsLogged)->setName('subscribeTopic');
    Route::get('/unsubscribe/{id:[0-9]+}[/{name:[\w\-]+}]', '\FeatherBB\Controller\Topic:unsubscribe')->add(new IsLogged)->setName('unsubscribeTopic');
    Route::get('/close/{id:[0-9]+}[/{name:[\w\-]+}]', '\FeatherBB\Controller\Topic:close')->add(new IsAdmMod)->setName('closeTopic');
    Route::get('/open/{id:[0-9]+}[/{name:[\w\-]+}]', '\FeatherBB\Controller\Topic:open')->add(new IsAdmMod)->setName('openTopic');
    Route::get('/stick/{id:[0-9]+}[/{name:[\w\-]+}]', '\FeatherBB\Controller\Topic:stick')->add(new IsAdmMod)->setName('stickTopic');
    Route::get('/unstick/{id:[0-9]+}[/{name:[\w\-]+}]', '\FeatherBB\Controller\Topic:unstick')->add(new IsAdmMod)->setName('unstickTopic');
    Route::map(['GET', 'POST'], '/move/{id:[0-9]+}[/{name:[\w\-]+}/forum/{fid:[0-9]+}]', '\FeatherBB\Controller\Topic:move')->add(new IsAdmMod)->setName('moveTopic');
    Route::map(['GET', 'POST'], '/moderate/{id:[0-9]+}/forum/{fid:[0-9]+}[/page/{page:[0-9]+}]', '\FeatherBB\Controller\Topic:moderate')->add(new IsAdmMod)->setName('moderateTopic');
    Route::get('/{id:[0-9]+}/action/{action}', '\FeatherBB\Controller\Topic{action}')->setName('topicAction');
})->add(new CanReadBoard);

// Post routes
Route::group('/post', function() {
    Route::get('/{pid:[0-9]+}', '\FeatherBB\Controller\Topic:viewpost')->setName('viewPost');
    Route::map(['GET', 'POST'], '/new-topic/{fid:[0-9]+}', '\FeatherBB\Controller\Post:newpost')->setName('newTopic');
    Route::map(['GET', 'POST'], '/reply/{tid:[0-9]+}', '\FeatherBB\Controller\Post:newreply')->setName('newReply');
    Route::map(['GET', 'POST'], '/reply/{tid:[0-9]+}/quote/{qid:[0-9]+}', '\FeatherBB\Controller\Post:newreply')->setName('newQuoteReply');
    Route::map(['GET', 'POST'], '/delete/{id:[0-9]+}', '\FeatherBB\Controller\Post:delete')->setName('deletePost');
    Route::map(['GET', 'POST'], '/edit/{id:[0-9]+}', '\FeatherBB\Controller\Post:editpost')->setName('editPost');
    Route::map(['GET', 'POST'], '/report/{id:[0-9]+}', '\FeatherBB\Controller\Post:report')->setName('report');
    Route::get('/get-host/{pid:[0-9]+}', '\FeatherBB\Controller\Post:gethost')->setName('getPostHost');
})->add(new CanReadBoard);

// Userlist
Route::get('/userlist', '\FeatherBB\Controller\Userlist:display')->add(new CanReadBoard)->setName('userList');

// Auth routes
Route::group('/auth', function() {
    Route::map(['GET', 'POST'], '', '\FeatherBB\Controller\Auth:login')->setName('login');
    Route::map(['GET', 'POST'], '/forget', '\FeatherBB\Controller\Auth:forget')->setName('resetPassword');
    Route::get('/logout/token/{token}', '\FeatherBB\Controller\Auth:logout')->setName('logout');
});

// Register routes
Route::group('/register', function() {
    Route::get('', '\FeatherBB\Controller\Register:rules')->setName('registerRules');
    Route::map(['GET', 'POST'], '/agree', '\FeatherBB\Controller\Register:display')->setName('register');
    Route::get('/cancel', '\FeatherBB\Controller\Register:cancel')->setName('registerCancel');
});

// Search routes
Route::group('/search', function() {
    Route::get('', '\FeatherBB\Controller\Search:display')->setName('search');
    Route::get('/show/{show}', '\FeatherBB\Controller\Search:quicksearches')->setName('quickSearch');
})->add(new CanReadBoard);

// Help
Route::get('/help', '\FeatherBB\Controller\Help:display')->add(new CanReadBoard)->setName('help');

// Profile routes
Route::group('/user', function() {
    Route::map(['GET', 'POST'], '/{id:[0-9]+}', '\FeatherBB\Controller\Profile:display')->setName('userProfile');
    Route::map(['GET', 'POST'], '/{id:[0-9]+}/section/{section}', '\FeatherBB\Controller\Profile:display')->setName('profileSection');
    Route::map(['GET', 'POST'], '/{id:[0-9]+}/action/{action}', '\FeatherBB\Controller\Profile:action')->setName('profileAction');
    Route::map(['GET', 'POST'], '/email/{id:[0-9]+}', '\FeatherBB\Controller\Profile:email')->setName('email');
    Route::get('/get-host/{ip}', '\FeatherBB\Controller\Profile:gethostip')->setName('getHostIp');
})->add(new IsLogged);


// Admin routes

Route::group('/admin', function() {

    // Admin index
    Route::get('[/action/{action}]', '\FeatherBB\Controller\Admin\Index:display')->setName('adminAction');
    Route::get('/index', '\FeatherBB\Controller\Admin\Index:display')->setName('adminIndex');

    // Admin updates
    Route::get('/updates', '\FeatherBB\Controller\Admin\Updates:display')->setName('adminUpdates');
    Route::post('/updates/upgrade-core', '\FeatherBB\Controller\Admin\Updates:upgradeCore')->setName('adminUpgradeCore');
    Route::post('/updates/upgrade-plugins', '\FeatherBB\Controller\Admin\Updates:upgradePlugins')->setName('adminUpgradePlugins');
    Route::post('/updates/upgrade-themes', '\FeatherBB\Controller\Admin\Updates:upgradeThemes')->setName('adminUpgradeThemes');

    // Admin bans
    Route::group('/bans', function() {
        Route::get('', '\FeatherBB\Controller\Admin\Bans:display')->setName('adminBans');
        Route::get('/delete/{id:[0-9]+}', '\FeatherBB\Controller\Admin\Bans:delete')->setName('deleteBan');
        Route::map(['GET', 'POST'], '/edit/{id:[0-9]+}', '\FeatherBB\Controller\Admin\Bans:edit')->setName('editBan');
        Route::map(['GET', 'POST'], '/add[/{id:[0-9]+}]', '\FeatherBB\Controller\Admin\Bans:add')->setName('addBan');
    });

    // Admin options
    Route::map(['GET', 'POST'], '/options', '\FeatherBB\Controller\Admin\Options:display')->add(new IsAdmin)->setName('adminOptions');

    // Admin categories
    Route::group('/categories', function() {
        Route::get('', '\FeatherBB\Controller\Admin\Categories:display')->setName('adminCategories');
        Route::post('/add', '\FeatherBB\Controller\Admin\Categories:add')->setName('addCategory');
        Route::post('/edit', '\FeatherBB\Controller\Admin\Categories:edit')->setName('editCategory');
        Route::post('/delete', '\FeatherBB\Controller\Admin\Categories:delete')->setName('deleteCategory');
    })->add(new IsAdmin);

    // Admin censoring
    Route::map(['GET', 'POST'], '/censoring', '\FeatherBB\Controller\Admin\Censoring:display')->add(new IsAdmin)->setName('adminCensoring');

    // Admin reports
    Route::map(['GET', 'POST'], '/reports', '\FeatherBB\Controller\Admin\Reports:display')->setName('adminReports');

    // Admin permissions
    Route::map(['GET', 'POST'], '/permissions', '\FeatherBB\Controller\Admin\Permissions:display')->add(new IsAdmin)->setName('adminPermissions');

    // Admin statistics
    Route::get('/statistics', '\FeatherBB\Controller\Admin\Statistics:display')->setName('statistics');
    Route::get('/phpinfo', '\FeatherBB\Controller\Admin\Statistics:phpinfo')->setName('phpInfo');

    // Admin forums
    Route::group('/forums', function() {
        Route::map(['GET', 'POST'], '', '\FeatherBB\Controller\Admin\Forums:display')->setName('adminForums');
        Route::post('/add', '\FeatherBB\Controller\Admin\Forums:add')->setName('addForum');
        Route::map(['GET', 'POST'], '/edit/{id:[0-9]+}', '\FeatherBB\Controller\Admin\Forums:edit')->setName('editForum');
        Route::map(['GET', 'POST'], '/delete/{id:[0-9]+}', '\FeatherBB\Controller\Admin\Forums:delete')->setName('deleteForum');
    })->add(new IsAdmin);

    // Admin groups
    Route::group('/groups', function() {
        Route::map(['GET', 'POST'], '', '\FeatherBB\Controller\Admin\Groups:display')->setName('adminGroups');
        Route::map(['GET', 'POST'], '/add', '\FeatherBB\Controller\Admin\Groups:addedit')->setName('addGroup');
        Route::map(['GET', 'POST'], '/edit/{id:[0-9]+}', '\FeatherBB\Controller\Admin\Groups:addedit')->setName('editGroup');
        Route::map(['GET', 'POST'], '/delete/{id:[0-9]+}', '\FeatherBB\Controller\Admin\Groups:delete')->setName('deleteGroup');
    })->add(new IsAdmin);

    // Admin plugins
    Route::group('/plugins', function() {
        Route::map(['GET', 'POST'], '', '\FeatherBB\Controller\Admin\Plugins:index')->setName('adminPlugins');
        Route::map(['GET', 'POST'], '/info/{name:[\w\-]+}', '\FeatherBB\Controller\Admin\Plugins:info')->setName('infoPlugin');
        Route::get('/activate/{name:[\w\-]+}', '\FeatherBB\Controller\Admin\Plugins:activate')->setName('activatePlugin');
        Route::get('/download/{name:[\w\-]+}/{version}', '\FeatherBB\Controller\Admin\Plugins:download')->setName('downloadPlugin');
        Route::get('/deactivate/{name:[\w\-]+}', '\FeatherBB\Controller\Admin\Plugins:deactivate')->setName('deactivatePlugin');
        Route::get('/uninstall/{name:[\w\-]+}', '\FeatherBB\Controller\Admin\Plugins:uninstall')->setName('uninstallPlugin');
    });

    // Admin maintenance
    Route::map(['GET', 'POST'], '/maintenance', '\FeatherBB\Controller\Admin\Maintenance:display')->add(new IsAdmin)->setName('adminMaintenance');

    // Admin parser
    Route::map(['GET', 'POST'], '/parser', '\FeatherBB\Controller\Admin\Parser:display')->add(new IsAdmin)->setName('adminParser');

    // Admin users
    Route::group('/users', function() {
        Route::map(['GET', 'POST'], '', '\FeatherBB\Controller\Admin\Users:display')->setName('adminUsers');
        Route::get('/ip-stats/id/{id:[0-9]+}', '\FeatherBB\Controller\Admin\Users:ipstats')->setName('usersIpStats');
        Route::get('/show-users', '\FeatherBB\Controller\Admin\Users:showusers')->setName('usersIpShow');
    });

})->add(new IsAdmMod);

// API
Route::group('/api', function() {
    Route::get('/user/{id:[0-9]+}', '\FeatherBB\Controller\Api\User:display')->setName('userApi');
    Route::get('/forum/{id:[0-9]+}', '\FeatherBB\Controller\Api\Forum:display')->setName('forumApi');
    Route::get('/topic/{id:[0-9]+}', '\FeatherBB\Controller\Api\Topic:display')->setName('topicApi');
    Route::post('/new/topic/forum-id/{id:[0-9]+}', '\FeatherBB\Controller\Api\Topic:newTopic')->setName('newTopicApi');
    Route::get('/post/{id:[0-9]+}', '\FeatherBB\Controller\Api\Post:display')->setName('postApi');
})->add(new JsonHeader);

// Override the default Not Found Handler
Container::set('notFoundHandler', function ($c) {
    return function ($request, $response) use ($c) {
        throw new Error('Page not found', 404); // TODO : translation
    };
});

Container::set('errorHandler', function ($c) {
    return function ($request, $response, $e) use ($c) {
        $error = array(
            'code' => $e->getCode(),
            'message' => $e->getMessage(),
            'back' => true,
            'html' => false,
        );

        // Hide internal mechanism
        if (!in_array(get_class($e), array('FeatherBB\Core\Error')) && ForumEnv::get('FEATHER_DEBUG') != 'all') {
            $error['message'] = 'There was an internal error'; // TODO : translation
        }

        if (method_exists($e, 'hasBacklink')) {
            $error['back'] = $e->hasBacklink();
        }

        if (method_exists($e, 'displayHtml')) {
            $error['html'] = $e->displayHtml();
        }

        return View::setPageInfo(array(
            'title' => array(\FeatherBB\Core\Utils::escape(ForumSettings::get('o_board_title')), __('Error')),
            'msg'    =>    $error['message'],
            'backlink'    => $error['back'],
            'html'    => $error['html'],
        ))->addTemplate('error.php')->display();
    };
});
