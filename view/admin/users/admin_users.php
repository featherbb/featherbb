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
		<h2><span><?php echo __('User search head') ?></span></h2>
		<div class="box">
			<form id="find_user" method="get" action="<?php echo get_link('admin/users/') ?>">
				<p class="submittop"><input type="submit" name="find_user" value="<?php echo __('Submit search') ?>" tabindex="1" /></p>
				<div class="inform">
					<fieldset>
						<legend><?php echo __('User search subhead') ?></legend>
						<div class="infldset">
							<p><?php echo __('User search info') ?></p>
							<table class="aligntop">
								<tr>
									<th scope="row"><?php echo __('Username label') ?></th>
									<td><input type="text" name="form[username]" size="25" maxlength="25" tabindex="2" /></td>
								</tr>
								<tr>
									<th scope="row"><?php echo __('E-mail address label') ?></th>
									<td><input type="text" name="form[email]" size="30" maxlength="80" tabindex="3" /></td>
								</tr>
								<tr>
									<th scope="row"><?php echo __('Title label') ?></th>
									<td><input type="text" name="form[title]" size="30" maxlength="50" tabindex="4" /></td>
								</tr>
								<tr>
									<th scope="row"><?php echo __('Real name label') ?></th>
									<td><input type="text" name="form[realname]" size="30" maxlength="40" tabindex="5" /></td>
								</tr>
								<tr>
									<th scope="row"><?php echo __('Website label') ?></th>
									<td><input type="text" name="form[url]" size="35" maxlength="100" tabindex="6" /></td>
								</tr>
								<tr>
									<th scope="row"><?php echo __('Jabber label') ?></th>
									<td><input type="text" name="form[jabber]" size="30" maxlength="75" tabindex="7" /></td>
								</tr>
								<tr>
									<th scope="row"><?php echo __('ICQ label') ?></th>
									<td><input type="text" name="form[icq]" size="12" maxlength="12" tabindex="8" /></td>
								</tr>
								<tr>
									<th scope="row"><?php echo __('MSN label') ?></th>
									<td><input type="text" name="form[msn]" size="30" maxlength="50" tabindex="9" /></td>
								</tr>
								<tr>
									<th scope="row"><?php echo __('AOL label') ?></th>
									<td><input type="text" name="form[aim]" size="20" maxlength="20" tabindex="10" /></td>
								</tr>
								<tr>
									<th scope="row"><?php echo __('Yahoo label') ?></th>
									<td><input type="text" name="form[yahoo]" size="20" maxlength="20" tabindex="11" /></td>
								</tr>
								<tr>
									<th scope="row"><?php echo __('Location label') ?></th>
									<td><input type="text" name="form[location]" size="30" maxlength="30" tabindex="12" /></td>
								</tr>
								<tr>
									<th scope="row"><?php echo __('Signature label') ?></th>
									<td><input type="text" name="form[signature]" size="35" maxlength="512" tabindex="13" /></td>
								</tr>
								<tr>
									<th scope="row"><?php echo __('Admin note label') ?></th>
									<td><input type="text" name="form[admin_note]" size="30" maxlength="30" tabindex="14" /></td>
								</tr>
								<tr>
									<th scope="row"><?php echo __('Posts more than label') ?></th>
									<td><input type="text" name="posts_greater" size="5" maxlength="8" tabindex="15" /></td>
								</tr>
								<tr>
									<th scope="row"><?php echo __('Posts less than label') ?></th>
									<td><input type="text" name="posts_less" size="5" maxlength="8" tabindex="16" /></td>
								</tr>
								<tr>
									<th scope="row"><?php echo __('Last post after label') ?></th>
									<td><input type="text" name="last_post_after" size="24" maxlength="19" tabindex="17" />
									<span><?php echo __('Date help') ?></span></td>
								</tr>
								<tr>
									<th scope="row"><?php echo __('Last post before label') ?></th>
									<td><input type="text" name="last_post_before" size="24" maxlength="19" tabindex="18" />
									<span><?php echo __('Date help') ?></span></td>
								</tr>
								<tr>
									<th scope="row"><?php echo __('Last visit after label') ?></th>
									<td><input type="text" name="last_visit_after" size="24" maxlength="19" tabindex="17" />
									<span><?php echo __('Date help') ?></span></td>
								</tr>
								<tr>
									<th scope="row"><?php echo __('Last visit before label') ?></th>
									<td><input type="text" name="last_visit_before" size="24" maxlength="19" tabindex="18" />
									<span><?php echo __('Date help') ?></span></td>
								</tr>
								<tr>
									<th scope="row"><?php echo __('Registered after label') ?></th>
									<td><input type="text" name="registered_after" size="24" maxlength="19" tabindex="19" />
									<span><?php echo __('Date help') ?></span></td>
								</tr>
								<tr>
									<th scope="row"><?php echo __('Registered before label') ?></th>
									<td><input type="text" name="registered_before" size="24" maxlength="19" tabindex="20" />
									<span><?php echo __('Date help') ?></span></td>
								</tr>
								<tr>
									<th scope="row"><?php echo __('Order by label') ?></th>
									<td>
										<select name="order_by" tabindex="21">
											<option value="username" selected="selected"><?php echo __('Order by username') ?></option>
											<option value="email"><?php echo __('Order by e-mail') ?></option>
											<option value="num_posts"><?php echo __('Order by posts') ?></option>
											<option value="last_post"><?php echo __('Order by last post') ?></option>
											<option value="last_visit"><?php echo __('Order by last visit') ?></option>
											<option value="registered"><?php echo __('Order by registered') ?></option>
										</select>&#160;&#160;&#160;<select name="direction" tabindex="22">
											<option value="ASC" selected="selected"><?php echo __('Ascending') ?></option>
											<option value="DESC"><?php echo __('Descending') ?></option>
										</select>
									</td>
								</tr>
								<tr>
									<th scope="row"><?php echo __('User group label') ?></th>
									<td>
										<select name="user_group" tabindex="23">
											<option value="-1" selected="selected"><?php echo __('All groups') ?></option>
											<option value="0"><?php echo __('Unverified users') ?></option>
											<?php echo $group_list; ?>
										</select>
									</td>
								</tr>
							</table>
						</div>
					</fieldset>
				</div>
				<p class="submitend"><input type="submit" name="find_user" value="<?php echo __('Submit search') ?>" tabindex="25" /></p>
			</form>
		</div>

		<h2 class="block2"><span><?php echo __('IP search head') ?></span></h2>
		<div class="box">
			<form method="get" action="<?php echo get_link('admin/users/') ?>">
				<div class="inform">
					<fieldset>
						<legend><?php echo __('IP search subhead') ?></legend>
						<div class="infldset">
							<table class="aligntop">
								<tr>
									<th scope="row"><?php echo __('IP address label') ?><div><input type="submit" value="<?php echo __('Find IP address') ?>" tabindex="26" /></div></th>
									<td><input type="text" name="show_users" size="18" maxlength="15" tabindex="24" />
									<span><?php echo __('IP address help') ?></span></td>
								</tr>
							</table>
						</div>
					</fieldset>
				</div>
			</form>
		</div>
	</div>
	<div class="clearer"></div>
</div>