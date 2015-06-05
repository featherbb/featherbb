<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */
 
// Make sure no one attempts to run this script "directly"
if (!defined('PUN'))
	exit;

// View of the actions: change a password, a mail, an avatar or delete an user
if ($action == 'change_pass'):
?>
<div class="blockform">
	<h2><span><?php echo $lang_profile['Change pass'] ?></span></h2>
	<div class="box">
		<form id="change_pass" method="post" action="profile.php?action=change_pass&amp;id=<?php echo $id ?>" onsubmit="return process_form(this)">
			<div class="inform">
				<input type="hidden" name="form_sent" value="1" />
				<fieldset>
					<legend><?php echo $lang_profile['Change pass legend'] ?></legend>
					<div class="infldset">
<?php if (!$pun_user['is_admmod']): ?>						<label class="required"><strong><?php echo $lang_profile['Old pass'] ?> <span><?php echo $lang_common['Required'] ?></span></strong><br />
						<input type="password" name="req_old_password" size="16" /><br /></label>
<?php endif; ?>						<label class="conl required"><strong><?php echo $lang_profile['New pass'] ?> <span><?php echo $lang_common['Required'] ?></span></strong><br />
						<input type="password" name="req_new_password1" size="16" /><br /></label>
						<label class="conl required"><strong><?php echo $lang_profile['Confirm new pass'] ?> <span><?php echo $lang_common['Required'] ?></span></strong><br />
						<input type="password" name="req_new_password2" size="16" /><br /></label>
						<p class="clearb"><?php echo $lang_profile['Pass info'] ?></p>
					</div>
				</fieldset>
			</div>
			<p class="buttons"><input type="submit" name="update" value="<?php echo $lang_common['Submit'] ?>" /> <a href="javascript:history.go(-1)"><?php echo $lang_common['Go back'] ?></a></p>
		</form>
	</div>
</div>
<?php
elseif ($action == 'change_mail'):
?>
<div class="blockform">
	<h2><span><?php echo $lang_profile['Change email'] ?></span></h2>
	<div class="box">
		<form id="change_email" method="post" action="profile.php?action=change_email&amp;id=<?php echo $id ?>" onsubmit="return process_form(this)">
			<div class="inform">
				<fieldset>
					<legend><?php echo $lang_profile['Email legend'] ?></legend>
					<div class="infldset">
						<input type="hidden" name="form_sent" value="1" />
						<label class="required"><strong><?php echo $lang_profile['New email'] ?> <span><?php echo $lang_common['Required'] ?></span></strong><br /><input type="text" name="req_new_email" size="50" maxlength="80" /><br /></label>
						<label class="required"><strong><?php echo $lang_common['Password'] ?> <span><?php echo $lang_common['Required'] ?></span></strong><br /><input type="password" name="req_password" size="16" /><br /></label>
						<p><?php echo $lang_profile['Email instructions'] ?></p>
					</div>
				</fieldset>
			</div>
			<p class="buttons"><input type="submit" name="new_email" value="<?php echo $lang_common['Submit'] ?>" /> <a href="javascript:history.go(-1)"><?php echo $lang_common['Go back'] ?></a></p>
		</form>
	</div>
</div>
<?php
elseif ($action == 'upload_avatar' || $action == 'upload_avatar2'):
?>
<div class="blockform">
	<h2><span><?php echo $lang_profile['Upload avatar'] ?></span></h2>
	<div class="box">
		<form id="upload_avatar" method="post" enctype="multipart/form-data" action="profile.php?action=upload_avatar2&amp;id=<?php echo $id ?>" onsubmit="return process_form(this)">
			<div class="inform">
				<fieldset>
					<legend><?php echo $lang_profile['Upload avatar legend'] ?></legend>
					<div class="infldset">
						<input type="hidden" name="form_sent" value="1" />
						<input type="hidden" name="MAX_FILE_SIZE" value="<?php echo $pun_config['o_avatars_size'] ?>" />
						<label class="required"><strong><?php echo $lang_profile['File'] ?> <span><?php echo $lang_common['Required'] ?></span></strong><br /><input name="req_file" type="file" size="40" /><br /></label>
						<p><?php echo $lang_profile['Avatar desc'].' '.$pun_config['o_avatars_width'].' x '.$pun_config['o_avatars_height'].' '.$lang_profile['pixels'].' '.$lang_common['and'].' '.forum_number_format($pun_config['o_avatars_size']).' '.$lang_profile['bytes'].' ('.file_size($pun_config['o_avatars_size']).').' ?></p>
					</div>
				</fieldset>
			</div>
			<p class="buttons"><input type="submit" name="upload" value="<?php echo $lang_profile['Upload'] ?>" /> <a href="javascript:history.go(-1)"><?php echo $lang_common['Go back'] ?></a></p>
		</form>
	</div>
