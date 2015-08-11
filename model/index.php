<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace model;

use DB;

class index
{

    public function __construct()
    {
        $this->feather = \Slim\Slim::getInstance();
        $this->start = $this->feather->start;
        $this->config = $this->feather->config;
        $this->user = $this->feather->user;
        $this->request = $this->feather->request;
    }
    
    // Returns page head
    public function get_page_head()
    {
        global $lang_common;
        
        if ($this->config['o_feed_type'] == '1') {
            $page_head = array('feed' => '<link rel="alternate" type="application/rss+xml" href="extern.php?action=feed&amp;type=rss" title="'.$lang_common['RSS active topics feed'].'" />');
        } elseif ($this->config['o_feed_type'] == '2') {
            $page_head = array('feed' => '<link rel="alternate" type="application/atom+xml" href="extern.php?action=feed&amp;type=atom" title="'.$lang_common['Atom active topics feed'].'" />');
        }

        return $page_head;
    }

    // Returns forum action
    public function get_forum_actions()
    {
        global $lang_common;

        $forum_actions = array();

        // Display a "mark all as read" link
        if (!$this->user->is_guest) {
            $forum_actions[] = '<a href="'.get_link('mark-read/').'">'.$lang_common['Mark all as read'].'</a>';
        }

        return $forum_actions;
    }

    // Detects if a "new" icon has to be displayed
    public function get_new_posts()
    {   
        $select_get_new_posts = array('f.id', 'f.last_post');
        $where_get_new_posts_any = array(
            array('fp.read_forum' => 'IS NULL'),
            array('fp.read_forum' => '1')
        );

        $result = DB::for_table('forums')
            ->table_alias('f')
            ->select_many($select_get_new_posts)
            ->left_outer_join('forum_perms', array('fp.forum_id', '=', 'f.id'), 'fp')
            ->left_outer_join('forum_perms', array('fp.group_id', '=', $this->user->g_id), null, true)
            ->where_any_is($where_get_new_posts_any)
            ->where_gt('f.last_post', $this->user->last_visit)
            ->find_result_set();
        
        $forums = $new_topics = array();
        $tracked_topics = get_tracked_topics();

        foreach ($result as $cur_forum) {
            if (!isset($tracked_topics['forums'][$cur_forum->id]) || $tracked_topics['forums'][$cur_forum->id] < $cur_forum->last_post) {
                $forums[$cur_forum->id] = $cur_forum->last_post;
            }
        }

        if (!empty($forums)) {
            if (empty($tracked_topics['topics'])) {
                $new_topics = $forums;
            } else {   
                $select_get_new_posts_tracked_topics = array('forum_id', 'id', 'last_post');

                $result = DB::for_table('topics')
                    ->select_many($select_get_new_posts_tracked_topics)
                    ->where_in('forum_id', array_keys($forums))
                    ->where_gt('last_post', $this->user->last_visit)
                    ->where_null('moved_to')
                    ->find_result_set();

                foreach ($result as $cur_topic) {
                    if (!isset($new_topics[$cur_topic->forum_id]) && (!isset($tracked_topics['forums'][$cur_topic->forum_id]) || $tracked_topics['forums'][$cur_topic->forum_id] < $forums[$cur_topic->forum_id]) && (!isset($tracked_topics['topics'][$cur_topic->id]) || $tracked_topics['topics'][$cur_topic->id] < $cur_topic->last_post)) {
                        $new_topics[$cur_topic->forum_id] = $forums[$cur_topic->forum_id];
                    }
                }
            }
        }

            return $new_topics;
    }

