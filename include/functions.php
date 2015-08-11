<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */



//
// Return current timestamp (with microseconds) as a float
//
function get_microtime()
{
    list($usec, $sec) = explode(' ', microtime());
    return ((float)$usec + (float)$sec);
}

//
// Cookie stuff!
//
function check_cookie()
{
    global $cookie_name, $cookie_seed;

    // Get Slim current session
    $feather = \Slim\Slim::getInstance();
    
    $now = time();

    // Get FeatherBB cookie
    $cookie_raw = $feather->getCookie($cookie_name);

    // Check if cookie exists and is valid (getCookie method returns false if the data has been tampered locally so it can't decrypt the cookie);
    if (isset($cookie_raw)) {
        $cookie = json_decode($cookie_raw, true);
        $checksum = hash_hmac('sha1', $cookie['user_id'].$cookie['expires'], $cookie_seed . '_checksum');

        // If cookie has a non-guest user, hasn't expired and is legit
        if ($cookie['user_id'] > 1 && $cookie['expires'] > $now && $checksum == $cookie['checksum']) {

            // Get user info from db
            $select_check_cookie = array('u.*', 'g.*', 'o.logged', 'o.idle');
            $where_check_cookie = array('u.id' => intval($cookie['user_id']));

            $result = \DB::for_table('users')
                ->table_alias('u')
                ->select_many($select_check_cookie)
                ->inner_join('groups', array('u.group_id', '=', 'g.g_id'), 'g')
                ->left_outer_join('online', array('o.user_id', '=', 'u.id'), 'o')
                ->where($where_check_cookie)
                ->find_result_set();

            foreach ($result as $feather->user);

            // Another security check, to prevent identity fraud by changing the user id in the cookie) (might be useless considering the strength of encryption)
            if (isset($feather->user->id) && hash_hmac('sha1', $feather->user->password, $cookie_seed.'_password_hash') === $cookie['password_hash']) {
                $expires = ($cookie['expires'] > $now + $feather->config['o_timeout_visit']) ? $now + 1209600 : $now + $feather->config['o_timeout_visit'];
                $feather->user->is_guest = false;
                $feather->user->is_admmod = $feather->user->g_id == FEATHER_ADMIN || $feather->user->g_moderator == '1';
                feather_setcookie($feather->user->id, $feather->user->password, $expires);
                set_preferences();
                return true;
            }
        }
    }
    // If there is no cookie, or cookie is guest or expired, let's reconnect.
    $expires = $now + 31536000; // The cookie expires after a year
    feather_setcookie(1, feather_hash(uniqid(rand(), true)), $expires);
    return set_default_user();
}

//
// Set preferences
//
function set_preferences()
{
    global $db_type, $cookie_name;

    // Get Slim current session
    $feather = \Slim\Slim::getInstance();
    $now = time();

    // Set a default language if the user selected language no longer exists
    if (!file_exists(FEATHER_ROOT.'lang/'.$feather->user->language)) {
        $feather->user->language = $feather->config['o_default_lang'];
    }

    // Set a default style if the user selected style no longer exists
    if (!file_exists(FEATHER_ROOT.'style/'.$feather->user->style.'.css')) {
        $feather->user->style = $feather->config['o_default_style'];
    }

    if (!$feather->user->disp_topics) {
        $feather->user->disp_topics = $feather->config['o_disp_topics_default'];
    }
    if (!$feather->user->disp_posts) {
        $feather->user->disp_posts = $feather->config['o_disp_posts_default'];
    }

    // Define this if you want this visit to affect the online list and the users last visit data
    if (!defined('FEATHER_QUIET_VISIT')) {
        // Update the online list
        if (!$feather->user->logged) {
            $feather->user->logged = $now;

            // With MySQL/MySQLi/SQLite, REPLACE INTO avoids a user having two rows in the online table
            switch ($db_type) {
                case 'mysql':
                case 'mysqli':
                case 'mysql_innodb':
                case 'mysqli_innodb':
                case 'sqlite':
                case 'sqlite3':
                    \DB::for_table('online')->raw_execute('REPLACE INTO '.$feather->prefix.'online (user_id, ident, logged) VALUES(:user_id, :ident, :logged)', array(':user_id' => $feather->user->id, ':ident' => $feather->user->username, ':logged' => $feather->user->logged));
                    break;

                default:
                    \DB::for_table('online')->raw_execute('INSERT INTO '.$feather->prefix.'online (user_id, ident, logged) SELECT :user_id, :ident, :logged WHERE NOT EXISTS (SELECT 1 FROM '.$feather->prefix.'online WHERE user_id=:user_id)', array(':user_id' => $feather->user->id, ':ident' => $feather->user->username, ':logged' => $feather->user->logged));
                    break;
            }

            // Reset tracked topics
            set_tracked_topics(null);

        } else {
            // Special case: We've timed out, but no other user has browsed the forums since we timed out
            if ($feather->user->logged < ($now-$feather->config['o_timeout_visit'])) {
                \DB::for_table('users')->where('id', $feather->user->id)
                    ->find_one()
                    ->set('last_visit', $feather->user->logged)
                    ->save();
                $feather->user->last_visit = $feather->user->logged;
            }

            $idle_sql = ($feather->user->idle == '1') ? ', idle=0' : '';

            \DB::for_table('online')->raw_execute('UPDATE '.$feather->prefix.'online SET logged='.$now.$idle_sql.' WHERE user_id=:user_id', array(':user_id' => $feather->user->id));

            // Update tracked topics with the current expire time
            $cookie_tracked_topics = $feather->getCookie($cookie_name.'_track');
            if (isset($cookie_tracked_topics)) {
                set_tracked_topics(json_decode($cookie_tracked_topics, true));
            }
        }
    } else {
        if (!$feather->user->logged) {
            $feather->user->logged = $feather->user->last_visit;
        }
    }
}


//
// Try to determine the current URL
//
function get_current_url($max_length = 0)
{
    $protocol = get_current_protocol();
    $port = (isset($_SERVER['SERVER_PORT']) && (($_SERVER['SERVER_PORT'] != '80' && $protocol == 'http') || ($_SERVER['SERVER_PORT'] != '443' && $protocol == 'https')) && strpos($_SERVER['HTTP_HOST'], ':') === false) ? ':'.$_SERVER['SERVER_PORT'] : '';

    $url = urldecode($protocol.'://'.$_SERVER['HTTP_HOST'].$port.$_SERVER['REQUEST_URI']);

    if (strlen($url) <= $max_length || $max_length == 0) {
        return $url;
    }

    // We can't find a short enough url
    return null;
}


//
// Fetch the current protocol in use - http or https
//
function get_current_protocol()
{
    $protocol = 'http';

    // Check if the server is claiming to using HTTPS
    if (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) != 'off') {
        $protocol = 'https';
    }

    // If we are behind a reverse proxy try to decide which protocol it is using
    if (defined('FORUM_BEHIND_REVERSE_PROXY')) {
        // Check if we are behind a Microsoft based reverse proxy
        if (!empty($_SERVER['HTTP_FRONT_END_HTTPS']) && strtolower($_SERVER['HTTP_FRONT_END_HTTPS']) != 'off') {
            $protocol = 'https';
        }

        // Check if we're behind a "proper" reverse proxy, and what protocol it's using
        if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
            $protocol = strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']);
        }
    }

    return $protocol;
}


//
// Fetch the base_url, optionally support HTTPS and HTTP
//
function get_base_url($support_https = false)
{
    global $feather_config;
    static $base_url;

    if (!$support_https) {
        return $feather_config['o_base_url'];
    }

    if (!isset($base_url)) {
        // Make sure we are using the correct protocol
        $base_url = str_replace(array('http://', 'https://'), get_current_protocol().'://', $feather_config['o_base_url']);
    }

    return $base_url;
}