</div>
<?php
elseif (isset($_POST['delete_user']) || isset($_POST['delete_user_comply'])) :
?>
<div class="blockform">
	<h2><span><?php echo $lang_profile['Confirm delete user'] ?></span></h2>
	<div class="box">
		<form id="confirm_del_user" method="post" action="profile.php?id=<?php echo $id ?>">
			<div class="inform">
				<fieldset>
					<legend><?php echo $lang_profile['Confirm delete legend'] ?></legend>
					<div class="infldset">
						<p><?php echo $lang_profile['Confirmation info'].' <strong>'.pun_htmlspecialchars($username).'</strong>.' ?></p>
						<div class="rbox">
							<label><input type="checkbox" name="delete_posts" value="1" checked="checked" /><?php echo $lang_profile['Delete posts'] ?><br /></label>
						</div>
						<p class="warntext"><strong><?php echo $lang_profile['Delete warning'] ?></strong></p>
					</div>
				</fieldset>
			</div>
			<p class="buttons"><input type="submit" name="delete_user_comply" value="<?php echo $lang_profile['Delete'] ?>" /> <a href="javascript:history.go(-1)"><?php echo $lang_common['Go back'] ?></a></p>
		</form>
	</div>
</div>
<?php
endif;

// View a profile (as guest or simple user)
if ($view_profile):
?>
<div id="viewprofile" class="block">
	<h2><span><?php echo $lang_common['Profile'] ?></span></h2>
	<div class="box">
		<div class="fakeform">
			<div class="inform">
				<fieldset>
				<legend><?php echo $lang_profile['Section personal'] ?></legend>
					<div class="infldset">
						<dl>
							<?php echo implode("\n\t\t\t\t\t\t\t", $user_info['personal'])."\n" ?>
						</dl>
						<div class="clearer"></div>
					</div>
				</fieldset>
			</div>
<?php if (!empty($user_info['messaging'])): ?>			<div class="inform">
				<fieldset>
				<legend><?php echo $lang_profile['Section messaging'] ?></legend>
					<div class="infldset">
						<dl>
							<?php echo implode("\n\t\t\t\t\t\t\t", $user_info['messaging'])."\n" ?>
						</dl>
						<div class="clearer"></div>
					</div>
				</fieldset>
			</div>
<?php endif; if (!empty($user_info['personality'])): ?>			<div class="inform">
				<fieldset>
				<legend><?php echo $lang_profile['Section personality'] ?></legend>
					<div class="infldset">
						<dl>
							<?php echo implode("\n\t\t\t\t\t\t\t", $user_info['personality'])."\n" ?>
						</dl>
						<div class="clearer"></div>
					</div>
				</fieldset>
			</div>
<?php endif; ?>			<div class="inform">
				<fieldset>
				<legend><?php echo $lang_profile['User activity'] ?></legend>
					<div class="infldset">
						<dl>
							<?php echo implode("\n\t\t\t\t\t\t\t", $user_info['activity'])."\n" ?>
						</dl>
						<div class="clearer"></div>
					</div>
				</fieldset>
			</div>
		</div>
	</div>
</div>
<?php
// Edit a profile (different sections)
else:
if (!$section || $section == 'essentials') :
?>
	<div class="blockform">
		<h2><span><?php echo pun_htmlspecialchars($user['username']).' - '.$lang_profile['Section essentials'] ?></span></h2>
		<div class="box">
			<form id="profile1" method="post" action="profile.php?section=essentials&amp;id=<?php echo $id ?>" onsubmit="return process_form(this)">
				<div class="inform">
					<fieldset>
						<legend><?php echo $lang_profile['Username and pass legend'] ?></legend>
						<div class="infldset">
							<input type="hidden" name="form_sent" value="1" />
							<?php echo $user_disp['username_field'] ?>
