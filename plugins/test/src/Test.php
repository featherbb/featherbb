<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace FeatherBB\Plugins;

use FeatherBB\Core\Plugin as BasePlugin;

class Test extends BasePlugin
{
    public function run()
    {
        $this->hooks->bind('get_forum_actions', function ($forum_actions) {
           $forum_actions[] = '<a href="' . $this->feather->url->get('mark-read/') . '">Test1</a>';
           return $forum_actions;
        });

        $this->hooks->bind('get_forum_actions', function ($forum_actions) {
            $forum_actions[] = '<a href="' . $this->feather->url->get('mark-read/') . '">Test2</a>';
            return $forum_actions;
        });
    }
}
