<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace model;

class edit
{
    public function __construct()
    {
        $this->feather = \Slim\Slim::getInstance();
        $this->db = $this->feather->db;
        $this->start = $this->feather->start;
        $this->config = $this->feather->config;
        $this->user = $this->feather->user;
        $this->request = $this->feather->request;
    }

    // Fetch some info about the post, the topic and the forum
    public function get_info_edit($id)
    {
        
        $result = $this->db->query('SELECT f.id AS fid, f.forum_name, f.moderators, f.redirect_url, fp.post_replies, fp.post_topics, t.id AS tid, t.subject, t.posted, t.first_post_id, t.sticky, t.closed, p.poster, p.poster_id, p.message, p.hide_smilies FROM '.$this->db->prefix.'posts AS p INNER JOIN '.$this->db->prefix.'topics AS t ON t.id=p.topic_id INNER JOIN '.$this->db->prefix.'forums AS f ON f.id=t.forum_id LEFT JOIN '.$this->db->prefix.'forum_perms AS fp ON (fp.forum_id=f.id AND fp.group_id='.$this->user['g_id'].') WHERE (fp.read_forum IS NULL OR fp.read_forum=1) AND p.id='.$id) or error('Unable to fetch post info', __FILE__, __LINE__, $this->db->error());

        if (!$this->db->num_rows($result)) {
            message($lang_common['Bad request'], false, '404 Not Found');
        }

        $cur_post = $this->db->fetch_assoc($result);

        return $cur_post;
    }

    public function check_errors_before_edit($id, $can_edit_subject, $errors)
    {
        global $lang_post, $pd;

        // Make sure they got here from the site
        confirm_referrer(get_link_r('edit/'.$id.'/'));

        // If it's a topic it must contain a subject
        if ($can_edit_subject) {
            $subject = feather_trim($this->request->post('req_subject'));

            if ($this->config['o_censoring'] == '1') {
                $censored_subject = feather_trim(censor_words($subject));
            }

            if ($subject == '') {
                $errors[] = $lang_post['No subject'];
            } elseif ($this->config['o_censoring'] == '1' && $censored_subject == '') {
                $errors[] = $lang_post['No subject after censoring'];
            } elseif (feather_strlen($subject) > 70) {
                $errors[] = $lang_post['Too long subject'];
            } elseif ($this->config['p_subject_all_caps'] == '0' && is_all_uppercase($subject) && !$this->user['is_admmod']) {
                $errors[] = $lang_post['All caps subject'];
            }
        }

        // Clean up message from POST
        $message = feather_linebreaks(feather_trim($this->request->post('req_message')));

        // Here we use strlen() not feather_strlen() as we want to limit the post to FEATHER_MAX_POSTSIZE bytes, not characters
        if (strlen($message) > FEATHER_MAX_POSTSIZE) {
            $errors[] = sprintf($lang_post['Too long message'], forum_number_format(FEATHER_MAX_POSTSIZE));
        } elseif ($this->config['p_message_all_caps'] == '0' && is_all_uppercase($message) && !$this->user['is_admmod']) {
            $errors[] = $lang_post['All caps message'];
        }

        // Validate BBCode syntax
        if ($this->config['p_message_bbcode'] == '1') {
            require FEATHER_ROOT.'include/parser.php';
            $message = preparse_bbcode($message, $errors);
        }

        if (empty($errors)) {
            if ($message == '') {
                $errors[] = $lang_post['No message'];
            } elseif ($this->config['o_censoring'] == '1') {
                // Censor message to see if that causes problems
                $censored_message = feather_trim(censor_words($message));

                if ($censored_message == '') {
                    $errors[] = $lang_post['No message after censoring'];
                }
            }
        }

        return $errors;
    }

    // If the previous check went OK, setup some variables used later
    public function setup_variables($cur_post, $is_admmod, $can_edit_subject, $errors)
    {
        global $pd;

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

        return $post;
    }

    public function edit_post($id, $can_edit_subject, $post, $cur_post, $is_admmod)
    {
        $edited_sql = (!$this->request->post('slient') || !$is_admmod) ? ', edited='.time().', edited_by=\''.$this->db->escape($this->user['username']).'\'' : '';

        require FEATHER_ROOT.'include/search_idx.php';

        if ($can_edit_subject) {
            // Update the topic and any redirect topics
            $this->db->query('UPDATE '.$this->db->prefix.'topics SET subject=\''.$this->db->escape($post['subject']).'\', sticky='.$post['stick_topic'].' WHERE id='.$cur_post['tid'].' OR moved_to='.$cur_post['tid']) or error('Unable to update topic', __FILE__, __LINE__, $this->db->error());

            // We changed the subject, so we need to take that into account when we update the search words
            update_search_index('edit', $id, $post['message'], $post['subject']);
        } else {
            update_search_index('edit', $id, $post['message']);
        }

        // Update the post
        $this->db->query('UPDATE '.$this->db->prefix.'posts SET message=\''.$this->db->escape($post['message']).'\', hide_smilies='.$post['hide_smilies'].$edited_sql.' WHERE id='.$id) or error('Unable to update post', __FILE__, __LINE__, $this->db->error());
    }

    public function get_checkboxes($can_edit_subject, $is_admmod, $cur_post, $cur_index)
    {
        global $lang_post, $lang_common;

        $checkboxes = array();

        if ($can_edit_subject && $is_admmod) {
            if ($this->request->post('stick_topic') || $cur_post['sticky'] == '1') {
                $checkboxes[] = '<label><input type="checkbox" name="stick_topic" value="1" checked="checked" tabindex="'.($cur_index++).'" />'.$lang_common['Stick topic'].'<br /></label>';
            } else {
                $checkboxes[] = '<label><input type="checkbox" name="stick_topic" value="1" tabindex="'.($cur_index++).'" />'.$lang_common['Stick topic'].'<br /></label>';
            }
        }

        if ($this->config['o_smilies'] == '1') {
            if ($this->request->post('hide_smilies') || $cur_post['hide_smilies'] == '1') {
                $checkboxes[] = '<label><input type="checkbox" name="hide_smilies" value="1" checked="checked" tabindex="'.($cur_index++).'" />'.$lang_post['Hide smilies'].'<br /></label>';
            } else {
                $checkboxes[] = '<label><input type="checkbox" name="hide_smilies" value="1" tabindex="'.($cur_index++).'" />'.$lang_post['Hide smilies'].'<br /></label>';
            }
        }

        if ($is_admmod) {
            if ($this->request->isPost() && $this->request->post('silent') || $this->request->isPost() == '') {
                $checkboxes[] = '<label><input type="checkbox" name="silent" value="1" tabindex="'.($cur_index++).'" checked="checked" />'.$lang_post['Silent edit'].'<br /></label>';
            } else {
                $checkboxes[] = '<label><input type="checkbox" name="silent" value="1" tabindex="'.($cur_index++).'" />'.$lang_post['Silent edit'].'<br /></label>';
            }
        }

        return $checkboxes;
    }
}