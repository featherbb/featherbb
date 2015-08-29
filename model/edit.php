<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace model;

use DB;

class edit
{
    public function __construct()
    {
        $this->feather = \Slim\Slim::getInstance();
        $this->start = $this->feather->start;
        $this->config = $this->feather->config;
        $this->user = $this->feather->user;
        $this->request = $this->feather->request;
        $this->hook = $this->feather->hooks;
        $this->search = new \FeatherBB\Search();
    }

    // Fetch some info about the post, the topic and the forum
    public function get_info_edit($id)
    {
        $id = $this->hook->fire('get_info_edit_start', $id);

        $cur_post['select'] = array('fid' => 'f.id', 'f.forum_name', 'f.moderators', 'f.redirect_url', 'fp.post_topics', 'tid' => 't.id', 't.subject', 't.posted', 't.first_post_id', 't.sticky', 't.closed', 'p.poster', 'p.poster_id', 'p.message', 'p.hide_smilies');
        $cur_post['where'] = array(
            array('fp.read_forum' => 'IS NULL'),
            array('fp.read_forum' => '1')
        );

        $cur_post = DB::for_table('posts')
            ->table_alias('p')
            ->select_many($cur_post['select'])
            ->inner_join('topics', array('t.id', '=', 'p.topic_id'), 't')
            ->inner_join('forums', array('f.id', '=', 't.forum_id'), 'f')
            ->left_outer_join('forum_perms', array('fp.forum_id', '=', 'f.id'), 'fp')
            ->left_outer_join('forum_perms', array('fp.group_id', '=', $this->user->g_id), null, true)
            ->where_any_is($cur_post['where'])
            ->where('p.id', $id);

        $cur_post = $this->hook->fireDB('get_info_edit_query', $cur_post);

        $cur_post = $cur_post->find_one();

        if (!$cur_post) {
            throw new \FeatherBB\Error(__('Bad request'), 400);
        }

        return $cur_post;
    }

    public function check_errors_before_edit($can_edit_subject, $errors)
    {
        global $pd;

        $errors = $this->hook->fire('check_errors_before_edit_start', $errors);

        // If it's a topic it must contain a subject
        if ($can_edit_subject) {
            $subject = feather_trim($this->request->post('req_subject'));

            if ($this->config['o_censoring'] == '1') {
                $censored_subject = feather_trim(censor_words($subject));
            }

            if ($subject == '') {
                $errors[] = __('No subject');
            } elseif ($this->config['o_censoring'] == '1' && $censored_subject == '') {
                $errors[] = __('No subject after censoring');
            } elseif (feather_strlen($subject) > 70) {
                $errors[] = __('Too long subject');
            } elseif ($this->config['p_subject_all_caps'] == '0' && is_all_uppercase($subject) && !$this->user->is_admmod) {
                $errors[] = __('All caps subject');
            }
        }

        // Clean up message from POST
        $message = feather_linebreaks(feather_trim($this->request->post('req_message')));

        // Here we use strlen() not feather_strlen() as we want to limit the post to FEATHER_MAX_POSTSIZE bytes, not characters
        if (strlen($message) > FEATHER_MAX_POSTSIZE) {
            $errors[] = sprintf(__('Too long message'), forum_number_format(FEATHER_MAX_POSTSIZE));
        } elseif ($this->config['p_message_all_caps'] == '0' && is_all_uppercase($message) && !$this->user->is_admmod) {
            $errors[] = __('All caps message');
        }

        // Validate BBCode syntax
        if ($this->config['p_message_bbcode'] == '1') {
            require FEATHER_ROOT.'include/parser.php';
            $message = preparse_bbcode($message, $errors);
        }

        if (empty($errors)) {
            if ($message == '') {
                $errors[] = __('No message');
            } elseif ($this->config['o_censoring'] == '1') {
                // Censor message to see if that causes problems
                $censored_message = feather_trim(censor_words($message));

                if ($censored_message == '') {
                    $errors[] = __('No message after censoring');
                }
            }
        }

        $errors = $this->hook->fire('check_errors_before_edit', $errors);

        return $errors;
    }

