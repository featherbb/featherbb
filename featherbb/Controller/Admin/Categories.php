<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher.
 */

namespace FeatherBB\Controller\Admin;

use FeatherBB\Utils;
use FeatherBB\Url;

class Categories
{
    public function __construct()
    {
        $this->feather = \Slim\Slim::getInstance();
        $this->start = $this->feather->start;
        $this->config = $this->feather->config;
        $this->user = $this->feather->user;
        $this->request = $this->feather->request;
        $this->model = new \FeatherBB\Model\Admin\Categories();
        load_textdomain('featherbb', $this->feather->forum_env['FEATHER_ROOT'].'featherbb/lang/'.$this->user->language.'/admin/categories.mo');
    }

    public function __autoload($class_name)
    {
        require $this->feather->forum_env['FEATHER_ROOT'].$class_name.'.php';
    }

    public function add_category()
    {
        $cat_name = Utils::trim($this->request->post('cat_name'));
        if ($cat_name == '') {
            redirect(Url::get('admin/categories/'), __('Must enter name message'));
        }

        if ($this->model->add_category($cat_name)) {
            redirect(Url::get('admin/categories/'), __('Category added redirect'));
        } else { //TODO, add error message
            redirect(Url::get('admin/categories/'), __('Category added redirect'));
        }
    }

    public function edit_categories()
    {
        if (empty($this->request->post('cat'))) {
            throw new \FeatherBB\Error(__('Bad request'), '400');
        }

        foreach ($this->request->post('cat') as $cat_id => $properties) {
            $category = array('id' => (int) $cat_id,
                              'name' => Utils::escape($properties['name']),
                              'order' => (int) $properties['order'], );
            if ($category['name'] == '') {
                redirect(Url::get('admin/categories/'), __('Must enter name message'));
            }
            $this->model->update_category($category);
        }

        // Regenerate the quick jump cache
        $this->feather->cache->store('quickjump', \FeatherBB\Model\Cache::get_quickjump());

        redirect(Url::get('admin/categories/'), __('Categories updated redirect'));
    }

    public function delete_category()
    {
        $cat_to_delete = (int) $this->request->post('cat_to_delete');

        if ($cat_to_delete < 1) {
            throw new \FeatherBB\Error(__('Bad request'), '400');
        }

        if (intval($this->request->post('disclaimer')) != 1) {
            redirect(Url::get('admin/categories/'), __('Delete category not validated'));
        }

        if ($this->model->delete_category($cat_to_delete)) {
            redirect(Url::get('admin/categories/'), __('Category deleted redirect'));
        } else {
            redirect(Url::get('admin/categories/'), __('Unable to delete category'));
        }
    }

    public function display()
    {
        \FeatherBB\AdminUtils::generateAdminMenu('categories');

        $this->feather->template->setPageInfo(array(
                'title' => array(Utils::escape($this->config['o_board_title']), __('Admin'), __('Categories')),
                'active_page' => 'admin',
                'admin_console' => true,
                'cat_list' => $this->model->get_cat_list(),
            ))->addTemplate('admin/categories.php')->display();
    }
}
