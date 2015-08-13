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

if (!defined('FEATHER_ROOT')) {
    define('FEATHER_ROOT', dirname(__FILE__).'/');
}

// Load Slim Framework
require 'Slim/Slim.php';
\Slim\Slim::registerAutoloader();

// Instantiate Slim
$feather = new \Slim\Slim();

require FEATHER_ROOT.'include/common.php';

// The length at which topic subjects will be truncated (for HTML output)
if (!defined('FORUM_EXTERN_MAX_SUBJECT_LENGTH')) {
    define('FORUM_EXTERN_MAX_SUBJECT_LENGTH', 30);
}

//
// Converts the CDATA end sequence ]]> into ]]&gt;
//
function escape_cdata($str)
{
    return str_replace(']]>', ']]&gt;', $str);
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
        (!$password_is_hash && feather_hash($password) != $feather->user->password)) {
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
    exit($lang_common['No view']);
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
    global $feather_config, $feather;

    if (!$feather->user->is_guest) {
        return;
    }

    header('WWW-Authenticate: Basic realm="'.$feather_config['o_board_title'].' External Syndication"');
    header('HTTP/1.0 401 Unauthorized');
}


//
// Output $feed as RSS 2.0
//
function output_rss($feed)
{
    global $lang_common, $feather_config;

    // Send XML/no cache headers
    header('Content-Type: application/xml; charset=utf-8');
    header('Expires: '.gmdate('D, d M Y H:i:s').' GMT');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Pragma: public');

    echo '<?xml version="1.0" encoding="utf-8"?>'."\n";
    echo '<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">'."\n";
    echo "\t".'<channel>'."\n";
    echo "\t\t".'<atom:link href="'.feather_escape(get_current_url()).'" rel="self" type="application/rss+xml" />'."\n";
    echo "\t\t".'<title><![CDATA['.escape_cdata($feed['title']).']]></title>'."\n";
    echo "\t\t".'<link>'.feather_escape($feed['link']).'</link>'."\n";
    echo "\t\t".'<description><![CDATA['.escape_cdata($feed['description']).']]></description>'."\n";
    echo "\t\t".'<lastBuildDate>'.gmdate('r', count($feed['items']) ? $feed['items'][0]['pubdate'] : time()).'</lastBuildDate>'."\n";

    if ($feather_config['o_show_version'] == '1') {
        echo "\t\t".'<generator>FeatherBB '.$feather_config['o_cur_version'].'</generator>'."\n";
    } else {
        echo "\t\t".'<generator>FeatherBB</generator>'."\n";
    }

    foreach ($feed['items'] as $item) {
        echo "\t\t".'<item>'."\n";
        echo "\t\t\t".'<title><![CDATA['.escape_cdata($item['title']).']]></title>'."\n";
        echo "\t\t\t".'<link>'.feather_escape($item['link']).'</link>'."\n";
        echo "\t\t\t".'<description><![CDATA['.escape_cdata($item['description']).']]></description>'."\n";
        echo "\t\t\t".'<author><![CDATA['.(isset($item['author']['email']) ? escape_cdata($item['author']['email']) : 'dummy@example.com').' ('.escape_cdata($item['author']['name']).')]]></author>'."\n";
        echo "\t\t\t".'<pubDate>'.gmdate('r', $item['pubdate']).'</pubDate>'."\n";
        echo "\t\t\t".'<guid>'.feather_escape($item['link']).'</guid>'."\n";

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
    global $lang_common, $feather_config;

    // Send XML/no cache headers
    header('Content-Type: application/atom+xml; charset=utf-8');
    header('Expires: '.gmdate('D, d M Y H:i:s').' GMT');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Pragma: public');

    echo '<?xml version="1.0" encoding="utf-8"?>'."\n";
    echo '<feed xmlns="http://www.w3.org/2005/Atom">'."\n";

    echo "\t".'<title type="html"><![CDATA['.escape_cdata($feed['title']).']]></title>'."\n";
    echo "\t".'<link rel="self" href="'.feather_escape(get_current_url()).'"/>'."\n";
    echo "\t".'<link href="'.feather_escape($feed['link']).'"/>'."\n";
    echo "\t".'<updated>'.gmdate('Y-m-d\TH:i:s\Z', count($feed['items']) ? $feed['items'][0]['pubdate'] : time()).'</updated>'."\n";

    if ($feather_config['o_show_version'] == '1') {
        echo "\t".'<generator version="'.$feather_config['o_cur_version'].'">FluxBB</generator>'."\n";
    } else {
        echo "\t".'<generator>FluxBB</generator>'."\n";
    }

    echo "\t".'<id>'.feather_escape($feed['link']).'</id>'."\n";

    $content_tag = ($feed['type'] == 'posts') ? 'content' : 'summary';

    foreach ($feed['items'] as $item) {
        echo "\t".'<entry>'."\n";
        echo "\t\t".'<title type="html"><![CDATA['.escape_cdata($item['title']).']]></title>'."\n";
        echo "\t\t".'<link rel="alternate" href="'.feather_escape($item['link']).'"/>'."\n";
        echo "\t\t".'<'.$content_tag.' type="html"><![CDATA['.escape_cdata($item['description']).']]></'.$content_tag.'>'."\n";
        echo "\t\t".'<author>'."\n";
        echo "\t\t\t".'<name><![CDATA['.escape_cdata($item['author']['name']).']]></name>'."\n";

        if (isset($item['author']['email'])) {
            echo "\t\t\t".'<email><![CDATA['.escape_cdata($item['author']['email']).']]></email>'."\n";
        }

        if (isset($item['author']['uri'])) {
            echo "\t\t\t".'<uri>'.feather_escape($item['author']['uri']).'</uri>'."\n";
        }

        echo "\t\t".'</author>'."\n";
        echo "\t\t".'<updated>'.gmdate('Y-m-d\TH:i:s\Z', $item['pubdate']).'</updated>'."\n";

        echo "\t\t".'<id>'.feather_escape($item['link']).'</id>'."\n";
        echo "\t".'</entry>'."\n";
    }

    echo '</feed>'."\n";
}


//
// Output $feed as XML
//
function output_xml($feed)
{
    global $lang_common, $feather_config;

    // Send XML/no cache headers
    header('Content-Type: application/xml; charset=utf-8');
    header('Expires: '.gmdate('D, d M Y H:i:s').' GMT');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Pragma: public');

    echo '<?xml version="1.0" encoding="utf-8"?>'."\n";
    echo '<source>'."\n";
    echo "\t".'<url>'.feather_escape($feed['link']).'</url>'."\n";

    $forum_tag = ($feed['type'] == 'posts') ? 'post' : 'topic';

    foreach ($feed['items'] as $item) {
        echo "\t".'<'.$forum_tag.' id="'.$item['id'].'">'."\n";

        echo "\t\t".'<title><![CDATA['.escape_cdata($item['title']).']]></title>'."\n";
        echo "\t\t".'<link>'.feather_escape($item['link']).'</link>'."\n";
        echo "\t\t".'<content><![CDATA['.escape_cdata($item['description']).']]></content>'."\n";
        echo "\t\t".'<author>'."\n";
        echo "\t\t\t".'<name><![CDATA['.escape_cdata($item['author']['name']).']]></name>'."\n";

        if (isset($item['author']['email'])) {
            echo "\t\t\t".'<email><![CDATA['.escape_cdata($item['author']['email']).']]></email>'."\n";
        }

        if (isset($item['author']['uri'])) {
            echo "\t\t\t".'<uri>'.feather_escape($item['author']['uri']).'</uri>'."\n";
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
            $subject_truncated = feather_escape(feather_trim(utf8_substr($item['title'], 0, (FORUM_EXTERN_MAX_SUBJECT_LENGTH - 5)))).' …';
        } else {
            $subject_truncated = feather_escape($item['title']);
        }

        echo '<li><a href="'.feather_escape($item['link']).'" title="'.feather_escape($item['title']).'">'.$subject_truncated.'</a></li>'."\n";
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
            exit($lang_common['Bad request']);
        }

        if ($feather_config['o_censoring'] == '1') {
            $cur_topic['subject'] = censor_words($cur_topic['subject']);
        }

        // Setup the feed
        $feed = array(
            'title'        =>    $feather_config['o_board_title'].$lang_common['Title separator'].$cur_topic['subject'],
            'link'            =>    get_link('topic/'.$tid.'/'.url_friendly($cur_topic['subject']).'/'),
            'description'        =>    sprintf($lang_common['RSS description topic'], $cur_topic['subject']),
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
                'title'            =>    $cur_topic['first_post_id'] == $cur_post['id'] ? $cur_topic['subject'] : $lang_common['RSS reply'].$cur_topic['subject'],
                'link'            =>    get_link('post/'.$cur_post['id'].'/#p'.$cur_post['id']),
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

                $item['author']['uri'] = get_link('user/'.$cur_post['poster_id'].'/');
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
            $fids = explode(',', feather_trim($_GET['fid']));
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
                    $forum_name = $lang_common['Title separator'].$cur_topic;
                }
            }
        }

        // Any forum IDs to exclude?
        if (isset($_GET['nfid']) && is_scalar($_GET['nfid']) && $_GET['nfid'] != '') {
            $nfids = explode(',', feather_trim($_GET['nfid']));
            $nfids = array_map('intval', $nfids);

            if (!empty($nfids)) {
                $result = $result->where_not_in('t.forum_id', $nfids);
            }
        }

        // Only attempt to cache if caching is enabled and we have all or a single forum
        if ($feather_config['o_feed_ttl'] > 0 && ($forum_sql == '' || ($forum_name != '' && !isset($_GET['nfid'])))) {
            $cache_id = 'feed'.sha1($feather->user->g_id.'|'.$lang_common['lang_identifier'].'|'.($order_posted ? '1' : '0').($forum_name == '' ? '' : '|'.$fids[0]));
        }

        // Load cached feed
        if (isset($cache_id) && file_exists(FORUM_CACHE_DIR.'cache_'.$cache_id.'.php')) {
            include FORUM_CACHE_DIR.'cache_'.$cache_id.'.php';
        }

        $now = time();
        if (!isset($feed) || $cache_expire < $now) {
            // Setup the feed
            $feed = array(
                'title'        =>    $feather_config['o_board_title'].$forum_name,
                'link'            =>    '/index.php',
                'description'    =>    sprintf($lang_common['RSS description'], $feather_config['o_board_title']),
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
                if ($feather_config['o_censoring'] == '1') {
                    $cur_topic['subject'] = censor_words($cur_topic['subject']);
                }

                $cur_topic['message'] = parse_message($cur_topic['message'], $cur_topic['hide_smilies']);

                $item = array(
                    'id'            =>    $cur_topic['id'],
                    'title'            =>    $cur_topic['subject'],
                    'link'            =>    get_link('topic/'.$cur_topic['id'].'/'.url_friendly($cur_topic['subject']).'/').($order_posted ? '' : '/action/new/'),
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

                    $item['author']['uri'] = get_link('user/'.$cur_topic['poster_id'].'/');
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

                $content = '<?php'."\n\n".'$feed = '.var_export($feed, true).';'."\n\n".'$cache_expire = '.($now + ($feather_config['o_feed_ttl'] * 60)).';'."\n\n".'?>';
                featherbb_write_cache_file('cache_'.$cache_id.'.php', $content);
            }
        }

        // If we only want to show a few items but due to caching we have too many
        if (count($feed['items']) > $show) {
            $feed['items'] = array_slice($feed['items'], 0, $show);
        }

        // Prepend the current base URL onto some links. Done after caching to handle http/https correctly
        $feed['link'] = get_base_url(true).$feed['link'];

        foreach ($feed['items'] as $key => $item) {
            $feed['items'][$key]['link'] = get_base_url(true).$item['link'];

            if (isset($item['author']['uri'])) {
                $feed['items'][$key]['author']['uri'] = get_base_url(true).$item['author']['uri'];
            }
        }

        $output_func = 'output_'.$type;
        $output_func($feed);
    }

    exit;
}

