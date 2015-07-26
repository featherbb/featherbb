<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace model;

class moderate
{
    public function __construct()
    {
        $this->feather = \Slim\Slim::getInstance();
        $this->db = $this->feather->db;
        $this->start = $this->feather->start;
        $this->config = $this->feather->config;
        $this->user = $this->feather->user;
        $this->request = $this->feather->request;
    }
 
    public function display_ip_info($ip)
    {
        global $lang_misc;

        // Load the misc.php language file
        require FEATHER_ROOT.'lang/'.$this->user->language.'/misc.php';

        message(sprintf($lang_misc['Host info 1'], $ip).'<br />'.sprintf($lang_misc['Host info 2'], @gethostbyaddr($ip)).'<br /><br /><a href="'.get_link('admin/users/show-users/ip/'.$ip.'/').'">'.$lang_misc['Show more users'].'</a>');
    }

    public function display_ip_address_post($pid)
    {
        global $lang_common;

        $ip = \ORM::for_table($this->db->prefix.'posts')
            ->where('id', $pid)
            ->find_one_col('poster_ip');

        if (!$ip) {
            message($lang_common['Bad request'], false, '404 Not Found');
        }

        // Load the misc.php language file
        require FEATHER_ROOT.'lang/'.$this->user->language.'/misc.php';

        message(sprintf($lang_misc['Host info 1'], $ip).'<br />'.sprintf($lang_misc['Host info 2'], @gethostbyaddr($ip)).'<br /><br /><a href="'.get_link('admin/users/show-users/ip/'.$ip.'/').'">'.$lang_misc['Show more users'].'</a>');
    }

    public function get_moderators($fid)
    {
        $moderators = \ORM::for_table($this->db->prefix.'forums')
            ->where('id', $fid)
            ->find_one_col('moderators');

        return $moderators;
    }

    public function get_topic_info($fid, $tid)
    {
        global $lang_common;
        
        // Fetch some info about the topic
        $select_get_topic_info = array('forum_id' => 'f.id', 'f.forum_name', 't.subject', 't.num_replies', 't.first_post_id');
        $where_get_topic_info = array(
            array('fp.read_forum' => 'IS NULL'),
            array('fp.read_forum' => '1')
        );

        $cur_topic = \ORM::for_table($this->feather->prefix.'topics')
            ->table_alias('t')
            ->select_many($select_get_topic_info)
            ->inner_join($this->feather->prefix.'forums', array('f.id', '=', 't.forum_id'), 'f')
            ->left_outer_join($this->feather->prefix.'forum_perms', array('fp.forum_id', '=', 'f.id'), 'fp')
            ->left_outer_join($this->feather->prefix.'forum_perms', array('fp.group_id', '=', $this->user->g_id), '', true)
            ->where_any_is($where_get_topic_info)
            ->where('f.id', $fid)
            ->where('t.id', $tid)
            ->where_null('t.moved_to')
            ->find_one();

        if (!$cur_topic) {
            message($lang_common['Bad request'], false, '404 Not Found');
        }

        return $cur_topic;
    }

    public function delete_posts($tid, $fid, $p = null)
    {
        global $lang_common, $lang_misc;

        $posts = $this->request->post('posts') ? $this->request->post('posts') : array();
        if (empty($posts)) {
            message($lang_misc['No posts selected']);
        }

        if ($this->request->post('delete_posts_comply')) {
            confirm_referrer(array(
                get_link_r('moderate/forum/'.$fid.'/'),
                get_link_r('moderate/topic/'.$tid.'/forum/'.$fid.'/action/moderate/page/'.$p.'/'),
            ));

            if (@preg_match('%[^0-9,]%', $posts)) {
                message($lang_common['Bad request'], false, '404 Not Found');
            }

            // Verify that the post IDs are valid
            $posts_array = explode(',', $posts);

            $result = \ORM::for_table($this->db->prefix.'posts')
                ->where_in('id', $posts_array)
                ->where('topic_id', $tid)
                ->find_many();

            if ($this->user->g_id != FEATHER_ADMIN) {
                $result->where_not_in('poster_id', get_admin_ids());
            }

            if (count($result) != substr_count($posts, ',') + 1) {
                message($lang_common['Bad request'], false, '404 Not Found');
            }

            // Delete the posts
            \ORM::for_table($this->db->prefix.'posts')
                ->where_in('id', $posts_array)
                ->delete_many();

            require FEATHER_ROOT.'include/search_idx.php';
            strip_search_index($posts);

            // Get last_post, last_post_id, and last_poster for the topic after deletion
            $select_last_post = array('id', 'poster', 'posted');

            $last_post = \ORM::for_table($this->feather->prefix.'posts')
                ->select_many($select_last_post)
                ->where('topic_id', $tid)
                ->find_one();

            // How many posts did we just delete?
            $num_posts_deleted = substr_count($posts, ',') + 1;

            // Update the topic
            $update_topic = array(
                'last_post' => $this->user->id,
                'last_post_id'  => $last_post['id'],
                'last_poster'  => $last_post['poster'],
            );

            \ORM::for_table($this->db->prefix.'topics')->where('id', $tid)
                ->find_one()
                ->set($update_topic)
                ->set_expr('num_replies', 'num_replies-'.$num_posts_deleted)
                ->save();

            update_forum($fid);

            redirect(get_link('topic/'.$tid.'/'), $lang_misc['Delete posts redirect']);
        }

        return $posts;
    }

