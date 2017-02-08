<?php

/**
 * Copyright (C) 2015-2017 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace FeatherBB\Model\Admin;

use FeatherBB\Core\Database as DB;
use FeatherBB\Core\Error;
use FeatherBB\Core\Url;
use FeatherBB\Core\Utils;

class Maintenance
{
    public function __construct()
    {
        $this->search = new \FeatherBB\Core\Search();
    }

    public function rebuild()
    {
        $per_page = Input::query('i_per_page') ? intval(Input::query('i_per_page')) : 0;
        $per_page = Container::get('hooks')->fire('model.admin.maintenance.rebuild.per_page', $per_page);

        // Check per page is > 0
        if ($per_page < 1) {
            throw new Error(__('Posts must be integer message'), 400);
        }

        @set_time_limit(0);

        // If this is the first cycle of posts we empty the search index before we proceed
        if (Input::query('i_empty_index')) {
            DB::for_table('search_words')->raw_execute('TRUNCATE '.ForumSettings::get('db_prefix').'search_words');
            DB::for_table('search_matches')->raw_execute('TRUNCATE '.ForumSettings::get('db_prefix').'search_matches');

            // Reset the sequence for the search words (not needed for SQLite)
            switch (ForumSettings::get('db_type')) {
                case 'mysql':
                case 'mysqli':
                case 'mysql_innodb':
                case 'mysqli_innodb':
                    DB::for_table('search_words')->raw_execute('ALTER TABLE '.ForumSettings::get('db_prefix').'search_words auto_increment=1');
                    break;

                case 'pgsql':
                    DB::for_table('search_words')->raw_execute('SELECT setval(\''.ForumSettings::get('db_prefix').'search_words_id_seq\', 1, false)');
            }
        }
    }

    public function getQueryString()
    {
        $query_str = '';

        $per_page = Input::query('i_per_page') ? intval(Input::query('i_per_page')) : 0;
        $per_page = Container::get('hooks')->fire('model.admin.maintenance.get_query_str.per_page', $per_page);
        $start_at = Input::query('i_start_at') ? intval(Input::query('i_start_at')) : 0;
        $start_at = Container::get('hooks')->fire('model.admin.maintenance.get_query_str.start_at', $start_at);

        // Fetch posts to process this cycle
        $result['select'] = ['p.id', 'p.message', 't.subject', 't.first_post_id'];

        $result = DB::for_table('posts')->table_alias('p')
                        ->select_many($result['select'])
                        ->inner_join('topics', ['t.id', '=', 'p.topic_id'], 't')
                        ->where_gte('p.id', $start_at)
                        ->order_by_asc('p.id')
                        ->limit($per_page);
        $result = Container::get('hooks')->fireDB('model.admin.maintenance.get_query_str.query', $result);
        $result = $result->find_many();

        $end_at = 0;
        foreach ($result as $cur_item) {
            echo '<p><span>'.sprintf(__('Processing post'), $cur_item['id']).'</span></p>'."\n";

            if ($cur_item['id'] == $cur_item['first_post_id']) {
                $this->search->updateSearchIndex('post', $cur_item['id'], $cur_item['message'], $cur_item['subject']);
            } else {
                $this->search->updateSearchIndex('post', $cur_item['id'], $cur_item['message']);
            }

            $end_at = $cur_item['id'];
        }

        // Check if there is more work to do
        if ($end_at > 0) {
            $id = DB::for_table('posts')->where_gt('id', $end_at)
                        ->order_by_asc('id')
                        ->find_one_col('id');

            if ($id) {
                $query_str = '?action=rebuild&i_per_page='.$per_page.'&i_start_at='.intval($id);
            }
        }

        $pdo = DB::get_db();
        $pdo = null;

        $query_str = Container::get('hooks')->fire('model.admin.maintenance.get_query_str', $query_str);
        return $query_str;
    }

    //
    // Delete topics from $forum_id that are "older than" $prune_date (if $prune_sticky is 1, sticky topics will also be deleted)
    //
    public function prune($forum_id, $prune_sticky, $prune_date)
    {
        // Fetch topics to prune
        $topics_id = DB::for_table('topics')->select('id')
                    ->where('forum_id', $forum_id);

        if ($prune_date != -1) {
            $topics_id = $topics_id->where_lt('last_post', $prune_date);
        }

        if (!$prune_sticky) {
            $topics_id = $topics_id->where('sticky', 0);
        }

        $topics_id = $topics_id->find_many();

        $topic_ids = [];
        foreach ($topics_id as $row) {
            $topic_ids[] = $row['id'];
        }
        $topic_ids = Container::get('hooks')->fire('model.admin.maintenance.prune.topic_ids', $topic_ids);

        if (!empty($topic_ids)) {
            // Fetch posts to prune
            $posts_id = DB::for_table('posts')->select('id')
                            ->where_in('topic_id', $topic_ids)
                            ->find_many();

            $post_ids = [];
            foreach ($posts_id as $row) {
                $post_ids[] = $row['id'];
            }
            $post_ids = Container::get('hooks')->fire('model.admin.maintenance.prune.post_ids', $post_ids);

            if ($post_ids != '') {
                // Delete topics
                DB::for_table('topics')
                        ->where_in('id', $topic_ids)
                        ->delete_many();
                // Delete subscriptions
                DB::for_table('topic_subscriptions')
                        ->where_in('topic_id', $topic_ids)
                        ->delete_many();
                // Delete posts
                DB::for_table('posts')
                        ->where_in('id', $post_ids)
                        ->delete_many();

                // We removed a bunch of posts, so now we have to update the search index
                $this->search->stripSearchIndex($post_ids);
            }
        }
    }

    public function pruneComply($prune_from, $prune_sticky)
    {
        $prune_days = intval(Input::post('prune_days'));
        $prune_days = Container::get('hooks')->fire('model.admin.maintenance.prune_comply.prune_days', $prune_days);
        $prune_date = ($prune_days) ? time() - ($prune_days * 86400) : -1;

        @set_time_limit(0);

        if ($prune_from == 'all') {
            $result = DB::for_table('forums')->select('id');
            $result = Container::get('hooks')->fireDB('model.admin.maintenance.prune_comply.query', $result);
            $result = $result->find_array();

            if (!empty($result)) {
                foreach ($result as $row) {
                    $this->prune($row['id'], $prune_sticky, $prune_date);
                    \FeatherBB\Model\Forum::update($row['id']);
                }
            }
        } else {
            $prune_from = intval($prune_from);
            $this->prune($prune_from, $prune_sticky, $prune_date);
            \FeatherBB\Model\Forum::update($prune_from);
        }

        // Locate any "orphaned redirect topics" and delete them
        $result = DB::for_table('topics')->table_alias('t1')
                        ->select('t1.id')
                        ->left_outer_join('topics', ['t1.moved_to', '=', 't2.id'], 't2')
                        ->where_null('t2.id')
                        ->where_not_null('t1.moved_to');
        $result = Container::get('hooks')->fireDB('model.admin.maintenance.prune_comply.orphans_query', $result);
        $result = $result->find_array();

        $orphans = [];
        if (!empty($result)) {
            foreach ($result as $row) {
                $orphans[] = $row['id'];
            }
            $orphans = Container::get('hooks')->fire('model.admin.maintenance.prune_comply.orphans', $orphans);

            DB::for_table('topics')
                    ->where_in('id', $orphans)
                    ->delete_many();
        }

        return Router::redirect(Router::pathFor('adminMaintenance'), __('Posts pruned redirect'));
    }

    public function getInfoPrune($prune_sticky, $prune_from)
    {
        $prune = [];

        $prune['days'] = Utils::trim(Input::post('req_prune_days'));
        if ($prune['days'] == '' || preg_match('%[^0-9]%', $prune['days'])) {
            throw new Error(__('Days must be integer message'), 400);
        }

        $prune['date'] = time() - ($prune['days'] * 86400);

        $prune = Container::get('hooks')->fire('model.admin.maintenance.get_info_prune.prune_dates', $prune);

        // Concatenate together the query for counting number of topics to prune
        $query = DB::for_table('topics')->where_lt('last_post', $prune['date'])
                        ->where_null('moved_to');

        if ($prune_sticky == '0') {
            $query = $query->where('sticky', 0);
        }

        if ($prune_from != 'all') {
            $query = $query->where('forum_id', intval($prune_from));

            // Fetch the forum name (just for cosmetic reasons)
            $forum = DB::for_table('forums')->where('id', $prune_from);
            $forum = Container::get('hooks')->fireDB('model.admin.maintenance.get_info_prune.forum_query', $forum);
            $forum = $forum->find_one_col('forum_name');

            $prune['forum'] = '"'.Utils::escape($forum).'"';
        } else {
            $prune['forum'] = __('All forums');
        }

        $prune['num_topics'] = $query->count('id');

        if (!$prune['num_topics']) {
            throw new Error(sprintf(__('No old topics message'), $prune['days']), 404);
        }

        $prune = Container::get('hooks')->fire('model.admin.maintenance.get_info_prune.prune', $prune);
        return $prune;
    }

    public function getCategories()
    {
        $output = '';

        $select_get_categories = ['cid' => 'c.id', 'c.cat_name', 'fid' => 'f.id', 'f.forum_name'];
        $order_by_get_categories = ['c.disp_position', 'c.id', 'f.disp_position'];

        $result = DB::for_table('categories')
                    ->table_alias('c')
                    ->select_many($select_get_categories)
                    ->inner_join('forums', ['c.id', '=', 'f.cat_id'], 'f')
                    ->where_null('f.redirect_url')
                    ->order_by_many($order_by_get_categories);
        $result = Container::get('hooks')->fireDB('model.admin.maintenance.get_categories.query', $result);
        $result = $result->find_many();

        $cur_category = 0;
        foreach ($result as $forum) {
            if ($forum['cid'] != $cur_category) {
                // Are we still in the same category?

                if ($cur_category) {
                    $output .= "\t\t\t\t\t\t\t\t\t\t\t".'</optgroup>'."\n";
                }

                $output .=  "\t\t\t\t\t\t\t\t\t\t\t".'<optgroup label="'.Utils::escape($forum['cat_name']).'">'."\n";
                $cur_category = $forum['cid'];
            }

            $output .=  "\t\t\t\t\t\t\t\t\t\t\t\t".'<option value="'.$forum['fid'].'">'.Utils::escape($forum['forum_name']).'</option>'."\n";
        }

        $output = Container::get('hooks')->fire('model.admin.maintenance.get_categories.output', $output);
        return $output;
    }

    public function getFirstId()
    {
        $first_id = '';
        $first_id_sql = DB::for_table('posts')->order_by_asc('id')
                            ->find_one_col('id');
        if ($first_id_sql) {
            $first_id = $first_id_sql;
        }

        $first_id = Container::get('hooks')->fire('model.admin.maintenance.get_first_id', $first_id);
        return $first_id;
    }
}
