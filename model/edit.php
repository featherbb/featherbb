<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

// Fetch some info about the post, the topic and the forum
function get_info_edit($id)
{
    global $db, $pun_user;

    $result = $db->query('SELECT f.id AS fid, f.forum_name, f.moderators, f.redirect_url, fp.post_replies, fp.post_topics, t.id AS tid, t.subject, t.posted, t.first_post_id, t.sticky, t.closed, p.poster, p.poster_id, p.message, p.hide_smilies FROM '.$db->prefix.'posts AS p INNER JOIN '.$db->prefix.'topics AS t ON t.id=p.topic_id INNER JOIN '.$db->prefix.'forums AS f ON f.id=t.forum_id LEFT JOIN '.$db->prefix.'forum_perms AS fp ON (fp.forum_id=f.id AND fp.group_id='.$pun_user['g_id'].') WHERE (fp.read_forum IS NULL OR fp.read_forum=1) AND p.id='.$id) or error('Unable to fetch post info', __FILE__, __LINE__, $db->error());
    
    if (!$db->num_rows($result)) {
        message($lang_common['Bad request'], false, '404 Not Found');
    }

    $cur_post = $db->fetch_assoc($result);
    
    return $cur_post;
}

function check_errors_before_edit($id, $feather, $can_edit_subject, $errors)
{
    global $db, $pun_user, $pun_config, $lang_post, $pd;
    
    // Make sure they got here from the site
    confirm_referrer(get_link_r('edit/'.$id.'/'));

    // If it's a topic it must contain a subject
    if ($can_edit_subject) {
        $subject = pun_trim($feather->request->post('req_subject'));

        if ($pun_config['o_censoring'] == '1') {
            $censored_subject = pun_trim(censor_words($subject));
        }

        if ($subject == '') {
            $errors[] = $lang_post['No subject'];
        } elseif ($pun_config['o_censoring'] == '1' && $censored_subject == '') {
            $errors[] = $lang_post['No subject after censoring'];
        } elseif (pun_strlen($subject) > 70) {
            $errors[] = $lang_post['Too long subject'];
        } elseif ($pun_config['p_subject_all_caps'] == '0' && is_all_uppercase($subject) && !$pun_user['is_admmod']) {
            $errors[] = $lang_post['All caps subject'];
        }
    }

    // Clean up message from POST
    $message = pun_linebreaks(pun_trim($feather->request->post('req_message')));

    // Here we use strlen() not pun_strlen() as we want to limit the post to PUN_MAX_POSTSIZE bytes, not characters
    if (strlen($message) > PUN_MAX_POSTSIZE) {
        $errors[] = sprintf($lang_post['Too long message'], forum_number_format(PUN_MAX_POSTSIZE));
    } elseif ($pun_config['p_message_all_caps'] == '0' && is_all_uppercase($message) && !$pun_user['is_admmod']) {
        $errors[] = $lang_post['All caps message'];
    }

    // Validate BBCode syntax
    if ($pun_config['p_message_bbcode'] == '1') {
        require PUN_ROOT.'include/parser.php';
        $message = preparse_bbcode($message, $errors);
    }

    if (empty($errors)) {
        if ($message == '') {
            $errors[] = $lang_post['No message'];
        } elseif ($pun_config['o_censoring'] == '1') {
            // Censor message to see if that causes problems
            $censored_message = pun_trim(censor_words($message));

            if ($censored_message == '') {
                $errors[] = $lang_post['No message after censoring'];
            }
        }
    }
    
    return $errors;
}

