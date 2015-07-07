<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace model\admin;

class forums
{
    public function __construct()
    {
        $this->feather = \Slim\Slim::getInstance();
        $this->db = $this->feather->db;
        $this->start = $this->feather->start;
        $this->config = $this->feather->config;
        $this->user = $this->feather->user;
    }
 
    public function add_forum($feather)
    {
        global $lang_admin_forums, $lang_common;

        confirm_referrer(get_link_r('admin/forums/'));

        $add_to_cat = intval($feather->request->post('add_to_cat'));
        if ($add_to_cat < 1) {
            message($lang_common['Bad request'], false, '404 Not Found');
        }

        $this->db->query('INSERT INTO '.$this->db->prefix.'forums (forum_name, cat_id) VALUES(\''.$this->db->escape($lang_admin_forums['New forum']).'\', '.$add_to_cat.')') or error('Unable to create forum', __FILE__, __LINE__, $this->db->error());
        $new_fid = $this->db->insert_id();

        // Regenerate the quick jump cache
        if (!defined('FORUM_CACHE_FUNCTIONS_LOADED')) {
            require FEATHER_ROOT.'include/cache.php';
        }

        generate_quickjump_cache();

        redirect(get_link('admin/forums/edit/'.$new_fid.'/'), $lang_admin_forums['Forum added redirect']);
    }

    public function delete_forum($forum_id)
    {
        global $lang_admin_forums;

        confirm_referrer(get_link_r('admin/forums/delete/'.$forum_id.'/'));

        @set_time_limit(0);

        // Load the maintenance.php model file for prune public function
        require FEATHER_ROOT . 'model/admin/maintenance.php';

        // Prune all posts and topics
        prune($forum_id, 1, -1);

        // Locate any "orphaned redirect topics" and delete them
        $result = $this->db->query('SELECT t1.id FROM '.$this->db->prefix.'topics AS t1 LEFT JOIN '.$this->db->prefix.'topics AS t2 ON t1.moved_to=t2.id WHERE t2.id IS NULL AND t1.moved_to IS NOT NULL') or error('Unable to fetch redirect topics', __FILE__, __LINE__, $this->db->error());
        $num_orphans = $this->db->num_rows($result);

        if ($num_orphans) {
            for ($i = 0; $i < $num_orphans; ++$i) {
                $orphans[] = $this->db->result($result, $i);
            }

            $this->db->query('DELETE FROM '.$this->db->prefix.'topics WHERE id IN('.implode(',', $orphans).')') or error('Unable to delete redirect topics', __FILE__, __LINE__, $this->db->error());
        }

        // Delete the forum and any forum specific group permissions
        $this->db->query('DELETE FROM '.$this->db->prefix.'forums WHERE id='.$forum_id) or error('Unable to delete forum', __FILE__, __LINE__, $this->db->error());
        $this->db->query('DELETE FROM '.$this->db->prefix.'forum_perms WHERE forum_id='.$forum_id) or error('Unable to delete group forum permissions', __FILE__, __LINE__, $this->db->error());

        // Delete any subscriptions for this forum
        $this->db->query('DELETE FROM '.$this->db->prefix.'forum_subscriptions WHERE forum_id='.$forum_id) or error('Unable to delete subscriptions', __FILE__, __LINE__, $this->db->error());

        // Regenerate the quick jump cache
        if (!defined('FORUM_CACHE_FUNCTIONS_LOADED')) {
            require FEATHER_ROOT.'include/cache.php';
        }

        generate_quickjump_cache();

        redirect(get_link('admin/forums/'), $lang_admin_forums['Forum deleted redirect']);
    }

    public function get_forum_name($forum_id)
    {
        

        $result = $this->db->query('SELECT forum_name FROM '.$this->db->prefix.'forums WHERE id='.$forum_id) or error('Unable to fetch forum info', __FILE__, __LINE__, $this->db->error());
        $forum_name = pun_htmlspecialchars($this->db->result($result));

        return $forum_name;
    }

    public function update_positions($feather)
    {
        global $lang_admin_forums;

        confirm_referrer(get_link_r('admin/forums/'));

        foreach ($feather->request->post('position') as $forum_id => $disp_position) {
            $disp_position = trim($disp_position);
            if ($disp_position == '' || preg_match('%[^0-9]%', $disp_position)) {
                message($lang_admin_forums['Must be integer message']);
            }

            $this->db->query('UPDATE '.$this->db->prefix.'forums SET disp_position='.$disp_position.' WHERE id='.intval($forum_id)) or error('Unable to update forum', __FILE__, __LINE__, $this->db->error());
        }

        // Regenerate the quick jump cache
        if (!defined('FORUM_CACHE_FUNCTIONS_LOADED')) {
            require FEATHER_ROOT.'include/cache.php';
        }

        generate_quickjump_cache();

        redirect(get_link('admin/forums/'), $lang_admin_forums['Forums updated redirect']);
    }

