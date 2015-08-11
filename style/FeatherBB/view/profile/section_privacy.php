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
	<h2><span><?php echo feather_escape($user['username']).' - '.$lang_profile['Section privacy'] ?></span></h2>
	<div class="box">
		<form id="profile6" method="post" action="<?php echo get_link('user/'.$id.'/section/privacy/') ?>">
			<input type="hidden" name="<?php echo $csrf_key; ?>" value="<?php echo $csrf_token; ?>">
			<div class="inform">
				<fieldset>
					<legend><?php echo $lang_prof_reg['Privacy options legend'] ?></legend>
					<div class="infldset">
						<input type="hidden" name="form_sent" value="1" />
						<p><?php echo $lang_prof_reg['Email setting info'] ?></p>
						<div class="rbox">
							<label><input type="radio" name="form_email_setting" value="0"<?php if ($user['email_setting'] == '0') {
    echo ' checked="checked"';
} ?> /><?php echo $lang_prof_reg['Email setting 1'] ?><br /></label>
							<label><input type="radio" name="form_email_setting" value="1"<?php if ($user['email_setting'] == '1') {
    echo ' checked="checked"';
} ?> /><?php echo $lang_prof_reg['Email setting 2'] ?><br /></label>
							<label><input type="radio" name="form_email_setting" value="2"<?php if ($user['email_setting'] == '2') {
    echo ' checked="checked"';
} ?> /><?php echo $lang_prof_reg['Email setting 3'] ?><br /></label>
						</div>
					</div>
				</fieldset>
			</div>
<?php if ($feather_config['o_forum_subscriptions'] == '1' || $feather_config['o_topic_subscriptions'] == '1'): ?>				<div class="inform">
				<fieldset>
					<legend><?php echo $lang_profile['Subscription legend'] ?></legend>
					<div class="infldset">
						<div class="rbox">
							<label><input type="checkbox" name="form_notify_with_post" value="1"<?php if ($user['notify_with_post'] == '1') {
    echo ' checked="checked"';
} ?> /><?php echo $lang_profile['Notify full'] ?><br /></label>
<?php if ($feather_config['o_topic_subscriptions'] == '1'): ?>								<label><input type="checkbox" name="form_auto_notify" value="1"<?php if ($user['auto_notify'] == '1') {
    echo ' checked="checked"';
} ?> /><?php echo $lang_profile['Auto notify full'] ?><br /></label>
<?php endif; ?>
						</div>
					</div>
				</fieldset>
			</div>
<?php endif; ?>				<p class="buttons"><input type="submit" name="update" value="<?php echo $lang_common['Submit'] ?>" /> <?php echo $lang_profile['Instructions'] ?></p>
		</form>
	</div>
</div>
	<div class="clearer"></div>
</div>