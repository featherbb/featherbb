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
		<h2><span><?php echo __('New ban head') ?></span></h2>
		<div class="box">
			<form id="bans" method="post" action="<?php echo get_link('admin/bans/add/') ?>">
				<input type="hidden" name="<?php echo $csrf_key; ?>" value="<?php echo $csrf_token; ?>">
				<div class="inform">
					<fieldset>
						<legend><?php echo __('Add ban subhead') ?></legend>
						<div class="infldset">
							<table class="aligntop">
								<tr>
									<th scope="row"><?php echo __('Username label') ?><div><input type="submit" name="add_ban" value="<?php echo __('Add') ?>" tabindex="2" /></div></th>
									<td>
										<input type="text" name="new_ban_user" size="25" maxlength="25" tabindex="1" />
										<span><?php echo __('Username advanced help') ?></span>
									</td>
								</tr>
							</table>
						</div>
					</fieldset>
				</div>
			</form>
		</div>

		<h2 class="block2"><span><?php echo __('Ban search head') ?></span></h2>
		<div class="box">
			<form id="find_bans" method="get" action="<?php echo get_link('admin/bans/') ?>">
				<input type="hidden" name="<?php echo $csrf_key; ?>" value="<?php echo $csrf_token; ?>">
				<p class="submittop"><input type="submit" name="find_ban" value="<?php echo __('Submit search') ?>" tabindex="3" /></p>
				<div class="inform">
					<fieldset>
						<legend><?php echo __('Ban search subhead') ?></legend>
						<div class="infldset">
							<p><?php echo __('Ban search info') ?></p>
							<table class="aligntop">
								<tr>
									<th scope="row"><?php echo __('Username label') ?></th>
									<td><input type="text" name="username" size="30" maxlength="25" tabindex="4" /></td>
								</tr>
								<tr>
									<th scope="row"><?php echo __('IP label') ?></th>
									<td><input type="text" name="ip" size="30" maxlength="255" tabindex="5" /></td>
								</tr>
								<tr>
									<th scope="row"><?php echo __('E-mail label') ?></th>
									<td><input type="text" name="email" size="30" maxlength="80" tabindex="6" /></td>
								</tr>
								<tr>
									<th scope="row"><?php echo __('Message label') ?></th>
									<td><input type="text" name="message" size="30" maxlength="255" tabindex="7" /></td>
								</tr>
								<tr>
									<th scope="row"><?php echo __('Expire after label') ?></th>
									<td><input type="text" name="expire_after" size="10" maxlength="10" tabindex="8" />
									<span><?php echo __('Date help') ?></span></td>
								</tr>
								<tr>
									<th scope="row"><?php echo __('Expire before label') ?></th>
									<td><input type="text" name="expire_before" size="10" maxlength="10" tabindex="9" />
									<span><?php echo __('Date help') ?></span></td>
								</tr>
								<tr>
									<th scope="row"><?php echo __('Order by label') ?></th>
									<td>
										<select name="order_by" tabindex="10">
											<option value="username" selected="selected"><?php echo __('Order by username') ?></option>
											<option value="ip"><?php echo __('Order by ip') ?></option>
											<option value="email"><?php echo __('Order by e-mail') ?></option>
											<option value="expire"><?php echo __('Order by expire') ?></option>
										</select>
                                        &#160;&#160;&#160;
                                        <select name="direction" tabindex="11">
											<option value="ASC" selected="selected"><?php echo __('Ascending') ?></option>
											<option value="DESC"><?php echo __('Descending') ?></option>
										</select>
									</td>
								</tr>
							</table>
						</div>
					</fieldset>
				</div>
				<p class="submitend"><input type="submit" name="find_ban" value="<?php echo __('Submit search') ?>" tabindex="12" /></p>
			</form>
		</div>
	</div>
	<div class="clearer"></div>
</div>