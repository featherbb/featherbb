<?php

/**
 * Copyright (C) 2015-2017 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

use FeatherBB\Core\Error;
use FeatherBB\Core\Interfaces\Container;
use FeatherBB\Core\Interfaces\ForumEnv;
use FeatherBB\Core\Interfaces\ForumSettings;
use FeatherBB\Core\Interfaces\Route;
use FeatherBB\Core\Interfaces\View;
use FeatherBB\Middleware\Admin as IsAdmin;
use FeatherBB\Middleware\AdminMod as IsAdmMod;
use FeatherBB\Middleware\JsonHeader;
use FeatherBB\Middleware\Logged as IsLogged;
use FeatherBB\Middleware\ModeratePermission;
use FeatherBB\Middleware\ReadBoard as CanReadBoard;

Route::map(['GET', 'POST'], '/install', '\FeatherBB\Controller\Install:run')->setName('install');

// Index
Route::get('/', '\FeatherBB\Controller\Index:display')->add(new CanReadBoard)->setName('home');
Route::get('/rules', '\FeatherBB\Controller\Index:rules')->setName('rules');
Route::get('/mark-read', '\FeatherBB\Controller\Index:markread')->add(new IsLogged)->setName('markRead');

// Forum
Route::group('/forum', function () {
    Route::get('/{id:\d+}/{name:[\w\-]+}[/page/{page:\d+}]', '\FeatherBB\Controller\Forum:display')->setName('Forum');
    Route::get('/{id:\d+}/{name:[\w\-]+}/mark-read', '\FeatherBB\Controller\Forum:markread')->add(new IsLogged)->setName('markForumRead');
    Route::get('/{id:\d+}/{name:[\w\-]+}/subscribe', '\FeatherBB\Controller\Forum:subscribe')->add(new IsLogged)->setName('subscribeForum');
    Route::get('/{id:\d+}/{name:[\w\-]+}/unsubscribe', '\FeatherBB\Controller\Forum:unsubscribe')->add(new IsLogged)->setName('unsubscribeForum');
    Route::get('/{id:\d+}/{name:[\w\-]+}/moderate/page/{page:\d+}', '\FeatherBB\Controller\Forum:moderate')->setName('moderateForum');
    Route::post('/{id:\d+}/{name:[\w\-]+}/moderate[/page/{page:\d+}]', '\FeatherBB\Controller\Forum:dealposts')->setName('dealPosts');
})->add(new CanReadBoard);

// Topic
Route::group('/topic', function () {
    Route::get('/{id:\d+}/{name:[\w\-]+}[/page/{page:\d+}]', '\FeatherBB\Controller\Topic:display')->setName('Topic');
    Route::get('/{id:\d+}/{name:[\w\-]+}/post/{pid:\d+}', '\FeatherBB\Controller\Topic:viewpost')->setName('viewPost');
    Route::get('/{id:\d+}/{name:[\w\-]+}/action/{action:[\w\-]+}', '\FeatherBB\Controller\Topic:action')->setName('topicAction');
    Route::get('/{id:\d+}/{name:[\w\-]+}/subscribe', '\FeatherBB\Controller\Topic:subscribe')->add(new IsLogged)->setName('subscribeTopic');
    Route::get('/{id:\d+}/{name:[\w\-]+}/unsubscribe', '\FeatherBB\Controller\Topic:unsubscribe')->add(new IsLogged)->setName('unsubscribeTopic');
    Route::get('/{id:\d+}/{name:[\w\-]+}/close', '\FeatherBB\Controller\Topic:close')->add(new IsAdmMod)->add(new ModeratePermission)->setName('closeTopic');
    Route::get('/{id:\d+}/{name:[\w\-]+}/open', '\FeatherBB\Controller\Topic:open')->add(new IsAdmMod)->add(new ModeratePermission)->setName('openTopic');
    Route::get('/{id:\d+}/{name:[\w\-]+}/stick', '\FeatherBB\Controller\Topic:stick')->add(new IsAdmMod)->add(new ModeratePermission)->setName('stickTopic');
    Route::get('/{id:\d+}/{name:[\w\-]+}/unstick', '\FeatherBB\Controller\Topic:unstick')->add(new IsAdmMod)->add(new ModeratePermission)->setName('unstickTopic');
    Route::map(['GET', 'POST'], '/{id:\d+}/{name:[\w\-]+}/move/from/{fid:\d+}', '\FeatherBB\Controller\Topic:move')->add(new IsAdmMod)->add(new ModeratePermission)->setName('moveTopic');
    Route::map(['GET', 'POST'], '/{id:\d+}/{name:[\w\-]+}/moderate/forum/{fid:\d+}[/page/{page:\d+}]', '\FeatherBB\Controller\Topic:moderate')->add(new IsAdmMod)->add(new ModeratePermission)->setName('moderateTopic');
})->add(new CanReadBoard);

// Post routes
Route::group('/post', function () {
    Route::map(['GET', 'POST'], '/new-topic/{fid:\d+}', '\FeatherBB\Controller\Post:newpost')->setName('newTopic');
    Route::map(['GET', 'POST'], '/reply/{tid:\d+}', '\FeatherBB\Controller\Post:newreply')->setName('newReply');
    Route::map(['GET', 'POST'], '/reply/{tid:\d+}/quote/{qid:\d+}', '\FeatherBB\Controller\Post:newreply')->setName('newQuoteReply');
    Route::map(['GET', 'POST'], '/delete/{id:\d+}', '\FeatherBB\Controller\Post:delete')->setName('deletePost');
    Route::map(['GET', 'POST'], '/edit/{id:\d+}', '\FeatherBB\Controller\Post:editpost')->setName('editPost');
    Route::map(['GET', 'POST'], '/report/{id:\d+}', '\FeatherBB\Controller\Post:report')->setName('report');
    Route::get('/get-host/{pid:\d+}', '\FeatherBB\Controller\Post:gethost')->setName('getPostHost');
})->add(new CanReadBoard);

// Userlist
Route::get('/userlist', '\FeatherBB\Controller\Userlist:display')->add(new CanReadBoard)->setName('userList');

// Auth routes
Route::group('/auth', function () {
    Route::map(['GET', 'POST'], '', '\FeatherBB\Controller\Auth:login')->setName('login');
    Route::map(['GET', 'POST'], '/forget', '\FeatherBB\Controller\Auth:forget')->setName('resetPassword');
    Route::get('/logout/token/{token}', '\FeatherBB\Controller\Auth:logout')->setName('logout');
});

// Register routes
Route::group('/register', function () {
    Route::get('', '\FeatherBB\Controller\Register:rules')->setName('registerRules');
    Route::map(['GET', 'POST'], '/agree', '\FeatherBB\Controller\Register:display')->setName('register');
    Route::get('/cancel', '\FeatherBB\Controller\Register:cancel')->setName('registerCancel');
});

// Search routes
Route::group('/search', function () {
    Route::get('[/{search_id:\d+}]', '\FeatherBB\Controller\Search:display')->setName('search');
    Route::get('/show/{show}', '\FeatherBB\Controller\Search:quicksearches')->setName('quickSearch');
})->add(new CanReadBoard);

// Help
Route::get('/help', '\FeatherBB\Controller\Help:display')->add(new CanReadBoard)->setName('help');

// Profile routes
Route::group('/user', function () {
    Route::map(['GET', 'POST'], '/{id:\d+}', '\FeatherBB\Controller\Profile:display')->setName('userProfile');
    Route::map(['GET', 'POST'], '/{id:\d+}/section/{section}', '\FeatherBB\Controller\Profile:display')->setName('profileSection');
    Route::map(['GET', 'POST'], '/{id:\d+}/action/{action}[/pid/{pid:\d+}]', '\FeatherBB\Controller\Profile:action')->setName('profileAction');
    Route::map(['GET', 'POST'], '/email/{id:\d+}', '\FeatherBB\Controller\Profile:email')->setName('email');
    Route::get('/get-host/{ip}', '\FeatherBB\Controller\Profile:gethostip')->setName('getHostIp');
})->add(new IsLogged);


// Admin routes

Route::group('/admin', function () {

    // Admin index
    Route::get('[/action/{action}]', '\FeatherBB\Controller\Admin\Index:display')->setName('adminAction');
    Route::get('/index', '\FeatherBB\Controller\Admin\Index:display')->setName('adminIndex');

    // Admin updates
    Route::get('/updates', '\FeatherBB\Controller\Admin\Updates:display')->setName('adminUpdates');
    Route::post('/updates/upgrade-core', '\FeatherBB\Controller\Admin\Updates:upgradeCore')->setName('adminUpgradeCore');
    Route::post('/updates/upgrade-plugins', '\FeatherBB\Controller\Admin\Updates:upgradePlugins')->setName('adminUpgradePlugins');
    Route::post('/updates/upgrade-themes', '\FeatherBB\Controller\Admin\Updates:upgradeThemes')->setName('adminUpgradeThemes');

    // Admin bans
    Route::group('/bans', function () {
        Route::get('', '\FeatherBB\Controller\Admin\Bans:display')->setName('adminBans');
        Route::get('/delete/{id:\d+}', '\FeatherBB\Controller\Admin\Bans:delete')->setName('deleteBan');
        Route::map(['GET', 'POST'], '/edit/{id:\d+}', '\FeatherBB\Controller\Admin\Bans:edit')->setName('editBan');
        Route::map(['GET', 'POST'], '/add[/{id:\d+}]', '\FeatherBB\Controller\Admin\Bans:add')->setName('addBan');
    });

    // Admin options
    Route::map(['GET', 'POST'], '/options', '\FeatherBB\Controller\Admin\Options:display')->add(new IsAdmin)->setName('adminOptions');

    // Admin categories
    Route::group('/categories', function () {
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
    Route::group('/forums', function () {
        Route::map(['GET', 'POST'], '', '\FeatherBB\Controller\Admin\Forums:display')->setName('adminForums');
        Route::post('/add', '\FeatherBB\Controller\Admin\Forums:add')->setName('addForum');
        Route::map(['GET', 'POST'], '/edit/{id:\d+}', '\FeatherBB\Controller\Admin\Forums:edit')->setName('editForum');
        Route::map(['GET', 'POST'], '/delete/{id:\d+}', '\FeatherBB\Controller\Admin\Forums:delete')->setName('deleteForum');
    })->add(new IsAdmin);

    // Admin groups
    Route::group('/groups', function () {
        Route::map(['GET', 'POST'], '', '\FeatherBB\Controller\Admin\Groups:display')->setName('adminGroups');
        Route::map(['GET', 'POST'], '/add', '\FeatherBB\Controller\Admin\Groups:addedit')->setName('addGroup');
        Route::map(['GET', 'POST'], '/edit/{id:\d+}', '\FeatherBB\Controller\Admin\Groups:addedit')->setName('editGroup');
        Route::map(['GET', 'POST'], '/delete/{id:\d+}', '\FeatherBB\Controller\Admin\Groups:delete')->setName('deleteGroup');
    })->add(new IsAdmin);

    // Admin plugins
    Route::group('/plugins', function () {
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
    Route::group('/users', function () {
        Route::map(['GET', 'POST'], '', '\FeatherBB\Controller\Admin\Users:display')->setName('adminUsers');
        Route::get('/ip-stats/id/{id:\d+}', '\FeatherBB\Controller\Admin\Users:ipstats')->setName('usersIpStats');
        Route::get('/show-users', '\FeatherBB\Controller\Admin\Users:showusers')->setName('usersIpShow');
    });
})->add(new IsAdmMod);

// API
Route::group('/api', function () {
    Route::get('/user/{id:\d+}', '\FeatherBB\Controller\Api\User:display')->setName('userApi');
    Route::get('/forum/{id:\d+}', '\FeatherBB\Controller\Api\Forum:display')->setName('forumApi');
    Route::post('/forum/{id:\d+}', '\FeatherBB\Controller\Api\Topic:newTopic')->setName('newTopicApi');
    Route::get('/topic/{id:\d+}', '\FeatherBB\Controller\Api\Topic:display')->setName('topicApi');
    Route::post('/topic/{id:\d+}[/quote/{qid:\d+}]', '\FeatherBB\Controller\Api\Topic:newReply')->setName('newReplyApi');
    Route::get('/post/{id:\d+}', '\FeatherBB\Controller\Api\Post:display')->setName('postApi');
    Route::delete('/post/{id:\d+}', '\FeatherBB\Controller\Api\Post:delete')->setName('deletePostApi');
    Route::put('/post/{id:\d+}', '\FeatherBB\Controller\Api\Post:update')->setName('updatePostApi');
    Route::patch('/post/{id:\d+}', '\FeatherBB\Controller\Api\Post:update')->setName('updatePostApi');
})->add(new JsonHeader);

// Override the default Not Found Handler
Container::set('notFoundHandler', function ($c) {
    return function ($request, $response) use ($c) {
        throw new Error(__('Bad request'), 404);
    };
});

Container::set('errorHandler', function ($c) {
    return function ($request, $response, $e) use ($c) {
        $error = [
            'code' => $e->getCode(),
            'message' => $e->getMessage(),
            'back' => true,
            'html' => false,
            'hide' => false,
        ];

        // Hide internal mechanism
        if (!ForumEnv::get('FEATHER_DEBUG')) {
            $error['message'] = __('Error');
            $error['hide'] = true;
        }

        // Display a simple error page that does not require heavy user-specific methods like permissions
        if (method_exists($e, 'isSimpleError') && $e->isSimpleError()) {
            if (ob_get_contents()) {
                ob_end_clean();
            }

            // ob_start to avoid an extra "1" returned by PHP with a successful inclusion
            ob_start();
            include(ForumEnv::get('FEATHER_ROOT') . 'featherbb/View/errorSimple.php');
            $include = ob_get_clean();

            $response->write($include);

            return $response;
        }

        if (method_exists($e, 'hasBacklink')) {
            $error['back'] = $e->hasBacklink();
        }

        if (method_exists($e, 'displayHtml')) {
            $error['html'] = $e->displayHtml();
        }


        return View::setPageInfo([
            'title' => [\FeatherBB\Core\Utils::escape(ForumSettings::get('o_board_title')), __('Error')],
            'msg'    =>    $error['message'],
            'backlink'    => $error['back'],
            'html'    => $error['html'],
        ])->addTemplate('@forum/error')->display();
    };
});
