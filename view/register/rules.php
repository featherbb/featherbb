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

<div id="rules" class="blockform">
	<div class="hd"><h2><span><?php echo __('Forum rules') ?></span></h2></div>
	<div class="box">
		<form method="get" action="<?php echo get_link('register/') ?>">
			<div class="inform">
				<fieldset>
					<legend><?php echo __('Rules legend') ?></legend>
					<div class="infldset">
						<div class="usercontent"><?php echo $feather_config['o_rules_message'] ?></div>
					</div>
				</fieldset>
			</div>
			<p class="buttons"><input type="submit" name="agree" value="<?php echo __('Agree') ?>" /> <input type="submit" name="cancel" value="<?php echo __('Cancel') ?>" /></p>
		</form>
	</div>
</div>