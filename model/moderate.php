<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */
 
 
function display_ip_info($ip)
{
    global $lang_misc, $feather_user;

    // Load the misc.php language file
    require FEATHER_ROOT.'lang/'.$feather_user['language'].'/misc.php';

    message(sprintf($lang_misc['Host info 1'], $ip).'<br />'.sprintf($lang_misc['Host info 2'], @gethostbyaddr($ip)).'<br /><br /><a href="'.get_link('admin/users/show-users/ip/'.$ip.'/').'">'.$lang_misc['Show more users'].'</a>');
}

function display_ip_address_post($pid)
{
    global $db, $lang_common, $lang_misc, $feather_user;

    $result = $db->query('SELECT poster_ip FROM '.$db->prefix.'posts WHERE id='.$pid) or error('Unable to fetch post IP address', __FILE__, __LINE__, $db->error());
    if (!$db->num_rows($result)) {
        message($lang_common['Bad request'], false, '404 Not Found');
    }

    $ip = $db->result($result);

    // Load the misc.php language file
    require FEATHER_ROOT.'lang/'.$feather_user['language'].'/misc.php';

    message(sprintf($lang_misc['Host info 1'], $ip).'<br />'.sprintf($lang_misc['Host info 2'], @gethostbyaddr($ip)).'<br /><br /><a href="'.get_link('admin/users/show-users/ip/'.$ip.'/').'">'.$lang_misc['Show more users'].'</a>');
}

function get_moderators($fid)
{
    global $db;
    
    $result = $db->query('SELECT moderators FROM '.$db->prefix.'forums WHERE id='.$fid) or error('Unable to fetch forum info', __FILE__, __LINE__, $db->error());

    $moderators = $db->result($result);
    
    return $moderators;
}

function get_topic_info($fid, $tid)
{
    global $db, $feather_user;
    
    // Fetch some info about the topic
    $result = $db->query('SELECT t.subject, t.num_replies, t.first_post_id, f.id AS forum_id, forum_name FROM '.$db->prefix.'topics AS t INNER JOIN '.$db->prefix.'forums AS f ON f.id=t.forum_id LEFT JOIN '.$db->prefix.'forum_perms AS fp ON (fp.forum_id=f.id AND fp.group_id='.$feather_user['g_id'].') WHERE (fp.read_forum IS NULL OR fp.read_forum=1) AND f.id='.$fid.' AND t.id='.$tid.' AND t.moved_to IS NULL') or error('Unable to fetch topic info', __FILE__, __LINE__, $db->error());
    if (!$db->num_rows($result)) {
        message($lang_common['Bad request'], false, '404 Not Found');
    }

    $cur_topic = $db->fetch_assoc($result);
    
    return $cur_topic;
}

function delete_posts($feather, $tid, $fid, $p = null)
{
    global $db, $lang_common, $lang_misc, $feather_user;
    
    $posts = $feather->request->post('posts') ? $feather->request->post('posts') : array();
    if (empty($posts)) {
        message($lang_misc['No posts selected']);
    }

    if ($feather->request->post('delete_posts_comply')) {
        confirm_referrer(array(
            get_link_r('moderate/forum/'.$fid.'/'),
            get_link_r('moderate/topic/'.$tid.'/forum/'.$fid.'/action/moderate/page/'.$p.'/'),
        ));

        if (@preg_match('%[^0-9,]%', $posts)) {
            message($lang_common['Bad request'], false, '404 Not Found');
        }

        // Verify that the post IDs are valid
        $admins_sql = ($feather_user['g_id'] != PUN_ADMIN) ? ' AND poster_id NOT IN('.implode(',', get_admin_ids()).')' : '';
        $result = $db->query('SELECT 1 FROM '.$db->prefix.'posts WHERE id IN('.$posts.') AND topic_id='.$tid.$admins_sql) or error('Unable to check posts', __FILE__, __LINE__, $db->error());

        if ($db->num_rows($result) != substr_count($posts, ',') + 1) {
            message($lang_common['Bad request'], false, '404 Not Found');
        }

        // Delete the posts
        $db->query('DELETE FROM '.$db->prefix.'posts WHERE id IN('.$posts.')') or error('Unable to delete posts', __FILE__, __LINE__, $db->error());

        require FEATHER_ROOT.'include/search_idx.php';
        strip_search_index($posts);

        // Get last_post, last_post_id, and last_poster for the topic after deletion
        $result = $db->query('SELECT id, poster, posted FROM '.$db->prefix.'posts WHERE topic_id='.$tid.' ORDER BY id DESC LIMIT 1') or error('Unable to fetch post info', __FILE__, __LINE__, $db->error());
        $last_post = $db->fetch_assoc($result);

        // How many posts did we just delete?
        $num_posts_deleted = substr_count($posts, ',') + 1;

        // Update the topic
        $db->query('UPDATE '.$db->prefix.'topics SET last_post='.$last_post['posted'].', last_post_id='.$last_post['id'].', last_poster=\''.$db->escape($last_post['poster']).'\', num_replies=num_replies-'.$num_posts_deleted.' WHERE id='.$tid) or error('Unable to update topic', __FILE__, __LINE__, $db->error());

        update_forum($fid);

        redirect(get_link('topic/'.$tid.'/'), $lang_misc['Delete posts redirect']);
    }
    
    return $posts;
}

