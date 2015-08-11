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
        $this->header = new \controller\header();
        $this->footer = new \controller\footer();
        $this->model = new \model\admin\groups();
    }

    public function __autoload($class_name)
    {
        require FEATHER_ROOT . $class_name . '.php';
    }
    
    public function display()
    {
        global $lang_common, $lang_admin_common, $lang_admin_groups;

        require FEATHER_ROOT.'include/common_admin.php';

        if ($this->user->g_id != FEATHER_ADMIN) {
            message($lang_common['No permission'], '403');
        }

        define('FEATHER_ADMIN_CONSOLE', 1);

        // Load the admin_groups.php language file
        require FEATHER_ROOT.'lang/'.$admin_language.'/groups.php';

        $groups = $this->model->fetch_groups();

        // Set default group
        if ($this->feather->request->isPost()) {
            $this->model->set_default_group($groups, $this->feather);
        }

        $page_title = array(feather_escape($this->config['o_board_title']), $lang_admin_common['Admin'], $lang_admin_common['User groups']);

        define('FEATHER_ACTIVE_PAGE', 'admin');

        $this->header->setTitle($page_title)->display();

        generate_admin_menu('groups');

        $this->feather->render('admin/groups/admin_groups.php', array(
                'lang_admin_groups'    =>    $lang_admin_groups,
                'lang_admin_common'    =>    $lang_admin_common,
                'feather_config'    =>    $this->config,
                'groups' => $groups,
                'cur_index' => 5,
            )
        );

        $this->footer->display();
    }

    public function delete($id)
    {
        global $lang_common, $lang_admin_common, $lang_admin_groups;

        require FEATHER_ROOT . 'include/common_admin.php';

        if ($this->user->g_id != FEATHER_ADMIN) {
            message($lang_common['No permission'], '403');
        }

        define('FEATHER_ADMIN_CONSOLE', 1);

        // Load the admin_groups.php language file
        require FEATHER_ROOT . 'lang/' . $admin_language . '/groups.php';

        if ($id < 5) {
            message($lang_common['Bad request'], '404');
        }

        // Make sure we don't remove the default group
        if ($id == $this->config['o_default_user_group']) {
            message($lang_admin_groups['Cannot remove default message']);
        }

        // Check if this group has any members
        $is_member = $this->model->check_members($id);

        // If the group doesn't have any members or if we've already selected a group to move the members to
        if (!$is_member || $this->request->post('del_group')) {
            if ($this->request->post('del_group_comply') || $this->request->post('del_group')) {
                $this->model->delete_group($id);
            } else {
                $group_title = $this->model->get_group_title($id);

                $page_title = array(feather_escape($this->config['o_board_title']), $lang_admin_common['Admin'], $lang_admin_common['User groups']);

                define('FEATHER_ACTIVE_PAGE', 'admin');

                $this->header->setTitle($page_title)->display();

                generate_admin_menu('groups');

                $this->feather->render('admin/groups/confirm_delete.php', array(
                        'lang_admin_groups'    =>    $lang_admin_groups,
                        'lang_admin_common'    =>    $lang_admin_common,
                        'group_title'      =>  $group_title,
                        'id'    => $id,
                    )
                );

                $this->footer->display();
            }
        }

        $page_title = array(feather_escape($this->config['o_board_title']), $lang_admin_common['Admin'], $lang_admin_common['User groups']);

        define('FEATHER_ACTIVE_PAGE', 'admin');

        $this->header->setTitle($page_title)->display();

        generate_admin_menu('groups');

        $this->feather->render('admin/groups/delete_group.php', array(
                'lang_admin_groups'    =>    $lang_admin_groups,
                'lang_admin_common'    =>    $lang_admin_common,
                'id'    => $id,
                'group_info'      =>  $this->model->get_title_members($id),
            )
        );

        $this->footer->display();
    }

    public function addedit($id = '')
    {
        global $lang_common, $lang_admin_common, $lang_admin_groups;

        require FEATHER_ROOT.'include/common_admin.php';

        if ($this->user->g_id != FEATHER_ADMIN) {
            message($lang_common['No permission'], '403');
        }

        define('FEATHER_ADMIN_CONSOLE', 1);

        // Load the admin_groups.php language file
        require FEATHER_ROOT.'lang/'.$admin_language.'/groups.php';

        $groups = $this->model->fetch_groups();

        // Add/edit a group (stage 2)
        if ($this->request->post('add_edit_group')) {
            $this->model->add_edit_group($groups, $this->feather);
        }

        // Add/edit a group (stage 1)
        elseif ($this->request->post('add_group') || isset($id)) {
            $page_title = array(feather_escape($this->config['o_board_title']), $lang_admin_common['Admin'], $lang_admin_common['User groups']);
            $required_fields = array('req_title' => $lang_admin_groups['Group title label']);
            $focus_element = array('groups2', 'req_title');

            define('FEATHER_ACTIVE_PAGE', 'admin');

            $this->header->setTitle($page_title)->setFocusElement($focus_element)->setRequiredFields($required_fields)->display();

            generate_admin_menu('groups');

            $group = $this->model->info_add_group($groups, $id);

            $this->feather->render('admin/groups/add_edit_group.php', array(
                    'lang_admin_groups'    =>    $lang_admin_groups,
                    'lang_admin_common'    =>    $lang_admin_common,
                    'feather_config'    =>    $this->config,
                    'group'    =>    $group,
                    'groups'    =>    $groups,
                    'id'    => $id,
                    'group_list'    => $this->model->get_group_list($groups, $group),
                )
            );

            $this->footer->display();
        }
    }
}
