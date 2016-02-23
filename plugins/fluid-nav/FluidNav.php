<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace FeatherBB\Plugins;

use FeatherBB\Core\Plugin as BasePlugin;

class FluidNav extends BasePlugin
{
    public function run()
    {
        // Container::get('hooks')->bind('model.index.get_forum_actions', [$this, 'addMarkRead']);
        View::addAsset('js', 'plugins/fluid-nav/pjax-standalone.js');
        View::addAsset('js', 'plugins/fluid-nav/script.js');
    }
}
