<?php

/**
 * Copyright (C) 2015-2016 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

// Make sure no one attempts to run this script "directly"
if (!isset($feather)) {
    exit;
}

Container::get('hooks')->fire('view.maintenance.start');
?>
<div id="msg" class="block">
    <h2><span><?= __('Maintenance') ?></span></h2>
    <div class="box">
        <div class="inbox">
            <p><?= $msg ?></p>
<?php if ($backlink) {
    echo "\t\t\t".'<p><a href="javascript: history.go(-1)">'._('Go back').'</a></p>';
} ?>
        </div>
    </div>
</div>
<?php
Container::get('hooks')->fire('view.maintenance.end');