    public function update_permissions($feather, $forum_id)
    {
        global $lang_admin_forums, $lang_common;

        confirm_referrer(get_link_r('admin/forums/edit/'.$forum_id.'/'));

        // Start with the forum details
        $forum_name = pun_trim($feather->request->post('forum_name'));
        $forum_desc = pun_linebreaks(pun_trim($feather->request->post('forum_desc')));
        $cat_id = intval($feather->request->post('cat_id'));
        $sort_by = intval($feather->request->post('sort_by'));
        $redirect_url = $feather->request->post('redirect_url') ? pun_trim($feather->request->post('redirect_url')) : null;

        if ($forum_name == '') {
            message($lang_admin_forums['Must enter name message']);
        }

        if ($cat_id < 1) {
            message($lang_common['Bad request'], false, '404 Not Found');
        }

        $forum_desc = ($forum_desc != '') ? '\''.$this->db->escape($forum_desc).'\'' : 'NULL';
        $redirect_url = ($redirect_url != '') ? '\''.$this->db->escape($redirect_url).'\'' : 'NULL';

        $this->db->query('UPDATE '.$this->db->prefix.'forums SET forum_name=\''.$this->db->escape($forum_name).'\', forum_desc='.$forum_desc.', redirect_url='.$redirect_url.', sort_by='.$sort_by.', cat_id='.$cat_id.' WHERE id='.$forum_id) or error('Unable to update forum', __FILE__, __LINE__, $this->db->error());

        // Now let's deal with the permissions
        if ($feather->request->post('read_forum_old')) {
            $result = $this->db->query('SELECT g_id, g_read_board, g_post_replies, g_post_topics FROM '.$this->db->prefix.'groups WHERE g_id!='.FEATHER_ADMIN) or error('Unable to fetch user group list', __FILE__, __LINE__, $this->db->error());
            while ($cur_group = $this->db->fetch_assoc($result)) {
                $read_forum_new = ($cur_group['g_read_board'] == '1') ? isset($feather->request->post('read_forum_new')[$cur_group['g_id']]) ? '1' : '0' : intval($feather->request->post('read_forum_new')[$cur_group['g_id']]);
                $post_replies_new = (isset($feather->request->post('post_replies_new')[$cur_group['g_id']])) ? '1' : '0';
                $post_topics_new = (isset($feather->request->post('post_topics_new')[$cur_group['g_id']])) ? '1' : '0';

                // Check if the new settings differ from the old
                if ($read_forum_new != $feather->request->post('read_forum_old')[$cur_group['g_id']] || $post_replies_new != $feather->request->post('post_replies_old')[$cur_group['g_id']] || $post_topics_new != $feather->request->post('post_topics_old')[$cur_group['g_id']]) {
                    // If the new settings are identical to the default settings for this group, delete its row in forum_perms
                    if ($read_forum_new == '1' && $post_replies_new == $cur_group['g_post_replies'] && $post_topics_new == $cur_group['g_post_topics']) {
                        $this->db->query('DELETE FROM '.$this->db->prefix.'forum_perms WHERE group_id='.$cur_group['g_id'].' AND forum_id='.$forum_id) or error('Unable to delete group forum permissions', __FILE__, __LINE__, $this->db->error());
                    } else {
                        // Run an UPDATE and see if it affected a row, if not, INSERT
                        $this->db->query('UPDATE '.$this->db->prefix.'forum_perms SET read_forum='.$read_forum_new.', post_replies='.$post_replies_new.', post_topics='.$post_topics_new.' WHERE group_id='.$cur_group['g_id'].' AND forum_id='.$forum_id) or error('Unable to insert group forum permissions', __FILE__, __LINE__, $this->db->error());
                        if (!$this->db->affected_rows()) {
                            $this->db->query('INSERT INTO '.$this->db->prefix.'forum_perms (group_id, forum_id, read_forum, post_replies, post_topics) VALUES('.$cur_group['g_id'].', '.$forum_id.', '.$read_forum_new.', '.$post_replies_new.', '.$post_topics_new.')') or error('Unable to insert group forum permissions', __FILE__, __LINE__, $this->db->error());
                        }
                    }
                }
            }
        }

        // Regenerate the quick jump cache
        if (!defined('FORUM_CACHE_FUNCTIONS_LOADED')) {
            require FEATHER_ROOT.'include/cache.php';
        }

        generate_quickjump_cache();

        redirect(get_link('admin/forums/edit/'.$forum_id.'/'), $lang_admin_forums['Forum updated redirect']);
    }

    public function revert_permissions($forum_id)
    {
        global $lang_admin_forums;

        confirm_referrer(get_link_r('admin/forums/edit/'.$forum_id.'/'));

        $this->db->query('DELETE FROM '.$this->db->prefix.'forum_perms WHERE forum_id='.$forum_id) or error('Unable to delete group forum permissions', __FILE__, __LINE__, $this->db->error());

        // Regenerate the quick jump cache
        if (!defined('FORUM_CACHE_FUNCTIONS_LOADED')) {
            require FEATHER_ROOT.'include/cache.php';
        }

        generate_quickjump_cache();

        redirect(get_link('admin/forums/edit/'.$forum_id.'/'), $lang_admin_forums['Perms reverted redirect']);
    }

