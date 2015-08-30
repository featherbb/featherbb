<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

/*-----------------------------------------------------------------------------

  INSTRUCTIONS

  This script is used to include information about your board from
  pages outside the forums and to syndicate news about recent
  discussions via RSS/Atom/XML. The script can display a list of
  recent discussions, a list of active users or a collection of
  general board statistics. The script can be called directly via
  an URL, from a PHP include command or through the use of Server
  Side Includes (SSI).

  The scripts behaviour is controlled via variables supplied in the
  URL to the script. The different variables are: action (what to
  do), show (how many items to display), fid (the ID or IDs of
  the forum(s) to poll for topics), nfid (the ID or IDs of forums
  that should be excluded), tid (the ID of the topic from which to
  display posts) and type (output as HTML or RSS). The only
  mandatory variable is action. Possible/default values are:

    action: feed - show most recent topics/posts (HTML or RSS)
            online - show users online (HTML)
            online_full - as above, but includes a full list (HTML)
            stats - show board statistics (HTML)

    type:   rss - output as RSS 2.0
            atom - output as Atom 1.0
            xml - output as XML
            html - output as HTML (<li>'s)

    fid:    One or more forum IDs (comma-separated). If ignored,
            topics from all readable forums will be pulled.

    nfid:   One or more forum IDs (comma-separated) that are to be
            excluded. E.g. the ID of a a test forum.

    tid:    A topic ID from which to show posts. If a tid is supplied,
            fid and nfid are ignored.

    show:   Any integer value between 1 and 50. The default is 15.

    order:  last_post - show topics ordered by when they were last
                        posted in, giving information about the reply.
            posted - show topics ordered by when they were first
                     posted, giving information about the original post.

-----------------------------------------------------------------------------*/

define('FEATHER_QUIET_VISIT', 1);

// Load Slim Framework
require 'Slim/Slim.php';
\Slim\Slim::registerAutoloader();

// Load FeatherBB
require 'include/classes/autoload.class.php';
\FeatherBB\Loader::registerAutoloader();

// Instantiate Slim and add CSRF
$feather = new \Slim\Slim();
$feather->add(new \FeatherBB\Csrf());

$feather_settings = array('config_file' => 'include/config.php',
    'cache_dir' => 'cache/',
    'debug' => 'all'); // 3 levels : false, info (only execution time and number of queries), and all (display info + queries)
$feather->add(new \FeatherBB\Auth());
$feather->add(new \FeatherBB\Core($feather_settings));

load_textdomain('featherbb', FEATHER_ROOT.'lang/'.$feather->user->language.'/common.mo');
load_textdomain('featherbb', FEATHER_ROOT.'lang/'.$feather->user->language.'/index.mo');

// The length at which topic subjects will be truncated (for HTML output)
if (!defined('FORUM_EXTERN_MAX_SUBJECT_LENGTH')) {
    define('FORUM_EXTERN_MAX_SUBJECT_LENGTH', 30);
}

function featherbb_write_cache_file($file, $content)
{
    $fh = @fopen(FORUM_CACHE_DIR.$file, 'wb');
    if (!$fh) {
        error('Unable to write cache file '.$feather->utils->escape($file).' to cache directory. Please make sure PHP has write access to the directory \''.$feather->utils->escape(FORUM_CACHE_DIR).'\'', __FILE__, __LINE__);
    }

    flock($fh, LOCK_EX);
    ftruncate($fh, 0);

    fwrite($fh, $content);

    flock($fh, LOCK_UN);
    fclose($fh);

    if (function_exists('opcache_invalidate')) {
        opcache_invalidate(FORUM_CACHE_DIR.$file, true);
    } elseif (function_exists('apc_delete_file')) {
        @apc_delete_file(FORUM_CACHE_DIR.$file);
    }
}

