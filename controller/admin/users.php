<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace controller\admin {

    class Users
    {

        function display()
        {

            global $feather, $lang_common, $lang_admin_common, $lang_admin_users, $pun_config, $pun_user, $pun_start, $db;

            define('PUN_ADMIN_CONSOLE', 1);

            require PUN_ROOT . 'include/common_admin.php';

            if (!$pun_user['is_admmod']) {
                message($lang_common['No permission'], false, '403 Forbidden');
            }

            // Load the admin_bans.php language file
            require PUN_ROOT . 'lang/' . $admin_language . '/users.php';

            // Load the bans.php model file
            require PUN_ROOT . 'model/admin/users.php';

            // Move multiple users to other user groups
            if (!empty($feather->request->post('move_users')) || !empty($feather->request->post('move_users_comply'))) {

                if ($pun_user['g_id'] > PUN_ADMIN) {
                    message($lang_common['No permission'], false, '403 Forbidden');
                }

                $move = move_users($feather);

                $page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), $lang_admin_common['Admin'], $lang_admin_common['Users'], $lang_admin_users['Move users']);
                if (!defined('PUN_ACTIVE_PAGE')) {
                    define('PUN_ACTIVE_PAGE', 'moderate');
                }
                require PUN_ROOT.'include/header.php';

                $feather->render('header.php', array(
                        'lang_common' => $lang_common,
                        'page_title' => $page_title,
                        'p' => '',
                        'pun_user' => $pun_user,
                        'pun_config' => $pun_config,
                        '_SERVER'	=>	$_SERVER,
                        'page_head'		=>	'',
                        'navlinks'		=>	$navlinks,
                        'page_info'		=>	$page_info,
                        'db'		=>	$db,
                    )
                );

                generate_admin_menu('users');

                $feather->render('admin/users/move_users.php', array(
                        'lang_admin_users' => $lang_admin_users,
                        'lang_admin_common' => $lang_admin_common,
                        'move'              =>  $move,
                    )
                );

                $feather->render('footer.php', array(
                        'lang_common' => $lang_common,
                        'pun_user' => $pun_user,
                        'pun_config' => $pun_config,
                        'pun_start' => $pun_start,
                        'footer_style' => 'moderate',
                    )
                );

                require PUN_ROOT.'include/footer.php';

                $feather->stop();
            }


            // Delete multiple users
            if (!empty($feather->request->post('delete_users')) || !empty($feather->request->post('delete_users_comply'))) {

                if ($pun_user['g_id'] > PUN_ADMIN) {
                    message($lang_common['No permission'], false, '403 Forbidden');
                }

                $user_ids = delete_users($feather);

                $page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), $lang_admin_common['Admin'], $lang_admin_common['Users'], $lang_admin_users['Delete users']);
                if (!defined('PUN_ACTIVE_PAGE')) {
                    define('PUN_ACTIVE_PAGE', 'moderate');
                }
                require PUN_ROOT.'include/header.php';

                $feather->render('header.php', array(
                        'lang_common' => $lang_common,
                        'page_title' => $page_title,
                        'p' => '',
                        'pun_user' => $pun_user,
                        'pun_config' => $pun_config,
                        '_SERVER'	=>	$_SERVER,
                        'page_head'		=>	'',
                        'navlinks'		=>	$navlinks,
                        'page_info'		=>	$page_info,
                        'db'		=>	$db,
                    )
                );

                generate_admin_menu('users');

                $feather->render('admin/users/delete_users.php', array(
                        'lang_admin_users' => $lang_admin_users,
                        'lang_admin_common' => $lang_admin_common,
                        'user_ids'          => $user_ids,
                    )
                );

                $feather->render('footer.php', array(
                        'lang_common' => $lang_common,
                        'pun_user' => $pun_user,
                        'pun_config' => $pun_config,
                        'pun_start' => $pun_start,
                        'footer_style' => 'moderate',
                    )
                );

                require PUN_ROOT.'include/footer.php';

                $feather->stop();
            }


            // Ban multiple users
            if (!empty($feather->request->post('ban_users')) || !empty($feather->request->post('ban_users_comply'))) {

                if ($pun_user['g_id'] != PUN_ADMIN && ($pun_user['g_moderator'] != '1' || $pun_user['g_mod_ban_users'] == '0')) {
                    message($lang_common['No permission'], false, '403 Forbidden');
                }

                $user_ids = ban_users($feather);

                $page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), $lang_admin_common['Admin'], $lang_admin_common['Bans']);
                $focus_element = array('bans2', 'ban_message');
                if (!defined('PUN_ACTIVE_PAGE')) {
                    define('PUN_ACTIVE_PAGE', 'moderate');
                }
                require PUN_ROOT.'include/header.php';

                $feather->render('header.php', array(
                        'lang_common' => $lang_common,
                        'page_title' => $page_title,
                        'p' => '',
                        'pun_user' => $pun_user,
                        'pun_config' => $pun_config,
                        '_SERVER'	=>	$_SERVER,
                        'page_head'		=>	'',
                        'navlinks'		=>	$navlinks,
                        'page_info'		=>	$page_info,
                        'db'		=>	$db,
                    )
                );

                generate_admin_menu('users');

                $feather->render('admin/users/ban_users.php', array(
                        'lang_admin_users' => $lang_admin_users,
                        'lang_admin_common' => $lang_admin_common,
                        'user_ids'          => $user_ids,
                    )
                );

                $feather->render('footer.php', array(
                        'lang_common' => $lang_common,
                        'pun_user' => $pun_user,
                        'pun_config' => $pun_config,
                        'pun_start' => $pun_start,
                        'footer_style' => 'moderate',
                    )
                );

                require PUN_ROOT.'include/footer.php';

                $feather->stop();

            }

            // Display bans
            if (!empty($feather->request->get('find_user'))) {

                // Return conditions and query string for the URL
                $search = get_user_search($feather);

                // Fetch user count
                $num_users = get_num_users_search($search['conditions']);

                // Determine the user offset (based on $_GET['p'])
                $num_pages = ceil($num_users / 50);

                $p = (empty($feather->request->get('p')) || $feather->request->get('p') <= 1 || $feather->request->get('p') > $num_pages) ? 1 : intval($feather->request->get('p'));
                $start_from = 50 * ($p - 1);

                // Generate paging links
                $paging_links = '<span class="pages-label">' . $lang_common['Pages'] . ' </span>' . paginate($num_pages, $p, 'admin_users.php?find_user=&amp;'.implode('&amp;', $search['query_str']));

                // Some helper variables for permissions
                $can_delete = $can_move = $pun_user['g_id'] == PUN_ADMIN;
                $can_ban = $pun_user['g_id'] == PUN_ADMIN || ($pun_user['g_moderator'] == '1' && $pun_user['g_mod_ban_users'] == '1');
                $can_action = ($can_delete || $can_ban || $can_move) && $num_users > 0;

                $page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), $lang_admin_common['Admin'], $lang_admin_common['Users'], $lang_admin_users['Results head']);
                $page_head = array('js' => '<script type="text/javascript" src="'.get_base_url().'/include/common.js"></script>');
                if (!defined('PUN_ACTIVE_PAGE')) {
                    define('PUN_ACTIVE_PAGE', 'admin');
                }
                require PUN_ROOT . 'include/header.php';

                $feather->render('header.php', array(
                        'lang_common' => $lang_common,
                        'page_title' => $page_title,
                        'page_head' =>  $page_head,
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

                $feather->render('admin/users/find_users.php', array(
                        'lang_admin_users' => $lang_admin_users,
                        'lang_admin_common' => $lang_admin_common,
                        'search' => $search,
                        'start_from' => $start_from,
                        'can_delete' => $can_delete,
                        'can_ban' => $can_ban,
                        'can_action' => $can_action,
                        'can_move' => $can_move,
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

            $page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), $lang_admin_common['Admin'], $lang_admin_common['Users']);
            $focus_element = array('find_user', 'form[username]');

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

            generate_admin_menu('users');

            $feather->render('admin/users/admin_users.php', array(
                    'lang_admin_users' => $lang_admin_users,
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

            $feather->stop();
        }

        // Show IP statistics for a certain user ID
        function ipstats($id)
        {

            global $feather, $lang_common, $lang_admin_common, $lang_admin_users, $pun_config, $pun_user, $pun_start, $db;

            define('PUN_ADMIN_CONSOLE', 1);

            require PUN_ROOT . 'include/common_admin.php';

            if (!$pun_user['is_admmod']) {
                message($lang_common['No permission'], false, '403 Forbidden');
            }

            // Load the admin_bans.php language file
            require PUN_ROOT . 'lang/' . $admin_language . '/users.php';

            // Load the bans.php model file
            require PUN_ROOT . 'model/admin/users.php';

            // Fetch ip count
            $num_ips = get_num_ip($id);

            // Determine the ip offset (based on $_GET['p'])
            $num_pages = ceil($num_ips / 50);

            $p = (empty($feather->request->get('p')) || $feather->request->get('p') <= 1 || $feather->request->get('p') > $num_pages) ? 1 : intval($feather->request->get('p'));
            $start_from = 50 * ($p - 1);

            // Generate paging links
            $paging_links = '<span class="pages-label">'.$lang_common['Pages'].' </span>'.paginate($num_pages, $p, 'admin_users.php?ip_stats='.$id);

            $page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), $lang_admin_common['Admin'], $lang_admin_common['Users'], $lang_admin_users['Results head']);
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
                    'paging_links' => $paging_links,
                    'db' => $db,
                    'p' => '',
                )
            );

            $feather->render('admin/users/search_ip.php', array(
                    'lang_admin_users' => $lang_admin_users,
                    'lang_admin_common' => $lang_admin_common,
                    'start_from'        =>  $start_from,
                    'ip_data'   =>  get_ip_stats($id, $start_from),
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

        // Show IP statistics for a certain user IP
        function showusers($ip)
        {

            global $feather, $lang_common, $lang_admin_common, $lang_admin_users, $pun_config, $pun_user, $pun_start, $db;

            define('PUN_ADMIN_CONSOLE', 1);

            require PUN_ROOT . 'include/common_admin.php';

            if (!$pun_user['is_admmod']) {
                message($lang_common['No permission'], false, '403 Forbidden');
            }

            // Load the admin_bans.php language file
            require PUN_ROOT . 'lang/' . $admin_language . '/users.php';

            // Load the bans.php model file
            require PUN_ROOT . 'model/admin/users.php';

            if (!@preg_match('%^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}$%', $ip) && !@preg_match('%^((([0-9A-Fa-f]{1,4}:){7}[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){6}:[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){5}:([0-9A-Fa-f]{1,4}:)?[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){4}:([0-9A-Fa-f]{1,4}:){0,2}[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){3}:([0-9A-Fa-f]{1,4}:){0,3}[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){2}:([0-9A-Fa-f]{1,4}:){0,4}[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){6}((\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b)\.){3}(\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b))|(([0-9A-Fa-f]{1,4}:){0,5}:((\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b)\.){3}(\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b))|(::([0-9A-Fa-f]{1,4}:){0,5}((\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b)\.){3}(\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b))|([0-9A-Fa-f]{1,4}::([0-9A-Fa-f]{1,4}:){0,5}[0-9A-Fa-f]{1,4})|(::([0-9A-Fa-f]{1,4}:){0,6}[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){1,7}:))$%', $ip)) {
                message($lang_admin_users['Bad IP message']);
            }

            // Fetch user count
            $num_users = get_num_users_ip($ip);

            // Determine the user offset (based on $_GET['p'])
            $num_pages = ceil($num_users / 50);

            $p = (empty($feather->request->get('p')) || $feather->request->get('p') <= 1 || $feather->request->get('p') > $num_pages) ? 1 : intval($feather->request->get('p'));
            $start_from = 50 * ($p - 1);

            // Generate paging links
            $paging_links = '<span class="pages-label">'.$lang_common['Pages'].' </span>'.paginate($num_pages, $p, 'admin_users.php?ip_stats='.$ip);

            $page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), $lang_admin_common['Admin'], $lang_admin_common['Users'], $lang_admin_users['Results head']);
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
                    'paging_links' => $paging_links,
                    'db' => $db,
                    'p' => '',
                )
            );

            $feather->render('admin/users/show_users.php', array(
                    'lang_admin_users' => $lang_admin_users,
                    'lang_admin_common' => $lang_admin_common,
                    'start_from'        =>  $start_from,
                    'info'   =>  get_info_poster($ip, $start_from),
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