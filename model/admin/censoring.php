<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

function add_word($feather)
{
    global $db, $lang_admin_censoring;
    
    confirm_referrer(get_link_r('admin/censoring/'));

    $search_for = pun_trim($feather->request->post('new_search_for'));
    $replace_with = pun_trim($feather->request->post('new_replace_with'));

    if ($search_for == '') {
        message($lang_admin_censoring['Must enter word message']);
    }

    $db->query('INSERT INTO '.$db->prefix.'censoring (search_for, replace_with) VALUES (\''.$db->escape($search_for).'\', \''.$db->escape($replace_with).'\')') or error('Unable to add censor word', __FILE__, __LINE__, $db->error());

    // Regenerate the censoring cache
    if (!defined('FORUM_CACHE_FUNCTIONS_LOADED')) {
        require PUN_ROOT.'include/cache.php';
    }

    generate_censoring_cache();

    redirect(get_link('admin/censoring/'), $lang_admin_censoring['Word added redirect']);
}

function update_word($feather)
{
    global $db, $lang_admin_censoring;
    
    confirm_referrer(get_link_r('admin/censoring/'));

    $id = intval(key($feather->request->post('update')));

    $search_for = pun_trim($feather->request->post('search_for')[$id]);
    $replace_with = pun_trim($feather->request->post('replace_with')[$id]);

    if ($search_for == '') {
        message($lang_admin_censoring['Must enter word message']);
    }

    $db->query('UPDATE '.$db->prefix.'censoring SET search_for=\''.$db->escape($search_for).'\', replace_with=\''.$db->escape($replace_with).'\' WHERE id='.$id) or error('Unable to update censor word', __FILE__, __LINE__, $db->error());

    // Regenerate the censoring cache
    if (!defined('FORUM_CACHE_FUNCTIONS_LOADED')) {
        require PUN_ROOT.'include/cache.php';
    }

    generate_censoring_cache();

    redirect(get_link('admin/censoring/'), $lang_admin_censoring['Word updated redirect']);
}

function remove_word($feather)
{
    global $db, $lang_admin_censoring;
    
    confirm_referrer(get_link_r('admin/censoring/'));

    $id = intval(key($feather->request->post('remove')));

    $db->query('DELETE FROM '.$db->prefix.'censoring WHERE id='.$id) or error('Unable to delete censor word', __FILE__, __LINE__, $db->error());

    // Regenerate the censoring cache
    if (!defined('FORUM_CACHE_FUNCTIONS_LOADED')) {
        require PUN_ROOT.'include/cache.php';
    }

    generate_censoring_cache();

    redirect(get_link('admin/censoring/'),  $lang_admin_censoring['Word removed redirect']);
}

function get_words()
{
    global $db;
    
    $word_data = array();
    
    $result = $db->query('SELECT id, search_for, replace_with FROM '.$db->prefix.'censoring ORDER BY id') or error('Unable to fetch censor word list', __FILE__, __LINE__, $db->error());
        
    while ($cur_word = $db->fetch_assoc($result)) {
        $word_data[] = $cur_word;
    }
    
    return $word_data;
}
