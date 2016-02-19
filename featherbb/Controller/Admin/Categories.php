<?php

/**
 * Copyright (C) 2015-2016 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher.
 */

namespace FeatherBB\Controller\Admin;

use FeatherBB\Core\AdminUtils;
use FeatherBB\Core\Error;
use FeatherBB\Core\Url;
use FeatherBB\Core\Utils;
use FeatherBB\Model\Cache;

class Categories
{
    public function __construct()
    {
        $this->feather = \Slim\Slim::getInstance();
        $this->start = $this->feather->start;
        $this->config = $this->feather->config;
        $this->user = Container::get('user');
        $this->request = $this->feather->request;
        $this->model = new \FeatherBB\Model\Admin\Categories();
        load_textdomain('featherbb', Config::get('forum_env')['FEATHER_ROOT'].'featherbb/lang/'.$this->user->language.'/admin/categories.mo');
    }

    public function add()
    {
        Container::get('hooks')->fire('controller.admin.categories.add');

        $cat_name = Utils::trim($this->request->post('cat_name'));
        if ($cat_name == '') {
            Router::redirect(Router::pathFor('adminCategories'), __('Must enter name message'));
        }

        if ($this->model->add_category($cat_name)) {
            Router::redirect(Router::pathFor('adminCategories'), __('Category added redirect'));
        } else { //TODO, add error message
            Router::redirect(Router::pathFor('adminCategories'), __('Category added redirect'));
        }
    }

    public function edit()
    {
        Container::get('hooks')->fire('controller.admin.categories.edit');

        if (empty($this->request->post('cat'))) {
            throw new Error(__('Bad request'), '400');
        }

        foreach ($this->request->post('cat') as $cat_id => $properties) {
            $category = array('id' => (int) $cat_id,
                              'name' => Utils::escape($properties['name']),
                              'order' => (int) $properties['order'], );
            if ($category['name'] == '') {
                Router::redirect(Router::pathFor('adminCategories'), __('Must enter name message'));
            }
            $this->model->update_category($category);
        }

        // Regenerate the quick jump cache
        Container::get('cache')->store('quickjump', Cache::get_quickjump());

        Router::redirect(Router::pathFor('adminCategories'), __('Categories updated redirect'));

    }

    public function delete()
    {
        Container::get('hooks')->fire('controller.admin.categories.delete');

        $cat_to_delete = (int) $this->request->post('cat_to_delete');

        if ($cat_to_delete < 1) {
            throw new Error(__('Bad request'), '400');
        }

        if (intval($this->request->post('disclaimer')) != 1) {
            Router::redirect(Router::pathFor('adminCategories'), __('Delete category not validated'));
        }

        if ($this->model->delete_category($cat_to_delete)) {
            Router::redirect(Router::pathFor('adminCategories'), __('Category deleted redirect'));
        } else {
            Router::redirect(Router::pathFor('adminCategories'), __('Unable to delete category'));
        }
    }

    public function display()
    {
        Container::get('hooks')->fire('controller.admin.categories.display');

        AdminUtils::generateAdminMenu('categories');

        View::setPageInfo(array(
                'title' => array(Utils::escape($this->config['o_board_title']), __('Admin'), __('Categories')),
                'active_page' => 'admin',
                'admin_console' => true,
                'cat_list' => $this->model->get_cat_list(),
            ))->addTemplate('admin/categories.php')->display();
    }
}