function split_posts($feather, $tid, $fid, $p = null)
{
    global $db, $lang_common, $lang_misc, $feather_user;
    
    $posts = $feather->request->post('posts') ? $feather->request->post('posts') : array();
    if (empty($posts)) {
        message($lang_misc['No posts selected']);
    }

    if ($feather->request->post('split_posts_comply')) {
        confirm_referrer(array(
            get_link_r('moderate/forum/'.$fid.'/'),
            get_link_r('moderate/topic/'.$tid.'/forum/'.$fid.'/action/moderate/page/'.$p.'/'),
        ));

        if (@preg_match('%[^0-9,]%', $posts)) {
            message($lang_common['Bad request'], false, '404 Not Found');
        }

        $move_to_forum = $feather->request->post('move_to_forum') ? intval($feather->request->post('move_to_forum')) : 0;
        if ($move_to_forum < 1) {
            message($lang_common['Bad request'], false, '404 Not Found');
        }

        // How many posts did we just split off?
        $num_posts_splitted = substr_count($posts, ',') + 1;

        // Verify that the post IDs are valid
        $result = $db->query('SELECT 1 FROM '.$db->prefix.'posts WHERE id IN('.$posts.') AND topic_id='.$tid) or error('Unable to check posts', __FILE__, __LINE__, $db->error());
        if ($db->num_rows($result) != $num_posts_splitted) {
            message($lang_common['Bad request'], false, '404 Not Found');
        }

        // Verify that the move to forum ID is valid
        $result = $db->query('SELECT 1 FROM '.$db->prefix.'forums AS f LEFT JOIN '.$db->prefix.'forum_perms AS fp ON (fp.group_id='.$feather_user['g_id'].' AND fp.forum_id='.$move_to_forum.') WHERE f.redirect_url IS NULL AND (fp.post_topics IS NULL OR fp.post_topics=1)') or error('Unable to fetch forum permissions', __FILE__, __LINE__, $db->error());
        if (!$db->num_rows($result)) {
            message($lang_common['Bad request'], false, '404 Not Found');
        }

        // Load the post.php language file
        require FEATHER_ROOT.'lang/'.$feather_user['language'].'/post.php';

        // Check subject
        $new_subject = $feather->request->post('new_subject') ? pun_trim($feather->request->post('new_subject')) : '';

        if ($new_subject == '') {
            message($lang_post['No subject']);
        } elseif (pun_strlen($new_subject) > 70) {
            message($lang_post['Too long subject']);
        }

        // Get data from the new first post
        $result = $db->query('SELECT p.id, p.poster, p.posted FROM '.$db->prefix.'posts AS p WHERE id IN('.$posts.') ORDER BY p.id ASC LIMIT 1') or error('Unable to get first post', __FILE__, __LINE__, $db->error());
        $first_post_data = $db->fetch_assoc($result);

        // Create the new topic
        $db->query('INSERT INTO '.$db->prefix.'topics (poster, subject, posted, first_post_id, forum_id) VALUES (\''.$db->escape($first_post_data['poster']).'\', \''.$db->escape($new_subject).'\', '.$first_post_data['posted'].', '.$first_post_data['id'].', '.$move_to_forum.')') or error('Unable to create new topic', __FILE__, __LINE__, $db->error());
        $new_tid = $db->insert_id();

        // Move the posts to the new topic
        $db->query('UPDATE '.$db->prefix.'posts SET topic_id='.$new_tid.' WHERE id IN('.$posts.')') or error('Unable to move posts into new topic', __FILE__, __LINE__, $db->error());

        // Apply every subscription to both topics
        $db->query('INSERT INTO '.$db->prefix.'topic_subscriptions (user_id, topic_id) SELECT user_id, '.$new_tid.' FROM '.$db->prefix.'topic_subscriptions WHERE topic_id='.$tid) or error('Unable to copy existing subscriptions', __FILE__, __LINE__, $db->error());

        // Get last_post, last_post_id, and last_poster from the topic and update it
        $result = $db->query('SELECT id, poster, posted FROM '.$db->prefix.'posts WHERE topic_id='.$tid.' ORDER BY id DESC LIMIT 1') or error('Unable to fetch post info', __FILE__, __LINE__, $db->error());
        $last_post_data = $db->fetch_assoc($result);
        $db->query('UPDATE '.$db->prefix.'topics SET last_post='.$last_post_data['posted'].', last_post_id='.$last_post_data['id'].', last_poster=\''.$db->escape($last_post_data['poster']).'\', num_replies=num_replies-'.$num_posts_splitted.' WHERE id='.$tid) or error('Unable to update topic', __FILE__, __LINE__, $db->error());

        // Get last_post, last_post_id, and last_poster from the new topic and update it
        $result = $db->query('SELECT id, poster, posted FROM '.$db->prefix.'posts WHERE topic_id='.$new_tid.' ORDER BY id DESC LIMIT 1') or error('Unable to fetch post info', __FILE__, __LINE__, $db->error());
        $last_post_data = $db->fetch_assoc($result);
        $db->query('UPDATE '.$db->prefix.'topics SET last_post='.$last_post_data['posted'].', last_post_id='.$last_post_data['id'].', last_poster=\''.$db->escape($last_post_data['poster']).'\', num_replies='.($num_posts_splitted-1).' WHERE id='.$new_tid) or error('Unable to update topic', __FILE__, __LINE__, $db->error());

        update_forum($fid);
        update_forum($move_to_forum);

        redirect(get_link('topic/'.$new_tid.'/'), $lang_misc['Split posts redirect']);
    }
    
    return $posts;
}

