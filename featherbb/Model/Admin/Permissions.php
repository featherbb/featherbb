<?php

/**
 * Copyright (C) 2015-2017 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace FeatherBB\Model\Admin;

use FeatherBB\Core\Database as DB;
use FeatherBB\Core\Interfaces\Cache as CacheInterface;
use FeatherBB\Core\Interfaces\Container;
use FeatherBB\Core\Interfaces\ForumSettings;
use FeatherBB\Core\Interfaces\Hooks;
use FeatherBB\Core\Interfaces\Input;
use FeatherBB\Core\Interfaces\Router;
use FeatherBB\Model\Cache;

class Permissions
{
    public function update()
    {
        $form = array_map('intval', Input::post('form'));
        $form = Hooks::fire('model.admin.permissions.update_permissions.form', $form);

        foreach ($form as $key => $input) {
            // Make sure the input is never a negative value
            if ($input < 0) {
                $input = 0;
            }

            // Only update values that have changed
            if (array_key_exists('p_'.$key, Container::get('forum_settings')) && ForumSettings::get('p_'.$key) != $input) {
                DB::table('config')->where('conf_name', 'p_'.$key)
                                                           ->updateMany('conf_value', $input);
            }
        }

        // Regenerate the config cache
        $config = array_merge(Cache::getConfig(), Cache::getPreferences());
        CacheInterface::store('config', $config);

        return Router::redirect(Router::pathFor('adminPermissions'), __('Perms updated redirect'));
    }
}
