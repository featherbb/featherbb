<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

// Returns page head
function get_page_head()
{
    global $feather_config, $lang_common;
    if ($feather_config['o_feed_type'] == '1') {
        $page_head = array('feed' => '<link rel="alternate" type="application/rss+xml" href="extern.php?action=feed&amp;type=rss" title="'.$lang_common['RSS active topics feed'].'" />');
    } elseif ($feather_config['o_feed_type'] == '2') {
        $page_head = array('feed' => '<link rel="alternate" type="application/atom+xml" href="extern.php?action=feed&amp;type=atom" title="'.$lang_common['Atom active topics feed'].'" />');
    }
    
    return $page_head;
}

// Returns forum action
function get_forum_actions()
{
    global $feather_user, $lang_common;
    
    $forum_actions = array();

    // Display a "mark all as read" link
    if (!$feather_user['is_guest']) {
        $forum_actions[] = '<a href="'.get_link('mark-read/').'">'.$lang_common['Mark all as read'].'</a>';
    }
    
    return $forum_actions;
}

// Detects if a "new" icon has to be displayed
function get_new_posts()
{
    global $db, $feather_user;
    
    $result = $db->query('SELECT f.id, f.last_post FROM '.$db->prefix.'forums AS f LEFT JOIN '.$db->prefix.'forum_perms AS fp ON (fp.forum_id=f.id AND fp.group_id='.$feather_user['g_id'].') WHERE (fp.read_forum IS NULL OR fp.read_forum=1) AND f.last_post>'.$feather_user['last_visit']) or error('Unable to fetch forum list', __FILE__, __LINE__, $db->error());

    if ($db->num_rows($result)) {
        $forums = $new_topics = array();
        $tracked_topics = get_tracked_topics();

        while ($cur_forum = $db->fetch_assoc($result)) {
            if (!isset($tracked_topics['forums'][$cur_forum['id']]) || $tracked_topics['forums'][$cur_forum['id']] < $cur_forum['last_post']) {
                $forums[$cur_forum['id']] = $cur_forum['last_post'];
            }
        }

        if (!empty($forums)) {
            if (empty($tracked_topics['topics'])) {
                $new_topics = $forums;
            } else {
                $result = $db->query('SELECT forum_id, id, last_post FROM '.$db->prefix.'topics WHERE forum_id IN('.implode(',', array_keys($forums)).') AND last_post>'.$feather_user['last_visit'].' AND moved_to IS NULL') or error('Unable to fetch new topics', __FILE__, __LINE__, $db->error());

                while ($cur_topic = $db->fetch_assoc($result)) {
                    if (!isset($new_topics[$cur_topic['forum_id']]) && (!isset($tracked_topics['forums'][$cur_topic['forum_id']]) || $tracked_topics['forums'][$cur_topic['forum_id']] < $forums[$cur_topic['forum_id']]) && (!isset($tracked_topics['topics'][$cur_topic['id']]) || $tracked_topics['topics'][$cur_topic['id']] < $cur_topic['last_post'])) {
                        $new_topics[$cur_topic['forum_id']] = $forums[$cur_topic['forum_id']];
                    }
                }
            }
        }
        
        return $new_topics;
    }
}