// Show users online
elseif ($action == 'online' || $action == 'online_full') {
    // Load the index.php language file
    require FEATHER_ROOT.'lang/'.$feather_config['o_default_lang'].'/index.php';

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
            $users[] = ($feather->user->g_view_users == '1') ? '<a href="'.get_link('user/'.$feather_user_online['user_id'].'/').'">'.feather_escape($feather_user_online['ident']).'</a>' : feather_escape($feather_user_online['ident']);
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

    echo sprintf($lang_index['Guests online'], forum_number_format($num_guests)).'<br />'."\n";

    if ($action == 'online_full' && !empty($users)) {
        echo sprintf($lang_index['Users online'], implode(', ', $users)).'<br />'."\n";
    } else {
        echo sprintf($lang_index['Users online'], forum_number_format($num_users)).'<br />'."\n";
    }

    exit;
}

// Show board statistics
elseif ($action == 'stats') {
    // Load the index.php language file
    require FEATHER_ROOT.'lang/'.$feather_config['o_default_lang'].'/index.php';

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

    echo sprintf($lang_index['No of users'], forum_number_format($stats['total_users'])).'<br />'."\n";
    echo sprintf($lang_index['Newest user'], (($feather->user->g_view_users == '1') ? '<a href="'.get_link('user/'.$stats['last_user']['id'].'/').'">'.feather_escape($stats['last_user']['username']).'</a>' : feather_escape($stats['last_user']['username']))).'<br />'."\n";
    echo sprintf($lang_index['No of topics'], forum_number_format($stats['total_topics'])).'<br />'."\n";
    echo sprintf($lang_index['No of posts'], forum_number_format($stats['total_posts'])).'<br />'."\n";

    exit;
}
// If we end up here, the script was called with some wacky parameters
exit($lang_common['Bad request']);
