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
					<li<?php if ($page == 'index') {
    echo ' class="isactive"';
}
    ?>><a href="<?php echo $feather->url->get('admin/') ?>"><?php _e('Index') ?></a></li>
					<li<?php if ($page == 'users') {
    echo ' class="isactive"';
}
    ?>><a href="<?php echo $feather->url->get('admin/users/') ?>"><?php _e('Users') ?></a></li>
<?php if ($is_admin || $feather->user->g_mod_ban_users == '1'): ?>					<li<?php if ($page == 'bans') {
    echo ' class="isactive"';
}
    ?>><a href="<?php echo $feather->url->get('admin/bans/') ?>"><?php _e('Bans') ?></a></li>
<?php endif;
    if ($is_admin || $feather->forum_settings['o_report_method'] == '0' || $feather->forum_settings['o_report_method'] == '2'): ?>					<li<?php if ($page == 'reports') {
    echo ' class="isactive"';
}
    ?>><a href="<?php echo $feather->url->get('admin/reports/') ?>"><?php _e('Reports') ?></a></li>
<?php endif;
    ?>				</ul>
			</div>
		</div>
<?php

    if ($is_admin) {
        ?>
		<h2 class="block2"><span><?php _e('Admin menu') ?></span></h2>
		<div class="box">
			<div class="inbox">
				<ul>
					<li<?php if ($page == 'options') {
    echo ' class="isactive"';
}
        ?>><a href="<?php echo $feather->url->get('admin/options/') ?>"><?php _e('Options') ?></a></li>
					<li<?php if ($page == 'permissions') {
    echo ' class="isactive"';
}
        ?>><a href="<?php echo $feather->url->get('admin/permissions/') ?>"><?php _e('Permissions') ?></a></li>
					<li<?php if ($page == 'categories') {
    echo ' class="isactive"';
}
        ?>><a href="<?php echo $feather->url->get('admin/categories/') ?>"><?php _e('Categories') ?></a></li>
					<li<?php if ($page == 'forums') {
    echo ' class="isactive"';
}
        ?>><a href="<?php echo $feather->url->get('admin/forums/') ?>"><?php _e('Forums') ?></a></li>
					<li<?php if ($page == 'groups') {
    echo ' class="isactive"';
}
        ?>><a href="<?php echo $feather->url->get('admin/groups/') ?>"><?php _e('User groups') ?></a></li>
					<li<?php if ($page == 'plugins') {
    echo ' class="isactive"';
}
        ?>><a href="<?php echo $feather->url->get('admin/plugins/') ?>"><?= 'Plugins'; ?></a></li>
					<li<?php if ($page == 'censoring') {
    echo ' class="isactive"';
}
        ?>><a href="<?php echo $feather->url->get('admin/censoring/') ?>"><?php _e('Censoring') ?></a></li>
					<li<?php if ($page == 'parser') {
    echo ' class="isactive"';
}
        ?>><a href="<?php echo $feather->url->get('admin/parser/') ?>"><?php _e('Parser') ?></a></li>
					<li<?php if ($page == 'maintenance') {
    echo ' class="isactive"';
}
        ?>><a href="<?php echo $feather->url->get('admin/maintenance/') ?>"><?php _e('Maintenance') ?></a></li>
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
            echo "\t\t\t\t\t".'<li'.(($page == $plugin_name) ? ' class="isactive"' : '').'><a href="'.$feather->url->get('admin/loader/?plugin='.$plugin_name).'">'.str_replace('_', ' ', $plugin).'</a></li>'."\n";
        }

        ?>
				</ul>
			</div>
		</div>
<?php

    }

    ?>
	</div>
