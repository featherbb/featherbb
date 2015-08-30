<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace app\model\admin;

use DB;

class permissions
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

    public function update_permissions()
    {
        $form = array_map('intval', $this->request->post('form'));
        $form = $this->hook->fire('permissions.update_permissions.form', $form);

        foreach ($form as $key => $input) {
            // Make sure the input is never a negative value
            if ($input < 0) {
                $input = 0;
            }

            // Only update values that have changed
            if (array_key_exists('p_'.$key, $this->config) && $this->config['p_'.$key] != $input) {
                DB::for_table('config')->where('conf_name', 'p_'.$key)
                                                           ->update_many('conf_value', $input);
            }
        }

        // Regenerate the config cache
        if (!defined('FORUM_CACHE_FUNCTIONS_LOADED')) {
            require FEATHER_ROOT.'include/cache.php';
        }

        generate_config_cache();

        redirect($this->feather->url->get('admin/permissions/'), __('Perms updated redirect'));
    }
}
