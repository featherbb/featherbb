<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace FeatherBB\Model;

use DB;
use FeatherBB\Core\Error;
use FeatherBB\Core\Track;
use FeatherBB\Core\Url;
use FeatherBB\Core\Utils;

class Forum
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
    public function get_forum_info($id)
    {
        $id = $this->hook->fire('model.get_info_forum_start', $id);

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
            throw new Error(__('Bad request'), '404');
        }

        $cur_forum = $this->hook->fire('model.get_info_forum', $cur_forum);

        return $cur_forum;
    }

    public function get_moderators($fid)
    {
        $moderators = DB::for_table('forums')
                        ->where('id', $fid);
        $moderators = $this->hook->fireDB('get_moderators', $moderators);
        $moderators = $moderators->find_one_col('moderators');

        return $moderators;
    }

    // Returns the text required by the query to sort the forum
    public function sort_forum_by($sort_by_sql)
    {
        $sort_by_sql = $this->hook->fire('model.sort_forum_by_start', $sort_by_sql);

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

        $sort_by = $this->hook->fire('model.sort_forum_by', $sort_by);

        return $sort_by;
    }

    // Returns forum action
    public function get_forum_actions($forum_id, $subscriptions, $is_subscribed)
    {
        $forum_actions = array();

        $forum_actions = $this->hook->fire('model.get_page_head_start', $forum_actions, $forum_id, $subscriptions, $is_subscribed);

        if (!$this->user->is_guest) {
            if ($subscriptions == 1) {
                if ($is_subscribed) {
                    $forum_actions[] = '<span>'.__('Is subscribed').' - </span><a href="'.$this->feather->urlFor('unsubscribeForum', ['id' => $forum_id]).'">'.__('Unsubscribe').'</a>';
                } else {
                    $forum_actions[] = '<a href="'.$this->feather->urlFor('subscribeForum', ['id' => $forum_id]).'">'.__('Subscribe').'</a>';
                }
            }

            $forum_actions[] = '<a href="'.$this->feather->urlFor('markForumRead', ['id' => $forum_id]).'">'.__('Mark forum read').'</a>';
        }

        $forum_actions = $this->hook->fire('model.get_page_head', $forum_actions);

        return $forum_actions;
    }

    // Returns the elements needed to display topics
    public function print_topics($forum_id, $sort_by, $start_from)
    {
        $forum_id = $this->hook->fire('model.print_topics_start', $forum_id, $sort_by, $start_from);

        // Get topic/forum tracking data
        if (!$this->user->is_guest) {
            $tracked_topics = Track::get_tracked_topics();
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
        $result = $this->hook->fire('model.print_topics_ids_query', $result);
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
                $url_subject = Url::url_friendly($cur_topic['subject']);

                if (is_null($cur_topic['moved_to'])) {
                    $cur_topic['last_post_formatted'] = '<a href="'.$this->feather->urlFor('viewPost', ['pid' => $cur_topic['last_post_id']]).'#p'.$cur_topic['last_post_id'].'">'.$this->feather->utils->format_time($cur_topic['last_post']).'</a> <span class="byuser">'.__('by').' '.Utils::escape($cur_topic['last_poster']).'</span>';
                } else {
                    $cur_topic['last_post_formatted'] = '- - -';
                }

                if ($this->config['o_censoring'] == '1') {
                    $cur_topic['subject'] = Utils::censor($cur_topic['subject']);
                }

                if ($cur_topic['sticky'] == '1') {
                    $cur_topic['item_status'] .= ' isticky';
                    $status_text[] = '<span class="stickytext">'.__('Sticky').'</span>';
                }

                if ($cur_topic['moved_to'] != 0) {
                    $cur_topic['subject_formatted'] = '<a href="'.$this->feather->urlFor('Topic', ['id' => $cur_topic['moved_to'], 'name' => $url_subject]).'">'.Utils::escape($cur_topic['subject']).'</a> <span class="byuser">'.__('by').' '.Utils::escape($cur_topic['poster']).'</span>';
                    $status_text[] = '<span class="movedtext">'.__('Moved').'</span>';
                    $cur_topic['item_status'] .= ' imoved';
                } elseif ($cur_topic['closed'] == '0') {
                    $cur_topic['subject_formatted'] = '<a href="'.$this->feather->urlFor('Topic', ['id' => $cur_topic['id'], 'name' => $url_subject]).'">'.Utils::escape($cur_topic['subject']).'</a> <span class="byuser">'.__('by').' '.Utils::escape($cur_topic['poster']).'</span>';
                } else {
                    $cur_topic['subject_formatted'] = '<a href="'.$this->feather->urlFor('Topic', ['id' => $cur_topic['id'], 'name' => $url_subject]).'">'.Utils::escape($cur_topic['subject']).'</a> <span class="byuser">'.__('by').' '.Utils::escape($cur_topic['poster']).'</span>';
                    $status_text[] = '<span class="closedtext">'.__('Closed').'</span>';
                    $cur_topic['item_status'] .= ' iclosed';
                }

                if (!$this->user->is_guest && $cur_topic['last_post'] > $this->user->last_visit && (!isset($tracked_topics['topics'][$cur_topic['id']]) || $tracked_topics['topics'][$cur_topic['id']] < $cur_topic['last_post']) && (!isset($tracked_topics['forums'][$forum_id]) || $tracked_topics['forums'][$forum_id] < $cur_topic['last_post']) && is_null($cur_topic['moved_to'])) {
                    $cur_topic['item_status'] .= ' inew';
                    $cur_topic['icon_type'] = 'icon icon-new';
                    $cur_topic['subject_formatted'] = '<strong>'.$cur_topic['subject_formatted'].'</strong>';
                    $subject_new_posts = '<span class="newtext">[ <a href="'.$this->feather->urlFor('topicAction', ['id' => $cur_topic['id'], 'action' => 'new']).'" title="'.__('New posts info').'">'.__('New posts').'</a> ]</span>';
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
                    $subject_multipage = '<span class="pagestext">[ '.Url::paginate($num_pages_topic, -1, 'topic/'.$cur_topic['id'].'/'.$url_subject.'/#').' ]</span>';
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

        $forum_data = $this->hook->fire('model.print_topics', $forum_data);

        return $forum_data;
    }

    public function display_topics_moderate($fid, $sort_by, $start_from)
    {
        $this->hook->fire('model.display_topics_start', $fid, $sort_by, $start_from);

        $topic_data = array();

        // Get topic/forum tracking data
        if (!$this->user->is_guest) {
            $tracked_topics = Track::get_tracked_topics();
        }

        // Retrieve a list of topic IDs, LIMIT is (really) expensive so we only fetch the IDs here then later fetch the remaining data
        $result = DB::for_table('topics')->select('id')
                    ->where('forum_id', $fid)
                    ->order_by_expr('sticky DESC, '.$sort_by)
                    ->limit($this->user->disp_topics)
                    ->offset($start_from);
        $result = $this->hook->fireDB('display_topics_list_ids', $result);
        $result = $result->find_many();

        // If there are topics in this forum
        if ($result) {

            foreach ($result as $id) {
                $topic_ids[] = $id['id'];
            }

            unset($result);
            // Select topics
            $result['select'] = array('id', 'poster', 'subject', 'posted', 'last_post', 'last_post_id', 'last_poster', 'num_views', 'num_replies', 'closed', 'sticky', 'moved_to');
            $result = DB::for_table('topics')->select_many($result['select'])
                        ->where_in('id', $topic_ids)
                        ->order_by_desc('sticky')
                        ->order_by_expr($sort_by)
                        ->order_by_desc('id');
            $result = $this->hook->fireDB('display_topics_query', $result);
            $result = $result->find_many();

            $topic_count = 0;
            foreach($result as $cur_topic) {
                ++$topic_count;
                $status_text = array();
                $cur_topic['item_status'] = ($topic_count % 2 == 0) ? 'roweven' : 'rowodd';
                $cur_topic['icon_type'] = 'icon';
                $url_topic = Url::url_friendly($cur_topic['subject']);

                if (is_null($cur_topic['moved_to'])) {
                    $cur_topic['last_post_disp'] = '<a href="'.$this->feather->urlFor('viewPost', ['pid' => $cur_topic['last_post_id']]).'#p'.$cur_topic['last_post_id'].'">'.$this->feather->utils->format_time($cur_topic['last_post']).'</a> <span class="byuser">'.__('by').' '.Utils::escape($cur_topic['last_poster']).'</span>';
                    $cur_topic['ghost_topic'] = false;
                } else {
                    $cur_topic['last_post_disp'] = '- - -';
                    $cur_topic['ghost_topic'] = true;
                }

                if ($this->config['o_censoring'] == '1') {
                    $cur_topic['subject'] = Utils::censor($cur_topic['subject']);
                }

                if ($cur_topic['sticky'] == '1') {
                    $cur_topic['item_status'] .= ' isticky';
                    $status_text[] = '<span class="stickytext">'.__('Sticky').'</span>';
                }

                if ($cur_topic['moved_to'] != 0) {
                    $cur_topic['subject_disp'] = '<a href="'.$this->feather->urlFor('Topic', ['id' => $cur_topic['moved_to'], 'name' => $url_topic]).'">'.Utils::escape($cur_topic['subject']).'</a> <span class="byuser">'.__('by').' '.Utils::escape($cur_topic['poster']).'</span>';
                    $status_text[] = '<span class="movedtext">'.__('Moved').'</span>';
                    $cur_topic['item_status'] .= ' imoved';
                } elseif ($cur_topic['closed'] == '0') {
                    $cur_topic['subject_disp'] = '<a href="'.$this->feather->urlFor('Topic', ['id' => $cur_topic['id'], 'name' => $url_topic]).'">'.Utils::escape($cur_topic['subject']).'</a> <span class="byuser">'.__('by').' '.Utils::escape($cur_topic['poster']).'</span>';
                } else {
                    $cur_topic['subject_disp'] = '<a href="'.$this->feather->urlFor('Topic', ['id' => $cur_topic['id'], 'name' => $url_topic]).'">'.Utils::escape($cur_topic['subject']).'</a> <span class="byuser">'.__('by').' '.Utils::escape($cur_topic['poster']).'</span>';
                    $status_text[] = '<span class="closedtext">'.__('Closed').'</span>';
                    $cur_topic['item_status'] .= ' iclosed';
                }

                if (!$cur_topic['ghost_topic'] && $cur_topic['last_post'] > $this->user->last_visit && (!isset($tracked_topics['topics'][$cur_topic['id']]) || $tracked_topics['topics'][$cur_topic['id']] < $cur_topic['last_post']) && (!isset($tracked_topics['forums'][$fid]) || $tracked_topics['forums'][$fid] < $cur_topic['last_post'])) {
                    $cur_topic['item_status'] .= ' inew';
                    $cur_topic['icon_type'] = 'icon icon-new';
                    $cur_topic['subject_disp'] = '<strong>'.$cur_topic['subject_disp'].'</strong>';
                    $subject_new_posts = '<span class="newtext">[ <a href="'.$this->feather->urlFor('Topic', ['id' => $cur_topic['id'], 'action' => 'new']).'" title="'.__('New posts info').'">'.__('New posts').'</a> ]</span>';
                } else {
                    $subject_new_posts = null;
                }

                // Insert the status text before the subject
                $cur_topic['subject_disp'] = implode(' ', $status_text).' '.$cur_topic['subject_disp'];

                $num_pages_topic = ceil(($cur_topic['num_replies'] + 1) / $this->user->disp_posts);

                if ($num_pages_topic > 1) {
                    $subject_multipage = '<span class="pagestext">[ '.Url::paginate($num_pages_topic, -1, 'topic/'.$cur_topic['id'].'/'.$url_topic.'/#').' ]</span>';
                } else {
                    $subject_multipage = null;
                }

                // Should we show the "New posts" and/or the multipage links?
                if (!empty($subject_new_posts) || !empty($subject_multipage)) {
                    $cur_topic['subject_disp'] .= !empty($subject_new_posts) ? ' '.$subject_new_posts : '';
                    $cur_topic['subject_disp'] .= !empty($subject_multipage) ? ' '.$subject_multipage : '';
                }

                $topic_data[] = $cur_topic;
            }
        }

        $topic_data = $this->hook->fire('model.display_topics', $topic_data);

        return $topic_data;
    }

    //
    // Update posts, topics, last_post, last_post_id and last_poster for a forum
    //
    public static function update($forum_id)
    {
        $stats_query = DB::for_table('topics')
                            ->where('forum_id', $forum_id)
                            ->select_expr('COUNT(id)', 'total_topics')
                            ->select_expr('SUM(num_replies)', 'total_replies')
                            ->find_one();

        $num_topics = intval($stats_query['total_topics']);
        $num_replies = intval($stats_query['total_replies']);

        $num_posts = $num_replies + $num_topics; // $num_posts is only the sum of all replies (we have to add the topic posts)

        $select_update_forum = array('last_post', 'last_post_id', 'last_poster');

        $result = DB::for_table('topics')->select_many($select_update_forum)
                    ->where('forum_id', $forum_id)
                    ->where_null('moved_to')
                    ->order_by_desc('last_post')
                    ->find_one();

        if ($result) {
            // There are topics in the forum
            $insert_update_forum = array(
                'num_topics' => $num_topics,
                'num_posts'  => $num_posts,
                'last_post'  => $result['last_post'],
                'last_post_id'  => $result['last_post_id'],
                'last_poster'  => $result['last_poster'],
            );
        } else {
            // There are no topics
            $insert_update_forum = array(
                'num_topics' => $num_topics,
                'num_posts'  => $num_posts,
                'last_post'  => 'NULL',
                'last_post_id'  => 'NULL',
                'last_poster'  => 'NULL',
            );
        }
        DB::for_table('forums')
            ->where('id', $forum_id)
            ->find_one()
            ->set($insert_update_forum)
            ->save();
    }

    public function unsubscribe($forum_id)
    {
        $forum_id = $this->hook->fire('model.unsubscribe_forum_start', $forum_id);

        if ($this->config['o_forum_subscriptions'] != '1') {
            throw new Error(__('No permission'), 403);
        }

        $is_subscribed = DB::for_table('forum_subscriptions')
            ->where('user_id', $this->user->id)
            ->where('forum_id', $forum_id);
        $is_subscribed = $this->hook->fireDB('unsubscribe_forum_subscribed_query', $is_subscribed);
        $is_subscribed = $is_subscribed->find_one();

        if (!$is_subscribed) {
            throw new Error(__('Not subscribed forum'), 400);
        }

        // Delete the subscription
        $delete = DB::for_table('forum_subscriptions')
            ->where('user_id', $this->user->id)
            ->where('forum_id', $forum_id);
        $delete = $this->hook->fireDB('unsubscribe_forum_query', $delete);
        $delete->delete_many();
    }

    public function subscribe($forum_id)
    {
        $forum_id = $this->hook->fire('model.subscribe_forum_start', $forum_id);

        if ($this->config['o_forum_subscriptions'] != '1') {
            throw new Error(__('No permission'), 403);
        }

        // Make sure the user can view the forum
        $authorized['where'] = array(
            array('fp.read_forum' => 'IS NULL'),
            array('fp.read_forum' => '1')
        );

        $authorized = DB::for_table('forums')
                        ->table_alias('f')
                        ->left_outer_join('forum_perms', array('fp.forum_id', '=', 'f.id'), 'fp')
                        ->left_outer_join('forum_perms', array('fp.group_id', '=', $this->user->g_id), null, true)
                        ->where_any_is($authorized['where'])
                        ->where('f.id', $forum_id);
        $authorized = $this->hook->fireDB('subscribe_forum_authorized_query', $authorized);
        $authorized = $authorized->find_one();

        if (!$authorized) {
            throw new Error(__('Bad request'), 404);
        }

        $is_subscribed = DB::for_table('forum_subscriptions')
            ->where('user_id', $this->user->id)
            ->where('forum_id', $forum_id);
        $is_subscribed = $this->hook->fireDB('subscribe_forum_subscribed_query', $is_subscribed);
        $is_subscribed = $is_subscribed->find_one();

        if ($is_subscribed) {
            throw new Error(__('Already subscribed forum'), 400);
        }

        // Insert the subscription
        $subscription['insert'] = array(
            'user_id' => $this->user->id,
            'forum_id'  => $forum_id
        );
        $subscription = DB::for_table('forum_subscriptions')
                            ->create()
                            ->set($subscription['insert']);
        $subscription = $this->hook->fireDB('subscribe_forum_query', $subscription);
        $subscription->save();
    }

    public function close_multiple_topics($action, $topics)
    {
        $close_multiple_topics = DB::for_table('topics')
                                    ->where_in('id', $topics);
        $close_multiple_topics = $this->hook->fireDB('open_topic', $close_multiple_topics);
        $close_multiple_topics = $close_multiple_topics->update_many('closed', $action);
    }

    public function delete_topics($topics, $fid)
    {
        $this->hook->fire('model.delete_topics', $topics, $fid);

        if (@preg_match('%[^0-9,]%', $topics)) {
            throw new Error(__('Bad request'), 400);
        }

        $topics_sql = explode(',', $topics);

        // Verify that the topic IDs are valid
        $result = DB::for_table('topics')
                    ->where_in('id', $topics_sql)
                    ->where('forum_id', $fid);
        $result = $this->hook->fireDB('delete_topics_verify_id', $result);
        $result = $result->find_many();

        if (count($result) != substr_count($topics, ',') + 1) {
            throw new Error(__('Bad request'), 400);
        }

        // Verify that the posts are not by admins
        if ($this->user->g_id != $this->feather->forum_env['FEATHER_ADMIN']) {
            $authorized = DB::for_table('posts')
                            ->where_in('topic_id', $topics_sql)
                            ->where('poster_id', Utils::get_admin_ids());
            $authorized = $this->hook->fireDB('delete_topics_authorized', $authorized);
            $authorized = $authorized->find_many();
            if ($authorized) {
                throw new Error(__('No permission'), 403);
            }
        }

        // Delete the topics
        $delete_topics = DB::for_table('topics')
                            ->where_in('id', $topics_sql);
        $delete_topics = $this->hook->fireDB('delete_topics_query', $delete_topics);
        $delete_topics = $delete_topics->delete_many();

        // Delete any redirect topics
        $delete_redirect_topics = DB::for_table('topics')
                                    ->where_in('moved_to', $topics_sql);
        $delete_redirect_topics = $this->hook->fireDB('delete_topics_redirect', $delete_redirect_topics);
        $delete_redirect_topics = $delete_redirect_topics->delete_many();

        // Delete any subscriptions
        $delete_subscriptions = DB::for_table('topic_subscriptions')
                                    ->where_in('topic_id', $topics_sql);
        $delete_subscriptions = $this->hook->fireDB('delete_topics_subscriptions', $delete_subscriptions);
        $delete_subscriptions = $delete_subscriptions->delete_many();

        // Create a list of the post IDs in this topic and then strip the search index
        $find_ids = DB::for_table('posts')
                        ->select('id')
                        ->where_in('topic_id', $topics_sql);
        $find_ids = $this->hook->fireDB('delete_topics_find_ids', $find_ids);
        $find_ids = $find_ids->find_many();

        $ids_post = array();

        foreach ($find_ids as $id) {
            $ids_post[] = $id['id'];
        }

        $post_ids = implode(', ', $ids_post);

        // We have to check that we actually have a list of post IDs since we could be deleting just a redirect topic
        if ($post_ids != '') {
            $search = new \FeatherBB\Core\Search();
            $search->strip_search_index($post_ids);
        }

        // Delete posts
        $delete_posts = DB::for_table('posts')
                            ->where_in('topic_id', $topics_sql);
        $delete_posts = $this->hook->fireDB('delete_topics_delete_posts', $delete_posts);
        $delete_posts = $delete_posts->delete_many();

        self::update($fid);
    }

    public function merge_topics($fid)
    {
        $fid = $this->hook->fire('model.merge_topics_start', $fid);

        if (@preg_match('%[^0-9,]%', $this->request->post('topics'))) {
            throw new Error(__('Bad request'), 404);
        }

        $topics = explode(',', $this->request->post('topics'));
        if (count($topics) < 2) {
            throw new Error(__('Not enough topics selected'), 400);
        }

        // Verify that the topic IDs are valid (redirect links will point to the merged topic after the merge)
        $result = DB::for_table('topics')
                    ->where_in('id', $topics)
                    ->where('forum_id', $fid);
        $result = $this->hook->fireDB('merge_topics_topic_ids', $result);
        $result = $result->find_many();

        if (count($result) != count($topics)) {
            throw new Error(__('Bad request'), 400);
        }

        // The topic that we are merging into is the one with the smallest ID
        $merge_to_tid = DB::for_table('topics')
                            ->where_in('id', $topics)
                            ->where('forum_id', $fid)
                            ->order_by_asc('id')
                            ->find_one_col('id');
        $merge_to_tid = $this->hook->fire('model.merge_topics_tid', $merge_to_tid);

        // Make any redirect topics point to our new, merged topic
        $query = 'UPDATE '.$this->feather->forum_settings['db_prefix'].'topics SET moved_to='.$merge_to_tid.' WHERE moved_to IN('.implode(',', $topics).')';

        // Should we create redirect topics?
        if ($this->request->post('with_redirect')) {
            $query .= ' OR (id IN('.implode(',', $topics).') AND id != '.$merge_to_tid.')';
        }

        // TODO ?
        DB::for_table('topics')->raw_execute($query);

        // Merge the posts into the topic
        $merge_posts = DB::for_table('posts')
                        ->where_in('topic_id', $topics);
        $merge_posts = $this->hook->fireDB('merge_topics_merge_posts', $merge_posts);
        $merge_posts = $merge_posts->update_many('topic_id', $merge_to_tid);

        // Update any subscriptions
        $find_ids = DB::for_table('topic_subscriptions')->select('user_id')
                        ->distinct()
                        ->where_in('topic_id', $topics);
        $find_ids = $this->hook->fireDB('merge_topics_find_ids', $find_ids);
        $find_ids = $find_ids->find_many();

        $subscribed_users = [];
        foreach ($find_ids as $id) {
            $subscribed_users[] = $id['user_id'];
        }

        // Delete the subscriptions
        $delete_subscriptions = DB::for_table('topic_subscriptions')
                                    ->where_in('topic_id', $topics);
        $delete_subscriptions = $this->hook->fireDB('merge_topics_delete_subscriptions', $delete_subscriptions);
        $delete_subscriptions = $delete_subscriptions->delete_many();

        // If users subscribed to one of the topics, keep subscription for merged topic
        foreach ($subscribed_users as $cur_user_id) {
            $subscriptions['insert'] = array(
                'topic_id'  =>  $merge_to_tid,
                'user_id'   =>  $cur_user_id,
            );
            // Insert the subscription
            $subscriptions = DB::for_table('topic_subscriptions')
                                ->create()
                                ->set($subscriptions['insert']);
            $subscriptions = $this->hook->fireDB('merge_topics_insert_subscriptions', $subscriptions);
            $subscriptions = $subscriptions->save();
        }

        // Without redirection the old topics are removed
        if ($this->request->post('with_redirect') == 0) {
            $delete_topics = DB::for_table('topics')
                                ->where_in('id', $topics)
                                ->where_not_equal('id', $merge_to_tid);
            $delete_topics = $this->hook->fireDB('merge_topics_delete_topics', $delete_topics);
            $delete_topics = $delete_topics->delete_many();
        }

        // Count number of replies in the topic
        $num_replies = DB::for_table('posts')->where('topic_id', $merge_to_tid)->count('id') - 1;
        $num_replies = $this->hook->fire('model.merge_topics_num_replies', $num_replies);

        // Get last_post, last_post_id and last_poster
        $last_post['select'] = array('posted', 'id', 'poster');

        $last_post = DB::for_table('posts')
                        ->select_many($last_post['select'])
                        ->where('topic_id', $merge_to_tid)
                        ->order_by_desc('id');
        $last_post = $this->hook->fireDB('merge_topics_last_post', $last_post);
        $last_post = $last_post->find_one();

        // Update topic
        $update_topic['insert'] = array(
            'num_replies' => $num_replies,
            'last_post'  => $last_post['posted'],
            'last_post_id'  => $last_post['id'],
            'last_poster'  => $last_post['poster'],
        );

        $topic = DB::for_table('topics')
                    ->where('id', $merge_to_tid)
                    ->find_one()
                    ->set($update_topic['insert']);
        $topic = $this->hook->fireDB('merge_topics_update_topic', $topic);
        $topic = $topic->save();

        $this->hook->fire('model.merge_topics');

        // Update the forum FROM which the topic was moved and redirect
        self::update($fid);
    }
}
