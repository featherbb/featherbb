<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace controller\admin;

class users
{
    public function __construct()
    {
        $this->feather = \Slim\Slim::getInstance();
        $this->start = $this->feather->start;
        $this->config = $this->feather->config;
        $this->user = $this->feather->user;
        $this->request = $this->feather->request;
        $this->header = new \controller\header();
        $this->footer = new \controller\footer();
        $this->model = new \model\admin\users();
        load_textdomain('featherbb', FEATHER_ROOT.'lang/'.$this->user->language.'/admin/users.mo');
        require FEATHER_ROOT . 'include/common_admin.php';
    }

    public function __autoload($class_name)
    {
        require FEATHER_ROOT . $class_name . '.php';
    }

    public function display()
    {
        define('FEATHER_ADMIN_CONSOLE', 1);

        if (!$this->user->is_admmod) {
            message(__('No permission'), '403');
        }

        // Move multiple users to other user groups
        if ($this->request->post('move_users') || $this->request->post('move_users_comply')) {
            if ($this->user->g_id > FEATHER_ADMIN) {
                message(__('No permission'), '403');
            }

            $move = $this->model->move_users();

            $page_title = array(feather_escape($this->config['o_board_title']), __('Admin'), __('Users'), __('Move users'));

            $this->header->setTitle($page_title)->setActivePage('moderate')->display();

            generate_admin_menu('users');

            $this->feather->render('admin/users/move_users.php', array(
                    'move'              =>  $move,
                )
            );

            $this->footer->display();
        }


        // Delete multiple users
        if ($this->request->post('delete_users') || $this->request->post('delete_users_comply')) {
            if ($this->user->g_id > FEATHER_ADMIN) {
                message(__('No permission'), '403');
            }

            $user_ids = $this->model->delete_users();

            $page_title = array(feather_escape($this->config['o_board_title']), __('Admin'), __('Users'), __('Delete users'));

            $this->header->setTitle($page_title)->setActivePage('moderate')->display();

            generate_admin_menu('users');

            $this->feather->render('admin/users/delete_users.php', array(
                    'user_ids'          => $user_ids,
                )
            );

            $this->footer->display();
        }


        // Ban multiple users
        if ($this->request->post('ban_users') || $this->request->post('ban_users_comply')) {
            if ($this->user->g_id != FEATHER_ADMIN && ($this->user->g_moderator != '1' || $this->user->g_mod_ban_users == '0')) {
                message(__('No permission'), '403');
            }

            $user_ids = $this->model->ban_users();

            $page_title = array(feather_escape($this->config['o_board_title']), __('Admin'), __('Bans'));
            $focus_element = array('bans2', 'ban_message');

            $this->header->setTitle($page_title)->setActivePage('moderate')->setFocusElement($focus_element)->display();

            generate_admin_menu('users');

            $this->feather->render('admin/users/ban_users.php', array(
                    'user_ids'          => $user_ids,
                )
            );

            $this->footer->display();
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
            $paging_links = '<span class="pages-label">' . __('Pages') . ' </span>' . paginate_old($num_pages, $p, '?find_user=&amp;'.implode('&amp;', $search['query_str']));

            // Some helper variables for permissions
            $can_delete = $can_move = $this->user->g_id == FEATHER_ADMIN;
            $can_ban = $this->user->g_id == FEATHER_ADMIN || ($this->user->g_moderator == '1' && $this->user->g_mod_ban_users == '1');
            $can_action = ($can_delete || $can_ban || $can_move) && $num_users > 0;

            $page_title = array(feather_escape($this->config['o_board_title']), __('Admin'), __('Users'), __('Results head'));
            $page_head = array('js' => '<script type="text/javascript" src="'.get_base_url().'/js/common.js"></script>');

            $this->header->setTitle($page_title)->setActivePage('admin')->setPage($p)->setPagingLinks($paging_links)->setPageHead($page_head)->display();

            $this->feather->render('admin/users/find_users.php', array(
                    'search' => $search,
                    'start_from' => $start_from,
                    'can_delete' => $can_delete,
                    'can_ban' => $can_ban,
                    'can_action' => $can_action,
                    'can_move' => $can_move,
                    'user_data' =>  $this->model->print_users($search['conditions'], $search['order_by'], $search['direction'], $start_from),
                )
            );

            $this->footer->display();
        }

        $page_title = array(feather_escape($this->config['o_board_title']), __('Admin'), __('Users'));
        $focus_element = array('find_user', 'form[username]');

        $this->header->setTitle($page_title)->setActivePage('admin')->setFocusElement($focus_element)->display();

        generate_admin_menu('users');

        $this->feather->render('admin/users/admin_users.php', array(
                'group_list' => $this->model->get_group_list(),
            )
        );

        $this->footer->display();
    }