//
// Converts the CDATA end sequence ]]> into ]]&gt;
//
function escape_cdata($str)
{
    return str_replace(']]>', ']]&gt;', $str);
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
// Fill $feather->user with default values (for guests)
//
function set_default_user()
{
    // Get Slim current session
    $feather = \Slim\Slim::getInstance();

    $remote_addr = $feather->request->getIp();

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
        switch ($feather->forum_settings['db_type']) {
            case 'mysql':
            case 'mysqli':
            case 'mysql_innodb':
            case 'mysqli_innodb':
            case 'sqlite':
            case 'sqlite3':
                \DB::for_table('online')->raw_execute('REPLACE INTO '.$feather->forum_settings['db_prefix'].'online (user_id, ident, logged) VALUES(1, :ident, :logged)', array(':ident' => $remote_addr, ':logged' => $feather->user->logged));
                break;

            default:
                \DB::for_table('online')->raw_execute('INSERT INTO '.$feather->forum_settings['db_prefix'].'online (user_id, ident, logged) SELECT 1, :ident, :logged WHERE NOT EXISTS (SELECT 1 FROM '.$feather->forum_settings['db_prefix'].'online WHERE ident=:ident)', array(':ident' => $remote_addr, ':logged' => $feather->user->logged));
                break;
        }
    } else {
        \DB::for_table('online')->where('ident', $remote_addr)
            ->update_many('logged', time());
    }

    $feather->user->disp_topics = $feather->forum_settings['o_disp_topics_default'];
    $feather->user->disp_posts = $feather->forum_settings['o_disp_posts_default'];
    $feather->user->timezone = $feather->forum_settings['o_default_timezone'];
    $feather->user->dst = $feather->forum_settings['o_default_dst'];
    $feather->user->language = $feather->forum_settings['o_default_lang'];
    $feather->user->style = $feather->forum_settings['o_default_style'];
    $feather->user->is_guest = true;
    $feather->user->is_admmod = false;
}

//
// Authenticates the provided username and password against the user database
// $user can be either a user ID (integer) or a username (string)
// $password can be either a plaintext password or a password hash including salt ($password_is_hash must be set accordingly)
//
function authenticate_user($user, $password, $password_is_hash = false)
{
    global $feather;

    // Check if there's a user matching $user and $password
    $select_check_cookie = array('u.*', 'g.*', 'o.logged', 'o.idle');

    $result = ORM::for_table('users')
                ->table_alias('u')
                ->select_many($select_check_cookie)
                ->inner_join('groups', array('u.group_id', '=', 'g.g_id'), 'g')
                ->left_outer_join('online', array('o.user_id', '=', 'u.id'), 'o');

    if (is_int($user)) {
        $result = $result->where('u.id', intval($user));
    }
    else {
        $result = $result->where('u.username', $user);
    }

    $result = $result->find_result_set();

    foreach ($result as $feather->user);

    if (!isset($feather->user->id) ||
        ($password_is_hash && $password != $feather->user->password) ||
        (!$password_is_hash && \FeatherBB\Utils::feather_hash($password) != $feather->user->password)) {
        set_default_user();
    } else {
        $feather->user->is_guest = false;
    }
}

