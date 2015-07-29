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
			<li><a href="index.php"><?php echo $lang_common['Index'] ?></a></li>
			<li><span>»&#160;</span><a href="<?php echo get_link('forum/'.$cur_post['fid'].'/'.$cur_post['forum_name'].'/') ?>"><?php echo feather_escape($cur_post['forum_name']) ?></a></li>
			<li><span>»&#160;</span><a href="<?php echo get_link('post/'.$id.'/#p'.$id) ?>"><?php echo feather_escape($cur_post['subject']) ?></a></li>
			<li><span>»&#160;</span><strong><?php echo $lang_delete['Delete post'] ?></strong></li>
		</ul>
	</div>
</div>

<div class="blockform">
	<h2><span><?php echo $lang_delete['Delete post'] ?></span></h2>
	<div class="box">
		<form method="post" action="<?php echo get_link('delete/'.$id.'/') ?>">
			<input type="hidden" name="<?php echo $csrf_key; ?>" value="<?php echo $csrf_token; ?>">
			<div class="inform">
				<div class="forminfo">
					<h3><span><?php printf($is_topic_post ? $lang_delete['Topic by'] : $lang_delete['Reply by'], '<strong>'.feather_escape($cur_post['poster']).'</strong>', format_time($cur_post['posted'])) ?></span></h3>
					<p><?php echo($is_topic_post) ? '<strong>'.$lang_delete['Topic warning'].'</strong>' : '<strong>'.$lang_delete['Warning'].'</strong>' ?><br /><?php echo $lang_delete['Delete info'] ?></p>
				</div>
			</div>
			<p class="buttons"><input type="submit" name="delete" value="<?php echo $lang_delete['Delete'] ?>" /> <a href="javascript:history.go(-1)"><?php echo $lang_common['Go back'] ?></a></p>
		</form>
	</div>
</div>

<div id="postreview">
	<div class="blockpost">
		<div class="box">
			<div class="inbox">
				<div class="postbody">
					<div class="postleft">
						<dl>
							<dt><strong><?php echo feather_escape($cur_post['poster']) ?></strong></dt>
							<dd><span><?php echo format_time($cur_post['posted']) ?></span></dd>
						</dl>
					</div>
					<div class="postright">
						<div class="postmsg">
							<?php echo $cur_post['message']."\n" ?>
						</div>
					</div>
				</div>
				<div class="clearer"></div>
			</div>
		</div>
	</div>
</div>