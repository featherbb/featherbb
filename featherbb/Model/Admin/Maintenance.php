<?php

/**
 * Copyright (C) 2015-2019 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace FeatherBB\Model\Admin;

use FeatherBB\Core\Database as DB;
use FeatherBB\Core\Error;
use FeatherBB\Core\Interfaces\Hooks;
use FeatherBB\Core\Interfaces\Input;
use FeatherBB\Core\Interfaces\Router;
use FeatherBB\Core\Utils;

class Maintenance
{
    public function __construct()
    {
        $this->search = new \FeatherBB\Core\Search();
    }

    public function rebuild()
    {
        $perPage = Input::query('i_per_page') ? intval(Input::query('i_per_page')) : 0;
        $perPage = Hooks::fire('model.admin.maintenance.rebuild.per_page', $perPage);

        // Check per page is > 0
        if ($perPage < 1) {
            throw new Error(__('Posts must be integer message'), 400);
        }

        @set_time_limit(0);

        // If this is the first cycle of posts we empty the search index before we proceed
        if (Input::query('i_empty_index')) {
            DB::table('search_words')->rawExecute('TRUNCATE '.ForumEnv::get('DB_PREFIX').'search_words');
            DB::table('search_matches')->rawExecute('TRUNCATE '.ForumEnv::get('DB_PREFIX').'search_matches');

            // Reset the sequence for the search words (not needed for SQLite)
            switch (ForumEnv::get('DB_TYPE')) {
                case 'mysql':
                case 'mysqli':
                case 'mysql_innodb':
                case 'mysqli_innodb':
                    DB::table('search_words')->rawExecute('ALTER TABLE '.ForumEnv::get('DB_PREFIX').'search_words auto_increment=1');
                    break;

                case 'pgsql':
                    DB::table('search_words')->rawExecute('SELECT setval(\''.ForumEnv::get('DB_PREFIX').'search_words_id_seq\', 1, false)');
            }
        }
    }

    public function getQueryString()
    {
        $queryStr = [];

        $perPage = Input::query('i_per_page') ? intval(Input::query('i_per_page')) : 0;
        $perPage = Hooks::fire('model.admin.maintenance.get_query_str.per_page', $perPage);
        $startAt = Input::query('i_start_at') ? intval(Input::query('i_start_at')) : 0;
        $startAt = Hooks::fire('model.admin.maintenance.get_query_str.start_at', $startAt);

        // Fetch posts to process this cycle
        $result['select'] = ['p.id', 'p.message', 't.subject', 't.first_post_id'];

        $result = DB::table('posts')->tableAlias('p')
                        ->selectMany($result['select'])
                        ->innerJoin('topics', ['t.id', '=', 'p.topic_id'], 't')
                        ->whereGte('p.id', $startAt)
                        ->orderByAsc('p.id')
                        ->limit($perPage);
        $result = Hooks::fireDB('model.admin.maintenance.get_query_str.query', $result);
        $result = $result->findMany();

        $endAt = 0;
        foreach ($result as $curItem) {
            $queryStr['id'] = $curItem['id'];

            if ($curItem['id'] == $curItem['first_post_id']) {
                $this->search->updateSearchIndex('post', $curItem['id'], $curItem['message'], $curItem['subject']);
            } else {
                $this->search->updateSearchIndex('post', $curItem['id'], $curItem['message']);
            }

            $endAt = $curItem['id'];
        }

        // Check if there is more work to do
        if ($endAt > 0) {
            $id = DB::table('posts')->whereGt('id', $endAt)
                        ->orderByAsc('id')
                        ->findOneCol('id');

            if ($id) {
                $queryStr['str'] = '?action=rebuild&i_per_page='.$perPage.'&i_start_at='.intval($id);
            }
        }

        $pdo = DB::getDb();
        $pdo = null;

        $queryStr = Hooks::fire('model.admin.maintenance.get_query_str', $queryStr);
        return $queryStr;
    }

    //
    // Delete topics from $forumId that are "older than" $pruneDate (if $pruneSticky is 1, sticky topics will also be deleted)
    //
    public function prune($forumId, $pruneSticky, $pruneDate)
    {
        // Fetch topics to prune
        $topicsId = DB::table('topics')->select('id')
                    ->where('forum_id', $forumId);

        if ($pruneDate != -1) {
            $topicsId = $topicsId->whereLt('last_post', $pruneDate);
        }

        if (!$pruneSticky) {
            $topicsId = $topicsId->where('sticky', 0);
        }

        $topicsId = $topicsId->findMany();

        $topicIds = [];
        foreach ($topicsId as $row) {
            $topicIds[] = $row['id'];
        }
        $topicIds = Hooks::fire('model.admin.maintenance.prune.topic_ids', $topicIds);

        if (!empty($topicIds)) {
            // Fetch posts to prune
            $postsId = DB::table('posts')->select('id')
                            ->whereIn('topic_id', $topicIds)
                            ->findMany();

            $postIds = [];
            foreach ($postsId as $row) {
                $postIds[] = $row['id'];
            }
            $postIds = Hooks::fire('model.admin.maintenance.prune.post_ids', $postIds);

            if ($postIds != '') {
                // Delete topics
                DB::table('topics')
                        ->whereIn('id', $topicIds)
                        ->deleteMany();
                // Delete subscriptions
                DB::table('topic_subscriptions')
                        ->whereIn('topic_id', $topicIds)
                        ->deleteMany();
                // Delete posts
                DB::table('posts')
                        ->whereIn('id', $postIds)
                        ->deleteMany();

                // We removed a bunch of posts, so now we have to update the search index
                $this->search->stripSearchIndex($postIds);
            }
        }
    }

    public function pruneComply($pruneFrom, $pruneSticky)
    {
        $pruneDays = intval(Input::post('prune_days'));
        $pruneDays = Hooks::fire('model.admin.maintenance.prune_comply.prune_days', $pruneDays);
        $pruneDate = ($pruneDays) ? time() - ($pruneDays * 86400) : -1;

        @set_time_limit(0);

        if ($pruneFrom == 'all') {
            $result = DB::table('forums')->select('id');
            $result = Hooks::fireDB('model.admin.maintenance.prune_comply.query', $result);
            $result = $result->findArray();

            if (!empty($result)) {
                foreach ($result as $row) {
                    $this->prune($row['id'], $pruneSticky, $pruneDate);
                    \FeatherBB\Model\Forum::update($row['id']);
                }
            }
        } else {
            $pruneFrom = intval($pruneFrom);
            $this->prune($pruneFrom, $pruneSticky, $pruneDate);
            \FeatherBB\Model\Forum::update($pruneFrom);
        }

        // Locate any "orphaned redirect topics" and delete them
        $result = DB::table('topics')->tableAlias('t1')
                        ->select('t1.id')
                        ->leftOuterJoin('topics', ['t1.moved_to', '=', 't2.id'], 't2')
                        ->whereNull('t2.id')
                        ->whereNotNull('t1.moved_to');
        $result = Hooks::fireDB('model.admin.maintenance.prune_comply.orphans_query', $result);
        $result = $result->findArray();

        $orphans = [];
        if (!empty($result)) {
            foreach ($result as $row) {
                $orphans[] = $row['id'];
            }
            $orphans = Hooks::fire('model.admin.maintenance.prune_comply.orphans', $orphans);

            DB::table('topics')
                    ->whereIn('id', $orphans)
                    ->deleteMany();
        }

        return Router::redirect(Router::pathFor('adminMaintenance'), __('Posts pruned redirect'));
    }

    public function getInfoPrune($pruneSticky, $pruneFrom)
    {
        $prune = [];

        $prune['days'] = Utils::trim(Input::post('req_prune_days'));
        if ($prune['days'] == '' || preg_match('%[^0-9]%', $prune['days'])) {
            throw new Error(__('Days must be integer message'), 400);
        }

        $prune['date'] = time() - ($prune['days'] * 86400);

        $prune = Hooks::fire('model.admin.maintenance.get_info_prune.prune_dates', $prune);

        // Concatenate together the query for counting number of topics to prune
        $query = DB::table('topics')->whereLt('last_post', $prune['date'])
                        ->whereNull('moved_to');

        if ($pruneSticky == '0') {
            $query = $query->where('sticky', 0);
        }

        if ($pruneFrom != 'all') {
            $query = $query->where('forum_id', intval($pruneFrom));

            // Fetch the forum name (just for cosmetic reasons)
            $forum = DB::table('forums')->where('id', $pruneFrom);
            $forum = Hooks::fireDB('model.admin.maintenance.get_info_prune.forum_query', $forum);
            $forum = $forum->findOneCol('forum_name');

            $prune['forum'] = '"'.Utils::escape($forum).'"';
        } else {
            $prune['forum'] = __('All forums');
        }

        $prune['num_topics'] = $query->count('id');

        if (!$prune['num_topics']) {
            throw new Error(sprintf(__('No old topics message'), $prune['days']), 404);
        }

        $prune = Hooks::fire('model.admin.maintenance.get_info_prune.prune', $prune);
        return $prune;
    }

    public function getCategories()
    {
        $output = '';

        $selectGetCategories = ['cid' => 'c.id', 'c.cat_name', 'fid' => 'f.id', 'f.forum_name'];
        $orderByGetCategories = ['c.disp_position', 'c.id', 'f.disp_position'];

        $result = DB::table('categories')
                    ->tableAlias('c')
                    ->selectMany($selectGetCategories)
                    ->innerJoin('forums', ['c.id', '=', 'f.cat_id'], 'f')
                    ->whereNull('f.redirect_url')
                    ->orderByMany($orderByGetCategories);
        $result = Hooks::fireDB('model.admin.maintenance.get_categories.query', $result);
        $result = $result->findMany();

        $curCategory = 0;
        foreach ($result as $forum) {
            if ($forum['cid'] != $curCategory) {
                // Are we still in the same category?

                if ($curCategory) {
                    $output .= "\t\t\t\t\t\t\t\t\t\t\t".'</optgroup>'."\n";
                }

                $output .=  "\t\t\t\t\t\t\t\t\t\t\t".'<optgroup label="'.Utils::escape($forum['cat_name']).'">'."\n";
                $curCategory = $forum['cid'];
            }

            $output .=  "\t\t\t\t\t\t\t\t\t\t\t\t".'<option value="'.$forum['fid'].'">'.Utils::escape($forum['forum_name']).'</option>'."\n";
        }

        $output = Hooks::fire('model.admin.maintenance.get_categories.output', $output);
        return $output;
    }

    public function getFirstId()
    {
        $firstId = '';
        $firstIdSql = DB::table('posts')->orderByAsc('id')
                            ->findOneCol('id');
        if ($firstIdSql) {
            $firstId = $firstIdSql;
        }

        $firstId = Hooks::fire('model.admin.maintenance.get_first_id', $firstId);
        return $firstId;
    }
}
