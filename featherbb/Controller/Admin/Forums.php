<?php

/**
 * Copyright (C) 2015-2017 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace FeatherBB\Controller;

use FeatherBB\Core\AdminUtils;
use FeatherBB\Core\Error;
use FeatherBB\Core\Interfaces\Container;
use FeatherBB\Core\Interfaces\ForumSettings;
use FeatherBB\Core\Interfaces\Hooks;
use FeatherBB\Core\Interfaces\Input;
use FeatherBB\Core\Interfaces\Lang;
use FeatherBB\Core\Interfaces\Request;
use FeatherBB\Core\Interfaces\Router;
use FeatherBB\Core\Interfaces\User;
use FeatherBB\Core\Interfaces\View;
use FeatherBB\Core\Url;
use FeatherBB\Core\Utils;
use FeatherBB\Model\Cache;

class Forums
{
    public function __construct()
    {
        $this->model = new \FeatherBB\Model\Admin\Forums();
        Lang::load('admin/forums');
        if (!User::isAdmin()) {
            throw new Error(__('No permission'), '403');
        }
    }

    public function add()
    {
        Container::get('hooks')->fire('controller.admin.forums.add');

        $catId = (int) Input::post('cat');

        if ($catId < 1) {
            return Router::redirect(Router::pathFor('adminForums'), __('Must be valid category'));
        }

        if ($fid = $this->model->addForum($catId, __('New forum'))) {
            // Regenerate the quick jump cache
            Container::get('cache')->store('quickjump', Cache::quickjump());

            return Router::redirect(Router::pathFor('editForum', ['id' => $fid]), __('Forum added redirect'));
        } else {
            return Router::redirect(Router::pathFor('adminForums'), __('Unable to add forum'));
        }
    }

    public function edit($req, $res, $args)
    {
        Container::get('hooks')->fire('controller.admin.forums.edit');

        if (Request::isPost()) {
            if (Input::post('save') && Input::post('read_forum_old')) {

                // Forums parameters / TODO : better handling of wrong parameters
                $forumData = ['forum_name' => Utils::escape(Input::post('forum_name')),
                                    'forum_desc' => Input::post('forum_desc') ? Utils::linebreaks(Utils::trim(Input::post('forum_desc'))) : null,
                                    'cat_id' => (int) Input::post('cat_id'),
                                    'sort_by' => (int) Input::post('sort_by'),
                                    'redirect_url' => Url::isValid(Input::post('redirect_url')) ? Utils::escape(Input::post('redirect_url')) : null];

                if ($forumData['forum_name'] == '') {
                    return Router::redirect(Router::pathFor('editForum', ['id' => $args['id']]), __('Must enter name message'));
                }
                if ($forumData['cat_id'] < 1) {
                    return Router::redirect(Router::pathFor('editForum', ['id' => $args['id']]), __('Must be valid category'));
                }

                $this->model->updateForum($args['id'], $forumData);

                // Permissions
                $permissions = $this->model->getDefaultGroupPermissions(false);
                foreach ($permissions as $permGroup) {
                    $permissionsData = ['group_id' => $permGroup['g_id'],
                                                'forum_id' => $args['id']];
                    if ($permGroup['board.read'] == '1' && isset(Input::post('read_forum_new')[$permGroup['g_id']]) && Input::post('read_forum_new')[$permGroup['g_id']] == '1') {
                        $permissionsData['read_forum'] = '1';
                    } else {
                        $permissionsData['read_forum'] = '0';
                    }

                    $permissionsData['post_replies'] = (isset(Input::post('post_replies_new')[$permGroup['g_id']])) ? '1' : '0';
                    $permissionsData['post_topics'] = (isset(Input::post('post_topics_new')[$permGroup['g_id']])) ? '1' : '0';
                    // Check if the new settings differ from the old
                    if ($permissionsData['read_forum'] != Input::post('read_forum_old')[$permGroup['g_id']] ||
                        $permissionsData['post_replies'] != Input::post('post_replies_old')[$permGroup['g_id']] ||
                        $permissionsData['post_topics'] != Input::post('post_topics_old')[$permGroup['g_id']]) {
                        // If there is no group permissions override for this forum
                            if ($permissionsData['read_forum'] == '1' && $permissionsData['post_replies'] == $permGroup['topic.reply'] && $permissionsData['post_topics'] == $permGroup['topic.post']) {
                                $this->model->deletePermissions($args['id'], $permGroup['g_id']);
                            } else {
                                // Run an UPDATE and see if it affected a row, if not, INSERT
                                $this->model->updatePermissions($permissionsData);
                            }
                    }
                }

                // Regenerate the quick jump cache
                Container::get('cache')->store('quickjump', Cache::quickjump());

                return Router::redirect(Router::pathFor('editForum', ['id' => $args['id']]), __('Forum updated redirect'));
            } elseif (Input::post('revert_perms')) {
                $this->model->deletePermissions($args['id']);

                // Regenerate the quick jump cache
                Container::get('cache')->store('quickjump', Cache::quickjump());

                return Router::redirect(Router::pathFor('editForum', ['id' => $args['id']]), __('Perms reverted redirect'));
            }
        } else {
            AdminUtils::generateAdminMenu('forums');

            View::setPageInfo([
                    'title'    =>    [Utils::escape(ForumSettings::get('o_board_title')), __('Admin'), __('Forums')],
                    'active_page'    =>    'admin',
                    'admin_console'    =>    true,
                    'perm_data' => $this->model->getPermissions($args['id']),
                    'cur_index'     =>  7,
                    'cur_forum' => $this->model->getForumInfo($args['id']),
                    'forum_data' => $this->model->getForums(),
                ]
            )->addTemplate('@forum/admin/forums/permissions')->display();
        }
    }

    public function delete($req, $res, $args)
    {
        Container::get('hooks')->fire('controller.admin.forums.delete');

        if (!$curForum = $this->model->getForumInfo($args['id'])) {
            $notFoundHandler = Container::get('notFoundHandler');
            return $notFoundHandler($req, $res);
        }

        if (Request::isPost()) {
            $this->model->deleteForum($args['id']);
            // Regenerate the quick jump cache
            Container::get('cache')->store('quickjump', Cache::quickjump());

            return Router::redirect(Router::pathFor('adminForums'), __('Forum deleted redirect'));
        } else { // If the user hasn't confirmed

            AdminUtils::generateAdminMenu('forums');

            View::setPageInfo([
                    'title'    =>    [Utils::escape(ForumSettings::get('o_board_title')), __('Admin'), __('Forums')],
                    'active_page'    =>    'admin',
                    'admin_console'    =>    true,
                    'cur_forum' => $curForum
                ]
            )->addTemplate('@forum/admin/forums/delete_forum')->display();
        }
    }

    public function editPositions($req, $res, $args)
    {
        Container::get('hooks')->fire('controller.admin.forums.edit_positions');

        foreach (Input::post('position') as $args['forum_id'] => $position) {
            $position = (int) Utils::trim($position);
            $this->model->updatePositions($args['forum_id'], $position);
        }

        // Regenerate the quick jump cache
        Container::get('cache')->store('quickjump', Cache::quickjump());

        return Router::redirect(Router::pathFor('adminForums'), __('Forums updated redirect'));
    }

    public function display($req, $res, $args)
    {
        Container::get('hooks')->fire('controller.admin.forums.display');

        if (Input::post('update_positions')) {
            return $this->editPositions($req, $res, $args);
        }

        AdminUtils::generateAdminMenu('forums');

        $categoriesModel = new \FeatherBB\Model\Admin\Categories();
        View::setPageInfo([
                'title' => [Utils::escape(ForumSettings::get('o_board_title')), __('Admin'), __('Forums')],
                'active_page' => 'admin',
                'admin_console' => true,
                'cat_list' => $categoriesModel->categoryList(),
                'forum_data' => $this->model->getForums(),
                'cur_index' => 4,
            ]
        )->addTemplate('@forum/admin/forums/admin_forums')->display();
    }
}
