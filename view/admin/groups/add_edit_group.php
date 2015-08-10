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
	<h2><span><?php echo $lang_admin_groups['Group settings head'] ?></span></h2>
	<div class="box">
		<form id="groups2" method="post" action="" onsubmit="return process_form(this)">
			<input type="hidden" name="<?php echo $csrf_key; ?>" value="<?php echo $csrf_token; ?>">
			<p class="submittop"><input type="submit" name="add_edit_group" value="<?php echo $lang_admin_common['Save'] ?>" /></p>
			<div class="inform">
				<input type="hidden" name="mode" value="<?php echo $group['mode'] ?>" />
				<?php if ($group['mode'] == 'edit'): ?>					<input type="hidden" name="group_id" value="<?php echo $id ?>" />
				<?php endif; ?><?php if ($group['mode'] == 'add'): ?>					<input type="hidden" name="base_group" value="<?php echo $group['base_group'] ?>" />
				<?php endif; ?>					<fieldset>
					<legend><?php echo $lang_admin_groups['Group settings subhead'] ?></legend>
					<div class="infldset">
						<p><?php echo $lang_admin_groups['Group settings info'] ?></p>
						<table class="aligntop">
							<tr>
								<th scope="row"><?php echo $lang_admin_groups['Group title label'] ?></th>
								<td>
									<input type="text" name="req_title" size="25" maxlength="50" value="<?php if ($group['mode'] == 'edit') {
										echo feather_escape($group['info']['g_title']);
									} ?>" tabindex="1" />
								</td>
							</tr>
							<tr>
								<th scope="row"><?php echo $lang_admin_groups['User title label'] ?></th>
								<td>
									<input type="text" name="user_title" size="25" maxlength="50" value="<?php echo feather_escape($group['info']['g_user_title']) ?>" tabindex="2" />
									<span><?php printf($lang_admin_groups['User title help'], ($group['info']['g_id'] != FEATHER_GUEST ? $lang_common['Member'] : $lang_common['Guest'])) ?></span>
								</td>
							</tr>
							<?php if ($group['info']['g_id'] != FEATHER_ADMIN): if ($group['info']['g_id'] != FEATHER_GUEST): ?>								<tr>
								<th scope="row"><?php echo $lang_admin_groups['Promote users label'] ?></th>
								<td>
									<select name="promote_next_group" tabindex="3">
										<option value="0"><?php echo $lang_admin_groups['Disable promotion'] ?></option>
										<?php echo $group_list ?>
									</select>
									<input type="text" name="promote_min_posts" size="5" maxlength="10" value="<?php echo feather_escape($group['info']['g_promote_min_posts']) ?>" tabindex="4" />
									<span><?php printf($lang_admin_groups['Promote users help'], $lang_admin_groups['Disable promotion']) ?></span>
								</td>
							</tr>
								<?php if ($group['mode'] != 'edit' || $feather_config['o_default_user_group'] != $group['info']['g_id']): ?>								<tr>
									<th scope="row"> <?php echo $lang_admin_groups['Mod privileges label'] ?></th>
									<td>
										<label class="conl"><input type="radio" name="moderator" value="1"<?php if ($group['info']['g_moderator'] == '1') {
												echo ' checked="checked"';
											} ?> tabindex="5" />&#160;<strong><?php echo $lang_admin_common['Yes'] ?></strong></label>
										<label class="conl"><input type="radio" name="moderator" value="0"<?php if ($group['info']['g_moderator'] == '0') {
												echo ' checked="checked"';
											} ?> tabindex="6" />&#160;<strong><?php echo $lang_admin_common['No'] ?></strong></label>
										<span class="clearb"><?php echo $lang_admin_groups['Mod privileges help'] ?></span>
									</td>
								</tr>
									<tr>
										<th scope="row"><?php echo $lang_admin_groups['Edit profile label'] ?></th>
										<td>
											<label class="conl"><input type="radio" name="mod_edit_users" value="1"<?php if ($group['info']['g_mod_edit_users'] == '1') {
													echo ' checked="checked"';
												} ?> tabindex="7" />&#160;<strong><?php echo $lang_admin_common['Yes'] ?></strong></label>
											<label class="conl"><input type="radio" name="mod_edit_users" value="0"<?php if ($group['info']['g_mod_edit_users'] == '0') {
													echo ' checked="checked"';
												} ?> tabindex="8" />&#160;<strong><?php echo $lang_admin_common['No'] ?></strong></label>
											<span class="clearb"><?php echo $lang_admin_groups['Edit profile help'] ?></span>
										</td>
									</tr>
									<tr>
										<th scope="row"><?php echo $lang_admin_groups['Rename users label'] ?></th>
										<td>
											<label class="conl"><input type="radio" name="mod_rename_users" value="1"<?php if ($group['info']['g_mod_rename_users'] == '1') {
													echo ' checked="checked"';
												} ?> tabindex="9" />&#160;<strong><?php echo $lang_admin_common['Yes'] ?></strong></label>
											<label class="conl"><input type="radio" name="mod_rename_users" value="0"<?php if ($group['info']['g_mod_rename_users'] == '0') {
													echo ' checked="checked"';
												} ?> tabindex="10" />&#160;<strong><?php echo $lang_admin_common['No'] ?></strong></label>
											<span class="clearb"><?php echo $lang_admin_groups['Rename users help'] ?></span>
										</td>
									</tr>
									<tr>
										<th scope="row"><?php echo $lang_admin_groups['Change passwords label'] ?></th>
										<td>
											<label class="conl"><input type="radio" name="mod_change_passwords" value="1"<?php if ($group['info']['g_mod_change_passwords'] == '1') {
													echo ' checked="checked"';
												} ?> tabindex="11" />&#160;<strong><?php echo $lang_admin_common['Yes'] ?></strong></label>
											<label class="conl"><input type="radio" name="mod_change_passwords" value="0"<?php if ($group['info']['g_mod_change_passwords'] == '0') {
													echo ' checked="checked"';
												} ?> tabindex="12" />&#160;<strong><?php echo $lang_admin_common['No'] ?></strong></label>
											<span class="clearb"><?php echo $lang_admin_groups['Change passwords help'] ?></span>
										</td>
									</tr>
									<tr>
										<th scope="row"><?php echo $lang_admin_groups['Mod promote users label'] ?></th>
										<td>
											<label class="conl"><input type="radio" name="mod_promote_users" value="1"<?php if ($group['info']['g_mod_promote_users'] == '1') {
													echo ' checked="checked"';
												} ?> tabindex="13" />&#160;<strong><?php echo $lang_admin_common['Yes'] ?></strong></label>
											<label class="conl"><input type="radio" name="mod_promote_users" value="0"<?php if ($group['info']['g_mod_promote_users'] == '0') {
													echo ' checked="checked"';
												} ?> tabindex="14" />&#160;<strong><?php echo $lang_admin_common['No'] ?></strong></label>
											<span class="clearb"><?php echo $lang_admin_groups['Mod promote users help'] ?></span>
										</td>
									</tr>
									<tr>
										<th scope="row"><?php echo $lang_admin_groups['Ban users label'] ?></th>
										<td>
											<label class="conl"><input type="radio" name="mod_ban_users" value="1"<?php if ($group['info']['g_mod_ban_users'] == '1') {
													echo ' checked="checked"';
												} ?> tabindex="15" />&#160;<strong><?php echo $lang_admin_common['Yes'] ?></strong></label>
											<label class="conl"><input type="radio" name="mod_ban_users" value="0"<?php if ($group['info']['g_mod_ban_users'] == '0') {
													echo ' checked="checked"';
												} ?> tabindex="16" />&#160;<strong><?php echo $lang_admin_common['No'] ?></strong></label>
											<span class="clearb"><?php echo $lang_admin_groups['Ban users help'] ?></span>
										</td>
									</tr>
								<?php endif; endif; ?>								<tr>
								<th scope="row"><?php echo $lang_admin_groups['Read board label'] ?></th>
								<td>
									<label class="conl"><input type="radio" name="read_board" value="1"<?php if ($group['info']['g_read_board'] == '1') {
											echo ' checked="checked"';
										} ?> tabindex="17" />&#160;<strong><?php echo $lang_admin_common['Yes'] ?></strong></label>
									<label class="conl"><input type="radio" name="read_board" value="0"<?php if ($group['info']['g_read_board'] == '0') {
											echo ' checked="checked"';
										} ?> tabindex="18" />&#160;<strong><?php echo $lang_admin_common['No'] ?></strong></label>
									<span class="clearb"><?php echo $lang_admin_groups['Read board help'] ?></span>
								</td>
							</tr>
								<tr>
									<th scope="row"><?php echo $lang_admin_groups['View user info label'] ?></th>
									<td>
										<label class="conl"><input type="radio" name="view_users" value="1"<?php if ($group['info']['g_view_users'] == '1') {
												echo ' checked="checked"';
											} ?> tabindex="19" />&#160;<strong><?php echo $lang_admin_common['Yes'] ?></strong></label>
										<label class="conl"><input type="radio" name="view_users" value="0"<?php if ($group['info']['g_view_users'] == '0') {
												echo ' checked="checked"';
											} ?> tabindex="20" />&#160;<strong><?php echo $lang_admin_common['No'] ?></strong></label>
										<span class="clearb"><?php echo $lang_admin_groups['View user info help'] ?></span>
									</td>
								</tr>
								<tr>
									<th scope="row"><?php echo $lang_admin_groups['Post replies label'] ?></th>
									<td>
										<label class="conl"><input type="radio" name="post_replies" value="1"<?php if ($group['info']['g_post_replies'] == '1') {
												echo ' checked="checked"';
											} ?> tabindex="21" />&#160;<strong><?php echo $lang_admin_common['Yes'] ?></strong></label>
										<label class="conl"><input type="radio" name="post_replies" value="0"<?php if ($group['info']['g_post_replies'] == '0') {
												echo ' checked="checked"';
											} ?> tabindex="22" />&#160;<strong><?php echo $lang_admin_common['No'] ?></strong></label>
										<span class="clearb"><?php echo $lang_admin_groups['Post replies help'] ?></span>
									</td>
								</tr>
								<tr>
									<th scope="row"><?php echo $lang_admin_groups['Post topics label'] ?></th>
									<td>
										<label class="conl"><input type="radio" name="post_topics" value="1"<?php if ($group['info']['g_post_topics'] == '1') {
												echo ' checked="checked"';
											} ?> tabindex="23" />&#160;<strong><?php echo $lang_admin_common['Yes'] ?></strong></label>
										<label class="conl"><input type="radio" name="post_topics" value="0"<?php if ($group['info']['g_post_topics'] == '0') {
												echo ' checked="checked"';
											} ?> tabindex="24" />&#160;<strong><?php echo $lang_admin_common['No'] ?></strong></label>
										<span class="clearb"><?php echo $lang_admin_groups['Post topics help'] ?></span>
									</td>
								</tr>
								<?php if ($group['info']['g_id'] != FEATHER_GUEST): ?>								<tr>
									<th scope="row"><?php echo $lang_admin_groups['Edit posts label'] ?></th>
									<td>
										<label class="conl"><input type="radio" name="edit_posts" value="1"<?php if ($group['info']['g_edit_posts'] == '1') {
												echo ' checked="checked"';
											} ?> tabindex="25" />&#160;<strong><?php echo $lang_admin_common['Yes'] ?></strong></label>
										<label class="conl"><input type="radio" name="edit_posts" value="0"<?php if ($group['info']['g_edit_posts'] == '0') {
												echo ' checked="checked"';
											} ?> tabindex="26" />&#160;<strong><?php echo $lang_admin_common['No'] ?></strong></label>
										<span class="clearb"><?php echo $lang_admin_groups['Edit posts help'] ?></span>
									</td>
								</tr>
									<tr>
										<th scope="row"><?php echo $lang_admin_groups['Delete posts label'] ?></th>
										<td>
											<label class="conl"><input type="radio" name="delete_posts" value="1"<?php if ($group['info']['g_delete_posts'] == '1') {
													echo ' checked="checked"';
												} ?> tabindex="27" />&#160;<strong><?php echo $lang_admin_common['Yes'] ?></strong></label>
											<label class="conl"><input type="radio" name="delete_posts" value="0"<?php if ($group['info']['g_delete_posts'] == '0') {
													echo ' checked="checked"';
												} ?> tabindex="28" />&#160;<strong><?php echo $lang_admin_common['No'] ?></strong></label>
											<span class="clearb"><?php echo $lang_admin_groups['Delete posts help'] ?></span>
										</td>
									</tr>
									<tr>
										<th scope="row"><?php echo $lang_admin_groups['Delete topics label'] ?></th>
										<td>
											<label class="conl"><input type="radio" name="delete_topics" value="1"<?php if ($group['info']['g_delete_topics'] == '1') {
													echo ' checked="checked"';
												} ?> tabindex="29" />&#160;<strong><?php echo $lang_admin_common['Yes'] ?></strong></label>
											<label class="conl"><input type="radio" name="delete_topics" value="0"<?php if ($group['info']['g_delete_topics'] == '0') {
													echo ' checked="checked"';
												} ?> tabindex="30" />&#160;<strong><?php echo $lang_admin_common['No'] ?></strong></label>
											<span class="clearb"><?php echo $lang_admin_groups['Delete topics help'] ?></span>
										</td>
									</tr>
								<?php endif; ?>								<tr>
									<th scope="row"><?php echo $lang_admin_groups['Post links label'] ?></th>
									<td>
										<label class="conl"><input type="radio" name="post_links" value="1"<?php if ($group['info']['g_post_links'] == '1') {
												echo ' checked="checked"';
											} ?> tabindex="31" />&#160;<strong><?php echo $lang_admin_common['Yes'] ?></strong></label>
										<label class="conl"><input type="radio" name="post_links" value="0"<?php if ($group['info']['g_post_links'] == '0') {
												echo ' checked="checked"';
											} ?> tabindex="32" />&#160;<strong><?php echo $lang_admin_common['No'] ?></strong></label>
										<span class="clearb"><?php echo $lang_admin_groups['Post links help'] ?></span>
									</td>
								</tr>
								<?php if ($group['info']['g_id'] != FEATHER_GUEST): ?>								<tr>
									<th scope="row"><?php echo $lang_admin_groups['Set own title label'] ?></th>
									<td>
										<label class="conl"><input type="radio" name="set_title" value="1"<?php if ($group['info']['g_set_title'] == '1') {
												echo ' checked="checked"';
											} ?> tabindex="33" />&#160;<strong><?php echo $lang_admin_common['Yes'] ?></strong></label>
										<label class="conl"><input type="radio" name="set_title" value="0"<?php if ($group['info']['g_set_title'] == '0') {
												echo ' checked="checked"';
											} ?> tabindex="34" />&#160;<strong><?php echo $lang_admin_common['No'] ?></strong></label>
										<span class="clearb"><?php echo $lang_admin_groups['Set own title help'] ?></span>
									</td>
								</tr>
								<?php endif; ?>								<tr>
									<th scope="row"><?php echo $lang_admin_groups['User search label'] ?></th>
									<td>
										<label class="conl"><input type="radio" name="search" value="1"<?php if ($group['info']['g_search'] == '1') {
												echo ' checked="checked"';
											} ?> tabindex="35" />&#160;<strong><?php echo $lang_admin_common['Yes'] ?></strong></label>
										<label class="conl"><input type="radio" name="search" value="0"<?php if ($group['info']['g_search'] == '0') {
												echo ' checked="checked"';
											} ?> tabindex="36" />&#160;<strong><?php echo $lang_admin_common['No'] ?></strong></label>
										<span class="clearb"><?php echo $lang_admin_groups['User search help'] ?></span>
									</td>
								</tr>
								<tr>
									<th scope="row"><?php echo $lang_admin_groups['User list search label'] ?></th>
									<td>
										<label class="conl"><input type="radio" name="search_users" value="1"<?php if ($group['info']['g_search_users'] == '1') {
												echo ' checked="checked"';
											} ?> tabindex="37" />&#160;<strong><?php echo $lang_admin_common['Yes'] ?></strong></label>
										<label class="conl"><input type="radio" name="search_users" value="0"<?php if ($group['info']['g_search_users'] == '0') {
												echo ' checked="checked"';
											} ?> tabindex="38" />&#160;<strong><?php echo $lang_admin_common['No'] ?></strong></label>
										<span class="clearb"><?php echo $lang_admin_groups['User list search help'] ?></span>
									</td>
								</tr>
								<?php if ($group['info']['g_id'] != FEATHER_GUEST): ?>								<tr>
									<th scope="row"><?php echo $lang_admin_groups['Send e-mails label'] ?></th>
									<td>
										<label class="conl"><input type="radio" name="send_email" value="1"<?php if ($group['info']['g_send_email'] == '1') {
												echo ' checked="checked"';
											} ?> tabindex="39" />&#160;<strong><?php echo $lang_admin_common['Yes'] ?></strong></label>
										<label class="conl"><input type="radio" name="send_email" value="0"<?php if ($group['info']['g_send_email'] == '0') {
												echo ' checked="checked"';
											} ?> tabindex="40" />&#160;<strong><?php echo $lang_admin_common['No'] ?></strong></label>
										<span class="clearb"><?php echo $lang_admin_groups['Send e-mails help'] ?></span>
									</td>
								</tr>
								<?php endif; ?>								<tr>
									<th scope="row"><?php echo $lang_admin_groups['Post flood label'] ?></th>
									<td>
										<input type="text" name="post_flood" size="5" maxlength="4" value="<?php echo $group['info']['g_post_flood'] ?>" tabindex="41" />
										<span><?php echo $lang_admin_groups['Post flood help'] ?></span>
									</td>
								</tr>
								<tr>
									<th scope="row"><?php echo $lang_admin_groups['Search flood label'] ?></th>
									<td>
										<input type="text" name="search_flood" size="5" maxlength="4" value="<?php echo $group['info']['g_search_flood'] ?>" tabindex="42" />
										<span><?php echo $lang_admin_groups['Search flood help'] ?></span>
									</td>
								</tr>
								<?php if ($group['info']['g_id'] != FEATHER_GUEST): ?>								<tr>
									<th scope="row"><?php echo $lang_admin_groups['E-mail flood label'] ?></th>
									<td>
										<input type="text" name="email_flood" size="5" maxlength="4" value="<?php echo $group['info']['g_email_flood'] ?>" tabindex="43" />
										<span><?php echo $lang_admin_groups['E-mail flood help'] ?></span>
									</td>
								</tr>
									<tr>
										<th scope="row"><?php echo $lang_admin_groups['Report flood label'] ?></th>
										<td>
											<input type="text" name="report_flood" size="5" maxlength="4" value="<?php echo $group['info']['g_report_flood'] ?>" tabindex="44" />
											<span><?php echo $lang_admin_groups['Report flood help'] ?></span>
										</td>
									</tr>
								<?php endif; endif; ?>							</table>
						<?php if ($group['info']['g_moderator'] == '1'): ?>							<p class="warntext"><?php echo $lang_admin_groups['Moderator info'] ?></p>
						<?php endif; ?>						</div>
				</fieldset>
			</div>
			<p class="submitend"><input type="submit" name="add_edit_group" value="<?php echo $lang_admin_common['Save'] ?>" tabindex="45" /></p>
		</form>
	</div>
</div>
<div class="clearer"></div>
</div>