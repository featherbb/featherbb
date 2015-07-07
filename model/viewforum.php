<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace model;

class viewforum
{
    public function __construct()
    {
        $this->feather = \Slim\Slim::getInstance();
        $this->db = $this->feather->db;
        $this->start = $this->feather->start;
        $this->config = $this->feather->config;
        $this->user = $this->feather->user;
    }
 
    // Returns basic informations about the forum
    public function get_info_forum($id)
    {
        global $lang_common;

        if (!$this->user['is_guest']) {
            $result = $this->db->query('SELECT f.forum_name, f.redirect_url, f.moderators, f.num_topics, f.sort_by, fp.post_topics, s.user_id AS is_subscribed FROM '.$this->db->prefix.'forums AS f LEFT JOIN '.$this->db->prefix.'forum_subscriptions AS s ON (f.id=s.forum_id AND s.user_id='.$this->user['id'].') LEFT JOIN '.$this->db->prefix.'forum_perms AS fp ON (fp.forum_id=f.id AND fp.group_id='.$this->user['g_id'].') WHERE (fp.read_forum IS NULL OR fp.read_forum=1) AND f.id='.$id) or error('Unable to fetch forum info', __FILE__, __LINE__, $this->db->error());
        } else {
            $result = $this->db->query('SELECT f.forum_name, f.redirect_url, f.moderators, f.num_topics, f.sort_by, fp.post_topics, 0 AS is_subscribed FROM '.$this->db->prefix.'forums AS f LEFT JOIN '.$this->db->prefix.'forum_perms AS fp ON (fp.forum_id=f.id AND fp.group_id='.$this->user['g_id'].') WHERE (fp.read_forum IS NULL OR fp.read_forum=1) AND f.id='.$id) or error('Unable to fetch forum info', __FILE__, __LINE__, $this->db->error());
        }

        if (!$this->db->num_rows($result)) {
            message($lang_common['Bad request'], false, '404 Not Found');
        }

        $cur_forum = $this->db->fetch_assoc($result);

        return $cur_forum;
    }

