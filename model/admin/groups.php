<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace model\admin;

class groups
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
 
    public function fetch_groups()
    {
        

        $result = $this->db->query('SELECT * FROM '.$this->db->prefix.'groups ORDER BY g_id') or error('Unable to fetch user groups', __FILE__, __LINE__, $this->db->error());
        $groups = array();
        while ($cur_group = $this->db->fetch_assoc($result)) {
            $groups[$cur_group['g_id']] = $cur_group;
        }

        return $groups;
    }

    public function info_add_group($groups, $feather, $id)
    {
        global $lang_common;

        $group = array();

        if ($this->request->post('add_group')) {
            $group['base_group'] = intval($this->request->post('base_group'));
            $group['info'] = $groups[$group['base_group']];

            $group['mode'] = 'add';
        } else {
            // We are editing a group
            if (!isset($groups[$id])) {
                message($lang_common['Bad request'], false, '404 Not Found');
            }

            $group['info'] = $groups[$id];

            $group['mode'] = 'edit';
        }

        return $group;
    }

    public function get_group_list($groups, $group)
    {
        foreach ($groups as $cur_group) {
            if (($cur_group['g_id'] != $group['info']['g_id'] || $group['mode'] == 'add') && $cur_group['g_id'] != FEATHER_ADMIN && $cur_group['g_id'] != FEATHER_GUEST) {
                if ($cur_group['g_id'] == $group['info']['g_promote_next_group']) {
                    echo "\t\t\t\t\t\t\t\t\t\t\t".'<option value="'.$cur_group['g_id'].'" selected="selected">'.pun_htmlspecialchars($cur_group['g_title']).'</option>'."\n";
                } else {
                    echo "\t\t\t\t\t\t\t\t\t\t\t".'<option value="'.$cur_group['g_id'].'">'.pun_htmlspecialchars($cur_group['g_title']).'</option>'."\n";
                }
            }
        }
    }

    public function get_group_list_delete($group_id)
    {
        

        $result = $this->db->query('SELECT g_id, g_title FROM '.$this->db->prefix.'groups WHERE g_id!='.FEATHER_GUEST.' AND g_id!='.$group_id.' ORDER BY g_title') or error('Unable to fetch user group list', __FILE__, __LINE__, $this->db->error());

        while ($cur_group = $this->db->fetch_assoc($result)) {
            if ($cur_group['g_id'] == FEATHER_MEMBER) {
                // Pre-select the pre-defined Members group
                echo "\t\t\t\t\t\t\t\t\t\t".'<option value="'.$cur_group['g_id'].'" selected="selected">'.pun_htmlspecialchars($cur_group['g_title']).'</option>'."\n";
            } else {
                echo "\t\t\t\t\t\t\t\t\t\t".'<option value="'.$cur_group['g_id'].'">'.pun_htmlspecialchars($cur_group['g_title']).'</option>'."\n";
            }
        }
    }

    public function add_edit_group($groups, $feather)
    {
        global $lang_admin_groups;

        if ($this->request->post('group_id')) {
            $group_id = $this->request->post('group_id');
        } else {
            $group_id = 0;
        }

        confirm_referrer(array(
            get_link_r('admin/groups/edit/'.$group_id.'/'),
            get_link_r('admin/groups/add/'),
        ));

        // Is this the admin group? (special rules apply)
        $is_admin_group = ($this->request->post('group_id') && $this->request->post('group_id') == FEATHER_ADMIN) ? true : false;

        $title = pun_trim($this->request->post('req_title'));
        $user_title = pun_trim($this->request->post('user_title'));

        $promote_min_posts = $this->request->post('promote_min_posts') ? intval($this->request->post('promote_min_posts')) : '0';
        if ($this->request->post('promote_next_group') &&
                isset($groups[$this->request->post('promote_next_group')]) &&
                !in_array($this->request->post('promote_next_group'), array(FEATHER_ADMIN, FEATHER_GUEST)) &&
                ($this->request->post('group_id') || $this->request->post('promote_next_group') != $this->request->post('group_id'))) {
            $promote_next_group = $this->request->post('promote_next_group');
        } else {
            $promote_next_group = '0';
        }

        $moderator = $this->request->post('moderator') && $this->request->post('moderator') == '1' ? '1' : '0';
        $mod_edit_users = $moderator == '1' && $this->request->post('mod_edit_users') == '1' ? '1' : '0';
        $mod_rename_users = $moderator == '1' && $this->request->post('mod_rename_users') == '1' ? '1' : '0';
        $mod_change_passwords = $moderator == '1' && $this->request->post('mod_change_passwords') == '1' ? '1' : '0';
        $mod_ban_users = $moderator == '1' && $this->request->post('mod_ban_users') == '1' ? '1' : '0';
        $mod_promote_users = $moderator == '1' && $this->request->post('mod_promote_users') == '1' ? '1' : '0';
        $read_board = ($this->request->post('read_board') == 0) ? $this->request->post('read_board') : '1';
        $view_users = ($this->request->post('view_users') && $this->request->post('view_users') == '1') || $is_admin_group ? '1' : '0';
        $post_replies = ($this->request->post('post_replies') == 0) ? $this->request->post('post_replies') : '1';
        $post_topics = ($this->request->post('post_topics') == 0) ? $this->request->post('post_topics') : '1';
        $edit_posts = ($this->request->post('edit_posts') == 0) ? $this->request->post('edit_posts') : ($is_admin_group) ? '1' : '0';
        $delete_posts = ($this->request->post('delete_posts') == 0) ? $this->request->post('delete_posts') : ($is_admin_group) ? '1' : '0';
        $delete_topics = ($this->request->post('delete_topics') == 0) ? $this->request->post('delete_topics') : ($is_admin_group) ? '1' : '0';
        $post_links = ($this->request->post('post_links') == 0) ? $this->request->post('post_links') : '1';
        $set_title = ($this->request->post('set_title') == 0) ? $this->request->post('set_title') : ($is_admin_group) ? '1' : '0';
        $search = ($this->request->post('search') == 0) ? $this->request->post('search') : '1';
        $search_users = ($this->request->post('search_users') == 0) ? $this->request->post('search_users') : '1';
        $send_email = ($this->request->post('send_email') && $this->request->post('send_email') == '1') || $is_admin_group ? '1' : '0';
        $post_flood = ($this->request->post('post_flood') && $this->request->post('post_flood') >= 0) ? $this->request->post('post_flood') : '0';
        $search_flood = ($this->request->post('search_flood') && $this->request->post('search_flood') >= 0) ? $this->request->post('search_flood') : '0';
        $email_flood = ($this->request->post('email_flood') && $this->request->post('email_flood') >= 0) ? $this->request->post('email_flood') : '0';
        $report_flood = ($this->request->post('report_flood') >= 0) ? $this->request->post('report_flood') : '0';

        if ($title == '') {
            message($lang_admin_groups['Must enter title message']);
        }

        $user_title = ($user_title != '') ? '\''.$this->db->escape($user_title).'\'' : 'NULL';

        if ($this->request->post('mode') == 'add') {
            $result = $this->db->query('SELECT 1 FROM '.$this->db->prefix.'groups WHERE g_title=\''.$this->db->escape($title).'\'') or error('Unable to check group title collision', __FILE__, __LINE__, $this->db->error());
            if ($this->db->num_rows($result)) {
                message(sprintf($lang_admin_groups['Title already exists message'], pun_htmlspecialchars($title)));
            }

            $this->db->query('INSERT INTO '.$this->db->prefix.'groups (g_title, g_user_title, g_promote_min_posts, g_promote_next_group, g_moderator, g_mod_edit_users, g_mod_rename_users, g_mod_change_passwords, g_mod_ban_users, g_mod_promote_users, g_read_board, g_view_users, g_post_replies, g_post_topics, g_edit_posts, g_delete_posts, g_delete_topics, g_post_links, g_set_title, g_search, g_search_users, g_send_email, g_post_flood, g_search_flood, g_email_flood, g_report_flood) VALUES(\''.$this->db->escape($title).'\', '.$user_title.', '.$promote_min_posts.', '.$promote_next_group.', '.$moderator.', '.$mod_edit_users.', '.$mod_rename_users.', '.$mod_change_passwords.', '.$mod_ban_users.', '.$mod_promote_users.', '.$read_board.', '.$view_users.', '.$post_replies.', '.$post_topics.', '.$edit_posts.', '.$delete_posts.', '.$delete_topics.', '.$post_links.', '.$set_title.', '.$search.', '.$search_users.', '.$send_email.', '.$post_flood.', '.$search_flood.', '.$email_flood.', '.$report_flood.')') or error('Unable to add group', __FILE__, __LINE__, $this->db->error());
            $new_group_id = $this->db->insert_id();

            // Now lets copy the forum specific permissions from the group which this group is based on
            $result = $this->db->query('SELECT forum_id, read_forum, post_replies, post_topics FROM '.$this->db->prefix.'forum_perms WHERE group_id='.$this->request->post('base_group')) or error('Unable to fetch group forum permission list', __FILE__, __LINE__, $this->db->error());
            while ($cur_forum_perm = $this->db->fetch_assoc($result)) {
                $this->db->query('INSERT INTO '.$this->db->prefix.'forum_perms (group_id, forum_id, read_forum, post_replies, post_topics) VALUES('.$new_group_id.', '.$cur_forum_perm['forum_id'].', '.$cur_forum_perm['read_forum'].', '.$cur_forum_perm['post_replies'].', '.$cur_forum_perm['post_topics'].')') or error('Unable to insert group forum permissions', __FILE__, __LINE__, $this->db->error());
            }
        } else {
            $result = $this->db->query('SELECT 1 FROM '.$this->db->prefix.'groups WHERE g_title=\''.$this->db->escape($title).'\' AND g_id!='.$this->request->post('group_id')) or error('Unable to check group title collision', __FILE__, __LINE__, $this->db->error());
            if ($this->db->num_rows($result)) {
                message(sprintf($lang_admin_groups['Title already exists message'], pun_htmlspecialchars($title)));
            }

            $this->db->query('UPDATE '.$this->db->prefix.'groups SET g_title=\''.$this->db->escape($title).'\', g_user_title='.$user_title.', g_promote_min_posts='.$promote_min_posts.', g_promote_next_group='.$promote_next_group.', g_moderator='.$moderator.', g_mod_edit_users='.$mod_edit_users.', g_mod_rename_users='.$mod_rename_users.', g_mod_change_passwords='.$mod_change_passwords.', g_mod_ban_users='.$mod_ban_users.', g_mod_promote_users='.$mod_promote_users.', g_read_board='.$read_board.', g_view_users='.$view_users.', g_post_replies='.$post_replies.', g_post_topics='.$post_topics.', g_edit_posts='.$edit_posts.', g_delete_posts='.$delete_posts.', g_delete_topics='.$delete_topics.', g_post_links='.$post_links.', g_set_title='.$set_title.', g_search='.$search.', g_search_users='.$search_users.', g_send_email='.$send_email.', g_post_flood='.$post_flood.', g_search_flood='.$search_flood.', g_email_flood='.$email_flood.', g_report_flood='.$report_flood.' WHERE g_id='.$this->request->post('group_id')) or error('Unable to update group', __FILE__, __LINE__, $this->db->error());

            // Promote all users who would be promoted to this group on their next post
            if ($promote_next_group) {
                $this->db->query('UPDATE '.$this->db->prefix.'users SET group_id = '.$promote_next_group.' WHERE group_id = '.$this->request->post('group_id').' AND num_posts >= '.$promote_min_posts) or error('Unable to auto-promote existing users', __FILE__, __LINE__, $this->db->error());
            }
        }

        // Regenerate the quick jump cache
        if (!defined('FORUM_CACHE_FUNCTIONS_LOADED')) {
            require FEATHER_ROOT.'include/cache.php';
        }

        $group_id = $this->request->post('mode') == 'add' ? $new_group_id : $this->request->post('group_id');

        generate_quickjump_cache($group_id);

        if ($this->request->post('mode') == 'edit') {
            redirect(get_link('admin/groups/'), $lang_admin_groups['Group edited redirect']);
        } else {
            redirect(get_link('admin/groups/'), $lang_admin_groups['Group added redirect']);
        }
    }

    public function set_default_group($groups, $feather)
    {
        global $lang_admin_groups, $lang_common;

        confirm_referrer(get_link_r('admin/groups/'));

        $group_id = intval($this->request->post('default_group'));

        // Make sure it's not the admin or guest groups
        if ($group_id == FEATHER_ADMIN || $group_id == FEATHER_GUEST) {
            message($lang_common['Bad request'], false, '404 Not Found');
        }

        // Make sure it's not a moderator group
        if ($groups[$group_id]['g_moderator'] != 0) {
            message($lang_common['Bad request'], false, '404 Not Found');
        }

        $this->db->query('UPDATE '.$this->db->prefix.'config SET conf_value='.$group_id.' WHERE conf_name=\'o_default_user_group\'') or error('Unable to update board config', __FILE__, __LINE__, $this->db->error());

        // Regenerate the config cache
        if (!defined('FORUM_CACHE_FUNCTIONS_LOADED')) {
            require FEATHER_ROOT.'include/cache.php';
        }

        generate_config_cache();

        redirect(get_link('admin/groups/'), $lang_admin_groups['Default group redirect']);
    }

    public function check_members($group_id)
    {
        

        $result = $this->db->query('SELECT g.g_title, COUNT(u.id) FROM '.$this->db->prefix.'groups AS g INNER JOIN '.$this->db->prefix.'users AS u ON g.g_id=u.group_id WHERE g.g_id='.$group_id.' GROUP BY g.g_id, g_title') or error('Unable to fetch group info', __FILE__, __LINE__, $this->db->error());

        if ($this->db->num_rows($result)) {
            $is_member = true;
        } else {
            $is_member = false;
        }

        return $is_member;
    }

    public function delete_group($feather, $group_id)
    {
        global $lang_admin_groups;

        if ($this->request->post('del_group')) {
            $move_to_group = intval($this->request->post('move_to_group'));
            $this->db->query('UPDATE '.$this->db->prefix.'users SET group_id='.$move_to_group.' WHERE group_id='.$group_id) or error('Unable to move users into group', __FILE__, __LINE__, $this->db->error());
        }

        // Delete the group and any forum specific permissions
        $this->db->query('DELETE FROM '.$this->db->prefix.'groups WHERE g_id='.$group_id) or error('Unable to delete group', __FILE__, __LINE__, $this->db->error());
        $this->db->query('DELETE FROM '.$this->db->prefix.'forum_perms WHERE group_id='.$group_id) or error('Unable to delete group forum permissions', __FILE__, __LINE__, $this->db->error());

        // Don't let users be promoted to this group
        $this->db->query('UPDATE '.$this->db->prefix.'groups SET g_promote_next_group=0 WHERE g_promote_next_group='.$group_id) or error('Unable to remove group as promotion target', __FILE__, __LINE__, $this->db->error());

        redirect(get_link('admin/groups/'), $lang_admin_groups['Group removed redirect']);
    }

    public function get_group_title($group_id)
    {
        

        $result = $this->db->query('SELECT g_title FROM '.$this->db->prefix.'groups WHERE g_id='.$group_id) or error('Unable to fetch group title', __FILE__, __LINE__, $this->db->error());
        $group_title = $this->db->result($result);

        return $group_title;
    }

    public function get_title_members($group_id)
    {
        

        $result = $this->db->query('SELECT g.g_title, COUNT(u.id) FROM '.$this->db->prefix.'groups AS g INNER JOIN '.$this->db->prefix.'users AS u ON g.g_id=u.group_id WHERE g.g_id='.$group_id.' GROUP BY g.g_id, g_title') or error('Unable to fetch group info', __FILE__, __LINE__, $this->db->error());

        list($group_info['title'], $group_info['members']) = $this->db->fetch_row($result);

        return $group_info;
    }
}