// If we're a guest and we've sent a username/pass, we can try to authenticate using those details
if ($feather->user->is_guest && isset($_SERVER['PHP_AUTH_USER'])) {
    authenticate_user($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']);
}

if ($feather->user->g_read_board == '0') {
    http_authenticate_user();
    exit(__('No view'));
}

$action = isset($_GET['action']) ? strtolower($_GET['action']) : 'feed';

// Handle a couple old formats, from FluxBB 1.2
switch ($action) {
    case 'active':
        $action = 'feed';
        $_GET['order'] = 'last_post';
        break;

    case 'new':
        $action = 'feed';
        $_GET['order'] = 'posted';
        break;
}

//
// Sends the proper headers for Basic HTTP Authentication
//
function http_authenticate_user()
{
    global $feather;

    if (!$feather->user->is_guest) {
        return;
    }

    header('WWW-Authenticate: Basic realm="'.$feather->forum_settings['o_board_title'].' External Syndication"');
    header('HTTP/1.0 401 Unauthorized');
}


//
// Output $feed as RSS 2.0
//
function output_rss($feed)
{
    // Send XML/no cache headers
    header('Content-Type: application/xml; charset=utf-8');
    header('Expires: '.gmdate('D, d M Y H:i:s').' GMT');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Pragma: public');

    echo '<?xml version="1.0" encoding="utf-8"?>'."\n";
    echo '<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">'."\n";
    echo "\t".'<channel>'."\n";
    echo "\t\t".'<atom:link href="'.$feather->utils->escape(get_current_url()).'" rel="self" type="application/rss+xml" />'."\n";
    echo "\t\t".'<title><![CDATA['.escape_cdata($feed['title']).']]></title>'."\n";
    echo "\t\t".'<link>'.$feather->utils->escape($feed['link']).'</link>'."\n";
    echo "\t\t".'<description><![CDATA['.escape_cdata($feed['description']).']]></description>'."\n";
    echo "\t\t".'<lastBuildDate>'.gmdate('r', count($feed['items']) ? $feed['items'][0]['pubdate'] : time()).'</lastBuildDate>'."\n";

    if ($feather->forum_settings['o_show_version'] == '1') {
        echo "\t\t".'<generator>FeatherBB '.$feather->forum_settings['o_cur_version'].'</generator>'."\n";
    } else {
        echo "\t\t".'<generator>FeatherBB</generator>'."\n";
    }

    foreach ($feed['items'] as $item) {
        echo "\t\t".'<item>'."\n";
        echo "\t\t\t".'<title><![CDATA['.escape_cdata($item['title']).']]></title>'."\n";
        echo "\t\t\t".'<link>'.$feather->utils->escape($item['link']).'</link>'."\n";
        echo "\t\t\t".'<description><![CDATA['.escape_cdata($item['description']).']]></description>'."\n";
        echo "\t\t\t".'<author><![CDATA['.(isset($item['author']['email']) ? escape_cdata($item['author']['email']) : 'dummy@example.com').' ('.escape_cdata($item['author']['name']).')]]></author>'."\n";
        echo "\t\t\t".'<pubDate>'.gmdate('r', $item['pubdate']).'</pubDate>'."\n";
        echo "\t\t\t".'<guid>'.$feather->utils->escape($item['link']).'</guid>'."\n";

        echo "\t\t".'</item>'."\n";
    }

    echo "\t".'</channel>'."\n";
    echo '</rss>'."\n";
}


//
// Output $feed as Atom 1.0
//
function output_atom($feed)
{
    // Send XML/no cache headers
    header('Content-Type: application/atom+xml; charset=utf-8');
    header('Expires: '.gmdate('D, d M Y H:i:s').' GMT');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Pragma: public');

    echo '<?xml version="1.0" encoding="utf-8"?>'."\n";
    echo '<feed xmlns="http://www.w3.org/2005/Atom">'."\n";

    echo "\t".'<title type="html"><![CDATA['.escape_cdata($feed['title']).']]></title>'."\n";
    echo "\t".'<link rel="self" href="'.$feather->utils->escape(get_current_url()).'"/>'."\n";
    echo "\t".'<link href="'.$feather->utils->escape($feed['link']).'"/>'."\n";
    echo "\t".'<updated>'.gmdate('Y-m-d\TH:i:s\Z', count($feed['items']) ? $feed['items'][0]['pubdate'] : time()).'</updated>'."\n";

    if ($feather->forum_settings['o_show_version'] == '1') {
        echo "\t".'<generator version="'.$feather->forum_settings['o_cur_version'].'">FluxBB</generator>'."\n";
    } else {
        echo "\t".'<generator>FluxBB</generator>'."\n";
    }

    echo "\t".'<id>'.$feather->utils->escape($feed['link']).'</id>'."\n";

    $content_tag = ($feed['type'] == 'posts') ? 'content' : 'summary';

    foreach ($feed['items'] as $item) {
        echo "\t".'<entry>'."\n";
        echo "\t\t".'<title type="html"><![CDATA['.escape_cdata($item['title']).']]></title>'."\n";
        echo "\t\t".'<link rel="alternate" href="'.$feather->utils->escape($item['link']).'"/>'."\n";
        echo "\t\t".'<'.$content_tag.' type="html"><![CDATA['.escape_cdata($item['description']).']]></'.$content_tag.'>'."\n";
        echo "\t\t".'<author>'."\n";
        echo "\t\t\t".'<name><![CDATA['.escape_cdata($item['author']['name']).']]></name>'."\n";

        if (isset($item['author']['email'])) {
            echo "\t\t\t".'<email><![CDATA['.escape_cdata($item['author']['email']).']]></email>'."\n";
        }

        if (isset($item['author']['uri'])) {
            echo "\t\t\t".'<uri>'.$feather->utils->escape($item['author']['uri']).'</uri>'."\n";
        }

        echo "\t\t".'</author>'."\n";
        echo "\t\t".'<updated>'.gmdate('Y-m-d\TH:i:s\Z', $item['pubdate']).'</updated>'."\n";

        echo "\t\t".'<id>'.$feather->utils->escape($item['link']).'</id>'."\n";
        echo "\t".'</entry>'."\n";
    }

    echo '</feed>'."\n";
}


//
// Output $feed as XML
//
function output_xml($feed)
{
    // Send XML/no cache headers
    header('Content-Type: application/xml; charset=utf-8');
    header('Expires: '.gmdate('D, d M Y H:i:s').' GMT');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Pragma: public');

    echo '<?xml version="1.0" encoding="utf-8"?>'."\n";
    echo '<source>'."\n";
    echo "\t".'<url>'.$feather->utils->escape($feed['link']).'</url>'."\n";

    $forum_tag = ($feed['type'] == 'posts') ? 'post' : 'topic';

    foreach ($feed['items'] as $item) {
        echo "\t".'<'.$forum_tag.' id="'.$item['id'].'">'."\n";

        echo "\t\t".'<title><![CDATA['.escape_cdata($item['title']).']]></title>'."\n";
        echo "\t\t".'<link>'.$feather->utils->escape($item['link']).'</link>'."\n";
        echo "\t\t".'<content><![CDATA['.escape_cdata($item['description']).']]></content>'."\n";
        echo "\t\t".'<author>'."\n";
        echo "\t\t\t".'<name><![CDATA['.escape_cdata($item['author']['name']).']]></name>'."\n";

        if (isset($item['author']['email'])) {
            echo "\t\t\t".'<email><![CDATA['.escape_cdata($item['author']['email']).']]></email>'."\n";
        }

        if (isset($item['author']['uri'])) {
            echo "\t\t\t".'<uri>'.$feather->utils->escape($item['author']['uri']).'</uri>'."\n";
        }

        echo "\t\t".'</author>'."\n";
        echo "\t\t".'<posted>'.gmdate('r', $item['pubdate']).'</posted>'."\n";

        echo "\t".'</'.$forum_tag.'>'."\n";
    }

    echo '</source>'."\n";
}


//
// Output $feed as HTML (using <li> tags)
//
function output_html($feed)
{
    // Send the Content-type header in case the web server is setup to send something else
    header('Content-type: text/html; charset=utf-8');
    header('Expires: '.gmdate('D, d M Y H:i:s').' GMT');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Pragma: public');

    foreach ($feed['items'] as $item) {
        if (utf8_strlen($item['title']) > FORUM_EXTERN_MAX_SUBJECT_LENGTH) {
            $subject_truncated = $feather->utils->escape($feather->utils->trim(utf8_substr($item['title'], 0, (FORUM_EXTERN_MAX_SUBJECT_LENGTH - 5)))).' …';
        } else {
            $subject_truncated = $feather->utils->escape($item['title']);
        }

        echo '<li><a href="'.$feather->utils->escape($item['link']).'" title="'.$feather->utils->escape($item['title']).'">'.$subject_truncated.'</a></li>'."\n";
    }
}

// Show recent discussions
if ($action == 'feed') {
    require FEATHER_ROOT.'include/parser.php';

    // Determine what type of feed to output
    $type = isset($_GET['type']) ? strtolower($_GET['type']) : 'html';
    if (!in_array($type, array('html', 'rss', 'atom', 'xml'))) {
        $type = 'html';
    }

    $show = isset($_GET['show']) ? intval($_GET['show']) : 15;
    if ($show < 1 || $show > 50) {
        $show = 15;
    }

    // Was a topic ID supplied?
    if (isset($_GET['tid'])) {
        $tid = intval($_GET['tid']);

        // Fetch topic subject
        $select_show_recent_topics = array('t.subject', 't.first_post_id');
        $where_show_recent_topics = array(
            array('fp.read_forum' => 'IS NULL'),
            array('fp.read_forum' => '1')
        );

        $cur_topic = \DB::for_table('topics')->table_alias('t')
                        ->select_many($select_show_recent_topics)
                        ->left_outer_join('forum_perms', array('fp.forum_id', '=', 't.forum_id'), 'fp')
                        ->left_outer_join('forum_perms', array('fp.group_id', '=', $feather->user->g_id), null, true)
                        ->where_any_is($where_show_recent_topics)
                        ->where_null('t.moved_to')
                        ->where('t.id', $tid)
                        ->find_one();

        if (!$cur_topic) {
            http_authenticate_user();
            exit(__('Bad request'));
        }

        if ($feather->forum_settings['o_censoring'] == '1') {
            $cur_topic['subject'] = censor_words($cur_topic['subject']);
        }

        // Setup the feed
        $feed = array(
            'title'        =>    $feather->forum_settings['o_board_title'].__('Title separator').$cur_topic['subject'],
            'link'            =>    get('topic/'.$tid.'/'.url_friendly($cur_topic['subject']).'/'),
            'description'        =>    sprintf(__('RSS description topic'), $cur_topic['subject']),
            'items'            =>    array(),
            'type'            =>    'posts'
        );

        // Fetch $show posts
        $select_print_posts = array('p.id', 'p.poster', 'p.message', 'p.hide_smilies', 'p.posted', 'p.poster_email', 'p.poster_id', 'u.email_setting', 'u.email');

        $result = \DB::for_table('posts')
            ->table_alias('p')
            ->select_many($select_print_posts)
            ->inner_join('users', array('u.id', '=', 'p.poster_id'), 'u')
            ->where('p.topic_id', $tid)
            ->order_by_desc('p.posted')
            ->limit($show)
            ->find_array();

        foreach ($result as $cur_post) {
            $cur_post['message'] = parse_message($cur_post['message'], $cur_post['hide_smilies']);

            $item = array(
                'id'            =>    $cur_post['id'],
                'title'            =>    $cur_topic['first_post_id'] == $cur_post['id'] ? $cur_topic['subject'] : __('RSS reply').$cur_topic['subject'],
                'link'            =>    get('post/'.$cur_post['id'].'/#p'.$cur_post['id']),
                'description'        =>    $cur_post['message'],
                'author'        =>    array(
                    'name'    => $cur_post['poster'],
                ),
                'pubdate'        =>    $cur_post['posted']
            );

            if ($cur_post['poster_id'] > 1) {
                if ($cur_post['email_setting'] == '0' && !$feather->user->is_guest) {
                    $item['author']['email'] = $cur_post['email'];
                }

                $item['author']['uri'] = get('user/'.$cur_post['poster_id'].'/');
            } elseif ($cur_post['poster_email'] != '' && !$feather->user->is_guest) {
                $item['author']['email'] = $cur_post['poster_email'];
            }

            $feed['items'][] = $item;
        }

        $output_func = 'output_'.$type;
        $output_func($feed);
    } else {
        $order_posted = isset($_GET['order']) && strtolower($_GET['order']) == 'posted';
        $forum_name = '';

        $result = \DB::for_table('topics')
                        ->table_alias('t');

        // Were any forum IDs supplied?
        if (isset($_GET['fid']) && is_scalar($_GET['fid']) && $_GET['fid'] != '') {
            $fids = explode(',', $feather->utils->trim($_GET['fid']));
            $fids = array_map('intval', $fids);

            if (!empty($fids)) {
                $result = $result->where_in('t.forum_id', $fids);
            }

            if (count($fids) == 1) {
                // Fetch forum name
                $where_show_forum_name = array(
                    array('fp.read_forum' => 'IS NULL'),
                    array('fp.read_forum' => '1')
                );

                $cur_topic = \DB::for_table('forums')->table_alias('f')
                    ->left_outer_join('forum_perms', array('fp.forum_id', '=', 'f.id'), 'fp')
                    ->left_outer_join('forum_perms', array('fp.group_id', '=', $feather->user->g_id), null, true)
                    ->where_any_is($where_show_forum_name)
                    ->where('f.id', $fids[0])
                    ->find_one_col('f.forum_name');

                if ($cur_topic) {
                    $forum_name = __('Title separator').$cur_topic;
                }
            }
        }

        // Any forum IDs to exclude?
        if (isset($_GET['nfid']) && is_scalar($_GET['nfid']) && $_GET['nfid'] != '') {
            $nfids = explode(',', $feather->utils->trim($_GET['nfid']));
            $nfids = array_map('intval', $nfids);

            if (!empty($nfids)) {
                $result = $result->where_not_in('t.forum_id', $nfids);
            }
        }

        // Only attempt to cache if caching is enabled and we have all or a single forum
        if ($feather->forum_settings['o_feed_ttl'] > 0 && ($forum_sql == '' || ($forum_name != '' && !isset($_GET['nfid'])))) {
            $cache_id = 'feed'.sha1($feather->user->g_id.'|'.__('lang_identifier').'|'.($order_posted ? '1' : '0').($forum_name == '' ? '' : '|'.$fids[0]));
        }

        // Load cached feed
        if (isset($cache_id) && file_exists(FORUM_CACHE_DIR.'cache_'.$cache_id.'.php')) {
            include FORUM_CACHE_DIR.'cache_'.$cache_id.'.php';
        }

        $now = time();
        if (!isset($feed) || $cache_expire < $now) {
            // Setup the feed
            $feed = array(
                'title'        =>    $feather->forum_settings['o_board_title'].$forum_name,
                'link'            =>    '/index.php',
                'description'    =>    sprintf(__('RSS description'), $feather->forum_settings['o_board_title']),
                'items'            =>    array(),
                'type'            =>    'topics'
            );

            // Fetch $show topics
            $select_print_posts = array('t.id', 't.poster', 't.subject', 't.posted', 't.last_post', 't.last_poster', 'p.message', 'p.hide_smilies', 'u.email_setting', 'u.email', 'p.poster_id', 'p.poster_email');
            $where_print_posts = array(
                array('fp.read_forum' => 'IS NULL'),
                array('fp.read_forum' => '1')
            );

            $result = $result->select_many($select_print_posts)
                        ->inner_join('posts', array('p.id', '=', ($order_posted ? 't.first_post_id' : 't.last_post_id')), 'p')
                        ->inner_join('users', array('u.id', '=', 'p.poster_id'), 'u')
                        ->left_outer_join('forum_perms', array('fp.forum_id', '=', 't.forum_id'), 'fp')
                        ->left_outer_join('forum_perms', array('fp.group_id', '=', $feather->user->g_id), null, true)
                        ->where_any_is($where_print_posts)
                        ->where_null('t.moved_to')
                        ->order_by(($order_posted ? 't.posted' : 't.last_post'))
                        ->limit((isset($cache_id) ? 50 : $show))
                        ->find_array();

            foreach ($result as $cur_topic) {
                if ($feather->forum_settings['o_censoring'] == '1') {
                    $cur_topic['subject'] = censor_words($cur_topic['subject']);
                }

                $cur_topic['message'] = parse_message($cur_topic['message'], $cur_topic['hide_smilies']);

                $item = array(
                    'id'            =>    $cur_topic['id'],
                    'title'            =>    $cur_topic['subject'],
                    'link'            =>    get('topic/'.$cur_topic['id'].'/'.url_friendly($cur_topic['subject']).'/').($order_posted ? '' : '/action/new/'),
                    'description'    =>    $cur_topic['message'],
                    'author'        =>    array(
                        'name'    => $order_posted ? $cur_topic['poster'] : $cur_topic['last_poster']
                    ),
                    'pubdate'        =>    $order_posted ? $cur_topic['posted'] : $cur_topic['last_post']
                );

                if ($cur_topic['poster_id'] > 1) {
                    if ($cur_topic['email_setting'] == '0' && !$feather->user->is_guest) {
                        $item['author']['email'] = $cur_topic['email'];
                    }

                    $item['author']['uri'] = get('user/'.$cur_topic['poster_id'].'/');
                } elseif ($cur_topic['poster_email'] != '' && !$feather->user->is_guest) {
                    $item['author']['email'] = $cur_topic['poster_email'];
                }

                $feed['items'][] = $item;
            }

            // Output feed as PHP code
            if (isset($cache_id)) {
                if (!defined('FORUM_CACHE_FUNCTIONS_LOADED')) {
                    require FEATHER_ROOT.'include/cache.php';
                }

                $content = '<?php'."\n\n".'$feed = '.var_export($feed, true).';'."\n\n".'$cache_expire = '.($now + ($feather->forum_settings['o_feed_ttl'] * 60)).';'."\n\n".'?>';
                featherbb_write_cache_file('cache_'.$cache_id.'.php', $content);
            }
        }

        // If we only want to show a few items but due to caching we have too many
        if (count($feed['items']) > $show) {
            $feed['items'] = array_slice($feed['items'], 0, $show);
        }

        // Prepend the current base URL onto some links. Done after caching to handle http/https correctly
        $feed['link'] = $feather->url->base(true).$feed['link'];

        foreach ($feed['items'] as $key => $item) {
            $feed['items'][$key]['link'] = $feather->url->base(true).$item['link'];

            if (isset($item['author']['uri'])) {
                $feed['items'][$key]['author']['uri'] = $feather->url->base(true).$item['author']['uri'];
            }
        }

        $output_func = 'output_'.$type;
        $output_func($feed);
    }

    exit;
}

// Show users online
elseif ($action == 'online' || $action == 'online_full') {

    // Fetch users online info and generate strings for output
    $num_guests = $num_users = 0;
    $users = array();

    $select_fetch_users_online = array('user_id', 'ident');
    $where_fetch_users_online = array('idle' => '0');
    $order_by_fetch_users_online = array('ident');

    $result = \DB::for_table('online')
                ->select_many($select_fetch_users_online)
                ->where($where_fetch_users_online)
                ->order_by_many($order_by_fetch_users_online)
                ->find_result_set();

    foreach ($result as $feather_user_online) {
        if ($feather_user_online['user_id'] > 1) {
            $users[] = ($feather->user->g_view_users == '1') ? '<a href="'.get('user/'.$feather_user_online['user_id'].'/').'">'.$feather->utils->escape($feather_user_online['ident']).'</a>' : $feather->utils->escape($feather_user_online['ident']);
            ++$num_users;
        } else {
            ++$num_guests;
        }
    }

    // Send the Content-type header in case the web server is setup to send something else
    header('Content-type: text/html; charset=utf-8');
    header('Expires: '.gmdate('D, d M Y H:i:s').' GMT');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Pragma: public');

    echo sprintf(__('Guests online'), $feather->utils->forum_number_format($num_guests)).'<br />'."\n";

    if ($action == 'online_full' && !empty($users)) {
        echo sprintf(__('Users online'), implode(', ', $users)).'<br />'."\n";
    } else {
        echo sprintf(__('Users online'), $feather->utils->forum_number_format($num_users)).'<br />'."\n";
    }

    exit;
}

// Show board statistics
elseif ($action == 'stats') {

    if (!$feather->cache->isCached('users_info')) {
        $feather->cache->store('users_info', \model\cache::get_users_info());
    }

    $stats = $feather->cache->retrieve('users_info');

    $stats_query = \DB::for_table('forums')
                        ->select_expr('SUM(num_topics)', 'total_topics')
                        ->select_expr('SUM(num_posts)', 'total_posts')
                        ->find_one();

    $stats['total_topics'] = intval($stats_query['total_topics']);
    $stats['total_posts'] = intval($stats_query['total_posts']);

    // Send the Content-type header in case the web server is setup to send something else
    header('Content-type: text/html; charset=utf-8');
    header('Expires: '.gmdate('D, d M Y H:i:s').' GMT');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Pragma: public');

    echo sprintf(__('No of users'), $feather->utils->forum_number_format($stats['total_users'])).'<br />'."\n";
    echo sprintf(__('Newest user'), (($feather->user->g_view_users == '1') ? '<a href="'.get('user/'.$stats['last_user']['id'].'/').'">'.$feather->utils->escape($stats['last_user']['username']).'</a>' : $feather->utils->escape($stats['last_user']['username']))).'<br />'."\n";
    echo sprintf(__('No of topics'), $feather->utils->forum_number_format($stats['total_topics'])).'<br />'."\n";
    echo sprintf(__('No of posts'), $feather->utils->forum_number_format($stats['total_posts'])).'<br />'."\n";

    exit;
}
// If we end up here, the script was called with some wacky parameters
exit(__('Bad request'));
