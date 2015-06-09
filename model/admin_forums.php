<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */
 
function add_forum($post_data)
{
    global $db, $lang_admin_forums, $lang_common;
    
    confirm_referrer('admin_forums.php');

    $add_to_cat = intval($post_data['add_to_cat']);
    if ($add_to_cat < 1) {
        message($lang_common['Bad request'], false, '404 Not Found');
    }

    $db->query('INSERT INTO '.$db->prefix.'forums (forum_name, cat_id) VALUES(\''.$db->escape($lang_admin_forums['New forum']).'\', '.$add_to_cat.')') or error('Unable to create forum', __FILE__, __LINE__, $db->error());
    $new_fid = $db->insert_id();

    // Regenerate the quick jump cache
    if (!defined('FORUM_CACHE_FUNCTIONS_LOADED')) {
        require PUN_ROOT.'include/cache.php';
    }

    generate_quickjump_cache();

    redirect('admin_forums.php?edit_forum='.$new_fid, $lang_admin_forums['Forum added redirect']);
}

function delete_forum($post_data, $forum_id)
{
    global $db, $lang_admin_forums;
    
    @set_time_limit(0);

    // Prune all posts and topics
    prune($forum_id, 1, -1);

    // Locate any "orphaned redirect topics" and delete them
    $result = $db->query('SELECT t1.id FROM '.$db->prefix.'topics AS t1 LEFT JOIN '.$db->prefix.'topics AS t2 ON t1.moved_to=t2.id WHERE t2.id IS NULL AND t1.moved_to IS NOT NULL') or error('Unable to fetch redirect topics', __FILE__, __LINE__, $db->error());
    $num_orphans = $db->num_rows($result);

    if ($num_orphans) {
        for ($i = 0; $i < $num_orphans; ++$i) {
            $orphans[] = $db->result($result, $i);
        }

        $db->query('DELETE FROM '.$db->prefix.'topics WHERE id IN('.implode(',', $orphans).')') or error('Unable to delete redirect topics', __FILE__, __LINE__, $db->error());
    }

    // Delete the forum and any forum specific group permissions
    $db->query('DELETE FROM '.$db->prefix.'forums WHERE id='.$forum_id) or error('Unable to delete forum', __FILE__, __LINE__, $db->error());
    $db->query('DELETE FROM '.$db->prefix.'forum_perms WHERE forum_id='.$forum_id) or error('Unable to delete group forum permissions', __FILE__, __LINE__, $db->error());

    // Delete any subscriptions for this forum
    $db->query('DELETE FROM '.$db->prefix.'forum_subscriptions WHERE forum_id='.$forum_id) or error('Unable to delete subscriptions', __FILE__, __LINE__, $db->error());

    // Regenerate the quick jump cache
    if (!defined('FORUM_CACHE_FUNCTIONS_LOADED')) {
        require PUN_ROOT.'include/cache.php';
    }

    generate_quickjump_cache();

    redirect('admin_forums.php', $lang_admin_forums['Forum deleted redirect']);
}

function get_forum_name($forum_id)
{
    global $db;
    
    $result = $db->query('SELECT forum_name FROM '.$db->prefix.'forums WHERE id='.$forum_id) or error('Unable to fetch forum info', __FILE__, __LINE__, $db->error());
    $forum_name = pun_htmlspecialchars($db->result($result));
    
    return $forum_name;
}

function update_positions($post_data)
{
    global $db, $lang_admin_forums;
    
    confirm_referrer('admin_forums.php');

    foreach ($post_data['position'] as $forum_id => $disp_position) {
        $disp_position = trim($disp_position);
        if ($disp_position == '' || preg_match('%[^0-9]%', $disp_position)) {
            message($lang_admin_forums['Must be integer message']);
        }

        $db->query('UPDATE '.$db->prefix.'forums SET disp_position='.$disp_position.' WHERE id='.intval($forum_id)) or error('Unable to update forum', __FILE__, __LINE__, $db->error());
    }

    // Regenerate the quick jump cache
    if (!defined('FORUM_CACHE_FUNCTIONS_LOADED')) {
        require PUN_ROOT.'include/cache.php';
    }

    generate_quickjump_cache();

    redirect('admin_forums.php', $lang_admin_forums['Forums updated redirect']);
}

