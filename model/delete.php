<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace model;

class delete
{
    public function __construct()
    {
        $this->feather = \Slim\Slim::getInstance();
        $this->db = $this->feather->db;
        $this->start = $this->feather->start;
        $this->config = $this->feather->config;
        $this->user = $this->feather->user;
    }
 
    public function get_info_delete($id)
    {
        global $lang_common;

        $result = $this->db->query('SELECT f.id AS fid, f.forum_name, f.moderators, f.redirect_url, fp.post_replies, fp.post_topics, t.id AS tid, t.subject, t.first_post_id, t.closed, p.posted, p.poster, p.poster_id, p.message, p.hide_smilies FROM '.$this->db->prefix.'posts AS p INNER JOIN '.$this->db->prefix.'topics AS t ON t.id=p.topic_id INNER JOIN '.$this->db->prefix.'forums AS f ON f.id=t.forum_id LEFT JOIN '.$this->db->prefix.'forum_perms AS fp ON (fp.forum_id=f.id AND fp.group_id='.$this->user['g_id'].') WHERE (fp.read_forum IS NULL OR fp.read_forum=1) AND p.id='.$id) or error('Unable to fetch post info', __FILE__, __LINE__, $this->db->error());
        if (!$this->db->num_rows($result)) {
            message($lang_common['Bad request'], false, '404 Not Found');
        }

        $cur_post = $this->db->fetch_assoc($result);

        return $cur_post;
    }

    public function handle_deletion($is_topic_post, $id, $tid, $fid)
    {
        global $lang_delete;

        // Make sure they got here from the site
        confirm_referrer(get_link_r('delete/'.$id.'/'));

        require FEATHER_ROOT.'include/search_idx.php';

        if ($is_topic_post) {
            // Delete the topic and all of its posts
            delete_topic($tid);
            update_forum($fid);

            redirect(get_link('forum/'.$fid.'/'), $lang_delete['Topic del redirect']);
        } else {
            // Delete just this one post
            delete_post($id, $tid);
            update_forum($fid);

            // Redirect towards the previous post
            $result = $this->db->query('SELECT id FROM '.$this->db->prefix.'posts WHERE topic_id='.$tid.' AND id < '.$id.' ORDER BY id DESC LIMIT 1') or error('Unable to fetch post info', __FILE__, __LINE__, $this->db->error());
            $post_id = $this->db->result($result);

            redirect(get_link('post/'.$post_id.'/#p'.$post_id), $lang_delete['Post del redirect']);
        }
    }
}