    public function split_posts($tid, $fid, $p = null)
    {
        global $lang_common, $lang_misc, $lang_post;

        $posts = $this->request->post('posts') ? $this->request->post('posts') : array();
        if (empty($posts)) {
            message($lang_misc['No posts selected']);
        }

        if ($this->request->post('split_posts_comply')) {
            confirm_referrer(array(
                get_link_r('moderate/forum/'.$fid.'/'),
                get_link_r('moderate/topic/'.$tid.'/forum/'.$fid.'/action/moderate/page/'.$p.'/'),
            ));

            if (@preg_match('%[^0-9,]%', $posts)) {
                message($lang_common['Bad request'], false, '404 Not Found');
            }

            $move_to_forum = $this->request->post('move_to_forum') ? intval($this->request->post('move_to_forum')) : 0;
            if ($move_to_forum < 1) {
                message($lang_common['Bad request'], false, '404 Not Found');
            }

            // How many posts did we just split off?
            $num_posts_splitted = substr_count($posts, ',') + 1;

            // Verify that the post IDs are valid
            $posts_array = explode(',', $posts);

            $result = \ORM::for_table($this->db->prefix.'posts')
                ->where_in('id', $posts_array)
                ->where('topic_id', $tid)
                ->find_many();

            if (count($result) != $num_posts_splitted) {
                message($lang_common['Bad request'], false, '404 Not Found');
            }

            // Verify that the move to forum ID is valid
            $where_split_posts = array(
                array('fp.post_topics' => 'IS NULL'),
                array('fp.post_topics' => '1')
            );

            $result = \ORM::for_table($this->feather->prefix.'forums')
                ->table_alias('f')
                ->left_outer_join($this->feather->prefix.'forum_perms', array('fp.forum_id', '=', $move_to_forum), 'fp', true)
                ->left_outer_join($this->feather->prefix.'forum_perms', array('fp.group_id', '=', $this->user->g_id), '', true)
                ->where_any_is($where_split_posts)
                ->where_null('f.redirect_url')
                ->find_one();

            if (!$result) {
                message($lang_common['Bad request'], false, '404 Not Found');
            }

            // Load the post.php language file
            require FEATHER_ROOT.'lang/'.$this->user->language.'/post.php';

            // Check subject
            $new_subject = $this->request->post('new_subject') ? feather_trim($this->request->post('new_subject')) : '';

            if ($new_subject == '') {
                message($lang_post['No subject']);
            } elseif (feather_strlen($new_subject) > 70) {
                message($lang_post['Too long subject']);
            }

            // Get data from the new first post
            $select_first_post = array('id', 'poster', 'posted');

            $first_post_data = \ORM::for_table($this->feather->prefix.'posts')
                ->select($select_first_post)
                ->where_in('id',$posts_array )
                ->order_by_asc('id')
                ->find_one();

            // Create the new topic
            $insert_topic = array(
                'poster' => $first_post_data['poster'],
                'subject'  => $new_subject,
                'posted'  => $first_post_data['posted'],
                'first_post_id'  => $first_post_data['id'],
                'forum_id'  => $move_to_forum,
            );

            \ORM::for_table($this->db->prefix.'topics')
                ->create()
                ->set($insert_topic)
                ->save();

            $new_tid = \ORM::get_db()->lastInsertId($this->db->prefix.'topics');

            // Move the posts to the new topic
            \ORM::for_table($this->db->prefix.'posts')->where_in('id', $posts_array)
                ->find_one()
                ->set('topic_id', $new_tid)
                ->save();

            // Apply every subscription to both topics
            \ORM::for_table($this->db->prefix.'topic_subscriptions')->raw_query('INSERT INTO '.$this->db->prefix.'topic_subscriptions (user_id, topic_id) SELECT user_id, '.$new_tid.' FROM '.$this->db->prefix.'topic_subscriptions WHERE topic_id=:tid', array('tid' => $tid));

            // Get last_post, last_post_id, and last_poster from the topic and update it
            $select_last_post = array('id', 'poster', 'posted');

            $last_old_post_data = \ORM::for_table($this->feather->prefix.'posts')
                ->select($select_last_post)
                ->where('topic_id', $tid)
                ->order_by_desc('id')
                ->find_one();

            // Update the old topic
            $update_old_topic = array(
                'last_post' => $last_old_post_data['posted'],
                'last_post_id'  => $last_old_post_data['id'],
                'last_poster'  => $last_old_post_data['poster'],
            );

            \ORM::for_table($this->db->prefix.'topics')
                ->where('id', $tid)
                ->find_one()
                ->set($update_old_topic)
                ->set_expr('num_replies', 'num_replies-'.$num_posts_splitted)
                ->save();

            // Get last_post, last_post_id, and last_poster from the new topic and update it
            $select_new_post = array('id', 'poster', 'posted');

            $last_new_post_data = \ORM::for_table($this->feather->prefix.'posts')
                ->select($select_new_post)
                ->where('topic_id', $new_tid)
                ->order_by_desc('id')
                ->find_one();

            // Update the new topic
            $update_new_topic = array(
                'last_post' => $last_new_post_data['posted'],
                'last_post_id'  => $last_new_post_data['id'],
                'last_poster'  => $last_new_post_data['poster'],
            );

            \ORM::for_table($this->db->prefix.'topics')
                ->where('id', $new_tid)
                ->find_one()
                ->set($update_new_topic)
                ->set_expr('num_replies', 'num_replies-'.$num_posts_splitted-1)
                ->save();

            update_forum($fid);
            update_forum($move_to_forum);

            redirect(get_link('topic/'.$new_tid.'/'), $lang_misc['Split posts redirect']);
        }

        return $posts;
    }

