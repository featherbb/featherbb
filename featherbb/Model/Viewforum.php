<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace FeatherBB\Model;

use FeatherBB\Utils;
use DB;

class Viewforum
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

    // Returns basic informations about the forum
    public function get_info_forum($id)
    {
        $id = $this->hook->fire('get_info_forum_start', $id);

        $cur_forum['where'] = array(
            array('fp.read_forum' => 'IS NULL'),
            array('fp.read_forum' => '1')
        );

        if (!$this->user->is_guest) {
            $cur_forum['select'] = array('f.forum_name', 'f.redirect_url', 'f.moderators', 'f.num_topics', 'f.sort_by', 'fp.post_topics', 'is_subscribed' => 's.user_id');

            $cur_forum = DB::for_table('forums')->table_alias('f')
                            ->select_many($cur_forum['select'])
                            ->left_outer_join('forum_subscriptions', array('f.id', '=', 's.forum_id'), 's')
                            ->left_outer_join('forum_subscriptions', array('s.user_id', '=', $this->user->id), null, true)
                            ->left_outer_join('forum_perms', array('fp.forum_id', '=', 'f.id'), 'fp')
                            ->left_outer_join('forum_perms', array('fp.group_id', '=', $this->user->g_id), null, true)
                            ->where_any_is($cur_forum['where'])
                            ->where('f.id', $id);
        } else {
            $cur_forum['select'] = array('f.forum_name', 'f.redirect_url', 'f.moderators', 'f.num_topics', 'f.sort_by', 'fp.post_topics');

            $cur_forum = DB::for_table('forums')->table_alias('f')
                            ->select_many($cur_forum['select'])
                            ->select_expr(0, 'is_subscribed')
                            ->left_outer_join('forum_perms', array('fp.forum_id', '=', 'f.id'), 'fp')
                            ->left_outer_join('forum_perms', array('fp.group_id', '=', $this->user->g_id), null, true)
                            ->where_any_is($cur_forum['where'])
                            ->where('f.id', $id);
        }

        $cur_forum = $this->hook->fireDB('get_info_forum_query', $cur_forum);
        $cur_forum = $cur_forum->find_one();

        if (!$cur_forum) {
            throw new \FeatherBB\Error(__('Bad request'), '404');
        }

        $cur_forum = $this->hook->fire('get_info_forum', $cur_forum);

        return $cur_forum;
    }

    // Returns the text required by the query to sort the forum
    public function sort_forum_by($sort_by_sql)
    {
        $sort_by_sql = $this->hook->fire('sort_forum_by_start', $sort_by_sql);

        switch ($sort_by_sql) {
            case 0:
                $sort_by = 'last_post DESC';
                break;
            case 1:
                $sort_by = 'posted DESC';
                break;
            case 2:
                $sort_by = 'subject ASC';
                break;
            default:
                $sort_by = 'last_post DESC';
                break;
        }

        $sort_by = $this->hook->fire('sort_forum_by', $sort_by);

        return $sort_by;
    }

    // Returns forum action
    public function get_forum_actions($forum_id, $subscriptions, $is_subscribed)
    {
        $forum_actions = array();

        $forum_actions = $this->hook->fire('get_page_head_start', $forum_actions, $forum_id, $subscriptions, $is_subscribed);

        if (!$this->user->is_guest) {
            if ($subscriptions == 1) {
                if ($is_subscribed) {
                    $forum_actions[] = '<span>'.__('Is subscribed').' - </span><a href="'.$this->feather->url->get('unsubscribe/forum/'.$forum_id.'/').'">'.__('Unsubscribe').'</a>';
                } else {
                    $forum_actions[] = '<a href="'.$this->feather->url->get('subscribe/forum/'.$forum_id.'/').'">'.__('Subscribe').'</a>';
                }
            }

            $forum_actions[] = '<a href="'.$this->feather->url->get('mark-forum-read/'.$forum_id.'/').'">'.__('Mark forum read').'</a>';
        }

        $forum_actions = $this->hook->fire('get_page_head', $forum_actions);

        return $forum_actions;
    }

    // Returns the elements needed to display topics
    public function print_topics($forum_id, $sort_by, $start_from)
    {
        $forum_id = $this->hook->fire('print_topics_start', $forum_id, $sort_by, $start_from);

        // Get topic/forum tracking data
        if (!$this->user->is_guest) {
            $tracked_topics = get_tracked_topics();
        }

        // Retrieve a list of topic IDs, LIMIT is (really) expensive so we only fetch the IDs here then later fetch the remaining data
        $result = DB::for_table('topics')
                        ->select('id')
                        ->where('forum_id', $forum_id)
                        ->order_by_desc('sticky')
                        ->order_by_expr($sort_by)
                        ->order_by_desc('id')
                        ->limit($this->user->disp_topics)
                        ->offset($start_from);
        $result = $this->hook->fire('print_topics_ids_query', $result);
        $result = $result->find_many();

        $forum_data = array();

        // If there are topics in this forum
        if ($result) {
            $topic_ids = array();
            foreach($result as $cur_topic_id) {
                $topic_ids[] = $cur_topic_id['id'];
            }

            // Fetch list of topics to display on this page
            if ($this->user->is_guest || $this->config['o_show_dot'] == '0') {
                // Without "the dot"
                $result['select'] = array('id', 'poster', 'subject', 'posted', 'last_post', 'last_post_id', 'last_poster', 'num_views', 'num_replies', 'closed', 'sticky', 'moved_to');

                $result = DB::for_table('topics')
                            ->select_many($result['select'])
                            ->where_in('id', $topic_ids)
                            ->order_by_desc('sticky')
                            ->order_by_expr($sort_by)
                            ->order_by_desc('id');
            } else {
                // With "the dot"
                $result['select'] = array('has_posted' => 'p.poster_id', 't.id', 't.subject', 't.poster', 't.posted', 't.last_post', 't.last_post_id', 't.last_poster', 't.num_views', 't.num_replies', 't.closed', 't.sticky', 't.moved_to');

                $result = DB::for_table('topics')
                            ->table_alias('t')
                            ->select_many($result['select'])
                            ->left_outer_join('posts', array('t.id', '=', 'p.topic_id'), 'p')
                            ->left_outer_join('posts', array('p.poster_id', '=', $this->user->id), null, true)
                            ->where_in('t.id', $topic_ids)
                            ->group_by('t.id')
                            ->order_by_desc('sticky')
                            ->order_by_expr($sort_by)
                            ->order_by_desc('id');
            }

            $result = $this->hook->fireDB('print_topics_query', $result);
            $result = $result->find_many();

            $topic_count = 0;
            foreach ($result as $cur_topic) {
                ++$topic_count;
                $status_text = array();
                $cur_topic['item_status'] = ($topic_count % 2 == 0) ? 'roweven' : 'rowodd';
                $cur_topic['icon_type'] = 'icon';
                $url_subject = $this->feather->url->url_friendly($cur_topic['subject']);

                if (is_null($cur_topic['moved_to'])) {
                    $cur_topic['last_post_formatted'] = '<a href="'.$this->feather->url->get('post/'.$cur_topic['last_post_id'].'/#p'.$cur_topic['last_post_id']).'">'.$this->feather->utils->format_time($cur_topic['last_post']).'</a> <span class="byuser">'.__('by').' '.Utils::escape($cur_topic['last_poster']).'</span>';
                } else {
                    $cur_topic['last_post_formatted'] = '- - -';
                }

                if ($this->config['o_censoring'] == '1') {
                    $cur_topic['subject'] = censor_words($cur_topic['subject']);
                }

                if ($cur_topic['sticky'] == '1') {
                    $cur_topic['item_status'] .= ' isticky';
                    $status_text[] = '<span class="stickytext">'.__('Sticky').'</span>';
                }

                if ($cur_topic['moved_to'] != 0) {
                    $cur_topic['subject_formatted'] = '<a href="'.$this->feather->url->get('topic/'.$cur_topic['moved_to'].'/'.$url_subject.'/').'">'.Utils::escape($cur_topic['subject']).'</a> <span class="byuser">'.__('by').' '.Utils::escape($cur_topic['poster']).'</span>';
                    $status_text[] = '<span class="movedtext">'.__('Moved').'</span>';
                    $cur_topic['item_status'] .= ' imoved';
                } elseif ($cur_topic['closed'] == '0') {
                    $cur_topic['subject_formatted'] = '<a href="'.$this->feather->url->get('topic/'.$cur_topic['id'].'/'.$url_subject.'/').'">'.Utils::escape($cur_topic['subject']).'</a> <span class="byuser">'.__('by').' '.Utils::escape($cur_topic['poster']).'</span>';
                } else {
                    $cur_topic['subject_formatted'] = '<a href="'.$this->feather->url->get('topic/'.$cur_topic['id'].'/'.$url_subject.'/').'">'.Utils::escape($cur_topic['subject']).'</a> <span class="byuser">'.__('by').' '.Utils::escape($cur_topic['poster']).'</span>';
                    $status_text[] = '<span class="closedtext">'.__('Closed').'</span>';
                    $cur_topic['item_status'] .= ' iclosed';
                }

                if (!$this->user->is_guest && $cur_topic['last_post'] > $this->user->last_visit && (!isset($tracked_topics['topics'][$cur_topic['id']]) || $tracked_topics['topics'][$cur_topic['id']] < $cur_topic['last_post']) && (!isset($tracked_topics['forums'][$forum_id]) || $tracked_topics['forums'][$forum_id] < $cur_topic['last_post']) && is_null($cur_topic['moved_to'])) {
                    $cur_topic['item_status'] .= ' inew';
                    $cur_topic['icon_type'] = 'icon icon-new';
                    $cur_topic['subject_formatted'] = '<strong>'.$cur_topic['subject_formatted'].'</strong>';
                    $subject_new_posts = '<span class="newtext">[ <a href="'.$this->feather->url->get('topic/'.$cur_topic['id'].'/action/new/').'" title="'.__('New posts info').'">'.__('New posts').'</a> ]</span>';
                } else {
                    $subject_new_posts = null;
                }

                // Insert the status text before the subject
                $cur_topic['subject_formatted'] = implode(' ', $status_text).' '.$cur_topic['subject_formatted'];

                // Should we display the dot or not? :)
                if (!$this->user->is_guest && $this->config['o_show_dot'] == '1') {
                    if ($cur_topic['has_posted'] == $this->user->id) {
                        $cur_topic['subject_formatted'] = '<strong class="ipost">·&#160;</strong>'.$cur_topic['subject_formatted'];
                        $cur_topic['item_status'] .= ' iposted';
                    }
                }

                $num_pages_topic = ceil(($cur_topic['num_replies'] + 1) / $this->user->disp_posts);

                if ($num_pages_topic > 1) {
                    $subject_multipage = '<span class="pagestext">[ '.$this->feather->url->paginate($num_pages_topic, -1, 'topic/'.$cur_topic['id'].'/'.$url_subject.'/#').' ]</span>';
                } else {
                    $subject_multipage = null;
                }

                // Should we show the "New posts" and/or the multipage links?
                if (!empty($subject_new_posts) || !empty($subject_multipage)) {
                    $cur_topic['subject_formatted'] .= !empty($subject_new_posts) ? ' '.$subject_new_posts : '';
                    $cur_topic['subject_formatted'] .= !empty($subject_multipage) ? ' '.$subject_multipage : '';
                }

                $forum_data[] = $cur_topic;
            }
        }

        $forum_data = $this->hook->fire('print_topics', $forum_data);

        return $forum_data;
    }
}
