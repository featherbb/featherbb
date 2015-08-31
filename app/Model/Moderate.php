<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace App\Model;

use DB;

class moderate
{
    public function __construct()
    {
        $this->feather = \Slim\Slim::getInstance();
        $this->start = $this->feather->start;
        $this->config = $this->feather->config;
        $this->user = $this->feather->user;
        $this->request = $this->feather->request;
        $this->hook = $this->feather->hooks;
        $this->search = new \FeatherBB\Search();
    }

    public function display_ip_info($ip)
    {
        $ip = $this->hook->fire('display_ip_info', $ip);
        message(sprintf(__('Host info 1'), $ip).'<br />'.sprintf(__('Host info 2'), @gethostbyaddr($ip)).'<br /><br /><a href="'.$this->feather->url->get('admin/users/show-users/ip/'.$ip.'/').'">'.__('Show more users').'</a>');
    }

    public function display_ip_address_post($pid)
    {
        $pid = $this->hook->fire('display_ip_address_post_start', $pid);

        $ip = DB::for_table('posts')
            ->where('id', $pid);
        $ip = $this->hook->fireDB('display_ip_address_post_query', $ip);
        $ip = $ip->find_one_col('poster_ip');

        if (!$ip) {
            throw new \FeatherBB\Error(__('Bad request'), 404);
        }

        $ip = $this->hook->fire('display_ip_address_post', $ip);

        message(sprintf(__('Host info 1'), $ip).'<br />'.sprintf(__('Host info 2'), @gethostbyaddr($ip)).'<br /><br /><a href="'.$this->feather->url->get('admin/users/show-users/ip/'.$ip.'/').'">'.__('Show more users').'</a>');
    }

    public function get_moderators($fid)
    {
        $moderators = DB::for_table('forums')
                        ->where('id', $fid);
        $moderators = $this->hook->fireDB('get_moderators', $moderators);
        $moderators = $moderators->find_one_col('moderators');

        return $moderators;
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
            ->left_outer_join('forum_perms', array('fp.forum_id', '=', 'f.id'), 'fp')
            ->left_outer_join('forum_perms', array('fp.group_id', '=', $this->user->g_id), null, true)
            ->where_any_is($cur_topic['where'])
            ->where('f.id', $fid)
            ->where('t.id', $tid)
            ->where_null('t.moved_to');
        $cur_topic = $this->hook->fireDB('get_topic_info', $cur_topic);
        $cur_topic = $cur_topic->find_one();

        if (!$cur_topic) {
            throw new \FeatherBB\Error(__('Bad request'), 404);
        }

        return $cur_topic;
    }

