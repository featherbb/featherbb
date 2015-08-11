<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace model;

use DB;

class viewtopic
{
    public function __construct()
    {
        $this->feather = \Slim\Slim::getInstance();
        $this->start = $this->feather->start;
        $this->config = $this->feather->config;
        $this->user = $this->feather->user;
        $this->request = $this->feather->request;
    }
 
    // Redirects to a post in particular
    public function redirect_to_post($post_id)
    {
        global $lang_common;

        $select_info_topic = array('topic_id', 'posted');

        $result = DB::for_table('posts')
                      ->select_many($select_info_topic)
                      ->where('id', $post_id)
                      ->find_one();

        if (!$result) {
            message($lang_common['Bad request'], '404');
        }

        $post['topic_id'] = $result['topic_id'];
        $posted = $result['posted'];

        // Determine on which page the post is located (depending on $forum_user['disp_posts'])
        $num_posts = DB::for_table('posts')
                        ->where('topic_id', $post['topic_id'])
                        ->where_lt('posted', $posted)
                        ->count('id');

        $post['get_p'] = ceil(($num_posts + 1) / $this->user->disp_posts);

        return $post;
    }

    // Redirects to new posts or last post
    public function handle_actions($topic_id, $action)
    {
        
        // If action=new, we redirect to the first new post (if any)
        if ($action == 'new') {
            if (!$this->user->is_guest) {
                // We need to check if this topic has been viewed recently by the user
                $tracked_topics = get_tracked_topics();
                $last_viewed = isset($tracked_topics['topics'][$topic_id]) ? $tracked_topics['topics'][$topic_id] : $this->user->last_visit;

                $first_new_post_id = DB::for_table('posts')
                                        ->where('topic_id', $topic_id)
                                        ->where_gt('posted', $last_viewed)
                                        ->min('id');

                if ($first_new_post_id) {
                    header('Location: '.get_base_url().'/post/'.$first_new_post_id.'/#p'.$first_new_post_id);
                    exit;
                }
            }

            // If there is no new post, we go to the last post
            $action = 'last';
        }

        // If action=last, we redirect to the last post
        if ($action == 'last') {
            $last_post_id = DB::for_table('posts')
                                ->where('topic_id', $topic_id)
                                ->max('id');

            if ($last_post_id) {
                header('Location: '.get_base_url().'/post/'.$last_post_id.'/#p'.$last_post_id);
                exit;
            }
        }
    }

    // Gets some info about the topic
    public function get_info_topic($id)
    {
        global $lang_common;

        $where_get_info_topic = array(
            array('fp.read_forum' => 'IS NULL'),
            array('fp.read_forum' => '1')
        );

        if (!$this->user->is_guest) {
            $select_get_info_topic = array('t.subject', 't.closed', 't.num_replies', 't.sticky', 't.first_post_id', 'forum_id' => 'f.id', 'f.forum_name', 'f.moderators', 'fp.post_replies', 'is_subscribed' => 's.user_id');

            $cur_topic = DB::for_table('topics')
                            ->table_alias('t')
                            ->select_many($select_get_info_topic)
                            ->inner_join('forums', array('f.id', '=', 't.forum_id'), 'f')
                            ->left_outer_join('topic_subscriptions', array('t.id', '=', 's.topic_id'), 's')
                            ->left_outer_join('topic_subscriptions', array('s.user_id', '=', $this->user->id), null, true)
                            ->left_outer_join('forum_perms', array('fp.forum_id', '=', 'f.id'), 'fp')
                            ->left_outer_join('forum_perms', array('fp.group_id', '=', $this->user->g_id), null, true)
                            ->where_any_is($where_get_info_topic)
                            ->where('t.id', $id)
                            ->where_null('t.moved_to')
                            ->find_one();
        } else {
            $select_get_info_topic = array('t.subject', 't.closed', 't.num_replies', 't.sticky', 't.first_post_id', 'forum_id' => 'f.id', 'f.forum_name', 'f.moderators', 'fp.post_replies');

            $cur_topic = DB::for_table('topics')
                            ->table_alias('t')
                            ->select_many($select_get_info_topic)
                            ->select_expr(0, 'is_subscribed')
                            ->inner_join('forums', array('f.id', '=', 't.forum_id'), 'f')
                            ->left_outer_join('forum_perms', array('fp.forum_id', '=', 'f.id'), 'fp')
                            ->left_outer_join('forum_perms', array('fp.group_id', '=', $this->user->g_id), null, true)
                            ->where_any_is($where_get_info_topic)
                            ->where('t.id', $id)
                            ->where_null('t.moved_to')
                            ->find_one();
        }

        if (!$cur_topic) {
            message($lang_common['Bad request'], '404');
        }

        return $cur_topic;
    }

