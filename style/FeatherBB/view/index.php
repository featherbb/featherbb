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

if (empty($index_data)): ?>
    <div id="idx0" class="block"><div class="box"><div class="inbox"><p><?php echo $lang_index['Empty board'] ?></p></div></div></div>
<?php endif;
foreach ($index_data as $forum) {
    if ($forum->cid != $cur_cat) :
        if ($cur_cat != 0) :
    ?>
				</tbody>
			</table>
		</div>
	</div>
	</div>
	<?php endif;
    ?>
	<div id="idx<?php echo $forum->cid ?>" class="blocktable">
	<h2><span><?php echo feather_escape($forum->cat_name) ?></span></h2>
	<div class="box">
		<div class="inbox">
			<table>
			<thead>
				<tr>
					<th class="tcl" scope="col"><?php echo $lang_common['Forum'] ?></th>
					<th class="tc2" scope="col"><?php echo $lang_index['Topics'] ?></th>
					<th class="tc3" scope="col"><?php echo $lang_common['Posts'] ?></th>
					<th class="tcr" scope="col"><?php echo $lang_common['Last post'] ?></th>
				</tr>
			</thead>
			<tbody>
	<?php
    $cur_cat = $forum->cid;
    endif;
    ?>
				<tr class="<?php echo $forum->item_status ?>">
					<td class="tcl">
						<div class="<?php echo $forum->icon_type ?>"><div class="nosize"><?php echo forum_number_format($forum->forum_count_formatted) ?></div></div>
						<div class="tclcon">
							<div>
								<?php echo $forum->forum_field."\n".$forum->moderators_formatted ?>
							</div>
						</div>
					</td>
					<td class="tc2"><?php echo forum_number_format($forum->num_topics_formatted) ?></td>
					<td class="tc3"><?php echo forum_number_format($forum->num_posts_formatted) ?></td>
					<td class="tcr"><?php echo $forum->last_post_formatted ?></td>
				</tr>
	<?php

}
if ($cur_cat > 0) :
    ?>
					</tbody>
			</table>
		</div>
	</div>
	</div>
<?php
endif;
if (!empty($forum_actions)) :
?>
<div class="linksb">
	<div class="inbox crumbsplus">
		<p class="subscribelink clearb"><?php echo implode(' - ', $forum_actions); ?></p>
	</div>
</div>
<?php
endif;
?>
<div id="brdstats" class="block">
	<h2><span><?php echo $lang_index['Board info'] ?></span></h2>
	<div class="box">
		<div class="inbox">
			<dl class="conr">
				<dt><strong><?php echo $lang_index['Board stats'] ?></strong></dt>
				<dd><span><?php printf($lang_index['No of users'], '<strong>'.forum_number_format($stats['total_users']).'</strong>') ?></span></dd>
				<dd><span><?php printf($lang_index['No of topics'], '<strong>'.forum_number_format($stats['total_topics']).'</strong>') ?></span></dd>
				<dd><span><?php printf($lang_index['No of posts'], '<strong>'.forum_number_format($stats['total_posts']).'</strong>') ?></span></dd>
			</dl>
			<dl class="conl">
				<dt><strong><?php echo $lang_index['User info'] ?></strong></dt>
				<dd><span><?php printf($lang_index['Newest user'], $stats['newest_user']) ?></span></dd>
				<?php if ($feather_config['o_users_online'] == 1) : ?>
				<dd><span><?php printf($lang_index['Users online'], '<strong>'.forum_number_format($online['num_users']).'</strong>') ?></span></dd>
				<dd><span><?php printf($lang_index['Guests online'], '<strong>'.forum_number_format($online['num_guests']).'</strong>') ?></span></dd>
				<?php endif; ?>
			</dl>
			<?php
            if ($feather_config['o_users_online'] == 1) :
                if ($online['num_users'] > 0) {
                    echo "\t\t\t".'<dl id="onlinelist" class="clearb">'."\n\t\t\t\t".'<dt><strong>'.$lang_index['Online'].' </strong></dt>'."\t\t\t\t".implode(',</dd> ', $online['users']).'</dd>'."\n\t\t\t".'</dl>'."\n";
                } else {
                    echo "\t\t\t".'<div class="clearer"></div>'."\n";
                }
            endif;
            ?>
		</div>
	</div>
</div>