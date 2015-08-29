<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

// Make sure no one attempts to run this script "directly"
if (!defined('FEATHER')) {
    exit;
}
?>
<h1><?php _e('Rebuilding index info') ?></h1>
<script type="text/javascript">window.location="<?= $feather->url->get('admin/maintenance/').$query_str ?>"</script>
<p><?= sprintf(__('Javascript redirect failed'), '<a href="'.$feather->url->get('admin/maintenance/').$query_str.'">'.__('Click here').'</a>')?></p>