function get_forum_list_split($id)
{
    global $db, $feather_user;
    
    $result = $db->query('SELECT c.id AS cid, c.cat_name, f.id AS fid, f.forum_name FROM '.$db->prefix.'categories AS c INNER JOIN '.$db->prefix.'forums AS f ON c.id=f.cat_id LEFT JOIN '.$db->prefix.'forum_perms AS fp ON (fp.forum_id=f.id AND fp.group_id='.$feather_user['g_id'].') WHERE (fp.post_topics IS NULL OR fp.post_topics=1) AND f.redirect_url IS NULL ORDER BY c.disp_position, c.id, f.disp_position') or error('Unable to fetch category/forum list', __FILE__, __LINE__, $db->error());

    $cur_category = 0;
    while ($cur_forum = $db->fetch_assoc($result)) {
        if ($cur_forum['cid'] != $cur_category) {
            // A new category since last iteration?

            if ($cur_category) {
                echo "\t\t\t\t\t\t\t".'</optgroup>'."\n";
            }

            echo "\t\t\t\t\t\t\t".'<optgroup label="'.pun_htmlspecialchars($cur_forum['cat_name']).'">'."\n";
            $cur_category = $cur_forum['cid'];
        }

        echo "\t\t\t\t\t\t\t\t".'<option value="'.$cur_forum['fid'].'"'.($id == $cur_forum['fid'] ? ' selected="selected"' : '').'>'.pun_htmlspecialchars($cur_forum['forum_name']).'</option>'."\n";
    }
}

