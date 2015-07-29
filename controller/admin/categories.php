<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace controller\admin;

class categories
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
        $this->model = new \model\admin\categories();
    }

    public function __autoload($class_name)
    {
        require FEATHER_ROOT . $class_name . '.php';
    }
    
    public function display()
    {
        global $lang_common, $lang_admin_common, $lang_admin_categories;

        require FEATHER_ROOT.'include/common_admin.php';

        if ($this->user->g_id != FEATHER_ADMIN) {
            message($lang_common['No permission'], false, '403 Forbidden');
        }

        define('FEATHER_ADMIN_CONSOLE', 1);

        // Load the admin_options.php language file
        require FEATHER_ROOT.'lang/'.$admin_language.'/categories.php';

        // Add a new category
        if ($this->request->post('add_cat')) {
            $this->model->add_category();
        }

        // Delete a category
        elseif ($this->request->post('del_cat') || $this->request->post('del_cat_comply')) {
            

            $cat_to_delete = intval($this->request->post('cat_to_delete'));
            if ($cat_to_delete < 1) {
                message($lang_common['Bad request'], false, '404 Not Found');
            }

            if ($this->request->post('del_cat_comply')) { // Delete a category with all forums and posts

                $this->model->delete_category($cat_to_delete);
            } else {
                // If the user hasn't confirmed the delete

                $page_title = array(feather_escape($this->config['o_board_title']), $lang_admin_common['Admin'], $lang_admin_common['Categories']);

                define('FEATHER_ACTIVE_PAGE', 'admin');

                $this->header->setTitle($page_title)->display();

                generate_admin_menu('categories');

                $this->feather->render('admin/categories/delete_category.php', array(
                                        'lang_admin_categories'    =>    $lang_admin_categories,
                                        'lang_admin_common'    =>    $lang_admin_common,
                                        'cat_to_delete'    =>    $cat_to_delete,
                                        'cat_name'    =>    $this->model->get_category_name($cat_to_delete),
                                )
                        );

                $this->footer->display();
            }
        } elseif ($this->request->post('update')) {
            // Change position and name of the categories
                

            $categories = $this->request->post('cat');
            if (empty($categories)) {
                message($lang_common['Bad request'], false, '404 Not Found');
            }

            $this->model->update_categories($categories);
        }

        $page_title = array(feather_escape($this->config['o_board_title']), $lang_admin_common['Admin'], $lang_admin_common['Categories']);

        define('FEATHER_ACTIVE_PAGE', 'admin');

        $this->header->setTitle($page_title)->display();

        generate_admin_menu('categories');

        $this->feather->render('admin/categories/admin_categories.php', array(
                'lang_admin_categories'    =>    $lang_admin_categories,
                'lang_admin_common'    =>    $lang_admin_common,
                'cat_list'    =>    $this->model->get_cat_list(),
            )
        );

        $this->footer->display();
    }
}
