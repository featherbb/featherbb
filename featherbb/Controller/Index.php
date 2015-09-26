<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace FeatherBB\Controller;

use FeatherBB\Core\Error;
use FeatherBB\Core\Track;
use FeatherBB\Core\Url;
use FeatherBB\Core\Utils;
use FeatherBB\Model\Auth;

class Index
{
    public function __construct()
    {
        $this->feather = \Slim\Slim::getInstance();
        $this->model = new \FeatherBB\Model\Index();
        load_textdomain('featherbb', $this->feather->forum_env['FEATHER_ROOT'].'featherbb/lang/'.$this->feather->user->language.'/index.mo');
        load_textdomain('featherbb', $this->feather->forum_env['FEATHER_ROOT'].'featherbb/lang/'.$this->feather->user->language.'/misc.mo');
    }

    public function display()
    {
        $this->feather->hooks->fire('controller.index.index');

        $this->feather->template->setPageInfo(array(
            'title' => array(Utils::escape($this->feather->forum_settings['o_board_title'])),
            'active_page' => 'index',
            'is_indexed' => true,
            'index_data' => $this->model->print_categories_forums(),
            'stats' => $this->model->collect_stats(),
            'online'    =>    $this->model->fetch_users_online(),
            'forum_actions'        =>    $this->model->get_forum_actions(),
            'cur_cat'   => 0
        ))->addTemplate('index.php')->display();
    }

    public function rules()
    {
        $this->feather->hooks->fire('controller.index.rules');

        if ($this->feather->forum_settings['o_rules'] == '0' || ($this->feather->user->is_guest && $this->feather->user->g_read_board == '0' && $this->feather->forum_settings['o_regs_allow'] == '0')) {
            throw new Error(__('Bad request'), 404);
        }

        $this->feather->template->setPageInfo(array(
            'title' => array(Utils::escape($this->feather->forum_settings['o_board_title']), __('Forum rules')),
            'active_page' => 'rules'
            ))->addTemplate('misc/rules.php')->display();
    }

    public function markread()
    {
        $this->feather->hooks->fire('controller.index.markread');

        Auth::set_last_visit($this->feather->user->id, $this->feather->user->logged);
        // Reset tracked topics
        Track::set_tracked_topics(null);
        Url::redirect($this->feather->urlFor('home'), __('Mark read redirect'));
    }
}
