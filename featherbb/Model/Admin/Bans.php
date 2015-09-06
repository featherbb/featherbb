<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace FeatherBB\Model\Admin;

use FeatherBB\Core\Utils;
use FeatherBB\Core\Url;
use DB;

class Bans
{
    public function __construct()
    {
        $this->feather = \Slim\Slim::getInstance();
        $this->start = $this->feather->start;
        $this->config = $this->feather->config;
        $this->user = $this->feather->user;
        $this->request = $this->feather->request;
        $this->hook = $this->feather->hooks;
        $this->email = $this->feather->email;
    }

    public function add_ban_info($id = null)
    {
        $ban = array();

        $id = $this->hook->fire('add_ban_info_start', $id);

        // If the ID of the user to ban was provided through GET (a link from profile.php)
        if (is_numeric($id)) {
            $ban['user_id'] = $id;
            if ($ban['user_id'] < 2) {
                throw new \FeatherBB\Core\Error(__('Bad request'), 404);
            }

            $select_add_ban_info = array('group_id', 'username', 'email');
            $result = DB::for_table('users')->select_many($select_add_ban_info)
                        ->where('id', $ban['user_id']);

            $result = $this->hook->fireDB('add_ban_info_query', $result);
            $result = $result->find_one();

            if ($result) {
                $group_id = $result['group_id'];
                $ban['ban_user'] = $result['username'];
                $ban['email'] = $result['email'];
            } else {
                throw new \FeatherBB\Core\Error(__('No user ID message'), 404);
            }
        } else {
            // Otherwise the username is in POST

            $ban['ban_user'] = Utils::trim($this->request->post('new_ban_user'));

            if ($ban['ban_user'] != '') {
                $select_add_ban_info = array('id', 'group_id', 'username', 'email');
                $result = DB::for_table('users')->select_many($select_add_ban_info)
                    ->where('username', $ban['ban_user'])
                    ->where_gt('id', 1);

                $result = $this->hook->fireDB('add_ban_info_query', $result);
                $result = $result->find_one();

                if ($result) {
                    $ban['user_id'] = $result['id'];
                    $group_id = $result['group_id'];
                    $ban['ban_user'] = $result['username'];
                    $ban['email'] = $result['email'];
                } else {
                    throw new \FeatherBB\Core\Error(__('No user message'), 404);
                }
            }
        }

        // Make sure we're not banning an admin or moderator
        if (isset($group_id)) {
            if ($group_id == FEATHER_ADMIN) {
                throw new \FeatherBB\Core\Error(sprintf(__('User is admin message'), Utils::escape($ban['ban_user'])), 403);
            }

            $is_moderator_group = DB::for_table('groups')->where('g_id', $group_id)
                                        ->find_one_col('g_moderator');

            if ($is_moderator_group) {
                throw new \FeatherBB\Core\Error(sprintf(__('User is mod message'), Utils::escape($ban['ban_user'])), 403);
            }
        }

        // If we have a $ban['user_id'], we can try to find the last known IP of that user
        if (isset($ban['user_id'])) {
            $ban['ip'] = DB::for_table('posts')->where('poster_id', $ban['user_id'])
                            ->order_by_desc('posted')
                            ->find_one_col('poster_ip');

            if (!$ban['ip']) {
                $ban['ip'] = DB::for_table('users')->where('id', $ban['user_id'])
                                 ->find_one_col('registration_ip');
            }
        }

        $ban['mode'] = 'add';

        $ban = $this->hook->fire('add_ban_info', $ban);

        return $ban;
    }

    public function edit_ban_info($id)
    {
        $ban = array();

        $id = $this->hook->fire('edit_ban_info_start', $id);

        $ban['id'] = $id;

        $select_edit_ban_info = array('username', 'ip', 'email', 'message', 'expire');
        $result = DB::for_table('bans')->select_many($select_edit_ban_info)
            ->where('id', $ban['id']);

        $result = $this->hook->fireDB('edit_ban_info_query', $result);
        $result = $result->find_one();

        if ($result) {
            $ban['ban_user'] = $result['username'];
            $ban['ip'] = $result['ip'];
            $ban['email'] = $result['email'];
            $ban['message'] = $result['message'];
            $ban['expire'] = $result['expire'];
        } else {
            throw new \FeatherBB\Core\Error(__('Bad request'), 404);
        }

        $diff = ($this->user->timezone + $this->user->dst) * 3600;
        $ban['expire'] = ($ban['expire'] != '') ? gmdate('Y-m-d', $ban['expire'] + $diff) : '';

        $ban['mode'] = 'edit';

        $ban = $this->hook->fire('edit_ban_info', $ban);

        return $ban;
    }

