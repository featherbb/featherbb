<?php

/**
 * Copyright (C) 2015-2016 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace FeatherBB\Controller\Admin;

use FeatherBB\Core\AdminUtils;
use FeatherBB\Core\Url;
use FeatherBB\Core\Utils;
use FeatherBB\Model\Cache;

class Forums
{
    public function __construct()
    {
        $this->model = new \FeatherBB\Model\Admin\Forums();
        translate('admin/forums');
        if (!User::can('board.forums')) {
            throw new Error(__('No permission'), '403');
        }
    }

    public function add()
    {
        Container::get('hooks')->fire('controller.admin.forums.add');

        $cat_id = (int) Input::post('cat');

        if ($cat_id < 1) {
            return Router::redirect(Router::pathFor('adminForums'), __('Must be valid category'));
        }

        if ($fid = $this->model->add_forum($cat_id, __('New forum'))) {
            // Regenerate the quick jump cache
            Container::get('cache')->store('quickjump', Cache::get_quickjump());

            return Router::redirect(Router::pathFor('editForum', array('id' => $fid)), __('Forum added redirect'));
        } else {
            return Router::redirect(Router::pathFor('adminForums'), __('Unable to add forum'));
        }
    }

    public function edit($req, $res, $args)
    {
        Container::get('hooks')->fire('controller.admin.forums.edit');

        if(Request::isPost()) {
            if (Input::post('save') && Input::post('read_forum_old')) {

                // Forums parameters / TODO : better handling of wrong parameters
                $forum_data = array('forum_name' => Utils::escape(Input::post('forum_name')),
                                    'forum_desc' => Input::post('forum_desc') ? Utils::linebreaks(Utils::trim(Input::post('forum_desc'))) : NULL,
                                    'cat_id' => (int) Input::post('cat_id'),
                                    'sort_by' => (int) Input::post('sort_by'),
                                    'redirect_url' => Url::is_valid(Input::post('redirect_url')) ? Utils::escape(Input::post('redirect_url')) : NULL);

                if ($forum_data['forum_name'] == '') {
                    return Router::redirect(Router::pathFor('editForum', array('id' => $args['id'])), __('Must enter name message'));
                }
                if ($forum_data['cat_id'] < 1) {
                    return Router::redirect(Router::pathFor('editForum', array('id' => $args['id'])), __('Must be valid category'));
                }

                $this->model->update_forum($args['id'], $forum_data);

                // Permissions
                $permissions = $this->model->get_default_group_permissions(false);
                foreach($permissions as $perm_group) {
                    $permissions_data = array('group_id' => $perm_group['g_id'],
                                                'forum_id' => $args['id']);
                    if ($perm_group['g_read_board'] == '1' && isset(Input::post('read_forum_new')[$perm_group['g_id']]) && Input::post('read_forum_new')[$perm_group['g_id']] == '1') {
                        $permissions_data['read_forum'] = '1';
                    }
                    else {
                        $permissions_data['read_forum'] = '0';
                    }

                    $permissions_data['post_replies'] = (isset(Input::post('post_replies_new')[$perm_group['g_id']])) ? '1' : '0';
                    $permissions_data['post_topics'] = (isset(Input::post('post_topics_new')[$perm_group['g_id']])) ? '1' : '0';
                    // Check if the new settings differ from the old
                    if ($permissions_data['read_forum'] != Input::post('read_forum_old')[$perm_group['g_id']] ||
                        $permissions_data['post_replies'] != Input::post('post_replies_old')[$perm_group['g_id']] ||
                        $permissions_data['post_topics'] != Input::post('post_topics_old')[$perm_group['g_id']]) {
                            // If there is no group permissions override for this forum
                            if ($permissions_data['read_forum'] == '1' && $permissions_data['post_replies'] == $perm_group['g_post_replies'] && $permissions_data['post_topics'] == $perm_group['g_post_topics']) {
                                $this->model->delete_permissions($args['id'], $perm_group['g_id']);
                            } else {
                            // Run an UPDATE and see if it affected a row, if not, INSERT
                                $this->model->update_permissions($permissions_data);
                            }
                    }
                }

                // Regenerate the quick jump cache
                Container::get('cache')->store('quickjump', Cache::get_quickjump());

                return Router::redirect(Router::pathFor('editForum', array('id' => $args['id'])), __('Forum updated redirect'));

            } elseif (Input::post('revert_perms')) {
                $this->model->delete_permissions($args['id']);

                // Regenerate the quick jump cache
                Container::get('cache')->store('quickjump', Cache::get_quickjump());

                return Router::redirect(Router::pathFor('editForum', array('id' => $args['id'])), __('Perms reverted redirect'));
            }

        } else {
            AdminUtils::generateAdminMenu('forums');

            View::setPageInfo(array(
                    'title'    =>    array(Utils::escape(ForumSettings::get('o_board_title')), __('Admin'), __('Forums')),
                    'active_page'    =>    'admin',
                    'admin_console'    =>    true,
                    'perm_data' => $this->model->get_permissions($args['id']),
                    'cur_index'     =>  7,
                    'cur_forum' => $this->model->get_forum_info($args['id']),
                    'forum_data' => $this->model->get_forums(),
                )
            )->addTemplate('admin/forums/permissions.php')->display();
        }
    }

    public function delete($req, $res, $args)
    {
        Container::get('hooks')->fire('controller.admin.forums.delete');

        if (!$cur_forum = $this->model->get_forum_info($args['id'])) {
            $notFoundHandler = Container::get('notFoundHandler');
            return $notFoundHandler($req, $res);
        }

        if(Request::isPost()) {
            $this->model->delete_forum($args['id']);
            // Regenerate the quick jump cache
            Container::get('cache')->store('quickjump', Cache::get_quickjump());

            return Router::redirect(Router::pathFor('adminForums'), __('Forum deleted redirect'));

        } else { // If the user hasn't confirmed

            AdminUtils::generateAdminMenu('forums');

            View::setPageInfo(array(
                    'title'    =>    array(Utils::escape(ForumSettings::get('o_board_title')), __('Admin'), __('Forums')),
                    'active_page'    =>    'admin',
                    'admin_console'    =>    true,
                    'cur_forum' => $cur_forum
                )
            )->addTemplate('admin/forums/delete_forum.php')->display();
        }
    }

    public function edit_positions($req, $res, $args)
    {
        Container::get('hooks')->fire('controller.admin.forums.edit_positions');

        foreach (Input::post('position') as $args['forum_id'] => $position) {
            $position = (int) Utils::trim($position);
            $this->model->update_positions($args['forum_id'], $position);
        }

        // Regenerate the quick jump cache
        Container::get('cache')->store('quickjump', Cache::get_quickjump());

        return Router::redirect(Router::pathFor('adminForums'), __('Forums updated redirect'));
    }

    public function display($req, $res, $args)
    {
        Container::get('hooks')->fire('controller.admin.forums.display');

        if (Input::post('update_positions')) {
            return $this->edit_positions($req, $res, $args);
        }

        AdminUtils::generateAdminMenu('forums');

        $categories_model = new \FeatherBB\Model\Admin\Categories();
        View::setPageInfo(array(
                'title' => array(Utils::escape(ForumSettings::get('o_board_title')), __('Admin'), __('Forums')),
                'active_page' => 'admin',
                'admin_console' => true,
                'cat_list' => $categories_model->get_cat_list(),
                'forum_data' => $this->model->get_forums(),
                'cur_index' => 4,
            )
        )->addTemplate('admin/forums/admin_forums.php')->display();
    }
}