    public function get_forum_list_split($id)
    {
        $output = '';

        $select_get_forum_list_split = array('cid' => 'c.id', 'c.cat_name', 'fid' => 'f.id', 'f.forum_name');
        $where_get_forum_list_split = array(
            array('fp.post_topics' => 'IS NULL'),
            array('fp.post_topics' => '1')
        );
        $order_by_get_forum_list_split = array('c.disp_position', 'c.id', 'f.disp_position');

        $result = \ORM::for_table($this->feather->prefix.'categories')
            ->table_alias('c')
            ->select_many($select_get_forum_list_split)
            ->inner_join($this->feather->prefix.'forums', array('c.id', '=', 'f.cat_id'), 'f')
            ->left_outer_join($this->feather->prefix.'forum_perms', array('fp.forum_id', '=', 'f.id'), 'fp')
            ->left_outer_join($this->feather->prefix.'forum_perms', array('fp.group_id', '=', $this->user->g_id), '', true)
            ->where_any_is($where_get_forum_list_split)
            ->where_null('f.redirect_url')
            ->order_by_many($order_by_get_forum_list_split)
            ->find_result_set();

        $cur_category = 0;

        foreach($result as $cur_forum) {
            if ($cur_forum->cid != $cur_category) {
                // A new category since last iteration?

                if ($cur_category) {
                    $output .= "\t\t\t\t\t\t\t".'</optgroup>'."\n";
                }

                $output .= "\t\t\t\t\t\t\t".'<optgroup label="'.feather_escape($cur_forum->cat_name).'">'."\n";
                $cur_category = $cur_forum->cid;
            }

            $output .= "\t\t\t\t\t\t\t\t".'<option value="'.$cur_forum->fid.'"'.($id == $cur_forum->fid ? ' selected="selected"' : '').'>'.feather_escape($cur_forum->forum_name).'</option>'."\n";
        }

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

        $result = \ORM::for_table($this->feather->prefix.'categories')
            ->table_alias('c')
            ->select_many($select_get_forum_list_move)
            ->inner_join($this->feather->prefix.'forums', array('c.id', '=', 'f.cat_id'), 'f')
            ->left_outer_join($this->feather->prefix.'forum_perms', array('fp.forum_id', '=', 'f.id'), 'fp')
            ->left_outer_join($this->feather->prefix.'forum_perms', array('fp.group_id', '=', $this->user->g_id), '', true)
            ->where_any_is($where_get_forum_list_move)
            ->where_null('f.redirect_url')
            ->order_by_many($order_by_get_forum_list_move)
            ->find_result_set();

        $cur_category = 0;

        foreach($result as $cur_forum) {
            if ($cur_forum->cid != $cur_category) {
                // A new category since last iteration?

                if ($cur_category) {
                    $output .= "\t\t\t\t\t\t\t".'</optgroup>'."\n";
                }

                $output .= "\t\t\t\t\t\t\t".'<optgroup label="'.feather_escape($cur_forum->cat_name).'">'."\n";
                $cur_category = $cur_forum->cid;
            }

            if ($cur_forum->fid != $id) {
                $output .= "\t\t\t\t\t\t\t\t".'<option value="'.$cur_forum->fid.'">'.feather_escape($cur_forum->forum_name).'</option>'."\n";
            }
        }

        return $output;
    }

