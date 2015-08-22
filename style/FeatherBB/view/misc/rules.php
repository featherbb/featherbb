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
<div id="rules" class="block">
	<div class="hd"><h2><span><?php _e('Forum rules') ?></span></h2></div>
	<div class="box">
		<div id="rules-block" class="inbox">
			<div class="usercontent"><?php echo $feather_config['o_rules_message'] ?></div>
		</div>
	</div>
</div>