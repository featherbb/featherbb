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

// If there are errors, we display them
if (!empty($user['errors']))
{

?>
<div id="posterror" class="block">
	<h2><span><?php echo $lang_register['Registration errors'] ?></span></h2>
	<div class="box">
		<div class="inbox error-info">
			<p><?php echo $lang_register['Registration errors info'] ?></p>
			<ul class="error-list">
<?php

	foreach ($user['errors'] as $cur_error)
		echo "\t\t\t\t".'<li><strong>'.$cur_error.'</strong></li>'."\n";
?>
			</ul>
		</div>
	</div>
</div>

<?php

}
?>
<div id="regform" class="blockform">
	<h2><span><?php echo $lang_register['Register'] ?></span></h2>
	<div class="box">
		<form id="register" method="post" action="register.php?action=register" onsubmit="this.register.disabled=true;if(process_form(this)){return true;}else{this.register.disabled=false;return false;}">
			<div class="inform">
				<div class="forminfo">
					<h3><?php echo $lang_common['Important information'] ?></h3>
					<p><?php echo $lang_register['Desc 1'] ?></p>
					<p><?php echo $lang_register['Desc 2'] ?></p>
				</div>
				<fieldset>
					<legend><?php echo $lang_register['Username legend'] ?></legend>
					<div class="infldset">
						<input type="hidden" name="form_sent" value="1" />
						<label class="required"><strong><?php echo $lang_common['Username'] ?> <span><?php echo $lang_common['Required'] ?></span></strong><br /><input type="text" name="req_user" value="<?php if (isset($_POST['req_user'])) echo pun_htmlspecialchars($_POST['req_user']); ?>" size="25" maxlength="25" /><br /></label>
					</div>
				</fieldset>
			</div>
<?php if ($pun_config['o_regs_verify'] == '0'): ?>			<div class="inform">
				<fieldset>
					<legend><?php echo $lang_register['Pass legend'] ?></legend>
					<div class="infldset">
						<label class="conl required"><strong><?php echo $lang_common['Password'] ?> <span><?php echo $lang_common['Required'] ?></span></strong><br /><input type="password" name="req_password1" value="<?php if (isset($_POST['req_password1'])) echo pun_htmlspecialchars($_POST['req_password1']); ?>" size="16" /><br /></label>
						<label class="conl required"><strong><?php echo $lang_prof_reg['Confirm pass'] ?> <span><?php echo $lang_common['Required'] ?></span></strong><br /><input type="password" name="req_password2" value="<?php if (isset($_POST['req_password2'])) echo pun_htmlspecialchars($_POST['req_password2']); ?>" size="16" /><br /></label>
						<p class="clearb"><?php echo $lang_register['Pass info'] ?></p>
					</div>
				</fieldset>
			</div>
<?php endif; ?>			<div class="inform">
				<fieldset>
					<legend><?php echo ($pun_config['o_regs_verify'] == '1') ? $lang_prof_reg['Email legend 2'] : $lang_prof_reg['Email legend'] ?></legend>
					<div class="infldset">
<?php if ($pun_config['o_regs_verify'] == '1'): ?>						<p><?php echo $lang_register['Email info'] ?></p>
<?php endif; ?>						<label class="required"><strong><?php echo $lang_common['Email'] ?> <span><?php echo $lang_common['Required'] ?></span></strong><br />
						<input type="text" name="req_email1" value="<?php if (isset($_POST['req_email1'])) echo pun_htmlspecialchars($_POST['req_email1']); ?>" size="50" maxlength="80" /><br /></label>
<?php if ($pun_config['o_regs_verify'] == '1'): ?>						<label class="required"><strong><?php echo $lang_register['Confirm email'] ?> <span><?php echo $lang_common['Required'] ?></span></strong><br />
						<input type="text" name="req_email2" value="<?php if (isset($_POST['req_email2'])) echo pun_htmlspecialchars($_POST['req_email2']); ?>" size="50" maxlength="80" /><br /></label>
