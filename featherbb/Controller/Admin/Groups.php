<?php

/**
 * Copyright (C) 2015-2016 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace FeatherBB\Controller\Admin;

use FeatherBB\Core\AdminUtils;
use FeatherBB\Core\Error;
use FeatherBB\Core\Utils;

class Groups
{
    public function __construct()
    {
        $this->model = new \FeatherBB\Model\Admin\Groups();
        translate('admin/groups');
        if (!User::can('board.groups')) {
            throw new Error(__('No permission'), '403');
        }
    }

    public function display($req, $res, $args)
    {
        Container::get('hooks')->fire('controller.admin.groups.display');

        $groups = $this->model->fetch_groups();

        // Set default group
        if (Request::isPost()) {
            return $this->model->set_default_group($groups);
        }

        AdminUtils::generateAdminMenu('user groups');

        View::setPageInfo(array(
                'title' => array(Utils::escape(ForumSettings::get('o_board_title')), __('Admin'), __('User groups')),
                'active_page' => 'admin',
                'admin_console' => true,
                'groups' => $groups,
                'cur_index' => 5,
            )
        )->addTemplate('admin/groups/admin_groups.php')->display();
    }

    public function delete($req, $res, $args)
    {
        Container::get('hooks')->fire('controller.admin.groups.delete');

        if ($args['id'] < 5) {
            throw new Error(__('Bad request'), 403);
        }

        // Make sure we don't remove the default group
        if ($args['id'] == ForumSettings::get('o_default_user_group')) {
            throw new Error(__('Cannot remove default message'), 403);
        }

        // Check if this group has any members
        $is_member = $this->model->check_members($args['id']);

        // If the group doesn't have any members or if we've already selected a group to move the members to
        if (!$is_member || Input::post('del_group')) {
            if (Input::post('del_group_comply') || Input::post('del_group')) {
                return $this->model->delete_group($args['id']);
            } else {
                AdminUtils::generateAdminMenu('user groups');

                return View::setPageInfo(array(
                        'title' => array(Utils::escape(ForumSettings::get('o_board_title')), __('Admin'), __('User groups')),
                        'active_page' => 'admin',
                        'admin_console' => true,
                        'group_title'      =>  $this->model->get_group_title($args['id']),
                        'id'    => $args['id'],
                    )
                )->addTemplate('admin/groups/confirm_delete.php')->display();
            }
        }

        AdminUtils::generateAdminMenu('user groups');

        return View::setPageInfo(array(
                'title' => array(Utils::escape(ForumSettings::get('o_board_title')), __('Admin'), __('User groups')),
                'active_page' => 'admin',
                'admin_console' => true,
                'id'    => $args['id'],
                'group_info'      =>  $this->model->get_title_members($args['id']),
                'group_list_delete'      =>  $this->model->get_group_list_delete($args['id']),
            )
        )->addTemplate('admin/groups/delete_group.php')->display();
    }

    public function addedit($req, $res, $args)
    {
        Container::get('hooks')->fire('controller.admin.groups.addedit');

        $groups = $this->model->fetch_groups();

        // Add/edit a group (stage 2)
        if (Input::post('add_edit_group')) {
            return $this->model->add_edit_group($groups);
        }

        // Add/edit a group (stage 1)
        elseif (Input::post('add_group') || isset($args['id'])) {

            AdminUtils::generateAdminMenu('user groups');

            $id = isset($args['id']) ? intval($args['id']) : intval(Input::post('base_group'));
            $group = $this->model->info_add_group($groups, $id);

            View::setPageInfo(array(
                    'title' => array(Utils::escape(ForumSettings::get('o_board_title')), __('Admin'), __('User groups')),
                    'active_page' => 'admin',
                    'admin_console' => true,
                    'group'    =>    $group,
                    'groups'    =>    $groups,
                    'id'    => $id,
                    'group_list'    => $this->model->get_group_list($groups, $group),
                )
            )->addTemplate('admin/groups/add_edit_group.php')->display();
        }
    }
}
