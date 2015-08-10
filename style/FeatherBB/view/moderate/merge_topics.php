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
	<h2><span><?php echo $lang_misc['Merge topics'] ?></span></h2>
	<div class="box">
		<form method="post" action="">
			<input type="hidden" name="<?php echo $csrf_key; ?>" value="<?php echo $csrf_token; ?>">
			<input type="hidden" name="topics" value="<?php echo implode(',', array_map('intval', array_keys($topics))) ?>" />
			<div class="inform">
				<fieldset>
					<legend><?php echo $lang_misc['Confirm merge legend'] ?></legend>
					<div class="infldset">
						<div class="rbox">
							<label><input type="checkbox" name="with_redirect" value="1" /><?php echo $lang_misc['Leave redirect'] ?><br /></label>
						</div>
					</div>
				</fieldset>
			</div>
			<p class="buttons"><input type="submit" name="merge_topics_comply" value="<?php echo $lang_misc['Merge'] ?>" /> <a href="javascript:history.go(-1)"><?php echo $lang_common['Go back'] ?></a></p>
		</form>
	</div>
</div>