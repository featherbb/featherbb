<?php

/**
 * Copyright (C) 2015-2016 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

use FeatherBB\Core\Url;

// Make sure no one attempts to run this script "directly"
if (!isset($feather)) {
    exit;
}

Container::get('hooks')->fire('view.admin.menu.start');
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
    ?>><a href="<?= Router::pathFor('adminIndex') ?>"><?php _e('Index') ?></a></li>
                    <li<?php if ($page == 'users') {
    echo ' class="isactive"';
}
    ?>><a href="<?= Router::pathFor('adminUsers') ?>"><?php _e('Users') ?></a></li>
<?php if ($is_admin || User::get()->g_mod_ban_users == '1'): ?>                    <li<?php if ($page == 'bans') {
    echo ' class="isactive"';
}
    ?>><a href="<?= Router::pathFor('adminBans') ?>"><?php _e('Bans') ?></a></li>
<?php endif;
    if ($is_admin || ForumSettings::get('o_report_method') == '0' || ForumSettings::get('o_report_method') == '2'): ?>                    <li<?php if ($page == 'reports') {
    echo ' class="isactive"';
}
    ?>><a href="<?= Router::pathFor('adminReports') ?>"><?php _e('Reports') ?></a></li>
<?php endif;
    ?>                </ul>
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
        ?>><a href="<?= Router::pathFor('adminOptions') ?>"><?php _e('Options') ?></a></li>
                    <li<?php if ($page == 'permissions') {
    echo ' class="isactive"';
}
        ?>><a href="<?= Router::pathFor('adminPermissions') ?>"><?php _e('Permissions') ?></a></li>
                    <li<?php if ($page == 'categories') {
    echo ' class="isactive"';
}
        ?>><a href="<?= Router::pathFor('adminCategories') ?>"><?php _e('Categories') ?></a></li>
                    <li<?php if ($page == 'forums') {
    echo ' class="isactive"';
}
        ?>><a href="<?= Router::pathFor('adminForums') ?>"><?php _e('Forums') ?></a></li>
                    <li<?php if ($page == 'groups') {
    echo ' class="isactive"';
}
        ?>><a href="<?= Router::pathFor('adminGroups') ?>"><?php _e('User groups') ?></a></li>
                    <li<?php if ($page == 'plugins') {
    echo ' class="isactive"';
}
        ?>><a href="<?= Router::pathFor('adminPlugins') ?>"><?= 'Plugins'; ?></a></li>
                    <li<?php if ($page == 'censoring') {
    echo ' class="isactive"';
}
        ?>><a href="<?= Router::pathFor('adminCensoring') ?>"><?php _e('Censoring') ?></a></li>
                    <li<?php if ($page == 'parser') {
    echo ' class="isactive"';
}
        ?>><a href="<?= Router::pathFor('adminParser') ?>"><?php _e('Parser') ?></a></li>
                    <li<?php if ($page == 'maintenance') {
    echo ' class="isactive"';
}
        ?>><a href="<?= Router::pathFor('adminMaintenance') ?>"><?php _e('Maintenance') ?></a></li>
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

        foreach ($plugins as $plugin) {
            $plugin_url = URL::url_friendly($plugin);
            echo "\t\t\t\t\t".'<li'.(($page == $plugin_url) ? ' class="isactive"' : '').'><a href="'.Router::pathFor('infoPlugin', ['name' => $plugin_url]).'">'.$plugin.'</a></li>'."\n";
        }

        ?>
                </ul>
            </div>
        </div>
<?php

    }

    ?>
    </div>

<?php
Container::get('hooks')->fire('view.admin.menu.end');