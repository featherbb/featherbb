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
		<h2><span><?php echo $lang_admin_index['Server statistics head'] ?></span></h2>
		<div id="adstats" class="box">
			<div class="inbox">
				<dl>
					<dt><?php echo $lang_admin_index['Server load label'] ?></dt>
					<dd>
						<?php printf($lang_admin_index['Server load data']."\n", $server_load, $num_online) ?>
					</dd>
<?php if ($feather->user->g_id == FEATHER_ADMIN): ?>					<dt><?php echo $lang_admin_index['Environment label'] ?></dt>
					<dd>
						<?php printf($lang_admin_index['Environment data OS'], PHP_OS) ?><br />
						<?php printf($lang_admin_index['Environment data version'], phpversion(), '<a href="'.get_link('admin/phpinfo/').'">'.$lang_admin_index['Show info'].'</a>') ?><br />
						<?php printf($lang_admin_index['Environment data acc']."\n", $php_accelerator) ?>
					</dd>
					<dt><?php echo $lang_admin_index['Database label'] ?></dt>
					<dd>
<?php if (isset($total_records) && isset($total_size)): ?>						<?php printf($lang_admin_index['Database data rows']."\n", forum_number_format($total_records)) ?>
						<br /><?php printf($lang_admin_index['Database data size']."\n", $total_size) ?>
<?php endif; ?>					</dd>
<?php endif; ?>
				</dl>
			</div>
		</div>
	</div>
	<div class="clearer"></div>
</div>