    public function delete_posts($tid, $fid, $p = null)
    {
        $posts = $this->request->post('posts') ? $this->request->post('posts') : array();
        $posts = $this->hook->fire('delete_posts_start', $posts, $tid, $fid);

        if (empty($posts)) {
            throw new \FeatherBB\Error(__('No posts selected'), 404);
        }

        if ($this->request->post('delete_posts_comply')) {
            if (@preg_match('%[^0-9,]%', $posts)) {
                throw new \FeatherBB\Error(__('Bad request'), 400);
            }

            // Verify that the post IDs are valid
            $posts_array = explode(',', $posts);

            $result = DB::for_table('posts')
                ->where_in('id', $posts_array)
                ->where('topic_id', $tid);

            if ($this->user->g_id != FEATHER_ADMIN) {
                $result->where_not_in('poster_id', get_admin_ids());
            }

            $result = $this->hook->fireDB('delete_posts_first_query', $result);
            $result = $result->find_many();

            if (count($result) != substr_count($posts, ',') + 1) {
                throw new \FeatherBB\Error(__('Bad request'), 400);
            }

            // Delete the posts
            $delete_posts = DB::for_table('posts')
                                ->where_in('id', $posts_array);
            $delete_posts = $this->hook->fireDB('delete_posts_query', $delete_posts);
            $delete_posts = $delete_posts->delete_many();

            $this->search->strip_search_index($posts);

            // Get last_post, last_post_id, and last_poster for the topic after deletion
            $last_post['select'] = array('id', 'poster', 'posted');

            $last_post = DB::for_table('posts')
                ->select_many($last_post['select'])
                ->where('topic_id', $tid);
            $last_post = $this->hook->fireDB('delete_posts_last_post_query', $last_post);
            $last_post = $last_post->find_one();

            // How many posts did we just delete?
            $num_posts_deleted = substr_count($posts, ',') + 1;

            // Update the topic
            $update_topic['insert'] = array(
                'last_post' => $this->user->id,
                'last_post_id'  => $last_post['id'],
                'last_poster'  => $last_post['poster'],
            );

            $update_topic = DB::for_table('topics')->where('id', $tid)
                ->find_one()
                ->set($update_topic['insert'])
                ->set_expr('num_replies', 'num_replies-'.$num_posts_deleted);
            $update_topic = $this->hook->fireDB('delete_posts_update_topic_query', $update_topic);
            $update_topic = $update_topic->save();

            update_forum($fid);

            redirect($this->feather->url->get('topic/'.$tid.'/'), __('Delete posts redirect'));
        }

        $posts = $this->hook->fire('delete_posts', $posts);

        return $posts;
    }

