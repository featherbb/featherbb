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

	<div class="block">
		<h2><span><?php echo __('Forum admin head') ?></span></h2>
		<div id="adintro" class="box">
			<div class="inbox">
				<p><?php echo __('Welcome to admin') ?></p>
				<ul>
					<li><span><?php echo __('Welcome 1') ?></span></li>
					<li><span><?php echo __('Welcome 2') ?></span></li>
					<li><span><?php echo __('Welcome 3') ?></span></li>
					<li><span><?php echo __('Welcome 4') ?></span></li>
					<li><span><?php echo __('Welcome 5') ?></span></li>
					<li><span><?php echo __('Welcome 6') ?></span></li>
					<li><span><?php echo __('Welcome 7') ?></span></li>
					<li><span><?php echo __('Welcome 8') ?></span></li>
					<li><span><?php echo __('Welcome 9') ?></span></li>
				</ul>
			</div>
		</div>

<?php if ($install_file_exists) : ?>
		<h2 class="block2"><span><?php echo __('Alerts head') ?></span></h2>
		<div id="adalerts" class="box">
			<p><?php printf(__('Install file exists'), '<a href="'.get_link('admin/action/remove_install_file/').'">'.__('Delete install file').'</a>') ?></p>
		</div>
<?php endif; ?>

		<h2 class="block2"><span><?php echo __('About head') ?></span></h2>
		<div id="adstats" class="box">
			<div class="inbox">
				<dl>
					<dt><?php echo __('FeatherBB version label') ?></dt>
					<dd>
						<?php printf(__('FeatherBB version data')."\n", $feather_config['o_cur_version'], '<a href="'.get_link('admin/action/check_upgrade/').'">'.__('Check for upgrade').'</a>') ?>
					</dd>
					<dt><?php echo __('Server statistics label') ?></dt>
					<dd>
						<a href="<?php echo get_link('admin/statistics/') ?>"><?php echo __('View server statistics') ?></a>
					</dd>
					<dt><?php echo __('Support label') ?></dt>
					<dd>
						<a href="http://FeatherBB.org/forums/index.php"><?php echo __('Forum label') ?></a> - <a href="http://gitter.im/featherbb/featherbb"><?php echo __('IRC label') ?></a>
					</dd>
				</dl>
			</div>
		</div>
	</div>
	<div class="clearer"></div>
</div>