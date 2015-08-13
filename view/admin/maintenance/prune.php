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
		<h2><span><?php echo __('Prune head') ?></span></h2>
		<div class="box">
			<form method="post" action="<?php echo get_link('admin/maintenance/') ?>">
				<input type="hidden" name="<?php echo $csrf_key; ?>" value="<?php echo $csrf_token; ?>">
				<div class="inform">
					<input type="hidden" name="action" value="prune" />
					<input type="hidden" name="prune_days" value="<?php echo $prune['days'] ?>" />
					<input type="hidden" name="prune_sticky" value="<?php echo $prune_sticky ?>" />
					<input type="hidden" name="prune_from" value="<?php echo $prune_from ?>" />
					<fieldset>
						<legend><?php echo __('Confirm prune subhead') ?></legend>
						<div class="infldset">
							<p><?php printf(__('Confirm prune info'), $prune['days'], $prune['forum'], forum_number_format($prune['num_topics'])) ?></p>
							<p class="warntext"><?php echo __('Confirm prune warn') ?></p>
						</div>
					</fieldset>
				</div>
				<p class="buttons"><input type="submit" name="prune_comply" value="<?php echo __('Prune') ?>" /><a href="javascript:history.go(-1)"><?php echo __('Go back') ?></a></p>
			</form>
		</div>
	</div>
	<div class="clearer"></div>
</div>