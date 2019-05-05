<?php

/**
 * Copyright (C) 2015-2019 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher.
 */

namespace FeatherBB\Model\Admin;

use FeatherBB\Core\Database as DB;
use FeatherBB\Core\Interfaces\Hooks;

class Categories
{
    public function addCategory($catName)
    {
        $catName = Hooks::fire('model.admin.categories.add_category', $catName);

        $setAddCategory = ['cat_name' => $catName];

        return DB::table('categories')
                ->create()
                ->set($setAddCategory)
                ->save();
    }

    public function updateCategory(array $category)
    {
        $category = Hooks::fire('model.admin.categories.update_category', $category);

        $setUpdateCategory = ['cat_name' => $category['name'],
                                    'disp_position' => $category['order']];

        return DB::table('categories')
                ->findOne($category['id'])
                ->set($setUpdateCategory)
                ->save();
    }

    public function deleteCategory($catToDelete)
    {
        $catToDelete = Hooks::fire('model.admin.categories.delete_category_start', $catToDelete);

        $forumsInCat = DB::table('forums')
                            ->select('id')
                            ->where('cat_id', $catToDelete);
        $forumsInCat = Hooks::fireDB('model.admin.categories.delete_forums_in_cat_query', $forumsInCat);
        $forumsInCat = $forumsInCat->findMany();

        foreach ($forumsInCat as $forum) {
            // Prune all posts and topics
            $this->maintenance = new \FeatherBB\Model\Admin\Maintenance();
            $this->maintenance->prune($forum->id, 1, -1);

            // Delete forum
            DB::table('forums')
                ->findOne($forum->id)
                ->delete();
        }

        // Delete orphan redirect forums
        $orphans = DB::table('topics')
                    ->tableAlias('t1')
                    ->leftOuterJoin('topics', ['t1.moved_to', '=', 't2.id'], 't2')
                    ->whereNull('t2.id')
                    ->whereNotNull('t1.moved_to');
        $orphans = Hooks::fireDB('model.admin.categories.delete_orphan_forums_query', $orphans);
        $orphans = $orphans->findMany();

        if (count($orphans) > 0) {
            $orphans->deleteMany();
        }

        // Delete category
        $result = DB::table('categories');
        $result = Hooks::fireDB('model.admin.categories.find_forums_in_cat', $result);
        $result = $result->findOne($catToDelete)->delete();

        return true;
    }

    public function categoryList()
    {
        $catList = [];
        $selectGetCatList = ['id', 'cat_name', 'disp_position'];

        $catList = DB::table('categories')
            ->select($selectGetCatList)
            ->orderByAsc('disp_position');
        $catList = Hooks::fireDB('model.admin.categories.get_cat_list', $catList);
        $catList = $catList->findArray();

        return $catList;
    }
}
