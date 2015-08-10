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
	<h2><span><?php echo feather_escape($user['username']).' - '.$lang_profile['Section personal'] ?></span></h2>
	<div class="box">
		<form id="profile2" method="post" action="<?php echo get_link('user/'.$id.'/section/personal/') ?>">
			<input type="hidden" name="<?php echo $csrf_key; ?>" value="<?php echo $csrf_token; ?>">
			<div class="inform">
				<fieldset>
					<legend><?php echo $lang_profile['Personal details legend'] ?></legend>
					<div class="infldset">
						<input type="hidden" name="form_sent" value="1" />
						<label><?php echo $lang_profile['Realname'] ?><br /><input type="text" name="form_realname" value="<?php echo feather_escape($user['realname']) ?>" size="40" maxlength="40" /><br /></label>
<?php if (isset($title_field)): ?>							<?php echo $title_field ?>
<?php endif; ?>							<label><?php echo $lang_profile['Location'] ?><br /><input type="text" name="form_location" value="<?php echo feather_escape($user['location']) ?>" size="30" maxlength="30" /><br /></label>
<?php if ($feather->user->g_post_links == '1' || $feather->user->g_id == FEATHER_ADMIN) : ?>							<label><?php echo $lang_profile['Website'] ?><br /><input type="text" name="form_url" value="<?php echo feather_escape($user['url']) ?>" size="50" maxlength="80" /><br /></label>
<?php endif; ?>
					</div>
				</fieldset>
			</div>
			<p class="buttons"><input type="submit" name="update" value="<?php echo $lang_common['Submit'] ?>" /> <?php echo $lang_profile['Instructions'] ?></p>
		</form>
	</div>
</div>
	<div class="clearer"></div>
</div>