// If the previous check went OK, setup some variables used later
function setup_variables($feather, $cur_post, $is_admmod, $can_edit_subject, $errors)
{
    global $pun_config, $pd;
    
    $post = array();
    
    $post['hide_smilies'] = !empty($feather->request->post('hide_smilies')) ? '1' : '0';
    $post['stick_topic'] = !empty($feather->request->post('stick_topic')) ? '1' : '0';
    if (!$is_admmod) {
        $post['stick_topic'] = $cur_post['sticky'];
    }
    
    // Clean up message from POST
    $post['message'] = pun_linebreaks(pun_trim($feather->request->post('req_message')));
    
    // Validate BBCode syntax
    if ($pun_config['p_message_bbcode'] == '1') {
        require_once PUN_ROOT.'include/parser.php';
        $post['message'] = preparse_bbcode($post['message'], $errors);
    }
    
    // Replace four-byte characters (MySQL cannot handle them)
    $post['message'] = strip_bad_multibyte_chars($post['message']);
    
    // Get the subject
    if ($can_edit_subject) {
        $post['subject'] = pun_trim($feather->request->post('req_subject'));
    }
    
    return $post;
}

function edit_post($id, $can_edit_subject, $post, $cur_post, $feather, $is_admmod)
{
    global $db, $pun_user;
    
    $edited_sql = (empty($feather->request->post('slient')) || !$is_admmod) ? ', edited='.time().', edited_by=\''.$db->escape($pun_user['username']).'\'' : '';

    require PUN_ROOT.'include/search_idx.php';

    if ($can_edit_subject) {
        // Update the topic and any redirect topics
        $db->query('UPDATE '.$db->prefix.'topics SET subject=\''.$db->escape($post['subject']).'\', sticky='.$post['stick_topic'].' WHERE id='.$cur_post['tid'].' OR moved_to='.$cur_post['tid']) or error('Unable to update topic', __FILE__, __LINE__, $db->error());

        // We changed the subject, so we need to take that into account when we update the search words
        update_search_index('edit', $id, $post['message'], $post['subject']);
    } else {
        update_search_index('edit', $id, $post['message']);
    }

    // Update the post
    $db->query('UPDATE '.$db->prefix.'posts SET message=\''.$db->escape($post['message']).'\', hide_smilies='.$post['hide_smilies'].$edited_sql.' WHERE id='.$id) or error('Unable to update post', __FILE__, __LINE__, $db->error());
}

function get_checkboxes($can_edit_subject, $is_admmod, $cur_post, $feather, $cur_index)
{
    global $lang_post, $lang_common, $pun_config;
    
    $checkboxes = array();
    
    if ($can_edit_subject && $is_admmod) {
        if (!empty($feather->request->post('stick_topic')) || $cur_post['sticky'] == '1') {
            $checkboxes[] = '<label><input type="checkbox" name="stick_topic" value="1" checked="checked" tabindex="'.($cur_index++).'" />'.$lang_common['Stick topic'].'<br /></label>';
        } else {
            $checkboxes[] = '<label><input type="checkbox" name="stick_topic" value="1" tabindex="'.($cur_index++).'" />'.$lang_common['Stick topic'].'<br /></label>';
        }
    }

    if ($pun_config['o_smilies'] == '1') {
        if (!empty($feather->request->post('hide_smilies')) || $cur_post['hide_smilies'] == '1') {
            $checkboxes[] = '<label><input type="checkbox" name="hide_smilies" value="1" checked="checked" tabindex="'.($cur_index++).'" />'.$lang_post['Hide smilies'].'<br /></label>';
        } else {
            $checkboxes[] = '<label><input type="checkbox" name="hide_smilies" value="1" tabindex="'.($cur_index++).'" />'.$lang_post['Hide smilies'].'<br /></label>';
        }
    }

    if ($is_admmod) {
        if ($feather->request()->isPost() && !empty($feather->request->post('silent')) || empty($feather->request()->isPost())) {
            $checkboxes[] = '<label><input type="checkbox" name="silent" value="1" tabindex="'.($cur_index++).'" checked="checked" />'.$lang_post['Silent edit'].'<br /></label>';
        } else {
            $checkboxes[] = '<label><input type="checkbox" name="silent" value="1" tabindex="'.($cur_index++).'" />'.$lang_post['Silent edit'].'<br /></label>';
        }
    }
    
    return $checkboxes;
}
