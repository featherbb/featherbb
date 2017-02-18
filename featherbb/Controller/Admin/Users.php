<?php

/**
 * Copyright (C) 2015-2017 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace FeatherBB\Controller\Admin;

use FeatherBB\Core\AdminUtils;
use FeatherBB\Core\Error;
use FeatherBB\Core\Url;
use FeatherBB\Core\Utils;

class Users
{
    public function __construct()
    {
        $this->model = new \FeatherBB\Model\Admin\Users();
        Lang::load('admin/users');
    }

    public function display($req, $res, $args)
    {
        Container::get('hooks')->fire('controller.admin.users.display');

        // Move multiple users to other user groups
        if (Input::post('move_users') || Input::post('move_users_comply')) {
            AdminUtils::generateAdminMenu('users');

            return View::setPageInfo([
                    'title' => [Utils::escape(ForumSettings::get('o_board_title')), __('Admin'), __('Users'), __('Move users')],
                    'active_page' => 'moderate',
                    'admin_console' => true,
                    'move'              =>  $this->model->moveUsers(),
                ]
            )->addTemplate('@forum/admin/users/move_users')->display();
        }


        // Delete multiple users
        if (Input::post('delete_users') || Input::post('delete_users_comply')) {
            AdminUtils::generateAdminMenu('users');

            return View::setPageInfo([
                    'title' => [Utils::escape(ForumSettings::get('o_board_title')), __('Admin'), __('Users'), __('Delete users')],
                    'active_page' => 'moderate',
                    'admin_console' => true,
                    'user_ids'          => $this->model->deleteUsers(),
                ]
            )->addTemplate('@forum/admin/users/delete_users')->display();
        }


        // Ban multiple users
        if (Input::post('ban_users') || Input::post('ban_users_comply')) {
            if (!User::can('mod.bans')) {
                throw new Error(__('No permission'), '403');
            }

            AdminUtils::generateAdminMenu('users');

            return View::setPageInfo([
                    'title' => [Utils::escape(ForumSettings::get('o_board_title')), __('Admin'), __('Users'), __('Bans')],
                    'active_page' => 'moderate',
                    'admin_console' => true,
                    'user_ids'          => $this->model->banUsers(),
                ]
            )->addTemplate('@forum/admin/users/ban_users')->display();
        }

        // Display bans
        if (Input::query('find_user')) {

            // Return conditions and query string for the URL
            $search = $this->model->getUserSearch();

            // Fetch user count
            $numUsers = $this->model->getNumUsersSearch($search['conditions']);

            // Determine the user offset (based on $_gET['p'])
            $numPages = ceil($numUsers / 50);

            $p = (!Input::query('p') || Input::query('p') <= 1 || Input::query('p') > $numPages) ? 1 : intval(Input::query('p'));
            $startFrom = 50 * ($p - 1);

            // Generate paging links
            $pagingLinks = '<span class="pages-label">' . __('Pages') . ' </span>' . Url::paginateOld($numPages, $p, '?find_user=&amp;'.implode('&amp;', $search['query_str']));

            // Some helper variables for permissions
            $canDelete = $canMove = User::isAdmin();
            $canBan = User::isAdmin() || (User::isAdminMod() && User::can('mod.ban_users'));
            $canAction = ($canDelete || $canBan || $canMove) && $numUsers > 0;
            View::addAsset('js', 'style/imports/common.js', ['type' => 'text/javascript']);

            View::setPageInfo([
                    'title' => [Utils::escape(ForumSettings::get('o_board_title')), __('Admin'), __('Users'), __('Results head')],
                    'active_page' => 'admin',
                    'admin_console' => true,
                    'paging_links' => $pagingLinks,
                    'search' => $search,
                    'start_from' => $startFrom,
                    'can_delete' => $canDelete,
                    'can_ban' => $canBan,
                    'can_action' => $canAction,
                    'can_move' => $canMove,
                    'user_data' =>  $this->model->printUsers($search['conditions'], $search['order_by'], $search['direction'], $startFrom),
                ]
            )->addTemplate('@forum/admin/users/find_users')->display();
        } else {
            AdminUtils::generateAdminMenu('users');

            return View::setPageInfo([
                    'title' => [Utils::escape(ForumSettings::get('o_board_title')), __('Admin'), __('Users')],
                    'active_page' => 'admin',
                    'admin_console' => true,
                    'group_list' => $this->model->getGroupList(),
                ]
            )->addTemplate('@forum/admin/users/admin_users')->display();
        }
    }

    // Show IP statistics for a certain user ID
    public function ipstats($req, $res, $args)
    {
        Container::get('hooks')->fire('controller.admin.users.ipstats');

        // Fetch ip count
        $numIps = $this->model->getNumIp($args['id']);

        // Determine the ip offset (based on $_gET['p'])
        $numPages = ceil($numIps / 50);

        $p = (!Input::query('p') || Input::query('p') <= 1 || Input::query('p') > $numPages) ? 1 : intval(Input::query('p'));
        $startFrom = 50 * ($p - 1);

        return View::setPageInfo([
                'title' => [Utils::escape(ForumSettings::get('o_board_title')), __('Admin'), __('Users'), __('Results head')],
                'active_page' => 'admin',
                'admin_console' => true,
                'page' => $p,
                'paging_links' => '<span class="pages-label">'.__('Pages').' </span>'.Url::paginateOld($numPages, $p, '?ip_stats='.$args['id']),
                'start_from'        =>  $startFrom,
                'ip_data'   =>  $this->model->getIpStats($args['id'], $startFrom),
            ]
        )->addTemplate('@forum/admin/users/search_ip')->display();
    }

    // Show IP statistics for a certain user IP
    public function showusers($req, $res, $args)
    {
        Container::get('hooks')->fire('controller.admin.users.showusers');

        $searchIp = Input::query('ip');

        if (!@preg_match('%^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}$%', $searchIp) && !@preg_match('%^((([0-9A-Fa-f]{1,4}:){7}[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){6}:[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){5}:([0-9A-Fa-f]{1,4}:)?[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){4}:([0-9A-Fa-f]{1,4}:){0,2}[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){3}:([0-9A-Fa-f]{1,4}:){0,3}[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){2}:([0-9A-Fa-f]{1,4}:){0,4}[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){6}((\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b)\.){3}(\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b))|(([0-9A-Fa-f]{1,4}:){0,5}:((\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b)\.){3}(\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b))|(::([0-9A-Fa-f]{1,4}:){0,5}((\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b)\.){3}(\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b))|([0-9A-Fa-f]{1,4}::([0-9A-Fa-f]{1,4}:){0,5}[0-9A-Fa-f]{1,4})|(::([0-9A-Fa-f]{1,4}:){0,6}[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){1,7}:))$%', $searchIp)) {
            throw new Error(__('Bad IP message'), 400);
        }

        // Fetch user count
        $numUsers = $this->model->getNumUsersIp($searchIp);

        // Determine the user offset (based on $_gET['p'])
        $numPages = ceil($numUsers / 50);

        $p = (!Input::query('p') || Input::query('p') <= 1 || Input::query('p') > $numPages) ? 1 : intval(Input::query('p'));
        $startFrom = 50 * ($p - 1);

        return View::setPageInfo([
                'title' => [Utils::escape(ForumSettings::get('o_board_title')), __('Admin'), __('Users'), __('Results head')],
                'active_page' => 'admin',
                'admin_console' => true,
                'paging_links' => '<span class="pages-label">'.__('Pages').' </span>'.Url::paginateOld($numPages, $p, '?ip_stats='.$searchIp),
                'page' => $p,
                'start_from'        =>  $startFrom,
                'info'   =>  $this->model->getInfoPoster($searchIp, $startFrom),
            ]
        )->addTemplate('@forum/admin/users/show_users')->display();
    }
}
