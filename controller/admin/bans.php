<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace controller\admin {

    class Bans
    {

        function display()
        {

            global $feather, $lang_common, $lang_admin_common, $lang_admin_index, $pun_config, $pun_user, $pun_start, $db;

            define('PUN_ADMIN_CONSOLE', 1);

            require PUN_ROOT . 'include/common_admin.php';

            if ($pun_user['g_id'] != PUN_ADMIN && ($pun_user['g_moderator'] != '1' || $pun_user['g_mod_ban_users'] == '0')) {
                message($lang_common['No permission'], false, '403 Forbidden');
            }

            // Load the admin_bans.php language file
            require PUN_ROOT . 'lang/' . $admin_language . '/bans.php';

            // Load the bans.php model file
            require PUN_ROOT . 'model/admin/bans.php';

            // Display bans
            if (!empty($feather->request->get('find_ban'))) {
                $ban_info = find_ban($feather);

                // Determine the ban offset (based on $_GET['p'])
                $num_pages = ceil($ban_info['num_bans'] / 50);

                $p = (empty($feather->request->get('p')) || $feather->request->get('p') <= 1 || $feather->request->get('p') > $num_pages) ? 1 : intval($feather->request->get('p'));
                $start_from = 50 * ($p - 1);

                // Generate paging links
                $paging_links = '<span class="pages-label">' . $lang_common['Pages'] . ' </span>' . paginate($num_pages, $p, 'admin_bans.php?find_ban=&amp;' . implode('&amp;', $ban_info['query_str']));

                $page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), $lang_admin_common['Admin'], $lang_admin_common['Bans'], $lang_admin_bans['Results head']);
                if (!defined('PUN_ACTIVE_PAGE')) {
                    define('PUN_ACTIVE_PAGE', 'admin');
                }
                require PUN_ROOT . 'include/header.php';

                $feather->render('header.php', array(
                        'lang_common' => $lang_common,
                        'page_title' => $page_title,
                        'pun_user' => $pun_user,
                        'pun_config' => $pun_config,
                        '_SERVER' => $_SERVER,
                        'navlinks' => $navlinks,
                        'page_info' => $page_info,
                        'focus_element' => '',
                        'db' => $db,
                        'p' => $p,
                        'paging_links' => $paging_links,
                    )
                );

                $feather->render('admin/bans/search_ban.php', array(
                        'lang_admin_bans' => $lang_admin_bans,
                        'lang_admin_common' => $lang_admin_common,
                        'ban_data' => print_bans($ban_info['conditions'], $ban_info['order_by'], $ban_info['direction'], $start_from),
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

                require PUN_ROOT . 'include/footer.php';

                $feather->stop();
            }

            $page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), $lang_admin_common['Admin'], $lang_admin_common['Bans']);
            $focus_element = array('bans', 'new_ban_user');

            if (!defined('PUN_ACTIVE_PAGE')) {
                define('PUN_ACTIVE_PAGE', 'admin');
            }
            require PUN_ROOT . 'include/header.php';

            $feather->render('header.php', array(
                    'lang_common' => $lang_common,
                    'page_title' => $page_title,
                    'pun_user' => $pun_user,
                    'pun_config' => $pun_config,
                    '_SERVER' => $_SERVER,
                    'navlinks' => $navlinks,
                    'page_info' => $page_info,
                    'focus_element' => $focus_element,
                    'db' => $db,
                    'p' => '',
                )
            );

            generate_admin_menu('index');

            $feather->render('admin/bans/admin_bans.php', array(
                    'lang_admin_bans' => $lang_admin_bans,
                    'lang_admin_common' => $lang_admin_common,
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

            require PUN_ROOT . 'include/footer.php';
        }

        function add()
        {
            global $feather, $lang_common, $lang_admin_common, $lang_admin_bans, $pun_config, $pun_user, $pun_start, $db;

            define('PUN_ADMIN_CONSOLE', 1);

            require PUN_ROOT . 'include/common_admin.php';

            if ($pun_user['g_id'] != PUN_ADMIN && ($pun_user['g_moderator'] != '1' || $pun_user['g_mod_ban_users'] == '0')) {
                message($lang_common['No permission'], false, '403 Forbidden');
            }

            // Load the admin_bans.php language file
            require PUN_ROOT . 'lang/' . $admin_language . '/bans.php';

            // Load the bans.php model file
            require PUN_ROOT . 'model/admin/bans.php';

            if (!empty($feather->request->post('add_edit_ban'))) {
                insert_ban($feather);
            }

            $page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), $lang_admin_common['Admin'], $lang_admin_common['Bans']);
            $focus_element = array('bans2', 'ban_user');

            if (!defined('PUN_ACTIVE_PAGE')) {
                define('PUN_ACTIVE_PAGE', 'admin');
            }
            require PUN_ROOT . 'include/header.php';

            $feather->render('header.php', array(
                    'lang_common' => $lang_common,
                    'page_title' => $page_title,
                    'pun_user' => $pun_user,
                    'pun_config' => $pun_config,
                    '_SERVER' => $_SERVER,
                    'navlinks' => $navlinks,
                    'page_info' => $page_info,
                    'focus_element' => $focus_element,
                    'db' => $db,
                    'p' => '',
                )
            );

            generate_admin_menu('bans');

            $feather->render('admin/bans/add_ban.php', array(
                    'lang_admin_bans' => $lang_admin_bans,
                    'lang_admin_common' => $lang_admin_common,
                    'ban' => add_ban_info($feather),
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

            require PUN_ROOT . 'include/footer.php';
        }

        function delete($id) {
            global $feather, $lang_common, $lang_admin_common, $pun_config, $lang_admin_bans, $pun_user, $pun_start, $db;

            require PUN_ROOT . 'include/common_admin.php';

            if ($pun_user['g_id'] != PUN_ADMIN && ($pun_user['g_moderator'] != '1' || $pun_user['g_mod_ban_users'] == '0')) {
                message($lang_common['No permission'], false, '403 Forbidden');
            }

            // Load the admin_bans.php language file
            require PUN_ROOT . 'lang/' . $admin_language . '/bans.php';

            // Load the bans.php model file
            require PUN_ROOT . 'model/admin/bans.php';

            // Remove the ban
            remove_ban($id);
        }

        function edit($id) {

            global $feather, $lang_common, $lang_admin_common, $lang_admin_bans, $pun_config, $pun_user, $pun_start, $db;

            define('PUN_ADMIN_CONSOLE', 1);

            require PUN_ROOT . 'include/common_admin.php';

            if ($pun_user['g_id'] != PUN_ADMIN && ($pun_user['g_moderator'] != '1' || $pun_user['g_mod_ban_users'] == '0')) {
                message($lang_common['No permission'], false, '403 Forbidden');
            }

            // Load the admin_bans.php language file
            require PUN_ROOT . 'lang/' . $admin_language . '/bans.php';

            // Load the bans.php model file
            require PUN_ROOT . 'model/admin/bans.php';

            if (!empty($feather->request->post('add_edit_ban'))) {
                insert_ban($feather);
            }

            $page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), $lang_admin_common['Admin'], $lang_admin_common['Bans']);
            $focus_element = array('bans2', 'ban_user');

            if (!defined('PUN_ACTIVE_PAGE')) {
                define('PUN_ACTIVE_PAGE', 'admin');
            }
            require PUN_ROOT . 'include/header.php';

            $feather->render('header.php', array(
                    'lang_common' => $lang_common,
                    'page_title' => $page_title,
                    'pun_user' => $pun_user,
                    'pun_config' => $pun_config,
                    '_SERVER' => $_SERVER,
                    'navlinks' => $navlinks,
                    'page_info' => $page_info,
                    'focus_element' => $focus_element,
                    'db' => $db,
                    'p' => '',
                )
            );

            generate_admin_menu('bans');

            $feather->render('admin/bans/add_ban.php', array(
                    'lang_admin_bans' => $lang_admin_bans,
                    'lang_admin_common' => $lang_admin_common,
                    'ban' => edit_ban_info($id),
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

            require PUN_ROOT . 'include/footer.php';
        }
    }
}