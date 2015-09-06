<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */
<<<<<<< HEAD
=======
 
use FeatherBB\Core\Utils;
use FeatherBB\Core\Url;
>>>>>>> development

// Make sure no one attempts to run this script "directly"
if (!defined('FEATHER')) {
    exit;
}
?>

<div class="linkst">
	<div class="inbox">
		<ul class="crumbs">
<<<<<<< HEAD
			<li><a href="<?= $feather->url->base() ?>"><?php _e('Index') ?></a></li>
			<li><span>»&#160;</span><a href="<?= $feather->urlFor('viewForum', array('id'=>$cur_post['fid'], 'name'=>$feather->url->url_friendly($cur_post['forum_name']),'page'=>'1')) ?>"><?= $feather->utils->escape($cur_post['forum_name']) ?></a></li>
			<li><span>»&#160;</span><a href="<?= $feather->urlFor('viewPost', array('pid'=>$id.'/#p'.$id)) ?>"><?= $feather->utils->escape($cur_post['subject']) ?></a></li>
=======
			<li><a href="<?php echo Url::base() ?>>"><?php _e('Index') ?></a></li>
			<li><span>»&#160;</span><a href="<?php echo Url::get('forum/'.$cur_post['fid'].'/'.$cur_post['forum_name'].'/') ?>"><?php echo Utils::escape($cur_post['forum_name']) ?></a></li>
			<li><span>»&#160;</span><a href="<?php echo Url::get('post/'.$id.'/#p'.$id) ?>"><?php echo Utils::escape($cur_post['subject']) ?></a></li>
>>>>>>> development
			<li><span>»&#160;</span><strong><?php _e('Delete post') ?></strong></li>
		</ul>
	</div>
</div>

<div class="blockform">
	<h2><span><?php _e('Delete post') ?></span></h2>
	<div class="box">
<<<<<<< HEAD
		<form method="post" action="<?= $feather->urlFor('deletePost', array('id'=>$id)) ?>">
			<input type="hidden" name="<?= $csrf_key; ?>" value="<?= $csrf_token; ?>">
			<div class="inform">
				<div class="forminfo">
					<h3><span><?php printf($is_topic_post ? __('Topic by') : __('Reply by'), '<strong>'.$feather->utils->escape($cur_post['poster']).'</strong>', $feather->utils->format_time($cur_post['posted'])) ?></span></h3>
					<p><?= ($is_topic_post) ? '<strong>'.__('Topic warning').'</strong>' : '<strong>'.__('Warning').'</strong>' ?><br /><?php _e('Delete info') ?></p>
=======
		<form method="post" action="<?php echo Url::get('delete/'.$id.'/') ?>">
			<input type="hidden" name="<?php echo $csrf_key; ?>" value="<?php echo $csrf_token; ?>">
			<div class="inform">
				<div class="forminfo">
					<h3><span><?php printf($is_topic_post ? __('Topic by') : __('Reply by'), '<strong>'.Utils::escape($cur_post['poster']).'</strong>', $feather->utils->format_time($cur_post['posted'])) ?></span></h3>
					<p><?php echo($is_topic_post) ? '<strong>'.__('Topic warning').'</strong>' : '<strong>'.__('Warning').'</strong>' ?><br /><?php _e('Delete info') ?></p>
>>>>>>> development
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
<<<<<<< HEAD
							<dt><strong><?= $feather->utils->escape($cur_post['poster']) ?></strong></dt>
							<dd><span><?= $feather->utils->format_time($cur_post['posted']) ?></span></dd>
=======
							<dt><strong><?php echo Utils::escape($cur_post['poster']) ?></strong></dt>
							<dd><span><?php echo $feather->utils->format_time($cur_post['posted']) ?></span></dd>
>>>>>>> development
						</dl>
					</div>
					<div class="postright">
						<div class="postmsg">
							<?= $cur_post['message']."\n" ?>
						</div>
					</div>
				</div>
				<div class="clearer"></div>
			</div>
		</div>
	</div>
</div>
