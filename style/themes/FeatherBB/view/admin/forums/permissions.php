<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */
 
use FeatherBB\Utils;

// Make sure no one attempts to run this script "directly"
if (!defined('FEATHER')) {
    exit;
}
?>

	<div class="blockform">
		<h2><span><?php _e('Edit forum head') ?></span></h2>
		<div class="box">
			<form id="edit_forum" method="post" action="<?php echo $feather->url->get('admin/forums/edit/'.$cur_forum['id'].'/') ?>">
				<input type="hidden" name="<?php echo $csrf_key; ?>" value="<?php echo $csrf_token; ?>">
				<p class="submittop"><input type="submit" name="save" value="<?php _e('Save changes') ?>" tabindex="6" /></p>
				<div class="inform">
					<fieldset>
						<legend><?php _e('Edit details subhead') ?></legend>
						<div class="infldset">
							<table class="aligntop">
								<tr>
									<th scope="row"><?php _e('Forum name label') ?></th>
									<td><input type="text" name="forum_name" size="35" maxlength="80" value="<?php echo Utils::escape($cur_forum['forum_name']) ?>" tabindex="1" /></td>
								</tr>
								<tr>
									<th scope="row"><?php _e('Forum description label') ?></th>
									<td><textarea name="forum_desc" rows="3" cols="50" tabindex="2"><?php echo Utils::escape($cur_forum['forum_desc']) ?></textarea></td>
								</tr>
								<tr>
									<th scope="row"><?php _e('Category label') ?></th>
									<td>
										<select name="cat_id" tabindex="3">
											<?php  foreach ($forum_data as $cat_id => $cat_data) {
												$selected = ($cat_id == $cur_forum['cat_id']) ? 'selected="selected"' : '';
												echo "\t\t\t\t\t\t\t\t\t\t\t".'<option value="'.$cat_id.'" '.$selected.'>'.Utils::escape($cat_data['cat_name']).'</option>'."\n";
											} ?>
										</select>
									</td>
								</tr>
								<tr>
									<th scope="row"><?php _e('Sort by label') ?></th>
									<td>
										<select name="sort_by" tabindex="4">
											<option value="0"<?php if ($cur_forum['sort_by'] == '0') {
    echo ' selected="selected"';
} ?>><?php _e('Last post') ?></option>
											<option value="1"<?php if ($cur_forum['sort_by'] == '1') {
    echo ' selected="selected"';
} ?>><?php _e('Topic start') ?></option>
											<option value="2"<?php if ($cur_forum['sort_by'] == '2') {
    echo ' selected="selected"';
} ?>><?php _e('Subject') ?></option>
										</select>
									</td>
								</tr>
								<tr>
									<th scope="row"><?php _e('Redirect label') ?></th>
									<td><?php echo(!empty($cur_forum['num_topics'])) ? __('Redirect help') : '<input type="text" name="redirect_url" size="45" maxlength="100" value="'.Utils::escape($cur_forum['redirect_url']).'" tabindex="5" />'; ?></td>
								</tr>
							</table>
						</div>
					</fieldset>
				</div>
				<div class="inform">
					<fieldset>
						<legend><?php _e('Group permissions subhead') ?></legend>
						<div class="infldset">
							<p><?php printf(__('Group permissions info'), '<a href="admin_groups.php">'.__('User groups').'</a>') ?></p>
							<table id="forumperms">
							<thead>
								<tr>
									<th class="atcl">&#160;</th>
									<th><?php _e('Read forum label') ?></th>
									<th><?php _e('Post replies label') ?></th>
									<th><?php _e('Post topics label') ?></th>
								</tr>
							</thead>
							<tbody>
<?php
    foreach ($perm_data as $perm) {
        ?>
								<tr>
									<th class="atcl"><?php echo Utils::escape($perm['g_title']) ?></th>
									<td<?php if (!$perm['read_forum_def']) {
    echo ' class="nodefault"';
}
        ?>>
										<input type="hidden" name="read_forum_old[<?php echo $perm['g_id'] ?>]" value="<?php echo($perm['read_forum']) ? '1' : '0';
        ?>" />
										<input type="checkbox" name="read_forum_new[<?php echo $perm['g_id'] ?>]" value="1"<?php echo($perm['read_forum']) ? ' checked="checked"' : '';
        ?><?php echo($perm['g_read_board'] == '0') ? ' disabled="disabled"' : '';
        ?> tabindex="<?php echo $cur_index++ ?>" />
									</td>
									<td<?php if (!$perm['post_replies_def'] && $cur_forum['redirect_url'] == '') {
    echo ' class="nodefault"';
}
        ?>>
										<input type="hidden" name="post_replies_old[<?php echo $perm['g_id'] ?>]" value="<?php echo($perm['post_replies']) ? '1' : '0';
        ?>" />
										<input type="checkbox" name="post_replies_new[<?php echo $perm['g_id'] ?>]" value="1"<?php echo($perm['post_replies']) ? ' checked="checked"' : '';
        ?><?php echo($cur_forum['redirect_url'] != '') ? ' disabled="disabled"' : '';
        ?> tabindex="<?php echo $cur_index++ ?>" />
									</td>
									<td<?php if (!$perm['post_topics_def'] && $cur_forum['redirect_url'] == '') {
    echo ' class="nodefault"';
}
        ?>>
										<input type="hidden" name="post_topics_old[<?php echo $perm['g_id'] ?>]" value="<?php echo($perm['post_topics']) ? '1' : '0';
        ?>" />
										<input type="checkbox" name="post_topics_new[<?php echo $perm['g_id'] ?>]" value="1"<?php echo($perm['post_topics']) ? ' checked="checked"' : '';
        ?><?php echo($cur_forum['redirect_url'] != '') ? ' disabled="disabled"' : '';
        ?> tabindex="<?php echo $cur_index++ ?>" />
									</td>
								</tr>
<?php

    }

?>
							</tbody>
							</table>
							<div class="fsetsubmit"><input type="submit" name="revert_perms" value="<?php _e('Revert to default') ?>" tabindex="<?php echo $cur_index++ ?>" /></div>
						</div>
					</fieldset>
				</div>
				<p class="submitend"><input type="submit" name="save" value="<?php _e('Save changes') ?>" tabindex="<?php echo $cur_index++ ?>" /></p>
			</form>
		</div>
	</div>
	<div class="clearer"></div>
</div>