<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

use FeatherBB\Core\Url;

// Make sure no one attempts to run this script "directly"
if (!defined('FEATHER')) {
    exit;
}

?>

<div id="rules" class="blockform">
	<div class="hd"><h2><span><?php _e('Forum rules') ?></span></h2></div>
	<div class="box">
		<form method="get" action="<?php echo $feather->urlFor('register') ?>">
			<div class="inform">
				<fieldset>
					<legend><?php _e('Rules legend') ?></legend>
					<div class="infldset">
						<div class="usercontent"><?php echo $feather->forum_settings['o_rules_message'] ?></div>
					</div>
				</fieldset>
			</div>
			<p class="buttons"><input type="submit" name="agree" value="<?php _e('Agree') ?>" /> <input type="submit" name="cancel" value="<?php _e('Cancel') ?>" /></p>
		</form>
	</div>
</div>