//
// Fetch admin IDs
//
function get_admin_ids()
{
    if (file_exists(FORUM_CACHE_DIR.'cache_admins.php')) {
        include FORUM_CACHE_DIR.'cache_admins.php';
    }

    if (!defined('FEATHER_ADMINS_LOADED')) {
        if (!defined('FORUM_CACHE_FUNCTIONS_LOADED')) {
            require FEATHER_ROOT.'include/cache.php';
        }

        generate_admins_cache();
        require FORUM_CACHE_DIR.'cache_admins.php';
    }

    return $feather_admins;
}


//
// Fill $feather->user with default values (for guests)
//
function set_default_user()
{
    global $db_type, $feather_config;

    // Get Slim current session
    $feather = \Slim\Slim::getInstance();

    $remote_addr = get_remote_address();

    // Fetch guest user
    $select_set_default_user = array('u.*', 'g.*', 'o.logged', 'o.last_post', 'o.last_search');
    $where_set_default_user = array('u.id' => '1');

    $result = \DB::for_table('users')
        ->table_alias('u')
        ->select_many($select_set_default_user)
        ->inner_join('groups', array('u.group_id', '=', 'g.g_id'), 'g')
        ->left_outer_join('online', array('o.ident', '=', $remote_addr), 'o', true)
        ->where($where_set_default_user)
        ->find_result_set();

    if (!$result) {
        exit('Unable to fetch guest information. Your database must contain both a guest user and a guest user group.');
    }

    foreach ($result as $feather->user);

    // Update online list
    if (!$feather->user->logged) {
        $feather->user->logged = time();

        // With MySQL/MySQLi/SQLite, REPLACE INTO avoids a user having two rows in the online table
        switch ($db_type) {
            case 'mysql':
            case 'mysqli':
            case 'mysql_innodb':
            case 'mysqli_innodb':
            case 'sqlite':
            case 'sqlite3':
            \DB::for_table('online')->raw_execute('REPLACE INTO '.$feather->prefix.'online (user_id, ident, logged) VALUES(1, :ident, :logged)', array(':ident' => $remote_addr, ':logged' => $feather->user->logged));
                break;

            default:
                \DB::for_table('online')->raw_execute('INSERT INTO '.$feather->prefix.'online (user_id, ident, logged) SELECT 1, :ident, :logged WHERE NOT EXISTS (SELECT 1 FROM '.$feather->prefix.'online WHERE ident=:ident)', array(':ident' => $remote_addr, ':logged' => $feather->user->logged));
                break;
        }
    } else {
        \DB::for_table('online')->where('ident', $remote_addr)
             ->update_many('logged', time());
    }

    $feather->user->disp_topics = $feather_config['o_disp_topics_default'];
    $feather->user->disp_posts = $feather_config['o_disp_posts_default'];
    $feather->user->timezone = $feather_config['o_default_timezone'];
    $feather->user->dst = $feather_config['o_default_dst'];
    $feather->user->language = $feather_config['o_default_lang'];
    $feather->user->style = $feather_config['o_default_style'];
    $feather->user->is_guest = true;
    $feather->user->is_admmod = false;
}


//
// Wrapper for Slim setCookie method
//
function feather_setcookie($user_id, $password, $expires)
{
    global $cookie_name, $cookie_seed;

    // Get Slim current session
    $feather = \Slim\Slim::getInstance();
    $cookie_data = array('user_id' => $user_id,
        'password_hash' => hash_hmac('sha1', $password, $cookie_seed.'_password_hash'),
        'expires' => $expires,
        'checksum' => hash_hmac('sha1', $user_id.$expires, $cookie_seed.'_checksum'));
    $feather->setCookie($cookie_name, json_encode($cookie_data), $expires);
}


//
// Check whether the connecting user is banned (and delete any expired bans while we're at it)
//
function check_bans()
{
    global $feather_config, $lang_common, $feather_bans;

    // Get Slim current session
    $feather = \Slim\Slim::getInstance();

    // Admins and moderators aren't affected
    if ($feather->user->is_admmod || !$feather_bans) {
        return;
    }

    // Add a dot or a colon (depending on IPv4/IPv6) at the end of the IP address to prevent banned address
    // 192.168.0.5 from matching e.g. 192.168.0.50
    $user_ip = get_remote_address();
    $user_ip .= (strpos($user_ip, '.') !== false) ? '.' : ':';

    $bans_altered = false;
    $is_banned = false;

    foreach ($feather_bans as $cur_ban) {
        // Has this ban expired?
        if ($cur_ban['expire'] != '' && $cur_ban['expire'] <= time()) {
            \DB::for_table('bans')->where('id', $cur_ban['id'])
                                               ->delete_many();
            $bans_altered = true;
            continue;
        }

        if ($cur_ban['username'] != '' && utf8_strtolower($feather->user->username) == utf8_strtolower($cur_ban['username'])) {
            $is_banned = true;
        }

        if ($cur_ban['ip'] != '') {
            $cur_ban_ips = explode(' ', $cur_ban['ip']);

            $num_ips = count($cur_ban_ips);
            for ($i = 0; $i < $num_ips; ++$i) {
                // Add the proper ending to the ban
                if (strpos($user_ip, '.') !== false) {
                    $cur_ban_ips[$i] = $cur_ban_ips[$i].'.';
                } else {
                    $cur_ban_ips[$i] = $cur_ban_ips[$i].':';
                }

                if (substr($user_ip, 0, strlen($cur_ban_ips[$i])) == $cur_ban_ips[$i]) {
                    $is_banned = true;
                    break;
                }
            }
        }

        if ($is_banned) {
            \DB::for_table('online')->where('ident', $feather->user->username)
                                                 ->delete_many();
            message($lang_common['Ban message'].' '.(($cur_ban['expire'] != '') ? $lang_common['Ban message 2'].' '.strtolower(format_time($cur_ban['expire'], true)).'. ' : '').(($cur_ban['message'] != '') ? $lang_common['Ban message 3'].'<br /><br /><strong>'.feather_escape($cur_ban['message']).'</strong><br /><br />' : '<br /><br />').$lang_common['Ban message 4'].' <a href="mailto:'.feather_escape($feather_config['o_admin_email']).'">'.feather_escape($feather_config['o_admin_email']).'</a>.', true, true, true);
        }
    }

    // If we removed any expired bans during our run-through, we need to regenerate the bans cache
    if ($bans_altered) {
        if (!defined('FORUM_CACHE_FUNCTIONS_LOADED')) {
            require FEATHER_ROOT.'include/cache.php';
        }

        generate_bans_cache();
    }
}


