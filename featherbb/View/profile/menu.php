<?php

/**
 * Copyright (C) 2015-2016 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

// Make sure no one attempts to run this script "directly"
if (!isset($feather)) {
    exit;
}

Container::get('hooks')->fire('view.profile.menu.start');
?>
            <div id="profile" class="block2col">
                <div class="blockmenu">
                    <h2><span><?= __('Profile menu') ?></span></h2>
                    <div class="box">
                        <div class="inbox">
                            <ul>
                                <li<?php if ($page == 'essentials') {
    echo ' class="isactive"';
}?>><a href="<?= Router::pathFor('profileSection', ['id' => $id, 'section' => 'essentials']) ?>"><?= __('Section essentials') ?></a></li>
                                <li<?php if ($page == 'personal') {
    echo ' class="isactive"';
}?>><a href="<?= Router::pathFor('profileSection', ['id' => $id, 'section' => 'personal']) ?>"><?= __('Section personal') ?></a></li>
<?php if (ForumSettings::get('o_avatars') == '1' || ForumSettings::get('o_signatures') == '1'): ?>
                                <li<?php if ($page == 'personality') {
    echo ' class="isactive"';
}?>><a href="<?= Router::pathFor('profileSection', ['id' => $id, 'section' => 'personality']) ?>"><?= __('Section personality') ?></a></li>
<?php endif;?>
                                <li<?php if ($page == 'display') {
    echo ' class="isactive"';
}?>><a href="<?= Router::pathFor('profileSection', ['id' => $id, 'section' => 'display']) ?>"><?= __('Section display') ?></a></li>
                                <li<?php if ($page == 'privacy') {
    echo ' class="isactive"';
}?>><a href="<?= Router::pathFor('profileSection', ['id' => $id, 'section' => 'privacy']) ?>"><?= __('Section privacy') ?></a></li>
<?php if (User::isAdmin() || (User::isAdminMod() && User::can('mod.ban_users'))): ?>
                                <li<?php if ($page == 'admin') {
    echo ' class="isactive"';
}?>><a href="<?= Router::pathFor('profileSection', ['id' => $id, 'section' => 'admin']) ?>"><?= __('Section admin') ?></a></li>
<?php endif;?>
                            </ul>
                        </div>
                    </div>
                </div>

<?php
Container::get('hooks')->fire('view.profile.menu.end');
