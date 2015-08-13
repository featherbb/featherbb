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
	<h2><span><?php echo feather_escape($user['username']).' - '.$lang_profile['Section messaging'] ?></span></h2>
	<div class="box">
		<form id="profile3" method="post" action="<?php echo get_link('user/'.$id.'/section/messaging/') ?>">
			<input type="hidden" name="<?php echo $csrf_key; ?>" value="<?php echo $csrf_token; ?>">
			<div class="inform">
				<fieldset>
					<legend><?php echo $lang_profile['Contact details legend'] ?></legend>
					<div class="infldset">
						<input type="hidden" name="form_sent" value="1" />
						<label><?php echo $lang_profile['Jabber'] ?><br /><input id="jabber" type="text" name="form_jabber" value="<?php echo feather_escape($user['jabber']) ?>" size="40" maxlength="75" /><br /></label>
						<label><?php echo $lang_profile['ICQ'] ?><br /><input id="icq" type="text" name="form_icq" value="<?php echo $user['icq'] ?>" size="12" maxlength="12" /><br /></label>
						<label><?php echo $lang_profile['MSN'] ?><br /><input id="msn" type="text" name="form_msn" value="<?php echo feather_escape($user['msn']) ?>" size="40" maxlength="50" /><br /></label>
						<label><?php echo $lang_profile['AOL IM'] ?><br /><input id="aim" type="text" name="form_aim" value="<?php echo feather_escape($user['aim']) ?>" size="20" maxlength="30" /><br /></label>
						<label><?php echo $lang_profile['Yahoo'] ?><br /><input id="yahoo" type="text" name="form_yahoo" value="<?php echo feather_escape($user['yahoo']) ?>" size="20" maxlength="30" /><br /></label>
					</div>
				</fieldset>
			</div>
			<p class="buttons"><input type="submit" name="update" value="<?php echo $lang_common['Submit'] ?>" /> <?php echo $lang_profile['Instructions'] ?></p>
		</form>
	</div>
</div>
	<div class="clearer"></div>
</div>