//
// Check username
//
function check_username($username, $errors, $exclude_id = null)
{
    global $feather, $feather_config, $errors, $lang_prof_reg, $lang_register, $lang_common, $feather_bans;

    // Include UTF-8 function
    require_once FEATHER_ROOT.'include/utf8/strcasecmp.php';

    // Convert multiple whitespace characters into one (to prevent people from registering with indistinguishable usernames)
    $username = preg_replace('%\s+%s', ' ', $username);

    // Validate username
    if (feather_strlen($username) < 2) {
        $errors[] = $lang_prof_reg['Username too short'];
    } elseif (feather_strlen($username) > 25) { // This usually doesn't happen since the form element only accepts 25 characters
        $errors[] = $lang_prof_reg['Username too long'];
    } elseif (!strcasecmp($username, 'Guest') || !utf8_strcasecmp($username, $lang_common['Guest'])) {
        $errors[] = $lang_prof_reg['Username guest'];
    } elseif (preg_match('%[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}%', $username) || preg_match('%((([0-9A-Fa-f]{1,4}:){7}[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){6}:[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){5}:([0-9A-Fa-f]{1,4}:)?[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){4}:([0-9A-Fa-f]{1,4}:){0,2}[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){3}:([0-9A-Fa-f]{1,4}:){0,3}[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){2}:([0-9A-Fa-f]{1,4}:){0,4}[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){6}((\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b)\.){3}(\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b))|(([0-9A-Fa-f]{1,4}:){0,5}:((\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b)\.){3}(\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b))|(::([0-9A-Fa-f]{1,4}:){0,5}((\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b)\.){3}(\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b))|([0-9A-Fa-f]{1,4}::([0-9A-Fa-f]{1,4}:){0,5}[0-9A-Fa-f]{1,4})|(::([0-9A-Fa-f]{1,4}:){0,6}[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){1,7}:))%', $username)) {
        $errors[] = $lang_prof_reg['Username IP'];
    } elseif ((strpos($username, '[') !== false || strpos($username, ']') !== false) && strpos($username, '\'') !== false && strpos($username, '"') !== false) {
        $errors[] = $lang_prof_reg['Username reserved chars'];
    } elseif (preg_match('%(?:\[/?(?:b|u|s|ins|del|em|i|h|colou?r|quote|code|img|url|email|list|\*|topic|post|forum|user)\]|\[(?:img|url|quote|list)=)%i', $username)) {
        $errors[] = $lang_prof_reg['Username BBCode'];
    }

    // Check username for any censored words
    if ($feather_config['o_censoring'] == '1' && censor_words($username) != $username) {
        $errors[] = $lang_register['Username censor'];
    }

    // Check that the username (or a too similar username) is not already registered
    $query = (!is_null($exclude_id)) ? ' AND id!='.$exclude_id : '';

    $result = \DB::for_table('online')->raw_query('SELECT username FROM '.$feather->prefix.'users WHERE (UPPER(username)=UPPER(:username1) OR UPPER(username)=UPPER(:username2)) AND id>1'.$query, array(':username1' => $username, ':username2' => ucp_preg_replace('%[^\p{L}\p{N}]%u', '', $username)))->find_one();

    if ($result) {
        $busy = $result['username'];
        $errors[] = $lang_register['Username dupe 1'].' '.feather_escape($busy).'. '.$lang_register['Username dupe 2'];
    }

    // Check username for any banned usernames
    foreach ($feather_bans as $cur_ban) {
        if ($cur_ban['username'] != '' && utf8_strtolower($username) == utf8_strtolower($cur_ban['username'])) {
            $errors[] = $lang_prof_reg['Banned username'];
            break;
        }
    }

    return $errors;
}


//
// Update "Users online"
//
function update_users_online()
{
    global $feather, $feather_config;

    $now = time();

    // Fetch all online list entries that are older than "o_timeout_online"
    $select_update_users_online = array('user_id', 'ident', 'logged', 'idle');

    $result = \DB::for_table('online')->select_many($select_update_users_online)
        ->where_lt('logged', $now-$feather_config['o_timeout_online'])
        ->find_many();

    foreach ($result as $cur_user) {
        // If the entry is a guest, delete it
        if ($cur_user['user_id'] == '1') {
            \DB::for_table('online')->where('ident', $cur_user['ident'])
                                                 ->delete_many();
        } else {
            // If the entry is older than "o_timeout_visit", update last_visit for the user in question, then delete him/her from the online list
            if ($cur_user['logged'] < ($now-$feather_config['o_timeout_visit'])) {
                \DB::for_table('users')->where('id', $cur_user['user_id'])
                        ->find_one()
                        ->set('last_visit', $cur_user['logged'])
                        ->save();
                \DB::for_table('online')->where('user_id', $cur_user['user_id'])
                    ->delete_many();
            } elseif ($cur_user['idle'] == '0') {
                \DB::for_table('online')->where('user_id', $cur_user['user_id'])
                        ->update_many('idle', 1);
            }
        }
    }
}


//
// Outputs markup to display a user's avatar
//
function generate_avatar_markup($user_id)
{
    global $feather_config;

    $filetypes = array('jpg', 'gif', 'png');
    $avatar_markup = '';

    foreach ($filetypes as $cur_type) {
        $path = $feather_config['o_avatars_dir'].'/'.$user_id.'.'.$cur_type;

        if (file_exists(FEATHER_ROOT.$path) && $img_size = getimagesize(FEATHER_ROOT.$path)) {
            $avatar_markup = '<img src="'.feather_escape(get_base_url(true).'/'.$path.'?m='.filemtime(FEATHER_ROOT.$path)).'" '.$img_size[3].' alt="" />';
            break;
        }
    }

    return $avatar_markup;
}


//
// Generate browser's title
//
function generate_page_title($page_title, $p = null)
{
    global $lang_common;

    if (!is_array($page_title)) {
        $page_title = array($page_title);
    }

    $page_title = array_reverse($page_title);

    if ($p > 1) {
        $page_title[0] .= ' ('.sprintf($lang_common['Page'], forum_number_format($p)).')';
    }

    $crumbs = implode($lang_common['Title separator'], $page_title);

    return $crumbs;
}


//
// Save array of tracked topics in cookie
//
function set_tracked_topics($tracked_topics = null)
{
    global $cookie_name;

    // Get Slim current session
    $feather = \Slim\Slim::getInstance();

    if (!empty($tracked_topics)) {
        // Sort the arrays (latest read first)
        arsort($tracked_topics['topics'], SORT_NUMERIC);
        arsort($tracked_topics['forums'], SORT_NUMERIC);
    } else {
        $tracked_topics = array('topics' => array(), 'forums' => array());
    }

    return $feather->setCookie($cookie_name . '_track', json_encode($tracked_topics), time() + $feather->config['o_timeout_visit']);
}


//
// Extract array of tracked topics from cookie
//
function get_tracked_topics()
{
    global $cookie_name;

    // Get Slim current session
    $feather = \Slim\Slim::getInstance();

    $cookie_raw = $feather->getCookie($cookie_name.'_track');

    if (isset($cookie_raw)) {
        $cookie_data = json_decode($cookie_raw, true);
        return $cookie_data;
    }
    return array('topics' => array(), 'forums' => array());
}


//
// Update posts, topics, last_post, last_post_id and last_poster for a forum
//
function update_forum($forum_id)
{
    // Get Slim current session
    $feather = \Slim\Slim::getInstance();

    $stats_query = \DB::for_table('topics')
                    ->where('forum_id', $forum_id)
                    ->select_expr('COUNT(id)', 'total_topics')
                    ->select_expr('SUM(num_replies)', 'total_replies')
                    ->find_one();

    $num_topics = intval($stats_query['total_topics']);
    $num_replies = intval($stats_query['total_replies']);

    $num_posts = $num_replies + $num_topics; // $num_posts is only the sum of all replies (we have to add the topic posts)

    $select_update_forum = array('last_post', 'last_post_id', 'last_poster');

    $result = \DB::for_table('topics')->select_many($select_update_forum)
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
        \DB::for_table('forums')
            ->where('id', $forum_id)
            ->find_one()
            ->set($insert_update_forum)
            ->save();
}


//
// Deletes any avatars owned by the specified user ID
//
function delete_avatar($user_id)
{
    global $feather_config;

    $filetypes = array('jpg', 'gif', 'png');

    // Delete user avatar
    foreach ($filetypes as $cur_type) {
        if (file_exists(FEATHER_ROOT.$feather_config['o_avatars_dir'].'/'.$user_id.'.'.$cur_type)) {
            @unlink(FEATHER_ROOT.$feather_config['o_avatars_dir'].'/'.$user_id.'.'.$cur_type);
        }
    }
}


