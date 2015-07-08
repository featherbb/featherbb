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
<div class="blockpost<?php echo($post_count % 2 == 0) ? ' roweven' : ' rowodd' ?><?php if ($cur_search['pid'] == $cur_search['first_post_id']) {
    echo ' firstpost';
} ?><?php if ($post_count == 1) {
    echo ' blockpost1';
} ?><?php if ($cur_search['item_status'] != '') {
    echo ' '.$cur_search['item_status'];
} ?>">
<h2><span><span class="conr">#<?php echo($search['start_from'] + $post_count) ?></span> <span><?php if ($cur_search['pid'] != $cur_search['first_post_id']) {
    echo $lang_topic['Re'].' ';
} ?><?php echo $forum ?></span> <span>»&#160;<a href="<?php echo get_link('topic/'.$cur_search['tid'].'/'.$url_topic.'/') ?>"><?php echo feather_htmlspecialchars($cur_search['subject']) ?></a></span> <span>»&#160;<a href="<?php echo get_link('post/'.$cur_search['pid'].'/#p'.$cur_search['pid']) ?>"><?php echo format_time($cur_search['pposted']) ?></a></span></span></h2>
<div class="box">
	<div class="inbox">
		<div class="postbody">
			<div class="postleft">
				<dl>
					<dt><?php echo $cur_search['pposter_disp'] ?></dt>
<?php if ($cur_search['pid'] == $cur_search['first_post_id']) : ?>						<dd><span><?php echo $lang_topic['Replies'].' '.forum_number_format($cur_search['num_replies']) ?></span></dd>
<?php endif; ?>
					<dd><div class="<?php echo $cur_search['icon_type'] ?>"><div class="nosize"><?php echo $cur_search['icon_text'] ?></div></div></dd>
				</dl>
			</div>
			<div class="postright">
				<div class="postmsg">
					<?php echo $cur_search['message']."\n" ?>
				</div>
			</div>
			<div class="clearer"></div>
		</div>
	</div>
	<div class="inbox">
		<div class="postfoot clearb">
			<div class="postfootright">
				<ul>
					<li><span><a href="<?php echo get_link('topic/'.$cur_search['tid'].'/'.$url_topic.'/') ?>"><?php echo $lang_search['Go to topic'] ?></a></span></li>
					<li><span><a href="<?php echo get_link('post/'.$cur_search['pid'].'/#p'.$cur_search['pid']) ?>"><?php echo $lang_search['Go to post'] ?></a></span></li>
				</ul>
			</div>
		</div>
	</div>
</div>
</div>