    public function split_posts($tid, $fid, $p = null)
    {
        $posts = $this->request->post('posts') ? $this->request->post('posts') : array();
        $posts = $this->hook->fire('split_posts_start', $posts, $tid, $fid);
        if (empty($posts)) {
            throw new \FeatherBB\Error(__('No posts selected'), 404);
        }

        if ($this->request->post('split_posts_comply')) {
            if (@preg_match('%[^0-9,]%', $posts)) {
                throw new \FeatherBB\Error(__('Bad request'), 400);
            }

            $move_to_forum = $this->request->post('move_to_forum') ? intval($this->request->post('move_to_forum')) : 0;
            if ($move_to_forum < 1) {
                throw new \FeatherBB\Error(__('Bad request'), 400);
            }

            // How many posts did we just split off?
            $num_posts_splitted = substr_count($posts, ',') + 1;

            // Verify that the post IDs are valid
            $posts_array = explode(',', $posts);

            $result = DB::for_table('posts')
                ->where_in('id', $posts_array)
                ->where('topic_id', $tid);
            $result = $this->hook->fireDB('split_posts_first_query', $result);
            $result = $result->find_many();

            if (count($result) != $num_posts_splitted) {
                throw new \FeatherBB\Error(__('Bad request'), 400);
            }

            unset($result);

            // Verify that the move to forum ID is valid
            $result['where'] = array(
                array('fp.post_topics' => 'IS NULL'),
                array('fp.post_topics' => '1')
            );

            $result = DB::for_table('forums')
                        ->table_alias('f')
                        ->left_outer_join('forum_perms', array('fp.forum_id', '=', $move_to_forum), 'fp', true)
                        ->left_outer_join('forum_perms', array('fp.group_id', '=', $this->user->g_id), null, true)
                        ->where_any_is($result['where'])
                        ->where_null('f.redirect_url');
            $result = $this->hook->fireDB('split_posts_second_query', $result);
            $result = $result->find_one();

            if (!$result) {
                throw new \FeatherBB\Error(__('Bad request'), 404);
            }

            // Check subject
            $new_subject = $this->request->post('new_subject') ? $this->feather->utils->trim($this->request->post('new_subject')) : '';

            if ($new_subject == '') {
                throw new \FeatherBB\Error(__('No subject'), 400);
            } elseif (feather_strlen($new_subject) > 70) {
                throw new \FeatherBB\Error(__('Too long subject'), 400);
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
            $topic = $this->hook->fireDB('split_posts_topic_query', $topic);
            $topic = $topic->save();

            $new_tid = DB::get_db()->lastInsertId($this->feather->forum_settings['db_prefix'].'topics');

            // Move the posts to the new topic
            $move_posts = DB::for_table('posts')->where_in('id', $posts_array)
                ->find_one()
                ->set('topic_id', $new_tid);
            $move_posts = $this->hook->fireDB('split_posts_move_query', $move_posts);
            $move_posts = $move_posts->save();

            // Apply every subscription to both topics
            DB::for_table('topic_subscriptions')->raw_query('INSERT INTO '.$this->feather->forum_settings['db_prefix'].'topic_subscriptions (user_id, topic_id) SELECT user_id, '.$new_tid.' FROM '.$this->feather->forum_settings['db_prefix'].'topic_subscriptions WHERE topic_id=:tid', array('tid' => $tid));

            // Get last_post, last_post_id, and last_poster from the topic and update it
            $last_old_post_data['select'] = array('id', 'poster', 'posted');

            $last_old_post_data = DB::for_table('posts')
                ->select_many($last_old_post_data['select'])
                ->where('topic_id', $tid)
                ->order_by_desc('id');
            $last_old_post_data = $this->hook->fireDB('split_posts_last_old_post_data_query', $last_old_post_data);
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
            $update_old_topic = $this->hook->fireDB('split_posts_update_old_topic_query', $update_old_topic);
            $update_old_topic = $update_old_topic->save();

            // Get last_post, last_post_id, and last_poster from the new topic and update it
            $last_new_post_data['select'] = array('id', 'poster', 'posted');

            $last_new_post_data = DB::for_table('posts')
                                    ->select_many($last_new_post_data['select'])
                                    ->where('topic_id', $new_tid)
                                    ->order_by_desc('id');
            $last_new_post_data = $this->hook->fireDB('split_posts_last_new_post_query', $last_new_post_data);
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
                ->set_expr('num_replies', 'num_replies-'.$num_posts_splitted-1);
            $update_new_topic = $this->hook->fireDB('split_posts_update_new_topic_query', $update_new_topic);
            $update_new_topic = $update_new_topic->save();

            update_forum($fid);
            update_forum($move_to_forum);

            redirect($this->feather->url->get('topic/'.$new_tid.'/'), __('Split posts redirect'));
        }

        $posts = $this->hook->fire('split_posts', $posts);

        return $posts;
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
                    ->left_outer_join('forum_perms', array('fp.forum_id', '=', 'f.id'), 'fp')
                    ->left_outer_join('forum_perms', array('fp.group_id', '=', $this->user->g_id), null, true)
                    ->where_any_is($result['where'])
                    ->where_null('f.redirect_url')
                    ->order_by_many($order_by_get_forum_list_split);
        $result = $this->hook->fireDB('get_forum_list_split_query', $result);
        $result = $result->find_result_set();

        $cur_category = 0;

        foreach($result as $cur_forum) {
            if ($cur_forum->cid != $cur_category) {
                // A new category since last iteration?

                if ($cur_category) {
                    $output .= "\t\t\t\t\t\t\t".'</optgroup>'."\n";
                }

                $output .= "\t\t\t\t\t\t\t".'<optgroup label="'.$this->feather->utils->escape($cur_forum->cat_name).'">'."\n";
                $cur_category = $cur_forum->cid;
            }

            $output .= "\t\t\t\t\t\t\t\t".'<option value="'.$cur_forum->fid.'"'.($id == $cur_forum->fid ? ' selected="selected"' : '').'>'.$this->feather->utils->escape($cur_forum->forum_name).'</option>'."\n";
        }

        $output = $this->hook->fire('get_forum_list_split', $output);

        return $output;
    }

    public function get_forum_list_move($id)
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
                    ->left_outer_join('forum_perms', array('fp.forum_id', '=', 'f.id'), 'fp')
                    ->left_outer_join('forum_perms', array('fp.group_id', '=', $this->user->g_id), null, true)
                    ->where_any_is($where_get_forum_list_move)
                    ->where_null('f.redirect_url')
                    ->order_by_many($order_by_get_forum_list_move);
        $result = $this->hook->fireDB('get_forum_list_move_query', $result);
        $result = $result->find_result_set();

        $cur_category = 0;

        foreach($result as $cur_forum) {
            if ($cur_forum->cid != $cur_category) {
                // A new category since last iteration?

                if ($cur_category) {
                    $output .= "\t\t\t\t\t\t\t".'</optgroup>'."\n";
                }

                $output .= "\t\t\t\t\t\t\t".'<optgroup label="'.$this->feather->utils->escape($cur_forum->cat_name).'">'."\n";
                $cur_category = $cur_forum->cid;
            }

            if ($cur_forum->fid != $id) {
                $output .= "\t\t\t\t\t\t\t\t".'<option value="'.$cur_forum->fid.'">'.$this->feather->utils->escape($cur_forum->forum_name).'</option>'."\n";
            }
        }

        $output = $this->hook->fire('get_forum_list_move', $output);

        return $output;
    }

