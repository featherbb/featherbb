<?php

/**
 * Copyright (C) 2015-2017 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace FeatherBB\Model\Admin;

use FeatherBB\Core\Database as DB;
use FeatherBB\Core\Error;
use FeatherBB\Core\Url;
use FeatherBB\Core\Utils;
use FeatherBB\Model\Cache;

// use FeatherBB\Model\Delete;

class Users
{
    public function get_num_ip($ip_stats)
    {
        $num_ips = DB::for_table('posts')->where('poster_id', $ip_stats)->group_by('poster_ip');
        $num_ips = Container::get('hooks')->fireDB('model.admin.model.admin.users.get_num_ip', $num_ips);
        $num_ips = $num_ips->count('poster_ip');

        return $num_ips;
    }

    public function get_ip_stats($ip_stats, $start_from)
    {
        $ip_data = [];

        $result = DB::for_table('posts')->where('poster_id', $ip_stats)
                    ->select('poster_ip')
                    ->select_expr('MAX(posted)', 'last_used')
                    ->select_expr('COUNT(id)', 'used_times')
                    ->select('poster_ip')
                    ->group_by('poster_ip')
                    ->order_by_desc('last_used')
                    ->offset($start_from)
                    ->limit(50);
        $result = Container::get('hooks')->fireDB('model.admin.model.admin.users.get_ip_stats.query', $result);
        $result = $result->find_many();

        if ($result) {
            foreach ($result as $cur_ip) {
                $ip_data[] = $cur_ip;
            }
        }

        $ip_data = Container::get('hooks')->fire('model.admin.model.users.get_ip_stats.ip_data', $ip_data);
        return $ip_data;
    }

    public function get_num_users_ip($ip)
    {
        $num_users = DB::for_table('posts')->where('poster_ip', $ip)->distinct();
        $num_users = Container::get('hooks')->fireDB('model.admin.model.admin.users.get_num_users_ip.query', $num_users);
        $num_users = $num_users->count('poster_id');

        return $num_users;
    }

    public function get_num_users_search($conditions)
    {
        $conditions = Container::get('hooks')->fire('model.admin.model.users.get_num_users_search.conditions', $conditions);

        $num_users = DB::for_table('users')->table_alias('u')
                        ->left_outer_join('groups', ['g.g_id', '=', 'u.group_id'], 'g')
                        ->where_raw('u.id>1'.(!empty($conditions) ? ' AND '.implode(' AND ', $conditions) : ''));
        $num_users = Container::get('hooks')->fireDB('model.admin.model.admin.users.get_num_users_search.query', $num_users);
        $num_users = $num_users->count('id');

        return $num_users;
    }

    public function get_info_poster($ip, $start_from)
    {
        $ip = Container::get('hooks')->fire('model.admin.model.users.get_info_poster.ip', $ip);

        $info = [];

        $select_info_get_info_poster = ['poster_id', 'poster'];

        $result = DB::for_table('posts')->select_many($select_info_get_info_poster)
                        ->distinct()
                        ->where('poster_ip', $ip)
                        ->order_by_asc('poster')
                        ->offset($start_from)
                        ->limit(50);
        $result = Container::get('hooks')->fireDB('model.admin.model.admin.users.get_info_poster.select_info_get_info_poster', $result);
        $result = $result->find_many();

        $info['num_posts'] = count($result);

        if ($result) {
            $poster_ids = [];
            foreach ($result as $cur_poster) {
                $info['posters'][] = $cur_poster;
                $poster_ids[] = $cur_poster['poster_id'];
            }

            $select_get_info_poster = ['u.id', 'u.username', 'u.email', 'u.title', 'u.num_posts', 'u.admin_note', 'g.g_id', 'g.g_user_title'];

            $result = DB::for_table('users')->table_alias('u')
                ->select_many($select_get_info_poster)
                ->inner_join('groups', ['g.g_id', '=', 'u.group_id'], 'g')
                ->where_gt('u.id', 1)
                ->where_in('u.id', $poster_ids);
            $result = Container::get('hooks')->fireDB('model.admin.model.admin.users.get_info_poster.select_get_info_poster', $result);
            $result = $result->find_many();

            foreach ($result as $cur_user) {
                $info['user_data'][$cur_user['id']] = $cur_user;
            }
        }

        $info = Container::get('hooks')->fire('model.admin.model.users.get_info_poster.info', $info);
        return $info;
    }

    public function move_users()
    {
        $move = [];

        if (Input::post('users')) {
            $move['user_ids'] = is_array(Input::post('users')) ? array_keys(Input::post('users')) : explode(',', Input::post('users'));
            $move['user_ids'] = array_map('intval', $move['user_ids']);

            // Delete invalid IDs
            $move['user_ids'] = array_diff($move['user_ids'], [0, 1]);
        } else {
            $move['user_ids'] = [];
        }

        $move['user_ids'] = Container::get('hooks')->fire('model.admin.model.users.move_users.user_ids', $move['user_ids']);

        if (empty($move['user_ids'])) {
            throw new Error(__('No users selected'), 404);
        }

        // Are we trying to batch move any admins?
        $is_admin = DB::for_table('users')->where_in('id', $move['user_ids'])
                        ->where('group_id', ForumEnv::get('FEATHER_ADMIN'))
                        ->find_one();
        if ($is_admin) {
            throw new Error(__('No move admins message'), 403);
        }

        // Fetch all user groups
        $select_user_groups = ['g_id', 'g_title'];
        $where_not_in = [ForumEnv::get('FEATHER_GUEST'), ForumEnv::get('FEATHER_ADMIN')];

        $result = DB::for_table('groups')->select_many($select_user_groups)
            ->where_not_in('g_id', $where_not_in)
            ->order_by_asc('g_title');
        $result = Container::get('hooks')->fireDB('model.admin.model.admin.users.move_users.all_user_groups_query', $result);
        $result = $result->find_many();

        foreach ($result as $row) {
            $move['all_groups'][$row['g_id']] = $row['g_title'];
        }

        if (Input::post('move_users_comply')) {
            if (Input::post('new_group') && isset($move['all_groups'][Input::post('new_group')])) {
                $new_group = Input::post('new_group');
            } else {
                throw new Error(__('Invalid group message'), 400);
            }
            $new_group = Container::get('hooks')->fire('model.admin.model.users.move_users.new_group', $new_group);

            // Is the new group a moderator group?
            $new_group_mod = Container::get('perms')->getGroupPermissions($new_group, 'mod.is_mod');

            // Fetch user groups
            $user_groups = [];
            $select_fetch_user_groups = ['id', 'group_id'];
            $result = DB::for_table('users')->select_many($select_fetch_user_groups)
                ->where_in('id', $move['user_ids']);
            $result = Container::get('hooks')->fireDB('model.admin.model.admin.users.move_users.user_groups_query', $result);
            $result = $result->find_many();

            foreach ($result as $cur_user) {
                if (!isset($user_groups[$cur_user['group_id']])) {
                    $user_groups[$cur_user['group_id']] = [];
                }

                $user_groups[$cur_user['group_id']][] = $cur_user['id'];
            }

            // Are any users moderators?
            $group_ids = array_keys($user_groups);
            foreach ($group_ids as $group_id) {
                if (!Container::get('perms')->getGroupPermissions($group_id, 'mod.is_mod')) {
                    unset($user_groups[$group_id]);
                }
            }

            $user_groups = Container::get('hooks')->fire('model.admin.model.users.move_users.user_groups', $user_groups);

            if (!empty($user_groups) && $new_group != ForumEnv::get('FEATHER_ADMIN') && !$new_group_mod) {
                // Fetch forum list and clean up their moderator list
                $select_mods = ['id', 'moderators'];
                $result = DB::for_table('forums')
                            ->select_many($select_mods)
                            ->find_many();

                foreach ($result as $cur_forum) {
                    $cur_moderators = ($cur_forum['moderators'] != '') ? unserialize($cur_forum['moderators']) : [];

                    foreach ($user_groups as $group_users) {
                        $cur_moderators = array_diff($cur_moderators, $group_users);
                    }

                    if (!empty($cur_moderators)) {
                        DB::for_table('forums')->where('id', $cur_forum['id'])
                            ->find_one()
                            ->set('moderators', serialize($cur_moderators))
                            ->save();
                    } else {
                        DB::for_table('forums')->where('id', $cur_forum['id'])
                            ->find_one()
                            ->set_expr('moderators', 'NULL')
                            ->save();
                    }
                }
            }

            // Change user group
            DB::for_table('users')->where_in('id', $move['user_ids'])
                                                      ->update_many('group_id', $new_group);

            return Router::redirect(Router::pathFor('adminUsers'), __('Users move redirect'));
        }

        $move = Container::get('hooks')->fire('model.admin.model.users.move_users.move', $move);
        return $move;
    }

    public function delete_users()
    {
        if (Input::post('users')) {
            $user_ids = is_array(Input::post('users')) ? array_keys(Input::post('users')) : explode(',', Input::post('users'));
            $user_ids = array_map('intval', $user_ids);

            // Delete invalid IDs
            $user_ids = array_diff($user_ids, [0, 1]);
        } else {
            $user_ids = [];
        }

        $user_ids = Container::get('hooks')->fire('model.admin.model.users.delete_users.user_ids', $user_ids);

        if (empty($user_ids)) {
            throw new Error(__('No users selected'), 404);
        }

        // Are we trying to delete any admins?
        $is_admin = DB::for_table('users')->where_in('id', $user_ids)
            ->where('group_id', ForumEnv::get('FEATHER_ADMIN'))
            ->find_one();
        if ($is_admin) {
            throw new Error(__('No delete admins message'), 403);
        }

        if (Input::post('delete_users_comply')) {
            // Fetch user groups
            $user_groups = [];
            $result['select'] = ['id', 'group_id'];
            $result = DB::for_table('users')
                        ->select_many($result['select'])
                        ->where_in('id', $user_ids);
            $result = Container::get('hooks')->fireDB('model.admin.model.admin.users.delete_users.user_groups_query', $result);
            $result = $result->find_many();

            foreach ($result as $cur_user) {
                if (!isset($user_groups[$cur_user['group_id']])) {
                    $user_groups[$cur_user['group_id']] = [];
                }

                $user_groups[$cur_user['group_id']][] = $cur_user['id'];
            }

            // Are any users moderators?
            $group_ids = array_keys($user_groups);
            foreach ($group_ids as $group_id) {
                if (!Container::get('perms')->getGroupPermissions($group_id, 'mod.is_mod')) {
                    unset($user_groups[$group_id]);
                }
            }

            $user_groups = Container::get('hooks')->fire('model.admin.model.users.delete_users.user_groups', $user_groups);

            // Fetch forum list and clean up their moderator list
            $select_mods = ['id', 'moderators'];
            $result = DB::for_table('forums')
                ->select_many($select_mods)
                ->find_many();

            foreach ($result as $cur_forum) {
                $cur_moderators = ($cur_forum['moderators'] != '') ? unserialize($cur_forum['moderators']) : [];

                foreach ($user_groups as $group_users) {
                    $cur_moderators = array_diff($cur_moderators, $group_users);
                }

                if (!empty($cur_moderators)) {
                    DB::for_table('forums')->where('id', $cur_forum['id'])
                        ->find_one()
                        ->set('moderators', serialize($cur_moderators))
                        ->save();
                } else {
                    DB::for_table('forums')->where('id', $cur_forum['id'])
                        ->find_one()
                        ->set_expr('moderators', 'NULL')
                        ->save();
                }
            }


            // Delete any subscriptions
            DB::for_table('topic_subscriptions')
                    ->where_in('user_id', $user_ids)
                    ->delete_many();
            DB::for_table('forum_subscriptions')
                    ->where_in('user_id', $user_ids)
                    ->delete_many();

            // Remove them from the online list (if they happen to be logged in)
            DB::for_table('online')
                    ->where_in('user_id', $user_ids)
                    ->delete_many();

            // Should we delete all posts made by these users?
            if (Input::post('delete_posts')) {
                @set_time_limit(0);

                // Find all posts made by this user
                $select_user_posts = ['p.id', 'p.topic_id', 't.forum_id'];

                $result = DB::for_table('posts')
                    ->table_alias('p')
                    ->select_many($select_user_posts)
                    ->inner_join('topics', ['t.id', '=', 'p.topic_id'], 't')
                    ->inner_join('forums', ['f.id', '=', 't.forum_id'], 'f')
                    ->where('p.poster_id', $user_ids);
                $result = Container::get('hooks')->fireDB('model.admin.model.admin.users.delete_users.user_posts_query', $result);
                $result = $result->find_many();

                if ($result) {
                    foreach ($result as $cur_post) {
                        // Determine whether this post is the "topic post" or not
                        $result2 = DB::for_table('posts')
                                        ->where('topic_id', $cur_post['topic_id'])
                                        ->order_by('posted')
                                        ->find_one_col('id');

                        if ($result2 == $cur_post['id']) {
                            \FeatherBB\Model\Topic::delete($cur_post['topic_id']);
                        } else {
                            \FeatherBB\Model\Post::delete($cur_post['id'], $cur_post['topic_id']);
                        }

                        \FeatherBB\Model\Forum::update($cur_post['forum_id']);
                    }
                }
            } else {
                // Set all their posts to guest
                DB::for_table('posts')
                        ->where_in('poster_id', $user_ids)
                        ->update_many('poster_id', '1');
            }

            // Delete the users
            DB::for_table('users')
                    ->where_in('id', $user_ids)
                    ->delete_many();


            // Delete user avatars
            $userProfile = new \FeatherBB\Model\Profile();
            foreach ($user_ids as $user_id) {
                $userProfile->delete_avatar($user_id);
            }

            // Regenerate the users info cache
            if (!Container::get('cache')->isCached('users_info')) {
                Container::get('cache')->store('users_info', Cache::get_users_info());
            }

            $stats = Container::get('cache')->retrieve('users_info');

            return Router::redirect(Router::pathFor('adminUsers'), __('Users delete redirect'));
        }

        return $user_ids;
    }

    public function ban_users()
    {
        if (Input::post('users')) {
            $user_ids = is_array(Input::post('users')) ? array_keys(Input::post('users')) : explode(',', Input::post('users'));
            $user_ids = array_map('intval', $user_ids);

            // Delete invalid IDs
            $user_ids = array_diff($user_ids, [0, 1]);
        } else {
            $user_ids = [];
        }

        $user_ids = Container::get('hooks')->fire('model.admin.model.users.ban_users.user_ids', $user_ids);

        if (empty($user_ids)) {
            throw new Error(__('No users selected'), 404);
        }

        // Are we trying to ban any admins?
        $is_admin = DB::for_table('users')->where_in('id', $user_ids)
            ->where('group_id', ForumEnv::get('FEATHER_ADMIN'))
            ->find_one();
        if ($is_admin) {
            throw new Error(__('No ban admins message'), 403);
        }

        // Also, we cannot ban moderators
        $is_mod = DB::for_table('users')->table_alias('u')
            ->inner_join('permissions', ['u.group_id', '=', 'p.group'], 'p')
            ->where('p.allow', 1)
            ->where('p.permission_name', 'mod.is_mod')
            ->where_in('u.id', $user_ids)
            ->find_one();
        if ($is_mod) {
            throw new Error(__('No ban mods message'), 403);
        }

        if (Input::post('ban_users_comply')) {
            $ban_message = Utils::trim(Input::post('ban_message'));
            $ban_expire = Utils::trim(Input::post('ban_expire'));
            $ban_the_ip = Input::post('ban_the_ip') ? intval(Input::post('ban_the_ip')) : 0;

            Container::get('hooks')->fire('model.admin.model.users.ban_users.comply', $ban_message, $ban_expire, $ban_the_ip);

            if ($ban_expire != '' && $ban_expire != 'Never') {
                $ban_expire = strtotime($ban_expire . ' GMT');

                if ($ban_expire == -1 || !$ban_expire) {
                    throw new Error(__('Invalid date message') . ' ' . __('Invalid date reasons'), 400);
                }

                $diff = (User::getPref('timezone') + User::getPref('dst')) * 3600;
                $ban_expire -= $diff;

                if ($ban_expire <= time()) {
                    throw new Error(__('Invalid date message') . ' ' . __('Invalid date reasons'), 400);
                }
            } else {
                $ban_expire = 'NULL';
            }

            $ban_message = ($ban_message != '') ? $ban_message : 'NULL';

            // Fetch user information
            $user_info = [];
            $select_fetch_user_information = ['id', 'username', 'email', 'registration_ip'];
            $result = DB::for_table('users')->select_many($select_fetch_user_information)
                ->where_in('id', $user_ids);
            $result = Container::get('hooks')->fireDB('model.admin.model.admin.users.ban_users.user_info_query', $result);
            $result = $result->find_many();

            foreach ($result as $cur_user) {
                $user_info[$cur_user['id']] = ['username' => $cur_user['username'], 'email' => $cur_user['email'], 'ip' => $cur_user['registration_ip']];
            }

            // Overwrite the registration IP with one from the last post (if it exists)
            if ($ban_the_ip != 0) {
                $result = DB::for_table('posts')->raw_query('SELECT p.poster_id, p.poster_ip FROM ' . ForumSettings::get('db_prefix') . 'posts AS p INNER JOIN (SELECT MAX(id) AS id FROM ' . ForumSettings::get('db_prefix') . 'posts WHERE poster_id IN (' . implode(',', $user_ids) . ') GROUP BY poster_id) AS i ON p.id=i.id')->find_many();
                foreach ($result as $cur_address) {
                    $user_info[$cur_address['poster_id']]['ip'] = $cur_address['poster_ip'];
                }
            }

            $user_info = Container::get('hooks')->fire('model.admin.model.users.ban_users.user_info', $user_info);

            // And insert the bans!
            foreach ($user_ids as $user_id) {
                $ban_username = $user_info[$user_id]['username'];
                $ban_email = $user_info[$user_id]['email'];
                $ban_ip = ($ban_the_ip != 0) ? $user_info[$user_id]['ip'] : 'NULL';

                $insert_update_ban = [
                    'username' => $ban_username,
                    'ip' => $ban_ip,
                    'email' => $ban_email,
                    'message' => $ban_message,
                    'expire' => $ban_expire,
                    'ban_creator' => User::get()->id,
                ];

                $insert_update_ban = Container::get('hooks')->fire('model.admin.model.users.ban_users.ban_data', $insert_update_ban);

                if (Input::post('mode') == 'add') {
                    $insert_update_ban['ban_creator'] = User::get()->id;

                    DB::for_table('bans')
                        ->create()
                        ->set($insert_update_ban)
                        ->save();
                }

                // Regenerate the bans cache
                Container::get('cache')->store('bans', Cache::get_bans());

                return Router::redirect(Router::pathFor('adminUsers'), __('Users banned redirect'));
            }
        }
        return $user_ids;
    }

    public function get_user_search()
    {
        $form = Input::query('form', [], false);
        $form = Container::get('hooks')->fire('model.admin.model.users.get_user_search.form', $form);

        $search = [];

        // trim() all elements in $form
        $form = array_map('trim', $form);

        $posts_greater = Input::query('posts_greater') ? Utils::trim(Input::query('posts_greater')) : '';
        $posts_less = Input::query('posts_less') ? Utils::trim(Input::query('posts_less')) : '';
        $last_post_after = Input::query('last_post_after') ? Utils::trim(Input::query('last_post_after')) : '';
        $last_post_before = Input::query('last_post_before') ? Utils::trim(Input::query('last_post_before')) : '';
        $last_visit_after = Input::query('last_visit_after') ? Utils::trim(Input::query('last_visit_after')) : '';
        $last_visit_before = Input::query('last_visit_before') ? Utils::trim(Input::query('last_visit_before')) : '';
        $registered_after = Input::query('registered_after') ? Utils::trim(Input::query('registered_after')) : '';
        $registered_before = Input::query('registered_before') ? Utils::trim(Input::query('registered_before')) : '';
        $order_by = $search['order_by'] = Input::query('order_by') && in_array(Input::query('order_by'), ['username', 'email', 'num_posts', 'last_post', 'last_visit', 'registered']) ? Input::query('order_by') : 'username';
        $direction = $search['direction'] = Input::query('direction') && Input::query('direction') == 'DESC' ? 'DESC' : 'ASC';
        $user_group = Input::query('user_group') ? intval(Input::query('user_group')) : -1;

        $search['query_str'][] = 'order_by='.$order_by;
        $search['query_str'][] = 'direction='.$direction;
        $search['query_str'][] = 'user_group='.$user_group;

        if (preg_match('%[^0-9]%', $posts_greater.$posts_less)) {
            throw new Error(__('Non numeric message'), 400);
        }

        $search['conditions'] = [];

        // Try to convert date/time to timestamps
        if ($last_post_after != '') {
            $search['query_str'][] = 'last_post_after='.$last_post_after;

            $last_post_after = strtotime($last_post_after);
            if ($last_post_after === false || $last_post_after == -1) {
                throw new Error(__('Invalid date time message'), 400);
            }

            $search['conditions'][] = 'u.last_post>'.$last_post_after;
        }
        if ($last_post_before != '') {
            $search['query_str'][] = 'last_post_before='.$last_post_before;

            $last_post_before = strtotime($last_post_before);
            if ($last_post_before === false || $last_post_before == -1) {
                throw new Error(__('Invalid date time message'), 400);
            }

            $search['conditions'][] = 'u.last_post<'.$last_post_before;
        }
        if ($last_visit_after != '') {
            $search['query_str'][] = 'last_visit_after='.$last_visit_after;

            $last_visit_after = strtotime($last_visit_after);
            if ($last_visit_after === false || $last_visit_after == -1) {
                throw new Error(__('Invalid date time message'), 400);
            }

            $search['conditions'][] = 'u.last_visit>'.$last_visit_after;
        }
        if ($last_visit_before != '') {
            $search['query_str'][] = 'last_visit_before='.$last_visit_before;

            $last_visit_before = strtotime($last_visit_before);
            if ($last_visit_before === false || $last_visit_before == -1) {
                throw new Error(__('Invalid date time message'), 400);
            }

            $search['conditions'][] = 'u.last_visit<'.$last_visit_before;
        }
        if ($registered_after != '') {
            $search['query_str'][] = 'registered_after='.$registered_after;

            $registered_after = strtotime($registered_after);
            if ($registered_after === false || $registered_after == -1) {
                throw new Error(__('Invalid date time message'), 400);
            }

            $search['conditions'][] = 'u.registered>'.$registered_after;
        }
        if ($registered_before != '') {
            $search['query_str'][] = 'registered_before='.$registered_before;

            $registered_before = strtotime($registered_before);
            if ($registered_before === false || $registered_before == -1) {
                throw new Error(__('Invalid date time message'), 400);
            }

            $search['conditions'][] = 'u.registered<'.$registered_before;
        }

        $like_command = (ForumSettings::get('db_type') == 'pgsql') ? 'ILIKE' : 'LIKE';
        foreach ($form as $key => $input) {
            if ($input != '' && in_array($key, ['username', 'email', 'title', 'realname', 'url', 'location', 'signature', 'admin_note'])) {
                $search['conditions'][] = 'u.'.str_replace("'", "''", $key).' '.$like_command.' \''.str_replace("'", "''", str_replace('*', '%', $input)).'\'';
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

        $search = Container::get('hooks')->fire('model.admin.model.users.get_user_search.search', $search);
        return $search;
    }

    public function print_users($conditions, $order_by, $direction, $start_from)
    {
        $user_data = [];

        $select_print_users = ['u.id', 'u.username', 'u.email', 'u.title', 'u.num_posts', 'u.admin_note', 'g.g_id', 'g.g_user_title'];
        $result = DB::for_table('users')->table_alias('u')
            ->select_many($select_print_users)
            ->left_outer_join('groups', ['g.g_id', '=', 'u.group_id'], 'g')
            ->where_raw('u.id>1'.(!empty($conditions) ? ' AND '.implode(' AND ', $conditions) : ''))
            ->offset($start_from)
            ->limit(50)
            ->order_by($order_by, $direction);
        $result = Container::get('hooks')->fireDB('model.admin.model.admin.users.print_users.query', $result);
        $result = $result->find_many();

        if ($result) {
            foreach ($result as $cur_user) {
                $cur_user['user_title'] = Utils::get_title($cur_user);

                // This script is a special case in that we want to display "Not verified" for non-verified users
                if (($cur_user['g_id'] == '' || $cur_user['g_id'] == ForumEnv::get('FEATHER_UNVERIFIED')) && $cur_user['user_title'] != __('Banned')) {
                    $cur_user['user_title'] = '<span class="warntext">'.__('Not verified').'</span>';
                }

                $user_data[] = $cur_user;
            }
        }

        $user_data = Container::get('hooks')->fire('model.admin.model.users.print_users.user_data', $user_data);
        return $user_data;
    }

    public function get_group_list()
    {
        $output = '';

        $select_get_group_list = ['g_id', 'g_title'];
        $result = DB::for_table('groups')->select_many($select_get_group_list)
                        ->where_not_equal('g_id', ForumEnv::get('FEATHER_GUEST'))
                        ->order_by('g_title');

        foreach ($result as $cur_group) {
            $output .= "\t\t\t\t\t\t\t\t\t\t\t".'<option value="'.$cur_group['g_id'].'">'.Utils::escape($cur_group['g_title']).'</option>'."\n";
        }

        $output = Container::get('hooks')->fire('model.admin.model.users.get_group_list.output', $output);
        return $output;
    }
}
