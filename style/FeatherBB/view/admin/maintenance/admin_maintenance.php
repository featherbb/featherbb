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
		<h2><span><?php echo __('Maintenance head') ?></span></h2>
		<div class="box">
			<form method="get" action="<?php echo get_link('admin/maintenance/') ?>">
				<div class="inform">
					<input type="hidden" name="action" value="rebuild" />
					<fieldset>
						<legend><?php echo __('Rebuild index subhead') ?></legend>
						<div class="infldset">
							<p><?php printf(__('Rebuild index info'), '<a href="'.get_link('admin/options/#maintenance').'">'.__('Maintenance mode').'</a>') ?></p>
							<table class="aligntop">
								<tr>
									<th scope="row"><?php echo __('Posts per cycle label') ?></th>
									<td>
										<input type="text" name="i_per_page" size="7" maxlength="7" value="300" tabindex="1" />
										<span><?php echo __('Posts per cycle help') ?></span>
									</td>
								</tr>
								<tr>
									<th scope="row"><?php echo __('Starting post label') ?></th>
									<td>
										<input type="text" name="i_start_at" size="7" maxlength="7" value="<?php echo(isset($first_id)) ? $first_id : 0 ?>" tabindex="2" />
										<span><?php echo __('Starting post help') ?></span>
									</td>
								</tr>
								<tr>
									<th scope="row"><?php echo __('Empty index label') ?></th>
									<td class="inputadmin">
										<label><input type="checkbox" name="i_empty_index" value="1" tabindex="3" checked="checked" />&#160;&#160;<?php echo __('Empty index help') ?></label>
									</td>
								</tr>
							</table>
							<p class="topspace"><?php echo __('Rebuild completed info') ?></p>
							<div class="fsetsubmit"><input type="submit" name="rebuild_index" value="<?php echo __('Rebuild index') ?>" tabindex="4" /></div>
						</div>
					</fieldset>
				</div>
			</form>

			<form method="post" action="<?php echo get_link('admin/maintenance/') ?>" onsubmit="return process_form(this)">
				<input type="hidden" name="<?php echo $csrf_key; ?>" value="<?php echo $csrf_token; ?>">
				<div class="inform">
					<input type="hidden" name="action" value="prune" />
					<fieldset>
						<legend><?php echo __('Prune subhead') ?></legend>
						<div class="infldset">
							<table class="aligntop">
								<tr>
									<th scope="row"><?php echo __('Days old label') ?></th>
									<td>
										<input type="text" name="req_prune_days" size="3" maxlength="3" tabindex="5" />
										<span><?php echo __('Days old help') ?></span>
									</td>
								</tr>
								<tr>
									<th scope="row"><?php echo __('Prune sticky label') ?></th>
									<td>
										<label class="conl"><input type="radio" name="prune_sticky" value="1" tabindex="6" checked="checked" />&#160;<strong><?php echo __('Yes') ?></strong></label>
										<label class="conl"><input type="radio" name="prune_sticky" value="0" />&#160;<strong><?php echo __('No') ?></strong></label>
										<span class="clearb"><?php echo __('Prune sticky help') ?></span>
									</td>
								</tr>
								<tr>
									<th scope="row"><?php echo __('Prune from label') ?></th>
									<td>
										<select name="prune_from" tabindex="7">
											<option value="all"><?php echo __('All forums') ?></option>
												<?php echo $categories; ?>
											</optgroup>
										</select>
										<span><?php echo __('Prune from help') ?></span>
									</td>
								</tr>
							</table>
							<p class="topspace"><?php printf(__('Prune info'), '<a href="'.get_link('admin/options/#maintenance').'">'.__('Maintenance mode').'</a>') ?></p>
							<div class="fsetsubmit"><input type="submit" name="prune" value="<?php echo __('Prune') ?>" tabindex="8" /></div>
						</div>
					</fieldset>
				</div>
			</form>
		</div>
	</div>
	<div class="clearer"></div>
</div>