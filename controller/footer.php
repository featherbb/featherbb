<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace controller;

class footer
{
    public function __construct()
    {
        $this->feather = \Slim\Slim::getInstance();
        $this->start = $this->feather->start;
        $this->config = $this->feather->config;
        $this->user = $this->feather->user;
        $this->request = $this->feather->request;
        $this->dontStop = false;
    }

    public function dontStop() {
        $this->dontStop = true;
    }

    public function display($footer_style = null, $id = null, $p = null, $pid = null, $forum_id = null, $num_pages = null)
    {
        // Render the footer
        // If no footer style has been specified, we use the default (only copyright/debug info)
        $footer_style = isset($footer_style) ? $footer_style : null;

        $id = isset($id) ? $id : null;
        $p = isset($p) ? $p : null;
        $pid = isset($pid) ? $pid : null;

        $forum_id = isset($forum_id) ? $forum_id : null;

        $num_pages = isset($num_pages) ? $num_pages : null;

        if (!$this->feather->cache->isCached('quickjump')) {
            $this->feather->cache->store('quickjump', \model\cache::get_quickjump());
        }

        $this->feather->render('footer.php', array(
                            'id' => $id,
                            'p' => $p,
                            'pid' => $pid,
                            'feather_start' => $this->start,
                            'quickjump' => $this->feather->cache->retrieve('quickjump'),
                            'forum_id' => $forum_id,
                            'num_pages' => $num_pages,
                            )
                    );

        // Close Idiorm connection
        $pdo = \DB::get_db();
        $pdo = null;

        // If we need to stop the application outside a route
        if ($this->dontStop) {
            die();
        }

        // If we reached this far, we shouldn't execute more code
        $this->feather->stop();
    }
}
