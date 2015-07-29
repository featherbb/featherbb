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
	<h2><span><?php echo $lang_profile['Change email'] ?></span></h2>
	<div class="box">
		<form id="change_email" method="post" action="<?php echo get_link('user/'.$id.'/action/change_email') ?>" onsubmit="return process_form(this)">
			<input type="hidden" name="<?php echo $csrf_key; ?>" value="<?php echo $csrf_token; ?>">
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