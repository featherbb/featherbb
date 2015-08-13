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
	<h2><span><?php echo $lang_common['Login'] ?></span></h2>
	<div class="box">
		<form id="login" method="post" action="<?php echo get_link('login/action/in/') ?>" onsubmit="return process_form(this)">
			<input type="hidden" name="<?php echo $csrf_key; ?>" value="<?php echo $csrf_token; ?>">
			<div class="inform">
				<fieldset>
					<legend><?php echo $lang_login['Login legend'] ?></legend>
					<div class="infldset">
						<input type="hidden" name="form_sent" value="1" />
						<input type="hidden" name="redirect_url" value="<?php echo feather_escape($redirect_url) ?>" />
						<label class="conl required"><strong><?php echo $lang_common['Username'] ?> <span><?php echo $lang_common['Required'] ?></span></strong><br /><input type="text" name="req_username" size="25" maxlength="25" tabindex="1" /><br /></label>
						<label class="conl required"><strong><?php echo $lang_common['Password'] ?> <span><?php echo $lang_common['Required'] ?></span></strong><br /><input type="password" name="req_password" size="25" tabindex="2" /><br /></label>

						<div class="rbox clearb">
							<label><input type="checkbox" name="save_pass" value="1" tabindex="3" /><?php echo $lang_login['Remember me'] ?><br /></label>
						</div>

						<p class="clearb"><?php echo $lang_login['Login info'] ?></p>
						<p class="actions"><span><a href="<?php echo get_link('register/') ?>" tabindex="5"><?php echo $lang_login['Not registered'] ?></a></span> <span><a href="<?php echo get_link('login/action/forget/') ?>" tabindex="6"><?php echo $lang_login['Forgotten pass'] ?></a></span></p>
					</div>
				</fieldset>
			</div>
			<p class="buttons"><input type="submit" name="login" value="<?php echo $lang_common['Login'] ?>" tabindex="4" /></p>
		</form>
	</div>
</div>