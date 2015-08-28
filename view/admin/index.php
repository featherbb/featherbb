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
		<h2><span><?php _e('Forum admin head') ?></span></h2>
		<div id="adintro" class="box">
			<div class="inbox">
				<p><?php _e('Welcome to admin') ?></p>
				<ul>
					<li><span><?php _e('Welcome 1') ?></span></li>
					<li><span><?php _e('Welcome 2') ?></span></li>
					<li><span><?php _e('Welcome 3') ?></span></li>
					<li><span><?php _e('Welcome 4') ?></span></li>
					<li><span><?php _e('Welcome 5') ?></span></li>
					<li><span><?php _e('Welcome 6') ?></span></li>
					<li><span><?php _e('Welcome 7') ?></span></li>
					<li><span><?php _e('Welcome 8') ?></span></li>
					<li><span><?php _e('Welcome 9') ?></span></li>
				</ul>
			</div>
		</div>

<?php if ($install_file_exists) : ?>
		<h2 class="block2"><span><?php _e('Alerts head') ?></span></h2>
		<div id="adalerts" class="box">
			<p><?php printf(__('Install file exists'), '<a href="'.$feather->url->get_link('admin/action/remove_install_file/').'">'.__('Delete install file').'</a>') ?></p>
		</div>
<?php endif; ?>

		<h2 class="block2"><span><?php _e('About head') ?></span></h2>
		<div id="adstats" class="box">
			<div class="inbox">
				<dl>
					<dt><?php _e('FeatherBB version label') ?></dt>
					<dd>
						<?php printf(__('FeatherBB version data')."\n", $feather->forum_settings['o_cur_version'], '<a href="'.$feather->url->get_link('admin/action/check_upgrade/').'">'.__('Check for upgrade').'</a>') ?>
					</dd>
					<dt><?php _e('Server statistics label') ?></dt>
					<dd>
						<a href="<?php echo $feather->url->get_link('admin/statistics/') ?>"><?php _e('View server statistics') ?></a>
					</dd>
					<dt><?php _e('Support label') ?></dt>
					<dd>
						<a href="http://FeatherBB.org/forums/index.php"><?php _e('Forum label') ?></a> - <a href="http://gitter.im/featherbb/featherbb"><?php _e('IRC label') ?></a>
					</dd>
				</dl>
			</div>
		</div>
	</div>
	<div class="clearer"></div>
</div>