    public function display_posts_view($tid, $start_from)
    {
        global $pd, $lang_topic;

        $post_data = array();

        require FEATHER_ROOT.'include/parser.php';

        $post_count = 0; // Keep track of post numbers

        // Retrieve a list of post IDs, LIMIT is (really) expensive so we only fetch the IDs here then later fetch the remaining data
        $find_ids = \ORM::for_table($this->db->prefix.'posts')->select('id')
            ->where('topic_id', $tid)
            ->order_by('id')
            ->limit($this->user->disp_posts)
            ->offset($start_from)
            ->find_many();

        foreach ($find_ids as $id) {
            $post_ids[] = $id['id'];
        }

        // Retrieve the posts (and their respective poster)
        $select_display_posts_view = array('u.title', 'u.num_posts', 'g.g_id', 'g.g_user_title', 'p.id', 'p.poster', 'p.poster_id', 'p.message', 'p.hide_smilies', 'p.posted', 'p.edited', 'p.edited_by');

        $result = \ORM::for_table($this->feather->prefix.'posts')
            ->table_alias('p')
            ->select_many($select_display_posts_view)
            ->inner_join($this->feather->prefix.'users', array('u.id', '=', 'p.poster_id'), 'u')
            ->inner_join($this->feather->prefix.'groups', array('g.g_id', '=', 'u.group_id'), 'g')
            ->where_in('p.id', $post_ids)
            ->order_by('p.id')
            ->find_many();

        foreach($result as $cur_post) {
            $post_count++;

            // If the poster is a registered user
            if ($cur_post->poster_id > 1) {
                if ($this->user->g_view_users == '1') {
                    $cur_post->poster_disp = '<a href="'.get_link('user/'.$cur_post->poster_id.'/').'">'.feather_escape($cur_post->poster).'</a>';
                } else {
                    $cur_post->poster_disp = feather_escape($cur_post->poster);
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
                $cur_post->poster_disp = feather_escape($cur_post->poster);
                $cur_post->user_title = $lang_topic['Guest'];
            }

            // Perform the main parsing of the message (BBCode, smilies, censor words etc)
            $cur_post->message = parse_message($cur_post->message, $cur_post->hide_smilies);

            $post_data[] = $cur_post;
        }

        return $post_data;
    }

    public function move_topics_to($fid, $tfid = null, $param = null)
    {
        global $lang_common, $lang_misc;

        confirm_referrer(array(
            get_link_r('moderate/forum/'.$fid.'/'),
            get_link_r('moderate/topic/'.$tfid.'/forum/'.$fid.'/action/move/'),
            get_link_r('moderate/topic/'.$tfid.'/forum/'.$fid.'/action/move/param/'.$param.'/'),
        ));

        if (@preg_match('%[^0-9,]%', $this->request->post('topics'))) {
            message($lang_common['Bad request'], false, '404 Not Found');
        }

        $topics = explode(',', $this->request->post('topics'));
        $move_to_forum = $this->request->post('move_to_forum') ? intval($this->request->post('move_to_forum')) : 0;
        if (empty($topics) || $move_to_forum < 1) {
            message($lang_common['Bad request'], false, '404 Not Found');
        }

        // Verify that the topic IDs are valid
        $result = \ORM::for_table($this->db->prefix.'topics')
            ->where_in('id', $topics)
            ->where('forum_id', $fid)
            ->find_many();

        if (count($result) != count($topics)) {
            message($lang_common['Bad request'], false, '404 Not Found');
        }


        // Verify that the move to forum ID is valid
        $where_move_topics_to = array(
            array('fp.post_topics' => 'IS NULL'),
            array('fp.post_topics' => '1')
        );

        $authorized = \ORM::for_table($this->feather->prefix.'forums')
            ->table_alias('f')
            ->left_outer_join($this->feather->prefix.'forum_perms', array('fp.forum_id', '=', $move_to_forum), 'fp', true)
            ->left_outer_join($this->feather->prefix.'forum_perms', array('fp.group_id', '=', $this->user->g_id), '', true)
            ->where_any_is($where_move_topics_to)
            ->where_null('f.redirect_url')
            ->find_one();

        if (!$authorized) {
            message($lang_common['Bad request'], false, '404 Not Found');
        }

        // Delete any redirect topics if there are any (only if we moved/copied the topic back to where it was once moved from)
        \ORM::for_table($this->db->prefix.'topics')
            ->where('forum_id', $move_to_forum)
            ->where_in('moved_to', $topics)
            ->delete_many();

        // Move the topic(s)
        \ORM::for_table($this->db->prefix.'topics')->where_in('id', $topics)
            ->find_one()
            ->set('forum_id', $move_to_forum)
            ->save();

        // Should we create redirect topics?
        if ($this->request->post('with_redirect')) {
            foreach ($topics as $cur_topic) {
                // Fetch info for the redirect topic
                $select_move_topics_to = array('poster', 'subject', 'posted', 'last_post');

                $moved_to = \ORM::for_table($this->db->prefix.'topics')->select_many($select_move_topics_to)
                    ->where('id', $cur_topic)
                    ->find_one();

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
                \ORM::for_table($this->db->prefix.'topics')
                    ->create()
                    ->set($insert_move_topics_to)
                    ->save();

            }
        }

        update_forum($fid); // Update the forum FROM which the topic was moved
        update_forum($move_to_forum); // Update the forum TO which the topic was moved

        $redirect_msg = (count($topics) > 1) ? $lang_misc['Move topics redirect'] : $lang_misc['Move topic redirect'];
        redirect(get_link('forum/'.$move_to_forum.'/'), $redirect_msg);
    }

    public function check_move_possible()
    {
        global $lang_misc;

        $select_check_move_possible = array('cid' => 'c.id', 'c.cat_name', 'fid' => 'f.id', 'f.forum_name');
        $where_check_move_possible = array(
            array('fp.post_topics' => 'IS NULL'),
            array('fp.post_topics' => '1')
        );
        $order_by_check_move_possible = array('c.disp_position', 'c.id', 'f.disp_position');

        $result = \ORM::for_table($this->feather->prefix.'categories')
            ->table_alias('c')
            ->select_many($select_check_move_possible)
            ->inner_join($this->feather->prefix.'forums', array('c.id', '=', 'f.cat_id'), 'f')
            ->left_outer_join($this->feather->prefix.'forum_perms', array('fp.forum_id', '=', 'f.id'), 'fp')
            ->left_outer_join($this->feather->prefix.'forum_perms', array('fp.group_id', '=', $this->user->g_id), '', true)
            ->where_any_is($where_check_move_possible)
            ->where_null('f.redirect_url')
            ->order_by_many($order_by_check_move_possible)
            ->find_many();

        if (count($result) < 2) {
            message($lang_misc['Nowhere to move']);
        }
    }

    public function merge_topics($fid)
    {
        global $lang_common, $lang_misc;

        confirm_referrer(get_link_r('moderate/forum/'.$fid.'/'));

        if (@preg_match('%[^0-9,]%', $this->request->post('topics'))) {
            message($lang_common['Bad request'], false, '404 Not Found');
        }

        $topics = explode(',', $this->request->post('topics'));
        if (count($topics) < 2) {
            message($lang_misc['Not enough topics selected']);
        }

        // Verify that the topic IDs are valid (redirect links will point to the merged topic after the merge)
        $result = \ORM::for_table($this->feather->prefix.'topics')
            ->where_in('id', $topics)
            ->where('forum_id', $fid)
            ->find_many();

        if (count($result) != count($topics)) {
            message($lang_common['Bad request'], false, '404 Not Found');
        }

        // The topic that we are merging into is the one with the smallest ID
        $merge_to_tid = \ORM::for_table($this->feather->prefix.'topics')
            ->where_in('id', $topics)
            ->where('forum_id', $fid)
            ->order_by_asc('id')
            ->find_one_col('id');

        // Make any redirect topics point to our new, merged topic
        $query = 'UPDATE '.$this->db->prefix.'topics SET moved_to='.$merge_to_tid.' WHERE moved_to IN('.implode(',', $topics).')';

        // Should we create redirect topics?
        if ($this->request->post('with_redirect')) {
            $query .= ' OR (id IN('.implode(',', $topics).') AND id != '.$merge_to_tid.')';
        }

        \ORM::for_table($this->db->prefix.'topics')->raw_query($query);

        // Merge the posts into the topic
        \ORM::for_table($this->db->prefix.'posts')->where_in('topic_id', $topics)
            ->find_one()
            ->set('topic_id', $merge_to_tid)
            ->save();

        // Update any subscriptions
        $result = $this->db->query('SELECT DISTINCT user_id FROM '.$this->db->prefix.'topic_subscriptions WHERE topic_id IN('.implode(',', $topics).')') or error('Unable to fetch subscriptions of merged topics', __FILE__, __LINE__, $this->db->error());

        $subscribed_users = array();
        while ($row = $this->db->fetch_row($result)) {
            $subscribed_users[] = $row[0];
        }

        // Delete the subscriptions
        \ORM::for_table($this->db->prefix.'topic_subscriptions')
            ->where_in('topic_id', $topics)
            ->delete_many();

        foreach ($subscribed_users as $cur_user_id) {
            $this->db->query('INSERT INTO '.$this->db->prefix.'topic_subscriptions (topic_id, user_id) VALUES ('.$merge_to_tid.', '.$cur_user_id.')') or error('Unable to re-enter subscriptions for merge topic', __FILE__, __LINE__, $this->db->error());
        }

        // Without redirection the old topics are removed
        if ($this->request->post('with_redirect') != '') {
            $this->db->query('DELETE FROM '.$this->db->prefix.'topics WHERE id IN('.implode(',', $topics).') AND id != '.$merge_to_tid) or error('Unable to delete old topics', __FILE__, __LINE__, $this->db->error());
        }

        // Count number of replies in the topic
        $result = $this->db->query('SELECT COUNT(id) FROM '.$this->db->prefix.'posts WHERE topic_id='.$merge_to_tid) or error('Unable to fetch post count for topic', __FILE__, __LINE__, $this->db->error());
        $num_replies = $this->db->result($result, 0) - 1;

        // Get last_post, last_post_id and last_poster
        $result = $this->db->query('SELECT posted, id, poster FROM '.$this->db->prefix.'posts WHERE topic_id='.$merge_to_tid.' ORDER BY id DESC LIMIT 1') or error('Unable to get last post info', __FILE__, __LINE__, $this->db->error());
        list($last_post, $last_post_id, $last_poster) = $this->db->fetch_row($result);

        // Update topic
        $this->db->query('UPDATE '.$this->db->prefix.'topics SET num_replies='.$num_replies.', last_post='.$last_post.', last_post_id='.$last_post_id.', last_poster=\''.$this->db->escape($last_poster).'\' WHERE id='.$merge_to_tid) or error('Unable to update topic', __FILE__, __LINE__, $this->db->error());

        // Update the forum FROM which the topic was moved and redirect
        update_forum($fid);
        redirect(get_link('forum/'.$fid.'/'), $lang_misc['Merge topics redirect']);
    }

    public function delete_topics($topics, $fid)
    {
        global $lang_misc, $lang_common;
        confirm_referrer(get_link_r('moderate/forum/'.$fid.'/'));

        if (@preg_match('%[^0-9,]%', $topics)) {
            message($lang_common['Bad request'], false, '404 Not Found');
        }

        require FEATHER_ROOT.'include/search_idx.php';

        // Verify that the topic IDs are valid
        $result = $this->db->query('SELECT 1 FROM '.$this->db->prefix.'topics WHERE id IN('.$topics.') AND forum_id='.$fid) or error('Unable to check topics', __FILE__, __LINE__, $this->db->error());

        if ($this->db->num_rows($result) != substr_count($topics, ',') + 1) {
            message($lang_common['Bad request'], false, '404 Not Found');
        }

        // Verify that the posts are not by admins
        if ($this->user->g_id != FEATHER_ADMIN) {
            $result = $this->db->query('SELECT 1 FROM '.$this->db->prefix.'posts WHERE topic_id IN('.$topics.') AND poster_id IN('.implode(',', get_admin_ids()).')') or error('Unable to check posts', __FILE__, __LINE__, $this->db->error());
            if ($this->db->num_rows($result)) {
                message($lang_common['No permission'], false, '403 Forbidden');
            }
        }

        // Delete the topics and any redirect topics
        $this->db->query('DELETE FROM '.$this->db->prefix.'topics WHERE id IN('.$topics.') OR moved_to IN('.$topics.')') or error('Unable to delete topic', __FILE__, __LINE__, $this->db->error());

        // Delete any subscriptions
        $this->db->query('DELETE FROM '.$this->db->prefix.'topic_subscriptions WHERE topic_id IN('.$topics.')') or error('Unable to delete subscriptions', __FILE__, __LINE__, $this->db->error());

        // Create a list of the post IDs in this topic and then strip the search index
        $result = $this->db->query('SELECT id FROM '.$this->db->prefix.'posts WHERE topic_id IN('.$topics.')') or error('Unable to fetch posts', __FILE__, __LINE__, $this->db->error());

        $post_ids = '';
        while ($row = $this->db->fetch_row($result)) {
            $post_ids .= ($post_ids != '') ? ','.$row[0] : $row[0];
        }

        // We have to check that we actually have a list of post IDs since we could be deleting just a redirect topic
        if ($post_ids != '') {
            strip_search_index($post_ids);
        }

        // Delete posts
        $this->db->query('DELETE FROM '.$this->db->prefix.'posts WHERE topic_id IN('.$topics.')') or error('Unable to delete posts', __FILE__, __LINE__, $this->db->error());

        update_forum($fid);

        redirect(get_link('forum/'.$fid.'/'), $lang_misc['Delete topics redirect']);
    }

    public function get_forum_info($fid)
    {
        global $lang_common;

        $result = $this->db->query('SELECT f.forum_name, f.redirect_url, f.num_topics, f.sort_by FROM '.$this->db->prefix.'forums AS f LEFT JOIN '.$this->db->prefix.'forum_perms AS fp ON (fp.forum_id=f.id AND fp.group_id='.$this->user->g_id.') WHERE (fp.read_forum IS NULL OR fp.read_forum=1) AND f.id='.$fid) or error('Unable to fetch forum info', __FILE__, __LINE__, $this->db->error());
        if (!$this->db->num_rows($result)) {
            message($lang_common['Bad request'], false, '404 Not Found');
        }

        $cur_forum = $this->db->fetch_assoc($result);

        return $cur_forum;
    }

    public function forum_sort_by($forum_sort)
    {
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

        return $sort_by;
    }

    public function display_topics($fid, $sort_by, $start_from)
    {
        global $lang_forum, $lang_common;

        $topic_data = array();

        // Get topic/forum tracking data
        if (!$this->user->is_guest) {
            $tracked_topics = get_tracked_topics();
        }

        // Retrieve a list of topic IDs, LIMIT is (really) expensive so we only fetch the IDs here then later fetch the remaining data
        $result = $this->db->query('SELECT id FROM '.$this->db->prefix.'topics WHERE forum_id='.$fid.' ORDER BY sticky DESC, '.$sort_by.', id DESC LIMIT '.$start_from.', '.$this->user->disp_topics) or error('Unable to fetch topic IDs', __FILE__, __LINE__, $this->db->error());

        // If there are topics in this forum
        if ($this->db->num_rows($result)) {
            $topic_ids = array();
            for ($i = 0;$cur_topic_id = $this->db->result($result, $i);$i++) {
                $topic_ids[] = $cur_topic_id;
            }

            // Select topics
            $result = $this->db->query('SELECT id, poster, subject, posted, last_post, last_post_id, last_poster, num_views, num_replies, closed, sticky, moved_to FROM '.$this->db->prefix.'topics WHERE id IN('.implode(',', $topic_ids).') ORDER BY sticky DESC, '.$sort_by.', id DESC') or error('Unable to fetch topic list for forum', __FILE__, __LINE__, $this->db->error());

            $topic_count = 0;
            while ($cur_topic = $this->db->fetch_assoc($result)) {
                ++$topic_count;
                $status_text = array();
                $cur_topic['item_status'] = ($topic_count % 2 == 0) ? 'roweven' : 'rowodd';
                $cur_topic['icon_type'] = 'icon';
                $url_topic = url_friendly($cur_topic['subject']);

                if (is_null($cur_topic['moved_to'])) {
                    $cur_topic['last_post_disp'] = '<a href="'.get_link('post/'.$cur_topic['last_post_id'].'/#p'.$cur_topic['last_post_id']).'">'.format_time($cur_topic['last_post']).'</a> <span class="byuser">'.$lang_common['by'].' '.feather_escape($cur_topic['last_poster']).'</span>';
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
                    $status_text[] = '<span class="stickytext">'.$lang_forum['Sticky'].'</span>';
                }

                if ($cur_topic['moved_to'] != 0) {
                    $cur_topic['subject_disp'] = '<a href="'.get_link('topic/'.$cur_topic['moved_to'].'/'.$url_topic.'/').'">'.feather_escape($cur_topic['subject']).'</a> <span class="byuser">'.$lang_common['by'].' '.feather_escape($cur_topic['poster']).'</span>';
                    $status_text[] = '<span class="movedtext">'.$lang_forum['Moved'].'</span>';
                    $cur_topic['item_status'] .= ' imoved';
                } elseif ($cur_topic['closed'] == '0') {
                    $cur_topic['subject_disp'] = '<a href="'.get_link('topic/'.$cur_topic['id'].'/'.$url_topic.'/').'">'.feather_escape($cur_topic['subject']).'</a> <span class="byuser">'.$lang_common['by'].' '.feather_escape($cur_topic['poster']).'</span>';
                } else {
                    $cur_topic['subject_disp'] = '<a href="'.get_link('topic/'.$cur_topic['id'].'/'.$url_topic.'/').'">'.feather_escape($cur_topic['subject']).'</a> <span class="byuser">'.$lang_common['by'].' '.feather_escape($cur_topic['poster']).'</span>';
                    $status_text[] = '<span class="closedtext">'.$lang_forum['Closed'].'</span>';
                    $cur_topic['item_status'] .= ' iclosed';
                }

                if (!$cur_topic['ghost_topic'] && $cur_topic['last_post'] > $this->user->last_visit && (!isset($tracked_topics['topics'][$cur_topic['id']]) || $tracked_topics['topics'][$cur_topic['id']] < $cur_topic['last_post']) && (!isset($tracked_topics['forums'][$fid]) || $tracked_topics['forums'][$fid] < $cur_topic['last_post'])) {
                    $cur_topic['item_status'] .= ' inew';
                    $cur_topic['icon_type'] = 'icon icon-new';
                    $cur_topic['subject_disp'] = '<strong>'.$cur_topic['subject_disp'].'</strong>';
                    $subject_new_posts = '<span class="newtext">[ <a href="'.get_link('topic/'.$cur_topic['id'].'/action/new/').'" title="'.$lang_common['New posts info'].'">'.$lang_common['New posts'].'</a> ]</span>';
                } else {
                    $subject_new_posts = null;
                }

                // Insert the status text before the subject
                $cur_topic['subject_disp'] = implode(' ', $status_text).' '.$cur_topic['subject_disp'];

                $num_pages_topic = ceil(($cur_topic['num_replies'] + 1) / $this->user->disp_posts);

                if ($num_pages_topic > 1) {
                    $subject_multipage = '<span class="pagestext">[ '.paginate($num_pages_topic, -1, 'topic/'.$cur_topic['id'].'/'.$url_topic.'/#').' ]</span>';
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

        return $topic_data;
    }
    
    public function stick_topic($id, $fid)
    {
        $this->db->query('UPDATE '.$this->db->prefix.'topics SET sticky=\'1\' WHERE id='.$id.' AND forum_id='.$fid) or error('Unable to stick topic', __FILE__, __LINE__, $this->db->error());
    }

    public function unstick_topic($id, $fid)
    {
        $this->db->query('UPDATE '.$this->db->prefix.'topics SET sticky=\'0\' WHERE id='.$id.' AND forum_id='.$fid) or error('Unable to stick topic', __FILE__, __LINE__, $this->db->error());
    }
    
    public function open_topic($id, $fid)
    {
        $this->db->query('UPDATE '.$this->db->prefix.'topics SET closed=\'0\' WHERE id='.$id.' AND forum_id='.$fid) or error('Unable to unstick topic', __FILE__, __LINE__, $this->db->error());
    }
    
    public function close_topic($id, $fid)
    {
        $this->db->query('UPDATE '.$this->db->prefix.'topics SET closed=\'1\' WHERE id='.$id.' AND forum_id='.$fid) or error('Unable to unstick topic', __FILE__, __LINE__, $this->db->error());
    }
    
    public function close_multiple_topics($action, $topics, $fid)
    {
        $this->db->query('UPDATE '.$this->db->prefix.'topics SET closed='.$action.' WHERE id IN('.implode(',', $topics).') AND forum_id='.$fid) or error('Unable to close topics', __FILE__, __LINE__, $this->db->error());
    }
    
    public function get_subject_tid($id)
    {
        $result = $this->db->query('SELECT subject FROM '.$this->db->prefix.'topics WHERE id='.$id) or error('Unable to get subject', __FILE__, __LINE__, $this->db->error());
        if (!$this->db->num_rows($result)) {
            message($lang_common['Bad request'], false, '404 Not Found');
        }
        return $this->db->result($result);
    }
}