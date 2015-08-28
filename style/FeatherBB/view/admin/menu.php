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
<div id="adminconsole" class="block2col">
	<div id="adminmenu" class="blockmenu">
		<h2><span><?php _e('Moderator menu') ?></span></h2>
		<div class="box">
			<div class="inbox">
				<ul>
<<<<<<< HEAD
					<li<?= ($page == 'index') ? ' class="isactive"' : ''; ?>><a href="<?php echo get_link('admin/') ?>"><?php _e('Index') ?></a></li>
					<li<?= ($page == 'users') ? ' class="isactive"' : ''; ?>><a href="<?php echo get_link('admin/users/') ?>"><?php _e('Users') ?></a></li>
                <?php if ($is_admin || $feather->user->g_mod_ban_users == '1'): ?>
                    <li<?= ($page == 'bans') ? ' class="isactive"' : ''; ?>><a href="<?php echo get_link('admin/bans/') ?>"><?php _e('Bans') ?></a></li>
                <?php endif;
                if ($is_admin || $feather_config['o_report_method'] == '0' || $feather_config['o_report_method'] == '2'): ?>
                    <li<?= ($page == 'reports') ? ' class="isactive"' : ''; ?>><a href="<?php echo get_link('admin/reports/') ?>"><?php _e('Reports') ?></a></li>
                <?php endif; ?>
				</ul>
=======
					<li<?php if ($page == 'index') {
    echo ' class="isactive"';
}
    ?>><a href="<?php echo get_link('admin/') ?>"><?php _e('Index') ?></a></li>
					<li<?php if ($page == 'users') {
    echo ' class="isactive"';
}
    ?>><a href="<?php echo get_link('admin/users/') ?>"><?php _e('Users') ?></a></li>
<?php if ($is_admin || $feather->user->g_mod_ban_users == '1'): ?>					<li<?php if ($page == 'bans') {
    echo ' class="isactive"';
}
    ?>><a href="<?php echo get_link('admin/bans/') ?>"><?php _e('Bans') ?></a></li>
<?php endif;
    if ($is_admin || $feather->forum_settings['o_report_method'] == '0' || $feather->forum_settings['o_report_method'] == '2'): ?>					<li<?php if ($page == 'reports') {
    echo ' class="isactive"';
}
    ?>><a href="<?php echo get_link('admin/reports/') ?>"><?php _e('Reports') ?></a></li>
<?php endif;
    ?>				</ul>
>>>>>>> development
			</div>
		</div>
<?php

    if ($is_admin) {
        ?>
		<h2 class="block2"><span><?php _e('Admin menu') ?></span></h2>
		<div class="box">
			<div class="inbox">
				<ul>
					<li<?= ($page == 'options') ? ' class="isactive"' : ''; ?>><a href="<?= get_link('admin/options/') ?>"><?php _e('Options') ?></a></li>
					<li<?= ($page == 'permissions') ? ' class="isactive"' : ''; ?>><a href="<?= get_link('admin/permissions/') ?>"><?php _e('Permissions') ?></a></li>
					<li<?= ($page == 'categories') ? ' class="isactive"' : ''; ?>><a href="<?= get_link('admin/categories/') ?>"><?php _e('Categories') ?></a></li>
					<li<?= ($page == 'forums') ? ' class="isactive"' : ''; ?>><a href="<?= get_link('admin/forums/') ?>"><?php _e('Forums') ?></a></li>
					<li<?= ($page == 'groups') ? ' class="isactive"' : ''; ?>><a href="<?= get_link('admin/groups/') ?>"><?php _e('User groups') ?></a></li>
					<li<?= ($page == 'plugins') ? ' class="isactive"' : ''; ?>><a href="<?= get_link('admin/plugins/') ?>"><?= 'Plugins'; ?></a></li>
					<li<?= ($page == 'censoring') ? ' class="isactive"' : ''; ?>><a href="<?= get_link('admin/censoring/') ?>"><?php _e('Censoring') ?></a></li>
					<li<?= ($page == 'parser') ? ' class="isactive"' : ''; ?>><a href="<?= get_link('admin/parser/') ?>"><?php _e('Parser') ?></a></li>
					<li<?= ($page == 'maintenance') ? ' class="isactive"' : ''; ?>><a href="<?= get_link('admin/maintenance/') ?>"><?php _e('Maintenance') ?></a></li>
				</ul>
			</div>
		</div>
<?php

    }

    // Did we find any plugins?
    if (!empty($plugins)) {
        ?>
		<h2 class="block2"><span><?php _e('Plugins menu') ?></span></h2>
		<div class="box">
			<div class="inbox">
				<ul>
<?php

        foreach ($plugins as $plugin_name => $plugin) {
            echo "\t\t\t\t\t".'<li'.(($page == $plugin_name) ? ' class="isactive"' : '').'><a href="'.get_link('admin/loader/?plugin='.$plugin_name).'">'.str_replace('_', ' ', $plugin).'</a></li>'."\n";
        }

        ?>
				</ul>
			</div>
		</div>
<?php

    }

    ?>
	</div>