<?php if ($pun_user['id'] == $id || $pun_user['g_id'] == PUN_ADMIN || ($user['g_moderator'] == '0' && $pun_user['g_mod_change_passwords'] == '1')): ?>							<p class="actions"><span><a href="profile.php?action=change_pass&amp;id=<?php echo $id ?>"><?php echo $lang_profile['Change pass'] ?></a></span></p>
<?php endif; ?>						</div>
					</fieldset>
				</div>
				<div class="inform">
					<fieldset>
						<legend><?php echo $lang_prof_reg['Email legend'] ?></legend>
						<div class="infldset">
							<?php echo $user_disp['email_field'] ?>
						</div>
					</fieldset>
				</div>
				<div class="inform">
					<fieldset>
						<legend><?php echo $lang_prof_reg['Localisation legend'] ?></legend>
						<div class="infldset">
							<p><?php echo $lang_prof_reg['Time zone info'] ?></p>
							<label><?php echo $lang_prof_reg['Time zone']."\n" ?>
							<br /><select name="form[timezone]">
								<option value="-12"<?php if ($user['timezone'] == -12) echo ' selected="selected"' ?>><?php echo $lang_prof_reg['UTC-12:00'] ?></option>
								<option value="-11"<?php if ($user['timezone'] == -11) echo ' selected="selected"' ?>><?php echo $lang_prof_reg['UTC-11:00'] ?></option>
								<option value="-10"<?php if ($user['timezone'] == -10) echo ' selected="selected"' ?>><?php echo $lang_prof_reg['UTC-10:00'] ?></option>
								<option value="-9.5"<?php if ($user['timezone'] == -9.5) echo ' selected="selected"' ?>><?php echo $lang_prof_reg['UTC-09:30'] ?></option>
								<option value="-9"<?php if ($user['timezone'] == -9) echo ' selected="selected"' ?>><?php echo $lang_prof_reg['UTC-09:00'] ?></option>
								<option value="-8.5"<?php if ($user['timezone'] == -8.5) echo ' selected="selected"' ?>><?php echo $lang_prof_reg['UTC-08:30'] ?></option>
								<option value="-8"<?php if ($user['timezone'] == -8) echo ' selected="selected"' ?>><?php echo $lang_prof_reg['UTC-08:00'] ?></option>
								<option value="-7"<?php if ($user['timezone'] == -7) echo ' selected="selected"' ?>><?php echo $lang_prof_reg['UTC-07:00'] ?></option>
								<option value="-6"<?php if ($user['timezone'] == -6) echo ' selected="selected"' ?>><?php echo $lang_prof_reg['UTC-06:00'] ?></option>
								<option value="-5"<?php if ($user['timezone'] == -5) echo ' selected="selected"' ?>><?php echo $lang_prof_reg['UTC-05:00'] ?></option>
								<option value="-4"<?php if ($user['timezone'] == -4) echo ' selected="selected"' ?>><?php echo $lang_prof_reg['UTC-04:00'] ?></option>
								<option value="-3.5"<?php if ($user['timezone'] == -3.5) echo ' selected="selected"' ?>><?php echo $lang_prof_reg['UTC-03:30'] ?></option>
								<option value="-3"<?php if ($user['timezone'] == -3) echo ' selected="selected"' ?>><?php echo $lang_prof_reg['UTC-03:00'] ?></option>
								<option value="-2"<?php if ($user['timezone'] == -2) echo ' selected="selected"' ?>><?php echo $lang_prof_reg['UTC-02:00'] ?></option>
								<option value="-1"<?php if ($user['timezone'] == -1) echo ' selected="selected"' ?>><?php echo $lang_prof_reg['UTC-01:00'] ?></option>
								<option value="0"<?php if ($user['timezone'] == 0) echo ' selected="selected"' ?>><?php echo $lang_prof_reg['UTC'] ?></option>
								<option value="1"<?php if ($user['timezone'] == 1) echo ' selected="selected"' ?>><?php echo $lang_prof_reg['UTC+01:00'] ?></option>
								<option value="2"<?php if ($user['timezone'] == 2) echo ' selected="selected"' ?>><?php echo $lang_prof_reg['UTC+02:00'] ?></option>
								<option value="3"<?php if ($user['timezone'] == 3) echo ' selected="selected"' ?>><?php echo $lang_prof_reg['UTC+03:00'] ?></option>
								<option value="3.5"<?php if ($user['timezone'] == 3.5) echo ' selected="selected"' ?>><?php echo $lang_prof_reg['UTC+03:30'] ?></option>
								<option value="4"<?php if ($user['timezone'] == 4) echo ' selected="selected"' ?>><?php echo $lang_prof_reg['UTC+04:00'] ?></option>
								<option value="4.5"<?php if ($user['timezone'] == 4.5) echo ' selected="selected"' ?>><?php echo $lang_prof_reg['UTC+04:30'] ?></option>
								<option value="5"<?php if ($user['timezone'] == 5) echo ' selected="selected"' ?>><?php echo $lang_prof_reg['UTC+05:00'] ?></option>
								<option value="5.5"<?php if ($user['timezone'] == 5.5) echo ' selected="selected"' ?>><?php echo $lang_prof_reg['UTC+05:30'] ?></option>
								<option value="5.75"<?php if ($user['timezone'] == 5.75) echo ' selected="selected"' ?>><?php echo $lang_prof_reg['UTC+05:45'] ?></option>
								<option value="6"<?php if ($user['timezone'] == 6) echo ' selected="selected"' ?>><?php echo $lang_prof_reg['UTC+06:00'] ?></option>
								<option value="6.5"<?php if ($user['timezone'] == 6.5) echo ' selected="selected"' ?>><?php echo $lang_prof_reg['UTC+06:30'] ?></option>
								<option value="7"<?php if ($user['timezone'] == 7) echo ' selected="selected"' ?>><?php echo $lang_prof_reg['UTC+07:00'] ?></option>
								<option value="8"<?php if ($user['timezone'] == 8) echo ' selected="selected"' ?>><?php echo $lang_prof_reg['UTC+08:00'] ?></option>
								<option value="8.75"<?php if ($user['timezone'] == 8.75) echo ' selected="selected"' ?>><?php echo $lang_prof_reg['UTC+08:45'] ?></option>
								<option value="9"<?php if ($user['timezone'] == 9) echo ' selected="selected"' ?>><?php echo $lang_prof_reg['UTC+09:00'] ?></option>
								<option value="9.5"<?php if ($user['timezone'] == 9.5) echo ' selected="selected"' ?>><?php echo $lang_prof_reg['UTC+09:30'] ?></option>
								<option value="10"<?php if ($user['timezone'] == 10) echo ' selected="selected"' ?>><?php echo $lang_prof_reg['UTC+10:00'] ?></option>
								<option value="10.5"<?php if ($user['timezone'] == 10.5) echo ' selected="selected"' ?>><?php echo $lang_prof_reg['UTC+10:30'] ?></option>
								<option value="11"<?php if ($user['timezone'] == 11) echo ' selected="selected"' ?>><?php echo $lang_prof_reg['UTC+11:00'] ?></option>
								<option value="11.5"<?php if ($user['timezone'] == 11.5) echo ' selected="selected"' ?>><?php echo $lang_prof_reg['UTC+11:30'] ?></option>
								<option value="12"<?php if ($user['timezone'] == 12) echo ' selected="selected"' ?>><?php echo $lang_prof_reg['UTC+12:00'] ?></option>
								<option value="12.75"<?php if ($user['timezone'] == 12.75) echo ' selected="selected"' ?>><?php echo $lang_prof_reg['UTC+12:45'] ?></option>
								<option value="13"<?php if ($user['timezone'] == 13) echo ' selected="selected"' ?>><?php echo $lang_prof_reg['UTC+13:00'] ?></option>
								<option value="14"<?php if ($user['timezone'] == 14) echo ' selected="selected"' ?>><?php echo $lang_prof_reg['UTC+14:00'] ?></option>
							</select>
							<br /></label>
							<div class="rbox">
								<label><input type="checkbox" name="form[dst]" value="1"<?php if ($user['dst'] == '1') echo ' checked="checked"' ?> /><?php echo $lang_prof_reg['DST'] ?><br /></label>
							</div>
							<label><?php echo $lang_prof_reg['Time format'] ?>

							<br /><select name="form[time_format]">
