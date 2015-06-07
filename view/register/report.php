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

<div class="linkst">
	<div class="inbox">
		<ul class="crumbs">
			<li><a href="index.php"><?php echo $lang_common['Index'] ?></a></li>
			<li><span>»&#160;</span><a href="viewforum.php?id=<?php echo $cur_post['fid'] ?>"><?php echo pun_htmlspecialchars($cur_post['forum_name']) ?></a></li>
			<li><span>»&#160;</span><a href="viewtopic.php?pid=<?php echo $post_id ?>#p<?php echo $post_id ?>"><?php echo pun_htmlspecialchars($cur_post['subject']) ?></a></li>
			<li><span>»&#160;</span><strong><?php echo $lang_misc['Report post'] ?></strong></li>
		</ul>
	</div>
</div>

<div id="reportform" class="blockform">
	<h2><span><?php echo $lang_misc['Report post'] ?></span></h2>
	<div class="box">
		<form id="report" method="post" action="misc.php?report=<?php echo $post_id ?>" onsubmit="this.submit.disabled=true;if(process_form(this)){return true;}else{this.submit.disabled=false;return false;}">
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