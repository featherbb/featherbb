<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace model\admin;

class users
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
 
    public function get_num_ip($ip_stats)
    {
        $num_ips = \ORM::for_table($this->db->prefix.'posts')->where('poster_id', $ip_stats)
                        ->group_by('poster_ip')
                        ->count('poster_ip');

        return $num_ips;
    }

    public function get_ip_stats($ip_stats, $start_from)
    {
        $ip_data = array();

        $result = \ORM::for_table($this->db->prefix.'posts')->where('poster_id', $ip_stats)
                    ->select('poster_ip')
                    ->select_expr('MAX(posted)', 'last_used')
                    ->select_expr('COUNT(id)', 'used_times')
                    ->select('poster_ip')
                    ->group_by('poster_ip')
                    ->order_by_desc('last_used')
                    ->offset($start_from)
                    ->limit(50)
                    ->find_many();
        if ($result) {
            foreach ($result as $cur_ip) {
                $ip_data[] = $cur_ip;
            }
        }

        return $ip_data;
    }

    public function get_num_users_ip($ip)
    {
        $num_users = \ORM::for_table($this->db->prefix.'posts')->where('poster_ip', $ip)
                        ->distinct()
                        ->count('poster_id');

        return $num_users;
    }

    public function get_num_users_search($conditions)
    {
        $num_users = \ORM::for_table($this->db->prefix.'users')->table_alias('u')
                        ->left_outer_join($this->db->prefix.'groups', array('g.g_id', '=', 'u.group_id'), 'g')
                        ->where_raw('u.id>1'.(!empty($conditions) ? ' AND '.implode(' AND ', $conditions) : ''))
                        ->count('id');

        return $num_users;
    }

    public function get_info_poster($ip, $start_from)
    {
        $info = array();

        $select_info_get_info_poster = array('poster_id', 'poster');

        $result = \ORM::for_table($this->db->prefix.'posts')->select_many($select_info_get_info_poster)
                        ->distinct()
                        ->where('poster_ip', $ip)
                        ->order_by_asc('poster')
                        ->offset($start_from)
                        ->limit(50)
                        ->find_many();

        $info['num_posts'] = count($result);

        if ($result) {
            $poster_ids = array();
            foreach($result as $cur_poster) {
                $info['posters'][] = $cur_poster;
                $poster_ids[] = $cur_poster['poster_id'];
            }

            $select_get_info_poster = array('u.id', 'u.username', 'u.email', 'u.title', 'u.num_posts', 'u.admin_note', 'g.g_id', 'g.g_user_title');

            $result = \ORM::for_table($this->db->prefix.'users')->table_alias('u')
                ->select_many($select_get_info_poster)
                ->inner_join($this->db->prefix.'groups', array('g.g_id', '=', 'u.group_id'), 'g')
                ->where_gt('u.id', 1)
                ->where_in('u.id', $poster_ids)
                ->find_many();

            foreach ($result as $cur_user) {
                $info['user_data'][$cur_user['id']] = $cur_user;
            }
        }

        return $info;
    }

    public function move_users()
    {
        global $lang_admin_users;

        $move = array();

        if ($this->request->post('users')) {
            $move['user_ids'] = is_array($this->request->post('users')) ? array_keys($this->request->post('users')) : explode(',', $this->request->post('users'));
            $move['user_ids'] = array_map('intval', $move['user_ids']);

            // Delete invalid IDs
            $move['user_ids'] = array_diff($move['user_ids'], array(0, 1));
        } else {
            $move['user_ids'] = array();
        }

        if (empty($move['user_ids'])) {
            message($lang_admin_users['No users selected']);
        }

        // Are we trying to batch move any admins?
        $is_admin = \ORM::for_table($this->db->prefix.'users')->where_in('id', $move['user_ids'])
                        ->where('group_id', FEATHER_ADMIN)
                        ->find_one();
        if ($is_admin) {
            message($lang_admin_users['No move admins message']);
        }

        // Fetch all user groups
        $select_user_groups = array('g_id', 'g_title');
        $where_not_in = array(FEATHER_GUEST, FEATHER_ADMIN);

        $result = \ORM::for_table($this->db->prefix.'groups')->select_many($select_user_groups)
            ->where_not_in('g_id', $where_not_in)
            ->order_by_asc('g_title')
            ->find_many();

        foreach ($result as $row) {
            $move['all_groups'][$row['g_id']] = $row['g_title'];
        }

        if ($this->request->post('move_users_comply')) {
            $new_group = $this->request->post('new_group') && isset($move['all_groups'][$this->request->post('new_group')]) ? $this->request->post('new_group') : message($lang_admin_users['Invalid group message']);

            // Is the new group a moderator group?
            $result = $this->db->query('SELECT g_moderator FROM '.$this->db->prefix.'groups WHERE g_id='.$new_group) or error('Unable to fetch group info', __FILE__, __LINE__, $this->db->error());
            $new_group_mod = $this->db->result($result);

            // Fetch user groups
            $user_groups = array();
            $select_fetch_user_groups = array('id', 'group_id');
            $result = \ORM::for_table($this->db->prefix.'users')->select_many($select_fetch_user_groups)
                            ->where_in('id', $move['user_ids'])
                            ->find_many();
            foreach($result as $cur_user) {
                if (!isset($user_groups[$cur_user['group_id']])) {
                    $user_groups[$cur_user['group_id']] = array();
                }

                $user_groups[$cur_user['group_id']][] = $cur_user['id'];
            }

            // Are any users moderators?
            $group_ids = array_keys($user_groups);
            $select_fetch_user_mods = array('g_id', 'g_moderator');
            $result = \ORM::for_table($this->db->prefix.'groups')->select_many($select_fetch_user_mods)
                            ->where_in('g_id', $group_ids)
                            ->find_many();
            foreach($result as $cur_group) {
                if ($cur_group['g_moderator'] == '0') {
                    unset($user_groups[$cur_group['g_id']]);
                }
            }

            if (!empty($user_groups) && $new_group != FEATHER_ADMIN && $new_group_mod != '1') {
                // Fetch forum list and clean up their moderator list
                $select_mods = array('id', 'moderators');
                $result = \ORM::for_table($this->feather->prefix.'forums')
                            ->select_many($select_mods)
                            ->find_many();

                foreach($result as $cur_forum) {
                    $cur_moderators = ($cur_forum['moderators'] != '') ? unserialize($cur_forum['moderators']) : array();

                    foreach ($user_groups as $group_users) {
                        $cur_moderators = array_diff($cur_moderators, $group_users);
                    }

                    if (!empty($cur_moderators)) {
                        \ORM::for_table($this->db->prefix.'forums')->where('id', $cur_forum['id'])
                            ->find_one()
                            ->set('moderators', serialize($cur_moderators))
                            ->save();
                    } else {
                        \ORM::for_table($this->db->prefix.'forums')->where('id', $cur_forum['id'])
                            ->find_one()
                            ->set_expr('moderators', 'NULL')
                            ->save();
                    }
                }
            }

            // Change user group
            \ORM::for_table($this->db->prefix.'users')->where_in('id', $move['user_ids'])
                                                      ->update_many('group_id', $new_group);

            redirect(get_link('admin/users/'), $lang_admin_users['Users move redirect']);
        }

        return $move;
    }

    public function delete_users()
    {
        global $lang_admin_users;

        if ($this->request->post('users')) {
            $user_ids = is_array($this->request->post('users')) ? array_keys($this->request->post('users')) : explode(',', $this->request->post('users'));
            $user_ids = array_map('intval', $user_ids);

            // Delete invalid IDs
            $user_ids = array_diff($user_ids, array(0, 1));
        } else {
            $user_ids = array();
        }

        if (empty($user_ids)) {
            message($lang_admin_users['No users selected']);
        }

        // Are we trying to delete any admins?
        $result = $this->db->query('SELECT COUNT(*) FROM '.$this->db->prefix.'users WHERE id IN ('.implode(',', $user_ids).') AND group_id='.FEATHER_ADMIN) or error('Unable to fetch user info', __FILE__, __LINE__, $this->db->error());
        if ($this->db->result($result) > 0) {
            message($lang_admin_users['No delete admins message']);
        }

        if ($this->request->post('delete_users_comply')) {
            // Fetch user groups
            $user_groups = array();
            $result = $this->db->query('SELECT id, group_id FROM '.$this->db->prefix.'users WHERE id IN ('.implode(',', $user_ids).')') or error('Unable to fetch user groups', __FILE__, __LINE__, $this->db->error());
            while ($cur_user = $this->db->fetch_assoc($result)) {
                if (!isset($user_groups[$cur_user['group_id']])) {
                    $user_groups[$cur_user['group_id']] = array();
                }

                $user_groups[$cur_user['group_id']][] = $cur_user['id'];
            }

            // Are any users moderators?
            $group_ids = array_keys($user_groups);
            $result = $this->db->query('SELECT g_id, g_moderator FROM '.$this->db->prefix.'groups WHERE g_id IN ('.implode(',', $group_ids).')') or error('Unable to fetch group moderators', __FILE__, __LINE__, $this->db->error());
            while ($cur_group = $this->db->fetch_assoc($result)) {
                if ($cur_group['g_moderator'] == '0') {
                    unset($user_groups[$cur_group['g_id']]);
                }
            }

            // Fetch forum list and clean up their moderator list
            $result = $this->db->query('SELECT id, moderators FROM '.$this->db->prefix.'forums') or error('Unable to fetch forum list', __FILE__, __LINE__, $this->db->error());
            while ($cur_forum = $this->db->fetch_assoc($result)) {
                $cur_moderators = ($cur_forum['moderators'] != '') ? unserialize($cur_forum['moderators']) : array();

                foreach ($user_groups as $group_users) {
                    $cur_moderators = array_diff($cur_moderators, $group_users);
                }

                $cur_moderators = (!empty($cur_moderators)) ? '\''.$this->db->escape(serialize($cur_moderators)).'\'' : 'NULL';
                $this->db->query('UPDATE '.$this->db->prefix.'forums SET moderators='.$cur_moderators.' WHERE id='.$cur_forum['id']) or error('Unable to update forum', __FILE__, __LINE__, $this->db->error());
            }

            // Delete any subscriptions
            $this->db->query('DELETE FROM '.$this->db->prefix.'topic_subscriptions WHERE user_id IN ('.implode(',', $user_ids).')') or error('Unable to delete topic subscriptions', __FILE__, __LINE__, $this->db->error());
            $this->db->query('DELETE FROM '.$this->db->prefix.'forum_subscriptions WHERE user_id IN ('.implode(',', $user_ids).')') or error('Unable to delete forum subscriptions', __FILE__, __LINE__, $this->db->error());

            // Remove them from the online list (if they happen to be logged in)
            $this->db->query('DELETE FROM '.$this->db->prefix.'online WHERE user_id IN ('.implode(',', $user_ids).')') or error('Unable to remove users from online list', __FILE__, __LINE__, $this->db->error());

            // Should we delete all posts made by these users?
            if ($this->request->post('delete_posts')) {
                require FEATHER_ROOT.'include/search_idx.php';
                @set_time_limit(0);

                // Find all posts made by this user
                $result = $this->db->query('SELECT p.id, p.topic_id, t.forum_id FROM '.$this->db->prefix.'posts AS p INNER JOIN '.$this->db->prefix.'topics AS t ON t.id=p.topic_id INNER JOIN '.$this->db->prefix.'forums AS f ON f.id=t.forum_id WHERE p.poster_id IN ('.implode(',', $user_ids).')') or error('Unable to fetch posts', __FILE__, __LINE__, $this->db->error());
                if ($this->db->num_rows($result)) {
                    while ($cur_post = $this->db->fetch_assoc($result)) {
                        // Determine whether this post is the "topic post" or not
                        $result2 = $this->db->query('SELECT id FROM '.$this->db->prefix.'posts WHERE topic_id='.$cur_post['topic_id'].' ORDER BY posted LIMIT 1') or error('Unable to fetch post info', __FILE__, __LINE__, $this->db->error());

                        if ($this->db->result($result2) == $cur_post['id']) {
                            delete_topic($cur_post['topic_id']);
                        } else {
                            delete_post($cur_post['id'], $cur_post['topic_id']);
                        }

                        update_forum($cur_post['forum_id']);
                    }
                }
            } else {
                // Set all their posts to guest
                $this->db->query('UPDATE '.$this->db->prefix.'posts SET poster_id=1 WHERE poster_id IN ('.implode(',', $user_ids).')') or error('Unable to update posts', __FILE__, __LINE__, $this->db->error());
            }

            // Delete the users
            $this->db->query('DELETE FROM '.$this->db->prefix.'users WHERE id IN ('.implode(',', $user_ids).')') or error('Unable to delete users', __FILE__, __LINE__, $this->db->error());

            // Delete user avatars
            foreach ($user_ids as $user_id) {
                delete_avatar($user_id);
            }

            // Regenerate the users info cache
            if (!defined('FORUM_CACHE_FUNCTIONS_LOADED')) {
                require FEATHER_ROOT.'include/cache.php';
            }

            generate_users_info_cache();

            redirect(get_link('admin/users/'), $lang_admin_users['Users delete redirect']);
        }

        return $user_ids;
    }

    public function ban_users()
    {
        global $lang_admin_users;

        if ($this->request->post('users')) {
            $user_ids = is_array($this->request->post('users')) ? array_keys($this->request->post('users')) : explode(',', $this->request->post('users'));
            $user_ids = array_map('intval', $user_ids);

            // Delete invalid IDs
            $user_ids = array_diff($user_ids, array(0, 1));
        } else {
            $user_ids = array();
        }

        if (empty($user_ids)) {
            message($lang_admin_users['No users selected']);
        }

        // Are we trying to ban any admins?
        $result = $this->db->query('SELECT COUNT(*) FROM '.$this->db->prefix.'users WHERE id IN ('.implode(',', $user_ids).') AND group_id='.FEATHER_ADMIN) or error('Unable to fetch group info', __FILE__, __LINE__, $this->db->error());
        if ($this->db->result($result) > 0) {
            message($lang_admin_users['No ban admins message']);
        }

        // Also, we cannot ban moderators
        $result = $this->db->query('SELECT COUNT(*) FROM '.$this->db->prefix.'users AS u INNER JOIN '.$this->db->prefix.'groups AS g ON u.group_id=g.g_id WHERE g.g_moderator=1 AND u.id IN ('.implode(',', $user_ids).')') or error('Unable to fetch moderator group info', __FILE__, __LINE__, $this->db->error());
        if ($this->db->result($result) > 0) {
            message($lang_admin_users['No ban mods message']);
        }

        if ($this->request->post('ban_users_comply')) {
            $ban_message = feather_trim($this->request->post('ban_message'));
            $ban_expire = feather_trim($this->request->post('ban_expire'));
            $ban_the_ip = $this->request->post('ban_the_ip') ? intval($this->request->post('ban_the_ip')) : 0;

            if ($ban_expire != '' && $ban_expire != 'Never') {
                $ban_expire = strtotime($ban_expire.' GMT');

                if ($ban_expire == -1 || !$ban_expire) {
                    message($lang_admin_users['Invalid date message'].' '.$lang_admin_users['Invalid date reasons']);
                }

                $diff = ($this->user->timezone + $this->user->dst) * 3600;
                $ban_expire -= $diff;

                if ($ban_expire <= time()) {
                    message($lang_admin_users['Invalid date message'].' '.$lang_admin_users['Invalid date reasons']);
                }
            } else {
                $ban_expire = 'NULL';
            }

            $ban_message = ($ban_message != '') ? '\''.$this->db->escape($ban_message).'\'' : 'NULL';

            // Fetch user information
            $user_info = array();
            $result = $this->db->query('SELECT id, username, email, registration_ip FROM '.$this->db->prefix.'users WHERE id IN ('.implode(',', $user_ids).')') or error('Unable to fetch user info', __FILE__, __LINE__, $this->db->error());
            while ($cur_user = $this->db->fetch_assoc($result)) {
                $user_info[$cur_user['id']] = array('username' => $cur_user['username'], 'email' => $cur_user['email'], 'ip' => $cur_user['registration_ip']);
            }

            // Overwrite the registration IP with one from the last post (if it exists)
            if ($ban_the_ip != 0) {
                $result = $this->db->query('SELECT p.poster_id, p.poster_ip FROM '.$this->db->prefix.'posts AS p INNER JOIN (SELECT MAX(id) AS id FROM '.$this->db->prefix.'posts WHERE poster_id IN ('.implode(',', $user_ids).') GROUP BY poster_id) AS i ON p.id=i.id') or error('Unable to fetch post info', __FILE__, __LINE__, $this->db->error());
                while ($cur_address = $this->db->fetch_assoc($result)) {
                    $user_info[$cur_address['poster_id']]['ip'] = $cur_address['poster_ip'];
                }
            }

            // And insert the bans!
            foreach ($user_ids as $user_id) {
                $ban_username = '\''.$this->db->escape($user_info[$user_id]['username']).'\'';
                $ban_email = '\''.$this->db->escape($user_info[$user_id]['email']).'\'';
                $ban_ip = ($ban_the_ip != 0) ? '\''.$this->db->escape($user_info[$user_id]['ip']).'\'' : 'NULL';

                $this->db->query('INSERT INTO '.$this->db->prefix.'bans (username, ip, email, message, expire, ban_creator) VALUES('.$ban_username.', '.$ban_ip.', '.$ban_email.', '.$ban_message.', '.$ban_expire.', '.$this->user->id.')') or error('Unable to add ban', __FILE__, __LINE__, $this->db->error());
            }

            // Regenerate the bans cache
            if (!defined('FORUM_CACHE_FUNCTIONS_LOADED')) {
                require FEATHER_ROOT.'include/cache.php';
            }

            generate_bans_cache();

            redirect(get_link('admin/users/'), $lang_admin_users['Users banned redirect']);
        }

        return $user_ids;
    }

    public function get_user_search()
    {
        global $db_type, $lang_common, $lang_admin_users;

        $form = $this->request->get('form') ? $this->request->get('form') : array();

        $search = array();

        // trim() all elements in $form
        $form = array_map('feather_trim', $form);

        $posts_greater = $this->request->get('posts_greater') ? feather_trim($this->request->get('posts_greater')) : '';
        $posts_less = $this->request->get('posts_less') ? feather_trim($this->request->get('posts_less')) : '';
        $last_post_after = $this->request->get('last_post_after') ? feather_trim($this->request->get('last_post_after')) : '';
        $last_post_before = $this->request->get('last_post_before') ? feather_trim($this->request->get('last_post_before')) : '';
        $last_visit_after = $this->request->get('last_visit_after') ? feather_trim($this->request->get('last_visit_after')) : '';
        $last_visit_before = $this->request->get('last_visit_before') ? feather_trim($this->request->get('last_visit_before')) : '';
        $registered_after = $this->request->get('registered_after') ? feather_trim($this->request->get('registered_after')) : '';
        $registered_before = $this->request->get('registered_before') ? feather_trim($this->request->get('registered_before')) : '';
        $order_by = $search['order_by'] = $this->request->get('order_by') && in_array($this->request->get('order_by'), array('username', 'email', 'num_posts', 'last_post', 'last_visit', 'registered')) ? $this->request->get('order_by') : 'username';
        $direction = $search['direction'] = $this->request->get('direction') && $this->request->get('direction') == 'DESC' ? 'DESC' : 'ASC';
        $user_group = $this->request->get('user_group') ? intval($this->request->get('user_group')) : -1;

        $search['query_str'][] = 'order_by='.$order_by;
        $search['query_str'][] = 'direction='.$direction;
        $search['query_str'][] = 'user_group='.$user_group;

        if (preg_match('%[^0-9]%', $posts_greater.$posts_less)) {
            message($lang_admin_users['Non numeric message']);
        }

        $search['conditions'] = array();

        // Try to convert date/time to timestamps
        if ($last_post_after != '') {
            $search['query_str'][] = 'last_post_after='.$last_post_after;

            $last_post_after = strtotime($last_post_after);
            if ($last_post_after === false || $last_post_after == -1) {
                message($lang_admin_users['Invalid date time message']);
            }

            $search['conditions'][] = 'u.last_post>'.$last_post_after;
        }
        if ($last_post_before != '') {
            $search['query_str'][] = 'last_post_before='.$last_post_before;

            $last_post_before = strtotime($last_post_before);
            if ($last_post_before === false || $last_post_before == -1) {
                message($lang_admin_users['Invalid date time message']);
            }

            $search['conditions'][] = 'u.last_post<'.$last_post_before;
        }
        if ($last_visit_after != '') {
            $search['query_str'][] = 'last_visit_after='.$last_visit_after;

            $last_visit_after = strtotime($last_visit_after);
            if ($last_visit_after === false || $last_visit_after == -1) {
                message($lang_admin_users['Invalid date time message']);
            }

            $search['conditions'][] = 'u.last_visit>'.$last_visit_after;
        }
        if ($last_visit_before != '') {
            $search['query_str'][] = 'last_visit_before='.$last_visit_before;

            $last_visit_before = strtotime($last_visit_before);
            if ($last_visit_before === false || $last_visit_before == -1) {
                message($lang_admin_users['Invalid date time message']);
            }

            $search['conditions'][] = 'u.last_visit<'.$last_visit_before;
        }
        if ($registered_after != '') {
            $search['query_str'][] = 'registered_after='.$registered_after;

            $registered_after = strtotime($registered_after);
            if ($registered_after === false || $registered_after == -1) {
                message($lang_admin_users['Invalid date time message']);
            }

            $search['conditions'][] = 'u.registered>'.$registered_after;
        }
        if ($registered_before != '') {
            $search['query_str'][] = 'registered_before='.$registered_before;

            $registered_before = strtotime($registered_before);
            if ($registered_before === false || $registered_before == -1) {
                message($lang_admin_users['Invalid date time message']);
            }

            $search['conditions'][] = 'u.registered<'.$registered_before;
        }

        $like_command = ($db_type == 'pgsql') ? 'ILIKE' : 'LIKE';
        foreach ($form as $key => $input) {
            if ($input != '' && in_array($key, array('username', 'email', 'title', 'realname', 'url', 'jabber', 'icq', 'msn', 'aim', 'yahoo', 'location', 'signature', 'admin_note'))) {
                $search['conditions'][] = 'u.'.$this->db->escape($key).' '.$like_command.' \''.$this->db->escape(str_replace('*', '%', $input)).'\'';
                $search['query_str'][] = 'form%5B'.$key.'%5D='.urlencode($input);
            }
        }

        if ($posts_greater != '') {
            $search['query_str'][] = 'posts_greater='.$posts_greater;
            $search['conditions'][] = 'u.num_posts>'.$posts_greater;
        }
        if ($posts_less != '') {
            $search['query_str'][] = 'posts_less='.$posts_less;
            $search['conditions'][] = 'u.num_posts<'.$posts_less;
        }

        if ($user_group > -1) {
            $search['conditions'][] = 'u.group_id='.$user_group;
        }

        return $search;
    }

    public function print_users($conditions, $order_by, $direction, $start_from)
    {
        global $lang_common, $lang_admin_users;

        $user_data = array();

        $result = $this->db->query('SELECT u.id, u.username, u.email, u.title, u.num_posts, u.admin_note, g.g_id, g.g_user_title FROM '.$this->db->prefix.'users AS u LEFT JOIN '.$this->db->prefix.'groups AS g ON g.g_id=u.group_id WHERE u.id>1'.(!empty($conditions) ? ' AND '.implode(' AND ', $conditions) : '').' ORDER BY '.$this->db->escape($order_by).' '.$this->db->escape($direction).' LIMIT '.$start_from.', 50') or error('Unable to fetch user info', __FILE__, __LINE__, $this->db->error());
        if ($this->db->num_rows($result)) {
            while ($cur_user = $this->db->fetch_assoc($result)) {
                $cur_user['user_title'] = get_title($cur_user);

                // This script is a special case in that we want to display "Not verified" for non-verified users
                if (($cur_user['g_id'] == '' || $cur_user['g_id'] == FEATHER_UNVERIFIED) && $cur_user['user_title'] != $lang_common['Banned']) {
                    $cur_user['user_title'] = '<span class="warntext">'.$lang_admin_users['Not verified'].'</span>';
                }

                $user_data[] = $cur_user;
            }
        }

        return $user_data;
    }

    public function get_group_list()
    {
        $output = '';

        $result = $this->db->query('SELECT g_id, g_title FROM '.$this->db->prefix.'groups WHERE g_id!='.FEATHER_GUEST.' ORDER BY g_title') or error('Unable to fetch user group list', __FILE__, __LINE__, $this->db->error());

        while ($cur_group = $this->db->fetch_assoc($result)) {
            $output .= "\t\t\t\t\t\t\t\t\t\t\t".'<option value="'.$cur_group['g_id'].'">'.feather_escape($cur_group['g_title']).'</option>'."\n";
        }

        return $output;
    }
}