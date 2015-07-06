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
    }
    
    public function display()
    {
        global $lang_common, $lang_admin_common, $lang_admin_categories, $feather_config, $feather_user, $db;

        require FEATHER_ROOT.'include/common_admin.php';

        if ($feather_user['g_id'] != FEATHER_ADMIN) {
            message($lang_common['No permission'], false, '403 Forbidden');
        }

        define('FEATHER_ADMIN_CONSOLE', 1);

        // Load the admin_options.php language file
        require FEATHER_ROOT.'lang/'.$admin_language.'/categories.php';

        // Load the categories.php model file
        require FEATHER_ROOT.'model/admin/categories.php';

                    // Add a new category
                    if ($this->feather->request->post('add_cat')) {
                        add_category($this->feather);
                    }

                    // Delete a category
                    elseif ($this->feather->request->post('del_cat') || $this->feather->request->post('del_cat_comply')) {
                        confirm_referrer(get_link_r('admin/categories/'));

                        $cat_to_delete = intval($this->feather->request->post('cat_to_delete'));
                        if ($cat_to_delete < 1) {
                            message($lang_common['Bad request'], false, '404 Not Found');
                        }

                        if ($this->feather->request->post('del_cat_comply')) { // Delete a category with all forums and posts
                                    // Load the maintenance.php model file to get the prune() function
                                    require FEATHER_ROOT.'model/admin/maintenance.php';

                            delete_category($cat_to_delete);
                        } else {
                            // If the user hasn't confirmed the delete

                            $page_title = array(pun_htmlspecialchars($feather_config['o_board_title']), $lang_admin_common['Admin'], $lang_admin_common['Categories']);
                                    
                            define('FEATHER_ACTIVE_PAGE', 'admin');

                            require FEATHER_ROOT.'include/header.php';

                            generate_admin_menu('categories');

                            $this->feather->render('admin/categories/delete_category.php', array(
                                                    'lang_admin_categories'    =>    $lang_admin_categories,
                                                    'lang_admin_common'    =>    $lang_admin_common,
                                                    'cat_to_delete'    =>    $cat_to_delete,
                                                    'cat_name'    =>    get_category_name($cat_to_delete),
                                            )
                                    );

                            require FEATHER_ROOT.'include/footer.php';
                        }
                    } elseif ($this->feather->request->post('update')) {
                        // Change position and name of the categories
                            confirm_referrer(get_link_r('admin/categories/'));

                        $categories = $this->feather->request->post('cat');
                        if (empty($categories)) {
                            message($lang_common['Bad request'], false, '404 Not Found');
                        }

                        update_categories($categories);
                    }

        $page_title = array(pun_htmlspecialchars($feather_config['o_board_title']), $lang_admin_common['Admin'], $lang_admin_common['Categories']);

        define('FEATHER_ACTIVE_PAGE', 'admin');

        require FEATHER_ROOT.'include/header.php';

        generate_admin_menu('categories');

        $this->feather->render('admin/categories/admin_categories.php', array(
                'lang_admin_categories'    =>    $lang_admin_categories,
                'lang_admin_common'    =>    $lang_admin_common,
                'cat_list'    =>    get_cat_list(),
            )
        );

        require FEATHER_ROOT.'include/footer.php';
    }
}