    public function get_forum_info($forum_id)
    {
        global $lang_common;

        $result = $this->db->query('SELECT id, forum_name, forum_desc, redirect_url, num_topics, sort_by, cat_id FROM '.$this->db->prefix.'forums WHERE id='.$forum_id) or error('Unable to fetch forum info', __FILE__, __LINE__, $this->db->error());
        if (!$this->db->num_rows($result)) {
            message($lang_common['Bad request'], false, '404 Not Found');
        }

        $cur_forum = $this->db->fetch_assoc($result);

        return $cur_forum;
    }

    public function get_categories_permissions($cur_forum)
    {
        
        
        $output = '';

        $result = $this->db->query('SELECT id, cat_name FROM '.$this->db->prefix.'categories ORDER BY disp_position') or error('Unable to fetch category list', __FILE__, __LINE__, $this->db->error());
        while ($cur_cat = $this->db->fetch_assoc($result)) {
            $selected = ($cur_cat['id'] == $cur_forum['cat_id']) ? ' selected="selected"' : '';
            $output .= "\t\t\t\t\t\t\t\t\t\t\t".'<option value="'.$cur_cat['id'].'"'.$selected.'>'.pun_htmlspecialchars($cur_cat['cat_name']).'</option>'."\n";
        }
        
        return $output;
    }

    public function get_categories_add()
    {
        
        
        $output = '';

        $result = $this->db->query('SELECT id, cat_name FROM '.$this->db->prefix.'categories ORDER BY disp_position') or error('Unable to fetch category list', __FILE__, __LINE__, $this->db->error());

        while ($cur_cat = $this->db->fetch_assoc($result)) {
            $output .= "\t\t\t\t\t\t\t\t\t\t\t".'<option value="'.$cur_cat['id'].'">'.pun_htmlspecialchars($cur_cat['cat_name']).'</option>'."\n";
        }
        
        return $output;
    }

    public function get_permissions($forum_id)
    {
        

        $perm_data = array();

        $result = $this->db->query('SELECT g.g_id, g.g_title, g.g_read_board, g.g_post_replies, g.g_post_topics, fp.read_forum, fp.post_replies, fp.post_topics FROM '.$this->db->prefix.'groups AS g LEFT JOIN '.$this->db->prefix.'forum_perms AS fp ON (g.g_id=fp.group_id AND fp.forum_id='.$forum_id.') WHERE g.g_id!='.FEATHER_ADMIN.' ORDER BY g.g_id') or error('Unable to fetch group forum permission list', __FILE__, __LINE__, $this->db->error());

        while ($cur_perm = $this->db->fetch_assoc($result)) {
            $cur_perm['read_forum'] = ($cur_perm['read_forum'] != '0') ? true : false;
            $cur_perm['post_replies'] = (($cur_perm['g_post_replies'] == '0' && $cur_perm['post_replies'] == '1') || ($cur_perm['g_post_replies'] == '1' && $cur_perm['post_replies'] != '0')) ? true : false;
            $cur_perm['post_topics'] = (($cur_perm['g_post_topics'] == '0' && $cur_perm['post_topics'] == '1') || ($cur_perm['g_post_topics'] == '1' && $cur_perm['post_topics'] != '0')) ? true : false;

            // Determine if the current settings differ from the default or not
            $cur_perm['read_forum_def'] = ($cur_perm['read_forum'] == '0') ? false : true;
            $cur_perm['post_replies_def'] = (($cur_perm['post_replies'] && $cur_perm['g_post_replies'] == '0') || (!$cur_perm['post_replies'] && ($cur_perm['g_post_replies'] == '' || $cur_perm['g_post_replies'] == '1'))) ? false : true;
            $cur_perm['post_topics_def'] = (($cur_perm['post_topics'] && $cur_perm['g_post_topics'] == '0') || ($cur_perm['post_topics'] && ($cur_perm['g_post_topics'] == '' || $cur_perm['g_post_topics'] == '1'))) ? false : true;

            $perm_data[] = $cur_perm;
        }

        return $perm_data;
    }

    public function check_forums()
    {
        

        $result = $this->db->query('SELECT id, cat_name FROM '.$this->db->prefix.'categories ORDER BY disp_position') or error('Unable to fetch category list', __FILE__, __LINE__, $this->db->error());
        if ($this->db->num_rows($result)) {
            $is_forum = true;
        } else {
            $is_forum = false;
        }

        return $is_forum;
    }

    public function get_forums()
    {
        

        $forum_data = array();

        $result = $this->db->query('SELECT c.id AS cid, c.cat_name, f.id AS fid, f.forum_name, f.disp_position FROM '.$this->db->prefix.'categories AS c INNER JOIN '.$this->db->prefix.'forums AS f ON c.id=f.cat_id ORDER BY c.disp_position, c.id, f.disp_position') or error('Unable to fetch category/forum list', __FILE__, __LINE__, $this->db->error());

        while ($cur_forum = $this->db->fetch_assoc($result)) {
            $forum_data[] = $cur_forum;
        }

        return $forum_data;
    }
}