    public function insert_ban()
    {
        $ban_user = Utils::trim($this->request->post('ban_user'));
        $ban_ip = Utils::trim($this->request->post('ban_ip'));
        $ban_email = strtolower(Utils::trim($this->request->post('ban_email')));
        $ban_message = Utils::trim($this->request->post('ban_message'));
        $ban_expire = Utils::trim($this->request->post('ban_expire'));

        $this->hook->fire('insert_ban_start', $ban_user, $ban_ip, $ban_email, $ban_message, $ban_expire);

        if ($ban_user == '' && $ban_ip == '' && $ban_email == '') {
            throw new \FeatherBB\Core\Error(__('Must enter message'), 400);
        } elseif (strtolower($ban_user) == 'guest') {
            throw new \FeatherBB\Core\Error(__('Cannot ban guest message'), 400);
        }

        // Make sure we're not banning an admin or moderator
        if (!empty($ban_user)) {
            $group_id = DB::for_table('users')->where('username', $ban_user)
                            ->where_gt('id', 1)
                            ->find_one_col('group_id');

            if ($group_id) {
                if ($group_id == FEATHER_ADMIN) {
                    throw new \FeatherBB\Core\Error(sprintf(__('User is admin message'), Utils::escape($ban_user)), 403);
                }

                $is_moderator_group = DB::for_table('groups')->where('g_id', $group_id)
                                            ->find_one_col('g_moderator');

                if ($is_moderator_group) {
                    throw new \FeatherBB\Core\Error(sprintf(__('User is mod message'), Utils::escape($ban_user)), 403);
                }
            }
        }

        // Validate IP/IP range (it's overkill, I know)
        if ($ban_ip != '') {
            $ban_ip = preg_replace('%\s{2,}%S', ' ', $ban_ip);
            $addresses = explode(' ', $ban_ip);
            $addresses = array_map('trim', $addresses);

            for ($i = 0; $i < count($addresses); ++$i) {
                if (strpos($addresses[$i], ':') !== false) {
                    $octets = explode(':', $addresses[$i]);

                    for ($c = 0; $c < count($octets); ++$c) {
                        $octets[$c] = ltrim($octets[$c], "0");

                        if ($c > 7 || (!empty($octets[$c]) && !ctype_xdigit($octets[$c])) || intval($octets[$c], 16) > 65535) {
                            throw new \FeatherBB\Core\Error(__('Invalid IP message'), 400);
                        }
                    }

                    $cur_address = implode(':', $octets);
                    $addresses[$i] = $cur_address;
                } else {
                    $octets = explode('.', $addresses[$i]);

                    for ($c = 0; $c < count($octets); ++$c) {
                        $octets[$c] = (strlen($octets[$c]) > 1) ? ltrim($octets[$c], "0") : $octets[$c];

                        if ($c > 3 || preg_match('%[^0-9]%', $octets[$c]) || intval($octets[$c]) > 255) {
                            throw new \FeatherBB\Core\Error(__('Invalid IP message'), 400);
                        }
                    }

                    $cur_address = implode('.', $octets);
                    $addresses[$i] = $cur_address;
                }
            }

            $ban_ip = implode(' ', $addresses);
        }

        if ($ban_email != '' && !$this->email->is_valid_email($ban_email)) {
            if (!preg_match('%^[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,63})$%', $ban_email)) {
                throw new \FeatherBB\Core\Error(__('Invalid e-mail message'), 400);
            }
        }

        if ($ban_expire != '' && $ban_expire != 'Never') {
            $ban_expire = strtotime($ban_expire.' GMT');

            if ($ban_expire == -1 || !$ban_expire) {
                throw new \FeatherBB\Core\Error(__('Invalid date message').' '.__('Invalid date reasons'), 400);
            }

            $diff = ($this->user->timezone + $this->user->dst) * 3600;
            $ban_expire -= $diff;

            if ($ban_expire <= time()) {
                throw new \FeatherBB\Core\Error(__('Invalid date message').' '.__('Invalid date reasons'), 400);
            }
        } else {
            $ban_expire = 'NULL';
        }

        $ban_user = ($ban_user != '') ? $ban_user : 'NULL';
        $ban_ip = ($ban_ip != '') ? $ban_ip : 'NULL';
        $ban_email = ($ban_email != '') ? $ban_email : 'NULL';
        $ban_message = ($ban_message != '') ? $ban_message : 'NULL';

        $insert_update_ban = array(
            'username'  =>  $ban_user,
            'ip'        =>  $ban_ip,
            'email'     =>  $ban_email,
            'message'   =>  $ban_message,
            'expire'    =>  $ban_expire,
        );

        $insert_update_ban = $this->hook->fire('insert_ban_data', $insert_update_ban);

        if ($this->request->post('mode') == 'add') {
            $insert_update_ban['ban_creator'] = $this->user->id;

            $result = DB::for_table('bans')
                ->create()
                ->set($insert_update_ban)
                ->save();
        } else {

            $result = DB::for_table('bans')
                ->where('id', $this->request->post('ban_id'))
                ->find_one()
                ->set($insert_update_ban)
                ->save();
        }

        // Regenerate the bans cache
        $this->feather->cache->store('bans', \FeatherBB\Model\Cache::get_bans());

<<<<<<< HEAD
        $this->feather->url->redirect($this->feather->urlFor('adminBans'), __('Ban edited redirect'));
=======
        redirect(Url::get('admin/bans/'), __('Ban edited redirect'));
>>>>>>> development
    }

