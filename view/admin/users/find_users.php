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
			<li><a href="<?php echo $feather->url->get('admin/index/') ?>"><?php _e('Admin').' '.__('Index') ?></a></li>
			<li><span>»&#160;</span><a href="<?php echo $feather->url->get('admin/users/') ?>"><?php _e('Users') ?></a></li>
			<li><span>»&#160;</span><strong><?php _e('Results head') ?></strong></li>
		</ul>
		<div class="pagepost">
			<p class="pagelink"><?php echo $paging_links ?></p>
		</div>
		<div class="clearer"></div>
	</div>
</div>


<form id="search-users-form" action="<?php echo $feather->url->get('admin/users/') ?>" method="post">
<input type="hidden" name="<?php echo $csrf_key; ?>" value="<?php echo $csrf_token; ?>">
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
<?php if ($can_action): ?>					<th class="tcmod" scope="col"><?php _e('Select') ?></th>
<?php endif;
    ?>
				</tr>
			</thead>
			<tbody>
<?php
    if (!empty($user_data)) {
        foreach ($user_data as $user) {
            ?>
				<tr>
					<td class="tcl"><?php echo '<a href="'.$feather->url->get('user/'.$user['id'].'/').'">'.feather_escape($user['username']).'</a>' ?></td>
					<td class="tc2"><a href="mailto:<?php echo feather_escape($user['email']) ?>"><?php echo feather_escape($user['email']) ?></a></td>
					<td class="tc3"><?php echo $user['user_title'] ?></td>
					<td class="tc4"><?php echo forum_number_format($user['num_posts']) ?></td>
					<td class="tc5"><?php echo($user['admin_note'] != '') ? feather_escape($user['admin_note']) : '&#160;' ?></td>
					<td class="tcr"><?php echo '<a href="'.$feather->url->get('admin/users/ip-stats/id/'.$user['id'].'/').'">'.__('Results view IP link').'</a> | <a href="'.$feather->url->get('search/?action=show_user_posts&amp;user_id='.$user['id']).'">'.__('Results show posts link').'</a>' ?></td>
<?php if ($can_action): ?>					<td class="tcmod"><input type="checkbox" name="users[<?php echo $user['id'] ?>]" value="1" /></td>
<?php endif;
            ?>
				</tr>
<?php

        }
    } else {
        echo "\t\t\t\t".'<tr><td class="tcl" colspan="6">'.__('No match').'</td></tr>'."\n";
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
<?php if ($can_action): ?>			<p class="conr modbuttons"><a href="#" onclick="return select_checkboxes('search-users-form', this, '<?php _e('Unselect all') ?>')"><?php _e('Select all') ?></a> <?php if ($can_ban) : ?><input type="submit" name="ban_users" value="<?php _e('Ban') ?>" /><?php endif;
    if ($can_delete) : ?><input type="submit" name="delete_users" value="<?php _e('Delete') ?>" /><?php endif;
    if ($can_move) : ?><input type="submit" name="move_users" value="<?php _e('Change group') ?>" /><?php endif;
    ?></p>
<?php endif;
    ?>
		</div>
		<ul class="crumbs">
			<li><a href="<?php echo $feather->url->get('admin/index/') ?>"><?php _e('Admin').' '.__('Index') ?></a></li>
			<li><span>»&#160;</span><a href="<?php echo $feather->url->get('admin/users/') ?>"><?php _e('Users') ?></a></li>
			<li><span>»&#160;</span><strong><?php _e('Results head') ?></strong></li>
		</ul>
		<div class="clearer"></div>
	</div>
</div>
</form>
