<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace FeatherBB\Plugins;

use FeatherBB\Core\Error;
use FeatherBB\Core\Plugin as BasePlugin;

class Test extends BasePlugin
{
    public function run()
    {
        $this->hooks->bind('model.forum.get_forum_actions', [$this, 'addMarkRead']);
        $this->hooks->bind('view.header.navlinks', [$this, 'addNavlink']);

        $feather = $this->feather;
        $this->feather->get('/test-plugin(/)', function() use ($feather) {
            if(!$feather->user->is_admmod) {
                throw new Error(__('No permission'), 403);
            }
        }, [$this, 'testRoute'])->name('FPtest');
    }

    public function addMarkRead($forum_actions)
    {
        $forum_actions[] = '<a href="' . $this->feather->url->get('mark-read/') . '">Test1</a>';
        $forum_actions[] = '<a href="' . $this->feather->url->get('mark-read/') . '">Test2</a>';
        return $forum_actions;
    }

    public function addNavlink($navlinks)
    {
        $navlinks[] = '3 = <a href="'.$this->feather->urlFor('FPtest').'">Test plugin</a>';
        return $navlinks;
    }

    public function testRoute()
    {
        echo 'This only is a test plugin.';
    }
}