<?php
								foreach (array_unique($forum_time_formats) as $key => $time_format)
								{
									echo "\t\t\t\t\t\t\t\t".'<option value="'.$key.'"';
									if ($user['time_format'] == $key)
										echo ' selected="selected"';
									echo '>'. format_time(time(), false, null, $time_format, true, true);
									if ($key == 0)
										echo ' ('.$lang_prof_reg['Default'].')';
									echo "</option>\n";
								}
								?>
							</select>
							<br /></label>
							<label><?php echo $lang_prof_reg['Date format'] ?>

							<br /><select name="form[date_format]">
<?php
								foreach (array_unique($forum_date_formats) as $key => $date_format)
								{
									echo "\t\t\t\t\t\t\t\t".'<option value="'.$key.'"';
									if ($user['date_format'] == $key)
										echo ' selected="selected"';
									echo '>'. format_time(time(), true, $date_format, null, false, true);
									if ($key == 0)
										echo ' ('.$lang_prof_reg['Default'].')';
									echo "</option>\n";
								}
								?>
							</select>
							<br /></label>

<?php

		$languages = forum_list_langs();

		// Only display the language selection box if there's more than one language available
		if (count($languages) > 1)
		{

?>
							<label><?php echo $lang_prof_reg['Language'] ?>
							<br /><select name="form[language]">
<?php

			foreach ($languages as $temp)
			{
				if ($user['language'] == $temp)
					echo "\t\t\t\t\t\t\t\t".'<option value="'.$temp.'" selected="selected">'.$temp.'</option>'."\n";
				else
					echo "\t\t\t\t\t\t\t\t".'<option value="'.$temp.'">'.$temp.'</option>'."\n";
			}

?>
							</select>
							<br /></label>
<?php

		}

