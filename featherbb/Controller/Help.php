<?php

/**
 * Copyright (C) 2015-2019 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace FeatherBB\Controller;

use FeatherBB\Core\Interfaces\ForumSettings;
use FeatherBB\Core\Interfaces\Hooks;
use FeatherBB\Core\Interfaces\Lang;
use FeatherBB\Core\Interfaces\View;
use FeatherBB\Core\Utils;

class Help
{
    public function __construct()
    {
        Lang::load('help');
    }

    public function display($req, $res, $args)
    {
        Hooks::fire('controller.help.start');

        View::setPageInfo([
            'title' => [Utils::escape(ForumSettings::get('o_board_title')), __('Help')],
            'active_page' => 'help',
        ])->addTemplate('@forum/help')->display();
    }
}
