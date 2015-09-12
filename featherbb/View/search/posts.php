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

<?php
$post_count = 0;
foreach ($search_results as $cur_search) {
    ++$post_count;
    // var_dump($cur_search);
    ?>
<div class="blockpost<?php echo($post_count % 2 == 0) ? ' roweven' : ' rowodd' ?><?php if ($cur_search['pid'] == $cur_search['first_post_id']) {
    echo ' firstpost';
} ?><?php if ($post_count == 1) {
    echo ' blockpost1';
} ?><?php if ($cur_search['item_status'] != '') {
    echo ' '.$cur_search['item_status'];
} ?>">
<h2><span><span class="conr">#<?php echo($search['start_from'] + $post_count) ?></span> <span><?php if ($cur_search['pid'] != $cur_search['first_post_id']) {
    _e('Re').' ';
} ?><?php echo $cur_search['forum'] ?></span> <span>»&#160;<a href="<?php echo $feather->urlFor('Topic', ['id' => $cur_search['tid'], 'name' => $cur_search['url_topic']]) ?>"><?php echo Utils::escape($cur_search['subject']) ?></a></span> <span>»&#160;<a href="<?php echo $feather->urlFor('viewPost', ['pid' => $cur_search['pid']]).'#p'.$cur_search['pid'] ?>"><?php echo $feather->utils->format_time($cur_search['pposted']) ?></a></span></span></h2>
<div class="box">
	<div class="inbox">
		<div class="postbody">
			<div class="postleft">
				<dl>
					<dt><?php echo $cur_search['pposter_disp'] ?></dt>
<?php if ($cur_search['pid'] == $cur_search['first_post_id']) : ?>						<dd><span><?php _e('Replies').' '.Utils::forum_number_format($cur_search['num_replies']) ?></span></dd>
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
					<li><span><a href="<?php echo $feather->urlFor('Topic', ['id' => $cur_search['tid'], 'name' => $cur_search['url_topic']]) ?>"><?php _e('Go to topic') ?></a></span></li>
					<li><span><a href="<?php echo $feather->urlFor('viewPost', ['pid' => $cur_search['pid']]).'#p'.$cur_search['pid'] ?>"><?php _e('Go to post') ?></a></span></li>
				</ul>
			</div>
		</div>
	</div>
</div>
</div>
<?php } ?>