//
// Delete a topic and all of its posts
//
function delete_topic($topic_id)
{
    // Get Slim current session
    $feather = \Slim\Slim::getInstance();

    // Delete the topic and any redirect topics
    $where_delete_topic = array(
            array('id' => $topic_id),
            array('moved_to' => $topic_id)
        );

    \DB::for_table('topics')
        ->where_any_is($where_delete_topic)
        ->delete_many();

    // Delete posts in topic
    \DB::for_table('posts')
        ->where('topic_id', $topic_id)
        ->delete_many();

    // Delete any subscriptions for this topic
    \DB::for_table('topic_subscriptions')
        ->where('topic_id', $topic_id)
        ->delete_many();
}


//
// Delete a single post
//
function delete_post($post_id, $topic_id)
{
    // Get Slim current session
    $feather = \Slim\Slim::getInstance();

    $result = \DB::for_table('posts')
                  ->select_many('id', 'poster', 'posted')
                  ->where('topic_id', $topic_id)
                  ->order_by_desc('id')
                  ->limit(2)
                  ->find_many();

    $i = 0;
    foreach ($result as $cur_result) {
        if ($i == 0) {
            $last_id = $cur_result['id'];
        }
        else {
            $second_last_id = $cur_result['id'];
            $second_poster = $cur_result['poster'];
            $second_posted = $cur_result['posted'];
        }
        ++$i;
   }

    // Delete the post
    \DB::for_table('posts')
        ->where('id', $post_id)
        ->find_one()
        ->delete();

    strip_search_index($post_id);

    // Count number of replies in the topic
    $num_replies = \DB::for_table('posts')->where('topic_id', $topic_id)->count() - 1;

    // If the message we deleted is the most recent in the topic (at the end of the topic)
    if ($last_id == $post_id) {
        // If there is a $second_last_id there is more than 1 reply to the topic
        if (isset($second_last_id)) {
             $update_topic = array(
                'last_post'  => $second_posted,
                'last_post_id'  => $second_last_id,
                'last_poster'  => $second_poster,
                'num_replies'  => $num_replies,
            );
            \DB::for_table('topics')
                ->where('id', $topic_id)
                ->find_one()
                ->set($update_topic)
                ->save();
        } else {
            // We deleted the only reply, so now last_post/last_post_id/last_poster is posted/id/poster from the topic itself
            \DB::for_table('topics')
                ->where('id', $topic_id)
                ->find_one()
                ->set_expr('last_post', 'posted')
                ->set_expr('last_post_id', 'id')
                ->set_expr('last_poster', 'poster')
                ->set('num_replies', $num_replies)
                ->save();
        }
    } else {
        // Otherwise we just decrement the reply counter
        \DB::for_table('topics')
            ->where('id', $topic_id)
            ->find_one()
            ->set('num_replies', $num_replies)
            ->save();
    }
}


//
// Replace censored words in $text
//
function censor_words($text)
{
    static $search_for, $replace_with;

    // If not already built in a previous call, build an array of censor words and their replacement text
    if (!isset($search_for)) {
        if (file_exists(FORUM_CACHE_DIR.'cache_censoring.php')) {
            include FORUM_CACHE_DIR.'cache_censoring.php';
        }

        if (!defined('FEATHER_CENSOR_LOADED')) {
            if (!defined('FORUM_CACHE_FUNCTIONS_LOADED')) {
                require FEATHER_ROOT.'include/cache.php';
            }

            generate_censoring_cache();
            require FORUM_CACHE_DIR.'cache_censoring.php';
        }
    }

    if (!empty($search_for)) {
        $text = substr(ucp_preg_replace($search_for, $replace_with, ' '.$text.' '), 1, -1);
    }

    return $text;
}


//
// Determines the correct title for $user
// $user must contain the elements 'username', 'title', 'posts', 'g_id' and 'g_user_title'
//
function get_title($user)
{
    global $feather_bans, $lang_common;
    static $ban_list;

    // If not already built in a previous call, build an array of lowercase banned usernames
    if (empty($ban_list)) {
        $ban_list = array();

        foreach ($feather_bans as $cur_ban) {
            $ban_list[] = utf8_strtolower($cur_ban['username']);
        }
    }

    // If the user has a custom title
    if ($user['title'] != '') {
        $user_title = feather_escape($user['title']);
    }
    // If the user is banned
    elseif (in_array(utf8_strtolower($user['username']), $ban_list)) {
        $user_title = $lang_common['Banned'];
    }
    // If the user group has a default user title
    elseif ($user['g_user_title'] != '') {
        $user_title = feather_escape($user['g_user_title']);
    }
    // If the user is a guest
    elseif ($user['g_id'] == FEATHER_GUEST) {
        $user_title = $lang_common['Guest'];
    }
    // If nothing else helps, we assign the default
    else {
        $user_title = $lang_common['Member'];
    }

    return $user_title;
}


//
// Generate a string with numbered links (for multipage scripts)
//
function paginate($num_pages, $cur_page, $link, $args = null)
{
    global $lang_common;

    $pages = array();
    $link_to_all = false;

    // If $cur_page == -1, we link to all pages (used in viewforum.php)
    if ($cur_page == -1) {
        $cur_page = 1;
        $link_to_all = true;
    }

    if ($num_pages <= 1) {
        $pages = array('<strong class="item1">1</strong>');
    } else {
        // Add a previous page link
        if ($num_pages > 1 && $cur_page > 1) {
            $pages[] = '<a rel="prev"'.(empty($pages) ? ' class="item1"' : '').' href="'.get_sublink($link, 'page/$1', ($cur_page - 1), $args).'">'.$lang_common['Previous'].'</a>';
        }

        if ($cur_page > 3) {
            $pages[] = '<a'.(empty($pages) ? ' class="item1"' : '').' href="'.$link.'">1</a>';

            if ($cur_page > 5) {
                $pages[] = '<span class="spacer">'.$lang_common['Spacer'].'</span>';
            }
        }

        // Don't ask me how the following works. It just does, OK? :-)
        for ($current = ($cur_page == 5) ? $cur_page - 3 : $cur_page - 2, $stop = ($cur_page + 4 == $num_pages) ? $cur_page + 4 : $cur_page + 3; $current < $stop; ++$current) {
            if ($current < 1 || $current > $num_pages) {
                continue;
            } elseif ($current != $cur_page || $link_to_all) {
                $pages[] = '<a'.(empty($pages) ? ' class="item1"' : '').' href="'.str_replace('#', '', get_sublink($link, 'page/$1', $current, $args)).'">'.forum_number_format($current).'</a>';
            } else {
                $pages[] = '<strong'.(empty($pages) ? ' class="item1"' : '').'>'.forum_number_format($current).'</strong>';
            }
        }

        if ($cur_page <= ($num_pages-3)) {
            if ($cur_page != ($num_pages-3) && $cur_page != ($num_pages-4)) {
                $pages[] = '<span class="spacer">'.$lang_common['Spacer'].'</span>';
            }

            $pages[] = '<a'.(empty($pages) ? ' class="item1"' : '').' href="'.get_sublink($link, 'page/$1', $num_pages, $args).'">'.forum_number_format($num_pages).'</a>';
        }

        // Add a next page link
        if ($num_pages > 1 && !$link_to_all && $cur_page < $num_pages) {
            $pages[] = '<a rel="next"'.(empty($pages) ? ' class="item1"' : '').' href="'.get_sublink($link, 'page/$1', ($cur_page + 1), $args).'">'.$lang_common['Next'].'</a>';
        }
    }

    return implode(' ', $pages);
}