// Returns the elements needed to display categories and their forums
function print_categories_forums()
{
    global $db, $lang_common, $lang_index, $feather_user;
    
    // Get list of forums and topics with new posts since last visit
    if (!$feather_user['is_guest']) {
        $new_topics = get_new_posts();
    }
    
    $result = $db->query('SELECT c.id AS cid, c.cat_name, f.id AS fid, f.forum_name, f.forum_desc, f.redirect_url, f.moderators, f.num_topics, f.num_posts, f.last_post, f.last_post_id, f.last_poster FROM '.$db->prefix.'categories AS c INNER JOIN '.$db->prefix.'forums AS f ON c.id=f.cat_id LEFT JOIN '.$db->prefix.'forum_perms AS fp ON (fp.forum_id=f.id AND fp.group_id='.$feather_user['g_id'].') WHERE fp.read_forum IS NULL OR fp.read_forum=1 ORDER BY c.disp_position, c.id, f.disp_position', true) or error('Unable to fetch category/forum list', __FILE__, __LINE__, $db->error());
    
    $index_data = array();

    $cur_forum['cur_category'] = 0;
    $cur_forum['forum_count_formatted'] = 0;
    while ($cur_forum = $db->fetch_assoc($result)) {
        $moderators = '';
        
        if (isset($cur_forum['cur_category'])) {
            $cur_cat = $cur_forum['cur_category'];
        } else {
            $cur_cat = 0;
        }

        if ($cur_forum['cid'] != $cur_cat) {
            // A new category since last iteration?

            $cur_forum['forum_count_formatted'] = 0;
            $cur_forum['cur_category'] = $cur_forum['cid'];
        }
        
        ++$cur_forum['forum_count_formatted'];
        
        $cur_forum['item_status'] = ($cur_forum['forum_count_formatted'] % 2 == 0) ? 'roweven' : 'rowodd';
        $forum_field_new = '';
        $cur_forum['icon_type'] = 'icon';

        // Are there new posts since our last visit?
        if (isset($new_topics[$cur_forum['fid']])) {
            $cur_forum['item_status'] .= ' inew';
            $forum_field_new = '<span class="newtext">[ <a href="search.php?action=show_new&amp;fid='.$cur_forum['fid'].'">'.$lang_common['New posts'].'</a> ]</span>';
            $cur_forum['icon_type'] = 'icon icon-new';
        }

        // Is this a redirect forum?
        if ($cur_forum['redirect_url'] != '') {
            $cur_forum['forum_field'] = '<h3><span class="redirtext">'.$lang_index['Link to'].'</span> <a href="'.pun_htmlspecialchars($cur_forum['redirect_url']).'" title="'.$lang_index['Link to'].' '.pun_htmlspecialchars($cur_forum['redirect_url']).'">'.pun_htmlspecialchars($cur_forum['forum_name']).'</a></h3>';
            $cur_forum['num_topics_formatted'] = $cur_forum['num_posts_formatted'] = '-';
            $cur_forum['item_status'] .= ' iredirect';
            $cur_forum['icon_type'] = 'icon';
        } else {
            $cur_forum['forum_field'] = '<h3><a href="'.get_link('forum/'.$cur_forum['fid'].'/'.url_friendly($cur_forum['forum_name'])).'/'.'">'.pun_htmlspecialchars($cur_forum['forum_name']).'</a>'.(!empty($forum_field_new) ? ' '.$forum_field_new : '').'</h3>';
            $cur_forum['num_topics_formatted'] = $cur_forum['num_topics'];
            $cur_forum['num_posts_formatted'] = $cur_forum['num_posts'];
        }

        if ($cur_forum['forum_desc'] != '') {
            $cur_forum['forum_field'] .= "\n\t\t\t\t\t\t\t\t".'<div class="forumdesc">'.$cur_forum['forum_desc'].'</div>';
        }

        // If there is a last_post/last_poster
        if ($cur_forum['last_post'] != '') {
            $cur_forum['last_post_formatted'] = '<a href="'.get_link('post/'.$cur_forum['last_post_id'].'/#p'.$cur_forum['last_post_id']).'">'.format_time($cur_forum['last_post']).'</a> <span class="byuser">'.$lang_common['by'].' '.pun_htmlspecialchars($cur_forum['last_poster']).'</span>';
        } elseif ($cur_forum['redirect_url'] != '') {
            $cur_forum['last_post_formatted'] = '- - -';
        } else {
            $cur_forum['last_post_formatted'] = $lang_common['Never'];
        }

        if ($cur_forum['moderators'] != '') {
            $mods_array = unserialize($cur_forum['moderators']);
            $moderators = array();

            foreach ($mods_array as $mod_username => $mod_id) {
                if ($feather_user['g_view_users'] == '1') {
                    $moderators[] = '<a href="'.get_link('user/'.$mod_id.'/').'">'.pun_htmlspecialchars($mod_username).'</a>';
                } else {
                    $moderators[] = pun_htmlspecialchars($mod_username);
                }
            }
            
            $cur_forum['moderators_formatted'] = "\t\t\t\t\t\t\t\t".'<p class="modlist">(<em>'.$lang_common['Moderated by'].'</em> '.implode(', ', $moderators).')</p>'."\n";
        } else {
            $cur_forum['moderators_formatted'] = '';
        }
        
        $index_data[] = $cur_forum;
    }
        
    return $index_data;
}

// Returns the elements needed to display stats
function collect_stats()
{
    global $db, $feather_user;
    
    // Collect some statistics from the database
    if (file_exists(FORUM_CACHE_DIR.'cache_users_info.php')) {
        include FORUM_CACHE_DIR.'cache_users_info.php';
    }

    if (!defined('feather_userS_INFO_LOADED')) {
        if (!defined('FORUM_CACHE_FUNCTIONS_LOADED')) {
            require FEATHER_ROOT.'include/cache.php';
        }

        generate_users_info_cache();
        require FORUM_CACHE_DIR.'cache_users_info.php';
    }

    $result = $db->query('SELECT SUM(num_topics), SUM(num_posts) FROM '.$db->prefix.'forums') or error('Unable to fetch topic/post count', __FILE__, __LINE__, $db->error());
    list($stats['total_topics'], $stats['total_posts']) = array_map('intval', $db->fetch_row($result));

    if ($feather_user['g_view_users'] == '1') {
        $stats['newest_user'] = '<a href="'.get_link('user/'.$stats['last_user']['id']).'/">'.pun_htmlspecialchars($stats['last_user']['username']).'</a>';
    } else {
        $stats['newest_user'] = pun_htmlspecialchars($stats['last_user']['username']);
    }
    
    return $stats;
}

// Returns the elements needed to display users online
function fetch_users_online()
{
    global $db, $feather_user;
    // Fetch users online info and generate strings for output
    $num_guests = 0;
    $online = array();
    $result = $db->query('SELECT user_id, ident FROM '.$db->prefix.'online WHERE idle=0 ORDER BY ident', true) or error('Unable to fetch online list', __FILE__, __LINE__, $db->error());

    while ($feather_user_online = $db->fetch_assoc($result)) {
        if ($feather_user_online['user_id'] > 1) {
            if ($feather_user['g_view_users'] == '1') {
                $online['users'][] = "\n\t\t\t\t".'<dd><a href="'.get_link('user/'.$feather_user_online['user_id']).'/">'.pun_htmlspecialchars($feather_user_online['ident']).'</a>';
            } else {
                $online['users'][] = "\n\t\t\t\t".'<dd>'.pun_htmlspecialchars($feather_user_online['ident']);
            }
        } else {
            ++$num_guests;
        }
    }

    if (isset($online['users'])) {
        $online['num_users'] = count($online['users']);
    } else {
        $online['num_users'] = 0;
    }
    $online['num_guests'] = $num_guests;
    
    return $online;
}