function get_forum_list_move($id)
{
    global $db, $feather_user;
    
    $result = $db->query('SELECT c.id AS cid, c.cat_name, f.id AS fid, f.forum_name FROM '.$db->prefix.'categories AS c INNER JOIN '.$db->prefix.'forums AS f ON c.id=f.cat_id LEFT JOIN '.$db->prefix.'forum_perms AS fp ON (fp.forum_id=f.id AND fp.group_id='.$feather_user['g_id'].') WHERE (fp.post_topics IS NULL OR fp.post_topics=1) AND f.redirect_url IS NULL ORDER BY c.disp_position, c.id, f.disp_position') or error('Unable to fetch category/forum list', __FILE__, __LINE__, $db->error());
    
    $cur_category = 0;
    while ($cur_forum = $db->fetch_assoc($result)) {
        if ($cur_forum['cid'] != $cur_category) {
            // A new category since last iteration?

            if ($cur_category) {
                echo "\t\t\t\t\t\t\t".'</optgroup>'."\n";
            }

            echo "\t\t\t\t\t\t\t".'<optgroup label="'.pun_htmlspecialchars($cur_forum['cat_name']).'">'."\n";
            $cur_category = $cur_forum['cid'];
        }

        if ($cur_forum['fid'] != $id) {
            echo "\t\t\t\t\t\t\t\t".'<option value="'.$cur_forum['fid'].'">'.pun_htmlspecialchars($cur_forum['forum_name']).'</option>'."\n";
        }
    }
}

function display_posts_view($tid, $start_from)
{
    global $db, $feather_user, $feather_config, $pd, $lang_topic;
    
    $post_data = array();
    
    require FEATHER_ROOT.'include/parser.php';

    $post_count = 0; // Keep track of post numbers

    // Retrieve a list of post IDs, LIMIT is (really) expensive so we only fetch the IDs here then later fetch the remaining data
    $result = $db->query('SELECT id FROM '.$db->prefix.'posts WHERE topic_id='.$tid.' ORDER BY id LIMIT '.$start_from.','.$feather_user['disp_posts']) or error('Unable to fetch post IDs', __FILE__, __LINE__, $db->error());

    $post_ids = array();
    for ($i = 0;$cur_post_id = $db->result($result, $i);$i++) {
        $post_ids[] = $cur_post_id;
    }

    // Retrieve the posts (and their respective poster)
    $result = $db->query('SELECT u.title, u.num_posts, g.g_id, g.g_user_title, p.id, p.poster, p.poster_id, p.message, p.hide_smilies, p.posted, p.edited, p.edited_by FROM '.$db->prefix.'posts AS p INNER JOIN '.$db->prefix.'users AS u ON u.id=p.poster_id INNER JOIN '.$db->prefix.'groups AS g ON g.g_id=u.group_id WHERE p.id IN ('.implode(',', $post_ids).') ORDER BY p.id', true) or error('Unable to fetch post info', __FILE__, __LINE__, $db->error());

    while ($cur_post = $db->fetch_assoc($result)) {
        $post_count++;

        // If the poster is a registered user
        if ($cur_post['poster_id'] > 1) {
            if ($feather_user['g_view_users'] == '1') {
                $cur_post['poster_disp'] = '<a href="'.get_link('user/'.$cur_post['poster_id'].'/').'">'.pun_htmlspecialchars($cur_post['poster']).'</a>';
            } else {
                $cur_post['poster_disp'] = pun_htmlspecialchars($cur_post['poster']);
            }

            // get_title() requires that an element 'username' be present in the array
            $cur_post['username'] = $cur_post['poster'];
            $cur_post['user_title'] = get_title($cur_post);

            if ($feather_config['o_censoring'] == '1') {
                $cur_post['user_title'] = censor_words($cur_post['user_title']);
            }
        }
        // If the poster is a guest (or a user that has been deleted)
        else {
            $cur_post['poster_disp'] = pun_htmlspecialchars($cur_post['poster']);
            $cur_post['user_title'] = $lang_topic['Guest'];
        }

        // Perform the main parsing of the message (BBCode, smilies, censor words etc)
        $cur_post['message'] = parse_message($cur_post['message'], $cur_post['hide_smilies']);
        
        $post_data[] = $cur_post;
    }
    
    return $post_data;
}