//
// Generate a string with numbered links (for multipage scripts)
// Old FluxBB-style function for search page
//
function paginate_old($num_pages, $cur_page, $link)
{
    global $lang_common;

    $pages = array();
    $link_to_all = false;

    // If $cur_page == -1, we link to all pages (used in viewforum.php)
    if ($cur_page == -1) {
        $cur_page = 1;
        $link_to_all = true;
    }

    if ($num_pages <= 1) {
        $pages = array('<strong class="item1">1</strong>');
    } else {
        // Add a previous page link
        if ($num_pages > 1 && $cur_page > 1) {
            $pages[] = '<a rel="prev"'.(empty($pages) ? ' class="item1"' : '').' href="'.$link.($cur_page == 2 ? '' : '&amp;p='.($cur_page - 1)).'">'.$lang_common['Previous'].'</a>';
        }

        if ($cur_page > 3) {
            $pages[] = '<a'.(empty($pages) ? ' class="item1"' : '').' href="'.$link.'">1</a>';

            if ($cur_page > 5) {
                $pages[] = '<span class="spacer">'.$lang_common['Spacer'].'</span>';
            }
        }

        // Don't ask me how the following works. It just does, OK? :-)
        for ($current = ($cur_page == 5) ? $cur_page - 3 : $cur_page - 2, $stop = ($cur_page + 4 == $num_pages) ? $cur_page + 4 : $cur_page + 3; $current < $stop; ++$current) {
            if ($current < 1 || $current > $num_pages) {
                continue;
            } elseif ($current != $cur_page || $link_to_all) {
                $pages[] = '<a'.(empty($pages) ? ' class="item1"' : '').' href="'.$link.($current == 1 ? '' : '&amp;p='.$current).'">'.forum_number_format($current).'</a>';
            } else {
                $pages[] = '<strong'.(empty($pages) ? ' class="item1"' : '').'>'.forum_number_format($current).'</strong>';
            }
        }

        if ($cur_page <= ($num_pages-3)) {
            if ($cur_page != ($num_pages-3) && $cur_page != ($num_pages-4)) {
                $pages[] = '<span class="spacer">'.$lang_common['Spacer'].'</span>';
            }

            $pages[] = '<a'.(empty($pages) ? ' class="item1"' : '').' href="'.$link.'&amp;p='.$num_pages.'">'.forum_number_format($num_pages).'</a>';
        }

        // Add a next page link
        if ($num_pages > 1 && !$link_to_all && $cur_page < $num_pages) {
            $pages[] = '<a rel="next"'.(empty($pages) ? ' class="item1"' : '').' href="'.$link.'&amp;p='.($cur_page +1).'">'.$lang_common['Next'].'</a>';
        }
    }

    return implode(' ', $pages);
}


//
// Display a message
//

function message($msg, $http_status = null, $no_back_link = false, $dontStop = false)
{
    global $lang_common;

    // Did we receive a custom header?
    if (!is_null($http_status)) {
        header('HTTP/1.1 ' . $http_status);
    }

    // Get Slim current session
    $feather = \Slim\Slim::getInstance();

    $http_status = (int) $http_status;
    if ($http_status > 0) {
        $feather->response->setStatus($http_status);
    }

    // Overwrite existing body
    $feather->response->setBody('');

    if (!defined('FEATHER_HEADER')) {
        $page_title = array(feather_escape($feather->config['o_board_title']), $lang_common['Info']);

        if (!defined('FEATHER_ACTIVE_PAGE')) {
            define('FEATHER_ACTIVE_PAGE', 'index');
        }

        require_once FEATHER_ROOT.'controller/header.php';

        $feather->config('templates.path', get_path_view());

        $header = new \controller\header();

        $header->setTitle($page_title)->display();
    }

    $feather->render('message.php', array(
        'lang_common' => $lang_common,
        'message'    =>    $msg,
        'no_back_link'    => $no_back_link,
        ));

    require_once FEATHER_ROOT.'controller/footer.php';

    $footer = new \controller\footer();

    if ($dontStop) {
        $footer->dontStop();
    }

    $footer->display();
}


//
// Format a time string according to $time_format and time zones
//
function format_time($timestamp, $date_only = false, $date_format = null, $time_format = null, $time_only = false, $no_text = false)
{
    global $lang_common, $forum_date_formats, $forum_time_formats;

    if ($timestamp == '') {
        return $lang_common['Never'];
    }

    // Get Slim current session
    $feather = \Slim\Slim::getInstance();

    $diff = ($feather->user->timezone + $feather->user->dst) * 3600;
    $timestamp += $diff;
    $now = time();

    if (is_null($date_format)) {
        $date_format = $forum_date_formats[$feather->user->date_format];
    }

    if (is_null($time_format)) {
        $time_format = $forum_time_formats[$feather->user->time_format];
    }

    $date = gmdate($date_format, $timestamp);
    $today = gmdate($date_format, $now+$diff);
    $yesterday = gmdate($date_format, $now+$diff-86400);

    if (!$no_text) {
        if ($date == $today) {
            $date = $lang_common['Today'];
        } elseif ($date == $yesterday) {
            $date = $lang_common['Yesterday'];
        }
    }

    if ($date_only) {
        return $date;
    } elseif ($time_only) {
        return gmdate($time_format, $timestamp);
    } else {
        return $date.' '.gmdate($time_format, $timestamp);
    }
}


//
// A wrapper for PHP's number_format function
//
function forum_number_format($number, $decimals = 0)
{
    global $lang_common;

    return is_numeric($number) ? number_format($number, $decimals, $lang_common['lang_decimal_point'], $lang_common['lang_thousands_sep']) : $number;
}


//
// Generate a random key of length $len
//
function random_key($len, $readable = false, $hash = false)
{
    if (!function_exists('secure_random_bytes')) {
        include FEATHER_ROOT.'include/srand.php';
    }

    $key = secure_random_bytes($len);

    if ($hash) {
        return substr(bin2hex($key), 0, $len);
    } elseif ($readable) {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';

        $result = '';
        for ($i = 0; $i < $len; ++$i) {
            $result .= substr($chars, (ord($key[$i]) % strlen($chars)), 1);
        }

        return $result;
    }

    return $key;
}


//
// Validate the given redirect URL, use the fallback otherwise
//
function validate_redirect($redirect_url, $fallback_url)
{
    $referrer = parse_url(strtolower($redirect_url));

    // Make sure the host component exists
    if (!isset($referrer['host'])) {
        $referrer['host'] = '';
    }

    // Remove www subdomain if it exists
    if (strpos($referrer['host'], 'www.') === 0) {
        $referrer['host'] = substr($referrer['host'], 4);
    }

    // Make sure the path component exists
    if (!isset($referrer['path'])) {
        $referrer['path'] = '';
    }

    $valid = parse_url(strtolower(get_base_url()));

    // Remove www subdomain if it exists
    if (strpos($valid['host'], 'www.') === 0) {
        $valid['host'] = substr($valid['host'], 4);
    }

    // Make sure the path component exists
    if (!isset($valid['path'])) {
        $valid['path'] = '';
    }

    if ($referrer['host'] == $valid['host'] && preg_match('%^'.preg_quote($valid['path'], '%').'/(.*?)\.php%i', $referrer['path'])) {
        return $redirect_url;
    } else {
        return $fallback_url;
    }
}


//
// Generate a random password of length $len
// Compatibility wrapper for random_key
//
function random_pass($len)
{
    return random_key($len, true);
}


//
// Compute a hash of $str
//
function feather_hash($str)
{
    return sha1($str);
}


//
// Try to determine the correct remote IP-address
//
function get_remote_address()
{
    $feather = \Slim\Slim::getInstance();

    $remote_addr = $feather->request->getIp();

    return $remote_addr;
}


//
// Calls htmlspecialchars with a few options already set
//
function feather_escape($str)
{
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}