    // If the previous check went OK, setup some variables used later
    public function setup_variables($cur_post, $is_admmod, $can_edit_subject, $errors)
    {
        global $pd;

        $this->hook->fire('setup_variables_start');

        $post = array();

        $post['hide_smilies'] = $this->request->post('hide_smilies') ? '1' : '0';
        $post['stick_topic'] = $this->request->post('stick_topic') ? '1' : '0';
        if (!$is_admmod) {
            $post['stick_topic'] = $cur_post['sticky'];
        }

        // Clean up message from POST
        $post['message'] = feather_linebreaks(feather_trim($this->request->post('req_message')));

        // Validate BBCode syntax
        if ($this->config['p_message_bbcode'] == '1') {
            require_once FEATHER_ROOT.'include/parser.php';
            $post['message'] = preparse_bbcode($post['message'], $errors);
        }

        // Replace four-byte characters (MySQL cannot handle them)
        $post['message'] = strip_bad_multibyte_chars($post['message']);

        // Get the subject
        if ($can_edit_subject) {
            $post['subject'] = feather_trim($this->request->post('req_subject'));
        }

        $post = $this->hook->fire('setup_variables_edit', $post);

        return $post;
    }

    public function edit_post($id, $can_edit_subject, $post, $cur_post, $is_admmod)
    {
        $this->hook->fire('edit_post_start');

        if ($can_edit_subject) {
            // Update the topic and any redirect topics
            $where_topic = array(
                array('id' => $cur_post['tid']),
                array('moved_to' => $cur_post['tid'])
            );

            $query['update_topic'] = array(
                'subject' => $post['subject'],
                'sticky'  => $post['stick_topic']
            );

            $query = DB::for_table('topics')->where_any_is($where_topic)
                                            ->find_one()
                                            ->set($query['update_topic']);

            $query = $this->hook->fireDB('edit_post_can_edit_subject', $query);

            $query = $query->save();

            // We changed the subject, so we need to take that into account when we update the search words
            $this->search->update_search_index('edit', $id, $post['message'], $post['subject']);
        } else {
            $this->search->update_search_index('edit', $id, $post['message']);
        }

        // Update the post
        unset($query);
        $query['update_post'] = array(
            'message' => $post['message'],
            'hide_smilies'  => $post['hide_smilies']
        );

        if (!$this->request->post('silent') || !$is_admmod) {
            $query['update_post']['edited'] = time();
            $query['update_post']['edited_by'] = $this->user->username;
        }

        $query = DB::for_table('posts')->where('id', $id)
                                       ->find_one()
                                       ->set($query['update_post']);
        $query = $this->hook->fireDB('edit_post_query', $query);
        $query = $query->save();
    }

    public function get_checkboxes($can_edit_subject, $is_admmod, $cur_post, $cur_index)
    {
        $this->hook->fire('get_checkboxes_start', $can_edit_subject, $is_admmod, $cur_post, $cur_index);

        $checkboxes = array();

        if ($can_edit_subject && $is_admmod) {
            if ($this->request->post('stick_topic') || $cur_post['sticky'] == '1') {
                $checkboxes[] = '<label><input type="checkbox" name="stick_topic" value="1" checked="checked" tabindex="'.($cur_index++).'" />'.__('Stick topic').'<br /></label>';
            } else {
                $checkboxes[] = '<label><input type="checkbox" name="stick_topic" value="1" tabindex="'.($cur_index++).'" />'.__('Stick topic').'<br /></label>';
            }
        }

        if ($this->config['o_smilies'] == '1') {
            if ($this->request->post('hide_smilies') || $cur_post['hide_smilies'] == '1') {
                $checkboxes[] = '<label><input type="checkbox" name="hide_smilies" value="1" checked="checked" tabindex="'.($cur_index++).'" />'.__('Hide smilies').'<br /></label>';
            } else {
                $checkboxes[] = '<label><input type="checkbox" name="hide_smilies" value="1" tabindex="'.($cur_index++).'" />'.__('Hide smilies').'<br /></label>';
            }
        }

        if ($is_admmod) {
            if ($this->request->isPost() && $this->request->post('silent') || $this->request->isPost() == '') {
                $checkboxes[] = '<label><input type="checkbox" name="silent" value="1" tabindex="'.($cur_index++).'" checked="checked" />'.__('Silent edit').'<br /></label>';
            } else {
                $checkboxes[] = '<label><input type="checkbox" name="silent" value="1" tabindex="'.($cur_index++).'" />'.__('Silent edit').'<br /></label>';
            }
        }

        $checkboxes = $this->hook->fire('get_checkboxes', $checkboxes);

        return $checkboxes;
    }
}
