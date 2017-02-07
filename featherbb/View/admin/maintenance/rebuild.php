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

Container::get('hooks')->fire('view.admin.maintenance.rebuild.start');
?>

<h1><?= __('Rebuilding index info') ?></h1>

<script type="text/javascript">window.location="<?= Router::pathFor('adminMaintenance').$query_str ?>"</script>

<p><?= sprintf(__('Javascript redirect failed'), '<a href="'.Router::pathFor('adminMaintenance').$query_str.'">'.__('Click here').'</a>')?></p>

<?php
Container::get('hooks')->fire('view.admin.maintenance.prune.end');