//
// Calls htmlspecialchars_decode with a few options already set
//
function feather_htmlspecialchars_decode($str)
{
    if (function_exists('htmlspecialchars_decode')) {
        return htmlspecialchars_decode($str, ENT_QUOTES);
    }

    static $translations;
    if (!isset($translations)) {
        $translations = get_html_translation_table(HTML_SPECIALCHARS, ENT_QUOTES);
        $translations['&#039;'] = '\''; // get_html_translation_table doesn't include &#039; which is what htmlspecialchars translates ' to, but apparently that is okay?! http://bugs.php.net/bug.php?id=25927
        $translations = array_flip($translations);
    }

    return strtr($str, $translations);
}


//
// A wrapper for utf8_strlen for compatibility
//
function feather_strlen($str)
{
    return utf8_strlen($str);
}


//
// Convert \r\n and \r to \n
//
function feather_linebreaks($str)
{
    return str_replace(array("\r\n", "\r"), "\n", $str);
}


//
// A wrapper for utf8_trim for compatibility
//
function feather_trim($str, $charlist = false)
{
    return is_string($str) ? utf8_trim($str, $charlist) : '';
}

//
// Checks if a string is in all uppercase
//
function is_all_uppercase($string)
{
    return utf8_strtoupper($string) == $string && utf8_strtolower($string) != $string;
}


//
// Display a message when board is in maintenance mode
//
function maintenance_message()
{
    global $lang_common, $feather_config, $tpl_main;

    // Deal with newlines, tabs and multiple spaces
    $pattern = array("\t", '  ', '  ');
    $replace = array('&#160; &#160; ', '&#160; ', ' &#160;');
    $message = str_replace($pattern, $replace, $feather_config['o_maintenance_message']);

    // Get Slim current session
    $feather = \Slim\Slim::getInstance();

    $page_title = array(feather_escape($feather_config['o_board_title']), $lang_common['Maintenance']);

    if (!defined('FEATHER_ACTIVE_PAGE')) {
        define('FEATHER_ACTIVE_PAGE', 'index');
    }

    require_once FEATHER_ROOT.'controller/header.php';
    require_once FEATHER_ROOT.'controller/footer.php';

    $feather->config('templates.path', get_path_view());

    $header = new \controller\header();

    $header->setTitle($page_title)->display();

    $feather->render('message.php', array(
            'lang_common' => $lang_common,
            'message'    =>    $message,
            'no_back_link'    =>    '',
        )
    );

    require_once FEATHER_ROOT.'controller/footer.php';

    $footer = new \controller\footer();

    $footer->dontStop();

    $footer->display();
}


//
// Display $message and redirect user to $destination_url
//
function redirect($destination_url, $message = null)
{
    $feather = \Slim\Slim::getInstance();

    // Add a flash message
    $feather->flash('message', $message);

    $feather->redirect($destination_url);
}


//
// Unset any variables instantiated as a result of register_globals being enabled
//
function forum_unregister_globals()
{
    $register_globals = ini_get('register_globals');
    if ($register_globals === '' || $register_globals === '0' || strtolower($register_globals) === 'off') {
        return;
    }

    // Prevent script.php?GLOBALS[foo]=bar
    if (isset($_REQUEST['GLOBALS']) || isset($_FILES['GLOBALS'])) {
        exit('I\'ll have a steak sandwich and... a steak sandwich.');
    }

    // Variables that shouldn't be unset
    $no_unset = array('GLOBALS', '_GET', '_POST', '_COOKIE', '_REQUEST', '_SERVER', '_ENV', '_FILES');

    // Remove elements in $GLOBALS that are present in any of the superglobals
    $input = array_merge($_GET, $_POST, $_COOKIE, $_SERVER, $_ENV, $_FILES, isset($_SESSION) && is_array($_SESSION) ? $_SESSION : array());
    foreach ($input as $k => $v) {
        if (!in_array($k, $no_unset) && isset($GLOBALS[$k])) {
            unset($GLOBALS[$k]);
            unset($GLOBALS[$k]); // Double unset to circumvent the zend_hash_del_key_or_index hole in PHP <4.4.3 and <5.1.4
        }
    }
}


//
// Removes any "bad" characters (characters which mess with the display of a page, are invisible, etc) from user input
//
function forum_remove_bad_characters()
{
    $_GET = remove_bad_characters($_GET);
    $_POST = remove_bad_characters($_POST);
    $_COOKIE = remove_bad_characters($_COOKIE);
    $_REQUEST = remove_bad_characters($_REQUEST);
}

//
// Removes any "bad" characters (characters which mess with the display of a page, are invisible, etc) from the given string
// See: http://kb.mozillazine.org/Network.IDN.blacklist_chars
//
function remove_bad_characters($array)
{
    static $bad_utf8_chars;

    if (!isset($bad_utf8_chars)) {
        $bad_utf8_chars = array(
            "\xcc\xb7"        => '',        // COMBINING SHORT SOLIDUS OVERLAY		0337	*
            "\xcc\xb8"        => '',        // COMBINING LONG SOLIDUS OVERLAY		0338	*
            "\xe1\x85\x9F"    => '',        // HANGUL CHOSEONG FILLER				115F	*
            "\xe1\x85\xA0"    => '',        // HANGUL JUNGSEONG FILLER				1160	*
            "\xe2\x80\x8b"    => '',        // ZERO WIDTH SPACE						200B	*
            "\xe2\x80\x8c"    => '',        // ZERO WIDTH NON-JOINER				200C
            "\xe2\x80\x8d"    => '',        // ZERO WIDTH JOINER					200D
            "\xe2\x80\x8e"    => '',        // LEFT-TO-RIGHT MARK					200E
            "\xe2\x80\x8f"    => '',        // RIGHT-TO-LEFT MARK					200F
            "\xe2\x80\xaa"    => '',        // LEFT-TO-RIGHT EMBEDDING				202A
            "\xe2\x80\xab"    => '',        // RIGHT-TO-LEFT EMBEDDING				202B
            "\xe2\x80\xac"    => '',        // POP DIRECTIONAL FORMATTING			202C
            "\xe2\x80\xad"    => '',        // LEFT-TO-RIGHT OVERRIDE				202D
            "\xe2\x80\xae"    => '',        // RIGHT-TO-LEFT OVERRIDE				202E
            "\xe2\x80\xaf"    => '',        // NARROW NO-BREAK SPACE				202F	*
            "\xe2\x81\x9f"    => '',        // MEDIUM MATHEMATICAL SPACE			205F	*
            "\xe2\x81\xa0"    => '',        // WORD JOINER							2060
            "\xe3\x85\xa4"    => '',        // HANGUL FILLER						3164	*
            "\xef\xbb\xbf"    => '',        // ZERO WIDTH NO-BREAK SPACE			FEFF
            "\xef\xbe\xa0"    => '',        // HALFWIDTH HANGUL FILLER				FFA0	*
            "\xef\xbf\xb9"    => '',        // INTERLINEAR ANNOTATION ANCHOR		FFF9	*
            "\xef\xbf\xba"    => '',        // INTERLINEAR ANNOTATION SEPARATOR		FFFA	*
            "\xef\xbf\xbb"    => '',        // INTERLINEAR ANNOTATION TERMINATOR	FFFB	*
            "\xef\xbf\xbc"    => '',        // OBJECT REPLACEMENT CHARACTER			FFFC	*
            "\xef\xbf\xbd"    => '',        // REPLACEMENT CHARACTER				FFFD	*
            "\xe2\x80\x80"    => ' ',        // EN QUAD								2000	*
            "\xe2\x80\x81"    => ' ',        // EM QUAD								2001	*
            "\xe2\x80\x82"    => ' ',        // EN SPACE								2002	*
            "\xe2\x80\x83"    => ' ',        // EM SPACE								2003	*
            "\xe2\x80\x84"    => ' ',        // THREE-PER-EM SPACE					2004	*
            "\xe2\x80\x85"    => ' ',        // FOUR-PER-EM SPACE					2005	*
            "\xe2\x80\x86"    => ' ',        // SIX-PER-EM SPACE						2006	*
            "\xe2\x80\x87"    => ' ',        // FIGURE SPACE							2007	*
            "\xe2\x80\x88"    => ' ',        // FEATHERCTUATION SPACE					2008	*
            "\xe2\x80\x89"    => ' ',        // THIN SPACE							2009	*
            "\xe2\x80\x8a"    => ' ',        // HAIR SPACE							200A	*
            "\xE3\x80\x80"    => ' ',        // IDEOGRAPHIC SPACE					3000	*
        );
    }

    if (is_array($array)) {
        return array_map('remove_bad_characters', $array);
    }

    // Strip out any invalid characters
    $array = utf8_bad_strip($array);

    // Remove control characters
    $array = preg_replace('%[\x00-\x08\x0b-\x0c\x0e-\x1f]%', '', $array);

    // Replace some "bad" characters
    $array = str_replace(array_keys($bad_utf8_chars), array_values($bad_utf8_chars), $array);

    return $array;
}