function move_topics_to($feather, $fid)
{
    global $db, $lang_common, $lang_misc, $feather_user;
    
    confirm_referrer(get_link_r('moderate/forum/'.$fid.'/'));

    if (@preg_match('%[^0-9,]%', $feather->request->post('topics'))) {
        message($lang_common['Bad request'], false, '404 Not Found');
    }

    $topics = explode(',', $feather->request->post('topics'));
    $move_to_forum = $feather->request->post('move_to_forum') ? intval($feather->request->post('move_to_forum')) : 0;
    if (empty($topics) || $move_to_forum < 1) {
        message($lang_common['Bad request'], false, '404 Not Found');
    }

    // Verify that the topic IDs are valid
    $result = $db->query('SELECT 1 FROM '.$db->prefix.'topics WHERE id IN('.implode(',', $topics).') AND forum_id='.$fid) or error('Unable to check topics', __FILE__, __LINE__, $db->error());

    if ($db->num_rows($result) != count($topics)) {
        message($lang_common['Bad request'], false, '404 Not Found');
    }

    // Verify that the move to forum ID is valid
    $result = $db->query('SELECT 1 FROM '.$db->prefix.'forums AS f LEFT JOIN '.$db->prefix.'forum_perms AS fp ON (fp.group_id='.$feather_user['g_id'].' AND fp.forum_id='.$move_to_forum.') WHERE f.redirect_url IS NULL AND (fp.post_topics IS NULL OR fp.post_topics=1)') or error('Unable to fetch forum permissions', __FILE__, __LINE__, $db->error());
    if (!$db->num_rows($result)) {
        message($lang_common['Bad request'], false, '404 Not Found');
    }

    // Delete any redirect topics if there are any (only if we moved/copied the topic back to where it was once moved from)
    $db->query('DELETE FROM '.$db->prefix.'topics WHERE forum_id='.$move_to_forum.' AND moved_to IN('.implode(',', $topics).')') or error('Unable to delete redirect topics', __FILE__, __LINE__, $db->error());

    // Move the topic(s)
    $db->query('UPDATE '.$db->prefix.'topics SET forum_id='.$move_to_forum.' WHERE id IN('.implode(',', $topics).')') or error('Unable to move topics', __FILE__, __LINE__, $db->error());

    // Should we create redirect topics?
    if ($feather->request->post('with_redirect')) {
        foreach ($topics as $cur_topic) {
            // Fetch info for the redirect topic
            $result = $db->query('SELECT poster, subject, posted, last_post FROM '.$db->prefix.'topics WHERE id='.$cur_topic) or error('Unable to fetch topic info', __FILE__, __LINE__, $db->error());
            $moved_to = $db->fetch_assoc($result);

            // Create the redirect topic
            $db->query('INSERT INTO '.$db->prefix.'topics (poster, subject, posted, last_post, moved_to, forum_id) VALUES(\''.$db->escape($moved_to['poster']).'\', \''.$db->escape($moved_to['subject']).'\', '.$moved_to['posted'].', '.$moved_to['last_post'].', '.$cur_topic.', '.$fid.')') or error('Unable to create redirect topic', __FILE__, __LINE__, $db->error());
        }
    }

    update_forum($fid); // Update the forum FROM which the topic was moved
    update_forum($move_to_forum); // Update the forum TO which the topic was moved

    $redirect_msg = (count($topics) > 1) ? $lang_misc['Move topics redirect'] : $lang_misc['Move topic redirect'];
    redirect(get_link('forum/'.$move_to_forum.'/'), $redirect_msg);
}

function check_move_possible()
{
    global $db, $feather_user, $lang_misc;
    
    $result = $db->query('SELECT c.id AS cid, c.cat_name, f.id AS fid, f.forum_name FROM '.$db->prefix.'categories AS c INNER JOIN '.$db->prefix.'forums AS f ON c.id=f.cat_id LEFT JOIN '.$db->prefix.'forum_perms AS fp ON (fp.forum_id=f.id AND fp.group_id='.$feather_user['g_id'].') WHERE (fp.post_topics IS NULL OR fp.post_topics=1) AND f.redirect_url IS NULL ORDER BY c.disp_position, c.id, f.disp_position') or error('Unable to fetch category/forum list', __FILE__, __LINE__, $db->error());
    if ($db->num_rows($result) < 2) {
        message($lang_misc['Nowhere to move']);
    }
}

