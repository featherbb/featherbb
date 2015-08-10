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
		<h2><span><?php echo $lang_admin_permissions['Permissions head'] ?></span></h2>
		<div class="box">
			<form method="post" action="<?php echo get_link('admin/permissions/') ?>">
				<input type="hidden" name="<?php echo $csrf_key; ?>" value="<?php echo $csrf_token; ?>">
				<p class="submittop"><input type="submit" name="save" value="<?php echo $lang_admin_common['Save changes'] ?>" /></p>
				<div class="inform">
					<input type="hidden" name="form_sent" value="1" />
					<fieldset>
						<legend><?php echo $lang_admin_permissions['Posting subhead'] ?></legend>
						<div class="infldset">
							<table class="aligntop">
								<tr>
									<th scope="row"><?php echo $lang_admin_permissions['BBCode label'] ?></th>
									<td>
										<label class="conl"><input type="radio" name="form[message_bbcode]" value="1"<?php if ($feather_config['p_message_bbcode'] == '1') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?php echo $lang_admin_common['Yes'] ?></strong></label>
										<label class="conl"><input type="radio" name="form[message_bbcode]" value="0"<?php if ($feather_config['p_message_bbcode'] == '0') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?php echo $lang_admin_common['No'] ?></strong></label>
										<span class="clearb"><?php echo $lang_admin_permissions['BBCode help'] ?></span>
									</td>
								</tr>
								<tr>
									<th scope="row"><?php echo $lang_admin_permissions['Image tag label'] ?></th>
									<td>
										<label class="conl"><input type="radio" name="form[message_img_tag]" value="1"<?php if ($feather_config['p_message_img_tag'] == '1') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?php echo $lang_admin_common['Yes'] ?></strong></label>
										<label class="conl"><input type="radio" name="form[message_img_tag]" value="0"<?php if ($feather_config['p_message_img_tag'] == '0') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?php echo $lang_admin_common['No'] ?></strong></label>
										<span class="clearb"><?php echo $lang_admin_permissions['Image tag help'] ?></span>
									</td>
								</tr>
								<tr>
									<th scope="row"><?php echo $lang_admin_permissions['All caps message label'] ?></th>
									<td>
										<label class="conl"><input type="radio" name="form[message_all_caps]" value="1"<?php if ($feather_config['p_message_all_caps'] == '1') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?php echo $lang_admin_common['Yes'] ?></strong></label>
										<label class="conl"><input type="radio" name="form[message_all_caps]" value="0"<?php if ($feather_config['p_message_all_caps'] == '0') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?php echo $lang_admin_common['No'] ?></strong></label>
										<span class="clearb"><?php echo $lang_admin_permissions['All caps message help'] ?></span>
									</td>
								</tr>
								<tr>
									<th scope="row"><?php echo $lang_admin_permissions['All caps subject label'] ?></th>
									<td>
										<label class="conl"><input type="radio" name="form[subject_all_caps]" value="1"<?php if ($feather_config['p_subject_all_caps'] == '1') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?php echo $lang_admin_common['Yes'] ?></strong></label>
										<label class="conl"><input type="radio" name="form[subject_all_caps]" value="0"<?php if ($feather_config['p_subject_all_caps'] == '0') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?php echo $lang_admin_common['No'] ?></strong></label>
										<span class="clearb"><?php echo $lang_admin_permissions['All caps subject help'] ?></span>
									</td>
								</tr>
								<tr>
									<th scope="row"><?php echo $lang_admin_permissions['Require e-mail label'] ?></th>
									<td>
										<label class="conl"><input type="radio" name="form[force_guest_email]" value="1"<?php if ($feather_config['p_force_guest_email'] == '1') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?php echo $lang_admin_common['Yes'] ?></strong></label>
										<label class="conl"><input type="radio" name="form[force_guest_email]" value="0"<?php if ($feather_config['p_force_guest_email'] == '0') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?php echo $lang_admin_common['No'] ?></strong></label>
										<span class="clearb"><?php echo $lang_admin_permissions['Require e-mail help'] ?></span>
									</td>
								</tr>
							</table>
						</div>
					</fieldset>
				</div>
				<div class="inform">
					<fieldset>
						<legend><?php echo $lang_admin_permissions['Signatures subhead'] ?></legend>
						<div class="infldset">
							<table class="aligntop">
								<tr>
									<th scope="row"><?php echo $lang_admin_permissions['BBCode sigs label'] ?></th>
									<td>
										<label class="conl"><input type="radio" name="form[sig_bbcode]" value="1"<?php if ($feather_config['p_sig_bbcode'] == '1') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?php echo $lang_admin_common['Yes'] ?></strong></label>
										<label class="conl"><input type="radio" name="form[sig_bbcode]" value="0"<?php if ($feather_config['p_sig_bbcode'] == '0') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?php echo $lang_admin_common['No'] ?></strong></label>
										<span class="clearb"><?php echo $lang_admin_permissions['BBCode sigs help'] ?></span>
									</td>
								</tr>
								<tr>
									<th scope="row"><?php echo $lang_admin_permissions['Image tag sigs label'] ?></th>
									<td>
										<label class="conl"><input type="radio" name="form[sig_img_tag]" value="1"<?php if ($feather_config['p_sig_img_tag'] == '1') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?php echo $lang_admin_common['Yes'] ?></strong></label>
										<label class="conl"><input type="radio" name="form[sig_img_tag]" value="0"<?php if ($feather_config['p_sig_img_tag'] == '0') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?php echo $lang_admin_common['No'] ?></strong></label>
										<span class="clearb"><?php echo $lang_admin_permissions['Image tag sigs help'] ?></span>
									</td>
								</tr>
								<tr>
									<th scope="row"><?php echo $lang_admin_permissions['All caps sigs label'] ?></th>
									<td>
										<label class="conl"><input type="radio" name="form[sig_all_caps]" value="1"<?php if ($feather_config['p_sig_all_caps'] == '1') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?php echo $lang_admin_common['Yes'] ?></strong></label>
										<label class="conl"><input type="radio" name="form[sig_all_caps]" value="0"<?php if ($feather_config['p_sig_all_caps'] == '0') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?php echo $lang_admin_common['No'] ?></strong></label>
										<span class="clearb"><?php echo $lang_admin_permissions['All caps sigs help'] ?></span>
									</td>
								</tr>
								<tr>
									<th scope="row"><?php echo $lang_admin_permissions['Max sig length label'] ?></th>
									<td>
										<input type="text" name="form[sig_length]" size="5" maxlength="5" value="<?php echo $feather_config['p_sig_length'] ?>" />
										<span class="clearb"><?php echo $lang_admin_permissions['Max sig length help'] ?></span>
									</td>
								</tr>
								<tr>
									<th scope="row"><?php echo $lang_admin_permissions['Max sig lines label'] ?></th>
									<td>
										<input type="text" name="form[sig_lines]" size="3" maxlength="3" value="<?php echo $feather_config['p_sig_lines'] ?>" />
										<span class="clearb"><?php echo $lang_admin_permissions['Max sig lines help'] ?></span>
									</td>
								</tr>
							</table>
						</div>
					</fieldset>
				</div>
				<div class="inform">
					<fieldset>
						<legend><?php echo $lang_admin_permissions['Registration subhead'] ?></legend>
						<div class="infldset">
							<table class="aligntop">
								<tr>
									<th scope="row"><?php echo $lang_admin_permissions['Banned e-mail label'] ?></th>
									<td>
										<label class="conl"><input type="radio" name="form[allow_banned_email]" value="1"<?php if ($feather_config['p_allow_banned_email'] == '1') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?php echo $lang_admin_common['Yes'] ?></strong></label>
										<label class="conl"><input type="radio" name="form[allow_banned_email]" value="0"<?php if ($feather_config['p_allow_banned_email'] == '0') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?php echo $lang_admin_common['No'] ?></strong></label>
										<span class="clearb"><?php echo $lang_admin_permissions['Banned e-mail help'] ?></span>
									</td>
								</tr>
								<tr>
									<th scope="row"><?php echo $lang_admin_permissions['Duplicate e-mail label'] ?></th>
									<td>
										<label class="conl"><input type="radio" name="form[allow_dupe_email]" value="1"<?php if ($feather_config['p_allow_dupe_email'] == '1') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?php echo $lang_admin_common['Yes'] ?></strong></label>
										<label class="conl"><input type="radio" name="form[allow_dupe_email]" value="0"<?php if ($feather_config['p_allow_dupe_email'] == '0') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?php echo $lang_admin_common['No'] ?></strong></label>
										<span class="clearb"><?php echo $lang_admin_permissions['Duplicate e-mail help'] ?></span>
									</td>
								</tr>
							</table>
						</div>
					</fieldset>
				</div>
				<p class="submitend"><input type="submit" name="save" value="<?php echo $lang_admin_common['Save changes'] ?>" /></p>
			</form>
		</div>
	</div>
	<div class="clearer"></div>
</div>