    // Returns the elements needed to display categories and their forums
    public function print_categories_forums()
    {
        global $lang_common, $lang_index;

        // Get list of forums and topics with new posts since last visit
        if (!$this->user->is_guest) {
            $new_topics = $this->get_new_posts();
        }
        
        $select_print_categories_forums = array('cid' => 'c.id', 'c.cat_name', 'fid' => 'f.id', 'f.forum_name', 'f.forum_desc', 'f.redirect_url', 'f.moderators', 'f.num_topics', 'f.num_posts', 'f.last_post', 'f.last_post_id', 'f.last_poster');
        $where_print_categories_forums = array(
            array('fp.read_forum' => 'IS NULL'),
            array('fp.read_forum' => '1')
        );
        $order_by_print_categories_forums = array('c.disp_position', 'c.id', 'f.disp_position');
        
        $result = DB::for_table('categories')
            ->table_alias('c')
            ->select_many($select_print_categories_forums)
            ->inner_join('forums', array('c.id', '=', 'f.cat_id'), 'f')
            ->left_outer_join('forum_perms', array('fp.forum_id', '=', 'f.id'), 'fp')
            ->left_outer_join('forum_perms', array('fp.group_id', '=', $this->user->g_id), null, true)
            ->where_any_is($where_print_categories_forums)
            ->order_by_many($order_by_print_categories_forums)
            ->find_result_set();

        $index_data = array();
        $i = 0;
        foreach ($result as $cur_forum) {
            if ($i == 0) {
                $cur_forum->cur_category = 0;
                $cur_forum->forum_count_formatted = 0;
            }
            
            $moderators = '';

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
                $forum_field_new = '<span class="newtext">[ <a href="'.get_link('search/?action=show_new&amp;fid='.$cur_forum->fid).'">'.$lang_common['New posts'].'</a> ]</span>';
                $cur_forum->icon_type = 'icon icon-new';
            }

            // Is this a redirect forum?
            if ($cur_forum->redirect_url != '') {
                $cur_forum->forum_field = '<h3><span class="redirtext">'.$lang_index['Link to'].'</span> <a href="'.feather_escape($cur_forum->redirect_url).'" title="'.$lang_index['Link to'].' '.feather_escape($cur_forum->redirect_url).'">'.feather_escape($cur_forum->forum_name).'</a></h3>';
                $cur_forum->num_topics_formatted = $cur_forum->num_posts_formatted = '-';
                $cur_forum->item_status .= ' iredirect';
                $cur_forum->icon_type = 'icon';
            } else {
                $cur_forum->forum_field = '<h3><a href="'.get_link('forum/'.$cur_forum->fid.'/'.url_friendly($cur_forum->forum_name)).'/'.'">'.feather_escape($cur_forum->forum_name).'</a>'.(!empty($forum_field_new) ? ' '.$forum_field_new : '').'</h3>';
                $cur_forum->num_topics_formatted = $cur_forum->num_topics;
                $cur_forum->num_posts_formatted = $cur_forum->num_posts;
            }

            if ($cur_forum->forum_desc != '') {
                $cur_forum->forum_field .= "\n\t\t\t\t\t\t\t\t".'<div class="forumdesc">'.$cur_forum->forum_desc.'</div>';
            }

            // If there is a last_post/last_poster
            if ($cur_forum->last_post != '') {
                $cur_forum->last_post_formatted = '<a href="'.get_link('post/'.$cur_forum->last_post_id.'/#p'.$cur_forum->last_post_id).'">'.format_time($cur_forum->last_post).'</a> <span class="byuser">'.$lang_common['by'].' '.feather_escape($cur_forum->last_poster).'</span>';
            } elseif ($cur_forum->redirect_url != '') {
                $cur_forum->last_post_formatted = '- - -';
            } else {
                $cur_forum->last_post_formatted = $lang_common['Never'];
            }

            if ($cur_forum->moderators != '') {
                $mods_array = unserialize($cur_forum->moderators);
                $moderators = array();

                foreach ($mods_array as $mod_username => $mod_id) {
                    if ($this->user->g_view_users == '1') {
                        $moderators[] = '<a href="'.get_link('user/'.$mod_id.'/').'">'.feather_escape($mod_username).'</a>';
                    } else {
                        $moderators[] = feather_escape($mod_username);
                    }
                }

                $cur_forum->moderators_formatted = "\t\t\t\t\t\t\t\t".'<p class="modlist">(<em>'.$lang_common['Moderated by'].'</em> '.implode(', ', $moderators).')</p>'."\n";
            } else {
                $cur_forum->moderators_formatted = '';
            }

            $index_data[] = $cur_forum;
            ++$i;
        }

        return $index_data;
    }

    // Returns the elements needed to display stats
    public function collect_stats()
    {
        // Collect some statistics from the database
        if (file_exists(FORUM_CACHE_DIR.'cache_users_info.php')) {
            include FORUM_CACHE_DIR.'cache_users_info.php';
        }

        if (!defined('feather_userS_INFO_LOADED')) {
            if (!defined('FORUM_CACHE_FUNCTIONS_LOADED')) {
                require FEATHER_ROOT.'include/cache.php';
            }

            generate_users_info_cache();
            require FORUM_CACHE_DIR.'cache_users_info.php';
        }
        
        $stats_query = DB::for_table('forums')
                        ->select_expr('SUM(num_topics)', 'total_topics')
                        ->select_expr('SUM(num_posts)', 'total_posts')
                        ->find_one();
        
        $stats['total_topics'] = intval($stats_query['total_topics']);
        $stats['total_posts'] = intval($stats_query['total_posts']);

        if ($this->user->g_view_users == '1') {
            $stats['newest_user'] = '<a href="'.get_link('user/'.$stats['last_user']['id']).'/">'.feather_escape($stats['last_user']['username']).'</a>';
        } else {
            $stats['newest_user'] = feather_escape($stats['last_user']['username']);
        }

        return $stats;
    }

    // Returns the elements needed to display users online
    public function fetch_users_online()
    {
        // Fetch users online info and generate strings for output
        $num_guests = 0;
        $online = array();

        $select_fetch_users_online = array('user_id', 'ident');
        $where_fetch_users_online = array('idle' => '0');
        $order_by_fetch_users_online = array('ident');
        
        $result = DB::for_table('online')
            ->select_many($select_fetch_users_online)
            ->where($where_fetch_users_online)
            ->order_by_many($order_by_fetch_users_online)
            ->find_result_set();

        foreach($result as $user_online) {
            if ($user_online->user_id > 1) {
                if ($this->user->g_view_users == '1') {
                    $online['users'][] = "\n\t\t\t\t".'<dd><a href="'.get_link('user/'.$user_online->user_id).'/">'.feather_escape($user_online->ident).'</a>';
                } else {
                    $online['users'][] = "\n\t\t\t\t".'<dd>'.feather_escape($user_online->ident);
                }
            } else {
                ++$num_guests;
            }
        }

        if (isset($online['users'])) {
            $online['num_users'] = count($online['users']);
        } else {
            $online['num_users'] = 0;
        }
        $online['num_guests'] = $num_guests;

        return $online;
    }
}