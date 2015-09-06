<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */
 
use FeatherBB\Core\Utils;
use FeatherBB\Core\Url;

// Make sure no one attempts to run this script "directly"
if (!defined('FEATHER')) {
    exit;
}
?>

	<div class="blockform">
		<h2><span><?php _e('New ban head') ?></span></h2>
		<div class="box">
			<form id="bans" method="post" action="<?php echo Url::get('admin/bans/add/') ?>">
				<input type="hidden" name="<?php echo $csrf_key; ?>" value="<?php echo $csrf_token; ?>">
				<div class="inform">
					<fieldset>
						<legend><?php _e('Add ban subhead') ?></legend>
						<div class="infldset">
							<table class="aligntop">
								<tr>
									<th scope="row"><?php _e('Username label') ?><div><input type="submit" name="add_ban" value="<?php _e('Add') ?>" tabindex="2" /></div></th>
									<td>
										<input type="text" name="new_ban_user" size="25" maxlength="25" tabindex="1" />
										<span><?php _e('Username advanced help') ?></span>
									</td>
								</tr>
							</table>
						</div>
					</fieldset>
				</div>
			</form>
		</div>

		<h2 class="block2"><span><?php _e('Ban search head') ?></span></h2>
		<div class="box">
			<form id="find_bans" method="get" action="<?php echo Url::get('admin/bans/') ?>">
				<input type="hidden" name="<?php echo $csrf_key; ?>" value="<?php echo $csrf_token; ?>">
				<p class="submittop"><input type="submit" name="find_ban" value="<?php _e('Submit search') ?>" tabindex="3" /></p>
				<div class="inform">
					<fieldset>
						<legend><?php _e('Ban search subhead') ?></legend>
						<div class="infldset">
							<p><?php _e('Ban search info') ?></p>
							<table class="aligntop">
								<tr>
									<th scope="row"><?php _e('Username label') ?></th>
									<td><input type="text" name="username" size="30" maxlength="25" tabindex="4" /></td>
								</tr>
								<tr>
									<th scope="row"><?php _e('IP label') ?></th>
									<td><input type="text" name="ip" size="30" maxlength="255" tabindex="5" /></td>
								</tr>
								<tr>
									<th scope="row"><?php _e('E-mail label') ?></th>
									<td><input type="text" name="email" size="30" maxlength="80" tabindex="6" /></td>
								</tr>
								<tr>
									<th scope="row"><?php _e('Message label') ?></th>
									<td><input type="text" name="message" size="30" maxlength="255" tabindex="7" /></td>
								</tr>
								<tr>
									<th scope="row"><?php _e('Expire after label') ?></th>
									<td><input type="text" name="expire_after" size="10" maxlength="10" tabindex="8" />
									<span><?php _e('Date help') ?></span></td>
								</tr>
								<tr>
									<th scope="row"><?php _e('Expire before label') ?></th>
									<td><input type="text" name="expire_before" size="10" maxlength="10" tabindex="9" />
									<span><?php _e('Date help') ?></span></td>
								</tr>
								<tr>
									<th scope="row"><?php _e('Order by label') ?></th>
									<td>
										<select name="order_by" tabindex="10">
											<option value="username" selected="selected"><?php _e('Order by username') ?></option>
											<option value="ip"><?php _e('Order by ip') ?></option>
											<option value="email"><?php _e('Order by e-mail') ?></option>
											<option value="expire"><?php _e('Order by expire') ?></option>
										</select>
                                        &#160;&#160;&#160;
                                        <select name="direction" tabindex="11">
											<option value="ASC" selected="selected"><?php _e('Ascending') ?></option>
											<option value="DESC"><?php _e('Descending') ?></option>
										</select>
									</td>
								</tr>
							</table>
						</div>
					</fieldset>
				</div>
				<p class="submitend"><input type="submit" name="find_ban" value="<?php _e('Submit search') ?>" tabindex="12" /></p>
			</form>
		</div>
	</div>
	<div class="clearer"></div>
</div>