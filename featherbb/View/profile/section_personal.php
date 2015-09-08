<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

use FeatherBB\Core\Url;
use FeatherBB\Core\Utils;

// Make sure no one attempts to run this script "directly"
if (!isset($feather)) {
    exit;
}

?>
<div class="blockform">
	<h2><span><?php echo Utils::escape($user['username']).' - '.__('Section personal') ?></span></h2>
	<div class="box">
		<form id="profile2" method="post" action="<?php echo $feather->urlFor('profileSection', ['id' => $id, 'section' => 'personal']) ?>">
			<input type="hidden" name="<?php echo $csrf_key; ?>" value="<?php echo $csrf_token; ?>">
			<div class="inform">
				<fieldset>
					<legend><?php _e('Personal details legend') ?></legend>
					<div class="infldset">
						<input type="hidden" name="form_sent" value="1" />
						<label><?php _e('Realname') ?><br /><input type="text" name="form_realname" value="<?php echo Utils::escape($user['realname']) ?>" size="40" maxlength="40" /><br /></label>
<?php if (isset($title_field)): ?>							<?php echo $title_field ?>
<?php endif; ?>							<label><?php _e('Location') ?><br /><input type="text" name="form_location" value="<?php echo Utils::escape($user['location']) ?>" size="30" maxlength="30" /><br /></label>
<?php if ($feather->user->g_post_links == '1' || $feather->user->g_id == $feather->forum_env['FEATHER_ADMIN']) : ?>							<label><?php _e('Website') ?><br /><input type="text" name="form_url" value="<?php echo Utils::escape($user['url']) ?>" size="50" maxlength="80" /><br /></label>
<?php endif; ?>
					</div>
				</fieldset>
			</div>
			<p class="buttons"><input type="submit" name="update" value="<?php _e('Submit') ?>" /> <?php _e('Instructions') ?></p>
		</form>
	</div>
</div>
	<div class="clearer"></div>
</div>
