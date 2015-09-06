<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */
 
use FeatherBB\Core\Error;
use FeatherBB\Core\Utils;
use FeatherBB\Core\Url;

// Make sure no one attempts to run this script "directly"
if (!defined('FEATHER')) {
    exit;
}
?>

	<div class="blockform">
		<h2><span><?php _e('Maintenance head') ?></span></h2>
		<div class="box">
			<form method="get" action="<?php echo Url::get('admin/maintenance/') ?>">
				<div class="inform">
					<input type="hidden" name="action" value="rebuild" />
					<fieldset>
						<legend><?php _e('Rebuild index subhead') ?></legend>
						<div class="infldset">
							<p><?php printf(__('Rebuild index info'), '<a href="'.Url::get('admin/options/#maintenance').'">'.__('Maintenance mode').'</a>') ?></p>
							<table class="aligntop">
								<tr>
									<th scope="row"><?php _e('Posts per cycle label') ?></th>
									<td>
										<input type="text" name="i_per_page" size="7" maxlength="7" value="300" tabindex="1" />
										<span><?php _e('Posts per cycle help') ?></span>
									</td>
								</tr>
								<tr>
									<th scope="row"><?php _e('Starting post label') ?></th>
									<td>
										<input type="text" name="i_start_at" size="7" maxlength="7" value="<?php echo(isset($first_id)) ? $first_id : 0 ?>" tabindex="2" />
										<span><?php _e('Starting post help') ?></span>
									</td>
								</tr>
								<tr>
									<th scope="row"><?php _e('Empty index label') ?></th>
									<td class="inputadmin">
										<label><input type="checkbox" name="i_empty_index" value="1" tabindex="3" checked="checked" />&#160;&#160;<?php _e('Empty index help') ?></label>
									</td>
								</tr>
							</table>
							<p class="topspace"><?php _e('Rebuild completed info') ?></p>
							<div class="fsetsubmit"><input type="submit" name="rebuild_index" value="<?php _e('Rebuild index') ?>" tabindex="4" /></div>
						</div>
					</fieldset>
				</div>
			</form>

			<form method="post" action="<?php echo Url::get('admin/maintenance/') ?>" onsubmit="return process_form(this)">
				<input type="hidden" name="<?php echo $csrf_key; ?>" value="<?php echo $csrf_token; ?>">
				<div class="inform">
					<input type="hidden" name="action" value="prune" />
					<fieldset>
						<legend><?php _e('Prune subhead') ?></legend>
						<div class="infldset">
							<table class="aligntop">
								<tr>
									<th scope="row"><?php _e('Days old label') ?></th>
									<td>
										<input type="text" name="req_prune_days" size="3" maxlength="3" tabindex="5" />
										<span><?php _e('Days old help') ?></span>
									</td>
								</tr>
								<tr>
									<th scope="row"><?php _e('Prune sticky label') ?></th>
									<td>
										<label class="conl"><input type="radio" name="prune_sticky" value="1" tabindex="6" checked="checked" />&#160;<strong><?php _e('Yes') ?></strong></label>
										<label class="conl"><input type="radio" name="prune_sticky" value="0" />&#160;<strong><?php _e('No') ?></strong></label>
										<span class="clearb"><?php _e('Prune sticky help') ?></span>
									</td>
								</tr>
								<tr>
									<th scope="row"><?php _e('Prune from label') ?></th>
									<td>
										<select name="prune_from" tabindex="7">
											<option value="all"><?php _e('All forums') ?></option>
												<?php echo $categories; ?>
											</optgroup>
										</select>
										<span><?php _e('Prune from help') ?></span>
									</td>
								</tr>
							</table>
							<p class="topspace"><?php printf(__('Prune info'), '<a href="'.Url::get('admin/options/#maintenance').'">'.__('Maintenance mode').'</a>') ?></p>
							<div class="fsetsubmit"><input type="submit" name="prune" value="<?php _e('Prune') ?>" tabindex="8" /></div>
						</div>
					</fieldset>
				</div>
			</form>
		</div>
	</div>
	<div class="clearer"></div>
</div>