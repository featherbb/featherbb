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
		<h2><span><?php _e('Ban users') ?></span></h2>
		<div class="box">
			<form id="bans2" name="confirm_ban_users" method="post" action="<?php echo $feather->url->get_link('admin/users') ?>">
				<input type="hidden" name="<?php echo $csrf_key; ?>" value="<?php echo $csrf_token; ?>">
				<input type="hidden" name="users" value="<?php echo implode(',', $user_ids) ?>" />
				<div class="inform">
					<fieldset>
						<legend><?php _e('Message expiry subhead') ?></legend>
						<div class="infldset">
							<table class="aligntop">
								<tr>
									<th scope="row"><?php _e('Ban message label') ?></th>
									<td>
										<input type="text" name="ban_message" size="50" maxlength="255" tabindex="1" />
										<span><?php _e('Ban message help') ?></span>
									</td>
								</tr>
								<tr>
									<th scope="row"><?php _e('Expire date label') ?></th>
									<td>
										<input type="text" name="ban_expire" size="17" maxlength="10" tabindex="2" />
										<span><?php _e('Expire date help') ?></span>
									</td>
								</tr>
								<tr>
									<th scope="row"><?php _e('Ban IP label') ?></th>
									<td>
										<label class="conl"><input type="radio" name="ban_the_ip" tabindex="3" value="1" checked="checked" />&#160;<strong><?php _e('Yes') ?></strong></label>
										<label class="conl"><input type="radio" name="ban_the_ip" tabindex="4" value="0" checked="checked" />&#160;<strong><?php _e('No') ?></strong></label>
										<span class="clearb"><?php _e('Ban IP help') ?></span>
									</td>
								</tr>
							</table>
						</div>
					</fieldset>
				</div>
				<p class="submitend"><input type="submit" name="ban_users_comply" value="<?php _e('Save') ?>" tabindex="3" /></p>
			</form>
		</div>
	</div>
	<div class="clearer"></div>
</div>