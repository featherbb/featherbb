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
        $this->db = $this->feather->db;
        $this->start = $this->feather->start;
        $this->config = $this->feather->config;
        $this->user = $this->feather->user;
    }
    
    public function display()
    {
        global $lang_common, $lang_admin_common, $lang_admin_groups, $feather_config, $feather_user, $db;

        require FEATHER_ROOT.'include/common_admin.php';

        if ($feather_user['g_id'] != FEATHER_ADMIN) {
            message($lang_common['No permission'], false, '403 Forbidden');
        }

        define('FEATHER_ADMIN_CONSOLE', 1);

        // Load the admin_groups.php language file
        require FEATHER_ROOT.'lang/'.$admin_language.'/groups.php';

        // Load the groups.php model file
        require FEATHER_ROOT.'model/admin/groups.php';

        $groups = fetch_groups();

        // Set default group
        if ($this->feather->request->isPost()) {
            set_default_group($groups, $this->feather);
        }

        $page_title = array(pun_htmlspecialchars($feather_config['o_board_title']), $lang_admin_common['Admin'], $lang_admin_common['User groups']);

        define('FEATHER_ACTIVE_PAGE', 'admin');

        require FEATHER_ROOT.'include/header.php';

        generate_admin_menu('groups');

        $this->feather->render('admin/groups/admin_groups.php', array(
                'lang_admin_groups'    =>    $lang_admin_groups,
                'lang_admin_common'    =>    $lang_admin_common,
                'feather_config'    =>    $feather_config,
                'groups' => $groups,
                'cur_index' => 5,
            )
        );

        require FEATHER_ROOT.'include/footer.php';
    }

    public function delete($id)
    {
        global $lang_common, $lang_admin_common, $lang_admin_groups, $feather_config, $feather_user, $db;

        require FEATHER_ROOT . 'include/common_admin.php';

        if ($feather_user['g_id'] != FEATHER_ADMIN) {
            message($lang_common['No permission'], false, '403 Forbidden');
        }

        define('FEATHER_ADMIN_CONSOLE', 1);

        // Load the admin_groups.php language file
        require FEATHER_ROOT . 'lang/' . $admin_language . '/groups.php';

        // Load the groups.php model file
        require FEATHER_ROOT . 'model/admin/groups.php';

        confirm_referrer(array(
            get_link_r('admin/groups/'),
            get_link_r('admin/groups/delete/'.$id.'/'),
        ));

        if ($id < 5) {
            message($lang_common['Bad request'], false, '404 Not Found');
        }

        // Make sure we don't remove the default group
        if ($id == $feather_config['o_default_user_group']) {
            message($lang_admin_groups['Cannot remove default message']);
        }

        // Check if this group has any members
        $is_member = check_members($id);

        // If the group doesn't have any members or if we've already selected a group to move the members to
        if (!$is_member || isset($_POST['del_group'])) {
            if ($this->feather->request->post('del_group_comply') || $this->feather->request->post('del_group')) {
                delete_group($this->feather, $id);
            } else {
                $group_title = get_group_title($id);

                $page_title = array(pun_htmlspecialchars($feather_config['o_board_title']), $lang_admin_common['Admin'], $lang_admin_common['User groups']);

                define('FEATHER_ACTIVE_PAGE', 'admin');

                require FEATHER_ROOT.'include/header.php';

                generate_admin_menu('groups');

                $this->feather->render('admin/groups/confirm_delete.php', array(
                        'lang_admin_groups'    =>    $lang_admin_groups,
                        'lang_admin_common'    =>    $lang_admin_common,
                        'group_title'      =>  $group_title,
                        'id'    => $id,
                    )
                );

                require FEATHER_ROOT.'include/footer.php';
            }
        }

        $page_title = array(pun_htmlspecialchars($feather_config['o_board_title']), $lang_admin_common['Admin'], $lang_admin_common['User groups']);

        define('FEATHER_ACTIVE_PAGE', 'admin');

        require FEATHER_ROOT.'include/header.php';

        generate_admin_menu('groups');

        $this->feather->render('admin/groups/delete_group.php', array(
                'lang_admin_groups'    =>    $lang_admin_groups,
                'lang_admin_common'    =>    $lang_admin_common,
                'id'    => $id,
                'group_info'      =>  get_title_members($id),
            )
        );

        require FEATHER_ROOT.'include/footer.php';
    }

    public function addedit($id = '')
    {
        global $lang_common, $lang_admin_common, $lang_admin_groups, $feather_config, $feather_user, $db;

        require FEATHER_ROOT.'include/common_admin.php';

        if ($feather_user['g_id'] != FEATHER_ADMIN) {
            message($lang_common['No permission'], false, '403 Forbidden');
        }

        define('FEATHER_ADMIN_CONSOLE', 1);

        // Load the admin_groups.php language file
        require FEATHER_ROOT.'lang/'.$admin_language.'/groups.php';

        // Load the groups.php model file
        require FEATHER_ROOT.'model/admin/groups.php';

        $groups = fetch_groups();

        // Add/edit a group (stage 2)
        if ($this->feather->request->post('add_edit_group')) {
            add_edit_group($groups, $this->feather);
        }

        // Add/edit a group (stage 1)
        elseif ($this->feather->request->post('add_group') || isset($id)) {
            $page_title = array(pun_htmlspecialchars($feather_config['o_board_title']), $lang_admin_common['Admin'], $lang_admin_common['User groups']);
            $required_fields = array('req_title' => $lang_admin_groups['Group title label']);
            $focus_element = array('groups2', 'req_title');

            define('FEATHER_ACTIVE_PAGE', 'admin');

            require FEATHER_ROOT.'include/header.php';

            generate_admin_menu('groups');

            $this->feather->render('admin/groups/add_edit_group.php', array(
                    'lang_admin_groups'    =>    $lang_admin_groups,
                    'lang_admin_common'    =>    $lang_admin_common,
                    'feather_config'    =>    $feather_config,
                    'group'    =>    info_add_group($groups, $this->feather, $id),
                    'groups'    =>    $groups,
                    'id'    => $id,
                )
            );

            require FEATHER_ROOT.'include/footer.php';
        }
    }
}