    public function display_posts_view($tid, $start_from)
    {
        global $pd;

        $this->hook->fire('display_posts_view_start', $tid, $start_from);

        $post_data = array();

        require FEATHER_ROOT.'include/parser.php';

        $post_count = 0; // Keep track of post numbers

        // Retrieve a list of post IDs, LIMIT is (really) expensive so we only fetch the IDs here then later fetch the remaining data
        $find_ids = DB::for_table('posts')->select('id')
            ->where('topic_id', $tid)
            ->order_by('id')
            ->limit($this->user->disp_posts)
            ->offset($start_from);
        $find_ids = $this->hook->fireDB('display_posts_view_find_ids', $find_ids);
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
        $result = $this->hook->fireDB('display_posts_view_query', $result);
        $result = $result->find_many();

        foreach($result as $cur_post) {
            $post_count++;

            // If the poster is a registered user
            if ($cur_post->poster_id > 1) {
                if ($this->user->g_view_users == '1') {
                    $cur_post->poster_disp = '<a href="'.$this->feather->url->get('user/'.$cur_post->poster_id.'/').'">'.$this->feather->utils->escape($cur_post->poster).'</a>';
                } else {
                    $cur_post->poster_disp = $this->feather->utils->escape($cur_post->poster);
                }

                // get_title() requires that an element 'username' be present in the array
                $cur_post->username = $cur_post->poster;
                $cur_post->user_title = get_title($cur_post);

                if ($this->config['o_censoring'] == '1') {
                    $cur_post->user_title = censor_words($cur_post->user_title);
                }
            }
            // If the poster is a guest (or a user that has been deleted)
            else {
                $cur_post->poster_disp = $this->feather->utils->escape($cur_post->poster);
                $cur_post->user_title = __('Guest');
            }

            // Perform the main parsing of the message (BBCode, smilies, censor words etc)
            $cur_post->message = parse_message($cur_post->message, $cur_post->hide_smilies);

            $post_data[] = $cur_post;
        }

        $post_data = $this->hook->fire('display_posts_view', $post_data);

        return $post_data;
    }

