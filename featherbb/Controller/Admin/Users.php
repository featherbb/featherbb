<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace FeatherBB\Controller\Admin;

use FeatherBB\Core\Utils;
use FeatherBB\Core\AdminUtils;
use FeatherBB\Core\Url;

class Users
{
    public function __construct()
    {
        $this->feather = \Slim\Slim::getInstance();
        $this->start = $this->feather->start;
        $this->config = $this->feather->config;
        $this->user = $this->feather->user;
        $this->request = $this->feather->request;
        $this->model = new \FeatherBB\Model\Admin\users();
        load_textdomain('featherbb', $this->feather->forum_env['FEATHER_ROOT'].'featherbb/lang/'.$this->user->language.'/admin/users.mo');
    }

    public function __autoload($class_name)
    {
        require $this->feather->forum_env['FEATHER_ROOT'] . $class_name . '.php';
    }

    public function display()
    {
        // Move multiple users to other user groups
        if ($this->request->post('move_users') || $this->request->post('move_users_comply')) {
            if ($this->user->g_id > FEATHER_ADMIN) {
                throw new \FeatherBB\Core\Error(__('No permission'), 403);
            }

            AdminUtils::generateAdminMenu('users');

            $this->feather->template->setPageInfo(array(
                    'title' => array(Utils::escape($this->config['o_board_title']), __('Admin'), __('Users'), __('Move users')),
                    'active_page' => 'moderate',
                    'admin_console' => true,
                    'move'              =>  $this->model->move_users(),
                )
            )->addTemplate('admin/users/move_users.php')->display();
        }


        // Delete multiple users
        if ($this->request->post('delete_users') || $this->request->post('delete_users_comply')) {
            if ($this->user->g_id > FEATHER_ADMIN) {
                throw new \FeatherBB\Core\Error(__('No permission'), 403);
            }

            AdminUtils::generateAdminMenu('users');

            $this->feather->template->setPageInfo(array(
                    'title' => array(Utils::escape($this->config['o_board_title']), __('Admin'), __('Users'), __('Delete users')),
                    'active_page' => 'moderate',
                    'admin_console' => true,
                    'user_ids'          => $this->model->delete_users(),
                )
            )->addTemplate('admin/users/delete_users.php')->display();
        }


        // Ban multiple users
        if ($this->request->post('ban_users') || $this->request->post('ban_users_comply')) {
            if ($this->user->g_id != FEATHER_ADMIN && ($this->user->g_moderator != '1' || $this->user->g_mod_ban_users == '0')) {
                throw new \FeatherBB\Core\Error(__('No permission'), 403);
            }

            AdminUtils::generateAdminMenu('users');

            $this->feather->template->setPageInfo(array(
                    'title' => array(Utils::escape($this->config['o_board_title']), __('Admin'), __('Users'), __('Bans')),
                    'active_page' => 'moderate',
                    'focus_element' => array('bans2', 'ban_message'),
                    'admin_console' => true,
                    'user_ids'          => $this->model->ban_users(),
                )
            )->addTemplate('admin/users/ban_users.php')->display();
        }

        // Display bans
        if ($this->request->get('find_user')) {

            // Return conditions and query string for the URL
            $search = $this->model->get_user_search();

            // Fetch user count
            $num_users = $this->model->get_num_users_search($search['conditions']);

            // Determine the user offset (based on $_GET['p'])
            $num_pages = ceil($num_users / 50);

            $p = (!$this->request->get('p') || $this->request->get('p') <= 1 || $this->request->get('p') > $num_pages) ? 1 : intval($this->request->get('p'));
            $start_from = 50 * ($p - 1);

            // Generate paging links
            $paging_links = '<span class="pages-label">' . __('Pages') . ' </span>' . Url::paginate_old($num_pages, $p, '?find_user=&amp;'.implode('&amp;', $search['query_str']));

            // Some helper variables for permissions
            $can_delete = $can_move = $this->user->g_id == FEATHER_ADMIN;
            $can_ban = $this->user->g_id == FEATHER_ADMIN || ($this->user->g_moderator == '1' && $this->user->g_mod_ban_users == '1');
            $can_action = ($can_delete || $can_ban || $can_move) && $num_users > 0;
            $this->feather->template->addAsset('js', 'style/imports/common.js', array('type' => 'text/javascript'));

            $this->feather->template->setPageInfo(array(
                    'title' => array(Utils::escape($this->config['o_board_title']), __('Admin'), __('Users'), __('Results head')),
                    'active_page' => 'admin',
                    'admin_console' => true,
                    'paging_links' => $paging_links,
                    'search' => $search,
                    'start_from' => $start_from,
                    'can_delete' => $can_delete,
                    'can_ban' => $can_ban,
                    'can_action' => $can_action,
                    'can_move' => $can_move,
                    'user_data' =>  $this->model->print_users($search['conditions'], $search['order_by'], $search['direction'], $start_from),
                )
            )->addTemplate('admin/users/find_users.php')->display();
        }
        else {
            AdminUtils::generateAdminMenu('users');

            $this->feather->template->setPageInfo(array(
                    'title' => array(Utils::escape($this->config['o_board_title']), __('Admin'), __('Users')),
                    'active_page' => 'admin',
                    'admin_console' => true,
                    'focus_element' => array('find_user', 'form[username]'),
                    'group_list' => $this->model->get_group_list(),
                )
            )->addTemplate('admin/users/admin_users.php')->display();
        }
    }

