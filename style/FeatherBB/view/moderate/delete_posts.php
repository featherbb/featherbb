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
<div class="blockform">
	<h2><span><?php echo __('Delete posts') ?></span></h2>
	<div class="box">
		<form method="post" action="">
			<input type="hidden" name="<?php echo $csrf_key; ?>" value="<?php echo $csrf_token; ?>">
			<div class="inform">
				<fieldset>
					<legend><?php echo __('Confirm delete legend') ?></legend>
					<div class="infldset">
						<input type="hidden" name="posts" value="<?php echo implode(',', array_map('intval', array_keys($posts))) ?>" />
						<p><?php echo __('Delete posts comply') ?></p>
					</div>
				</fieldset>
			</div>
			<p class="buttons"><input type="submit" name="delete_posts_comply" value="<?php echo __('Delete') ?>" /> <a href="javascript:history.go(-1)"><?php echo __('Go back') ?></a></p>
		</form>
	</div>
</div>