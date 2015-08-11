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
			<li><a href="<?php echo get_base_url() ?>"><?php echo $lang_common['Index'] ?></a></li>
			<li><span>»&#160;</span><a href="<?php echo get_link('forum/'.$id.'/'.$url_forum.'/') ?>"><?php echo feather_escape($cur_forum['forum_name']) ?></a></li>
			<li><span>»&#160;</span><strong><?php echo $lang_misc['Moderate'] ?></strong></li>
		</ul>
		<div class="pagepost">
			<p class="pagelink conl"><?php echo $paging_links ?></p>
		</div>
		<div class="clearer"></div>
	</div>
</div>

<form method="post" action="<?php echo get_link('moderate/forum/'.$id.'/') ?>">
<input type="hidden" name="<?php echo $csrf_key; ?>" value="<?php echo $csrf_token; ?>">
<input type="hidden" name="page" value="<?php echo feather_escape($p) ?>" />
<div id="vf" class="blocktable">
	<h2><span><?php echo feather_escape($cur_forum['forum_name']) ?></span></h2>
	<div class="box">
		<div class="inbox">
			<table>
			<thead>
				<tr>
					<th class="tcl" scope="col"><?php echo $lang_common['Topic'] ?></th>
					<th class="tc2" scope="col"><?php echo $lang_common['Replies'] ?></th>
<?php if ($feather_config['o_topic_views'] == '1'): ?>					<th class="tc3" scope="col"><?php echo $lang_forum['Views'] ?></th>
<?php endif; ?>					<th class="tcr"><?php echo $lang_common['Last post'] ?></th>
					<th class="tcmod" scope="col"><?php echo $lang_misc['Select'] ?></th>
				</tr>
			</thead>
			<tbody>
			
			<?php
            $topic_count = 0;
            $button_status = '';
            foreach ($topic_data as $topic) {
                ++$topic_count;
                ?>
							<tr class="<?php echo $topic['item_status'] ?>">
					<td class="tcl">
						<div class="<?php echo $topic['icon_type'] ?>"><div class="nosize"><?php echo forum_number_format($topic_count + $start_from) ?></div></div>
						<div class="tclcon">
							<div>
								<?php echo $topic['subject_disp']."\n" ?>
							</div>
						</div>
					</td>
					<td class="tc2"><?php echo(!$topic['ghost_topic']) ? forum_number_format($topic['num_replies']) : '-' ?></td>
<?php if ($feather_config['o_topic_views'] == '1'): ?>					<td class="tc3"><?php echo(!$topic['ghost_topic']) ? forum_number_format($topic['num_views']) : '-' ?></td>
<?php endif;
                ?>					<td class="tcr"><?php echo $topic['last_post_disp'] ?></td>
					<td class="tcmod"><input type="checkbox" name="topics[<?php echo $topic['id'] ?>]" value="1" /></td>
				</tr>
			<?php

            }
            if (empty($topic_data)):
                $colspan = ($feather_config['o_topic_views'] == '1') ? 5 : 4;
                $button_status = ' disabled="disabled"';
                echo "\t\t\t\t\t".'<tr><td class="tcl" colspan="'.$colspan.'">'.$lang_forum['Empty forum'].'</td></tr>'."\n";
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
			<p class="conr modbuttons"><input type="submit" name="move_topics" value="<?php echo $lang_misc['Move'] ?>"<?php echo $button_status ?> /> <input type="submit" name="delete_topics" value="<?php echo $lang_misc['Delete'] ?>"<?php echo $button_status ?> /> <input type="submit" name="merge_topics" value="<?php echo $lang_misc['Merge'] ?>"<?php echo $button_status ?> /> <input type="submit" name="open" value="<?php echo $lang_misc['Open'] ?>"<?php echo $button_status ?> /> <input type="submit" name="close" value="<?php echo $lang_misc['Close'] ?>"<?php echo $button_status ?> /></p>
			<div class="clearer"></div>
		</div>
		<ul class="crumbs">
			<li><a href="<?php echo get_base_url() ?>"><?php echo $lang_common['Index'] ?></a></li>
			<li><span>»&#160;</span><a href="<?php echo get_link('forum/'.$id.'/'.$url_forum.'/') ?>"><?php echo feather_escape($cur_forum['forum_name']) ?></a></li>
			<li><span>»&#160;</span><strong><?php echo $lang_misc['Moderate'] ?></strong></li>
		</ul>
		<div class="clearer"></div>
	</div>
</div>
</form>