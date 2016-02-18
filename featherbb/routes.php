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
$canReadBoard = function ($request, $response, $next) use ($feather) {
    $response = $next($request, $response);
    if (Container::get('user')->g_read_board == '0') {
        throw new Error(__('No view'), 403);
    }

    return $response;
};

/**
 * Middleware to check if user is allowed to read the board.
 */
$isGuest = function ($request, $response, $next) use ($feather) {
    $response = $next($request, $response);
    if (Container::get('user')->is_guest) {
        throw new Error(__('No permission'), 403);
    }

    return $response;
};

/**
 * Middleware to check if user is allowed to moderate, if he's not redirect to homepage.
 */
$isAdmmod = function ($request, $response, $next) use ($feather) {
    $response = $next($request, $response);
    // if(!Container::get('user')->is_admmod) {
    //     throw new Error(__('No permission'), 403);
    // }

    return $response;
};

Route::get('/install', '\FeatherBB\Controller\Install:run')->setName('install');

// Index
Route::get('/', '\FeatherBB\Controller\Index:display')->add($canReadBoard)->setName('home');
Route::get('/rules', '\FeatherBB\Controller\Index:rules')->setName('rules');
Route::get('/mark-read', '\FeatherBB\Controller\Index:markread')->add($isGuest)->setName('markRead');

// Forum
Route::group('/forum', function() use ($feather) {
    $isGuest = function() use ($feather) {
        if (Container::get('user')->is_guest) {
            throw new Error(__('No permission'), 403);
        }
    };
    Route::get('/{id:[0-9]+}/{name:[\w\-]+}', '\FeatherBB\Controller\Forum:display')->setName('Forum');
    Route::get('/{id:[0-9]+}/{name:[\w\-]+}/page/{page:[0-9]+}', '\FeatherBB\Controller\Forum:display')->setName('ForumPaginate');
    Route::get('/mark-read/{id:[0-9]+}[/{name:[\w\-]+}]', '\FeatherBB\Controller\Forum:markread')->add($isGuest)->setName('markForumRead');
    Route::get('/subscribe/{id:[0-9]+}[/{name:[\w\-]+}]', '\FeatherBB\Controller\Forum:subscribe')->add($isGuest)->setName('subscribeForum');
    Route::get('/unsubscribe/{id:[0-9]+}[/{name:[\w\-]+}]', '\FeatherBB\Controller\Forum:unsubscribe')->add($isGuest)->setName('unsubscribeForum');
    Route::get('/moderate/{fid:[0-9]+}/page/{page:[0-9]+}', '\FeatherBB\Controller\Forum:moderate')->setName('moderateForum');
    $feather->post('/moderate/{fid:[0-9]+}[/page/{page:[0-9]+}]', '\FeatherBB\Controller\Forum:dealposts')->setName('dealPosts');
})->add($canReadBoard);

// Topic
Route::group('/topic', function() use ($feather) {
    $isGuest = function() use ($feather) {
        if (Container::get('user')->is_guest) {
            throw new Error(__('No permission'), 403);
        }
    };
    $isAdmmod = function() use ($feather) {
        if(!Container::get('user')->is_admmod) {
            throw new Error(__('No permission'), 403);
        }
    };
    Route::get('/{id:[0-9]+}/{name:[\w\-]+}', '\FeatherBB\Controller\Topic:display')->setName('Topic');
    Route::get('/{id:[0-9]+}/{name:[\w\-]+}/page/{page:[0-9]+}', '\FeatherBB\Controller\Topic:display')->setName('TopicPaginate');
    Route::get('/subscribe/{id:[0-9]+}[/{name:[\w\-]+}]', '\FeatherBB\Controller\Topic:subscribe')->add($isGuest)->setName('subscribeTopic');
    Route::get('/unsubscribe/{id:[0-9]+}[/{name:[\w\-]+}]', '\FeatherBB\Controller\Topic:unsubscribe')->add($isGuest)->setName('unsubscribeTopic');
    Route::get('/close/{id:[0-9]+}[/{name:[\w\-]+}]', '\FeatherBB\Controller\Topic:close')->add($isAdmmod)->setName('closeTopic');
    Route::get('/open/{id:[0-9]+}[/{name:[\w\-]+}]', '\FeatherBB\Controller\Topic:open')->add($isAdmmod)->setName('openTopic');
    Route::get('/stick/{id:[0-9]+}[/{name:[\w\-]+}]', '\FeatherBB\Controller\Topic:stick')->add($isAdmmod)->setName('stickTopic');
    Route::get('/unstick/{id:[0-9]+}[/{name:[\w\-]+}]', '\FeatherBB\Controller\Topic:unstick')->add($isAdmmod)->setName('unstickTopic');
    Route::map(['GET', 'POST'], '/move/{id:[0-9]+}[/{name:[\w\-]+}/forum/{fid:[0-9]+}]', '\FeatherBB\Controller\Topic:move')->add($isAdmmod)->setName('moveTopic');
    Route::map(['GET', 'POST'], '/moderate/{id:[0-9]+}/forum/{fid:[0-9]+}[/page/{page:[0-9]+}]', '\FeatherBB\Controller\Topic:moderate')->add($isAdmmod)->setName('moderateTopic');
    Route::get('/{id:[0-9]+}/action/{action}', '\FeatherBB\Controller\Topic{action}')->setName('topicAction');
})->add($canReadBoard);