<?php endif; ?>					</div>
				</fieldset>
			</div>
			<div class="inform">
				<fieldset>
					<legend><?php echo $lang_prof_reg['Localisation legend'] ?></legend>
					<div class="infldset">
						<p><?php echo $lang_prof_reg['Time zone info'] ?></p>
						<label><?php echo $lang_prof_reg['Time zone']."\n" ?>
						<br /><select id="time_zone" name="timezone">
							<option value="-12"<?php if ($timezone == -12) echo ' selected="selected"' ?>><?php echo $lang_prof_reg['UTC-12:00'] ?></option>
							<option value="-11"<?php if ($timezone == -11) echo ' selected="selected"' ?>><?php echo $lang_prof_reg['UTC-11:00'] ?></option>
							<option value="-10"<?php if ($timezone == -10) echo ' selected="selected"' ?>><?php echo $lang_prof_reg['UTC-10:00'] ?></option>
							<option value="-9.5"<?php if ($timezone == -9.5) echo ' selected="selected"' ?>><?php echo $lang_prof_reg['UTC-09:30'] ?></option>
							<option value="-9"<?php if ($timezone == -9) echo ' selected="selected"' ?>><?php echo $lang_prof_reg['UTC-09:00'] ?></option>
							<option value="-8.5"<?php if ($timezone == -8.5) echo ' selected="selected"' ?>><?php echo $lang_prof_reg['UTC-08:30'] ?></option>
							<option value="-8"<?php if ($timezone == -8) echo ' selected="selected"' ?>><?php echo $lang_prof_reg['UTC-08:00'] ?></option>
							<option value="-7"<?php if ($timezone == -7) echo ' selected="selected"' ?>><?php echo $lang_prof_reg['UTC-07:00'] ?></option>
							<option value="-6"<?php if ($timezone == -6) echo ' selected="selected"' ?>><?php echo $lang_prof_reg['UTC-06:00'] ?></option>
							<option value="-5"<?php if ($timezone == -5) echo ' selected="selected"' ?>><?php echo $lang_prof_reg['UTC-05:00'] ?></option>
							<option value="-4"<?php if ($timezone == -4) echo ' selected="selected"' ?>><?php echo $lang_prof_reg['UTC-04:00'] ?></option>
							<option value="-3.5"<?php if ($timezone == -3.5) echo ' selected="selected"' ?>><?php echo $lang_prof_reg['UTC-03:30'] ?></option>
							<option value="-3"<?php if ($timezone == -3) echo ' selected="selected"' ?>><?php echo $lang_prof_reg['UTC-03:00'] ?></option>
							<option value="-2"<?php if ($timezone == -2) echo ' selected="selected"' ?>><?php echo $lang_prof_reg['UTC-02:00'] ?></option>
							<option value="-1"<?php if ($timezone == -1) echo ' selected="selected"' ?>><?php echo $lang_prof_reg['UTC-01:00'] ?></option>
							<option value="0"<?php if ($timezone == 0) echo ' selected="selected"' ?>><?php echo $lang_prof_reg['UTC'] ?></option>
							<option value="1"<?php if ($timezone == 1) echo ' selected="selected"' ?>><?php echo $lang_prof_reg['UTC+01:00'] ?></option>
							<option value="2"<?php if ($timezone == 2) echo ' selected="selected"' ?>><?php echo $lang_prof_reg['UTC+02:00'] ?></option>
							<option value="3"<?php if ($timezone == 3) echo ' selected="selected"' ?>><?php echo $lang_prof_reg['UTC+03:00'] ?></option>
							<option value="3.5"<?php if ($timezone == 3.5) echo ' selected="selected"' ?>><?php echo $lang_prof_reg['UTC+03:30'] ?></option>
							<option value="4"<?php if ($timezone == 4) echo ' selected="selected"' ?>><?php echo $lang_prof_reg['UTC+04:00'] ?></option>
							<option value="4.5"<?php if ($timezone == 4.5) echo ' selected="selected"' ?>><?php echo $lang_prof_reg['UTC+04:30'] ?></option>
							<option value="5"<?php if ($timezone == 5) echo ' selected="selected"' ?>><?php echo $lang_prof_reg['UTC+05:00'] ?></option>
							<option value="5.5"<?php if ($timezone == 5.5) echo ' selected="selected"' ?>><?php echo $lang_prof_reg['UTC+05:30'] ?></option>
							<option value="5.75"<?php if ($timezone == 5.75) echo ' selected="selected"' ?>><?php echo $lang_prof_reg['UTC+05:45'] ?></option>
							<option value="6"<?php if ($timezone == 6) echo ' selected="selected"' ?>><?php echo $lang_prof_reg['UTC+06:00'] ?></option>
							<option value="6.5"<?php if ($timezone == 6.5) echo ' selected="selected"' ?>><?php echo $lang_prof_reg['UTC+06:30'] ?></option>
							<option value="7"<?php if ($timezone == 7) echo ' selected="selected"' ?>><?php echo $lang_prof_reg['UTC+07:00'] ?></option>
							<option value="8"<?php if ($timezone == 8) echo ' selected="selected"' ?>><?php echo $lang_prof_reg['UTC+08:00'] ?></option>
							<option value="8.75"<?php if ($timezone == 8.75) echo ' selected="selected"' ?>><?php echo $lang_prof_reg['UTC+08:45'] ?></option>
							<option value="9"<?php if ($timezone == 9) echo ' selected="selected"' ?>><?php echo $lang_prof_reg['UTC+09:00'] ?></option>
							<option value="9.5"<?php if ($timezone == 9.5) echo ' selected="selected"' ?>><?php echo $lang_prof_reg['UTC+09:30'] ?></option>
							<option value="10"<?php if ($timezone == 10) echo ' selected="selected"' ?>><?php echo $lang_prof_reg['UTC+10:00'] ?></option>
							<option value="10.5"<?php if ($timezone == 10.5) echo ' selected="selected"' ?>><?php echo $lang_prof_reg['UTC+10:30'] ?></option>
							<option value="11"<?php if ($timezone == 11) echo ' selected="selected"' ?>><?php echo $lang_prof_reg['UTC+11:00'] ?></option>
							<option value="11.5"<?php if ($timezone == 11.5) echo ' selected="selected"' ?>><?php echo $lang_prof_reg['UTC+11:30'] ?></option>
							<option value="12"<?php if ($timezone == 12) echo ' selected="selected"' ?>><?php echo $lang_prof_reg['UTC+12:00'] ?></option>
							<option value="12.75"<?php if ($timezone == 12.75) echo ' selected="selected"' ?>><?php echo $lang_prof_reg['UTC+12:45'] ?></option>
							<option value="13"<?php if ($timezone == 13) echo ' selected="selected"' ?>><?php echo $lang_prof_reg['UTC+13:00'] ?></option>
							<option value="14"<?php if ($timezone == 14) echo ' selected="selected"' ?>><?php echo $lang_prof_reg['UTC+14:00'] ?></option>
						</select>
						<br /></label>
						<div class="rbox">
							<label><input type="checkbox" name="dst" value="1"<?php if ($dst == '1') echo ' checked="checked"' ?> /><?php echo $lang_prof_reg['DST'] ?><br /></label>
						</div>
