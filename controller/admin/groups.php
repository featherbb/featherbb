<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace controller\admin{
    
    class Groups{

        function display(){

            global $feather, $lang_common, $lang_admin_common, $lang_admin_groups, $pun_config, $pun_user, $pun_start, $db;

            require PUN_ROOT.'include/common_admin.php';

            if ($pun_user['g_id'] != PUN_ADMIN) {
                message($lang_common['No permission'], false, '403 Forbidden');
            }

            define('PUN_ADMIN_CONSOLE', 1);

            // Load the admin_groups.php language file
            require PUN_ROOT.'lang/'.$admin_language.'/groups.php';

            // Load the groups.php model file
            require PUN_ROOT.'model/admin/groups.php';

            $groups = fetch_groups();

            // Set default group
            if ($feather->request->isPost()) {
                set_default_group($groups, $feather);
            }

            $page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), $lang_admin_common['Admin'], $lang_admin_common['User groups']);
            if (!defined('PUN_ACTIVE_PAGE')) {
                define('PUN_ACTIVE_PAGE', 'admin');
            }
            require PUN_ROOT.'include/header.php';

            $feather->render('header.php', array(
                    'lang_common' => $lang_common,
                    'page_title' => $page_title,
                    'pun_user' => $pun_user,
                    'pun_config' => $pun_config,
                    '_SERVER'	=>	$_SERVER,
                    'navlinks'		=>	$navlinks,
                    'page_info'		=>	$page_info,
                    'db'		=>	$db,
                    'p'		=>	'',
                )
            );

            generate_admin_menu('groups');

            $feather->render('admin/groups/admin_groups.php', array(
                    'lang_admin_groups'	=>	$lang_admin_groups,
                    'lang_admin_common'	=>	$lang_admin_common,
                    'pun_config'	=>	$pun_config,
                    'groups' => $groups,
                    'cur_index' => 5,
                )
            );

            $feather->render('footer.php', array(
                    'lang_common' => $lang_common,
                    'pun_user' => $pun_user,
                    'pun_config' => $pun_config,
                    'pun_start' => $pun_start,
                    'footer_style' => 'index',
                )
            );

            require PUN_ROOT.'include/footer.php';

    }

        function delete($id) {

            global $feather, $lang_common, $lang_admin_common, $lang_admin_groups, $pun_config, $pun_user, $pun_start, $db;

            require PUN_ROOT . 'include/common_admin.php';

            if ($pun_user['g_id'] != PUN_ADMIN) {
                message($lang_common['No permission'], false, '403 Forbidden');
            }

            define('PUN_ADMIN_CONSOLE', 1);

            // Load the admin_groups.php language file
            require PUN_ROOT . 'lang/' . $admin_language . '/groups.php';

            // Load the groups.php model file
            require PUN_ROOT . 'model/admin/groups.php';

            confirm_referrer(array(
                get_link_r('admin/groups/'),
                get_link_r('admin/groups/delete/'.$id.'/'),
            ));

            if ($id < 5) {
                message($lang_common['Bad request'], false, '404 Not Found');
            }

            // Make sure we don't remove the default group
            if ($id == $pun_config['o_default_user_group']) {
                message($lang_admin_groups['Cannot remove default message']);
            }

            // Check if this group has any members
            $is_member = check_members($id);

            // If the group doesn't have any members or if we've already selected a group to move the members to
            if (!$is_member || isset($_POST['del_group']))
            {
                if (!empty($feather->request->post('del_group_comply')) || !empty($feather->request->post('del_group'))) {
                    delete_group($feather, $id);
                }
                else {

                    $group_title = get_group_title($id);

                    $page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), $lang_admin_common['Admin'], $lang_admin_common['User groups']);
                    if (!defined('PUN_ACTIVE_PAGE')) {
                        define('PUN_ACTIVE_PAGE', 'admin');
                    }
                    require PUN_ROOT.'include/header.php';

                    $feather->render('header.php', array(
                            'lang_common' => $lang_common,
                            'page_title' => $page_title,
                            'pun_user' => $pun_user,
                            'pun_config' => $pun_config,
                            '_SERVER'	=>	$_SERVER,
                            'navlinks'		=>	$navlinks,
                            'page_info'		=>	$page_info,
                            'db'		=>	$db,
                            'p'		=>	'',
                        )
                    );

                    generate_admin_menu('groups');

                    $feather->render('admin/groups/confirm_delete.php', array(
                            'lang_admin_groups'	=>	$lang_admin_groups,
                            'lang_admin_common'	=>	$lang_admin_common,
                            'group_title'      =>  $group_title,
                            'id'    => $id,
                        )
                    );

                    $feather->render('footer.php', array(
                            'lang_common' => $lang_common,
                            'pun_user' => $pun_user,
                            'pun_config' => $pun_config,
                            'pun_start' => $pun_start,
                            'footer_style' => 'index',
                        )
                    );

                    require PUN_ROOT.'include/footer.php';

                    $feather->stop();
                }
            }

            $page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), $lang_admin_common['Admin'], $lang_admin_common['User groups']);
            if (!defined('PUN_ACTIVE_PAGE')) {
                define('PUN_ACTIVE_PAGE', 'admin');
            }
            require PUN_ROOT.'include/header.php';

            $feather->render('header.php', array(
                    'lang_common' => $lang_common,
                    'page_title' => $page_title,
                    'pun_user' => $pun_user,
                    'pun_config' => $pun_config,
                    '_SERVER'	=>	$_SERVER,
                    'navlinks'		=>	$navlinks,
                    'page_info'		=>	$page_info,
                    'db'		=>	$db,
                    'p'		=>	'',
                )
            );

            generate_admin_menu('groups');

            $feather->render('admin/groups/delete_group.php', array(
                    'lang_admin_groups'	=>	$lang_admin_groups,
                    'lang_admin_common'	=>	$lang_admin_common,
                    'id'    => $id,
                    'group_info'      =>  get_title_members($id),
                )
            );

            $feather->render('footer.php', array(
                    'lang_common' => $lang_common,
                    'pun_user' => $pun_user,
                    'pun_config' => $pun_config,
                    'pun_start' => $pun_start,
                    'footer_style' => 'index',
                )
            );

            require PUN_ROOT.'include/footer.php';
        }

        function addedit($id = ''){

            global $feather, $lang_common, $lang_admin_common, $lang_admin_groups, $pun_config, $pun_user, $pun_start, $db;

            require PUN_ROOT.'include/common_admin.php';

            if ($pun_user['g_id'] != PUN_ADMIN) {
                message($lang_common['No permission'], false, '403 Forbidden');
            }

            define('PUN_ADMIN_CONSOLE', 1);

            // Load the admin_groups.php language file
            require PUN_ROOT.'lang/'.$admin_language.'/groups.php';

            // Load the groups.php model file
            require PUN_ROOT.'model/admin/groups.php';

            $groups = fetch_groups();

            // Add/edit a group (stage 2)
            if (!empty($feather->request->post('add_edit_group'))) {
                add_edit_group($groups, $feather);
            }

            // Add/edit a group (stage 1)
            else if (!empty($feather->request->post('add_group')) || isset($id)) {

                $page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), $lang_admin_common['Admin'], $lang_admin_common['User groups']);
                $required_fields = array('req_title' => $lang_admin_groups['Group title label']);
                $focus_element = array('groups2', 'req_title');
                if (!defined('PUN_ACTIVE_PAGE')) {
                    define('PUN_ACTIVE_PAGE', 'admin');
                }
                require PUN_ROOT.'include/header.php';

                $feather->render('header.php', array(
                        'lang_common' => $lang_common,
                        'page_title' => $page_title,
                        'pun_user' => $pun_user,
                        'pun_config' => $pun_config,
                        '_SERVER'	=>	$_SERVER,
                        'navlinks'		=>	$navlinks,
                        'page_info'		=>	$page_info,
                        'db'		=>	$db,
                        'p'		=>	'',
                        'focus_element'		=>	$focus_element,
                        'required_fields'		=>	$required_fields,
                    )
                );

                generate_admin_menu('groups');

                $feather->render('admin/groups/add_edit_group.php', array(
                        'lang_admin_groups'	=>	$lang_admin_groups,
                        'lang_admin_common'	=>	$lang_admin_common,
                        'pun_config'	=>	$pun_config,
                        'group'	=>	info_add_group($groups, $feather, $id),
                        'groups'	=>	$groups,
                        'id'    => $id,
                    )
                );

                $feather->render('footer.php', array(
                        'lang_common' => $lang_common,
                        'pun_user' => $pun_user,
                        'pun_config' => $pun_config,
                        'pun_start' => $pun_start,
                        'footer_style' => 'index',
                    )
                );

                require PUN_ROOT.'include/footer.php';
            }



        }

    }
}