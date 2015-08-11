<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace model\admin;

use DB;

class maintenance
{
    public function __construct()
    {
        $this->feather = \Slim\Slim::getInstance();
        $this->start = $this->feather->start;
        $this->config = $this->feather->config;
        $this->user = $this->feather->user;
        $this->request = $this->feather->request;
    }
 
    public function rebuild()
    {
        global $db_type, $lang_admin_maintenance;

        $per_page = $this->request->get('i_per_page') ? intval($this->request->get('i_per_page')) : 0;

        // Check per page is > 0
        if ($per_page < 1) {
            message($lang_admin_maintenance['Posts must be integer message']);
        }

        @set_time_limit(0);

        // If this is the first cycle of posts we empty the search index before we proceed
        if ($this->request->get('i_empty_index')) {
            DB::for_table('search_words')->raw_execute('TRUNCATE '.$this->feather->prefix.'search_words');
            DB::for_table('search_matches')->raw_execute('TRUNCATE '.$this->feather->prefix.'search_matches');

            // Reset the sequence for the search words (not needed for SQLite)
            switch ($db_type) {
                case 'mysql':
                case 'mysqli':
                case 'mysql_innodb':
                case 'mysqli_innodb':
                    DB::for_table('search_words')->raw_execute('ALTER TABLE '.$this->feather->prefix.'search_words auto_increment=1');
                    break;

                case 'pgsql';
                    DB::for_table('search_words')->raw_execute('SELECT setval(\''.$this->feather->prefix.'search_words_id_seq\', 1, false)');
            }
        }
    }

    public function get_query_str()
    {
        global $lang_admin_maintenance;

        $query_str = '';

        $per_page = $this->request->get('i_per_page') ? intval($this->request->get('i_per_page')) : 0;
        $start_at = $this->request->get('i_start_at') ? intval($this->request->get('i_start_at')) : 0;

        require FEATHER_ROOT.'include/search_idx.php';

        // Fetch posts to process this cycle
        $select_get_query_str = array('p.id', 'p.message', 't.subject', 't.first_post_id');

        $result = DB::for_table('posts')->table_alias('p')
                        ->select_many($select_get_query_str)
                        ->inner_join('topics', array('t.id', '=', 'p.topic_id'), 't')
                        ->where_gte('p.id', $start_at)
                        ->order_by_asc('p.id')
                        ->limit($per_page)
                        ->find_many();

        $end_at = 0;
        foreach ($result as $cur_item) {
            echo '<p><span>'.sprintf($lang_admin_maintenance['Processing post'], $cur_item['id']).'</span></p>'."\n";

            if ($cur_item['id'] == $cur_item['first_post_id']) {
                update_search_index('post', $cur_item['id'], $cur_item['message'], $cur_item['subject']);
            } else {
                update_search_index('post', $cur_item['id'], $cur_item['message']);
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

        $topic_ids = array();
        foreach ($topics_id as $row) {
            $topic_ids[] = $row['id'];
        }

        if (!empty($topic_ids)) {
            // Fetch posts to prune
            $posts_id = DB::for_table('posts')->select('id')
                            ->where_in('topic_id', $topic_ids)
                            ->find_many();

            $post_ids = array();
            foreach ($posts_id as $row) {
                $post_ids[] = $row['id'];
            }

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
                require_once FEATHER_ROOT.'include/search_idx.php';
                strip_search_index($post_ids);
            }
        }
    }

    public function prune_comply($prune_from, $prune_sticky)
    {
        global $lang_admin_maintenance;

        $prune_days = intval($this->request->post('prune_days'));
        $prune_date = ($prune_days) ? time() - ($prune_days * 86400) : -1;

        @set_time_limit(0);

        if ($prune_from == 'all') {
            $result = DB::for_table('forums')->select('id')->find_array();

            if (!empty($result)) {
                foreach ($result as $row) {
                    $this->prune($row['id'], $prune_sticky, $prune_date);
                    update_forum($row['id']);
                }
            }
        } else {
            $prune_from = intval($prune_from);
            $this->prune($prune_from, $prune_sticky, $prune_date);
            update_forum($prune_from);
        }

        // Locate any "orphaned redirect topics" and delete them
        $result = DB::for_table('topics')->table_alias('t1')
                        ->select('t1.id')
                        ->left_outer_join('topics', array('t1.moved_to', '=', 't2.id'), 't2')
                        ->where_null('t2.id')
                        ->where_not_null('t1.moved_to')
                        ->find_array();

        $orphans = array();
        if (!empty($result)) {
            foreach ($result as $row) {
                $orphans[] = $row['id'];
            }

            DB::for_table('topics')
                    ->where_in('id', $orphans)
                    ->delete_many();
        }

        redirect(get_link('admin/maintenance/'), $lang_admin_maintenance['Posts pruned redirect']);
    }

    public function get_info_prune($prune_sticky, $prune_from)
    {
        global $lang_admin_maintenance;

        $prune = array();

        $prune['days'] = feather_trim($this->request->post('req_prune_days'));
        if ($prune['days'] == '' || preg_match('%[^0-9]%', $prune['days'])) {
            message($lang_admin_maintenance['Days must be integer message']);
        }

        $prune['date'] = time() - ($prune['days'] * 86400);

        // Concatenate together the query for counting number of topics to prune
        $query = DB::for_table('topics')->where_lt('last_post', $prune['date'])
                        ->where_null('moved_to');

        if ($prune_sticky == '0') {
            $query = $query->where('sticky', 0);
        }

        if ($prune_from != 'all') {
            $query = $query->where('forum_id', intval($prune_from));

            // Fetch the forum name (just for cosmetic reasons)
            $forum = DB::for_table('forums')->where('id', $prune_from)
                        ->find_one_col('forum_name');
            $prune['forum'] = '"'.feather_escape($forum).'"';
        } else {
            $prune['forum'] = $lang_admin_maintenance['All forums'];
        }

        $prune['num_topics'] = $query->count('id');

        if (!$prune['num_topics']) {
            message(sprintf($lang_admin_maintenance['No old topics message'], $prune['days']));
        }

        return $prune;
    }

    public function get_categories()
    {
        $output = '';

        $select_get_categories = array('cid' => 'c.id', 'c.cat_name', 'fid' => 'f.id', 'f.forum_name');
        $order_by_get_categories = array('c.disp_position', 'c.id', 'f.disp_position');

        $result = DB::for_table('categories')
                    ->table_alias('c')
                    ->select_many($select_get_categories)
                    ->inner_join('forums', array('c.id', '=', 'f.cat_id'), 'f')
                    ->where_null('f.redirect_url')
                    ->order_by_many($order_by_get_categories)
                    ->find_many();

        $cur_category = 0;
        foreach ($result as $forum) {
            if ($forum['cid'] != $cur_category) {
                // Are we still in the same category?

                if ($cur_category) {
                    $output .= "\t\t\t\t\t\t\t\t\t\t\t".'</optgroup>'."\n";
                }

                $output .=  "\t\t\t\t\t\t\t\t\t\t\t".'<optgroup label="'.feather_escape($forum['cat_name']).'">'."\n";
                $cur_category = $forum['cid'];
            }

            $output .=  "\t\t\t\t\t\t\t\t\t\t\t\t".'<option value="'.$forum['fid'].'">'.feather_escape($forum['forum_name']).'</option>'."\n";
        }
        
        return $output;
    }
    
    public function get_first_id()
    {
        $first_id = '';
        $first_id_sql = DB::for_table('posts')->order_by_asc('id')
                            ->find_one_col('id');
        if ($first_id_sql) {
            $first_id = $first_id_sql;
        }
        
        return $first_id;
    }
}