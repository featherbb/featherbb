<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

use FeatherBB\Core\Utils;

// Make sure no one attempts to run this script "directly"
if (!isset($feather)) {
    exit;
}

if (empty($index_data)): ?>
    <div id="idx0" class="block"><div class="box"><div class="inbox"><p><?php _e('Empty board') ?></p></div></div></div>
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
	<h2><span><?php echo Utils::escape($forum->cat_name) ?></span></h2>
	<div class="box">
		<div class="inbox">
			<table>
			<thead>
				<tr>
					<th class="tcl" scope="col"><?php _e('Forum') ?></th>
					<th class="tc2" scope="col"><?php _e('Topics') ?></th>
					<th class="tc3" scope="col"><?php _e('Posts') ?></th>
					<th class="tcr" scope="col"><?php _e('Last post') ?></th>
				</tr>
			</thead>
			<tbody>
	<?php
    $cur_cat = $forum->cid;
    endif;
    ?>
				<tr class="<?php echo $forum->item_status ?>">
					<td class="tcl">
						<div class="<?php echo $forum->icon_type ?>"><div class="nosize"><?php echo Utils::forum_number_format($forum->forum_count_formatted) ?></div></div>
						<div class="tclcon">
							<div>
								<?php echo $forum->forum_field."\n".$forum->moderators_formatted ?>
							</div>
						</div>
					</td>
					<td class="tc2"><?php echo Utils::forum_number_format($forum->num_topics_formatted) ?></td>
					<td class="tc3"><?php echo Utils::forum_number_format($forum->num_posts_formatted) ?></td>
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
	<h2><span><?php _e('Board info') ?></span></h2>
	<div class="box">
		<div class="inbox">
			<dl class="conr">
				<dt><strong><?php _e('Board stats') ?></strong></dt>
				<dd><span><?php printf(__('No of users'), '<strong>'.Utils::forum_number_format($stats['total_users']).'</strong>') ?></span></dd>
				<dd><span><?php printf(__('No of topics'), '<strong>'.Utils::forum_number_format($stats['total_topics']).'</strong>') ?></span></dd>
				<dd><span><?php printf(__('No of posts'), '<strong>'.Utils::forum_number_format($stats['total_posts']).'</strong>') ?></span></dd>
			</dl>
			<dl class="conl">
				<dt><strong><?php _e('User info') ?></strong></dt>
				<dd><span><?php printf(__('Newest user'), $stats['newest_user']) ?></span></dd>
				<?php if ($feather->forum_settings['o_users_online'] == 1) : ?>
				<dd><span><?php printf(__('Users online'), '<strong>'.Utils::forum_number_format($online['num_users']).'</strong>') ?></span></dd>
				<dd><span><?php printf(__('Guests online'), '<strong>'.Utils::forum_number_format($online['num_guests']).'</strong>') ?></span></dd>
				<?php endif; ?>
			</dl>
			<?php
            if ($feather->forum_settings['o_users_online'] == 1) :
                if ($online['num_users'] > 0) {
                    echo "\t\t\t".'<dl id="onlinelist" class="clearb">'."\n\t\t\t\t".'<dt><strong>'.__('Online').' </strong></dt>'."\t\t\t\t".implode(',</dd> ', $online['users']).'</dd>'."\n\t\t\t".'</dl>'."\n";
                } else {
                    echo "\t\t\t".'<div class="clearer"></div>'."\n";
                }
            endif;
            ?>
		</div>
	</div>
</div>
