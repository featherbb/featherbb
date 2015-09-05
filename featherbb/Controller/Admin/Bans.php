<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace FeatherBB\Controller\Admin;

use FeatherBB\Utils;

class Bans
{
    public function __construct()
    {
        $this->feather = \Slim\Slim::getInstance();
        $this->start = $this->feather->start;
        $this->config = $this->feather->config;
        $this->user = $this->feather->user;
        $this->request = $this->feather->request;
        $this->model = new \FeatherBB\Model\Admin\Bans();
        load_textdomain('featherbb', $this->feather->forum_env['FEATHER_ROOT'].'featherbb/lang/'.$this->user->language.'/admin/bans.mo');

        if ($this->user->g_id != FEATHER_ADMIN && ($this->user->g_moderator != '1' || $this->user->g_mod_ban_users == '0')) {
            throw new \FeatherBB\Error(__('No permission'), '403');
        }
    }

    public function __autoload($class_name)
    {
        require $this->feather->forum_env['FEATHER_ROOT'] . $class_name . '.php';
    }

    public function display()
    {
        // Display bans
        if ($this->request->get('find_ban')) {
            $ban_info = $this->model->find_ban();

            // Determine the ban offset (based on $_GET['p'])
            $num_pages = ceil($ban_info['num_bans'] / 50);

            $p = (!$this->request->get('p') || $this->request->get('p') <= 1 || $this->request->get('p') > $num_pages) ? 1 : intval($this->request->get('p'));
            $start_from = 50 * ($p - 1);

            $ban_data = $this->model->find_ban($start_from);

            $this->feather->view2->setPageInfo(array(
                    'admin_console' => true,
                    'page' => $p,
                    'title' => array(Utils::escape($this->config['o_board_title']), __('Admin'), __('Bans'), __('Results head')),
                    'paging_links' => '<span class="pages-label">' . __('Pages') . ' </span>' . $this->feather->url->paginate_old($num_pages, $p, '?find_ban=&amp;' . implode('&amp;', $ban_info['query_str'])),
                    'ban_data' => $ban_data['data'],
                )
            )->addTemplate('admin/bans/search_ban.php')->display();
        }
        else {
            \FeatherBB\AdminUtils::generateAdminMenu('bans');

            $this->feather->view2->setPageInfo(array(
                    'admin_console' => true,
                    'focus_element' => array('bans', 'new_ban_user'),
                    'title' => array(Utils::escape($this->config['o_board_title']), __('Admin'), __('Bans')),
                )
            )->addTemplate('admin/bans/admin_bans.php')->display();
        }
    }

    public function add($id = null)
    {
        if ($this->request->post('add_edit_ban')) {
            $this->model->insert_ban();
        }

        \FeatherBB\AdminUtils::generateAdminMenu('bans');

        $this->feather->view2->setPageInfo(array(
                'admin_console' => true,
                'focus_element' => array('bans2', 'ban_user'),
                'title' => array(Utils::escape($this->config['o_board_title']), __('Admin'), __('Bans')),
                'ban' => $this->model->add_ban_info($id),
            )
        )->addTemplate('admin/bans/add_ban.php')->display();
    }

    public function delete($id)
    {
        // Remove the ban
        $this->model->remove_ban($id);
    }

    public function edit($id)
    {
        if ($this->request->post('add_edit_ban')) {
            $this->model->insert_ban();
        }
        \FeatherBB\AdminUtils::generateAdminMenu('bans');

        $this->feather->view2->setPageInfo(array(
                'admin_console' => true,
                'focus_element' => array('bans2', 'ban_user'),
                'title' => array(Utils::escape($this->config['o_board_title']), __('Admin'), __('Bans')),
                'ban' => $this->model->edit_ban_info($id),
            )
        )->addTemplate('admin/bans/add_ban.php')->display();
    }
}
