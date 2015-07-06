<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace controller\admin;

class bans
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
        global $lang_common, $lang_admin_common, $lang_admin_bans, $feather_config, $feather_user, $db;

        define('FEATHER_ADMIN_CONSOLE', 1);

        require FEATHER_ROOT . 'include/common_admin.php';

        if ($feather_user['g_id'] != FEATHER_ADMIN && ($feather_user['g_moderator'] != '1' || $feather_user['g_mod_ban_users'] == '0')) {
            message($lang_common['No permission'], false, '403 Forbidden');
        }

        // Load the admin_bans.php language file
        require FEATHER_ROOT . 'lang/' . $admin_language . '/bans.php';

        // Load the bans.php model file
        require FEATHER_ROOT . 'model/admin/bans.php';

        // Display bans
        if ($this->feather->request->get('find_ban')) {
            $ban_info = find_ban($this->feather);

            // Determine the ban offset (based on $_GET['p'])
            $num_pages = ceil($ban_info['num_bans'] / 50);

            $p = (!$this->feather->request->get('p') || $this->feather->request->get('p') <= 1 || $this->feather->request->get('p') > $num_pages) ? 1 : intval($this->feather->request->get('p'));
            $start_from = 50 * ($p - 1);

            // Generate paging links
            $paging_links = '<span class="pages-label">' . $lang_common['Pages'] . ' </span>' . paginate($num_pages, $p, 'admin_bans.php?find_ban=&amp;' . implode('&amp;', $ban_info['query_str']));

            $page_title = array(pun_htmlspecialchars($feather_config['o_board_title']), $lang_admin_common['Admin'], $lang_admin_common['Bans'], $lang_admin_bans['Results head']);
            define('FEATHER_ACTIVE_PAGE', 'admin');
            
            require FEATHER_ROOT . 'include/header.php';

            $this->feather->render('admin/bans/search_ban.php', array(
                    'lang_admin_bans' => $lang_admin_bans,
                    'lang_admin_common' => $lang_admin_common,
                    'ban_data' => print_bans($ban_info['conditions'], $ban_info['order_by'], $ban_info['direction'], $start_from),
                )
            );

            require FEATHER_ROOT . 'include/footer.php';
        }

        $page_title = array(pun_htmlspecialchars($feather_config['o_board_title']), $lang_admin_common['Admin'], $lang_admin_common['Bans']);
        $focus_element = array('bans', 'new_ban_user');

        define('FEATHER_ACTIVE_PAGE', 'admin');
        
        require FEATHER_ROOT . 'include/header.php';

        generate_admin_menu('bans');

        $this->feather->render('admin/bans/admin_bans.php', array(
                'lang_admin_bans' => $lang_admin_bans,
                'lang_admin_common' => $lang_admin_common,
            )
        );

        require FEATHER_ROOT . 'include/footer.php';
    }

    public function add()
    {
        global $lang_common, $lang_admin_common, $lang_admin_bans, $feather_config, $feather_user, $db;

        define('FEATHER_ADMIN_CONSOLE', 1);

        require FEATHER_ROOT . 'include/common_admin.php';

        if ($feather_user['g_id'] != FEATHER_ADMIN && ($feather_user['g_moderator'] != '1' || $feather_user['g_mod_ban_users'] == '0')) {
            message($lang_common['No permission'], false, '403 Forbidden');
        }

        // Load the admin_bans.php language file
        require FEATHER_ROOT . 'lang/' . $admin_language . '/bans.php';

        // Load the bans.php model file
        require FEATHER_ROOT . 'model/admin/bans.php';

        if ($this->feather->request->post('add_edit_ban')) {
            insert_ban($this->feather);
        }

        $page_title = array(pun_htmlspecialchars($feather_config['o_board_title']), $lang_admin_common['Admin'], $lang_admin_common['Bans']);
        $focus_element = array('bans2', 'ban_user');

        define('FEATHER_ACTIVE_PAGE', 'admin');
        
        require FEATHER_ROOT . 'include/header.php';

        generate_admin_menu('bans');

        $this->feather->render('admin/bans/add_ban.php', array(
                'lang_admin_bans' => $lang_admin_bans,
                'lang_admin_common' => $lang_admin_common,
                'ban' => add_ban_info($this->feather),
            )
        );

        require FEATHER_ROOT . 'include/footer.php';
    }

    public function delete($id)
    {
        global $lang_common, $lang_admin_common, $feather_config, $lang_admin_bans, $feather_user, $db;

        require FEATHER_ROOT . 'include/common_admin.php';

        if ($feather_user['g_id'] != FEATHER_ADMIN && ($feather_user['g_moderator'] != '1' || $feather_user['g_mod_ban_users'] == '0')) {
            message($lang_common['No permission'], false, '403 Forbidden');
        }

        // Load the admin_bans.php language file
        require FEATHER_ROOT . 'lang/' . $admin_language . '/bans.php';

        // Load the bans.php model file
        require FEATHER_ROOT . 'model/admin/bans.php';

        // Remove the ban
        remove_ban($id);
    }

    public function edit($id)
    {
        global $lang_common, $lang_admin_common, $lang_admin_bans, $feather_config, $feather_user, $db;

        define('FEATHER_ADMIN_CONSOLE', 1);

        require FEATHER_ROOT . 'include/common_admin.php';

        if ($feather_user['g_id'] != FEATHER_ADMIN && ($feather_user['g_moderator'] != '1' || $feather_user['g_mod_ban_users'] == '0')) {
            message($lang_common['No permission'], false, '403 Forbidden');
        }

        // Load the admin_bans.php language file
        require FEATHER_ROOT . 'lang/' . $admin_language . '/bans.php';

        // Load the bans.php model file
        require FEATHER_ROOT . 'model/admin/bans.php';

        if ($this->feather->request->post('add_edit_ban')) {
            insert_ban($this->feather);
        }

        $page_title = array(pun_htmlspecialchars($feather_config['o_board_title']), $lang_admin_common['Admin'], $lang_admin_common['Bans']);
        $focus_element = array('bans2', 'ban_user');

        define('FEATHER_ACTIVE_PAGE', 'admin');

        require FEATHER_ROOT . 'include/header.php';

        generate_admin_menu('bans');

        $this->feather->render('admin/bans/add_ban.php', array(
                'lang_admin_bans' => $lang_admin_bans,
                'lang_admin_common' => $lang_admin_common,
                'ban' => edit_ban_info($id),
            )
        );

        require FEATHER_ROOT . 'include/footer.php';
    }
}