    public function move_topics_to($fid, $tfid = null, $param = null)
    {
        $fid = $this->hook->fire('move_topics_to_start', $fid);

        if (@preg_match('%[^0-9,]%', $this->request->post('topics'))) {
            throw new \FeatherBB\Error(__('Bad request'), 400);
        }

        $topics = explode(',', $this->request->post('topics'));
        $move_to_forum = $this->request->post('move_to_forum') ? intval($this->request->post('move_to_forum')) : 0;
        if (empty($topics) || $move_to_forum < 1) {
            throw new \FeatherBB\Error(__('Bad request'), 400);
        }

        // Verify that the topic IDs are valid
        $result = DB::for_table('topics')
                    ->where_in('id', $topics)
                    ->where('forum_id', $fid);
        $result = $this->hook->fireDB('move_topics_to_topic_valid', $result);
        $result = $result->find_many();

        if (count($result) != count($topics)) {
            throw new \FeatherBB\Error(__('Bad request'), 400);
        }


        // Verify that the move to forum ID is valid
        $authorized['where'] = array(
            array('fp.post_topics' => 'IS NULL'),
            array('fp.post_topics' => '1')
        );

        $authorized = DB::for_table('forums')
                        ->table_alias('f')
                        ->left_outer_join('forum_perms', array('fp.forum_id', '=', $move_to_forum), 'fp', true)
                        ->left_outer_join('forum_perms', array('fp.group_id', '=', $this->user->g_id), null, true)
                        ->where_any_is($authorized['where'])
                        ->where_null('f.redirect_url');
        $authorized = $this->hook->fireDB('move_topics_to_authorized', $authorized);
        $authorized = $authorized->find_one();

        if (!$authorized) {
            throw new \FeatherBB\Error(__('Bad request'), 404);
        }

        // Delete any redirect topics if there are any (only if we moved/copied the topic back to where it was once moved from)
        $delete_redirect = DB::for_table('topics')
                                ->where('forum_id', $move_to_forum)
                                ->where_in('moved_to', $topics);
        $delete_redirect = $this->hook->fireDB('move_topics_to_delete_redirect', $delete_redirect);
        $delete_redirect = $delete_redirect->delete_many();

        // Move the topic(s)
        $move_topics = DB::for_table('topics')->where_in('id', $topics)
                        ->find_one()
                        ->set('forum_id', $move_to_forum);
        $move_topics = $this->hook->fireDB('move_topics_to_query', $move_topics);
        $move_topics = $move_topics->save();

        // Should we create redirect topics?
        if ($this->request->post('with_redirect')) {
            foreach ($topics as $cur_topic) {
                // Fetch info for the redirect topic
                $moved_to['select'] = array('poster', 'subject', 'posted', 'last_post');

                $moved_to = DB::for_table('topics')->select_many($moved_to['select'])
                                ->where('id', $cur_topic);
                $moved_to = $this->hook->fireDB('move_topics_to_fetch_redirect', $moved_to);
                $moved_to = $moved_to->find_one();

                // Create the redirect topic
                $insert_move_topics_to = array(
                    'poster' => $moved_to['poster'],
                    'subject'  => $moved_to['subject'],
                    'posted'  => $moved_to['posted'],
                    'last_post'  => $moved_to['last_post'],
                    'moved_to'  => $cur_topic,
                    'forum_id'  => $fid,
                );

                // Insert the report
                $move_topics_to = DB::for_table('topics')
                                    ->create()
                                    ->set($insert_move_topics_to);
                $move_topics_to = $this->hook->fireDB('move_topics_to_redirect', $move_topics_to);
                $move_topics_to = $move_topics_to->save();

            }
        }

        update_forum($fid); // Update the forum FROM which the topic was moved
        update_forum($move_to_forum); // Update the forum TO which the topic was moved

        $redirect_msg = (count($topics) > 1) ? __('Move topics redirect') : __('Move topic redirect');
        $redirect_msg = $this->hook->fire('move_topics_to_redirect_message', $redirect_msg);
        redirect($this->feather->url->get('forum/'.$move_to_forum.'/'), $redirect_msg);
    }

    public function check_move_possible()
    {
        $this->hook->fire('check_move_possible_start');

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
                    ->left_outer_join('forum_perms', array('fp.forum_id', '=', 'f.id'), 'fp')
                    ->left_outer_join('forum_perms', array('fp.group_id', '=', $this->user->g_id), null, true)
                    ->where_any_is($result['where'])
                    ->where_null('f.redirect_url')
                    ->order_by_many($result['order_by']);
        $result = $this->hook->fireDB('check_move_possible', $result);
        $result = $result->find_many();

        if (count($result) < 2) {
            throw new \FeatherBB\Error(__('Nowhere to move'), 403);
        }
    }