?>
						</div>
					</fieldset>
				</div>
				<div class="inform">
					<fieldset>
						<legend><?php echo $lang_profile['User activity'] ?></legend>
						<div class="infldset">
							<p><?php printf($lang_profile['Registered info'], format_time($user['registered'], true).(($pun_user['is_admmod']) ? ' (<a href="moderate.php?get_host='.pun_htmlspecialchars($user['registration_ip']).'">'.pun_htmlspecialchars($user['registration_ip']).'</a>)' : '')) ?></p>
							<p><?php printf($lang_profile['Last post info'], format_time($user['last_post'])) ?></p>
							<p><?php printf($lang_profile['Last visit info'], format_time($user['last_visit'])) ?></p>
							<?php echo $user_disp['posts_field'] ?>
<?php if ($pun_user['is_admmod']): ?>							<label><?php echo $lang_profile['Admin note'] ?><br />
							<input id="admin_note" type="text" name="admin_note" value="<?php echo pun_htmlspecialchars($user['admin_note']) ?>" size="30" maxlength="30" /><br /></label>
<?php endif; ?>						</div>
					</fieldset>
				</div>
				<p class="buttons"><input type="submit" name="update" value="<?php echo $lang_common['Submit'] ?>" /> <?php echo $lang_profile['Instructions'] ?></p>
			</form>
		</div>
	</div>
<?php
elseif ($section == 'personal'):
?>
	<div class="blockform">
		<h2><span><?php echo pun_htmlspecialchars($user['username']).' - '.$lang_profile['Section personal'] ?></span></h2>
		<div class="box">
			<form id="profile2" method="post" action="profile.php?section=personal&amp;id=<?php echo $id ?>">
				<div class="inform">
					<fieldset>
						<legend><?php echo $lang_profile['Personal details legend'] ?></legend>
						<div class="infldset">
							<input type="hidden" name="form_sent" value="1" />
							<label><?php echo $lang_profile['Realname'] ?><br /><input type="text" name="form[realname]" value="<?php echo pun_htmlspecialchars($user['realname']) ?>" size="40" maxlength="40" /><br /></label>
<?php if (isset($title_field)): ?>							<?php echo $title_field ?>
<?php endif; ?>							<label><?php echo $lang_profile['Location'] ?><br /><input type="text" name="form[location]" value="<?php echo pun_htmlspecialchars($user['location']) ?>" size="30" maxlength="30" /><br /></label>
<?php if ($pun_user['g_post_links'] == '1' || $pun_user['g_id'] == PUN_ADMIN) : ?>							<label><?php echo $lang_profile['Website'] ?><br /><input type="text" name="form[url]" value="<?php echo pun_htmlspecialchars($user['url']) ?>" size="50" maxlength="80" /><br /></label>
<?php endif; ?>
						</div>
					</fieldset>
				</div>
				<p class="buttons"><input type="submit" name="update" value="<?php echo $lang_common['Submit'] ?>" /> <?php echo $lang_profile['Instructions'] ?></p>
			</form>
		</div>
	</div>
<?php
elseif ($section == 'messaging'):
?>
<div class="blockform">
	<h2><span><?php echo pun_htmlspecialchars($user['username']).' - '.$lang_profile['Section messaging'] ?></span></h2>
	<div class="box">
		<form id="profile3" method="post" action="profile.php?section=messaging&amp;id=<?php echo $id ?>">
			<div class="inform">
				<fieldset>
					<legend><?php echo $lang_profile['Contact details legend'] ?></legend>
					<div class="infldset">
						<input type="hidden" name="form_sent" value="1" />
						<label><?php echo $lang_profile['Jabber'] ?><br /><input id="jabber" type="text" name="form[jabber]" value="<?php echo pun_htmlspecialchars($user['jabber']) ?>" size="40" maxlength="75" /><br /></label>
						<label><?php echo $lang_profile['ICQ'] ?><br /><input id="icq" type="text" name="form[icq]" value="<?php echo $user['icq'] ?>" size="12" maxlength="12" /><br /></label>
						<label><?php echo $lang_profile['MSN'] ?><br /><input id="msn" type="text" name="form[msn]" value="<?php echo pun_htmlspecialchars($user['msn']) ?>" size="40" maxlength="50" /><br /></label>
						<label><?php echo $lang_profile['AOL IM'] ?><br /><input id="aim" type="text" name="form[aim]" value="<?php echo pun_htmlspecialchars($user['aim']) ?>" size="20" maxlength="30" /><br /></label>
						<label><?php echo $lang_profile['Yahoo'] ?><br /><input id="yahoo" type="text" name="form[yahoo]" value="<?php echo pun_htmlspecialchars($user['yahoo']) ?>" size="20" maxlength="30" /><br /></label>
					</div>
				</fieldset>
			</div>
			<p class="buttons"><input type="submit" name="update" value="<?php echo $lang_common['Submit'] ?>" /> <?php echo $lang_profile['Instructions'] ?></p>
		</form>
	</div>
