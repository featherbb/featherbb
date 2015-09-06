<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

use FeatherBB\Core\Url;

// Make sure no one attempts to run this script "directly"
if (!defined('FEATHER')) {
    exit;
}

?>
<div class="blockform">
	<h2><span><?php _e('Change pass') ?></span></h2>
	<div class="box">
		<form id="change_pass" method="post" action="<?php echo Url::get('user/'.$id.'/action/change_pass/') ?>" onsubmit="return process_form(this)">
			<input type="hidden" name="<?php echo $csrf_key; ?>" value="<?php echo $csrf_token; ?>">
			<div class="inform">
				<input type="hidden" name="form_sent" value="1" />
				<fieldset>
					<legend><?php _e('Change pass legend') ?></legend>
					<div class="infldset">
<?php if (!$feather->user->is_admmod): ?>						<label class="required"><strong><?php _e('Old pass') ?> <span><?php _e('Required') ?></span></strong><br />
						<input type="password" name="req_old_password" size="16" /><br /></label>
<?php endif; ?>						<label class="conl required"><strong><?php _e('New pass') ?> <span><?php _e('Required') ?></span></strong><br />
						<input type="password" name="req_new_password1" size="16" /><br /></label>
						<label class="conl required"><strong><?php _e('Confirm new pass') ?> <span><?php _e('Required') ?></span></strong><br />
						<input type="password" name="req_new_password2" size="16" /><br /></label>
						<p class="clearb"><?php _e('Pass info') ?></p>
					</div>
				</fieldset>
			</div>
			<p class="buttons"><input type="submit" name="update" value="<?php _e('Submit') ?>" /> <a href="javascript:history.go(-1)"><?php _e('Go back') ?></a></p>
		</form>
	</div>
</div>