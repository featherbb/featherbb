<?php

/**
 * Copyright (C) 2015-2017 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace FeatherBB\Controller;

use FeatherBB\Core\Error;
use FeatherBB\Core\Track;
use FeatherBB\Core\Utils;
use FeatherBB\Model\Auth;

class Index
{
    public function __construct()
    {
        $this->model = new \FeatherBB\Model\Index();
        Lang::load('index');
        Lang::load('misc');
    }

    public function display($req, $res, $args)
    {
        Container::get('hooks')->fire('controller.index.index');
        return View::setPageInfo([
            'title' => [Utils::escape(ForumSettings::get('o_board_title'))],
            'active_page' => 'index',
            'is_indexed' => true,
            'index_data' => $this->model->print_categories_forums(),
            'stats' => $this->model->collect_stats(),
            'online'    =>    $this->model->fetch_users_online(),
            'forum_actions'        =>    $this->model->get_forum_actions(),
            'cur_cat'   => 0
        ])->addTemplate('index.php')->display();
    }

    public function rules()
    {
        Container::get('hooks')->fire('controller.index.rules');

        if (ForumSettings::get('o_rules') == '0' || (User::get()->is_guest && !User::can('board.read') && ForumSettings::get('o_regs_allow') == '0')) {
            throw new Error(__('Bad request'), 404);
        }

        return View::setPageInfo([
            'title' => [Utils::escape(ForumSettings::get('o_board_title')), __('Forum rules')],
            'active_page' => 'rules'
            ]
        )->addTemplate('misc/rules.php')->display();
    }

    public function markread()
    {
        Container::get('hooks')->fire('controller.index.markread');

        Auth::set_last_visit(User::get()->id, User::get()->logged);
        // Reset tracked topics
        Track::set_tracked_topics(null);
        return Router::redirect(Router::pathFor('home'), __('Mark read redirect'));
    }
}