    // Show IP statistics for a certain user ID
    public function ipstats($id)
    {
        define('FEATHER_ADMIN_CONSOLE', 1);

        if (!$this->user->is_admmod) {
            message(__('No permission'), '403');
        }

        // Fetch ip count
        $num_ips = $this->model->get_num_ip($id);

        // Determine the ip offset (based on $_GET['p'])
        $num_pages = ceil($num_ips / 50);

        $p = (!$this->request->get('p') || $this->request->get('p') <= 1 || $this->request->get('p') > $num_pages) ? 1 : intval($this->request->get('p'));
        $start_from = 50 * ($p - 1);

        // Generate paging links
        $paging_links = '<span class="pages-label">'.__('Pages').' </span>'.paginate_old($num_pages, $p, '?ip_stats='.$id);

        $page_title = array(feather_escape($this->config['o_board_title']), __('Admin'), __('Users'), __('Results head'));

        $this->header->setTitle($page_title)->setActivePage('admin')->setPage($p)->setPagingLinks($paging_links)->display();

        $this->feather->render('admin/users/search_ip.php', array(
                'start_from'        =>  $start_from,
                'ip_data'   =>  $this->model->get_ip_stats($id, $start_from),
            )
        );

        $this->footer->display();
    }

    // Show IP statistics for a certain user IP
    public function showusers($ip)
    {
        define('FEATHER_ADMIN_CONSOLE', 1);

        if (!$this->user->is_admmod) {
            message(__('No permission'), '403');
        }

        if (!@preg_match('%^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}$%', $ip) && !@preg_match('%^((([0-9A-Fa-f]{1,4}:){7}[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){6}:[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){5}:([0-9A-Fa-f]{1,4}:)?[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){4}:([0-9A-Fa-f]{1,4}:){0,2}[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){3}:([0-9A-Fa-f]{1,4}:){0,3}[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){2}:([0-9A-Fa-f]{1,4}:){0,4}[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){6}((\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b)\.){3}(\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b))|(([0-9A-Fa-f]{1,4}:){0,5}:((\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b)\.){3}(\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b))|(::([0-9A-Fa-f]{1,4}:){0,5}((\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b)\.){3}(\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b))|([0-9A-Fa-f]{1,4}::([0-9A-Fa-f]{1,4}:){0,5}[0-9A-Fa-f]{1,4})|(::([0-9A-Fa-f]{1,4}:){0,6}[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){1,7}:))$%', $ip)) {
            message(__('Bad IP message'));
        }

        // Fetch user count
        $num_users = $this->model->get_num_users_ip($ip);

        // Determine the user offset (based on $_GET['p'])
        $num_pages = ceil($num_users / 50);

        $p = (!$this->request->get('p') || $this->request->get('p') <= 1 || $this->request->get('p') > $num_pages) ? 1 : intval($this->request->get('p'));
        $start_from = 50 * ($p - 1);

        // Generate paging links
        $paging_links = '<span class="pages-label">'.__('Pages').' </span>'.paginate_old($num_pages, $p, '?ip_stats='.$ip);

        $page_title = array(feather_escape($this->config['o_board_title']), __('Admin'), __('Users'), __('Results head'));

        $this->header->setTitle($page_title)->setActivePage('admin')->setPage($p)->setPagingLinks($paging_links)->display();

        $this->feather->render('admin/users/show_users.php', array(
                'start_from'        =>  $start_from,
                'info'   =>  $this->model->get_info_poster($ip, $start_from),
            )
        );

        $this->footer->display();
    }
}
