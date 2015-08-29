<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace Plugins;

use \FeatherBB\Plugin as Plugin;

class Test extends Plugin
{
    public static $name = 'A Test plugin';
    public static $description = "Just a useless plugin to check everything is all right! And it seems so ;)";

    public function runHooks()
    {
        $this->hooks->bind('get_forum_actions', function ($forum_actions) {
           $forum_actions[] = '<a href="' . get_link('mark-read/') . '">Test1</a>';
           return $forum_actions;
        });

        // $this->hooks->bind('get_forum_actions', function ($forum_actions) {
        //     $forum_actions[] = '<a href="' . get_link('mark-read/') . '">Test2</a>';
        //     return $forum_actions;
        // });
    }

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

    public static function test()
    {
        echo 'test';
    }
}
