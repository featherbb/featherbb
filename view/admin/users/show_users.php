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
			<li><a href="<?php echo get_link('admin/') ?>"><?php _e('Admin').' '.__('Index') ?></a></li>
			<li><span>»&#160;</span><a href="<?php echo get_link('admin/users/') ?>"><?php _e('Users') ?></a></li>
			<li><span>»&#160;</span><strong><?php _e('Results head') ?></strong></li>
		</ul>
		<div class="pagepost">
			<p class="pagelink"><?php echo $paging_links ?></p>
		</div>
		<div class="clearer"></div>
	</div>
</div>

<div id="users2" class="blocktable">
	<h2><span><?php _e('Results head') ?></span></h2>
	<div class="box">
		<div class="inbox">
			<table>
			<thead>
				<tr>
					<th class="tcl" scope="col"><?php _e('Results username head') ?></th>
					<th class="tc2" scope="col"><?php _e('Results e-mail head') ?></th>
					<th class="tc3" scope="col"><?php _e('Results title head') ?></th>
					<th class="tc4" scope="col"><?php _e('Results posts head') ?></th>
					<th class="tc5" scope="col"><?php _e('Results admin note head') ?></th>
					<th class="tcr" scope="col"><?php _e('Results actions head') ?></th>
				</tr>
			</thead>
			<tbody>
<?php
    if ($info['num_posts']) {

        // Loop through users and print out some info
        foreach ($info['posters'] as $cur_poster) {
            if (isset($info['user_data'][$cur_poster['poster_id']])) {
                ?>
				<tr>
					<td class="tcl"><?php echo '<a href="'.get_link('user/'.$info['user_data'][$cur_poster['poster_id']]['id'].'/').'">'.feather_escape($info['user_data'][$cur_poster['poster_id']]['username']).'</a>' ?></td>
					<td class="tc2"><a href="mailto:<?php echo feather_escape($info['user_data'][$cur_poster['poster_id']]['email']) ?>"><?php echo feather_escape($info['user_data'][$cur_poster['poster_id']]['email']) ?></a></td>
					<td class="tc3"><?php echo get_title($info['user_data'][$cur_poster['poster_id']]) ?></td>
					<td class="tc4"><?php echo forum_number_format($info['user_data'][$cur_poster['poster_id']]['num_posts']) ?></td>
					<td class="tc5"><?php echo($info['user_data'][$cur_poster['poster_id']]['admin_note'] != '') ? feather_escape($info['user_data'][$cur_poster['poster_id']]['admin_note']) : '&#160;' ?></td>
					<td class="tcr"><?php echo '<a href="'.get_link('admin/users/ip-stats/id/'.$info['user_data'][$cur_poster['poster_id']]['id'].'/').'">'.__('Results view IP link').'</a> | <a href="search.php?action=show_user_posts&amp;user_id='.$info['user_data'][$cur_poster['poster_id']]['id'].'">'.__('Results show posts link').'</a>' ?></td>
				</tr>
<?php

            } else {
                ?>
				<tr>
					<td class="tcl"><?php echo feather_escape($cur_poster['poster']) ?></td>
					<td class="tc2">&#160;</td>
					<td class="tc3"><?php _e('Results guest') ?></td>
					<td class="tc4">&#160;</td>
					<td class="tc5">&#160;</td>
					<td class="tcr">&#160;</td>
				</tr>
<?php

            }
        }
    } else {
        echo "\t\t\t\t".'<tr><td class="tcl" colspan="6">'.__('Results no IP found').'</td></tr>'."\n";
    }

    ?>
			</tbody>
			</table>
		</div>
	</div>
</div>

<div class="linksb">
	<div class="inbox crumbsplus">
		<div class="pagepost">
			<p class="pagelink"><?php echo $paging_links ?></p>
		</div>
		<ul class="crumbs">
			<li><a href="<?php echo get_link('admin/') ?>"><?php _e('Admin').' '.__('Index') ?></a></li>
			<li><span>»&#160;</span><a href="<?php echo get_link('admin/users/') ?>"><?php _e('Users') ?></a></li>
			<li><span>»&#160;</span><strong><?php _e('Results head') ?></strong></li>
		</ul>
		<div class="clearer"></div>
	</div>
</div>