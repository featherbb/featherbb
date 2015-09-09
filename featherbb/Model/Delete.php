<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace FeatherBB\Model;

use DB;
use FeatherBB\Core\Error;
use FeatherBB\Core\Url;

class Delete
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

    //
    // Deletes any avatars owned by the specified user ID
    //
    public static function avatar($user_id)
    {
        $feather = \Slim\Slim::getInstance();

        $filetypes = array('jpg', 'gif', 'png');

        // Delete user avatar
        foreach ($filetypes as $cur_type) {
            if (file_exists($feather->forum_env['FEATHER_ROOT'].$feather->config['o_avatars_dir'].'/'.$user_id.'.'.$cur_type)) {
                @unlink($feather->forum_env['FEATHER_ROOT'].$feather->config['o_avatars_dir'].'/'.$user_id.'.'.$cur_type);
            }
        }
    }
}