    // Returns the text required by the query to sort the forum
    public function sort_forum_by($sort_by_sql)
    {
        switch ($sort_by_sql) {
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

    // Adds relationship meta tags
    public function get_page_head($forum_id, $num_pages, $p, $url_forum)
    {
        global $lang_common;

        $page_head = array();
        $page_head['canonical'] = "\t".'<link href="'.get_link('forum/'.$forum_id.'/'.$url_forum.'/').'" rel="canonical" />';

        if ($num_pages > 1) {
            if ($p > 1) {
                $page_head['prev'] = "\t".'<link href="'.get_link('forum/'.$forum_id.'/'.$url_forum.'/page/'.($p - 1).'/').'" rel="prev" />';
            }
            if ($p < $num_pages) {
                $page_head['next'] = "\t".'<link href="'.get_link('forum/'.$forum_id.'/'.$url_forum.'/page/'.($p + 1).'/').'" rel="next" />';
            }
        }

        if ($this->config['o_feed_type'] == '1') {
            $page_head['feed'] = '<link rel="alternate" type="application/rss+xml" href="extern.php?action=feed&amp;fid='.$forum_id.'&amp;type=rss" title="'.$lang_common['RSS forum feed'].'" />';
        } elseif ($this->config['o_feed_type'] == '2') {
            $page_head['feed'] = '<link rel="alternate" type="application/atom+xml" href="extern.php?action=feed&amp;fid='.$forum_id.'&amp;type=atom" title="'.$lang_common['Atom forum feed'].'" />';
        }

        return $page_head;
    }

    // Returns forum action
    public function get_forum_actions($forum_id, $subscriptions, $is_subscribed)
    {
        global $lang_forum, $lang_common;

        $forum_actions = array();

        if (!$this->user['is_guest']) {
            if ($subscriptions == 1) {
                if ($is_subscribed) {
                    $forum_actions[] = '<span>'.$lang_forum['Is subscribed'].' - </span><a href="'.get_link('unsubscribe/forum/'.$forum_id.'/').'">'.$lang_forum['Unsubscribe'].'</a>';
                } else {
                    $forum_actions[] = '<a href="'.get_link('subscribe/forum/'.$forum_id.'/').'">'.$lang_forum['Subscribe'].'</a>';
                }
            }

            $forum_actions[] = '<a href="'.get_link('mark-forum-read/'.$forum_id.'/').'">'.$lang_common['Mark forum read'].'</a>';
        }

        return $forum_actions;
    }

    // Returns the elements needed to display topics
    public function print_topics($forum_id, $sort_by, $start_from)
    {
        global $lang_common, $lang_forum;

        // Get topic/forum tracking data
        if (!$this->user['is_guest']) {
            $tracked_topics = get_tracked_topics();
        }

        // Retrieve a list of topic IDs, LIMIT is (really) expensive so we only fetch the IDs here then later fetch the remaining data
        $result = $this->db->query('SELECT id FROM '.$this->db->prefix.'topics WHERE forum_id='.$forum_id.' ORDER BY sticky DESC, '.$sort_by.', id DESC LIMIT '.$start_from.', '.$this->user['disp_topics']) or error('Unable to fetch topic IDs', __FILE__, __LINE__, $this->db->error());

        $forum_data = array();

        // If there are topics in this forum
        if ($this->db->num_rows($result)) {
            $topic_ids = array();
            for ($i = 0; $cur_topic_id = $this->db->result($result, $i); $i++) {
                $topic_ids[] = $cur_topic_id;
            }

            // Fetch list of topics to display on this page
            if ($this->user['is_guest'] || $this->config['o_show_dot'] == '0') {
                // Without "the dot"
                $sql = 'SELECT id, poster, subject, posted, last_post, last_post_id, last_poster, num_views, num_replies, closed, sticky, moved_to FROM '.$this->db->prefix.'topics WHERE id IN('.implode(',', $topic_ids).') ORDER BY sticky DESC, '.$sort_by.', id DESC';
            } else {
                // With "the dot"
                $sql = 'SELECT p.poster_id AS has_posted, t.id, t.subject, t.poster, t.posted, t.last_post, t.last_post_id, t.last_poster, t.num_views, t.num_replies, t.closed, t.sticky, t.moved_to FROM '.$this->db->prefix.'topics AS t LEFT JOIN '.$this->db->prefix.'posts AS p ON t.id=p.topic_id AND p.poster_id='.$this->user['id'].' WHERE t.id IN('.implode(',', $topic_ids).') GROUP BY t.id'.($db_type == 'pgsql' ? ', t.subject, t.poster, t.posted, t.last_post, t.last_post_id, t.last_poster, t.num_views, t.num_replies, t.closed, t.sticky, t.moved_to, p.poster_id' : '').' ORDER BY t.sticky DESC, t.'.$sort_by.', t.id DESC';
            }

            $result = $this->db->query($sql) or error('Unable to fetch topic list', __FILE__, __LINE__, $this->db->error());

            $topic_count = 0;
            while ($cur_topic = $this->db->fetch_assoc($result)) {
                ++$topic_count;
                $status_text = array();
                $cur_topic['item_status'] = ($topic_count % 2 == 0) ? 'roweven' : 'rowodd';
                $cur_topic['icon_type'] = 'icon';
                $url_subject = url_friendly($cur_topic['subject']);

                if (is_null($cur_topic['moved_to'])) {
                    $cur_topic['last_post_formatted'] = '<a href="'.get_link('post/'.$cur_topic['last_post_id'].'/#p'.$cur_topic['last_post_id']).'">'.format_time($cur_topic['last_post']).'</a> <span class="byuser">'.$lang_common['by'].' '.pun_htmlspecialchars($cur_topic['last_poster']).'</span>';
                } else {
                    $cur_topic['last_post_formatted'] = '- - -';
                }

                if ($this->config['o_censoring'] == '1') {
                    $cur_topic['subject'] = censor_words($cur_topic['subject']);
                }

                if ($cur_topic['sticky'] == '1') {
                    $cur_topic['item_status'] .= ' isticky';
                    $status_text[] = '<span class="stickytext">'.$lang_forum['Sticky'].'</span>';
                }

                if ($cur_topic['moved_to'] != 0) {
                    $cur_topic['subject_formatted'] = '<a href="'.get_link('topic/'.$cur_topic['moved_to'].'/'.$url_subject.'/').'">'.pun_htmlspecialchars($cur_topic['subject']).'</a> <span class="byuser">'.$lang_common['by'].' '.pun_htmlspecialchars($cur_topic['poster']).'</span>';
                    $status_text[] = '<span class="movedtext">'.$lang_forum['Moved'].'</span>';
                    $cur_topic['item_status'] .= ' imoved';
                } elseif ($cur_topic['closed'] == '0') {
                    $cur_topic['subject_formatted'] = '<a href="'.get_link('topic/'.$cur_topic['id'].'/'.$url_subject.'/').'">'.pun_htmlspecialchars($cur_topic['subject']).'</a> <span class="byuser">'.$lang_common['by'].' '.pun_htmlspecialchars($cur_topic['poster']).'</span>';
                } else {
                    $cur_topic['subject_formatted'] = '<a href="'.get_link('topic/'.$cur_topic['id'].'/'.$url_subject.'/').'">'.pun_htmlspecialchars($cur_topic['subject']).'</a> <span class="byuser">'.$lang_common['by'].' '.pun_htmlspecialchars($cur_topic['poster']).'</span>';
                    $status_text[] = '<span class="closedtext">'.$lang_forum['Closed'].'</span>';
                    $cur_topic['item_status'] .= ' iclosed';
                }

                if (!$this->user['is_guest'] && $cur_topic['last_post'] > $this->user['last_visit'] && (!isset($tracked_topics['topics'][$cur_topic['id']]) || $tracked_topics['topics'][$cur_topic['id']] < $cur_topic['last_post']) && (!isset($tracked_topics['forums'][$forum_id]) || $tracked_topics['forums'][$forum_id] < $cur_topic['last_post']) && is_null($cur_topic['moved_to'])) {
                    $cur_topic['item_status'] .= ' inew';
                    $cur_topic['icon_type'] = 'icon icon-new';
                    $cur_topic['subject_formatted'] = '<strong>'.$cur_topic['subject_formatted'].'</strong>';
                    $subject_new_posts = '<span class="newtext">[ <a href="'.get_link('topic/'.$cur_topic['id'].'/action/new/').'" title="'.$lang_common['New posts info'].'">'.$lang_common['New posts'].'</a> ]</span>';
                } else {
                    $subject_new_posts = null;
                }

                // Insert the status text before the subject
                $cur_topic['subject_formatted'] = implode(' ', $status_text).' '.$cur_topic['subject_formatted'];

                // Should we display the dot or not? :)
                if (!$this->user['is_guest'] && $this->config['o_show_dot'] == '1') {
                    if ($cur_topic['has_posted'] == $this->user['id']) {
                        $cur_topic['subject_formatted'] = '<strong class="ipost">·&#160;</strong>'.$cur_topic['subject_formatted'];
                        $cur_topic['item_status'] .= ' iposted';
                    }
                }

                $num_pages_topic = ceil(($cur_topic['num_replies'] + 1) / $this->user['disp_posts']);

                if ($num_pages_topic > 1) {
                    $subject_multipage = '<span class="pagestext">[ '.paginate($num_pages_topic, -1, 'topic/'.$cur_topic['id'].'/'.$url_subject.'/#').' ]</span>';
                } else {
                    $subject_multipage = null;
                }

                // Should we show the "New posts" and/or the multipage links?
                if (!empty($subject_new_posts) || !empty($subject_multipage)) {
                    $cur_topic['subject_formatted'] .= !empty($subject_new_posts) ? ' '.$subject_new_posts : '';
                    $cur_topic['subject_formatted'] .= !empty($subject_multipage) ? ' '.$subject_multipage : '';
                }

                $forum_data[] = $cur_topic;
            }
        }

        return $forum_data;
    }
}