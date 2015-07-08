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
	<div class="inbox crumbsplus">
		<ul class="crumbs">
			<li><a href="<?php echo get_base_url() ?>/"><?php echo $lang_common['Index'] ?></a></li>
			<li><span>»&#160;</span><strong><a href="<?php get_link('forum/'.$id.'/'.$url_forum.'/') ?>"><?php echo feather_htmlspecialchars($cur_forum['forum_name']) ?></a></strong></li>
		</ul>
		<div class="pagepost">
			<p class="pagelink conl"><?php echo $paging_links ?></p>
<?php echo $post_link ?>
		</div>
		<div class="clearer"></div>
	</div>
</div>

<div id="vf" class="blocktable">
	<h2><span><?php echo feather_htmlspecialchars($cur_forum['forum_name']) ?></span></h2>
	<div class="box">
		<div class="inbox">
			<table>
			<thead>
				<tr>
					<th class="tcl" scope="col"><?php echo $lang_common['Topic'] ?></th>
					<th class="tc2" scope="col"><?php echo $lang_common['Replies'] ?></th>
<?php if ($feather_config['o_topic_views'] == '1'): ?>					<th class="tc3" scope="col"><?php echo $lang_forum['Views'] ?></th>
<?php endif; ?>					<th class="tcr" scope="col"><?php echo $lang_common['Last post'] ?></th>
				</tr>
			</thead>
			<tbody>
			<?php
            $topic_count = 0;
            foreach ($forum_data as $topic) {
                ++$topic_count;
                ?>
					<tr class="<?php echo $topic['item_status'] ?>">
						<td class="tcl">
							<div class="<?php echo $topic['icon_type'] ?>"><div class="nosize"><?php echo forum_number_format($topic_count + $start_from) ?></div></div>
							<div class="tclcon">
								<div>
									<?php echo $topic['subject_formatted']."\n" ?>
								</div>
							</div>
						</td>
						<td class="tc2"><?php echo(is_null($topic['moved_to'])) ? forum_number_format($topic['num_replies']) : '-' ?></td>
	<?php if ($feather_config['o_topic_views'] == '1'): ?>					<td class="tc3"><?php echo(is_null($topic['moved_to'])) ? forum_number_format($topic['num_views']) : '-' ?></td>
	<?php endif;
                ?>					<td class="tcr"><?php echo $topic['last_post_formatted'] ?></td>
					</tr>
			<?php

            }
            if (empty($forum_data)):
            ?>
					<tr class="rowodd inone">
						<td class="tcl" colspan="<?php echo($feather_config['o_topic_views'] == 1) ? 4 : 3 ?>">
							<div class="icon inone"><div class="nosize"><!-- --></div></div>
							<div class="tclcon">
								<div>
									<strong><?php echo $lang_forum['Empty forum'] ?></strong>
								</div>
							</div>
						</td>
					</tr>
			<?php
            endif;
            ?>
			</tbody>
			</table>
		</div>
	</div>
</div>

<div class="linksb">
	<div class="inbox crumbsplus">
		<div class="pagepost">
			<p class="pagelink conl"><?php echo $paging_links ?></p>
<?php echo $post_link ?>
		</div>
		<ul class="crumbs">
			<li><a href="<?php echo get_base_url() ?>/"><?php echo $lang_common['Index'] ?></a></li>
			<li><span>»&#160;</span><strong><a href="<?php get_link('forum/'.$id.'/'.$url_forum.'/') ?>"><?php echo feather_htmlspecialchars($cur_forum['forum_name']) ?></a></strong></li>
		</ul>
<?php echo(!empty($forum_actions) ? "\t\t".'<p class="subscribelink clearb">'.implode(' - ', $forum_actions).'</p>'."\n" : '') ?>
		<div class="clearer"></div>
	</div>
</div>