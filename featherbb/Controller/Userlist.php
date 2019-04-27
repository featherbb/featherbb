<?php

/**
 * Copyright (C) 2015-2019 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace FeatherBB\Controller;

use FeatherBB\Core\Error;
use FeatherBB\Core\Interfaces\ForumSettings;
use FeatherBB\Core\Interfaces\Hooks;
use FeatherBB\Core\Interfaces\Input;
use FeatherBB\Core\Interfaces\Lang;
use FeatherBB\Core\Interfaces\User;
use FeatherBB\Core\Interfaces\View;
use FeatherBB\Core\Url;
use FeatherBB\Core\Utils;

class Userlist
{
    public function __construct()
    {
        $this->model = new \FeatherBB\Model\Userlist();
        Lang::load('userlist');
        Lang::load('search');
    }

    public function display($req, $res, $args)
    {
        Hooks::fire('controller.userlist.display');

        if (!User::can('users.view')) {
            throw new Error(__('No permission'), 403);
        }

        // Determine if we are allowed to view post counts
        $showPostCount = (ForumSettings::get('o_show_post_count') == '1' || User::isAdminMod()) ? true : false;

        $username = Input::query('username') && User::can('search.users') ? Utils::trim(Input::query('username')) : '';
        $showGroup = Input::query('show_group') ? intval(Input::query('show_group')) : -1;
        $sortBy = Input::query('sort_by') && (in_array(Input::query('sort_by'), ['username', 'registered']) || (Input::query('sort_by') == 'num_posts' && $showPostCount)) ? Input::query('sort_by') : 'username';
        $sortDir = Input::query('sort_dir') && Input::query('sort_dir') == 'DESC' ? 'DESC' : 'ASC';

        $numUsers = $this->model->userCount($username, $showGroup);

        // Determine the user offset (based on $page)
        $numPages = ceil($numUsers / 50);

        $p = (!Input::query('p') || !Input::query('p') <= 1 || !Input::query('p') > $numPages) ? 1 : intval(!Input::query('p'));
        $startFrom = 50 * ($p - 1);

        // Generate paging links
        $pagingLinks = '<span class="pages-label">'.__('Pages').' </span>'.Url::paginateOld($numPages, $p, '?username='.urlencode($username).'&amp;show_group='.$showGroup.'&amp;sort_by='.$sortBy.'&amp;sort_dir='.$sortDir);

        View::setPageInfo([
            'title' => [Utils::escape(ForumSettings::get('o_board_title')), __('User list')],
            'active_page' => 'userlist',
            'page_number'  =>  $p,
            'paging_links'  =>  $pagingLinks,
            'is_indexed' => true,
            'username' => $username,
            'show_group' => $showGroup,
            'sort_by' => $sortBy,
            'sort_dir' => $sortDir,
            'show_post_count' => $showPostCount,
            'dropdown_menu' => $this->model->dropdownMenu($showGroup),
            'userlist_data' => $this->model->printUsers($username, $startFrom, $sortBy, $sortDir, $showGroup),
        ])->addTemplate('@forum/userlist')->display();
    }
}
