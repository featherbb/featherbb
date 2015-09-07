<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */
 
// Make sure no one attempts to run this script "directly"
if (!defined('FEATHER')) {
    exit;
}

?>
	<div class="blockform">
		<h2><span><?php _e('Add categories head') ?></span></h2>
		<div class="box">
			<form method="post" action="<?php echo $feather->url->get('admin/categories/add/') ?>">
				<div class="inform">
					<fieldset>
						<legend><?php _e('Add categories subhead') ?></legend>
						<div class="infldset">
							<table class="aligntop">
								<tr>
									<th scope="row"><?php _e('Add category label') ?><div><input type="submit" value="<?php _e('Add new submit') ?>" tabindex="2" /></div></th>
									<td>
										<input type="hidden" name="<?php echo $csrf_key; ?>" value="<?php echo $csrf_token; ?>">
										<input type="text" name="cat_name" size="35" maxlength="80" tabindex="1" />
										<span><?php printf(__('Add category help'), '<a href="'.$feather->url->get('admin/forums').'">'.__('Forums').'</a>') ?></span>
									</td>
								</tr>
							</table>
						</div>
					</fieldset>
				</div>
			</form>
		</div>

<?php if (!empty($cat_list)): ?>		<h2 class="block2"><span><?php _e('Delete categories head') ?></span></h2>
		<div class="box">
			<form method="post" action="<?php echo $feather->url->get('admin/categories/delete') ?>">
				<input type="hidden" name="<?php echo $csrf_key; ?>" value="<?php echo $csrf_token; ?>">
				<div class="inform">
					<fieldset>
						<legend><?php _e('Delete categories subhead') ?></legend>
						<div class="infldset">
							<table class="aligntop">
								<tr>
									<th scope="row"><?php _e('Delete category label') ?><div><input type="submit" value="<?php _e('Delete') ?>" tabindex="4" /></div></th>
									<td>
										<select name="cat_to_delete" tabindex="3">
<?php

    foreach ($cat_list as $cur_cat) {
        echo "\t\t\t\t\t\t\t\t\t\t\t".'<option value="'.$cur_cat['id'].'">'.Utils::escape($cur_cat['cat_name']).'</option>'."\n";
    }

?>
										</select>
										<span style="color: red;"><input type="checkbox" name="disclaimer" value="1"> <?php _e('Delete category disclaimer') ?></span>
									</td>
								</tr>
							</table>
						</div>
					</fieldset>
				</div>
			</form>
		</div>
<?php endif; ?>

<?php if (!empty($cat_list)): ?>		<h2 class="block2"><span><?php _e('Edit categories head') ?></span></h2>
		<div class="box">
			<form method="post" action="<?php echo $feather->url->get('admin/categories/edit/') ?>">
				<input type="hidden" name="<?php echo $csrf_key; ?>" value="<?php echo $csrf_token; ?>">
				<div class="inform">
					<fieldset>
						<legend><?php _e('Edit categories subhead') ?></legend>
						<div class="infldset">
							<table id="categoryedit">
							<thead>
								<tr>
									<th class="tcl" scope="col"><?php _e('Category name label') ?></th>
									<th scope="col"><?php _e('Category position label') ?></th>
								</tr>
							</thead>
							<tbody>
<?php

    foreach ($cat_list as $cur_cat) {
        ?>
								<tr>
									<td class="tcl"><input type="text" name="cat[<?php echo $cur_cat['id'] ?>][name]" value="<?php echo Utils::escape($cur_cat['cat_name']) ?>" size="35" maxlength="80" /></td>
									<td><input type="text" name="cat[<?php echo $cur_cat['id'] ?>][order]" value="<?php echo $cur_cat['disp_position'] ?>" size="3" maxlength="3" /></td>
								</tr>
<?php

    }

?>
							</tbody>
							</table>
							<div class="fsetsubmit"><input type="submit" value="<?php _e('Update') ?>" /></div>
						</div>
					</fieldset>
				</div>
			</form>
		</div>
<?php endif; ?>	</div>
	<div class="clearer"></div>
</div>