//
// Converts the file size in bytes to a human readable file size
//
function file_size($size)
{
    global $lang_common;

    $units = array('B', 'KiB', 'MiB', 'GiB', 'TiB', 'PiB', 'EiB');

    for ($i = 0; $size > 1024; $i++) {
        $size /= 1024;
    }

    return sprintf($lang_common['Size unit '.$units[$i]], round($size, 2));
}


//
// Fetch a list of available styles
//
function forum_list_styles()
{
    $styles = array();

    $d = dir(FEATHER_ROOT.'style');
    while (($entry = $d->read()) !== false) {
        if ($entry{0} == '.') {
            continue;
        }

        if (substr($entry, -4) == '.css') {
            $styles[] = substr($entry, 0, -4);
        }
    }
    $d->close();

    natcasesort($styles);

    return $styles;
}


//
// Fetch a list of available language packs
//
function forum_list_langs()
{
    $languages = array();

    $d = dir(FEATHER_ROOT.'lang');
    while (($entry = $d->read()) !== false) {
        if ($entry{0} == '.') {
            continue;
        }

        if (is_dir(FEATHER_ROOT.'lang/'.$entry) && file_exists(FEATHER_ROOT.'lang/'.$entry.'/common.php')) {
            $languages[] = $entry;
        }
    }
    $d->close();

    natcasesort($languages);

    return $languages;
}


//
// Generate a cache ID based on the last modification time for all stopwords files
//
function generate_stopwords_cache_id()
{
    $files = glob(FEATHER_ROOT.'lang/*/stopwords.txt');
    if ($files === false) {
        return 'cache_id_error';
    }

    $hash = array();

    foreach ($files as $file) {
        $hash[] = $file;
        $hash[] = filemtime($file);
    }

    return sha1(implode('|', $hash));
}