    public function remove_ban($ban_id)
    {
        $ban_id = $this->hook->fire('remove_ban', $ban_id);

        $result = DB::for_table('bans')->where('id', $ban_id)
                    ->find_one();
        $result = $this->hook->fireDB('remove_ban_query', $result);
        $result = $result->delete();

        // Regenerate the bans cache
        $this->feather->cache->store('bans', \FeatherBB\Model\Cache::get_bans());

<<<<<<< HEAD
        $this->feather->url->redirect($this->feather->urlFor('adminBans'), __('Ban removed redirect'));
=======
        redirect(Url::get('admin/bans/'), __('Ban removed redirect'));
>>>>>>> development
    }

    public function find_ban($start_from = false)
    {
        $ban_info = array();

        $this->hook->fire('find_ban_start');

        // trim() all elements in $form
        $ban_info['conditions'] = $ban_info['query_str'] = array();

        $expire_after = $this->request->get('expire_after') ? Utils::trim($this->request->get('expire_after')) : '';
        $expire_before = $this->request->get('expire_before') ? Utils::trim($this->request->get('expire_before')) : '';
        $ban_info['order_by'] = $this->request->get('order_by') && in_array($this->request->get('order_by'), array('username', 'ip', 'email', 'expire')) ? 'b.'.$this->request->get('order_by') : 'b.username';
        $ban_info['direction'] = $this->request->get('direction') && $this->request->get('direction') == 'DESC' ? 'DESC' : 'ASC';

        $ban_info['query_str'][] = 'order_by='.$ban_info['order_by'];
        $ban_info['query_str'][] = 'direction='.$ban_info['direction'];

        // Build the query
        $result = DB::for_table('bans')->table_alias('b')
                        ->where_gt('b.id', 0);

        // Try to convert date/time to timestamps
        if ($expire_after != '') {
            $ban_info['query_str'][] = 'expire_after='.$expire_after;

            $expire_after = strtotime($expire_after);
            if ($expire_after === false || $expire_after == -1) {
                throw new \FeatherBB\Core\Error(__('Invalid date message'), 400);
            }

            $result = $result->where_gt('b.expire', $expire_after);
        }
        if ($expire_before != '') {
            $ban_info['query_str'][] = 'expire_before='.$expire_before;

            $expire_before = strtotime($expire_before);
            if ($expire_before === false || $expire_before == -1) {
                throw new \FeatherBB\Core\Error(__('Invalid date message'), 400);
            }

            $result = $result->where_lt('b.expire', $expire_before);
        }

        if ($this->request->get('username')) {
            $result = $result->where_like('b.username', str_replace('*', '%', $this->request->get('username')));
            $ban_info['query_str'][] = 'username=' . urlencode($this->request->get('username'));
        }

        if ($this->request->get('ip')) {
            $result = $result->where_like('b.ip', str_replace('*', '%', $this->request->get('ip')));
            $ban_info['query_str'][] = 'ip=' . urlencode($this->request->get('ip'));
        }

        if ($this->request->get('email')) {
            $result = $result->where_like('b.email', str_replace('*', '%', $this->request->get('email')));
            $ban_info['query_str'][] = 'email=' . urlencode($this->request->get('email'));
        }

        if ($this->request->get('message')) {
            $result = $result->where_like('b.message', str_replace('*', '%', $this->request->get('message')));
            $ban_info['query_str'][] = 'message=' . urlencode($this->request->get('message'));
        }

        // Fetch ban count
        if (is_numeric($start_from)) {
            $ban_info['data'] = array();
            $select_bans = array('b.id', 'b.username', 'b.ip', 'b.email', 'b.message', 'b.expire', 'b.ban_creator', 'ban_creator_username' => 'u.username');

            $result = $result->select_many($select_bans)
                             ->left_outer_join('users', array('b.ban_creator', '=', 'u.id'), 'u')
                             ->order_by($ban_info['order_by'], $ban_info['direction'])
                             ->offset($start_from)
                             ->limit(50)
                             ->find_many();

            foreach ($result as $cur_ban) {
                $ban_info['data'][] = $cur_ban;
            }
        }
        else {
            $ban_info['num_bans'] = $result->count('id');
        }

        $this->hook->fire('find_ban', $ban_info);

        return $ban_info;
    }
}
