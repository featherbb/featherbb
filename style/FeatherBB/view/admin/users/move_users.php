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

	<div class="blockform">
		<h2><span><?php echo $lang_admin_users['Move users'] ?></span></h2>
		<div class="box">
			<form name="confirm_move_users" method="post" action="<?php echo get_link('admin/users/') ?>">
				<input type="hidden" name="<?php echo $csrf_key; ?>" value="<?php echo $csrf_token; ?>">
				<input type="hidden" name="users" value="<?php echo implode(',', $move['user_ids']) ?>" />
				<div class="inform">
					<fieldset>
						<legend><?php echo $lang_admin_users['Move users subhead'] ?></legend>
						<div class="infldset">
							<table class="aligntop">
								<tr>
									<th scope="row"><?php echo $lang_admin_users['New group label'] ?></th>
									<td>
										<select name="new_group" tabindex="1">
<?php foreach ($move['all_groups'] as $gid => $group) : ?>											<option value="<?php echo $gid ?>"><?php echo feather_escape($group) ?></option>
<?php endforeach;
    ?>
										</select>
										<span><?php echo $lang_admin_users['New group help'] ?></span>
									</td>
								</tr>
							</table>
						</div>
					</fieldset>
				</div>
				<p class="submitend"><input type="submit" name="move_users_comply" value="<?php echo $lang_admin_common['Save'] ?>" tabindex="2" /></p>
			</form>
		</div>
	</div>
	<div class="clearer"></div>
</div>