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
		<h2><span><?php _e('Delete group head') ?></span></h2>
		<div class="box">
			<form id="groups" method="post" action="<?php echo $feather->url->get_link('admin/groups/delete/'.$id.'/') ?>">
				<input type="hidden" name="<?php echo $csrf_key; ?>" value="<?php echo $csrf_token; ?>">
				<div class="inform">
					<fieldset>
						<legend><?php _e('Move users subhead') ?></legend>
						<div class="infldset">
							<p><?php printf(__('Move users info'), feather_escape($group_info['title']), forum_number_format($group_info['members'])) ?></p>
							<label><?php _e('Move users label') ?>
							<select name="move_to_group">
								<?= $group_list_delete; ?>
							</select>
							<br /></label>
						</div>
					</fieldset>
				</div>
				<p class="buttons"><input type="submit" name="del_group" value="<?php _e('Delete group') ?>" /><a href="javascript:history.go(-1)"><?php _e('Go back') ?></a></p>
			</form>
		</div>
	</div>
	<div class="clearer"></div>
</div>
