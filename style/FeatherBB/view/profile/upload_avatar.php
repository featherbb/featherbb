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
	<h2><span><?php echo $lang_profile['Upload avatar'] ?></span></h2>
	<div class="box">
		<form id="upload_avatar" method="post" enctype="multipart/form-data" action="<?php echo get_link('user/'.$id.'/action/upload_avatar2/') ?>" onsubmit="return process_form(this)">
			<input type="hidden" name="<?php echo $csrf_key; ?>" value="<?php echo $csrf_token; ?>">
			<div class="inform">
				<fieldset>
					<legend><?php echo $lang_profile['Upload avatar legend'] ?></legend>
					<div class="infldset">
						<input type="hidden" name="form_sent" value="1" />
						<input type="hidden" name="MAX_FILE_SIZE" value="<?php echo $feather_config['o_avatars_size'] ?>" />
						<label class="required"><strong><?php echo $lang_profile['File'] ?> <span><?php echo $lang_common['Required'] ?></span></strong><br /><input name="req_file" type="file" size="40" /><br /></label>
						<p><?php echo $lang_profile['Avatar desc'].' '.$feather_config['o_avatars_width'].' x '.$feather_config['o_avatars_height'].' '.$lang_profile['pixels'].' '.$lang_common['and'].' '.forum_number_format($feather_config['o_avatars_size']).' '.$lang_profile['bytes'].' ('.file_size($feather_config['o_avatars_size']).').' ?></p>
					</div>
				</fieldset>
			</div>
			<p class="buttons"><input type="submit" name="upload" value="<?php echo $lang_profile['Upload'] ?>" /> <a href="javascript:history.go(-1)"><?php echo $lang_common['Go back'] ?></a></p>
		</form>
	</div>
</div>