    // Show IP statistics for a certain user ID
    public function ipstats($id)
    {
        // Fetch ip count
        $num_ips = $this->model->get_num_ip($id);

        // Determine the ip offset (based on $_GET['p'])
        $num_pages = ceil($num_ips / 50);

        $p = (!$this->request->get('p') || $this->request->get('p') <= 1 || $this->request->get('p') > $num_pages) ? 1 : intval($this->request->get('p'));
        $start_from = 50 * ($p - 1);

        $this->feather->template->setPageInfo(array(
                'title' => array(Utils::escape($this->config['o_board_title']), __('Admin'), __('Users'), __('Results head')),
                'active_page' => 'admin',
                'admin_console' => true,
                'page' => $p,
                'paging_links' => '<span class="pages-label">'.__('Pages').' </span>'.Url::paginate_old($num_pages, $p, '?ip_stats='.$id),
                'start_from'        =>  $start_from,
                'ip_data'   =>  $this->model->get_ip_stats($id, $start_from),
            )
        )->addTemplate('admin/users/search_ip.php')->display();
    }

    // Show IP statistics for a certain user IP
    public function showusers($ip)
    {
        if (!@preg_match('%^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}$%', $ip) && !@preg_match('%^((([0-9A-Fa-f]{1,4}:){7}[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){6}:[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){5}:([0-9A-Fa-f]{1,4}:)?[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){4}:([0-9A-Fa-f]{1,4}:){0,2}[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){3}:([0-9A-Fa-f]{1,4}:){0,3}[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){2}:([0-9A-Fa-f]{1,4}:){0,4}[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){6}((\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b)\.){3}(\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b))|(([0-9A-Fa-f]{1,4}:){0,5}:((\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b)\.){3}(\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b))|(::([0-9A-Fa-f]{1,4}:){0,5}((\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b)\.){3}(\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b))|([0-9A-Fa-f]{1,4}::([0-9A-Fa-f]{1,4}:){0,5}[0-9A-Fa-f]{1,4})|(::([0-9A-Fa-f]{1,4}:){0,6}[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){1,7}:))$%', $ip)) {
            throw new \FeatherBB\Core\Error(__('Bad IP message'), 400);
        }

        // Fetch user count
        $num_users = $this->model->get_num_users_ip($ip);

        // Determine the user offset (based on $_GET['p'])
        $num_pages = ceil($num_users / 50);

        $p = (!$this->request->get('p') || $this->request->get('p') <= 1 || $this->request->get('p') > $num_pages) ? 1 : intval($this->request->get('p'));
        $start_from = 50 * ($p - 1);

        $this->feather->template->setPageInfo(array(
                'title' => array(Utils::escape($this->config['o_board_title']), __('Admin'), __('Users'), __('Results head')),
                'active_page' => 'admin',
                'admin_console' => true,
                'paging_links' => '<span class="pages-label">'.__('Pages').' </span>'.Url::paginate_old($num_pages, $p, '?ip_stats='.$ip),
                'page' => $p,
                'start_from'        =>  $start_from,
                'info'   =>  $this->model->get_info_poster($ip, $start_from),
            )
        )->addTemplate('admin/users/show_users.php')->display();
    }
}
