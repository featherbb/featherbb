<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */
 
function update_permissions($post_data)
{
	global $db, $lang_admin_permissions, $pun_config;
	
    confirm_referrer('admin_permissions.php');

    $form = array_map('intval', $post_data['form']);

    foreach ($form as $key => $input) {
        // Make sure the input is never a negative value
        if ($input < 0) {
            $input = 0;
        }

        // Only update values that have changed
        if (array_key_exists('p_'.$key, $pun_config) && $pun_config['p_'.$key] != $input) {
            $db->query('UPDATE '.$db->prefix.'config SET conf_value='.$input.' WHERE conf_name=\'p_'.$db->escape($key).'\'') or error('Unable to update board config', __FILE__, __LINE__, $db->error());
        }
    }

    // Regenerate the config cache
    if (!defined('FORUM_CACHE_FUNCTIONS_LOADED')) {
        require PUN_ROOT.'include/cache.php';
    }

    generate_config_cache();

    redirect('admin_permissions.php', $lang_admin_permissions['Perms updated redirect']);
}