function update_permissions($post_data, $forum_id)
{
    global $db, $lang_admin_forums, $lang_common;
    
    confirm_referrer('admin_forums.php');

    // Start with the forum details
    $forum_name = pun_trim($post_data['forum_name']);
    $forum_desc = pun_linebreaks(pun_trim($post_data['forum_desc']));
    $cat_id = intval($post_data['cat_id']);
    $sort_by = intval($post_data['sort_by']);
    $redirect_url = isset($post_data['redirect_url']) ? pun_trim($post_data['redirect_url']) : null;

    if ($forum_name == '') {
        message($lang_admin_forums['Must enter name message']);
    }

    if ($cat_id < 1) {
        message($lang_common['Bad request'], false, '404 Not Found');
    }

    $forum_desc = ($forum_desc != '') ? '\''.$db->escape($forum_desc).'\'' : 'NULL';
    $redirect_url = ($redirect_url != '') ? '\''.$db->escape($redirect_url).'\'' : 'NULL';

    $db->query('UPDATE '.$db->prefix.'forums SET forum_name=\''.$db->escape($forum_name).'\', forum_desc='.$forum_desc.', redirect_url='.$redirect_url.', sort_by='.$sort_by.', cat_id='.$cat_id.' WHERE id='.$forum_id) or error('Unable to update forum', __FILE__, __LINE__, $db->error());

    // Now let's deal with the permissions
    if (isset($post_data['read_forum_old'])) {
        $result = $db->query('SELECT g_id, g_read_board, g_post_replies, g_post_topics FROM '.$db->prefix.'groups WHERE g_id!='.PUN_ADMIN) or error('Unable to fetch user group list', __FILE__, __LINE__, $db->error());
        while ($cur_group = $db->fetch_assoc($result)) {
            $read_forum_new = ($cur_group['g_read_board'] == '1') ? isset($post_data['read_forum_new'][$cur_group['g_id']]) ? '1' : '0' : intval($post_data['read_forum_old'][$cur_group['g_id']]);
            $post_replies_new = isset($post_data['post_replies_new'][$cur_group['g_id']]) ? '1' : '0';
            $post_topics_new = isset($post_data['post_topics_new'][$cur_group['g_id']]) ? '1' : '0';

            // Check if the new settings differ from the old
            if ($read_forum_new != $post_data['read_forum_old'][$cur_group['g_id']] || $post_replies_new != $post_data['post_replies_old'][$cur_group['g_id']] || $post_topics_new != $post_data['post_topics_old'][$cur_group['g_id']]) {
                // If the new settings are identical to the default settings for this group, delete its row in forum_perms
                if ($read_forum_new == '1' && $post_replies_new == $cur_group['g_post_replies'] && $post_topics_new == $cur_group['g_post_topics']) {
                    $db->query('DELETE FROM '.$db->prefix.'forum_perms WHERE group_id='.$cur_group['g_id'].' AND forum_id='.$forum_id) or error('Unable to delete group forum permissions', __FILE__, __LINE__, $db->error());
                } else {
                    // Run an UPDATE and see if it affected a row, if not, INSERT
                    $db->query('UPDATE '.$db->prefix.'forum_perms SET read_forum='.$read_forum_new.', post_replies='.$post_replies_new.', post_topics='.$post_topics_new.' WHERE group_id='.$cur_group['g_id'].' AND forum_id='.$forum_id) or error('Unable to insert group forum permissions', __FILE__, __LINE__, $db->error());
                    if (!$db->affected_rows()) {
                        $db->query('INSERT INTO '.$db->prefix.'forum_perms (group_id, forum_id, read_forum, post_replies, post_topics) VALUES('.$cur_group['g_id'].', '.$forum_id.', '.$read_forum_new.', '.$post_replies_new.', '.$post_topics_new.')') or error('Unable to insert group forum permissions', __FILE__, __LINE__, $db->error());
                    }
                }
            }
        }
    }

    // Regenerate the quick jump cache
    if (!defined('FORUM_CACHE_FUNCTIONS_LOADED')) {
        require PUN_ROOT.'include/cache.php';
    }

    generate_quickjump_cache();

    redirect('admin_forums.php', $lang_admin_forums['Forum updated redirect']);
}

function revert_permissions($forum_id)
{
    global $db, $lang_admin_forums;
    
    confirm_referrer('admin_forums.php');

    $db->query('DELETE FROM '.$db->prefix.'forum_perms WHERE forum_id='.$forum_id) or error('Unable to delete group forum permissions', __FILE__, __LINE__, $db->error());

    // Regenerate the quick jump cache
    if (!defined('FORUM_CACHE_FUNCTIONS_LOADED')) {
        require PUN_ROOT.'include/cache.php';
    }

    generate_quickjump_cache();

    redirect('admin_forums.php?edit_forum='.$forum_id, $lang_admin_forums['Perms reverted redirect']);
}

function get_forum_info($forum_id)
{
    global $db, $lang_common;
    
    $result = $db->query('SELECT id, forum_name, forum_desc, redirect_url, num_topics, sort_by, cat_id FROM '.$db->prefix.'forums WHERE id='.$forum_id) or error('Unable to fetch forum info', __FILE__, __LINE__, $db->error());
    if (!$db->num_rows($result)) {
        message($lang_common['Bad request'], false, '404 Not Found');
    }
    
    $cur_forum = $db->fetch_assoc($result);
    
    return $cur_forum;
}

