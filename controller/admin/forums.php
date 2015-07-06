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
        global $lang_common, $lang_admin_common, $lang_admin_forums, $feather_config, $feather_user, $db;

        require FEATHER_ROOT . 'include/common_admin.php';

        if ($feather_user['g_id'] != FEATHER_ADMIN) {
            message($lang_common['No permission'], false, '403 Forbidden');
        }

        define('FEATHER_ADMIN_CONSOLE', 1);

        // Load the admin_options.php language file
        require FEATHER_ROOT . 'lang/' . $admin_language . '/forums.php';

        // Load the forums.php model file
        require FEATHER_ROOT . 'model/admin/forums.php';

        // Add a "default" forum
        if ($this->feather->request->post('add_forum')) {
            add_forum($this->feather);
        }  // Update forum positions
        elseif ($this->feather->request->post('update_positions')) {
            update_positions($this->feather);
        }

        $page_title = array(pun_htmlspecialchars($feather_config['o_board_title']), $lang_admin_common['Admin'], $lang_admin_common['Forums']);

        define('FEATHER_ACTIVE_PAGE', 'admin');

        require FEATHER_ROOT . 'include/header.php';

        generate_admin_menu('forums');

        $this->feather->render('admin/forums/admin_forums.php', array(
                'lang_admin_forums' => $lang_admin_forums,
                'lang_admin_common' => $lang_admin_common,
                'feather_config' => $feather_config,
                'is_forum' => check_forums(),
                'forum_data'    =>  get_forums(),
                'cur_index'     =>  4,
                'cur_category' => 0,
            )
        );

        require FEATHER_ROOT . 'include/footer.php';
    }


    public function edit($id)
    {
        global $lang_common, $lang_admin_common, $lang_admin_forums, $feather_config, $feather_user, $db;

        require FEATHER_ROOT . 'include/common_admin.php';

        if ($feather_user['g_id'] != FEATHER_ADMIN) {
            message($lang_common['No permission'], false, '403 Forbidden');
        }

        define('FEATHER_ADMIN_CONSOLE', 1);

        // Load the admin_options.php language file
        require FEATHER_ROOT . 'lang/' . $admin_language . '/forums.php';

        // Load the forums.php model file
        require FEATHER_ROOT . 'model/admin/forums.php';

        // Update forum

        // Update group permissions for $forum_id
        if ($this->feather->request->post('save')) {
            update_permissions($this->feather, $id);
        } elseif ($this->feather->request->post('revert_perms')) {
            revert_permissions($id);
        }

        // Fetch forum info
        $cur_forum = get_forum_info($id);

        $page_title = array(pun_htmlspecialchars($feather_config['o_board_title']), $lang_admin_common['Admin'], $lang_admin_common['Forums']);

        define('FEATHER_ACTIVE_PAGE', 'admin');

        require FEATHER_ROOT . 'include/header.php';

        generate_admin_menu('forums');

        $this->feather->render('admin/forums/permissions.php', array(
                'lang_admin_forums' => $lang_admin_forums,
                'lang_admin_common' => $lang_admin_common,
                'feather_config' => $feather_config,
                'perm_data' => get_permissions($id),
                'cur_index'     =>  7,
                'cur_forum' => get_forum_info($id),
                'forum_id'  =>  $id,
            )
        );

        require FEATHER_ROOT . 'include/footer.php';
    }

    public function delete($id)
    {
        global $lang_common, $lang_admin_common, $lang_admin_forums, $feather_config, $feather_user, $db;

        require FEATHER_ROOT . 'include/common_admin.php';

        if ($feather_user['g_id'] != FEATHER_ADMIN) {
            message($lang_common['No permission'], false, '403 Forbidden');
        }

        define('FEATHER_ADMIN_CONSOLE', 1);

        // Load the admin_options.php language file
        require FEATHER_ROOT . 'lang/' . $admin_language . '/forums.php';

        // Load the report.php model file
        require FEATHER_ROOT . 'model/admin/forums.php';

        if ($this->feather->request->isPost()) { // Delete a forum with all posts
            delete_forum($id);
        } else {
            // If the user hasn't confirmed the delete

            $page_title = array(pun_htmlspecialchars($feather_config['o_board_title']), $lang_admin_common['Admin'], $lang_admin_common['Forums']);

            define('FEATHER_ACTIVE_PAGE', 'admin');

            require FEATHER_ROOT . 'include/header.php';

            generate_admin_menu('forums');

            $this->feather->render('admin/forums/delete_forum.php', array(
                    'lang_admin_forums' => $lang_admin_forums,
                    'lang_admin_common' => $lang_admin_common,
                    'forum_name' => get_forum_name($id),
                    'forum_id'  =>  $id,
                )
            );

            require FEATHER_ROOT . 'include/footer.php';
        }
    }
}
