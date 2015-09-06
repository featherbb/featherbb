<?php

/**
 * Copyright (C) 2015 FeatherBB
 * Parser (C) 2011 Jeff Roberson (jmrware.com)
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace FeatherBB\Model\Admin;

use FeatherBB\Core\Utils;
use FeatherBB\Core\Url;
use DB;

class Parser
{
    public function __construct()
    {
        $this->feather = \Slim\Slim::getInstance();
        $this->start = $this->feather->start;
        $this->config = $this->feather->config;
        $this->user = $this->feather->user;
        $this->request = $this->feather->request;
        $this->hook = $this->feather->hooks;
    }

    // Helper public function returns array of smiley image files
    //   stored in the style/img/smilies directory.
    public function get_smiley_files()
    {
        $imgfiles = array();
        $filelist = scandir($this->feather->forum_env['FEATHER_ROOT'].'style/img/smilies');
        $filelist = $this->hook->fire('parser.get_smiley_files.filelist', $filelist);
        foreach ($filelist as $file) {
            if (preg_match('/\.(?:png|gif|jpe?g)$/', $file)) {
                $imgfiles[] = $file;
            }
        }
        $imgfiles = $this->hook->fire('parser.get_smiley_files.imgfiles', $imgfiles);
        return $imgfiles;
    }
}