    public function merge_topics($fid)
    {
        $fid = $this->hook->fire('merge_topics_start', $fid);

        if (@preg_match('%[^0-9,]%', $this->request->post('topics'))) {
            throw new \FeatherBB\Error(__('Bad request'), 404);
        }

        $topics = explode(',', $this->request->post('topics'));
        if (count($topics) < 2) {
            throw new \FeatherBB\Error(__('Not enough topics selected'), 400);
        }

        // Verify that the topic IDs are valid (redirect links will point to the merged topic after the merge)
        $result = DB::for_table('topics')
                    ->where_in('id', $topics)
                    ->where('forum_id', $fid);
        $result = $this->hook->fireDB('merge_topics_topic_ids', $result);
        $result = $result->find_many();

        if (count($result) != count($topics)) {
            throw new \FeatherBB\Error(__('Bad request'), 400);
        }

        // The topic that we are merging into is the one with the smallest ID
        $merge_to_tid = DB::for_table('topics')
                            ->where_in('id', $topics)
                            ->where('forum_id', $fid)
                            ->order_by_asc('id')
                            ->find_one_col('id');
        $merge_to_tid = $this->hook->fire('merge_topics_tid', $merge_to_tid);

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

        foreach ($find_ids as $id) {
            $subscribed_users[] = $id['user_id'];
        }

        // Delete the subscriptions
        $delete_subscriptions = DB::for_table('topic_subscriptions')
                                    ->where_in('topic_id', $topics);
        $delete_subscriptions = $this->hook->fireDB('merge_topics_delete_subscriptions', $delete_subscriptions);
        $delete_subscriptions = $delete_subscriptions->delete_many();

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
        $num_replies = $this->hook->fire('merge_topics_num_replies', $num_replies);

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

        $this->hook->fire('merge_topics');

        // Update the forum FROM which the topic was moved and redirect
        update_forum($fid);
        redirect($this->feather->url->get('forum/'.$fid.'/'), __('Merge topics redirect'));
    }