<?php

		$languages = forum_list_langs();

		// Only display the language selection box if there's more than one language available
		if (count($languages) > 1)
		{

?>
							<label><?php echo $lang_prof_reg['Language'] ?>
							<br /><select name="language">
<?php

			foreach ($languages as $temp)
			{
				if ($pun_config['o_default_lang'] == $temp)
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
					<legend><?php echo $lang_prof_reg['Privacy options legend'] ?></legend>
					<div class="infldset">
						<p><?php echo $lang_prof_reg['Email setting info'] ?></p>
						<div class="rbox">
							<label><input type="radio" name="email_setting" value="0"<?php if ($email_setting == '0') echo ' checked="checked"' ?> /><?php echo $lang_prof_reg['Email setting 1'] ?><br /></label>
							<label><input type="radio" name="email_setting" value="1"<?php if ($email_setting == '1') echo ' checked="checked"' ?> /><?php echo $lang_prof_reg['Email setting 2'] ?><br /></label>
							<label><input type="radio" name="email_setting" value="2"<?php if ($email_setting == '2') echo ' checked="checked"' ?> /><?php echo $lang_prof_reg['Email setting 3'] ?><br /></label>
						</div>
					</div>
				</fieldset>
			</div>
			<p class="buttons"><input type="submit" name="register" value="<?php echo $lang_register['Register'] ?>" /></p>
		</form>
	</div>
</div>