<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher.
 */

namespace controller\admin;

class categories
{
    public function __construct()
    {
        $this->feather = \Slim\Slim::getInstance();
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
        require FEATHER_ROOT.$class_name.'.php';
    }

    public function add_category()
    {
        global $lang_common, $lang_admin_common, $lang_admin_categories;

        if ($this->user->g_id != FEATHER_ADMIN) {
            message($lang_common['No permission'], '403');
        }

        // Load the admin_options.php language file
        require FEATHER_ROOT.'include/common_admin.php';
        require FEATHER_ROOT.'lang/'.$admin_language.'/categories.php';

        $cat_name = feather_trim($this->request->post('cat_name'));
        if ($cat_name == '') {
            redirect(get_link('admin/categories/'), $lang_admin_categories['Must enter name message']);
        }

        if ($this->model->add_category($cat_name)) {
            redirect(get_link('admin/categories/'), $lang_admin_categories['Category added redirect']);
        } else { //TODO, add error message
            redirect(get_link('admin/categories/'), $lang_admin_categories['Category added redirect']);
        }
    }

    public function edit_categories()
    {
        global $lang_common, $lang_admin_common, $lang_admin_categories;

        if ($this->user->g_id != FEATHER_ADMIN) {
            message($lang_common['No permission'], '403');
        }

        // Load the admin_options.php language file
        require FEATHER_ROOT.'include/common_admin.php';
        require FEATHER_ROOT.'lang/'.$admin_language.'/categories.php';

        if (empty($this->request->post('cat'))) {
            message($lang_common['Bad request'], '404');
        }

        $categories = array();
        foreach ($this->request->post('cat') as $cat_id => $properties) {
            $category = array('id' => (int) $cat_id,
                              'name' => feather_escape($properties['name']),
                              'order' => (int) $properties['order'], );
            if ($category['name'] == '') {
                redirect(get_link('admin/categories/'), $lang_admin_categories['Must enter name message']);
            }
            $this->model->update_category($category);
        }

        // Regenerate the quick jump cache
        if (!defined('FORUM_CACHE_FUNCTIONS_LOADED')) {
            require FEATHER_ROOT.'include/cache.php';
        }
        generate_quickjump_cache();

        redirect(get_link('admin/categories/'), $lang_admin_categories['Categories updated redirect']);
    }

    public function delete_category()
    {
        global $lang_common, $lang_admin_common, $lang_admin_categories;

        if ($this->user->g_id != FEATHER_ADMIN) {
            message($lang_common['No permission'], '403');
        }

        // Load the admin_options.php language file
        require FEATHER_ROOT.'include/common_admin.php';
        require FEATHER_ROOT.'lang/'.$admin_language.'/categories.php';

        $cat_to_delete = (int) $this->request->post('cat_to_delete');

        if ($cat_to_delete < 1) {
            message($lang_common['Bad request'], '404');
        }

        if (intval($this->request->post('disclaimer')) != 1) {
            redirect(get_link('admin/categories/'), $lang_admin_categories['Delete category not validated']);
        }

        if ($this->model->delete_category($cat_to_delete)) {
            redirect(get_link('admin/categories/'), $lang_admin_categories['Category deleted redirect']);
        } else {
            redirect(get_link('admin/categories/'), $lang_admin_categories['Unable to delete category']);
        }
    }

    public function display()
    {
        global $lang_common, $lang_admin_common, $lang_admin_categories;

        require FEATHER_ROOT.'include/common_admin.php';

        if ($this->user->g_id != FEATHER_ADMIN) {
            message($lang_common['No permission'], '403');
        }

        define('FEATHER_ADMIN_CONSOLE', 1);

        // Load the admin_options.php language file
        require FEATHER_ROOT.'lang/'.$admin_language.'/categories.php';

        $page_title = array(feather_escape($this->config['o_board_title']), $lang_admin_common['Admin'], $lang_admin_common['Categories']);

        define('FEATHER_ACTIVE_PAGE', 'admin');

        $this->header->setTitle($page_title)->display();

        generate_admin_menu('categories');

        $this->feather->render('admin/categories.php', array(
                'lang_admin_categories' => $lang_admin_categories,
                'lang_admin_common' => $lang_admin_common,
                'cat_list' => $this->model->get_cat_list(),
            ));

        $this->footer->display();
    }
}