    // Generates the post link
    public function get_post_link($topic_id, $closed, $post_replies, $is_admmod)
    {
        global $lang_topic;

        if ($closed == '0') {
            if (($post_replies == '' && $this->user->g_post_replies == '1') || $post_replies == '1' || $is_admmod) {
                $post_link = "\t\t\t".'<p class="postlink conr"><a href="'.get_link('post/reply/'.$topic_id.'/').'">'.$lang_topic['Post reply'].'</a></p>'."\n";
            } else {
                $post_link = '';
            }
        } else {
            $post_link = $lang_topic['Topic closed'];

            if ($is_admmod) {
                $post_link .= ' / <a href="'.get_link('post/reply/'.$topic_id.'/').'">'.$lang_topic['Post reply'].'</a>';
            }

            $post_link = "\t\t\t".'<p class="postlink conr">'.$post_link.'</p>'."\n";
        }

        return $post_link;
    }

    // Should we display the quickpost?
    public function is_quickpost($post_replies, $closed, $is_admmod)
    {
        global $lang_common;

        $quickpost = false;
        if ($this->config['o_quickpost'] == '1' && ($post_replies == '1' || ($post_replies == '' && $this->user->g_post_replies == '1')) && ($closed == '0' || $is_admmod)) {
            // Load the post.php language file
            require FEATHER_ROOT.'lang/'.$this->user->language.'/post.php';

            $required_fields = array('req_message' => $lang_common['Message']);
            if ($this->user->is_guest) {
                $required_fields['req_username'] = $lang_post['Guest name'];
                if ($this->config['p_force_guest_email'] == '1') {
                    $required_fields['req_email'] = $lang_common['Email'];
                }
            }
            $quickpost = true;
        }

        return $quickpost;
    }

    // Subscraction link
    public function get_subscraction($is_subscribed, $topic_id)
    {
        global $lang_topic;

        if (!$this->user->is_guest && $this->config['o_topic_subscriptions'] == '1') {
            if ($is_subscribed) {
                // I apologize for the variable naming here. It's a mix of subscription and action I guess :-)
                $subscraction = "\t\t".'<p class="subscribelink clearb"><span>'.$lang_topic['Is subscribed'].' - </span><a href="'.get_link('unsubscribe/topic/'.$topic_id.'/').'">'.$lang_topic['Unsubscribe'].'</a></p>'."\n";
            } else {
                $subscraction = "\t\t".'<p class="subscribelink clearb"><a href="'.get_link('subscribe/topic/'.$topic_id.'/').'">'.$lang_topic['Subscribe'].'</a></p>'."\n";
            }
        } else {
            $subscraction = '';
        }

        return $subscraction;
    }

