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
		<h2><span><?php echo $lang_admin_bans['New ban head'] ?></span></h2>
		<div class="box">
			<form id="bans" method="post" action="<?php echo get_link('admin/bans/add/') ?>">
				<input type="hidden" name="<?php echo $csrf_key; ?>" value="<?php echo $csrf_token; ?>">
				<div class="inform">
					<fieldset>
						<legend><?php echo $lang_admin_bans['Add ban subhead'] ?></legend>
						<div class="infldset">
							<table class="aligntop">
								<tr>
									<th scope="row"><?php echo $lang_admin_bans['Username label'] ?><div><input type="submit" name="add_ban" value="<?php echo $lang_admin_common['Add'] ?>" tabindex="2" /></div></th>
									<td>
										<input type="text" name="new_ban_user" size="25" maxlength="25" tabindex="1" />
										<span><?php echo $lang_admin_bans['Username advanced help'] ?></span>
									</td>
								</tr>
							</table>
						</div>
					</fieldset>
				</div>
			</form>
		</div>

		<h2 class="block2"><span><?php echo $lang_admin_bans['Ban search head'] ?></span></h2>
		<div class="box">
			<form id="find_bans" method="get" action="<?php echo get_link('admin/bans/') ?>">
				<input type="hidden" name="<?php echo $csrf_key; ?>" value="<?php echo $csrf_token; ?>">
				<p class="submittop"><input type="submit" name="find_ban" value="<?php echo $lang_admin_bans['Submit search'] ?>" tabindex="3" /></p>
				<div class="inform">
					<fieldset>
						<legend><?php echo $lang_admin_bans['Ban search subhead'] ?></legend>
						<div class="infldset">
							<p><?php echo $lang_admin_bans['Ban search info'] ?></p>
							<table class="aligntop">
								<tr>
									<th scope="row"><?php echo $lang_admin_bans['Username label'] ?></th>
									<td><input type="text" name="username" size="30" maxlength="25" tabindex="4" /></td>
								</tr>
								<tr>
									<th scope="row"><?php echo $lang_admin_bans['IP label'] ?></th>
									<td><input type="text" name="ip" size="30" maxlength="255" tabindex="5" /></td>
								</tr>
								<tr>
									<th scope="row"><?php echo $lang_admin_bans['E-mail label'] ?></th>
									<td><input type="text" name="email" size="30" maxlength="80" tabindex="6" /></td>
								</tr>
								<tr>
									<th scope="row"><?php echo $lang_admin_bans['Message label'] ?></th>
									<td><input type="text" name="message" size="30" maxlength="255" tabindex="7" /></td>
								</tr>
								<tr>
									<th scope="row"><?php echo $lang_admin_bans['Expire after label'] ?></th>
									<td><input type="text" name="expire_after" size="10" maxlength="10" tabindex="8" />
									<span><?php echo $lang_admin_bans['Date help'] ?></span></td>
								</tr>
								<tr>
									<th scope="row"><?php echo $lang_admin_bans['Expire before label'] ?></th>
									<td><input type="text" name="expire_before" size="10" maxlength="10" tabindex="9" />
									<span><?php echo $lang_admin_bans['Date help'] ?></span></td>
								</tr>
								<tr>
									<th scope="row"><?php echo $lang_admin_bans['Order by label'] ?></th>
									<td>
										<select name="order_by" tabindex="10">
											<option value="username" selected="selected"><?php echo $lang_admin_bans['Order by username'] ?></option>
											<option value="ip"><?php echo $lang_admin_bans['Order by ip'] ?></option>
											<option value="email"><?php echo $lang_admin_bans['Order by e-mail'] ?></option>
											<option value="expire"><?php echo $lang_admin_bans['Order by expire'] ?></option>
										</select>
                                        &#160;&#160;&#160;
                                        <select name="direction" tabindex="11">
											<option value="ASC" selected="selected"><?php echo $lang_admin_bans['Ascending'] ?></option>
											<option value="DESC"><?php echo $lang_admin_bans['Descending'] ?></option>
										</select>
									</td>
								</tr>
							</table>
						</div>
					</fieldset>
				</div>
				<p class="submitend"><input type="submit" name="find_ban" value="<?php echo $lang_admin_bans['Submit search'] ?>" tabindex="12" /></p>
			</form>
		</div>
	</div>
	<div class="clearer"></div>
</div>