function merge_topics($feather, $fid)
{
    global $db, $lang_common, $lang_misc;
    
    confirm_referrer(get_link_r('moderate/forum/'.$fid.'/'));

    if (@preg_match('%[^0-9,]%', $feather->request->post('topics'))) {
        message($lang_common['Bad request'], false, '404 Not Found');
    }

    $topics = explode(',', $feather->request->post('topics'));
    if (count($topics) < 2) {
        message($lang_misc['Not enough topics selected']);
    }

    // Verify that the topic IDs are valid (redirect links will point to the merged topic after the merge)
    $result = $db->query('SELECT id FROM '.$db->prefix.'topics WHERE id IN('.implode(',', $topics).') AND forum_id='.$fid.' ORDER BY id ASC') or error('Unable to check topics', __FILE__, __LINE__, $db->error());
    if ($db->num_rows($result) != count($topics)) {
        message($lang_common['Bad request'], false, '404 Not Found');
    }

    // The topic that we are merging into is the one with the smallest ID
    $merge_to_tid = $db->result($result);

    // Make any redirect topics point to our new, merged topic
    $query = 'UPDATE '.$db->prefix.'topics SET moved_to='.$merge_to_tid.' WHERE moved_to IN('.implode(',', $topics).')';

    // Should we create redirect topics?
    if ($feather->request->post('with_redirect')) {
        $query .= ' OR (id IN('.implode(',', $topics).') AND id != '.$merge_to_tid.')';
    }

    $db->query($query) or error('Unable to make redirection topics', __FILE__, __LINE__, $db->error());

    // Merge the posts into the topic
    $db->query('UPDATE '.$db->prefix.'posts SET topic_id='.$merge_to_tid.' WHERE topic_id IN('.implode(',', $topics).')') or error('Unable to merge the posts into the topic', __FILE__, __LINE__, $db->error());

    // Update any subscriptions
    $result = $db->query('SELECT DISTINCT user_id FROM '.$db->prefix.'topic_subscriptions WHERE topic_id IN('.implode(',', $topics).')') or error('Unable to fetch subscriptions of merged topics', __FILE__, __LINE__, $db->error());

    $subscribed_users = array();
    while ($row = $db->fetch_row($result)) {
        $subscribed_users[] = $row[0];
    }

    $db->query('DELETE FROM '.$db->prefix.'topic_subscriptions WHERE topic_id IN('.implode(',', $topics).')') or error('Unable to delete subscriptions of merged topics', __FILE__, __LINE__, $db->error());

    foreach ($subscribed_users as $cur_user_id) {
        $db->query('INSERT INTO '.$db->prefix.'topic_subscriptions (topic_id, user_id) VALUES ('.$merge_to_tid.', '.$cur_user_id.')') or error('Unable to re-enter subscriptions for merge topic', __FILE__, __LINE__, $db->error());
    }

    // Without redirection the old topics are removed
    if ($feather->request->post('with_redirect') != '') {
        $db->query('DELETE FROM '.$db->prefix.'topics WHERE id IN('.implode(',', $topics).') AND id != '.$merge_to_tid) or error('Unable to delete old topics', __FILE__, __LINE__, $db->error());
    }

    // Count number of replies in the topic
    $result = $db->query('SELECT COUNT(id) FROM '.$db->prefix.'posts WHERE topic_id='.$merge_to_tid) or error('Unable to fetch post count for topic', __FILE__, __LINE__, $db->error());
    $num_replies = $db->result($result, 0) - 1;

    // Get last_post, last_post_id and last_poster
    $result = $db->query('SELECT posted, id, poster FROM '.$db->prefix.'posts WHERE topic_id='.$merge_to_tid.' ORDER BY id DESC LIMIT 1') or error('Unable to get last post info', __FILE__, __LINE__, $db->error());
    list($last_post, $last_post_id, $last_poster) = $db->fetch_row($result);

    // Update topic
    $db->query('UPDATE '.$db->prefix.'topics SET num_replies='.$num_replies.', last_post='.$last_post.', last_post_id='.$last_post_id.', last_poster=\''.$db->escape($last_poster).'\' WHERE id='.$merge_to_tid) or error('Unable to update topic', __FILE__, __LINE__, $db->error());

    // Update the forum FROM which the topic was moved and redirect
    update_forum($fid);
    redirect(get_link('forum/'.$fid.'/'), $lang_misc['Merge topics redirect']);
}

