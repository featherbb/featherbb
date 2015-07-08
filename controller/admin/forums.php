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
        $this->request = $this->feather->request;
        $this->header = new \controller\header();
        $this->footer = new \controller\footer();
        $this->model = new \model\admin\forums();
    }

    public function __autoload($class_name)
    {
        require FEATHER_ROOT . $class_name . '.php';
    }
    
    public function display()
    {
        global $lang_common, $lang_admin_common, $lang_admin_forums;

        require FEATHER_ROOT . 'include/common_admin.php';

        if ($this->user['g_id'] != FEATHER_ADMIN) {
            message($lang_common['No permission'], false, '403 Forbidden');
        }

        define('FEATHER_ADMIN_CONSOLE', 1);

        // Load the admin_options.php language file
        require FEATHER_ROOT . 'lang/' . $admin_language . '/forums.php';

        // Add a "default" forum
        if ($this->request->post('add_forum')) {
            $this->model->add_forum($this->feather);
        }  // Update forum positions
        elseif ($this->request->post('update_positions')) {
            $this->model->update_positions($this->feather);
        }

        $page_title = array(feather_htmlspecialchars($this->config['o_board_title']), $lang_admin_common['Admin'], $lang_admin_common['Forums']);

        define('FEATHER_ACTIVE_PAGE', 'admin');

        $this->header->display($page_title);

        generate_admin_menu('forums');

        $this->feather->render('admin/forums/admin_forums.php', array(
                'lang_admin_forums' => $lang_admin_forums,
                'lang_admin_common' => $lang_admin_common,
                'feather_config' => $this->config,
                'is_forum' => $this->model->check_forums(),
                'forum_data'    =>  $this->model->get_forums(),
                'categories_add' => $this->model->get_categories_add(),
                'cur_index'     =>  4,
                'cur_category' => 0,
            )
        );

        $this->footer->display();
    }


    public function edit($id)
    {
        global $lang_common, $lang_admin_common, $lang_admin_forums;

        require FEATHER_ROOT . 'include/common_admin.php';

        if ($this->user['g_id'] != FEATHER_ADMIN) {
            message($lang_common['No permission'], false, '403 Forbidden');
        }

        define('FEATHER_ADMIN_CONSOLE', 1);

        // Load the admin_options.php language file
        require FEATHER_ROOT . 'lang/' . $admin_language . '/forums.php';

        // Update forum

        // Update group permissions for $forum_id
        if ($this->request->post('save')) {
            $this->model->update_permissions($this->feather, $id);
        } elseif ($this->request->post('revert_perms')) {
            $this->model->revert_permissions($id);
        }

        // Fetch forum info
        $cur_forum = $this->model->get_forum_info($id);

        $page_title = array(feather_htmlspecialchars($this->config['o_board_title']), $lang_admin_common['Admin'], $lang_admin_common['Forums']);

        define('FEATHER_ACTIVE_PAGE', 'admin');

        $this->header->display($page_title);

        generate_admin_menu('forums');

        $this->feather->render('admin/forums/permissions.php', array(
                'lang_admin_forums' => $lang_admin_forums,
                'lang_admin_common' => $lang_admin_common,
                'feather_config' => $this->config,
                'perm_data' => $this->model->get_permissions($id),
                'cur_index'     =>  7,
                'cur_forum' => $this->model->get_forum_info($id),
                'categories_perms' => $this->model->get_categories_permissions($cur_forum),
                'forum_id'  =>  $id,
            )
        );

        $this->footer->display();
    }

    public function delete($id)
    {
        global $lang_common, $lang_admin_common, $lang_admin_forums;

        require FEATHER_ROOT . 'include/common_admin.php';

        if ($this->user['g_id'] != FEATHER_ADMIN) {
            message($lang_common['No permission'], false, '403 Forbidden');
        }

        define('FEATHER_ADMIN_CONSOLE', 1);

        // Load the admin_options.php language file
        require FEATHER_ROOT . 'lang/' . $admin_language . '/forums.php';

        if ($this->feather->request->isPost()) { // Delete a forum with all posts
            $this->model->delete_forum($id);
        } else {
            // If the user hasn't confirmed the delete

            $page_title = array(feather_htmlspecialchars($this->config['o_board_title']), $lang_admin_common['Admin'], $lang_admin_common['Forums']);

            define('FEATHER_ACTIVE_PAGE', 'admin');

            $this->header->display($page_title);

            generate_admin_menu('forums');

            $this->feather->render('admin/forums/delete_forum.php', array(
                    'lang_admin_forums' => $lang_admin_forums,
                    'lang_admin_common' => $lang_admin_common,
                    'forum_name' => $this->model->get_forum_name($id),
                    'forum_id'  =>  $id,
                )
            );

            $this->footer->display();
        }
    }
}
