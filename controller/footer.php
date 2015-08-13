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
        global $lang_common;

        // Render the footer
        // If no footer style has been specified, we use the default (only copyright/debug info)
        $footer_style = isset($footer_style) ? $footer_style : null;

        $id = isset($id) ? $id : null;
        $p = isset($p) ? $p : null;
        $pid = isset($pid) ? $pid : null;

        $forum_id = isset($forum_id) ? $forum_id : null;

        $num_pages = isset($num_pages) ? $num_pages : null;

        $this->feather->render('footer.php', array(
                            'lang_common' => $lang_common,
                            'id' => $id,
                            'p' => $p,
                            'pid' => $pid,
                            'feather_user' => $this->user,
                            'feather_config' => $this->config,
                            'feather_start' => $this->start,
                            'footer_style' => $footer_style,
                            'forum_id' => $forum_id,
                            'num_pages' => $num_pages,
                            'feather' => $this->feather,
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