    public function delete_topics($topics, $fid)
    {
        $this->hook->fire('delete_topics');

        if (@preg_match('%[^0-9,]%', $topics)) {
            throw new \FeatherBB\Error(__('Bad request'), 400);
        }

        $topics_sql = explode(',', $topics);

        // Verify that the topic IDs are valid
        $result = DB::for_table('topics')
                    ->where_in('id', $topics_sql)
                    ->where('forum_id', $fid);
        $result = $this->hook->fireDB('delete_topics_verify_id', $result);
        $result = $result->find_many();

        if (count($result) != substr_count($topics, ',') + 1) {
            throw new \FeatherBB\Error(__('Bad request'), 400);
        }

        // Verify that the posts are not by admins
        if ($this->user->g_id != FEATHER_ADMIN) {
            $authorized = DB::for_table('posts')
                            ->where_in('topic_id', $topics_sql)
                            ->where('poster_id', get_admin_ids());
            $authorized = $this->hook->fireDB('delete_topics_authorized', $authorized);
            $authorized = $authorized->find_many();
            if ($authorized) {
                throw new \FeatherBB\Error(__('No permission'), 403);
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
            $this->search->strip_search_index($post_ids);
        }

        // Delete posts
        $delete_posts = DB::for_table('posts')
                            ->where_in('topic_id', $topics_sql);
        $delete_posts = $this->hook->fireDB('delete_topics_delete_posts', $delete_posts);
        $delete_posts = $delete_posts->delete_many();

        update_forum($fid);

        $this->hook->fire('delete_topics');

        redirect($this->feather->url->get('forum/'.$fid.'/'), __('Delete topics redirect'));
    }

    public function get_forum_info($fid)
    {
        $cur_forum['select'] = array('f.forum_name', 'f.redirect_url', 'f.num_topics', 'f.sort_by');
        $cur_forum['where'] = array(
            array('fp.read_forum' => 'IS NULL'),
            array('fp.read_forum' => '1')
        );

        $cur_forum = DB::for_table('forums')
                        ->table_alias('f')
                        ->select_many($cur_forum['select'])
                        ->left_outer_join('forum_perms', array('fp.forum_id', '=', 'f.id'), 'fp')
                        ->left_outer_join('forum_perms', array('fp.group_id', '=', $this->user->g_id), null, true)
                        ->where_any_is($cur_forum['where'])
                        ->where('f.id', $fid);
        $cur_forum = $this->hook->fireDB('get_forum_info', $cur_forum);
        $cur_forum = $cur_forum->find_one();

        if (!$cur_forum) {
            throw new \FeatherBB\Error(__('Bad request'), 404);
        }

        return $cur_forum;

    }

    public function forum_sort_by($forum_sort)
    {
        $forum_sort = $this->hook->fire('forum_sort_by_start', $forum_sort);

        switch ($forum_sort) {
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

        $sort_by = $this->hook->fire('forum_sort_by', $sort_by);

        return $sort_by;
    }

    public function display_topics($fid, $sort_by, $start_from)
    {
        $this->hook->fire('display_topics_start', $fid, $sort_by, $start_from);

        $topic_data = array();

        // Get topic/forum tracking data
        if (!$this->user->is_guest) {
            $tracked_topics = get_tracked_topics();
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
                $url_topic = $this->feather->url->url_friendly($cur_topic['subject']);

                if (is_null($cur_topic['moved_to'])) {
                    $cur_topic['last_post_disp'] = '<a href="'.$this->feather->url->get('post/'.$cur_topic['last_post_id'].'/#p'.$cur_topic['last_post_id']).'">'.$this->feather->utils->format_time($cur_topic['last_post']).'</a> <span class="byuser">'.__('by').' '.$this->feather->utils->escape($cur_topic['last_poster']).'</span>';
                    $cur_topic['ghost_topic'] = false;
                } else {
                    $cur_topic['last_post_disp'] = '- - -';
                    $cur_topic['ghost_topic'] = true;
                }

                if ($this->config['o_censoring'] == '1') {
                    $cur_topic['subject'] = censor_words($cur_topic['subject']);
                }

                if ($cur_topic['sticky'] == '1') {
                    $cur_topic['item_status'] .= ' isticky';
                    $status_text[] = '<span class="stickytext">'.__('Sticky').'</span>';
                }

                if ($cur_topic['moved_to'] != 0) {
                    $cur_topic['subject_disp'] = '<a href="'.$this->feather->url->get('topic/'.$cur_topic['moved_to'].'/'.$url_topic.'/').'">'.$this->feather->utils->escape($cur_topic['subject']).'</a> <span class="byuser">'.__('by').' '.$this->feather->utils->escape($cur_topic['poster']).'</span>';
                    $status_text[] = '<span class="movedtext">'.__('Moved').'</span>';
                    $cur_topic['item_status'] .= ' imoved';
                } elseif ($cur_topic['closed'] == '0') {
                    $cur_topic['subject_disp'] = '<a href="'.$this->feather->url->get('topic/'.$cur_topic['id'].'/'.$url_topic.'/').'">'.$this->feather->utils->escape($cur_topic['subject']).'</a> <span class="byuser">'.__('by').' '.$this->feather->utils->escape($cur_topic['poster']).'</span>';
                } else {
                    $cur_topic['subject_disp'] = '<a href="'.$this->feather->url->get('topic/'.$cur_topic['id'].'/'.$url_topic.'/').'">'.$this->feather->utils->escape($cur_topic['subject']).'</a> <span class="byuser">'.__('by').' '.$this->feather->utils->escape($cur_topic['poster']).'</span>';
                    $status_text[] = '<span class="closedtext">'.__('Closed').'</span>';
                    $cur_topic['item_status'] .= ' iclosed';
                }

                if (!$cur_topic['ghost_topic'] && $cur_topic['last_post'] > $this->user->last_visit && (!isset($tracked_topics['topics'][$cur_topic['id']]) || $tracked_topics['topics'][$cur_topic['id']] < $cur_topic['last_post']) && (!isset($tracked_topics['forums'][$fid]) || $tracked_topics['forums'][$fid] < $cur_topic['last_post'])) {
                    $cur_topic['item_status'] .= ' inew';
                    $cur_topic['icon_type'] = 'icon icon-new';
                    $cur_topic['subject_disp'] = '<strong>'.$cur_topic['subject_disp'].'</strong>';
                    $subject_new_posts = '<span class="newtext">[ <a href="'.$this->feather->url->get('topic/'.$cur_topic['id'].'/action/new/').'" title="'.__('New posts info').'">'.__('New posts').'</a> ]</span>';
                } else {
                    $subject_new_posts = null;
                }

                // Insert the status text before the subject
                $cur_topic['subject_disp'] = implode(' ', $status_text).' '.$cur_topic['subject_disp'];

                $num_pages_topic = ceil(($cur_topic['num_replies'] + 1) / $this->user->disp_posts);

                if ($num_pages_topic > 1) {
                    $subject_multipage = '<span class="pagestext">[ '.$this->feather->url->paginate($num_pages_topic, -1, 'topic/'.$cur_topic['id'].'/'.$url_topic.'/#').' ]</span>';
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

        $topic_data = $this->hook->fire('display_topics', $topic_data);

        return $topic_data;
    }

    public function stick_topic($id, $fid)
    {
        $stick_topic = DB::for_table('topics')
                            ->where('id', $id)
                            ->where('forum_id', $fid)
                            ->find_one()
                            ->set('sticky', 1);
        $stick_topic = $this->hook->fireDB('stick_topic', $stick_topic);
        $stick_topic = $stick_topic->save();
    }

    public function unstick_topic($id, $fid)
    {
        $unstick_topic = DB::for_table('topics')
            ->where('id', $id)
            ->where('forum_id', $fid)
            ->find_one()
            ->set('sticky', 0);
        $unstick_topic = $this->hook->fireDB('unstick_topic', $unstick_topic);
        $unstick_topic = $unstick_topic->save();
    }

    public function open_topic($id, $fid)
    {
        $open_topic = DB::for_table('topics')
                        ->where('id', $id)
                        ->where('forum_id', $fid)
                        ->find_one()
                        ->set('closed', 0);
        $open_topic = $this->hook->fireDB('open_topic', $open_topic);
        $open_topic = $open_topic->save();
    }

    public function close_topic($id, $fid)
    {
        $close_topic = DB::for_table('topics')
                        ->where('id', $id)
                        ->where('forum_id', $fid)
                        ->find_one()
                        ->set('closed', 1);
        $close_topic = $this->hook->fireDB('close_topic', $close_topic);
        $close_topic = $close_topic->save();
    }

    public function close_multiple_topics($action, $topics, $fid)
    {
        $close_multiple_topics = DB::for_table('topics')
                                    ->where_in('id', $topics);
        $close_multiple_topics = $this->hook->fireDB('open_topic', $close_multiple_topics);
        $close_multiple_topics = $close_multiple_topics->update_many('closed', $action);
    }

    public function get_subject_tid($id)
    {
        $subject = DB::for_table('topics')
                    ->where('id', $id);
        $subject = $this->hook->fireDB('get_subject_tid', $subject);
        $subject = $subject->find_one_col('subject');

        if (!$subject) {
            throw new \FeatherBB\Error(__('Bad request'), 404);
        }

        return $subject;
    }
}
