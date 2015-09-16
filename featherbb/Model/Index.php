<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace FeatherBB\Model;

use DB;
use FeatherBB\Core\Track;
use FeatherBB\Core\Url;
use FeatherBB\Core\Utils;

class Index
{

    public function __construct()
    {
        $this->feather = \Slim\Slim::getInstance();
        $this->start = $this->feather->start;
        $this->config = $this->feather->config;
        $this->user = $this->feather->user;
        $this->request = $this->feather->request;
        $this->hook = $this->feather->hooks;
    }

    // Returns page head
    public function get_page_head()
    {
        $this->hook->fire('model.get_page_head_start');

        if ($this->config['o_feed_type'] == '1') {
            $page_head = array('feed' => '<link rel="alternate" type="application/rss+xml" href="extern.php?action=feed&amp;type=rss" title="'.__('RSS active topics feed').'" />');
        } elseif ($this->config['o_feed_type'] == '2') {
            $page_head = array('feed' => '<link rel="alternate" type="application/atom+xml" href="extern.php?action=feed&amp;type=atom" title="'.__('Atom active topics feed').'" />');
        }

        $page_head = $this->hook->fire('model.get_page_head', $page_head);

        return $page_head;
    }

    // Returns forum action
    public function get_forum_actions()
    {
        $this->hook->fire('model.get_forum_actions_start');

        $forum_actions = array();

        // Display a "mark all as read" link
        if (!$this->user->is_guest) {
            $forum_actions[] = '<a href="'.$this->feather->urlFor('markRead').'">'.__('Mark all as read').'</a>';
        }

        $forum_actions = $this->hook->fire('model.get_forum_actions', $forum_actions);

        return $forum_actions;
    }

    // Detects if a "new" icon has to be displayed
    protected function get_new_posts()
    {
        $this->hook->fire('model.get_new_posts_start');

        $query['select'] = array('f.id', 'f.last_post');
        $query['where'] = array(
            array('fp.read_forum' => 'IS NULL'),
            array('fp.read_forum' => '1')
        );

        $query = DB::for_table('forums')
            ->table_alias('f')
            ->select_many($query['select'])
            ->left_outer_join('forum_perms', array('fp.forum_id', '=', 'f.id'), 'fp')
            ->left_outer_join('forum_perms', array('fp.group_id', '=', $this->user->g_id), null, true)
            ->where_any_is($query['where'])
            ->where_gt('f.last_post', $this->user->last_visit);

        $query = $this->hook->fireDB('query_get_new_posts', $query);

        $query = $query->find_result_set();

        $forums = $new_topics = array();
        $tracked_topics = Track::get_tracked_topics();

        foreach ($query as $cur_forum) {
            if (!isset($tracked_topics['forums'][$cur_forum->id]) || $tracked_topics['forums'][$cur_forum->id] < $cur_forum->last_post) {
                $forums[$cur_forum->id] = $cur_forum->last_post;
            }
        }

        if (!empty($forums)) {
            if (empty($tracked_topics['topics'])) {
                $new_topics = $forums;
            } else {
                $query['select'] = array('forum_id', 'id', 'last_post');

                $query = DB::for_table('topics')
                    ->select_many($query['select'])
                    ->where_in('forum_id', array_keys($forums))
                    ->where_gt('last_post', $this->user->last_visit)
                    ->where_null('moved_to');

                $query = $this->hook->fireDB('get_new_posts_query', $query);

                $query = $query->find_result_set();

                foreach ($query as $cur_topic) {
                    if (!isset($new_topics[$cur_topic->forum_id]) && (!isset($tracked_topics['forums'][$cur_topic->forum_id]) || $tracked_topics['forums'][$cur_topic->forum_id] < $forums[$cur_topic->forum_id]) && (!isset($tracked_topics['topics'][$cur_topic->id]) || $tracked_topics['topics'][$cur_topic->id] < $cur_topic->last_post)) {
                        $new_topics[$cur_topic->forum_id] = $forums[$cur_topic->forum_id];
                    }
                }
            }
        }

        $new_topics = $this->hook->fire('model.get_new_posts', $new_topics);

        return $new_topics;
    }

