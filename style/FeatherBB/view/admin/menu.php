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
		<h2><span><?php echo $lang_admin_common['Moderator menu'] ?></span></h2>
		<div class="box">
			<div class="inbox">
				<ul>
					<li<?php if ($page == 'index') {
    echo ' class="isactive"';
}
    ?>><a href="<?php echo get_link('admin/') ?>"><?php echo $lang_admin_common['Index'] ?></a></li>
					<li<?php if ($page == 'users') {
    echo ' class="isactive"';
}
    ?>><a href="<?php echo get_link('admin/users/') ?>"><?php echo $lang_admin_common['Users'] ?></a></li>
<?php if ($is_admin || $feather->user->g_mod_ban_users == '1'): ?>					<li<?php if ($page == 'bans') {
    echo ' class="isactive"';
}
    ?>><a href="<?php echo get_link('admin/bans/') ?>"><?php echo $lang_admin_common['Bans'] ?></a></li>
<?php endif;
    if ($is_admin || $feather_config['o_report_method'] == '0' || $feather_config['o_report_method'] == '2'): ?>					<li<?php if ($page == 'reports') {
    echo ' class="isactive"';
}
    ?>><a href="<?php echo get_link('admin/reports/') ?>"><?php echo $lang_admin_common['Reports'] ?></a></li>
<?php endif;
    ?>				</ul>
			</div>
		</div>
<?php

    if ($is_admin) {
        ?>
		<h2 class="block2"><span><?php echo $lang_admin_common['Admin menu'] ?></span></h2>
		<div class="box">
			<div class="inbox">
				<ul>
					<li<?php if ($page == 'options') {
    echo ' class="isactive"';
}
        ?>><a href="<?php echo get_link('admin/options/') ?>"><?php echo $lang_admin_common['Options'] ?></a></li>
					<li<?php if ($page == 'permissions') {
    echo ' class="isactive"';
}
        ?>><a href="<?php echo get_link('admin/permissions/') ?>"><?php echo $lang_admin_common['Permissions'] ?></a></li>
					<li<?php if ($page == 'categories') {
    echo ' class="isactive"';
}
        ?>><a href="<?php echo get_link('admin/categories/') ?>"><?php echo $lang_admin_common['Categories'] ?></a></li>
					<li<?php if ($page == 'forums') {
    echo ' class="isactive"';
}
        ?>><a href="<?php echo get_link('admin/forums/') ?>"><?php echo $lang_admin_common['Forums'] ?></a></li>
					<li<?php if ($page == 'groups') {
    echo ' class="isactive"';
}
        ?>><a href="<?php echo get_link('admin/groups/') ?>"><?php echo $lang_admin_common['User groups'] ?></a></li>
					<li<?php if ($page == 'censoring') {
    echo ' class="isactive"';
}
        ?>><a href="<?php echo get_link('admin/censoring/') ?>"><?php echo $lang_admin_common['Censoring'] ?></a></li>
					<li<?php if ($page == 'parser') {
    echo ' class="isactive"';
}
        ?>><a href="<?php echo get_link('admin/parser/') ?>"><?php echo $lang_admin_common['Parser'] ?></a></li>
					<li<?php if ($page == 'maintenance') {
    echo ' class="isactive"';
}
        ?>><a href="<?php echo get_link('admin/maintenance/') ?>"><?php echo $lang_admin_common['Maintenance'] ?></a></li>
				</ul>
			</div>
		</div>
<?php

    }

    // Did we find any plugins?
    if (!empty($plugins)) {
        ?>
		<h2 class="block2"><span><?php echo $lang_admin_common['Plugins menu'] ?></span></h2>
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