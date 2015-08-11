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
		<h2><span><?php echo $lang_admin_users['Delete users'] ?></span></h2>
		<div class="box">
			<form name="confirm_del_users" method="post" action="<?php echo get_link('admin/users') ?>">
				<input type="hidden" name="<?php echo $csrf_key; ?>" value="<?php echo $csrf_token; ?>">
				<input type="hidden" name="users" value="<?php echo implode(',', $user_ids) ?>" />
				<div class="inform">
					<fieldset>
						<legend><?php echo $lang_admin_users['Confirm delete legend'] ?></legend>
						<div class="infldset">
							<p><?php echo $lang_admin_users['Confirm delete info'] ?></p>
							<div class="rbox">
								<label><input type="checkbox" name="delete_posts" value="1" checked="checked" /><?php echo $lang_admin_users['Delete posts'] ?><br /></label>
							</div>
							<p class="warntext"><strong><?php echo $lang_admin_users['Delete warning'] ?></strong></p>
						</div>
					</fieldset>
				</div>
				<p class="buttons"><input type="submit" name="delete_users_comply" value="<?php echo $lang_admin_users['Delete'] ?>" /> <a href="javascript:history.go(-1)"><?php echo $lang_admin_common['Go back'] ?></a></p>
			</form>
		</div>
	</div>
	<div class="clearer"></div>
</div>