<?php

/**
 * Copyright (C) 2015-2016 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher.
 */

namespace FeatherBB\Model\Admin;

use FeatherBB\Core\Database as DB;

class Categories
{
    public function __construct()
    {
        $this->feather = \Slim\Slim::getInstance();
        $this->start = $this->feather->start;
        $this->config = $this->feather->config;
        $this->user = Container::get('user');
        $this->request = $this->feather->request;
        Container::get('hooks') = $this->feather->hooks;
    }

    public function add_category($cat_name)
    {
        $cat_name = Container::get('hooks')->fire('model.admin.categories.add_category', $cat_name);

        $set_add_category = array('cat_name' => $cat_name);

        return DB::for_table('categories')
                ->create()
                ->set($set_add_category)
                ->save();
    }

    public function update_category(array $category)
    {
        $category = Container::get('hooks')->fire('model.admin.categories.update_category', $category);

        $set_update_category = array('cat_name' => $category['name'],
                                    'disp_position' => $category['order']);

        return DB::for_table('categories')
                ->find_one($category['id'])
                ->set($set_update_category)
                ->save();
    }

    public function delete_category($cat_to_delete)
    {
        $cat_to_delete = Container::get('hooks')->fire('model.admin.categories.delete_category_start', $cat_to_delete);

        $forums_in_cat = DB::for_table('forums')
                            ->select('id')
                            ->where('cat_id', $cat_to_delete);
        $forums_in_cat = Container::get('hooks')->fireDB('model.admin.categories.delete_forums_in_cat_query', $forums_in_cat);
        $forums_in_cat = $forums_in_cat->find_many();

        foreach ($forums_in_cat as $forum) {
            // Prune all posts and topics
            $this->maintenance = new \FeatherBB\Model\Admin\Maintenance();
            $this->maintenance->prune($forum->id, 1, -1);

            // Delete forum
            DB::for_table('forums')
                ->find_one($forum->id)
                ->delete();
        }

        // Delete orphan redirect forums
        $orphans = DB::for_table('topics')
                    ->table_alias('t1')
                    ->left_outer_join('topics', array('t1.moved_to', '=', 't2.id'), 't2')
                    ->where_null('t2.id')
                    ->where_not_null('t1.moved_to');
        $orphans = Container::get('hooks')->fireDB('model.admin.categories.delete_orphan_forums_query', $orphans);
        $orphans = $orphans->find_many();

        if (count($orphans) > 0) {
            $orphans->delete_many();
        }

        // Delete category
        $result = DB::for_table('categories');
        $result = Container::get('hooks')->fireDB('model.admin.categories.find_forums_in_cat', $result);
        $result = $result->find_one($cat_to_delete)->delete();

        return true;
    }

    public function get_cat_list()
    {
        $cat_list = array();
        $select_get_cat_list = array('id', 'cat_name', 'disp_position');

        $cat_list = DB::for_table('categories')
            ->select($select_get_cat_list)
            ->order_by_asc('disp_position');
        $cat_list = Container::get('hooks')->fireDB('model.admin.categories.get_cat_list', $cat_list);
        $cat_list = $cat_list->find_array();

        return $cat_list;
    }
}