function delete_topics($topics, $fid)
{
    global $db, $lang_misc, $lang_common, $feather_user;
    confirm_referrer(get_link_r('moderate/forum/'.$fid.'/'));

    if (@preg_match('%[^0-9,]%', $topics)) {
        message($lang_common['Bad request'], false, '404 Not Found');
    }

    require FEATHER_ROOT.'include/search_idx.php';

    // Verify that the topic IDs are valid
    $result = $db->query('SELECT 1 FROM '.$db->prefix.'topics WHERE id IN('.$topics.') AND forum_id='.$fid) or error('Unable to check topics', __FILE__, __LINE__, $db->error());

    if ($db->num_rows($result) != substr_count($topics, ',') + 1) {
        message($lang_common['Bad request'], false, '404 Not Found');
    }

    // Verify that the posts are not by admins
    if ($feather_user['g_id'] != PUN_ADMIN) {
        $result = $db->query('SELECT 1 FROM '.$db->prefix.'posts WHERE topic_id IN('.$topics.') AND poster_id IN('.implode(',', get_admin_ids()).')') or error('Unable to check posts', __FILE__, __LINE__, $db->error());
        if ($db->num_rows($result)) {
            message($lang_common['No permission'], false, '403 Forbidden');
        }
    }

    // Delete the topics and any redirect topics
    $db->query('DELETE FROM '.$db->prefix.'topics WHERE id IN('.$topics.') OR moved_to IN('.$topics.')') or error('Unable to delete topic', __FILE__, __LINE__, $db->error());

    // Delete any subscriptions
    $db->query('DELETE FROM '.$db->prefix.'topic_subscriptions WHERE topic_id IN('.$topics.')') or error('Unable to delete subscriptions', __FILE__, __LINE__, $db->error());

    // Create a list of the post IDs in this topic and then strip the search index
    $result = $db->query('SELECT id FROM '.$db->prefix.'posts WHERE topic_id IN('.$topics.')') or error('Unable to fetch posts', __FILE__, __LINE__, $db->error());

    $post_ids = '';
    while ($row = $db->fetch_row($result)) {
        $post_ids .= ($post_ids != '') ? ','.$row[0] : $row[0];
    }

    // We have to check that we actually have a list of post IDs since we could be deleting just a redirect topic
    if ($post_ids != '') {
        strip_search_index($post_ids);
    }

    // Delete posts
    $db->query('DELETE FROM '.$db->prefix.'posts WHERE topic_id IN('.$topics.')') or error('Unable to delete posts', __FILE__, __LINE__, $db->error());

    update_forum($fid);

    redirect(get_link('forum/'.$fid.'/'), $lang_misc['Delete topics redirect']);
}

function get_forum_info($fid)
{
    global $db, $feather_user, $lang_common;
    
    $result = $db->query('SELECT f.forum_name, f.redirect_url, f.num_topics, f.sort_by FROM '.$db->prefix.'forums AS f LEFT JOIN '.$db->prefix.'forum_perms AS fp ON (fp.forum_id=f.id AND fp.group_id='.$feather_user['g_id'].') WHERE (fp.read_forum IS NULL OR fp.read_forum=1) AND f.id='.$fid) or error('Unable to fetch forum info', __FILE__, __LINE__, $db->error());
    if (!$db->num_rows($result)) {
        message($lang_common['Bad request'], false, '404 Not Found');
    }

    $cur_forum = $db->fetch_assoc($result);

    return $cur_forum;
}

function forum_sort_by($forum_sort)
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

