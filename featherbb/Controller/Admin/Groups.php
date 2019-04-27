<?php

/**
 * Copyright (C) 2015-2019 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace FeatherBB\Controller\Admin;

use FeatherBB\Core\AdminUtils;
use FeatherBB\Core\Error;
use FeatherBB\Core\Interfaces\ForumSettings;
use FeatherBB\Core\Interfaces\Hooks;
use FeatherBB\Core\Interfaces\Input;
use FeatherBB\Core\Interfaces\Lang;
use FeatherBB\Core\Interfaces\Request;
use FeatherBB\Core\Interfaces\User;
use FeatherBB\Core\Interfaces\View;
use FeatherBB\Core\Utils;

class Groups
{
    public function __construct()
    {
        $this->model = new \FeatherBB\Model\Admin\Groups();
        Lang::load('admin/groups');
        if (!User::isAdmin()) {
            throw new Error(__('No permission'), '403');
        }
    }

    public function display($req, $res, $args)
    {
        Hooks::fire('controller.admin.groups.display');

        $groups = $this->model->groups();

        // Set default group
        if (Request::isPost()) {
            return $this->model->setDefaultGroup($groups);
        }

        AdminUtils::generateAdminMenu('user groups');

        View::setPageInfo([
                'title' => [Utils::escape(ForumSettings::get('o_board_title')), __('Admin'), __('User groups')],
                'active_page' => 'admin',
                'admin_console' => true,
                'groups' => $groups,
                'cur_index' => 5,
            ]
        )->addTemplate('@forum/admin/groups/admin_groups')->display();
    }

    public function delete($req, $res, $args)
    {
        Hooks::fire('controller.admin.groups.delete');

        if ($args['id'] < 5) {
            throw new Error(__('Bad request'), 403);
        }

        // Make sure we don't remove the default group
        if ($args['id'] == ForumSettings::get('o_default_user_group')) {
            throw new Error(__('Cannot remove default message'), 403);
        }

        // Check if this group has any members
        $isMember = $this->model->checkMembers($args['id']);

        // If the group doesn't have any members or if we've already selected a group to move the members to
        if (!$isMember || Input::post('del_group')) {
            if (Input::post('del_group_comply') || Input::post('del_group')) {
                return $this->model->deleteGroup($args['id']);
            } else {
                AdminUtils::generateAdminMenu('user groups');

                return View::setPageInfo([
                        'title' => [Utils::escape(ForumSettings::get('o_board_title')), __('Admin'), __('User groups')],
                        'active_page' => 'admin',
                        'admin_console' => true,
                        'group_title'      =>  $this->model->groupTitle($args['id']),
                        'id'    => $args['id'],
                    ]
                )->addTemplate('@forum/admin/groups/confirm_delete')->display();
            }
        }

        AdminUtils::generateAdminMenu('user groups');

        return View::setPageInfo([
                'title' => [Utils::escape(ForumSettings::get('o_board_title')), __('Admin'), __('User groups')],
                'active_page' => 'admin',
                'admin_console' => true,
                'id'    => $args['id'],
                'group_info'      =>  $this->model->titleMembers($args['id']),
                'group_list_delete'      =>  $this->model->groupListDelete($args['id']),
            ]
        )->addTemplate('@forum/admin/groups/delete_group')->display();
    }

    public function addedit($req, $res, $args)
    {
        Hooks::fire('controller.admin.groups.addedit');

        $groups = $this->model->groups();

        // Add/edit a group (stage 2)
        if (Input::post('add_edit_group')) {
            return $this->model->addEdit($groups);
        }

        // Add/edit a group (stage 1)
        elseif (Input::post('add_group') || isset($args['id'])) {
            AdminUtils::generateAdminMenu('user groups');

            $id = isset($args['id']) ? intval($args['id']) : intval(Input::post('base_group'));
            $group = $this->model->infoAdd($groups, $id);

            View::setPageInfo([
                    'title' => [Utils::escape(ForumSettings::get('o_board_title')), __('Admin'), __('User groups')],
                    'active_page' => 'admin',
                    'admin_console' => true,
                    'group'    =>    $group,
                    'id'    => $id,
                    'group_list'    => $this->model->getGroupList($groups, $group),
                ]
            )->addTemplate('@forum/admin/groups/add_edit_group')->display();
        }
    }
}
