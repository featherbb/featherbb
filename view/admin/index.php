<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */
 
// Make sure no one attempts to run this script "directly"
if (!defined('PUN'))
	exit;
?>

	<div class="block">
		<h2><span><?php echo $lang_admin_index['Forum admin head'] ?></span></h2>
		<div id="adintro" class="box">
			<div class="inbox">
				<p><?php echo $lang_admin_index['Welcome to admin'] ?></p>
				<ul>
					<li><span><?php echo $lang_admin_index['Welcome 1'] ?></span></li>
					<li><span><?php echo $lang_admin_index['Welcome 2'] ?></span></li>
					<li><span><?php echo $lang_admin_index['Welcome 3'] ?></span></li>
					<li><span><?php echo $lang_admin_index['Welcome 4'] ?></span></li>
					<li><span><?php echo $lang_admin_index['Welcome 5'] ?></span></li>
					<li><span><?php echo $lang_admin_index['Welcome 6'] ?></span></li>
					<li><span><?php echo $lang_admin_index['Welcome 7'] ?></span></li>
					<li><span><?php echo $lang_admin_index['Welcome 8'] ?></span></li>
					<li><span><?php echo $lang_admin_index['Welcome 9'] ?></span></li>
				</ul>
			</div>
		</div>

<?php if ($install_file_exists) : ?>
		<h2 class="block2"><span><?php echo $lang_admin_index['Alerts head'] ?></span></h2>
		<div id="adalerts" class="box">
			<p><?php printf($lang_admin_index['Install file exists'], '<a href="'.get_link('admin/action/remove_install_file/').'">'.$lang_admin_index['Delete install file'].'</a>') ?></p>
		</div>
<?php endif; ?>

		<h2 class="block2"><span><?php echo $lang_admin_index['About head'] ?></span></h2>
		<div id="adstats" class="box">
			<div class="inbox">
				<dl>
					<dt><?php echo $lang_admin_index['FeatherBB version label'] ?></dt>
					<dd>
						<?php printf($lang_admin_index['FeatherBB version data']."\n", $pun_config['o_cur_version'], '<a href="'.get_link('admin/action/check_upgrade/').'">'.$lang_admin_index['Check for upgrade'].'</a>') ?>
					</dd>
					<dt><?php echo $lang_admin_index['Server statistics label'] ?></dt>
					<dd>
						<a href="<?php echo get_link('admin/statistics/') ?>"><?php echo $lang_admin_index['View server statistics'] ?></a>
					</dd>
					<dt><?php echo $lang_admin_index['Support label'] ?></dt>
					<dd>
						<a href="http://FeatherBB.org/forums/index.php"><?php echo $lang_admin_index['Forum label'] ?></a> - <a href="http://gitter.im/featherbb/featherbb"><?php echo $lang_admin_index['IRC label'] ?></a>
					</dd>
				</dl>
			</div>
		</div>
	</div>
	<div class="clearer"></div>
</div>