</div>
<?php
elseif ($section == 'personality') :
?>
<div class="blockform">
	<h2><span><?php echo pun_htmlspecialchars($user['username']).' - '.$lang_profile['Section personality'] ?></span></h2>
	<div class="box">
		<form id="profile4" method="post" action="profile.php?section=personality&amp;id=<?php echo $id ?>">
			<div><input type="hidden" name="form_sent" value="1" /></div>
<?php if ($pun_config['o_avatars'] == '1'): ?>				<div class="inform">
				<fieldset id="profileavatar">
					<legend><?php echo $lang_profile['Avatar legend'] ?></legend>
					<div class="infldset">
<?php if ($user_avatar): ?>							<div class="useravatar"><?php echo $user_avatar ?></div>
<?php endif; ?>							<p><?php echo $lang_profile['Avatar info'] ?></p>
						<p class="clearb actions"><?php echo $avatar_field ?></p>
					</div>
				</fieldset>
			</div>
<?php endif; if ($pun_config['o_signatures'] == '1'): ?>				<div class="inform">
				<fieldset>
					<legend><?php echo $lang_profile['Signature legend'] ?></legend>
					<div class="infldset">
						<p><?php echo $lang_profile['Signature info'] ?></p>
						<div class="txtarea">
							<label><?php printf($lang_profile['Sig max size'], forum_number_format($pun_config['p_sig_length']), $pun_config['p_sig_lines']) ?><br />
							<textarea name="signature" rows="4" cols="65"><?php echo pun_htmlspecialchars($user['signature']) ?></textarea><br /></label>
						</div>
						<ul class="bblinks">
							<li><span><a href="help.php#bbcode" onclick="window.open(this.href); return false;"><?php echo $lang_common['BBCode'] ?></a> <?php echo ($pun_config['p_sig_bbcode'] == '1') ? $lang_common['on'] : $lang_common['off']; ?></span></li>
							<li><span><a href="help.php#url" onclick="window.open(this.href); return false;"><?php echo $lang_common['url tag'] ?></a> <?php echo ($pun_config['p_sig_bbcode'] == '1' && $pun_user['g_post_links'] == '1') ? $lang_common['on'] : $lang_common['off']; ?></span></li>
							<li><span><a href="help.php#img" onclick="window.open(this.href); return false;"><?php echo $lang_common['img tag'] ?></a> <?php echo ($pun_config['p_sig_bbcode'] == '1' && $pun_config['p_sig_img_tag'] == '1') ? $lang_common['on'] : $lang_common['off']; ?></span></li>
							<li><span><a href="help.php#smilies" onclick="window.open(this.href); return false;"><?php echo $lang_common['Smilies'] ?></a> <?php echo ($pun_config['o_smilies_sig'] == '1') ? $lang_common['on'] : $lang_common['off']; ?></span></li>
						</ul>
						<?php echo $signature_preview ?>
					</div>
				</fieldset>
			</div>
<?php endif; ?>				<p class="buttons"><input type="submit" name="update" value="<?php echo $lang_common['Submit'] ?>" /> <?php echo $lang_profile['Instructions'] ?></p>
		</form>
	</div>
</div>
<?php
elseif ($section == 'display') :
?>
<div class="blockform">
	<h2><span><?php echo pun_htmlspecialchars($user['username']).' - '.$lang_profile['Section display'] ?></span></h2>
	<div class="box">
		<form id="profile5" method="post" action="profile.php?section=display&amp;id=<?php echo $id ?>">
			<div><input type="hidden" name="form_sent" value="1" /></div>