function display_topics($fid, $sort_by, $start_from)
{
    global $db, $feather_user, $feather_config, $lang_forum, $lang_common;
    
    $topic_data = array();
    
    // Get topic/forum tracking data
    if (!$feather_user['is_guest']) {
        $tracked_topics = get_tracked_topics();
    }
    
    // Retrieve a list of topic IDs, LIMIT is (really) expensive so we only fetch the IDs here then later fetch the remaining data
    $result = $db->query('SELECT id FROM '.$db->prefix.'topics WHERE forum_id='.$fid.' ORDER BY sticky DESC, '.$sort_by.', id DESC LIMIT '.$start_from.', '.$feather_user['disp_topics']) or error('Unable to fetch topic IDs', __FILE__, __LINE__, $db->error());

    // If there are topics in this forum
    if ($db->num_rows($result)) {
        $topic_ids = array();
        for ($i = 0;$cur_topic_id = $db->result($result, $i);$i++) {
            $topic_ids[] = $cur_topic_id;
        }

        // Select topics
        $result = $db->query('SELECT id, poster, subject, posted, last_post, last_post_id, last_poster, num_views, num_replies, closed, sticky, moved_to FROM '.$db->prefix.'topics WHERE id IN('.implode(',', $topic_ids).') ORDER BY sticky DESC, '.$sort_by.', id DESC') or error('Unable to fetch topic list for forum', __FILE__, __LINE__, $db->error());

        $topic_count = 0;
        while ($cur_topic = $db->fetch_assoc($result)) {
            ++$topic_count;
            $status_text = array();
            $cur_topic['item_status'] = ($topic_count % 2 == 0) ? 'roweven' : 'rowodd';
            $cur_topic['icon_type'] = 'icon';
            $url_topic = url_friendly($cur_topic['subject']);

            if (is_null($cur_topic['moved_to'])) {
                $cur_topic['last_post_disp'] = '<a href="'.get_link('post/'.$cur_topic['last_post_id'].'/#p'.$cur_topic['last_post_id']).'">'.format_time($cur_topic['last_post']).'</a> <span class="byuser">'.$lang_common['by'].' '.pun_htmlspecialchars($cur_topic['last_poster']).'</span>';
                $cur_topic['ghost_topic'] = false;
            } else {
                $cur_topic['last_post_disp'] = '- - -';
                $cur_topic['ghost_topic'] = true;
            }

            if ($feather_config['o_censoring'] == '1') {
                $cur_topic['subject'] = censor_words($cur_topic['subject']);
            }

            if ($cur_topic['sticky'] == '1') {
                $cur_topic['item_status'] .= ' isticky';
                $status_text[] = '<span class="stickytext">'.$lang_forum['Sticky'].'</span>';
            }

            if ($cur_topic['moved_to'] != 0) {
                $cur_topic['subject_disp'] = '<a href="'.get_link('topic/'.$cur_topic['moved_to'].'/'.$url_topic.'/').'">'.pun_htmlspecialchars($cur_topic['subject']).'</a> <span class="byuser">'.$lang_common['by'].' '.pun_htmlspecialchars($cur_topic['poster']).'</span>';
                $status_text[] = '<span class="movedtext">'.$lang_forum['Moved'].'</span>';
                $cur_topic['item_status'] .= ' imoved';
            } elseif ($cur_topic['closed'] == '0') {
                $cur_topic['subject_disp'] = '<a href="'.get_link('topic/'.$cur_topic['id'].'/'.$url_topic.'/').'">'.pun_htmlspecialchars($cur_topic['subject']).'</a> <span class="byuser">'.$lang_common['by'].' '.pun_htmlspecialchars($cur_topic['poster']).'</span>';
            } else {
                $cur_topic['subject_disp'] = '<a href="'.get_link('topic/'.$cur_topic['id'].'/'.$url_topic.'/').'">'.pun_htmlspecialchars($cur_topic['subject']).'</a> <span class="byuser">'.$lang_common['by'].' '.pun_htmlspecialchars($cur_topic['poster']).'</span>';
                $status_text[] = '<span class="closedtext">'.$lang_forum['Closed'].'</span>';
                $cur_topic['item_status'] .= ' iclosed';
            }

            if (!$cur_topic['ghost_topic'] && $cur_topic['last_post'] > $feather_user['last_visit'] && (!isset($tracked_topics['topics'][$cur_topic['id']]) || $tracked_topics['topics'][$cur_topic['id']] < $cur_topic['last_post']) && (!isset($tracked_topics['forums'][$fid]) || $tracked_topics['forums'][$fid] < $cur_topic['last_post'])) {
                $cur_topic['item_status'] .= ' inew';
                $cur_topic['icon_type'] = 'icon icon-new';
                $cur_topic['subject_disp'] = '<strong>'.$cur_topic['subject_disp'].'</strong>';
                $subject_new_posts = '<span class="newtext">[ <a href="'.get_link('topic/'.$cur_topic['id'].'/action/new/').'" title="'.$lang_common['New posts info'].'">'.$lang_common['New posts'].'</a> ]</span>';
            } else {
                $subject_new_posts = null;
            }

            // Insert the status text before the subject
            $cur_topic['subject_disp'] = implode(' ', $status_text).' '.$cur_topic['subject_disp'];

            $num_pages_topic = ceil(($cur_topic['num_replies'] + 1) / $feather_user['disp_posts']);

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
