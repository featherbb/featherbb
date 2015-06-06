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