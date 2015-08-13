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

<div id="searchform" class="blockform">
	<h2><span><?php echo __('Search') ?></span></h2>
	<div class="box">
		<form id="search" method="get" action="">
			<div class="inform">
				<fieldset>
					<legend><?php echo __('Search criteria legend') ?></legend>
					<div class="infldset">
						<input type="hidden" name="action" value="search" />
						<label class="conl"><?php echo __('Keyword search') ?><br /><input type="text" name="keywords" size="40" maxlength="100" /><br /></label>
						<label class="conl"><?php echo __('Author search') ?><br /><input id="author" type="text" name="author" size="25" maxlength="25" /><br /></label>
						<p class="clearb"><?php echo __('Search info') ?></p>
					</div>
				</fieldset>
			</div>
			<div class="inform">
				<fieldset>
					<legend><?php echo __('Search in legend') ?></legend>
					<div class="infldset">
					<?php echo $forums ?>
						<label class="conl"><?php echo __('Search in')."\n" ?>
						<br /><select id="search_in" name="search_in">
							<option value="0"><?php echo __('Message and subject') ?></option>
							<option value="1"><?php echo __('Message only') ?></option>
							<option value="-1"><?php echo __('Topic only') ?></option>
						</select>
						<br /></label>
						<p class="clearl"><?php echo __('Search in info') ?></p>
<?php echo($feather_config['o_search_all_forums'] == '1' || $feather->user->is_admmod ? '<p>'.__('Search multiple forums info').'</p>' : '') ?>
					</div>
				</fieldset>
			</div>
			<div class="inform">
				<fieldset>
					<legend><?php echo __('Search results legend') ?></legend>
					<div class="infldset">
						<label class="conl"><?php echo __('Sort by')."\n" ?>
						<br /><select name="sort_by">
							<option value="0"><?php echo __('Sort by post time') ?></option>
							<option value="1"><?php echo __('Sort by author') ?></option>
							<option value="2"><?php echo __('Sort by subject') ?></option>
							<option value="3"><?php echo __('Sort by forum') ?></option>
						</select>
						<br /></label>
						<label class="conl"><?php echo __('Sort order')."\n" ?>
						<br /><select name="sort_dir">
							<option value="DESC"><?php echo __('Descending') ?></option>
							<option value="ASC"><?php echo __('Ascending') ?></option>
						</select>
						<br /></label>
						<label class="conl"><?php echo __('Show as')."\n" ?>
						<br /><select name="show_as">
							<option value="topics"><?php echo __('Show as topics') ?></option>
							<option value="posts"><?php echo __('Show as posts') ?></option>
						</select>
						<br /></label>
						<p class="clearb"><?php echo __('Search results info') ?></p>
					</div>
				</fieldset>
			</div>
			<p class="buttons"><input type="submit" name="search" value="<?php echo __('Submit') ?>" accesskey="s" /></p>
		</form>
	</div>
</div>