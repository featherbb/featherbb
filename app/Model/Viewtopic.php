<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace App\Model;

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
        $this->hook = $this->feather->hooks;
    }

    // Redirect to a post in particular
    public function redirect_to_post($post_id)
    {
        $post_id = $this->hook->fire('redirect_to_post', $post_id);

        $result['select'] = array('topic_id', 'posted');

        $result = DB::for_table('posts')
                      ->select_many($result['select'])
                      ->where('id', $post_id);
        $result = $this->hook->fireDB('redirect_to_post_query', $result);
        $result = $result->find_one();

        if (!$result) {
            throw new \FeatherBB\Error(__('Bad request'), 404);
        }

        $post['topic_id'] = $result['topic_id'];
        $posted = $result['posted'];

        // Determine on which page the post is located (depending on $forum_user['disp_posts'])
        $num_posts = DB::for_table('posts')
                        ->where('topic_id', $post['topic_id'])
                        ->where_lt('posted', $posted)
                        ->count('id');

        $num_posts = $this->hook->fire('redirect_to_post_num', $num_posts);

        $post['get_p'] = ceil(($num_posts + 1) / $this->user->disp_posts);

        $post = $this->hook->fire('redirect_to_post', $post);

        return $post;
    }

    // Redirect to new posts or last post
    public function handle_actions($topic_id, $action)
    {
        $action = $this->hook->fire('handle_actions_start', $action, $topic_id);

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

                $first_new_post_id = $this->hook->fire('handle_actions_first_new', $first_new_post_id);

                if ($first_new_post_id) {
                    header('Location: '.$this->feather->url->base().'/post/'.$first_new_post_id.'/#p'.$first_new_post_id);
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

            $last_post_id = $this->hook->fire('handle_actions_last_post', $last_post_id);

            if ($last_post_id) {
                header('Location: '.$this->feather->url->base().'/post/'.$last_post_id.'/#p'.$last_post_id);
                exit;
            }
        }

        $this->hook->fire('handle_actions', $action, $topic_id);
    }

    // Gets some info about the topic
    public function get_info_topic($id)
    {
        $cur_topic['where'] = array(
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
                            ->where_any_is($cur_topic['where'])
                            ->where('t.id', $id)
                            ->where_null('t.moved_to');
        } else {
            $select_get_info_topic = array('t.subject', 't.closed', 't.num_replies', 't.sticky', 't.first_post_id', 'forum_id' => 'f.id', 'f.forum_name', 'f.moderators', 'fp.post_replies');

            $cur_topic = DB::for_table('topics')
                            ->table_alias('t')
                            ->select_many($select_get_info_topic)
                            ->select_expr(0, 'is_subscribed')
                            ->inner_join('forums', array('f.id', '=', 't.forum_id'), 'f')
                            ->left_outer_join('forum_perms', array('fp.forum_id', '=', 'f.id'), 'fp')
                            ->left_outer_join('forum_perms', array('fp.group_id', '=', $this->user->g_id), null, true)
                            ->where_any_is($cur_topic['where'])
                            ->where('t.id', $id)
                            ->where_null('t.moved_to');
        }

        $cur_topic = $this->hook->fireDB('get_info_topic_query', $cur_topic);
        $cur_topic = $cur_topic->find_one();

        if (!$cur_topic) {
            throw new \FeatherBB\Error(__('Bad request'), 404);
        }

        $cur_topic = $this->hook->fire('get_info_topic', $cur_topic);

        return $cur_topic;
    }

    // Generates the post link
    public function get_post_link($topic_id, $closed, $post_replies, $is_admmod)
    {
        $closed = $this->hook->fire('get_post_link_start', $closed, $topic_id, $post_replies, $is_admmod);

        if ($closed == '0') {
            if (($post_replies == '' && $this->user->g_post_replies == '1') || $post_replies == '1' || $is_admmod) {
                $post_link = "\t\t\t".'<p class="postlink conr"><a href="'.$this->feather->url->get('post/reply/'.$topic_id.'/').'">'.__('Post reply').'</a></p>'."\n";
            } else {
                $post_link = '';
            }
        } else {
            $post_link = __('Topic closed');

            if ($is_admmod) {
                $post_link .= ' / <a href="'.$this->feather->url->get('post/reply/'.$topic_id.'/').'">'.__('Post reply').'</a>';
            }

            $post_link = "\t\t\t".'<p class="postlink conr">'.$post_link.'</p>'."\n";
        }

        $post_link = $this->hook->fire('get_post_link_start', $post_link, $topic_id, $closed, $post_replies, $is_admmod);

        return $post_link;
    }

    // Should we display the quickpost?
    public function is_quickpost($post_replies, $closed, $is_admmod)
    {
        $quickpost = false;
        if ($this->config['o_quickpost'] == '1' && ($post_replies == '1' || ($post_replies == '' && $this->user->g_post_replies == '1')) && ($closed == '0' || $is_admmod)) {

            $required_fields = array('req_message' => __('Message'));
            if ($this->user->is_guest) {
                $required_fields['req_username'] = __('Guest name');
                if ($this->config['p_force_guest_email'] == '1') {
                    $required_fields['req_email'] = __('Email');
                }
            }
            $quickpost = true;
        }

        $quickpost = $this->hook->fire('is_quickpost', $quickpost, $post_replies, $closed, $is_admmod);

        return $quickpost;
    }

    // Subscraction link
    public function get_subscraction($is_subscribed, $topic_id)
    {
        if (!$this->user->is_guest && $this->config['o_topic_subscriptions'] == '1') {
            if ($is_subscribed) {
                // I apologize for the variable naming here. It's a mix of subscription and action I guess :-)
                $subscraction = "\t\t".'<p class="subscribelink clearb"><span>'.__('Is subscribed').' - </span><a href="'.$this->feather->url->get('unsubscribe/topic/'.$topic_id.'/').'">'.__('Unsubscribe').'</a></p>'."\n";
            } else {
                $subscraction = "\t\t".'<p class="subscribelink clearb"><a href="'.$this->feather->url->get('subscribe/topic/'.$topic_id.'/').'">'.__('Subscribe').'</a></p>'."\n";
            }
        } else {
            $subscraction = '';
        }

        $subscraction = $this->hook->fire('get_subscraction', $subscraction, $is_subscribed, $topic_id);

        return $subscraction;
    }

    // Prints the posts
    public function print_posts($topic_id, $start_from, $cur_topic, $is_admmod)
    {
        global $pd;

        $post_data = array();

        $post_data = $this->hook->fire('print_posts_start', $post_data, $topic_id, $start_from, $cur_topic, $is_admmod);

        $post_count = 0; // Keep track of post numbers

        // Retrieve a list of post IDs, LIMIT is (really) expensive so we only fetch the IDs here then later fetch the remaining data
        $result = DB::for_table('posts')
                    ->select('id')
                    ->where('topic_id', $topic_id)
                    ->order_by('id')
                    ->limit($this->user->disp_topics)
                    ->offset($start_from);
        $result = $this->hook->fireDB('print_posts_ids_query', $result);
        $result = $result->find_many();

        $post_ids = array();
        foreach ($result as $cur_post_id) {
            $post_ids[] = $cur_post_id['id'];
        }

        if (empty($post_ids)) {
            throw new \FeatherBB\Error('The post table and topic table seem to be out of sync!', 500);
        }

        // Retrieve the posts (and their respective poster/online status)
        $result['select'] = array('u.email', 'u.title', 'u.url', 'u.location', 'u.signature', 'u.email_setting', 'u.num_posts', 'u.registered', 'u.admin_note', 'p.id','username' => 'p.poster', 'p.poster_id', 'p.poster_ip', 'p.poster_email', 'p.message', 'p.hide_smilies', 'p.posted', 'p.edited', 'p.edited_by', 'g.g_id', 'g.g_user_title', 'g.g_promote_next_group', 'is_online' => 'o.user_id');

        $result = DB::for_table('posts')
                    ->table_alias('p')
                    ->select_many($result['select'])
                    ->inner_join('users', array('u.id', '=', 'p.poster_id'), 'u')
                    ->inner_join('groups', array('g.g_id', '=', 'u.group_id'), 'g')
                    ->raw_join('LEFT OUTER JOIN '.$this->feather->forum_settings['db_prefix'].'online', "o.user_id!=1 AND o.idle=0 AND o.user_id=u.id", 'o')
                    ->where_in('p.id', $post_ids)
                    ->order_by('p.id');
        $result = $this->hook->fireDB('print_posts_query', $result);
        $result = $result->find_array();

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
                    $cur_post['username_formatted'] = '<a href="'.$this->feather->url->base().'/user/'.$cur_post['poster_id'].'/">'.$this->feather->utils->escape($cur_post['username']).'</a>';
                } else {
                    $cur_post['username_formatted'] = $this->feather->utils->escape($cur_post['username']);
                }

                $cur_post['user_title_formatted'] = get_title($cur_post);

                if ($this->config['o_censoring'] == '1') {
                    $cur_post['user_title_formatted'] = censor_words($cur_post['user_title_formatted']);
                }

                // Format the online indicator
                $cur_post['is_online_formatted'] = ($cur_post['is_online'] == $cur_post['poster_id']) ? '<strong>'.__('Online').'</strong>' : '<span>'.__('Offline').'</span>';

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

                        $cur_post['user_info'][] = '<dd><span>'.__('From').' '.$this->feather->utils->escape($cur_post['location']).'</span></dd>';
                    }

                    $cur_post['user_info'][] = '<dd><span>'.__('Registered topic').' '.$this->feather->utils->format_time($cur_post['registered'], true).'</span></dd>';

                    if ($this->config['o_show_post_count'] == '1' || $this->user->is_admmod) {
                        $cur_post['user_info'][] = '<dd><span>'.__('Posts topic').' '.$this->feather->utils->forum_number_format($cur_post['num_posts']).'</span></dd>';
                    }

                    // Now let's deal with the contact links (Email and URL)
                    if ((($cur_post['email_setting'] == '0' && !$this->user->is_guest) || $this->user->is_admmod) && $this->user->g_send_email == '1') {
                        $cur_post['user_contacts'][] = '<span class="email"><a href="mailto:'.$this->feather->utils->escape($cur_post['email']).'">'.__('Email').'</a></span>';
                    } elseif ($cur_post['email_setting'] == '1' && !$this->user->is_guest && $this->user->g_send_email == '1') {
                        $cur_post['user_contacts'][] = '<span class="email"><a href="'.$this->feather->url->get('email/'.$cur_post['poster_id'].'/').'">'.__('Email').'</a></span>';
                    }

                    if ($cur_post['url'] != '') {
                        if ($this->config['o_censoring'] == '1') {
                            $cur_post['url'] = censor_words($cur_post['url']);
                        }

                        $cur_post['user_contacts'][] = '<span class="website"><a href="'.$this->feather->utils->escape($cur_post['url']).'" rel="nofollow">'.__('Website').'</a></span>';
                    }
                }

                if ($this->user->g_id == FEATHER_ADMIN || ($this->user->g_moderator == '1' && $this->user->g_mod_promote_users == '1')) {
                    if ($cur_post['g_promote_next_group']) {
                        $cur_post['user_info'][] = '<dd><span><a href="'.$this->feather->url->base().'/user/'.$cur_post['poster_id'].'/action/promote/pid/'.$cur_post['id'].'">'.__('Promote user').'</a></span></dd>';
                    }
                }

                if ($this->user->is_admmod) {
                    $cur_post['user_info'][] = '<dd><span><a href="'.$this->feather->url->get('moderate/get-host/post/'.$cur_post['id'].'/').'" title="'.$this->feather->utils->escape($cur_post['poster_ip']).'">'.__('IP address logged').'</a></span></dd>';

                    if ($cur_post['admin_note'] != '') {
                        $cur_post['user_info'][] = '<dd><span>'.__('Note').' <strong>'.$this->feather->utils->escape($cur_post['admin_note']).'</strong></span></dd>';
                    }
                }
            }
            // If the poster is a guest (or a user that has been deleted)
            else {
                $cur_post['username_formatted'] = $this->feather->utils->escape($cur_post['username']);
                $cur_post['user_title_formatted'] = get_title($cur_post);

                if ($this->user->is_admmod) {
                    $cur_post['user_info'][] = '<dd><span><a href="'.$this->feather->url->get('moderate/get-host/post/'.$cur_post['id'].'/').'" title="'.$this->feather->utils->escape($cur_post['poster_ip']).'">'.__('IP address logged').'</a></span></dd>';
                }

                if ($this->config['o_show_user_info'] == '1' && $cur_post['poster_email'] != '' && !$this->user->is_guest && $this->user->g_send_email == '1') {
                    $cur_post['user_contacts'][] = '<span class="email"><a href="mailto:'.$this->feather->utils->escape($cur_post['poster_email']).'">'.__('Email').'</a></span>';
                }
            }

            // Generation post action array (quote, edit, delete etc.)
            if (!$is_admmod) {
                if (!$this->user->is_guest) {
                    $cur_post['post_actions'][] = '<li class="postreport"><span><a href="'.$this->feather->url->get('report/'.$cur_post['id'].'/').'">'.__('Report').'</a></span></li>';
                }

                if ($cur_topic['closed'] == '0') {
                    if ($cur_post['poster_id'] == $this->user->id) {
                        if ((($start_from + $post_count) == 1 && $this->user->g_delete_topics == '1') || (($start_from + $post_count) > 1 && $this->user->g_delete_posts == '1')) {
                            $cur_post['post_actions'][] = '<li class="postdelete"><span><a href="'.$this->feather->url->get('delete/'.$cur_post['id'].'/').'">'.__('Delete').'</a></span></li>';
                        }
                        if ($this->user->g_edit_posts == '1') {
                            $cur_post['post_actions'][] = '<li class="postedit"><span><a href="'.$this->feather->url->get('edit/'.$cur_post['id'].'/').'">'.__('Edit').'</a></span></li>';
                        }
                    }

                    if (($cur_topic['post_replies'] == '' && $this->user->g_post_replies == '1') || $cur_topic['post_replies'] == '1') {
                        $cur_post['post_actions'][] = '<li class="postquote"><span><a href="'.$this->feather->url->get('post/reply/'.$topic_id.'/quote/'.$cur_post['id'].'/').'">'.__('Quote').'</a></span></li>';
                    }
                }
            } else {
                $cur_post['post_actions'][] = '<li class="postreport"><span><a href="'.$this->feather->url->get('report/'.$cur_post['id'].'/').'">'.__('Report').'</a></span></li>';
                if ($this->user->g_id == FEATHER_ADMIN || !in_array($cur_post['poster_id'], $admin_ids)) {
                    $cur_post['post_actions'][] = '<li class="postdelete"><span><a href="'.$this->feather->url->get('delete/'.$cur_post['id'].'/').'">'.__('Delete').'</a></span></li>';
                    $cur_post['post_actions'][] = '<li class="postedit"><span><a href="'.$this->feather->url->get('edit/'.$cur_post['id'].'/').'">'.__('Edit').'</a></span></li>';
                }
                $cur_post['post_actions'][] = '<li class="postquote"><span><a href="'.$this->feather->url->get('post/reply/'.$topic_id.'/quote/'.$cur_post['id'].'/').'">'.__('Quote').'</a></span></li>';
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

        $post_data = $this->hook->fire('print_posts', $post_data);

        return $post_data;
    }

    public function increment_views($id)
    {
        if ($this->config['o_topic_views'] == '1') {
            $query = DB::for_table('topics')
                        ->where('id', $id)
                        ->find_one()
                        ->set_expr('num_views', 'num_views+1');
            $query = $this->hook->fire('increment_views', $query);
            $query = $query->save();
        }
    }
}
