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

<div class="linkst">
	<div class="inbox">
		<ul class="crumbs">
			<li><a href="<?php echo get_base_url() ?>"><?php echo $lang_common['Index'] ?></a></li>
			<li><span>»&#160;</span><a href="<?php echo get_link('forum/'.$cur_post['fid'].'/'.url_friendly($cur_post['forum_name']).'/') ?>"><?php echo feather_escape($cur_post['forum_name']) ?></a></li>
			<li><span>»&#160;</span><a href="<?php echo get_link('post/'.$id.'/#p'.$id) ?>"><?php echo feather_escape($cur_post['subject']) ?></a></li>
			<li><span>»&#160;</span><strong><?php echo $lang_misc['Report post'] ?></strong></li>
		</ul>
	</div>
</div>

<div id="reportform" class="blockform">
	<h2><span><?php echo $lang_misc['Report post'] ?></span></h2>
	<div class="box">
		<form id="report" method="post" action="<?php echo get_link('report/'.$id.'/') ?>" onsubmit="this.submit.disabled=true;if(process_form(this)){return true;}else{this.submit.disabled=false;return false;}">
			<input type="hidden" name="<?php echo $csrf_key; ?>" value="<?php echo $csrf_token; ?>">
			<div class="inform">
				<fieldset>
					<legend><?php echo $lang_misc['Reason desc'] ?></legend>
					<div class="infldset txtarea">
						<input type="hidden" name="form_sent" value="1" />
						<label class="required"><strong><?php echo $lang_misc['Reason'] ?> <span><?php echo $lang_common['Required'] ?></span></strong><br /><textarea name="req_reason" rows="5" cols="60"></textarea><br /></label>
					</div>
				</fieldset>
			</div>
			<p class="buttons"><input type="submit" name="submit" value="<?php echo $lang_common['Submit'] ?>" accesskey="s" /> <a href="javascript:history.go(-1)"><?php echo $lang_common['Go back'] ?></a></p>
		</form>
	</div>
</div>