    // Adds relationship meta tags
    public function get_page_head($topic_id, $num_pages, $p, $url_topic)
    {
        global $lang_common;

        $page_head = array();
        $page_head['canonical'] = "\t".'<link href="'.get_link('topic/'.$topic_id.'/'.$url_topic.'/').'" rel="canonical" />';

        if ($num_pages > 1) {
            if ($p > 1) {
                $page_head['prev'] = "\t".'<link href="'.get_link('topic/'.$topic_id.'/'.$url_topic.'/page/'.($p - 1).'/').'" rel="prev" />';
            }
            if ($p < $num_pages) {
                $page_head['next'] = "\t".'<link href="'.get_link('topic/'.$topic_id.'/'.$url_topic.'/page/'.($p + 1).'/').'" rel="next" />';
            }
        }

        if ($this->config['o_feed_type'] == '1') {
            $page_head['feed'] = '<link rel="alternate" type="application/rss+xml" href="extern.php?action=feed&amp;tid='.$topic_id.'&amp;type=rss" title="'.$lang_common['RSS topic feed'].'" />';
        } elseif ($this->config['o_feed_type'] == '2') {
            $page_head['feed'] = '<link rel="alternate" type="application/atom+xml" href="extern.php?action=feed&amp;tid='.$topic_id.'&amp;type=atom" title="'.$lang_common['Atom topic feed'].'" />';
        }

        return $page_head;
    }

