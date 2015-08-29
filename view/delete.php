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
			<li><a href="<?php echo $feather->url->base() ?>>"><?php _e('Index') ?></a></li>
			<li><span>»&#160;</span><a href="<?php echo $feather->url->get('forum/'.$cur_post['fid'].'/'.$cur_post['forum_name'].'/') ?>"><?php echo $feather->utils->escape($cur_post['forum_name']) ?></a></li>
			<li><span>»&#160;</span><a href="<?php echo $feather->url->get('post/'.$id.'/#p'.$id) ?>"><?php echo $feather->utils->escape($cur_post['subject']) ?></a></li>
			<li><span>»&#160;</span><strong><?php _e('Delete post') ?></strong></li>
		</ul>
	</div>
</div>

<div class="blockform">
	<h2><span><?php _e('Delete post') ?></span></h2>
	<div class="box">
		<form method="post" action="<?php echo $feather->url->get('delete/'.$id.'/') ?>">
			<input type="hidden" name="<?php echo $csrf_key; ?>" value="<?php echo $csrf_token; ?>">
			<div class="inform">
				<div class="forminfo">
					<h3><span><?php printf($is_topic_post ? __('Topic by') : __('Reply by'), '<strong>'.$feather->utils->escape($cur_post['poster']).'</strong>', $feather->utils->format_time($cur_post['posted'])) ?></span></h3>
					<p><?php echo($is_topic_post) ? '<strong>'.__('Topic warning').'</strong>' : '<strong>'.__('Warning').'</strong>' ?><br /><?php _e('Delete info') ?></p>
				</div>
			</div>
			<p class="buttons"><input type="submit" name="delete" value="<?php _e('Delete') ?>" /> <a href="javascript:history.go(-1)"><?php _e('Go back') ?></a></p>
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
							<dt><strong><?php echo $feather->utils->escape($cur_post['poster']) ?></strong></dt>
							<dd><span><?php echo $feather->utils->format_time($cur_post['posted']) ?></span></dd>
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