function get_categories($cur_forum)
{
    global $db;
    
    $result = $db->query('SELECT id, cat_name FROM '.$db->prefix.'categories ORDER BY disp_position') or error('Unable to fetch category list', __FILE__, __LINE__, $db->error());
    while ($cur_cat = $db->fetch_assoc($result)) {
        $selected = ($cur_cat['id'] == $cur_forum['cat_id']) ? ' selected="selected"' : '';
        echo "\t\t\t\t\t\t\t\t\t\t\t".'<option value="'.$cur_cat['id'].'"'.$selected.'>'.pun_htmlspecialchars($cur_cat['cat_name']).'</option>'."\n";
    }
}

function get_categories_add()
{
    global $db;
    
    $result = $db->query('SELECT id, cat_name FROM '.$db->prefix.'categories ORDER BY disp_position') or error('Unable to fetch category list', __FILE__, __LINE__, $db->error());
    
    while ($cur_cat = $db->fetch_assoc($result)) {
        echo "\t\t\t\t\t\t\t\t\t\t\t".'<option value="'.$cur_cat['id'].'">'.pun_htmlspecialchars($cur_cat['cat_name']).'</option>'."\n";
    }
}

function get_permissions($forum_id)
{
    global $db;
    
    $perm_data = array();
    
    $result = $db->query('SELECT g.g_id, g.g_title, g.g_read_board, g.g_post_replies, g.g_post_topics, fp.read_forum, fp.post_replies, fp.post_topics FROM '.$db->prefix.'groups AS g LEFT JOIN '.$db->prefix.'forum_perms AS fp ON (g.g_id=fp.group_id AND fp.forum_id='.$forum_id.') WHERE g.g_id!='.PUN_ADMIN.' ORDER BY g.g_id') or error('Unable to fetch group forum permission list', __FILE__, __LINE__, $db->error());

    while ($cur_perm = $db->fetch_assoc($result)) {
        $cur_perm['read_forum'] = ($cur_perm['read_forum'] != '0') ? true : false;
        $cur_perm['post_replies'] = (($cur_perm['g_post_replies'] == '0' && $cur_perm['post_replies'] == '1') || ($cur_perm['g_post_replies'] == '1' && $cur_perm['post_replies'] != '0')) ? true : false;
        $cur_perm['post_topics'] = (($cur_perm['g_post_topics'] == '0' && $cur_perm['post_topics'] == '1') || ($cur_perm['g_post_topics'] == '1' && $cur_perm['post_topics'] != '0')) ? true : false;

        // Determine if the current settings differ from the default or not
        $cur_perm['read_forum_def'] = ($cur_perm['read_forum'] == '0') ? false : true;
        $cur_perm['post_replies_def'] = (($cur_perm['post_replies'] && $cur_perm['g_post_replies'] == '0') || (!$cur_perm['post_replies'] && ($cur_perm['g_post_replies'] == '' || $cur_perm['g_post_replies'] == '1'))) ? false : true;
        $cur_perm['post_topics_def'] = (($cur_perm['post_topics'] && $cur_perm['g_post_topics'] == '0') || ($cur_perm['post_topics'] && ($cur_perm['g_post_topics'] == '' || $cur_perm['g_post_topics'] == '1'))) ? false : true;
        
        $perm_data[] = $cur_perm;
    }
    
    return $perm_data;
}

function check_forums()
{
    global $db;
    
    $result = $db->query('SELECT id, cat_name FROM '.$db->prefix.'categories ORDER BY disp_position') or error('Unable to fetch category list', __FILE__, __LINE__, $db->error());
    if ($db->num_rows($result)) {
        $is_forum = true;
    } else {
        $is_forum = false;
    }
    
    return $is_forum;
}

function check_categories()
{
    global $db;
    
    $result = $db->query('SELECT c.id AS cid, c.cat_name, f.id AS fid, f.forum_name, f.disp_position FROM '.$db->prefix.'categories AS c INNER JOIN '.$db->prefix.'forums AS f ON c.id=f.cat_id ORDER BY c.disp_position, c.id, f.disp_position') or error('Unable to fetch category/forum list', __FILE__, __LINE__, $db->error());
    if ($db->num_rows($result)) {
        $is_category = true;
    } else {
        $is_category = false;
    }
    
    return $is_category;
}

function get_forums()
{
    global $db;
    
    $forum_data = array();
    
    $result = $db->query('SELECT c.id AS cid, c.cat_name, f.id AS fid, f.forum_name, f.disp_position FROM '.$db->prefix.'categories AS c INNER JOIN '.$db->prefix.'forums AS f ON c.id=f.cat_id ORDER BY c.disp_position, c.id, f.disp_position') or error('Unable to fetch category/forum list', __FILE__, __LINE__, $db->error());
    
    while ($cur_forum = $db->fetch_assoc($result)) {
        $forum_data[] = $cur_forum;
    }
    
    return $forum_data;
}