    // Prints the posts
    public function print_posts($topic_id, $start_from, $cur_topic, $is_admmod)
    {
        global $lang_topic, $lang_common, $pd;

        $post_data = array();

        $post_count = 0; // Keep track of post numbers

        // Retrieve a list of post IDs, LIMIT is (really) expensive so we only fetch the IDs here then later fetch the remaining data
        $result = DB::for_table('posts')->select('id')
                    ->where('topic_id', $topic_id)
                    ->order_by('id')
                    ->limit($this->user->disp_topics)
                    ->offset($start_from)
                    ->find_many();

        $post_ids = array();
        foreach ($result as $cur_post_id) {
            $post_ids[] = $cur_post_id['id'];
        }

        if (empty($post_ids)) {
            error('The post table and topic table seem to be out of sync!', __FILE__, __LINE__);
        }

        // Retrieve the posts (and their respective poster/online status)
        $select_print_posts = array('u.email', 'u.title', 'u.url', 'u.location', 'u.signature', 'u.email_setting', 'u.num_posts', 'u.registered', 'u.admin_note', 'p.id','username' => 'p.poster', 'p.poster_id', 'p.poster_ip', 'p.poster_email', 'p.message', 'p.hide_smilies', 'p.posted', 'p.edited', 'p.edited_by', 'g.g_id', 'g.g_user_title', 'g.g_promote_next_group', 'is_online' => 'o.user_id');

        $result = DB::for_table('posts')
            ->table_alias('p')
            ->select_many($select_print_posts)
            ->inner_join('users', array('u.id', '=', 'p.poster_id'), 'u')
            ->inner_join('groups', array('g.g_id', '=', 'u.group_id'), 'g')
            ->raw_join('LEFT OUTER JOIN '.$this->feather->prefix.'online', "o.user_id!=1 AND o.idle=0 AND o.user_id=u.id", 'o')
            ->where_in('p.id', $post_ids)
            ->order_by('p.id')
            ->find_array();

        foreach($result as $cur_post) {
            $post_count++;
            $cur_post['user_avatar'] = '';
            $cur_post['user_info'] = array();
            $cur_post['user_contacts'] = array();
            $cur_post['post_actions'] = array();
            $cur_post['is_online_formatted'] = '';
            $cur_post['signature_formatted'] = '';

            // If the poster is a registered user
            if ($cur_post['poster_id'] > 1) {
                if ($this->user->g_view_users == '1') {
                    $cur_post['username_formatted'] = '<a href="'.get_base_url().'/user/'.$cur_post['poster_id'].'/">'.feather_escape($cur_post['username']).'</a>';
                } else {
                    $cur_post['username_formatted'] = feather_escape($cur_post['username']);
                }

                $cur_post['user_title_formatted'] = get_title($cur_post);

                if ($this->config['o_censoring'] == '1') {
                    $cur_post['user_title_formatted'] = censor_words($cur_post['user_title_formatted']);
                }

                // Format the online indicator
                $cur_post['is_online_formatted'] = ($cur_post['is_online'] == $cur_post['poster_id']) ? '<strong>'.$lang_topic['Online'].'</strong>' : '<span>'.$lang_topic['Offline'].'</span>';

                if ($this->config['o_avatars'] == '1' && $this->user->show_avatars != '0') {
                    if (isset($avatar_cache[$cur_post['poster_id']])) {
                        $cur_post['user_avatar'] = $avatar_cache[$cur_post['poster_id']];
                    } else {
                        $cur_post['user_avatar'] = $avatar_cache[$cur_post['poster_id']] = generate_avatar_markup($cur_post['poster_id']);
                    }
                }

                // We only show location, register date, post count and the contact links if "Show user info" is enabled
                if ($this->config['o_show_user_info'] == '1') {
                    if ($cur_post['location'] != '') {
                        if ($this->config['o_censoring'] == '1') {
                            $cur_post['location'] = censor_words($cur_post['location']);
                        }

                        $cur_post['user_info'][] = '<dd><span>'.$lang_topic['From'].' '.feather_escape($cur_post['location']).'</span></dd>';
                    }

                    $cur_post['user_info'][] = '<dd><span>'.$lang_topic['Registered'].' '.format_time($cur_post['registered'], true).'</span></dd>';

                    if ($this->config['o_show_post_count'] == '1' || $this->user->is_admmod) {
                        $cur_post['user_info'][] = '<dd><span>'.$lang_topic['Posts'].' '.forum_number_format($cur_post['num_posts']).'</span></dd>';
                    }

                    // Now let's deal with the contact links (Email and URL)
                    if ((($cur_post['email_setting'] == '0' && !$this->user->is_guest) || $this->user->is_admmod) && $this->user->g_send_email == '1') {
                        $cur_post['user_contacts'][] = '<span class="email"><a href="mailto:'.feather_escape($cur_post['email']).'">'.$lang_common['Email'].'</a></span>';
                    } elseif ($cur_post['email_setting'] == '1' && !$this->user->is_guest && $this->user->g_send_email == '1') {
                        $cur_post['user_contacts'][] = '<span class="email"><a href="'.get_link('mail/'.$cur_post['poster_id'].'/').'">'.$lang_common['Email'].'</a></span>';
                    }

                    if ($cur_post['url'] != '') {
                        if ($this->config['o_censoring'] == '1') {
                            $cur_post['url'] = censor_words($cur_post['url']);
                        }

                        $cur_post['user_contacts'][] = '<span class="website"><a href="'.feather_escape($cur_post['url']).'" rel="nofollow">'.$lang_topic['Website'].'</a></span>';
                    }
                }

                if ($this->user->g_id == FEATHER_ADMIN || ($this->user->g_moderator == '1' && $this->user->g_mod_promote_users == '1')) {
                    if ($cur_post['g_promote_next_group']) {
                        $cur_post['user_info'][] = '<dd><span><a href="'.get_base_url().'/user/'.$cur_post['poster_id'].'/action/promote/pid/'.$cur_post['id'].'">'.$lang_topic['Promote user'].'</a></span></dd>';
                    }
                }

                if ($this->user->is_admmod) {
                    $cur_post['user_info'][] = '<dd><span><a href="'.get_link('moderate/get-host/post/'.$cur_post['id'].'/').'" title="'.feather_escape($cur_post['poster_ip']).'">'.$lang_topic['IP address logged'].'</a></span></dd>';

                    if ($cur_post['admin_note'] != '') {
                        $cur_post['user_info'][] = '<dd><span>'.$lang_topic['Note'].' <strong>'.feather_escape($cur_post['admin_note']).'</strong></span></dd>';
                    }
                }
            }
            // If the poster is a guest (or a user that has been deleted)
            else {
                $cur_post['username_formatted'] = feather_escape($cur_post['username']);
                $cur_post['user_title_formatted'] = get_title($cur_post);

                if ($this->user->is_admmod) {
                    $cur_post['user_info'][] = '<dd><span><a href="moderate.php?get_host='.$cur_post['id'].'" title="'.feather_escape($cur_post['poster_ip']).'">'.$lang_topic['IP address logged'].'</a></span></dd>';
                }

                if ($this->config['o_show_user_info'] == '1' && $cur_post['poster_email'] != '' && !$this->user->is_guest && $this->user->g_send_email == '1') {
                    $cur_post['user_contacts'][] = '<span class="email"><a href="mailto:'.feather_escape($cur_post['poster_email']).'">'.$lang_common['Email'].'</a></span>';
                }
            }

            // Generation post action array (quote, edit, delete etc.)
            if (!$is_admmod) {
                if (!$this->user->is_guest) {
                    $cur_post['post_actions'][] = '<li class="postreport"><span><a href="'.get_link('report/'.$cur_post['id'].'/').'">'.$lang_topic['Report'].'</a></span></li>';
                }

                if ($cur_topic['closed'] == '0') {
                    if ($cur_post['poster_id'] == $this->user->id) {
                        if ((($start_from + $post_count) == 1 && $this->user->g_delete_topics == '1') || (($start_from + $post_count) > 1 && $this->user->g_delete_posts == '1')) {
                            $cur_post['post_actions'][] = '<li class="postdelete"><span><a href="'.get_link('edit/'.$cur_post['id'].'/').'">'.$lang_topic['Delete'].'</a></span></li>';
                        }
                        if ($this->user->g_edit_posts == '1') {
                            $cur_post['post_actions'][] = '<li class="postedit"><span><a href="'.get_link('edit/'.$cur_post['id'].'/').'">'.$lang_topic['Edit'].'</a></span></li>';
                        }
                    }

                    if (($cur_topic['post_replies'] == '' && $this->user->g_post_replies == '1') || $cur_topic['post_replies'] == '1') {
                        $cur_post['post_actions'][] = '<li class="postquote"><span><a href="'.get_link('post/reply/'.$topic_id.'/quote/'.$cur_post['id'].'/').'">'.$lang_topic['Quote'].'</a></span></li>';
                    }
                }
            } else {
                $cur_post['post_actions'][] = '<li class="postreport"><span><a href="'.get_link('report/'.$cur_post['id'].'/').'">'.$lang_topic['Report'].'</a></span></li>';
                if ($this->user->g_id == FEATHER_ADMIN || !in_array($cur_post['poster_id'], $admin_ids)) {
                    $cur_post['post_actions'][] = '<li class="postdelete"><span><a href="'.get_link('delete/'.$cur_post['id'].'/').'">'.$lang_topic['Delete'].'</a></span></li>';
                    $cur_post['post_actions'][] = '<li class="postedit"><span><a href="'.get_link('edit/'.$cur_post['id'].'/').'">'.$lang_topic['Edit'].'</a></span></li>';
                }
                $cur_post['post_actions'][] = '<li class="postquote"><span><a href="'.get_link('post/reply/'.$topic_id.'/quote/'.$cur_post['id'].'/').'">'.$lang_topic['Quote'].'</a></span></li>';
            }

            // Perform the main parsing of the message (BBCode, smilies, censor words etc)
            $cur_post['message'] = parse_message($cur_post['message'], $cur_post['hide_smilies']);

            // Do signature parsing/caching
            if ($this->config['o_signatures'] == '1' && $cur_post['signature'] != '' && $this->user->show_sig != '0') {
                if (isset($avatar_cache[$cur_post['poster_id']])) {
                    $cur_post['signature_formatted'] = $avatar_cache[$cur_post['poster_id']];
                } else {
                    $cur_post['signature_formatted'] = parse_signature($cur_post['signature']);
                    $avatar_cache[$cur_post['poster_id']] = $cur_post['signature_formatted'];
                }
            }

            $post_data[] = $cur_post;
        }

        return $post_data;
    }
    
    public function increment_views($id)
    {
        if ($this->config['o_topic_views'] == '1') {
            DB::for_table('topics')->where('id', $id)
                ->find_one()
                ->set_expr('num_views', 'num_views+1')
                ->save();
        }
    }
}