    // Returns the elements needed to display categories and their forums
    public function print_categories_forums()
    {
        $this->hook->fire('model.print_categories_forums_start');

        // Get list of forums and topics with new posts since last visit
        if (!$this->user->is_guest) {
            $new_topics = $this->get_new_posts();
        }

        $query['select'] = array('cid' => 'c.id', 'c.cat_name', 'fid' => 'f.id', 'f.forum_name', 'f.forum_desc', 'f.redirect_url', 'f.moderators', 'f.num_topics', 'f.num_posts', 'f.last_post', 'f.last_post_id', 'f.last_poster');
        $query['where'] = array(
            array('fp.read_forum' => 'IS NULL'),
            array('fp.read_forum' => '1')
        );
        $query['order_by'] = array('c.disp_position', 'c.id', 'f.disp_position');

        $query = DB::for_table('categories')
            ->table_alias('c')
            ->select_many($query['select'])
            ->inner_join('forums', array('c.id', '=', 'f.cat_id'), 'f')
            ->left_outer_join('forum_perms', array('fp.forum_id', '=', 'f.id'), 'fp')
            ->left_outer_join('forum_perms', array('fp.group_id', '=', $this->user->g_id), null, true)
            ->where_any_is($query['where'])
            ->order_by_many($query['order_by']);

        $query = $this->hook->fireDB('query_print_categories_forums', $query);

        $query = $query->find_result_set();

        $index_data = array();
        $i = 0;
        foreach ($query as $cur_forum) {
            if ($i == 0) {
                $cur_forum->cur_category = 0;
                $cur_forum->forum_count_formatted = 0;
            }

            if (isset($cur_forum->cur_category)) {
                $cur_cat = $cur_forum->cur_category;
            } else {
                $cur_cat = 0;
            }

            if ($cur_forum->cid != $cur_cat) {
                // A new category since last iteration?
                $cur_forum->forum_count_formatted = 0;
                $cur_forum->cur_category = $cur_forum->cid;
            }

            ++$cur_forum->forum_count_formatted;

            $cur_forum->item_status = ($cur_forum->forum_count_formatted % 2 == 0) ? 'roweven' : 'rowodd';
            $forum_field_new = '';
            $cur_forum->icon_type = 'icon';

            // Are there new posts since our last visit?
            if (isset($new_topics[$cur_forum->fid])) {
                $cur_forum->item_status .= ' inew';
                $forum_field_new = '<span class="newtext">[ <a href="'.$this->feather->urlFor('quickSearch', ['show' => 'new&amp;fid='.$cur_forum->fid]).'">'.__('New posts').'</a> ]</span>';
                $cur_forum->icon_type = 'icon icon-new';
            }

            // Is this a redirect forum?
            if ($cur_forum->redirect_url != '') {
                $cur_forum->forum_field = '<h3><span class="redirtext">'.__('Link to').'</span> <a href="'.Utils::escape($cur_forum->redirect_url).'" title="'.__('Link to').' '.Utils::escape($cur_forum->redirect_url).'">'.Utils::escape($cur_forum->forum_name).'</a></h3>';
                $cur_forum->num_topics_formatted = $cur_forum->num_posts_formatted = '-';
                $cur_forum->item_status .= ' iredirect';
                $cur_forum->icon_type = 'icon';
            } else {
                $forum_name = Url::url_friendly($cur_forum->forum_name);
                $cur_forum->forum_field = '<h3><a href="'.$this->feather->urlFor('Forum', ['id' => $cur_forum->fid, 'name' => $forum_name]).'">'.Utils::escape($cur_forum->forum_name).'</a>'.(!empty($forum_field_new) ? ' '.$forum_field_new : '').'</h3>';
                $cur_forum->num_topics_formatted = $cur_forum->num_topics;
                $cur_forum->num_posts_formatted = $cur_forum->num_posts;
            }

            if ($cur_forum->forum_desc != '') {
                $cur_forum->forum_field .= "\n\t\t\t\t\t\t\t\t".'<div class="forumdesc">'.$cur_forum->forum_desc.'</div>';
            }

            // If there is a last_post/last_poster
            if ($cur_forum->last_post != '') {
                $cur_forum->last_post_formatted = '<a href="'.$this->feather->urlFor('viewPost', ['pid' => $cur_forum->last_post_id]).'#p'.$cur_forum->last_post_id.'">'.$this->feather->utils->format_time($cur_forum->last_post).'</a> <span class="byuser">'.__('by').' '.Utils::escape($cur_forum->last_poster).'</span>';
            } elseif ($cur_forum->redirect_url != '') {
                $cur_forum->last_post_formatted = '- - -';
            } else {
                $cur_forum->last_post_formatted = __('Never');
            }

            if ($cur_forum->moderators != '') {
                $mods_array = unserialize($cur_forum->moderators);
                $moderators = array();

                foreach ($mods_array as $mod_username => $mod_id) {
                    if ($this->user->g_view_users == '1') {
                        $moderators[] = '<a href="'.$this->feather->urlFor('userProfile', ['id' => $mod_id]).'">'.Utils::escape($mod_username).'</a>';
                    } else {
                        $moderators[] = Utils::escape($mod_username);
                    }
                }

                $cur_forum->moderators_formatted = "\t\t\t\t\t\t\t\t".'<p class="modlist">(<em>'.__('Moderated by').'</em> '.implode(', ', $moderators).')</p>'."\n";
            } else {
                $cur_forum->moderators_formatted = '';
            }

            $index_data[] = $cur_forum;
            ++$i;
        }

        $index_data = $this->hook->fire('model.print_categories_forums', $index_data);

        return $index_data;
    }

