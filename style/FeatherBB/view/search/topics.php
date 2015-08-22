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

<tr class="<?php echo $cur_search['item_status'] ?>">
	<td class="tcl">
		<div class="<?php echo $cur_search['icon_type'] ?>"><div class="nosize"><?php echo forum_number_format($topic_count + $start_from) ?></div></div>
		<div class="tclcon">
			<div>
				<?php echo $subject."\n" ?>
			</div>
		</div>
	</td>
	<td class="tc2"><?php echo $forum ?></td>
	<td class="tc3"><?php echo forum_number_format($cur_search['num_replies']) ?></td>
	<td class="tcr"><?php echo '<a href="'.get_link('post/'.$cur_search['last_post_id'].'/#p'.$cur_search['last_post_id']).'">'.format_time($cur_search['last_post']).'</a> <span class="byuser">'.__('by').' '.feather_escape($cur_search['last_poster']) ?></span></td>
</tr>