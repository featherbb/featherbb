<?php

/**
 * Copyright (C) 2015-2016 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace FeatherBB\Model;

use FeatherBB\Core\Database as DB;
use FeatherBB\Core\Error;
use FeatherBB\Core\Track;
use FeatherBB\Core\Url;
use FeatherBB\Core\Utils;

class Topic
{
    //
    // Delete a topic and all of its posts
    //
    public static function delete($topic_id)
    {
        // Delete the topic and any redirect topics
        $where_delete_topic = array(
            array('id' => $topic_id),
            array('moved_to' => $topic_id)
        );

        DB::for_table('topics')
            ->where_any_is($where_delete_topic)
            ->delete_many();

        // Delete posts in topic
        DB::for_table('posts')
            ->where('topic_id', $topic_id)
            ->delete_many();

        // Delete any subscriptions for this topic
        DB::for_table('topic_subscriptions')
            ->where('topic_id', $topic_id)
            ->delete_many();
    }

    // Redirect to a post in particular
    public function redirect_to_post($post_id)
    {
        $post_id = Container::get('hooks')->fire('model.topic.redirect_to_post', $post_id);

        $result['select'] = array('topic_id', 'posted');

        $result = DB::for_table('posts')
                      ->select_many($result['select'])
                      ->where('id', $post_id);
        $result = Container::get('hooks')->fireDB('model.topic.redirect_to_post_query', $result);
        $result = $result->find_one();

        if (!$result) {
            throw new Error(__('Bad request'), 404);
        }

        $post['topic_id'] = $result['topic_id'];
        $posted = $result['posted'];

        // Determine on which page the post is located (depending on $forum_user['disp_posts'])
        $num_posts = DB::for_table('posts')
                        ->where('topic_id', $post['topic_id'])
                        ->where_lt('posted', $posted)
                        ->count('id');

        $num_posts = Container::get('hooks')->fire('model.topic.redirect_to_post_num', $num_posts);

        $post['get_p'] = ceil(($num_posts + 1) / User::getPref('disp.posts'));

        $post = Container::get('hooks')->fire('model.topic.redirect_to_post', $post);

        return $post;
    }

    // Redirect to new posts or last post
    public function handle_actions($topic_id, $topic_subject, $action)
    {
        $action = Container::get('hooks')->fire('model.topic.handle_actions_start', $action, $topic_id);

        // If action=new, we redirect to the first new post (if any)
        if ($action == 'new') {
            if (!User::get()->is_guest) {
                // We need to check if this topic has been viewed recently by the user
                $tracked_topics = Track::get_tracked_topics();
                $last_viewed = isset($tracked_topics['topics'][$topic_id]) ? $tracked_topics['topics'][$topic_id] : User::get()->last_visit;

                $first_new_post_id = DB::for_table('posts')
                                        ->where('topic_id', $topic_id)
                                        ->where_gt('posted', $last_viewed)
                                        ->min('id');

                $first_new_post_id = Container::get('hooks')->fire('model.topic.handle_actions_first_new', $first_new_post_id);

                if ($first_new_post_id) {
                    return Router::redirect(Router::pathFor('viewPost', ['id' => $topic_id, 'name' => $topic_subject, 'pid' => $first_new_post_id]).'#p'.$first_new_post_id);
                }
            }

            // If there is no new post, we go to the last post
            $action = 'last';
        }

        // If action=last, we redirect to the last post
        if ($action == 'last') {
            $last_post_id = DB::for_table('posts')
                                ->where('topic_id', $topic_id)
                                ->max('id');

            $last_post_id = Container::get('hooks')->fire('model.topic.handle_actions_last_post', $last_post_id);

            if ($last_post_id) {
                return Router::redirect(Router::pathFor('viewPost', ['id' => $topic_id, 'name' => $topic_subject, 'pid' => $last_post_id]).'#p'.$last_post_id);
            }
        }

        Container::get('hooks')->fire('model.topic.handle_actions', $action, $topic_id);
    }

    // Gets some info about the topic
    public function get_info_topic($id)
    {
        $cur_topic['where'] = array(
            array('fp.read_forum' => 'IS NULL'),
            array('fp.read_forum' => '1')
        );

        if (!User::get()->is_guest) {
            $select_get_info_topic = array('t.subject', 't.closed', 't.num_replies', 't.sticky', 't.first_post_id', 'forum_id' => 'f.id', 'f.forum_name', 'f.moderators', 'fp.post_replies', 'is_subscribed' => 's.user_id');

            $cur_topic = DB::for_table('topics')
                ->table_alias('t')
                ->select_many($select_get_info_topic)
                ->inner_join('forums', array('f.id', '=', 't.forum_id'), 'f')
                ->left_outer_join('topic_subscriptions', array('t.id', '=', 's.topic_id'), 's')
                ->left_outer_join('topic_subscriptions', array('s.user_id', '=', User::get()->id), null, true)
                ->left_outer_join('forum_perms', 'fp.forum_id=f.id AND fp.group_id='.User::get()->g_id, 'fp')
                ->where_any_is($cur_topic['where'])
                ->where('t.id', $id)
                ->where_null('t.moved_to');
        } else {
            $select_get_info_topic = array('t.subject', 't.closed', 't.num_replies', 't.sticky', 't.first_post_id', 'forum_id' => 'f.id', 'f.forum_name', 'f.moderators', 'fp.post_replies');

            $cur_topic = DB::for_table('topics')
                            ->table_alias('t')
                            ->select_many($select_get_info_topic)
                            ->select_expr(0, 'is_subscribed')
                            ->inner_join('forums', array('f.id', '=', 't.forum_id'), 'f')
                            ->left_outer_join('forum_perms', 'fp.forum_id=f.id AND fp.group_id='.User::get()->g_id, 'fp')
                            ->where_any_is($cur_topic['where'])
                            ->where('t.id', $id)
                            ->where_null('t.moved_to');
        }

        $cur_topic = Container::get('hooks')->fireDB('model.topic.get_info_topic_query', $cur_topic);
        $cur_topic = $cur_topic->find_one();

        if (!$cur_topic) {
            throw new Error(__('Bad request'), 404);
        }

        $cur_topic = Container::get('hooks')->fire('model.topic.get_info_topic', $cur_topic);

        return $cur_topic;
    }

    // Generates the post link
    public function get_post_link($topic_id, $closed, $post_replies, $is_admmod)
    {
        $closed = Container::get('hooks')->fire('model.topic.get_post_link_start', $closed, $topic_id, $post_replies, $is_admmod);

        if ($closed == '0') {
            if (($post_replies == '' && User::can('topic.reply')) || $post_replies == '1' || $is_admmod) {
                $post_link = "\t\t\t".'<p class="postlink conr"><a href="'.Router::pathFor('newReply', ['tid' => $topic_id]).'">'.__('Post reply').'</a></p>'."\n";
            } else {
                $post_link = '';
            }
        } else {
            $post_link = __('Topic closed');

            if ($is_admmod) {
                $post_link .= ' / <a href="'.Router::pathFor('newReply', ['tid' => $topic_id]).'">'.__('Post reply').'</a>';
            }

            $post_link = "\t\t\t".'<p class="postlink conr">'.$post_link.'</p>'."\n";
        }

        $post_link = Container::get('hooks')->fire('model.topic.get_post_link_start', $post_link, $topic_id, $closed, $post_replies, $is_admmod);

        return $post_link;
    }

    // Should we display the quickpost?
    public function is_quickpost($post_replies, $closed, $is_admmod)
    {
        $quickpost = false;
        if (ForumSettings::get('o_quickpost') == '1' && ($post_replies == '1' || ($post_replies == '' && User::can('topic.reply'))) && ($closed == '0' || $is_admmod)) {
            $quickpost = true;
        }

        $quickpost = Container::get('hooks')->fire('model.topic.is_quickpost', $quickpost, $post_replies, $closed, $is_admmod);

        return $quickpost;
    }

    public function subscribe($topic_id)
    {
        $topic_id = Container::get('hooks')->fire('model.topic.subscribe_topic_start', $topic_id);

        if (ForumSettings::get('o_topic_subscriptions') != '1') {
            throw new Error(__('No permission'), 403);
        }

        // Make sure the user can view the topic
        $authorized['where'] = array(
            array('fp.read_forum' => 'IS NULL'),
            array('fp.read_forum' => '1')
        );

        $authorized = DB::for_table('topics')
                        ->table_alias('t')
                        ->left_outer_join('forum_perms', 'fp.forum_id=t.forum_id AND fp.group_id='.User::get()->g_id, 'fp')
                        ->where_any_is($authorized['where'])
                        ->where('t.id', $topic_id)
                        ->where_null('t.moved_to');
        $authorized = Container::get('hooks')->fireDB('model.topic.subscribe_topic_authorized_query', $authorized);
        $authorized = $authorized->find_one();

        if (!$authorized) {
            throw new Error(__('Bad request'), 404);
        }

        $is_subscribed = DB::for_table('topic_subscriptions')
                        ->where('user_id', User::get()->id)
                        ->where('topic_id', $topic_id);
        $is_subscribed = Container::get('hooks')->fireDB('model.topic.subscribe_topic_is_subscribed_query', $is_subscribed);
        $is_subscribed = $is_subscribed->find_one();

        if ($is_subscribed) {
            throw new Error(__('Already subscribed topic'), 400);
        }

        $subscription['insert'] = array(
            'user_id' => User::get()->id,
            'topic_id'  => $topic_id
        );

        // Insert the subscription
        $subscription = DB::for_table('topic_subscriptions')
                                    ->create()
                                    ->set($subscription['insert']);
        $subscription = Container::get('hooks')->fireDB('model.topic.subscribe_topic_query', $subscription);
        $subscription = $subscription->save();

        return $subscription;
    }

    public function unsubscribe($topic_id)
    {
        $topic_id = Container::get('hooks')->fire('model.topic.unsubscribe_topic_start', $topic_id);

        if (ForumSettings::get('o_topic_subscriptions') != '1') {
            throw new Error(__('No permission'), 403);
        }

        $is_subscribed = DB::for_table('topic_subscriptions')
                            ->where('user_id', User::get()->id)
                            ->where('topic_id', $topic_id);
        $is_subscribed = Container::get('hooks')->fireDB('model.topic.unsubscribe_topic_subscribed_query', $is_subscribed);
        $is_subscribed = $is_subscribed->find_one();

        if (!$is_subscribed) {
            throw new Error(__('Not subscribed topic'), 400);
        }

        // Delete the subscription
        $delete = DB::for_table('topic_subscriptions')
                    ->where('user_id', User::get()->id)
                    ->where('topic_id', $topic_id);
        $delete = Container::get('hooks')->fireDB('model.topic.unsubscribe_topic_query', $delete);
        $delete = $delete->delete_many();

        return $delete;
    }

    // Subscraction link
    public function get_subscraction($is_subscribed, $topic_id, $topic_subject)
    {
        if (!User::get()->is_guest && ForumSettings::get('o_topic_subscriptions') == '1') {
            if ($is_subscribed) {
                // I apologize for the variable naming here. It's a mix of subscription and action I guess :-)
                $subscraction = "\t\t".'<p class="subscribelink clearb"><span>'.__('Is subscribed').' - </span><a href="'.Router::pathFor('unsubscribeTopic', ['id' => $topic_id, 'name' => $topic_subject]).'">'.__('Unsubscribe').'</a></p>'."\n";
            } else {
                $subscraction = "\t\t".'<p class="subscribelink clearb"><a href="'.Router::pathFor('subscribeTopic', ['id' => $topic_id, 'name' => $topic_subject]).'">'.__('Subscribe').'</a></p>'."\n";
            }
        } else {
            $subscraction = '';
        }

        $subscraction = Container::get('hooks')->fire('model.topic.get_subscraction', $subscraction, $is_subscribed, $topic_id, $topic_subject);

        return $subscraction;
    }

    public function setSticky($id, $value)
    {
        $sticky = DB::for_table('topics')
                            ->find_one($id)
                            ->set('sticky', $value);
        $sticky = Container::get('hooks')->fireDB('model.topic.stick_topic', $sticky);
        $sticky->save();

        return $sticky;
    }

    public function setClosed($id, $value)
    {
        $closed = DB::for_table('topics')
                            ->find_one($id)
                            ->set('closed', $value);
        $closed = Container::get('hooks')->fireDB('model.topic.stick_topic', $closed);
        $closed->save();

        return $closed;
    }

    public function check_move_possible()
    {
        Container::get('hooks')->fire('model.topic.check_move_possible_start');

        $result['select'] = array('cid' => 'c.id', 'c.cat_name', 'fid' => 'f.id', 'f.forum_name');
        $result['where'] = array(
            array('fp.post_topics' => 'IS NULL'),
            array('fp.post_topics' => '1')
        );
        $result['order_by'] = array('c.disp_position', 'c.id', 'f.disp_position');

        $result = DB::for_table('categories')
                    ->table_alias('c')
                    ->select_many($result['select'])
                    ->inner_join('forums', array('c.id', '=', 'f.cat_id'), 'f')
                    ->left_outer_join('forum_perms', 'fp.forum_id=f.id AND fp.group_id='.User::get()->g_id, 'fp')
                    ->where_any_is($result['where'])
                    ->where_null('f.redirect_url')
                    ->order_by_many($result['order_by']);
        $result = Container::get('hooks')->fireDB('model.topic.check_move_possible', $result);
        $result = $result->find_many();

        if (count($result) < 2) {
            return false;
        }
        return true;
    }

    public function get_forum_list_move($fid)
    {
        $output = '';

        $select_get_forum_list_move = array('cid' => 'c.id', 'c.cat_name', 'fid' => 'f.id', 'f.forum_name');
        $where_get_forum_list_move = array(
            array('fp.post_topics' => 'IS NULL'),
            array('fp.post_topics' => '1')
        );
        $order_by_get_forum_list_move = array('c.disp_position', 'c.id', 'f.disp_position');

        $result = DB::for_table('categories')
                    ->table_alias('c')
                    ->select_many($select_get_forum_list_move)
                    ->inner_join('forums', array('c.id', '=', 'f.cat_id'), 'f')
                    ->left_outer_join('forum_perms', 'fp.forum_id=f.id AND fp.group_id='.User::get()->g_id, 'fp')
                    ->where_any_is($where_get_forum_list_move)
                    ->where_null('f.redirect_url')
                    ->order_by_many($order_by_get_forum_list_move);
        $result = Container::get('hooks')->fireDB('model.topic.get_forum_list_move_query', $result);
        $result = $result->find_result_set();

        $cur_category = 0;

        foreach($result as $cur_forum) {
            if ($cur_forum->fid != $fid) {
                if ($cur_forum->cid != $cur_category) {
                    // A new category since last iteration?

                    if ($cur_category) {
                        $output .= "\t\t\t\t\t\t\t".'</optgroup>'."\n";
                    }

                    $output .= "\t\t\t\t\t\t\t".'<optgroup label="'.Utils::escape($cur_forum->cat_name).'">'."\n";
                    $cur_category = $cur_forum->cid;
                }

                $output .= "\t\t\t\t\t\t\t\t".'<option value="'.$cur_forum->fid.'">'.Utils::escape($cur_forum->forum_name).'</option>'."\n";
            }
        }

        $output = Container::get('hooks')->fire('model.topic.get_forum_list_move', $output);

        return $output;
    }

    public function get_forum_list_split($id)
    {
        $output = '';

        $result['select'] = array('cid' => 'c.id', 'c.cat_name', 'fid' => 'f.id', 'f.forum_name');
        $result['where'] = array(
            array('fp.post_topics' => 'IS NULL'),
            array('fp.post_topics' => '1')
        );
        $order_by_get_forum_list_split = array('c.disp_position', 'c.id', 'f.disp_position');

        $result = DB::for_table('categories')
                    ->table_alias('c')
                    ->select_many($result['select'])
                    ->inner_join('forums', array('c.id', '=', 'f.cat_id'), 'f')
                    ->left_outer_join('forum_perms', 'fp.forum_id=f.id AND fp.group_id='.User::get()->g_id, 'fp')
                    ->where_any_is($result['where'])
                    ->where_null('f.redirect_url')
                    ->order_by_many($order_by_get_forum_list_split);
        $result = Container::get('hooks')->fireDB('model.topic.get_forum_list_split_query', $result);
        $result = $result->find_result_set();

        $cur_category = 0;

        foreach($result as $cur_forum) {
            if ($cur_forum->cid != $cur_category) {
                // A new category since last iteration?

                if ($cur_category) {
                    $output .= "\t\t\t\t\t\t\t".'</optgroup>'."\n";
                }

                $output .= "\t\t\t\t\t\t\t".'<optgroup label="'.Utils::escape($cur_forum->cat_name).'">'."\n";
                $cur_category = $cur_forum->cid;
            }

            $output .= "\t\t\t\t\t\t\t\t".'<option value="'.$cur_forum->fid.'"'.($id == $cur_forum->fid ? ' selected="selected"' : '').'>'.Utils::escape($cur_forum->forum_name).'</option>'."\n";
        }

        $output = Container::get('hooks')->fire('model.topic.get_forum_list_split', $output);

        return $output;
    }

    public function move_to($fid, $new_fid, $tid = null)
    {
        Container::get('hooks')->fire('model.topic.move_to_start', $fid, $new_fid, $tid);

        $topics = is_string($tid) ? [$tid] : $tid;
        $new_fid = intval($new_fid);
        $fid = intval($fid);

        if (empty($topics) || $new_fid < 1) {
            throw new Error(__('Bad request'), 400);
        }

        // Verify that the topic IDs are valid
        $result = DB::for_table('topics')
                    ->where_in('id', $topics)
                    ->where('forum_id', $fid);
        $result = Container::get('hooks')->fireDB('model.topic.move_to_topic_valid', $result);
        $result = $result->find_many();

        if (count($result) != count($topics)) {
            throw new Error(__('Bad request'), 400);
        }

        // Verify that the move to forum ID is valid
        $authorized['where'] = array(
            array('fp.post_topics' => 'IS NULL'),
            array('fp.post_topics' => '1')
        );

        $authorized = DB::for_table('forums')
                        ->table_alias('f')
                        ->left_outer_join('forum_perms', 'fp.forum_id='.$new_fid.' AND fp.group_id='.User::get()->g_id, 'fp')
                        ->where_any_is($authorized['where'])
                        ->where_null('f.redirect_url');
        $authorized = Container::get('hooks')->fireDB('model.topic.move_to_authorized', $authorized);
        $authorized = $authorized->find_one();

        if (!$authorized) {
            throw new Error(__('Bad request'), 404);
        }

        // Delete any redirect topics if there are any (only if we moved/copied the topic back to where it was once moved from)
        $delete_redirect = DB::for_table('topics')
                                ->where('forum_id', $new_fid)
                                ->where_in('moved_to', $topics);
        $delete_redirect = Container::get('hooks')->fireDB('model.topic.move_to_delete_redirect', $delete_redirect);
        $delete_redirect->delete_many();

        // Move the topic(s)
        $move_topics = DB::for_table('topics')->where_in('id', $topics)
                        ->find_result_set()
                        ->set('forum_id', $new_fid);
        $move_topics = Container::get('hooks')->fireDB('model.topic.move_to_query', $move_topics);
        $move_topics->save();

        // Should we create redirect topics?
        if (Input::post('with_redirect')) {
            foreach ($topics as $cur_topic) {
                // Fetch info for the redirect topic
                $moved_to['select'] = array('poster', 'subject', 'posted', 'last_post');

                $moved_to = DB::for_table('topics')->select_many($moved_to['select'])
                                ->where('id', $cur_topic);
                $moved_to = Container::get('hooks')->fireDB('model.topic.move_to_fetch_redirect', $moved_to);
                $moved_to = $moved_to->find_one();

                // Create the redirect topic
                $insert_move_to = array(
                    'poster' => $moved_to['poster'],
                    'subject'  => $moved_to['subject'],
                    'posted'  => $moved_to['posted'],
                    'last_post'  => $moved_to['last_post'],
                    'moved_to'  => $cur_topic,
                    'forum_id'  => $fid,
                );

                // Insert the report
                $move_to = DB::for_table('topics')
                                    ->create()
                                    ->set($insert_move_to);
                $move_to = Container::get('hooks')->fireDB('model.topic.move_to_redirect', $move_to);
                $move_to = $move_to->save();

            }
        }

        Forum::update($fid); // Update the forum FROM which the topic was moved
        Forum::update($new_fid); // Update the forum TO which the topic was moved
    }

    public function delete_posts($tid, $fid)
    {
        $posts = Input::post('posts', array());
        $posts = Container::get('hooks')->fire('model.topic.delete_posts_start', $posts, $tid, $fid);

        if (empty($posts)) {
            throw new Error(__('No posts selected'), 404);
        }

        if (Input::post('delete_posts_comply')) {
            if (@preg_match('%[^0-9,]%', $posts)) {
                throw new Error(__('Bad request'), 400);
            }

            // Verify that the post IDs are valid
            $posts_array = explode(',', $posts);

            $result = DB::for_table('posts')
                ->where_in('id', $posts_array)
                ->where('topic_id', $tid);

            if (User::get()->g_id != ForumEnv::get('FEATHER_ADMIN')) {
                $result->where_not_in('poster_id', Utils::get_admin_ids());
            }

            $result = Container::get('hooks')->fireDB('model.topic.delete_posts_first_query', $result);
            $result = $result->find_many();

            if (count($result) != substr_count($posts, ',') + 1) {
                throw new Error(__('Bad request'), 400);
            }

            // Delete the posts
            $delete_posts = DB::for_table('posts')
                                ->where_in('id', $posts_array);
            $delete_posts = Container::get('hooks')->fireDB('model.topic.delete_posts_query', $delete_posts);
            $delete_posts = $delete_posts->delete_many();

            $search = new \FeatherBB\Core\Search();
            $search->strip_search_index($posts);

            // Get last_post, last_post_id, and last_poster for the topic after deletion
            $last_post['select'] = array('id', 'poster', 'posted');

            $last_post = DB::for_table('posts')
                ->select_many($last_post['select'])
                ->where('topic_id', $tid);
            $last_post = Container::get('hooks')->fireDB('model.topic.delete_posts_last_post_query', $last_post);
            $last_post = $last_post->find_one();

            // How many posts did we just delete?
            $num_posts_deleted = substr_count($posts, ',') + 1;

            // Update the topic
            $update_topic['insert'] = array(
                'last_post' => User::get()->id,
                'last_post_id'  => $last_post['id'],
                'last_poster'  => $last_post['poster'],
            );

            $update_topic = DB::for_table('topics')->where('id', $tid)
                ->find_one()
                ->set($update_topic['insert'])
                ->set_expr('num_replies', 'num_replies-'.$num_posts_deleted);
            $update_topic = Container::get('hooks')->fireDB('model.topic.delete_posts_update_topic_query', $update_topic);
            $topic_subject = Url::url_friendly($update_topic->subject);
            $update_topic = $update_topic->save();

            Forum::update($fid);
            return Router::redirect(Router::pathFor('Topic', array('id' => $tid, 'name' => $topic_subject)), __('Delete posts redirect'));
        }
        else {
            $posts = Container::get('hooks')->fire('model.topic.delete_posts', $posts);
            return $posts;
        }
    }

    public function get_topic_info($fid, $tid)
    {
        // Fetch some info about the topic
        $cur_topic['select'] = array('forum_id' => 'f.id', 'f.forum_name', 't.subject', 't.num_replies', 't.first_post_id');
        $cur_topic['where'] = array(
            array('fp.read_forum' => 'IS NULL'),
            array('fp.read_forum' => '1')
        );

        $cur_topic = DB::for_table('topics')
            ->table_alias('t')
            ->select_many($cur_topic['select'])
            ->inner_join('forums', array('f.id', '=', 't.forum_id'), 'f')
            ->left_outer_join('forum_perms', 'fp.forum_id=f.id AND fp.group_id='.User::get()->g_id, 'fp')
            ->where_any_is($cur_topic['where'])
            ->where('f.id', $fid)
            ->where('t.id', $tid)
            ->where_null('t.moved_to');
        $cur_topic = Container::get('hooks')->fireDB('model.topic.get_topic_info', $cur_topic);
        $cur_topic = $cur_topic->find_one();

        if (!$cur_topic) {
            throw new Error(__('Bad request'), 404);
        }

        return $cur_topic;
    }

    public function split_posts($tid, $fid, $p = null)
    {
        $posts = Input::post('posts') ? Input::post('posts') : array();
        $posts = Container::get('hooks')->fire('model.topic.split_posts_start', $posts, $tid, $fid);
        if (empty($posts)) {
            throw new Error(__('No posts selected'), 404);
        }

        if (Input::post('split_posts_comply')) {
            if (@preg_match('%[^0-9,]%', $posts)) {
                throw new Error(__('Bad request'), 400);
            }

            $move_to_forum = Input::post('move_to_forum') ? intval(Input::post('move_to_forum')) : 0;
            if ($move_to_forum < 1) {
                throw new Error(__('Bad request'), 400);
            }

            // How many posts did we just split off?
            $num_posts_splitted = substr_count($posts, ',') + 1;

            // Verify that the post IDs are valid
            $posts_array = explode(',', $posts);

            $result = DB::for_table('posts')
                ->where_in('id', $posts_array)
                ->where('topic_id', $tid);
            $result = Container::get('hooks')->fireDB('model.topic.split_posts_first_query', $result);
            $result = $result->find_many();

            if (count($result) != $num_posts_splitted) {
                throw new Error(__('Bad request'), 400);
            }

            unset($result);

            // Verify that the move to forum ID is valid
            $result['where'] = array(
                array('fp.post_topics' => 'IS NULL'),
                array('fp.post_topics' => '1')
            );

            $result = DB::for_table('forums')
                        ->table_alias('f')
                        ->left_outer_join('forum_perms', 'fp.forum_id='.$move_to_forum.' AND fp.group_id='.User::get()->g_id, 'fp')
                        ->where_any_is($result['where'])
                        ->where_null('f.redirect_url');
            $result = Container::get('hooks')->fireDB('model.topic.split_posts_second_query', $result);
            $result = $result->find_one();

            if (!$result) {
                throw new Error(__('Bad request'), 404);
            }

            // Check subject
            $new_subject = Input::post('new_subject');

            if ($new_subject == '') {
                throw new Error(__('No subject'), 400);
            } elseif (Utils::strlen($new_subject) > 70) {
                throw new Error(__('Too long subject'), 400);
            }

            // Get data from the new first post
            $select_first_post = array('id', 'poster', 'posted');

            $first_post_data = DB::for_table('posts')
                ->select_many($select_first_post)
                ->where_in('id',$posts_array )
                ->order_by_asc('id')
                ->find_one();

            // Create the new topic
            $topic['insert'] = array(
                'poster' => $first_post_data['poster'],
                'subject'  => $new_subject,
                'posted'  => $first_post_data['posted'],
                'first_post_id'  => $first_post_data['id'],
                'forum_id'  => $move_to_forum,
            );

            $topic = DB::for_table('topics')
                ->create()
                ->set($topic['insert']);
            $topic = Container::get('hooks')->fireDB('model.topic.split_posts_topic_query', $topic);
            $topic->save();

            $new_tid = DB::get_db()->lastInsertId(ForumSettings::get('db_prefix').'topics');

            // Move the posts to the new topic
            $move_posts = DB::for_table('posts')->where_in('id', $posts_array)
                ->find_result_set()
                ->set('topic_id', $new_tid);
            $move_posts = Container::get('hooks')->fireDB('model.topic.split_posts_move_query', $move_posts);
            $move_posts->save();

            // Apply every subscription to both topics
            DB::for_table('topic_subscriptions')->raw_query('INSERT INTO '.ForumSettings::get('db_prefix').'topic_subscriptions (user_id, topic_id) SELECT user_id, '.$new_tid.' FROM '.ForumSettings::get('db_prefix').'topic_subscriptions WHERE topic_id=:tid', array('tid' => $tid));

            // Get last_post, last_post_id, and last_poster from the topic and update it
            $last_old_post_data['select'] = array('id', 'poster', 'posted');

            $last_old_post_data = DB::for_table('posts')
                ->select_many($last_old_post_data['select'])
                ->where('topic_id', $tid)
                ->order_by_desc('id');
            $last_old_post_data = Container::get('hooks')->fireDB('model.topic.split_posts_last_old_post_data_query', $last_old_post_data);
            $last_old_post_data = $last_old_post_data->find_one();

            // Update the old topic
            $update_old_topic['insert'] = array(
                'last_post' => $last_old_post_data['posted'],
                'last_post_id'  => $last_old_post_data['id'],
                'last_poster'  => $last_old_post_data['poster'],
            );

            $update_old_topic = DB::for_table('topics')
                                ->where('id', $tid)
                                ->find_one()
                                ->set($update_old_topic['insert'])
                                ->set_expr('num_replies', 'num_replies-'.$num_posts_splitted);
            $update_old_topic = Container::get('hooks')->fireDB('model.topic.split_posts_update_old_topic_query', $update_old_topic);
            $update_old_topic->save();

            // Get last_post, last_post_id, and last_poster from the new topic and update it
            $last_new_post_data['select'] = array('id', 'poster', 'posted');

            $last_new_post_data = DB::for_table('posts')
                                    ->select_many($last_new_post_data['select'])
                                    ->where('topic_id', $new_tid)
                                    ->order_by_desc('id');
            $last_new_post_data = Container::get('hooks')->fireDB('model.topic.split_posts_last_new_post_query', $last_new_post_data);
            $last_new_post_data = $last_new_post_data->find_one();

            // Update the new topic
            $update_new_topic['insert'] = array(
                'last_post' => $last_new_post_data['posted'],
                'last_post_id'  => $last_new_post_data['id'],
                'last_poster'  => $last_new_post_data['poster'],
            );

            $update_new_topic = DB::for_table('topics')
                ->where('id', $new_tid)
                ->find_one()
                ->set($update_new_topic['insert'])
                ->set_expr('num_replies', $num_posts_splitted-1);
            $update_new_topic = Container::get('hooks')->fireDB('model.topic.split_posts_update_new_topic_query', $update_new_topic);
            $update_new_topic = $update_new_topic->save();

            Forum::update($fid);
            Forum::update($move_to_forum);

            return Router::redirect(Router::pathFor('Topic', array('id' => $new_tid, 'name' => Url::url_friendly($new_subject))), __('Split posts redirect'));
        }

        $posts = Container::get('hooks')->fire('model.topic.split_posts', $posts);
        return $posts;
    }

    // Prints the posts
    public function print_posts($topic_id, $start_from, $cur_topic, $is_admmod)
    {
        $post_data = array();

        $post_data = Container::get('hooks')->fire('model.topic.print_posts_start', $post_data, $topic_id, $start_from, $cur_topic, $is_admmod);

        $post_count = 0; // Keep track of post numbers

        // Retrieve a list of post IDs, LIMIT is (really) expensive so we only fetch the IDs here then later fetch the remaining data
        $result = DB::for_table('posts')
                    ->select('id')
                    ->where('topic_id', $topic_id)
                    ->order_by('id')
                    ->limit(User::getPref('disp.topics'))
                    ->offset($start_from);
        $result = Container::get('hooks')->fireDB('model.topic.print_posts_ids_query', $result);
        $result = $result->find_many();

        $post_ids = array();
        foreach ($result as $cur_post_id) {
            $post_ids[] = $cur_post_id['id'];
        }

        if (empty($post_ids)) {
            throw new Error('The post table and topic table seem to be out of sync!', 500);
        }

        // Retrieve the posts (and their respective poster/online status)
        $result['select'] = array('u.email', 'u.title', 'u.url', 'u.location', 'u.signature', 'email_setting' => 'pr.preference_value', 'u.num_posts', 'u.registered', 'u.admin_note', 'p.id','username' => 'p.poster', 'p.poster_id', 'p.poster_ip', 'p.poster_email', 'p.message', 'p.hide_smilies', 'p.posted', 'p.edited', 'p.edited_by', 'g.g_id', 'g.g_user_title', 'is_online' => 'o.user_id');

        $result = DB::for_table('posts')
                    ->table_alias('p')
                    ->select_many($result['select'])
                    ->inner_join('users', array('u.id', '=', 'p.poster_id'), 'u')
                    ->inner_join('groups', array('g.g_id', '=', 'u.group_id'), 'g')
                    ->raw_join('LEFT OUTER JOIN '.ForumSettings::get('db_prefix').'`online`', "`o`.`user_id`!=1 AND `o`.`idle`=0 AND `o`.`user_id`=`u`.`id`", 'o')
                    ->raw_join('LEFT OUTER JOIN '.ForumSettings::get('db_prefix').'`preferences`', "`pr`.`user`=`u`.`id` AND `pr`.`preference_name`='email.setting'", 'pr')
                    ->where_in('p.id', $post_ids)
                    ->order_by('p.id');
        $result = Container::get('hooks')->fireDB('model.topic.print_posts_query', $result);
        $result = $result->find_array();

        foreach($result as $cur_post) {
            $post_count++;
            $cur_post['user_avatar'] = '';
            $cur_post['user_info'] = array();
            $cur_post['user_contacts'] = array();
            $cur_post['post_actions'] = array();
            $cur_post['is_online_formatted'] = '';
            $cur_post['signature_formatted'] = '';
            $cur_post['promote.next_group'] = Container::get('prefs')->getGroupPreferences($cur_post['g_id'], 'promote.next_group');

            // If the poster is a registered user
            if ($cur_post['poster_id'] > 1) {
                if (User::can('users.view')) {
                    $cur_post['username_formatted'] = '<a href="'.Router::pathFor('userProfile', ['id' => $cur_post['poster_id']]).'/">'.Utils::escape($cur_post['username']).'</a>';
                } else {
                    $cur_post['username_formatted'] = Utils::escape($cur_post['username']);
                }

                $cur_post['user_title_formatted'] = Utils::get_title($cur_post);

                if (ForumSettings::get('o_censoring') == '1') {
                    $cur_post['user_title_formatted'] = Utils::censor($cur_post['user_title_formatted']);
                }

                // Format the online indicator
                $cur_post['is_online_formatted'] = ($cur_post['is_online'] == $cur_post['poster_id']) ? '<strong>'.__('Online').'</strong>' : '<span>'.__('Offline').'</span>';

                if (ForumSettings::get('o_avatars') == '1' && User::getPref('show.avatars') != '0') {
                    if (isset($avatar_cache[$cur_post['poster_id']])) {
                        $cur_post['user_avatar'] = $avatar_cache[$cur_post['poster_id']];
                    } else {
                        $cur_post['user_avatar'] = $avatar_cache[$cur_post['poster_id']] = Utils::generate_avatar_markup($cur_post['poster_id']);
                    }
                }

                // We only show location, register date, post count and the contact links if "Show user info" is enabled
                if (ForumSettings::get('o_show_user_info') == '1') {
                    if ($cur_post['location'] != '') {
                        if (ForumSettings::get('o_censoring') == '1') {
                            $cur_post['location'] = Utils::censor($cur_post['location']);
                        }

                        $cur_post['user_info'][] = '<dd><span>'.__('From').' '.Utils::escape($cur_post['location']).'</span></dd>';
                    }

                    $cur_post['user_info'][] = '<dd><span>'.__('Registered topic').' '.Utils::format_time($cur_post['registered'], true).'</span></dd>';

                    if (ForumSettings::get('o_show_post_count') == '1' || User::isAdminMod()) {
                        $cur_post['user_info'][] = '<dd><span>'.__('Posts topic').' '.Utils::forum_number_format($cur_post['num_posts']).'</span></dd>';
                    }

                    // Now let's deal with the contact links (Email and URL)
                    if (!isset($cur_post['email_setting']) || is_null($cur_post['email_setting'])) {
                        $cur_post['email_setting'] = ForumSettings::get('email.setting');
                    }
                    if ((($cur_post['email_setting'] == '0' && !User::get()->is_guest) || User::isAdminMod()) && User::can('email.send')) {
                        $cur_post['user_contacts'][] = '<span class="email"><a href="mailto:'.Utils::escape($cur_post['email']).'">'.__('Email').'</a></span>';
                    } elseif ($cur_post['email_setting'] == '1' && !User::get()->is_guest && User::can('email.send')) {
                        $cur_post['user_contacts'][] = '<span class="email"><a href="'.Router::pathFor('email', ['id' => $cur_post['poster_id']]).'">'.__('Email').'</a></span>';
                    }

                    if ($cur_post['url'] != '') {
                        if (ForumSettings::get('o_censoring') == '1') {
                            $cur_post['url'] = Utils::censor($cur_post['url']);
                        }

                        $cur_post['user_contacts'][] = '<span class="website"><a href="'.Utils::escape($cur_post['url']).'" rel="nofollow">'.__('Website').'</a></span>';
                    }
                }

                if (User::isAdmin() || (User::isAdminMod() && User::can('mod.promote_users'))) {
                    if ($cur_post['promote.next_group']) {
                        $cur_post['user_info'][] = '<dd><span><a href="'.Router::pathFor('profileAction', ['id' => $cur_post['poster_id'], 'action' => 'promote', 'pid' => $cur_post['id']]).'">'.__('Promote user').'</a></span></dd>';
                    }
                }

                if (User::isAdminMod()) {
                    $cur_post['user_info'][] = '<dd><span><a href="'.Router::pathFor('getPostHost', ['pid' => $cur_post['id']]).'" title="'.Utils::escape($cur_post['poster_ip']).'">'.__('IP address logged').'</a></span></dd>';

                    if ($cur_post['admin_note'] != '') {
                        $cur_post['user_info'][] = '<dd><span>'.__('Note').' <strong>'.Utils::escape($cur_post['admin_note']).'</strong></span></dd>';
                    }
                }
            }
            // If the poster is a guest (or a user that has been deleted)
            else {
                $cur_post['username_formatted'] = Utils::escape($cur_post['username']);
                $cur_post['user_title_formatted'] = Utils::get_title($cur_post);

                if (User::isAdminMod()) {
                    $cur_post['user_info'][] = '<dd><span><a href="'.Router::pathFor('getPostHost', ['pid' => $cur_post['id']]).'" title="'.Utils::escape($cur_post['poster_ip']).'">'.__('IP address logged').'</a></span></dd>';
                }

                if (ForumSettings::get('o_show_user_info') == '1' && $cur_post['poster_email'] != '' && !User::get()->is_guest && User::can('email.send')) {
                    $cur_post['user_contacts'][] = '<span class="email"><a href="mailto:'.Utils::escape($cur_post['poster_email']).'">'.__('Email').'</a></span>';
                }
            }

            // Generation post action array (quote, edit, delete etc.)
            if (!$is_admmod) {
                if (!User::get()->is_guest) {
                    $cur_post['post_actions'][] = '<li class="postreport"><span><a href="'.Router::pathFor('report', ['id' => $cur_post['id']]).'">'.__('Report').'</a></span></li>';
                }

                if ($cur_topic['closed'] == '0') {
                    if ($cur_post['poster_id'] == User::get()->id) {
                        if ((($start_from + $post_count) == 1 && User::can('topic.delete')) || (($start_from + $post_count) > 1 && User::can('post.delete'))) {
                            $cur_post['post_actions'][] = '<li class="postdelete"><span><a href="'.Router::pathFor('deletePost', ['id' => $cur_post['id']]).'">'.__('Delete').'</a></span></li>';
                        }
                        if (User::can('post.edit')) {
                            $cur_post['post_actions'][] = '<li class="postedit"><span><a href="'.Router::pathFor('editPost', ['id' => $cur_post['id']]).'">'.__('Edit').'</a></span></li>';
                        }
                    }

                    if (($cur_topic['post_replies'] == '' && User::can('topic.reply')) || $cur_topic['post_replies'] == '1') {
                        $cur_post['post_actions'][] = '<li class="postquote"><span><a href="'.Router::pathFor('newQuoteReply', ['tid' => $topic_id, 'qid' => $cur_post['id']]).'">'.__('Quote').'</a></span></li>';
                    }
                }
            } else {
                $cur_post['post_actions'][] = '<li class="postreport"><span><a href="'.Router::pathFor('report', ['id' => $cur_post['id']]).'">'.__('Report').'</a></span></li>';
                if (User::isAdmin() || !in_array($cur_post['poster_id'], Utils::get_admin_ids())) {
                    $cur_post['post_actions'][] = '<li class="postdelete"><span><a href="'.Router::pathFor('deletePost', ['id' => $cur_post['id']]).'">'.__('Delete').'</a></span></li>';
                    $cur_post['post_actions'][] = '<li class="postedit"><span><a href="'.Router::pathFor('editPost', ['id' => $cur_post['id']]).'">'.__('Edit').'</a></span></li>';
                }
                $cur_post['post_actions'][] = '<li class="postquote"><span><a href="'.Router::pathFor('newQuoteReply', ['tid' => $topic_id, 'qid' => $cur_post['id']]).'">'.__('Quote').'</a></span></li>';
            }

            // Perform the main parsing of the message (BBCode, smilies, censor words etc)
            $cur_post['message'] = Container::get('parser')->parse_message($cur_post['message'], $cur_post['hide_smilies']);

            // Do signature parsing/caching
            if (ForumSettings::get('o_signatures') == '1' && $cur_post['signature'] != '' && User::getPref('show.sig') != '0') {
                // if (isset($avatar_cache[$cur_post['poster_id']])) {
                //     $cur_post['signature_formatted'] = $avatar_cache[$cur_post['poster_id']];
                // } else {
                    $cur_post['signature_formatted'] = Container::get('parser')->parse_signature($cur_post['signature']);
                //     $avatar_cache[$cur_post['poster_id']] = $cur_post['signature_formatted'];
                // }
            }
            $cur_post = Container::get('hooks')->fire('model.print_posts.one', $cur_post);
            $post_data[] = $cur_post;
        }

        $post_data = Container::get('hooks')->fire('model.topic.print_posts', $post_data);

        return $post_data;
    }

    public function display_posts_moderate($tid, $start_from)
    {
        Container::get('hooks')->fire('model.disp.topics_posts_view_start', $tid, $start_from);

        $post_data = array();

        $post_count = 0; // Keep track of post numbers

        // Retrieve a list of post IDs, LIMIT is (really) expensive so we only fetch the IDs here then later fetch the remaining data
        $find_ids = DB::for_table('posts')->select('id')
            ->where('topic_id', $tid)
            ->order_by('id')
            ->limit(User::getPref('disp.posts'))
            ->offset($start_from);
        $find_ids = Container::get('hooks')->fireDB('model.disp.topics_posts_view_find_ids', $find_ids);
        $find_ids = $find_ids->find_many();

        foreach ($find_ids as $id) {
            $post_ids[] = $id['id'];
        }

        // Retrieve the posts (and their respective poster)
        $result['select'] = array('u.title', 'u.num_posts', 'g.g_id', 'g.g_user_title', 'p.id', 'p.poster', 'p.poster_id', 'p.message', 'p.hide_smilies', 'p.posted', 'p.edited', 'p.edited_by');

        $result = DB::for_table('posts')
                    ->table_alias('p')
                    ->select_many($result['select'])
                    ->inner_join('users', array('u.id', '=', 'p.poster_id'), 'u')
                    ->inner_join('groups', array('g.g_id', '=', 'u.group_id'), 'g')
                    ->where_in('p.id', $post_ids)
                    ->order_by('p.id');
        $result = Container::get('hooks')->fireDB('model.disp.topics_posts_view_query', $result);
        $result = $result->find_many();

        foreach($result as $cur_post) {
            $post_count++;

            // If the poster is a registered user
            if ($cur_post->poster_id > 1) {
                if (User::can('users.view')) {
                    $cur_post->poster_disp = '<a href="'.Router::pathFor('userProfile', ['id' => $cur_post->poster_id]).'">'.Utils::escape($cur_post->poster).'</a>';
                } else {
                    $cur_post->poster_disp = Utils::escape($cur_post->poster);
                }

                // Utils::get_title() requires that an element 'username' be present in the array
                $cur_post->username = $cur_post->poster;
                $cur_post->user_title = Utils::get_title($cur_post);

                if (ForumSettings::get('o_censoring') == '1') {
                    $cur_post->user_title = Utils::censor($cur_post->user_title);
                }
            }
            // If the poster is a guest (or a user that has been deleted)
            else {
                $cur_post->poster_disp = Utils::escape($cur_post->poster);
                $cur_post->user_title = __('Guest');
            }

            // Perform the main parsing of the message (BBCode, smilies, censor words etc)
            $cur_post->message = Container::get('parser')->parse_message($cur_post->message, $cur_post->hide_smilies);

            $post_data[] = $cur_post;
        }

        $post_data = Container::get('hooks')->fire('model.disp.topics_posts_view', $post_data);

        return $post_data;
    }

    public function increment_views($id)
    {
        if (ForumSettings::get('o_topic_views') == '1') {
            $query = DB::for_table('topics')
                        ->where('id', $id)
                        ->find_one()
                        ->set_expr('num_views', 'num_views+1');
            $query = Container::get('hooks')->fire('model.topic.increment_views', $query);
            $query = $query->save();
        }
    }
}
