<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace plugin;

class plugintest
{

    public function __construct()
    {
        $this->feather = \Slim\Slim::getInstance();
        $this->start = $this->feather->start;
        $this->config = $this->feather->config;
        $this->user = $this->feather->user;
        $this->request = $this->feather->request;
        $this->hook = $this->feather->hooks;

        $this->hook->bind('get_forum_actions', function ($forum_actions) {
            $forum_actions[] = '<a href="' . get_link('mark-read/') . '">Test1</a>';
            return $forum_actions;
        });

        $this->hook->bind('get_forum_actions', function ($forum_actions) {
            $forum_actions[] = '<a href="' . get_link('mark-read/') . '">Test2</a>';
            return $forum_actions;
        });

        $this->hook->bind('query_fetch_users_online', function ($query) {
            $query = $query->limit(50);
            return $query;
        });

        /*$this->hook->bind('query_fetch_users_online', function ($query) {
            $query = $query->offset(50);
            return $query;
        });*/
    }
}