// Post routes
Route::group('/post', function() use ($feather) {
    Route::get('/{pid:[0-9]+}', '\FeatherBB\Controller\Topic:viewpost')->setName('viewPost');
    Route::map(['GET', 'POST'], '/new-topic/{fid:[0-9]+}', '\FeatherBB\Controller\Post:newpost')->setName('newTopic');
    Route::map(['GET', 'POST'], '/reply/{tid:[0-9]+}[/quote/{qid:[0-9]+}]', '\FeatherBB\Controller\Post:newreply')->setName('newQuoteReply');
    Route::map(['GET', 'POST'], '/delete/{id:[0-9]+}', '\FeatherBB\Controller\Post:delete')->setName('deletePost');
    Route::map(['GET', 'POST'], '/edit/{id:[0-9]+}', '\FeatherBB\Controller\Post:editpost')->setName('editPost');
    Route::map(['GET', 'POST'], '/report/{id:[0-9]+}', '\FeatherBB\Controller\Post:report')->setName('report');
    Route::get('/get-host/{pid:[0-9]+}', '\FeatherBB\Controller\Post:gethost')->setName('getPostHost');
})->add($canReadBoard);

// Userlist
Route::get('/userlist', '\FeatherBB\Controller\Userlist:display')->add($canReadBoard)->setName('userList');

// Auth routes
Route::group('/auth', function() use ($feather) {
    Route::get('', function() use ($feather) {
        if (!Container::get('user')->is_guest) {
            $feather->url->redirect($feather->urlFor('home'), 'Already logged');
        } else {
            $feather->redirect($feather->urlFor('login'));
        }
    });
    Route::map(['GET', 'POST'], '/login', '\FeatherBB\Controller\Auth:login')->setName('login');
    Route::map(['GET', 'POST'], '/forget', '\FeatherBB\Controller\Auth:forget')->setName('resetPassword');
    Route::get('/logout/token/{token}', '\FeatherBB\Controller\Auth:logout')->setName('logout');
});

// Register routes
Route::group('/register', function() use ($feather) {
    Route::get('', '\FeatherBB\Controller\Register:rules')->setName('registerRules');
    Route::map(['GET', 'POST'], '/agree', '\FeatherBB\Controller\Register:display')->setName('register');
    Route::get('/cancel', '\FeatherBB\Controller\Register:cancel')->setName('registerCancel');
});

// Search routes
Route::group('/search', function() use ($feather) {
    Route::get('', '\FeatherBB\Controller\Search:display')->setName('search');
    Route::get('/show/{show}', '\FeatherBB\Controller\Search:quicksearches')->setName('quickSearch');
})->add($canReadBoard);

// Help
Route::get('/help', '\FeatherBB\Controller\Help:display')->add($canReadBoard)->setName('help');

// Profile routes
Route::group('/user', function() use ($feather) {
    Route::get('/{id:[0-9]+}', '\FeatherBB\Controller\Profile:display')->setName('userProfile');
    Route::map(['GET', 'POST'], '/{id:[0-9]+}/section/{section}', '\FeatherBB\Controller\Profile:display')->setName('profileSection');
    Route::map(['GET', 'POST'], '/{id:[0-9]+}/action/{action}', '\FeatherBB\Controller\Profile:action')->setName('profileAction');
    Route::map(['GET', 'POST'], '/email/{id:[0-9]+}', '\FeatherBB\Controller\Profile:email')->setName('email');
    Route::get('/get-host/{ip}', '\FeatherBB\Controller\Profile:gethostip')->setName('getHostIp');
})->add($isGuest);


// Admin routes

