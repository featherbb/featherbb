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
                    <h2><span><?= __('Moderator menu') ?></span></h2>
                    <div class="box">
                        <div class="inbox">
                            <ul>
<?php foreach ($menu_items as $perm => $data) {
    if (preg_match('/^mod\..*$/', $perm)) {
        // ForumSettings::get('o_report_method') == '0' || ForumSettings::get('o_report_method') == '2')
        echo "\t\t\t\t\t\t\t\t".'<li'.($page == strtolower($data['title']) ? ' class="isactive"' : '').'><a href="'.Router::pathFor($data['url']).'">'.__($data['title']).'</a></li>'."\n";
    }
} ?>
                            </ul>
                        </div>
                    </div>
<?php
if (User::isAdmin()):
?>
                    <h2 class="block2"><span><?= __('Admin menu') ?></span></h2>
                    <div class="box">
                        <div class="inbox">
                            <ul>
<?php foreach ($menu_items as $perm => $data) {
    if (preg_match('/^board\..*$/', $perm)) {
        echo "\t\t\t\t\t\t\t\t".'<li'.($page == strtolower($data['title']) ? ' class="isactive"' : '').'><a href="'.Router::pathFor($data['url']).'">'.__($data['title']).'</a></li>'."\n";
    }
} ?>
                            </ul>
                        </div>
                    </div>
<?php
endif;

// Did we find any plugins?
if (!empty($plugins)): ?>
                    <h2 class="block2"><span><?= __('Plugins menu') ?></span></h2>
                    <div class="box">
                        <div class="inbox">
                            <ul>
<?php foreach ($plugins as $plugin) {
    $plugin_url = Url::url_friendly($plugin);
    echo "\t\t\t\t\t\t\t\t".'<li'.(($page == $plugin_url) ? ' class="isactive"' : '').'><a href="'.Router::pathFor('infoPlugin', ['name' => $plugin_url]).'">'.$plugin.'</a></li>'."\n";
}
?>
                            </ul>
                        </div>
                    </div>
<?php endif; ?>
                </div>
<?php
Container::get('hooks')->fire('view.admin.menu.end');
