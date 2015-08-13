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
	<h2><span><?php echo __('Change pass') ?></span></h2>
	<div class="box">
		<form id="change_pass" method="post" action="<?php echo get_link('user/'.$id.'/action/change_pass/') ?>" onsubmit="return process_form(this)">
			<input type="hidden" name="<?php echo $csrf_key; ?>" value="<?php echo $csrf_token; ?>">
			<div class="inform">
				<input type="hidden" name="form_sent" value="1" />
				<fieldset>
					<legend><?php echo __('Change pass legend') ?></legend>
					<div class="infldset">
<?php if (!$feather->user->is_admmod): ?>						<label class="required"><strong><?php echo __('Old pass') ?> <span><?php echo __('Required') ?></span></strong><br />
						<input type="password" name="req_old_password" size="16" /><br /></label>
<?php endif; ?>						<label class="conl required"><strong><?php echo __('New pass') ?> <span><?php echo __('Required') ?></span></strong><br />
						<input type="password" name="req_new_password1" size="16" /><br /></label>
						<label class="conl required"><strong><?php echo __('Confirm new pass') ?> <span><?php echo __('Required') ?></span></strong><br />
						<input type="password" name="req_new_password2" size="16" /><br /></label>
						<p class="clearb"><?php echo __('Pass info') ?></p>
					</div>
				</fieldset>
			</div>
			<p class="buttons"><input type="submit" name="update" value="<?php echo __('Submit') ?>" /> <a href="javascript:history.go(-1)"><?php echo __('Go back') ?></a></p>
		</form>
	</div>
</div>