<?php

	$styles = forum_list_styles();

	// Only display the style selection box if there's more than one style available
	if (count($styles) == 1)
		echo "\t\t\t".'<div><input type="hidden" name="form[style]" value="'.$styles[0].'" /></div>'."\n";
	else if (count($styles) > 1)
	{

?>
			<div class="inform">
				<fieldset>
					<legend><?php echo $lang_profile['Style legend'] ?></legend>
					<div class="infldset">
						<label><?php echo $lang_profile['Styles'] ?><br />
						<select name="form[style]">
<?php

		foreach ($styles as $temp)
		{
			if ($user['style'] == $temp)
				echo "\t\t\t\t\t\t\t\t".'<option value="'.$temp.'" selected="selected">'.str_replace('_', ' ', $temp).'</option>'."\n";
			else
				echo "\t\t\t\t\t\t\t\t".'<option value="'.$temp.'">'.str_replace('_', ' ', $temp).'</option>'."\n";
		}

?>
						</select>
						<br /></label>
					</div>
				</fieldset>
			</div>
<?php

	}

?>
<?php if ($pun_config['o_smilies'] == '1' || $pun_config['o_smilies_sig'] == '1' || $pun_config['o_signatures'] == '1' || $pun_config['o_avatars'] == '1' || ($pun_config['p_message_bbcode'] == '1' && $pun_config['p_message_img_tag'] == '1')): ?>
			<div class="inform">
				<fieldset>
					<legend><?php echo $lang_profile['Post display legend'] ?></legend>
					<div class="infldset">
						<p><?php echo $lang_profile['Post display info'] ?></p>
						<div class="rbox">
<?php if ($pun_config['o_smilies'] == '1' || $pun_config['o_smilies_sig'] == '1'): ?>								<label><input type="checkbox" name="form[show_smilies]" value="1"<?php if ($user['show_smilies'] == '1') echo ' checked="checked"' ?> /><?php echo $lang_profile['Show smilies'] ?><br /></label>
<?php endif; if ($pun_config['o_signatures'] == '1'): ?>								<label><input type="checkbox" name="form[show_sig]" value="1"<?php if ($user['show_sig'] == '1') echo ' checked="checked"' ?> /><?php echo $lang_profile['Show sigs'] ?><br /></label>
<?php endif; if ($pun_config['o_avatars'] == '1'): ?>								<label><input type="checkbox" name="form[show_avatars]" value="1"<?php if ($user['show_avatars'] == '1') echo ' checked="checked"' ?> /><?php echo $lang_profile['Show avatars'] ?><br /></label>
<?php endif; if ($pun_config['p_message_bbcode'] == '1' && $pun_config['p_message_img_tag'] == '1'): ?>								<label><input type="checkbox" name="form[show_img]" value="1"<?php if ($user['show_img'] == '1') echo ' checked="checked"' ?> /><?php echo $lang_profile['Show images'] ?><br /></label>
<?php endif; if ($pun_config['o_signatures'] == '1' && $pun_config['p_sig_bbcode'] == '1' && $pun_config['p_sig_img_tag'] == '1'): ?>								<label><input type="checkbox" name="form[show_img_sig]" value="1"<?php if ($user['show_img_sig'] == '1') echo ' checked="checked"' ?> /><?php echo $lang_profile['Show images sigs'] ?><br /></label>
<?php endif; ?>
						</div>
					</div>
				</fieldset>
			</div>
<?php endif; ?>
			<div class="inform">
				<fieldset>
					<legend><?php echo $lang_profile['Pagination legend'] ?></legend>
					<div class="infldset">
						<label class="conl"><?php echo $lang_profile['Topics per page'] ?><br /><input type="text" name="form[disp_topics]" value="<?php echo $user['disp_topics'] ?>" size="6" maxlength="2" /><br /></label>
						<label class="conl"><?php echo $lang_profile['Posts per page'] ?><br /><input type="text" name="form[disp_posts]" value="<?php echo $user['disp_posts'] ?>" size="6" maxlength="2" /><br /></label>
						<p class="clearb"><?php echo $lang_profile['Paginate info'] ?> <?php echo $lang_profile['Leave blank'] ?></p>
					</div>
				</fieldset>
			</div>
			<p class="buttons"><input type="submit" name="update" value="<?php echo $lang_common['Submit'] ?>" /> <?php echo $lang_profile['Instructions'] ?></p>
		</form>
	</div>
