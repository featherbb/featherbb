<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace controller\admin;

class groups
{
    public function __construct()
    {
        $this->feather = \Slim\Slim::getInstance();
        $this->start = $this->feather->start;
        $this->config = $this->feather->config;
        $this->user = $this->feather->user;
        $this->request = $this->feather->request;
        $this->model = new \model\admin\groups();
        load_textdomain('featherbb', FEATHER_ROOT.'lang/'.$this->user->language.'/admin/groups.mo');
        require FEATHER_ROOT . 'include/common_admin.php';
    }

    public function __autoload($class_name)
    {
        require FEATHER_ROOT . $class_name . '.php';
    }

    public function display()
    {
        if ($this->user->g_id != FEATHER_ADMIN) {
            message(__('No permission'), '403');
        }

        $groups = $this->model->fetch_groups();

        // Set default group
        if ($this->feather->request->isPost()) {
            $this->model->set_default_group($groups, $this->feather);
        }

        generate_admin_menu('groups');

        $this->feather->view2->setPageInfo(array(
                'title' => array(feather_escape($this->config['o_board_title']), __('Admin'), __('User groups')),
                'active_page' => 'admin',
                'admin_console' => true,
                'groups' => $this->model->fetch_groups(),
                'cur_index' => 5,
            )
        )->addTemplate('admin/groups/admin_groups.php')->display();
    }

    public function delete($id)
    {
        if ($this->user->g_id != FEATHER_ADMIN) {
            message(__('No permission'), '403');
        }

        if ($id < 5) {
            message(__('Bad request'), '404');
        }

        // Make sure we don't remove the default group
        if ($id == $this->config['o_default_user_group']) {
            message(__('Cannot remove default message'));
        }

        // Check if this group has any members
        $is_member = $this->model->check_members($id);

        // If the group doesn't have any members or if we've already selected a group to move the members to
        if (!$is_member || $this->request->post('del_group')) {
            if ($this->request->post('del_group_comply') || $this->request->post('del_group')) {
                $this->model->delete_group($id);
            } else {
                generate_admin_menu('groups');

                $this->feather->view2->setPageInfo(array(
                        'title' => array(feather_escape($this->config['o_board_title']), __('Admin'), __('User groups')),
                        'active_page' => 'admin',
                        'admin_console' => true,
                        'group_title'      =>  $this->model->get_group_title($id),
                        'id'    => $id,
                    )
                )->addTemplate('admin/groups/confirm_delete.php')->display();
            }
        }

        generate_admin_menu('groups');

        $this->feather->view2->setPageInfo(array(
                'title' => array(feather_escape($this->config['o_board_title']), __('Admin'), __('User groups')),
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
        if ($this->user->g_id != FEATHER_ADMIN) {
            message(__('No permission'), '403');
        }

        $groups = $this->model->fetch_groups();

        // Add/edit a group (stage 2)
        if ($this->request->post('add_edit_group')) {
            $this->model->add_edit_group($groups, $this->feather);
        }

        // Add/edit a group (stage 1)
        elseif ($this->request->post('add_group') || isset($id)) {

            generate_admin_menu('groups');

            $group = $this->model->info_add_group($groups, $id);

            $this->feather->view2->setPageInfo(array(
                    'title' => array(feather_escape($this->config['o_board_title']), __('Admin'), __('User groups')),
                    'active_page' => 'admin',
                    'admin_console' => true,
                    'focus_element' => array('groups2', 'req_title'),
                    'required_fields' => array('req_title' => __('Group title label')),
                    'group'    =>    $group,
                    'groups'    =>    $groups,
                    'id'    => $id,
                    'group_list'    => $this->model->get_group_list($groups, $group),
                )
            )->addTemplate('admin/groups/delete_group.php')->display();
        }
    }
}