    // Returns the elements needed to display stats
    public function collect_stats()
    {
        $this->hook->fire('model.collect_stats_start');

        // Collect some statistics from the database
        if (!$this->feather->cache->isCached('users_info')) {
            $this->feather->cache->store('users_info', Cache::get_users_info());
        }

        $stats = $this->feather->cache->retrieve('users_info');

        $query = DB::for_table('forums')
            ->select_expr('SUM(num_topics)', 'total_topics')
            ->select_expr('SUM(num_posts)', 'total_posts');

        $query = $this->hook->fireDB('collect_stats_query', $query);

        $query = $query->find_one();

        $stats['total_topics'] = intval($query['total_topics']);
        $stats['total_posts'] = intval($query['total_posts']);

        if ($this->user->g_view_users == '1') {
            $stats['newest_user'] = '<a href="'.$this->feather->urlFor('userProfile', ['id' => $stats['last_user']['id']]).'">'.Utils::escape($stats['last_user']['username']).'</a>';
        } else {
            $stats['newest_user'] = Utils::escape($stats['last_user']['username']);
        }

        $stats = $this->hook->fire('model.collect_stats', $stats);

        return $stats;
    }

    // Returns the elements needed to display users online
    public function fetch_users_online()
    {
        $this->hook->fire('model.fetch_users_online_start');

        // Fetch users online info and generate strings for output
        $online = array();
        $online['num_guests'] = 0;

        $query['select'] = array('user_id', 'ident');
        $query['where'] = array('idle' => '0');
        $query['order_by'] = array('ident');

        $query = DB::for_table('online')
            ->select_many($query['select'])
            ->where($query['where'])
            ->order_by_many($query['order_by']);

        $query = $this->hook->fireDB('query_fetch_users_online', $query);

        $query = $query->find_result_set();

        foreach($query as $user_online) {
            if ($user_online->user_id > 1) {
                if ($this->user->g_view_users == '1') {
                    $online['users'][] = "\n\t\t\t\t".'<dd><a href="'.$this->feather->urlFor('userProfile', ['id' => $user_online->user_id]).'">'.Utils::escape($user_online->ident).'</a>';
                } else {
                    $online['users'][] = "\n\t\t\t\t".'<dd>'.Utils::escape($user_online->ident);
                }
            } else {
                ++$online['num_guests'];
            }
        }

        if (isset($online['users'])) {
            $online['num_users'] = count($online['users']);
        } else {
            $online['num_users'] = 0;
        }

        $online = $this->hook->fire('model.fetch_users_online', $online);

        return $online;
    }
}