//
// function url_valid($url) {
//
// Return associative array of valid URI components, or FALSE if $url is not
// RFC-3986 compliant. If the passed URL begins with: "www." or "ftp.", then
// "http://" or "ftp://" is prepended and the corrected full-url is stored in
// the return array with a key name "url". This value should be used by the caller.
//
// Return value: FALSE if $url is not valid, otherwise array of URI components:
// e.g.
// Given: "http://www.jmrware.com:80/articles?height=10&width=75#fragone"
// Array(
//	  [scheme] => http
//	  [authority] => www.jmrware.com:80
//	  [userinfo] =>
//	  [host] => www.jmrware.com
//	  [IP_literal] =>
//	  [IPV6address] =>
//	  [ls32] =>
//	  [IPvFuture] =>
//	  [IPv4address] =>
//	  [regname] => www.jmrware.com
//	  [port] => 80
//	  [path_abempty] => /articles
//	  [query] => height=10&width=75
//	  [fragment] => fragone
//	  [url] => http://www.jmrware.com:80/articles?height=10&width=75#fragone
// )
function url_valid($url)
{
    if (strpos($url, 'www.') === 0) {
        $url = 'http://'. $url;
    }
    if (strpos($url, 'ftp.') === 0) {
        $url = 'ftp://'. $url;
    }
    if (!preg_match('/# Valid absolute URI having a non-empty, valid DNS host.
		^
		(?P<scheme>[A-Za-z][A-Za-z0-9+\-.]*):\/\/
		(?P<authority>
		  (?:(?P<userinfo>(?:[A-Za-z0-9\-._~!$&\'()*+,;=:]|%[0-9A-Fa-f]{2})*)@)?
		  (?P<host>
			(?P<IP_literal>
			  \[
			  (?:
				(?P<IPV6address>
				  (?:												 (?:[0-9A-Fa-f]{1,4}:){6}
				  |												   ::(?:[0-9A-Fa-f]{1,4}:){5}
				  | (?:							 [0-9A-Fa-f]{1,4})?::(?:[0-9A-Fa-f]{1,4}:){4}
				  | (?:(?:[0-9A-Fa-f]{1,4}:){0,1}[0-9A-Fa-f]{1,4})?::(?:[0-9A-Fa-f]{1,4}:){3}
				  | (?:(?:[0-9A-Fa-f]{1,4}:){0,2}[0-9A-Fa-f]{1,4})?::(?:[0-9A-Fa-f]{1,4}:){2}
				  | (?:(?:[0-9A-Fa-f]{1,4}:){0,3}[0-9A-Fa-f]{1,4})?::	[0-9A-Fa-f]{1,4}:
				  | (?:(?:[0-9A-Fa-f]{1,4}:){0,4}[0-9A-Fa-f]{1,4})?::
				  )
				  (?P<ls32>[0-9A-Fa-f]{1,4}:[0-9A-Fa-f]{1,4}
				  | (?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}
					   (?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)
				  )
				|	(?:(?:[0-9A-Fa-f]{1,4}:){0,5}[0-9A-Fa-f]{1,4})?::	[0-9A-Fa-f]{1,4}
				|	(?:(?:[0-9A-Fa-f]{1,4}:){0,6}[0-9A-Fa-f]{1,4})?::
				)
			  | (?P<IPvFuture>[Vv][0-9A-Fa-f]+\.[A-Za-z0-9\-._~!$&\'()*+,;=:]+)
			  )
			  \]
			)
		  | (?P<IPv4address>(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}
							   (?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?))
		  | (?P<regname>(?:[A-Za-z0-9\-._~!$&\'()*+,;=]|%[0-9A-Fa-f]{2})+)
		  )
		  (?::(?P<port>[0-9]*))?
		)
		(?P<path_abempty>(?:\/(?:[A-Za-z0-9\-._~!$&\'()*+,;=:@]|%[0-9A-Fa-f]{2})*)*)
		(?:\?(?P<query>		  (?:[A-Za-z0-9\-._~!$&\'()*+,;=:@\\/?]|%[0-9A-Fa-f]{2})*))?
		(?:\#(?P<fragment>	  (?:[A-Za-z0-9\-._~!$&\'()*+,;=:@\\/?]|%[0-9A-Fa-f]{2})*))?
		$
		/mx', $url, $m)) {
        return false;
    }
    switch ($m['scheme']) {
    case 'https':
    case 'http':
        if ($m['userinfo']) {
            return false;
        } // HTTP scheme does not allow userinfo.
        break;
    case 'ftps':
    case 'ftp':
        break;
    default:
        return false;    // Unrecognised URI scheme. Default to FALSE.
    }
    // Validate host name conforms to DNS "dot-separated-parts".
    if ($m{'regname'}) {
        // If host regname specified, check for DNS conformance.

        if (!preg_match('/# HTTP DNS host name.
			^					   # Anchor to beginning of string.
			(?!.{256})			   # Overall host length is less than 256 chars.
			(?:					   # Group dot separated host part alternatives.
			  [0-9A-Za-z]\.		   # Either a single alphanum followed by dot
			|					   # or... part has more than one char (63 chars max).
			  [0-9A-Za-z]		   # Part first char is alphanum (no dash).
			  [\-0-9A-Za-z]{0,61}  # Internal chars are alphanum plus dash.
			  [0-9A-Za-z]		   # Part last char is alphanum (no dash).
			  \.				   # Each part followed by literal dot.
			)*					   # One or more parts before top level domain.
			(?:					   # Top level domains
			  [A-Za-z]{2,63}|	   # Country codes are exactly two alpha chars.
			  xn--[0-9A-Za-z]{4,59})		   # Internationalized Domain Name (IDN)
			$					   # Anchor to end of string.
			/ix', $m['host'])) {
            return false;
        }
    }
    $m['url'] = $url;
    for ($i = 0; isset($m[$i]); ++$i) {
        unset($m[$i]);
    }
    return $m; // return TRUE == array of useful named $matches plus the valid $url.
}

//
// Replace string matching regular expression
//
// This function takes care of possibly disabled unicode properties in PCRE builds
//
function ucp_preg_replace($pattern, $replace, $subject, $callback = false)
{
    if ($callback) {
        $replaced = preg_replace_callback($pattern, create_function('$matches', 'return '.$replace.';'), $subject);
    } else {
        $replaced = preg_replace($pattern, $replace, $subject);
    }

    // If preg_replace() returns false, this probably means unicode support is not built-in, so we need to modify the pattern a little
    if ($replaced === false) {
        if (is_array($pattern)) {
            foreach ($pattern as $cur_key => $cur_pattern) {
                $pattern[$cur_key] = str_replace('\p{L}\p{N}', '\w', $cur_pattern);
            }

            $replaced = preg_replace($pattern, $replace, $subject);
        } else {
            $replaced = preg_replace(str_replace('\p{L}\p{N}', '\w', $pattern), $replace, $subject);
        }
    }

    return $replaced;
}

//
// Replace four-byte characters with a question mark
//
// As MySQL cannot properly handle four-byte characters with the default utf-8
// charset up until version 5.5.3 (where a special charset has to be used), they
// need to be replaced, by question marks in this case.
//
function strip_bad_multibyte_chars($str)
{
    $result = '';
    $length = strlen($str);

    for ($i = 0; $i < $length; $i++) {
        // Replace four-byte characters (11110www 10zzzzzz 10yyyyyy 10xxxxxx)
        $ord = ord($str[$i]);
        if ($ord >= 240 && $ord <= 244) {
            $result .= '?';
            $i += 3;
        } else {
            $result .= $str[$i];
        }
    }

    return $result;
}

//
// Check whether a file/folder is writable.
//
// This function also works on Windows Server where ACLs seem to be ignored.
//
function forum_is_writable($path)
{
    if (is_dir($path)) {
        $path = rtrim($path, '/').'/';
        return forum_is_writable($path.uniqid(mt_rand()).'.tmp');
    }

    // Check temporary file for read/write capabilities
    $rm = file_exists($path);
    $f = @fopen($path, 'a');

    if ($f === false) {
        return false;
    }

    fclose($f);

    if (!$rm) {
        @unlink($path);
    }

    return true;
}


// DEBUG FUNCTIONS BELOW

//
// Display executed queries (if enabled)
//
function display_saved_queries()
{
    global $lang_common;

    ?>

<div id="debug" class="blocktable">
	<h2><span><?php echo $lang_common['Debug table'] ?></span></h2>
	<div class="box">
		<div class="inbox">
			<table>
			<thead>
				<tr>
					<th class="tcl" scope="col"><?php echo $lang_common['Query times'] ?></th>
					<th class="tcr" scope="col"><?php echo $lang_common['Query'] ?></th>
				</tr>
			</thead>
			<tbody>
<?php

    $query_time_total = 0.0;
    $i = 0;
    foreach (\DB::get_query_log()[1] as $query) {
        ?>
                <tr>
					<td class="tcl"><?php echo feather_escape(round(\DB::get_query_log()[0][$i], 6)) ?></td>
					<td class="tcr"><?php echo feather_escape($query) ?></td>
				</tr>
        <?php
        ++$i;
    }
        ?>
				<tr>
					<td class="tcl" colspan="2"><?php printf($lang_common['Total query time'], $query_time_total.' s') ?></td>
				</tr>
			</tbody>
			</table>
		</div>
	</div>
</div>
<?php

}

//
// Return the path to load the view file
//
function get_path_view($file = null)
{
    // Get Slim current session
    $feather = \Slim\Slim::getInstance();

    if ($file && is_file('style/'.$feather->user->style)) {
        return FEATHER_ROOT.'style/'.$feather->user->style.'/view';
    }
    elseif (is_dir('style/'.$feather->user->style.'/view')) {
        return FEATHER_ROOT.'style/'.$feather->user->style.'/view';
    } else {
        return FEATHER_ROOT.'view';
    }
}

//
// Make a string safe to use in a URL
// Inspired by (c) Panther <http://www.pantherforum.org/>
//
function url_friendly($str)
{
    require FEATHER_ROOT.'include/url_replace.php';

    $str = strtr($str, $url_replace);
    $str = strtolower(utf8_decode($str));
    $str = feather_trim(preg_replace(array('/[^a-z0-9\s]/', '/[\s]+/'), array('', '-'), $str), '-');

    if (empty($str)) {
        $str = 'view';
    }

    return $str;
}

//
// Generate link to another page on the forum
// Inspired by (c) Panther <http://www.pantherforum.org/>
//
function get_link($link, $args = null)
{
    if (function_exists('apache_get_modules') && in_array('mod_rewrite', apache_get_modules())) { // If we have Apache's mod_rewrite enabled
        $base_url = get_base_url();
    } else {
        $base_url = get_base_url().'/index.php';
    }

    $gen_link = $link;
    if ($args == null) {
        $gen_link = $base_url.'/'.$link;
    } elseif (!is_array($args)) {
        $gen_link = $base_url.'/'.str_replace('$1', $args, $link);
    } else {
        for ($i = 0; isset($args[$i]); ++$i) {
            $gen_link = str_replace('$'.($i + 1), $args[$i], $gen_link);
        }
        $gen_link = $base_url.'/'.$gen_link;
    }

    return $gen_link;
}


//
// Generate a hyperlink with parameters and anchor and a subsection such as a subpage
// Inspired by (c) Panther <http://www.pantherforum.org/>
//
function get_sublink($link, $sublink, $subarg, $args = null)
{
    $base_url = get_base_url();

    if ($sublink == 'p$1' && $subarg == 1) {
        return get_link($link, $args);
    }

    $gen_link = $link;
    if (!is_array($args) && $args != null) {
        $gen_link = str_replace('$1', $args, $link);
    } else {
        for ($i = 0; isset($args[$i]); ++$i) {
            $gen_link = str_replace('$'.($i + 1), $args[$i], $gen_link);
        }
    }

    $gen_link = $base_url.'/'.str_replace('#', str_replace('$1', str_replace('$1', $subarg, $sublink), '$1/'), $gen_link);

    return $gen_link;
}

// Generate breadcrumbs from an array of name and URLs

function breadcrumbs(array $links) {
    global $lang_admin_reports;

    foreach ($links as $name => $url) {
        if ($name != '' && $url != '') {
            $tmp[] = '<span><a href="' . $url . '">'.feather_escape($name).'</a></span>';
        } else {
            $tmp[] = '<span>'.$lang_admin_reports['Deleted'].'</span>';
            return implode(' » ', $tmp);
        }
    }
    return implode(' » ', $tmp);
}