</div>
<?php
elseif ($section == 'privacy') :
?>
<div class="blockform">
	<h2><span><?php echo pun_htmlspecialchars($user['username']).' - '.$lang_profile['Section privacy'] ?></span></h2>
	<div class="box">
		<form id="profile6" method="post" action="profile.php?section=privacy&amp;id=<?php echo $id ?>">
			<div class="inform">
				<fieldset>
					<legend><?php echo $lang_prof_reg['Privacy options legend'] ?></legend>
					<div class="infldset">
						<input type="hidden" name="form_sent" value="1" />
						<p><?php echo $lang_prof_reg['Email setting info'] ?></p>
						<div class="rbox">
							<label><input type="radio" name="form[email_setting]" value="0"<?php if ($user['email_setting'] == '0') echo ' checked="checked"' ?> /><?php echo $lang_prof_reg['Email setting 1'] ?><br /></label>
							<label><input type="radio" name="form[email_setting]" value="1"<?php if ($user['email_setting'] == '1') echo ' checked="checked"' ?> /><?php echo $lang_prof_reg['Email setting 2'] ?><br /></label>
							<label><input type="radio" name="form[email_setting]" value="2"<?php if ($user['email_setting'] == '2') echo ' checked="checked"' ?> /><?php echo $lang_prof_reg['Email setting 3'] ?><br /></label>
						</div>
					</div>
				</fieldset>
			</div>
<?php if ($pun_config['o_forum_subscriptions'] == '1' || $pun_config['o_topic_subscriptions'] == '1'): ?>				<div class="inform">
				<fieldset>
					<legend><?php echo $lang_profile['Subscription legend'] ?></legend>
					<div class="infldset">
						<div class="rbox">
							<label><input type="checkbox" name="form[notify_with_post]" value="1"<?php if ($user['notify_with_post'] == '1') echo ' checked="checked"' ?> /><?php echo $lang_profile['Notify full'] ?><br /></label>
<?php if ($pun_config['o_topic_subscriptions'] == '1'): ?>								<label><input type="checkbox" name="form[auto_notify]" value="1"<?php if ($user['auto_notify'] == '1') echo ' checked="checked"' ?> /><?php echo $lang_profile['Auto notify full'] ?><br /></label>
<?php endif; ?>
						</div>
					</div>
				</fieldset>
			</div>
<?php endif; ?>				<p class="buttons"><input type="submit" name="update" value="<?php echo $lang_common['Submit'] ?>" /> <?php echo $lang_profile['Instructions'] ?></p>
		</form>
	</div>
</div>
<?php
elseif ($section == 'admin'):
?>
	<div class="blockform">
		<h2><span><?php echo pun_htmlspecialchars($user['username']).' - '.$lang_profile['Section admin'] ?></span></h2>
		<div class="box">
			<form id="profile7" method="post" action="profile.php?section=admin&amp;id=<?php echo $id ?>">
				<div class="inform">
				<input type="hidden" name="form_sent" value="1" />
					<fieldset>
<?php

		if ($pun_user['g_moderator'] == '1')
		{

?>
						<legend><?php echo $lang_profile['Delete ban legend'] ?></legend>
						<div class="infldset">
							<p><input type="submit" name="ban" value="<?php echo $lang_profile['Ban user'] ?>" /></p>
						</div>
					</fieldset>
				</div>
<?php

		}
		else
		{
			if ($pun_user['id'] != $id)
			{

?>
						<legend><?php echo $lang_profile['Group membership legend'] ?></legend>
						<div class="infldset">
							<select id="group_id" name="group_id">
<?php
							get_group_list($user);
?>
							</select>
							<input type="submit" name="update_group_membership" value="<?php echo $lang_profile['Save'] ?>" />
						</div>
					</fieldset>
				</div>
				<div class="inform">
					<fieldset>
<?php

			}

?>
						<legend><?php echo $lang_profile['Delete ban legend'] ?></legend>
						<div class="infldset">
							<input type="submit" name="delete_user" value="<?php echo $lang_profile['Delete user'] ?>" /> <input type="submit" name="ban" value="<?php echo $lang_profile['Ban user'] ?>" />
						</div>
					</fieldset>
				</div>
<?php

			if ($user['g_moderator'] == '1' || $user['g_id'] == PUN_ADMIN)
			{

?>
				<div class="inform">
					<fieldset>
						<legend><?php echo $lang_profile['Set mods legend'] ?></legend>
						<div class="infldset">
							<p><?php echo $lang_profile['Moderator in info'] ?></p>
<?php

								get_forum_list($id);

?>
								</div>
							</div>
							<br class="clearb" /><input type="submit" name="update_forums" value="<?php echo $lang_profile['Update forums'] ?>" />
						</div>
					</fieldset>
				</div>
<?php

			}
		}

?>
			</form>
		</div>
	</div>
<?php
endif;
?>
	<div class="clearer"></div>
</div>
<?php
endif;