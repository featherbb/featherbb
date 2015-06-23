<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */
 
function add_category($post_data)
{
    global $db, $lang_admin_categories;
    
    confirm_referrer('admin_categories.php');

    $new_cat_name = pun_trim($post_data['new_cat_name']);
    if ($new_cat_name == '') {
        message($lang_admin_categories['Must enter name message']);
    }

    $db->query('INSERT INTO '.$db->prefix.'categories (cat_name) VALUES(\''.$db->escape($new_cat_name).'\')') or error('Unable to create category', __FILE__, __LINE__, $db->error());

    redirect('admin_categories.php', $lang_admin_categories['Category added redirect']);
}

function delete_category($cat_to_delete)
{
    global $db, $lang_admin_categories;
    
    @set_time_limit(0);

    $result = $db->query('SELECT id FROM '.$db->prefix.'forums WHERE cat_id='.$cat_to_delete) or error('Unable to fetch forum list', __FILE__, __LINE__, $db->error());
    $num_forums = $db->num_rows($result);

    for ($i = 0; $i < $num_forums; ++$i) {
        $cur_forum = $db->result($result, $i);

        // Prune all posts and topics
        prune($cur_forum, 1, -1);

        // Delete the forum
        $db->query('DELETE FROM '.$db->prefix.'forums WHERE id='.$cur_forum) or error('Unable to delete forum', __FILE__, __LINE__, $db->error());
    }

    // Locate any "orphaned redirect topics" and delete them
    $result = $db->query('SELECT t1.id FROM '.$db->prefix.'topics AS t1 LEFT JOIN '.$db->prefix.'topics AS t2 ON t1.moved_to=t2.id WHERE t2.id IS NULL AND t1.moved_to IS NOT NULL') or error('Unable to fetch redirect topics', __FILE__, __LINE__, $db->error());
    $num_orphans = $db->num_rows($result);

    if ($num_orphans) {
        for ($i = 0; $i < $num_orphans; ++$i) {
            $orphans[] = $db->result($result, $i);
        }

        $db->query('DELETE FROM '.$db->prefix.'topics WHERE id IN('.implode(',', $orphans).')') or error('Unable to delete redirect topics', __FILE__, __LINE__, $db->error());
    }

    // Delete the category
    $db->query('DELETE FROM '.$db->prefix.'categories WHERE id='.$cat_to_delete) or error('Unable to delete category', __FILE__, __LINE__, $db->error());

    // Regenerate the quick jump cache
    if (!defined('FORUM_CACHE_FUNCTIONS_LOADED')) {
        require PUN_ROOT.'include/cache.php';
    }

    generate_quickjump_cache();

    redirect('admin_categories.php', $lang_admin_categories['Category deleted redirect']);
}

function get_category_name($cat_to_delete)
{
    global $db;
    
    $result = $db->query('SELECT cat_name FROM '.$db->prefix.'categories WHERE id='.$cat_to_delete) or error('Unable to fetch category info', __FILE__, __LINE__, $db->error());
    $cat_name = $db->result($result);

    return $cat_name;
}

function update_categories($categories)
{
    global $db, $lang_admin_categories;
    
    foreach ($categories as $cat_id => $cur_cat) {
        $cur_cat['name'] = pun_trim($cur_cat['name']);
        $cur_cat['order'] = pun_trim($cur_cat['order']);

        if ($cur_cat['name'] == '') {
            message($lang_admin_categories['Must enter name message']);
        }

        if ($cur_cat['order'] == '' || preg_match('%[^0-9]%', $cur_cat['order'])) {
            message($lang_admin_categories['Must enter integer message']);
        }

        $db->query('UPDATE '.$db->prefix.'categories SET cat_name=\''.$db->escape($cur_cat['name']).'\', disp_position='.$cur_cat['order'].' WHERE id='.intval($cat_id)) or error('Unable to update category', __FILE__, __LINE__, $db->error());
    }

    // Regenerate the quick jump cache
    if (!defined('FORUM_CACHE_FUNCTIONS_LOADED')) {
        require PUN_ROOT.'include/cache.php';
    }

    generate_quickjump_cache();

    redirect('admin_categories.php', $lang_admin_categories['Categories updated redirect']);
}

function get_cat_list()
{
    global $db;
    
    $cat_list = array();
    
    $result = $db->query('SELECT id, cat_name, disp_position FROM '.$db->prefix.'categories ORDER BY disp_position') or error('Unable to fetch category list', __FILE__, __LINE__, $db->error());
    $num_cats = $db->num_rows($result);

    for ($i = 0; $i < $num_cats; ++$i) {
        $cat_list[] = $db->fetch_assoc($result);
    }

    return $cat_list;
}