Route::group('/admin', function() use ($feather) {

    /**
     * Middleware to check if user is admin.
     */

    $isAdmin = function() use ($feather) {
        if(Container::get('user')->g_id != $feather->forum_env['FEATHER_ADMIN']) {
            $feather->url->redirect($feather->urlFor('home'), __('No permission'));
        }
    };

    // Admin index
    Route::get('[/action/{action}]', '\FeatherBB\Controller\Admin\Index:display')->setName('adminAction');
    Route::get('/index', '\FeatherBB\Controller\Admin\Index:display')->setName('adminIndex');

    // Admin bans
    Route::group('/bans', function() use ($feather) {
        Route::get('', '\FeatherBB\Controller\Admin\Bans:display')->setName('adminBans');
        Route::get('/delete/{id:[0-9]+}', '\FeatherBB\Controller\Admin\Bans:delete')->setName('deleteBan');
        Route::map(['GET', 'POST'], '/edit/{id:[0-9]+}', '\FeatherBB\Controller\Admin\Bans:edit')->setName('editBan');
        Route::map(['GET', 'POST'], '/add[/{id:[0-9]+}]', '\FeatherBB\Controller\Admin\Bans:add')->setName('addBan');
    });

    // Admin options
    Route::map(['GET', 'POST'], '/options', '\FeatherBB\Controller\Admin\Options:display')->add($isAdmin)->setName('adminOptions');

    // Admin categories
    Route::group('/categories', function() use ($feather) {
        Route::get('', '\FeatherBB\Controller\Admin\Categories:display')->setName('adminCategories');
        $feather->post('/add', '\FeatherBB\Controller\Admin\Categories:add')->setName('addCategory');
        $feather->post('/edit', '\FeatherBB\Controller\Admin\Categories:edit')->setName('editCategory');
        $feather->post('/delete', '\FeatherBB\Controller\Admin\Categories:delete')->setName('deleteCategory');
    })->add($isAdmin);

    // Admin censoring
    Route::map(['GET', 'POST'], '/censoring', '\FeatherBB\Controller\Admin\Censoring:display')->add($isAdmin)->setName('adminCensoring');

    // Admin reports
    Route::map(['GET', 'POST'], '/reports', '\FeatherBB\Controller\Admin\Reports:display')->setName('adminReports');

    // Admin permissions
    Route::map(['GET', 'POST'], '/permissions', '\FeatherBB\Controller\Admin\Permissions:display')->add($isAdmin)->setName('adminPermissions');

    // Admin statistics
    Route::get('/statistics', '\FeatherBB\Controller\Admin\Statistics:display')->setName('statistics');
    Route::get('/phpinfo', '\FeatherBB\Controller\Admin\Statistics:phpinfo')->setName('phpInfo');

    // Admin forums
    Route::group('/forums', function() use ($feather) {
        Route::map(['GET', 'POST'], '', '\FeatherBB\Controller\Admin\Forums:display')->setName('adminForums');
        $feather->post('/add', '\FeatherBB\Controller\Admin\Forums:add')->setName('addForum');
        Route::map(['GET', 'POST'], '/edit/{id:[0-9]+}', '\FeatherBB\Controller\Admin\Forums:edit')->setName('editForum');
        Route::map(['GET', 'POST'], '/delete/{id:[0-9]+}', '\FeatherBB\Controller\Admin\Forums:delete')->setName('deleteForum');
    })->add($isAdmin);

    // Admin groups
    Route::group('/groups', function() use ($feather) {
        Route::map(['GET', 'POST'], '', '\FeatherBB\Controller\Admin\Groups:display')->setName('adminGroups');
        Route::map(['GET', 'POST'], '/add', '\FeatherBB\Controller\Admin\Groups:addedit')->setName('addGroup');
        Route::map(['GET', 'POST'], '/edit/{id:[0-9]+}', '\FeatherBB\Controller\Admin\Groups:addedit')->setName('editGroup');
        Route::map(['GET', 'POST'], '/delete/{id:[0-9]+}', '\FeatherBB\Controller\Admin\Groups:delete')->setName('deleteGroup');
    })->add($isAdmin);

    // Admin plugins
    Route::group('/plugins', function() use ($feather) {
        Route::map(['GET', 'POST'], '', '\FeatherBB\Controller\Admin\Plugins:index')->setName('adminPlugins');
        Route::map(['GET', 'POST'], '/info/{name:[\w\-]+}', '\FeatherBB\Controller\Admin\Plugins:info')->setName('infoPlugin');
        Route::get('/activate/{name:[\w\-]+}', '\FeatherBB\Controller\Admin\Plugins:activate')->setName('activatePlugin');
        Route::get('/download/{name:[\w\-]+}/{version}', '\FeatherBB\Controller\Admin\Plugins:download')->setName('downloadPlugin');
        Route::get('/deactivate/{name:[\w\-]+}', '\FeatherBB\Controller\Admin\Plugins:deactivate')->setName('deactivatePlugin');
        Route::get('/uninstall/{name:[\w\-]+}', '\FeatherBB\Controller\Admin\Plugins:uninstall')->setName('uninstallPlugin');
    });

    // Admin maintenance
    Route::map(['GET', 'POST'], '/maintenance', '\FeatherBB\Controller\Admin\Maintenance:display')->add($isAdmin)->setName('adminMaintenance');

    // Admin parser
    Route::map(['GET', 'POST'], '/parser', '\FeatherBB\Controller\Admin\Parser:display')->add($isAdmin)->setName('adminParser');

    // Admin users
    Route::group('/users', function() use ($feather) {
        Route::map(['GET', 'POST'], '', '\FeatherBB\Controller\Admin\Users:display')->setName('adminUsers');
        Route::get('/ip-stats/id/{id:[0-9]+}', '\FeatherBB\Controller\Admin\Users:ipstats')->setName('usersIpStats');
        Route::get('/show-users/ip/{ip}', '\FeatherBB\Controller\Admin\Users:showusers')->setName('usersIpShow');
    });

})->add($isAdmmod);

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
        var_dump($exception);
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
//         'title' => array(\FeatherBB\Core\Utils::escape(Config::get('forum_settings')['o_board_title']), __('Error')),
//         'msg'    =>    $error['message'],
//         'backlink'    => $error['back'],
//     ))->addTemplate('error.php')->display();
//     $feather->stop();
// });
