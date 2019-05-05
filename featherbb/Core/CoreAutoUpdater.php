<?php
/**
 * Copyright (C) 2015-2019 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace FeatherBB\Core;

use FeatherBB\Core\Interfaces\ForumEnv;

class CoreAutoUpdater extends AutoUpdater
{
    public function __construct()
    {
        // Construct parent class
        parent::__construct(getcwd().'/temp', getcwd());

        // Set plugin informations
        $this->setRootFolder('featherbb');
        $this->setCurrentVersion(ForumEnv::get('FORUM_VERSION'));
        $this->setUpdateUrl('https://api.github.com/repos/featherbb/featherbb/releases');
    }
}