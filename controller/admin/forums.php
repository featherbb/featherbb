<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace controller\admin;

class forums
{
    public function display()
    {
        global $feather, $lang_common, $lang_admin_common, $lang_admin_forums, $feather_config, $feather_user, $feather_start, $db;

        require FEATHER_ROOT . 'include/common_admin.php';

        if ($feather_user['g_id'] != PUN_ADMIN) {
            message($lang_common['No permission'], false, '403 Forbidden');
        }

        define('PUN_ADMIN_CONSOLE', 1);

        // Load the admin_options.php language file
        require FEATHER_ROOT . 'lang/' . $admin_language . '/forums.php';

        // Load the forums.php model file
        require FEATHER_ROOT . 'model/admin/forums.php';

        // Add a "default" forum
        if ($feather->request->post('add_forum')) {
            add_forum($feather);
        }  // Update forum positions
        elseif ($feather->request->post('update_positions')) {
            update_positions($feather);
        }

        $page_title = array(pun_htmlspecialchars($feather_config['o_board_title']), $lang_admin_common['Admin'], $lang_admin_common['Forums']);
        if (!defined('PUN_ACTIVE_PAGE')) {
            define('PUN_ACTIVE_PAGE', 'admin');
        }
        require FEATHER_ROOT . 'include/header.php';

        $feather->render('header.php', array(
                'lang_common' => $lang_common,
                'page_title' => $page_title,
                'feather_user' => $feather_user,
                'feather_config' => $feather_config,
                '_SERVER' => $_SERVER,
                'navlinks' => $navlinks,
                'page_info' => $page_info,
                'db' => $db,
                'p' => '',
            )
        );

        generate_admin_menu('forums');

        $feather->render('admin/forums/admin_forums.php', array(
                'lang_admin_forums' => $lang_admin_forums,
                'lang_admin_common' => $lang_admin_common,
                'feather_config' => $feather_config,
                'is_forum' => check_forums(),
                'forum_data'    =>  get_forums(),
                'cur_index'     =>  4,
                'cur_category' => 0,
            )
        );

        $feather->render('footer.php', array(
                'lang_common' => $lang_common,
                'feather_user' => $feather_user,
                'feather_config' => $feather_config,
                'feather_start' => $feather_start,
                'footer_style' => 'index',
            )
        );

        require FEATHER_ROOT . 'include/footer.php';
    }


    public function edit($id)
    {
        global $feather, $lang_common, $lang_admin_common, $lang_admin_forums, $feather_config, $feather_user, $feather_start, $db;

        require FEATHER_ROOT . 'include/common_admin.php';

        if ($feather_user['g_id'] != PUN_ADMIN) {
            message($lang_common['No permission'], false, '403 Forbidden');
        }

        define('PUN_ADMIN_CONSOLE', 1);

        // Load the admin_options.php language file
        require FEATHER_ROOT . 'lang/' . $admin_language . '/forums.php';

        // Load the forums.php model file
        require FEATHER_ROOT . 'model/admin/forums.php';

        // Update forum

        // Update group permissions for $forum_id
        if ($feather->request->post('save')) {
            update_permissions($feather, $id);
        } elseif ($feather->request->post('revert_perms')) {
            revert_permissions($id);
        }

        // Fetch forum info
        $cur_forum = get_forum_info($id);

        $page_title = array(pun_htmlspecialchars($feather_config['o_board_title']), $lang_admin_common['Admin'], $lang_admin_common['Forums']);
        if (!defined('PUN_ACTIVE_PAGE')) {
            define('PUN_ACTIVE_PAGE', 'admin');
        }
        require FEATHER_ROOT . 'include/header.php';

        $feather->render('header.php', array(
                'lang_common' => $lang_common,
                'page_title' => $page_title,
                'feather_user' => $feather_user,
                'feather_config' => $feather_config,
                '_SERVER' => $_SERVER,
                'navlinks' => $navlinks,
                'page_info' => $page_info,
                'db' => $db,
                'p' => '',
            )
        );

        generate_admin_menu('forums');

        $feather->render('admin/forums/permissions.php', array(
                'lang_admin_forums' => $lang_admin_forums,
                'lang_admin_common' => $lang_admin_common,
                'feather_config' => $feather_config,
                'perm_data' => get_permissions($id),
                'cur_index'     =>  7,
                'cur_forum' => get_forum_info($id),
                'forum_id'  =>  $id,
            )
        );

        $feather->render('footer.php', array(
                'lang_common' => $lang_common,
                'feather_user' => $feather_user,
                'feather_config' => $feather_config,
                'feather_start' => $feather_start,
                'footer_style' => 'index',
            )
        );

        require FEATHER_ROOT . 'include/footer.php';
    }

    public function delete($id)
    {
        global $feather, $lang_common, $lang_admin_common, $lang_admin_forums, $feather_config, $feather_user, $feather_start, $db;

        require FEATHER_ROOT . 'include/common_admin.php';

        if ($feather_user['g_id'] != PUN_ADMIN) {
            message($lang_common['No permission'], false, '403 Forbidden');
        }

        define('PUN_ADMIN_CONSOLE', 1);

        // Load the admin_options.php language file
        require FEATHER_ROOT . 'lang/' . $admin_language . '/forums.php';

        // Load the report.php model file
        require FEATHER_ROOT . 'model/admin/forums.php';

        if ($feather->request->isPost()) { // Delete a forum with all posts
            delete_forum($id);
        } else {
            // If the user hasn't confirmed the delete

            $page_title = array(pun_htmlspecialchars($feather_config['o_board_title']), $lang_admin_common['Admin'], $lang_admin_common['Forums']);
            if (!defined('PUN_ACTIVE_PAGE')) {
                define('PUN_ACTIVE_PAGE', 'admin');
            }
            require FEATHER_ROOT . 'include/header.php';

            $feather->render('header.php', array(
                    'lang_common' => $lang_common,
                    'page_title' => $page_title,
                    'feather_user' => $feather_user,
                    'feather_config' => $feather_config,
                    '_SERVER' => $_SERVER,
                    'navlinks' => $navlinks,
                    'page_info' => $page_info,
                    'db' => $db,
                    'p' => '',
                )
            );

            generate_admin_menu('forums');

            $feather->render('admin/forums/delete_forum.php', array(
                    'lang_admin_forums' => $lang_admin_forums,
                    'lang_admin_common' => $lang_admin_common,
                    'forum_name' => get_forum_name($id),
                    'forum_id'  =>  $id,
                )
            );

            $feather->render('footer.php', array(
                    'lang_common' => $lang_common,
                    'feather_user' => $feather_user,
                    'feather_config' => $feather_config,
                    'feather_start' => $feather_start,
                    'footer_style' => 'index',
                )
            );

            require FEATHER_ROOT . 'include/footer.php';
        }
    }
}
