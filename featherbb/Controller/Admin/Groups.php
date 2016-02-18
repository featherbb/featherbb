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
        $this->feather = \Slim\Slim::getInstance();
        $this->start = $this->feather->start;
        $this->config = $this->feather->config;
        $this->user = Container::get('user');
        $this->request = $this->feather->request;
        $this->model = new \FeatherBB\Model\Admin\Groups();
        load_textdomain('featherbb', Container::get('forum_env')['FEATHER_ROOT'].'featherbb/lang/'.$this->user->language.'/admin/groups.mo');
    }

    public function display()
    {
        Container::get('hooks')->fire('controller.admin.groups.display');

        $groups = $this->model->fetch_groups();

        // Set default group
        if ($this->feather->request->isPost()) {
            $this->model->set_default_group($groups);
        }

        AdminUtils::generateAdminMenu('groups');

        View::setPageInfo(array(
                'title' => array(Utils::escape($this->config['o_board_title']), __('Admin'), __('User groups')),
                'active_page' => 'admin',
                'admin_console' => true,
                'groups' => $groups,
                'cur_index' => 5,
            )
        )->addTemplate('admin/groups/admin_groups.php')->display();
    }

    public function delete($id)
    {
        Container::get('hooks')->fire('controller.admin.groups.delete');

        if ($id < 5) {
            throw new Error(__('Bad request'), 403);
        }

        // Make sure we don't remove the default group
        if ($id == $this->config['o_default_user_group']) {
            throw new Error(__('Cannot remove default message'), 403);
        }

        // Check if this group has any members
        $is_member = $this->model->check_members($id);

        // If the group doesn't have any members or if we've already selected a group to move the members to
        if (!$is_member || $this->request->post('del_group')) {
            if ($this->request->post('del_group_comply') || $this->request->post('del_group')) {
                $this->model->delete_group($id);
            } else {
                AdminUtils::generateAdminMenu('groups');

                View::setPageInfo(array(
                        'title' => array(Utils::escape($this->config['o_board_title']), __('Admin'), __('User groups')),
                        'active_page' => 'admin',
                        'admin_console' => true,
                        'group_title'      =>  $this->model->get_group_title($id),
                        'id'    => $id,
                    )
                )->addTemplate('admin/groups/confirm_delete.php')->display();
            }
        }

        AdminUtils::generateAdminMenu('groups');

        View::setPageInfo(array(
                'title' => array(Utils::escape($this->config['o_board_title']), __('Admin'), __('User groups')),
                'active_page' => 'admin',
                'admin_console' => true,
                'id'    => $id,
                'group_info'      =>  $this->model->get_title_members($id),
                'group_list_delete'      =>  $this->model->get_group_list_delete($id),
            )
        )->addTemplate('admin/groups/delete_group.php')->display();
    }

    public function addedit($id = '')
    {
        Container::get('hooks')->fire('controller.admin.groups.addedit');

        $groups = $this->model->fetch_groups();

        // Add/edit a group (stage 2)
        if ($this->request->post('add_edit_group')) {
            $this->model->add_edit_group($groups);
        }

        // Add/edit a group (stage 1)
        elseif ($this->request->post('add_group') || isset($id)) {

            AdminUtils::generateAdminMenu('groups');

            $group = $this->model->info_add_group($groups, $id);

            View::setPageInfo(array(
                    'title' => array(Utils::escape($this->config['o_board_title']), __('Admin'), __('User groups')),
                    'active_page' => 'admin',
                    'admin_console' => true,
                    'focus_element' => array('groups2', 'req_title'),
                    'required_fields' => array('req_title' => __('Group title label')),
                    'group'    =>    $group,
                    'groups'    =>    $groups,
                    'id'    => $id,
                    'group_list'    => $this->model->get_group_list($groups, $group),
                )
            )->addTemplate('admin/groups/add_edit_group.php')->display();
        }
    }
}
