<?php

/**
 * Copyright (C) 2015-2016 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

use FeatherBB\Core\Interfaces\User;
use FeatherBB\Core\Utils;
use FeatherBB\Model\Api\Api;

// Make sure no one attempts to run this script "directly"
if (!isset($feather)) {
    exit;
}

Container::get('hooks')->fire('view.profile.section_personal.start');
?>
<div class="blockform">
    <h2><span><?= Utils::escape($user['username']).' - '.__('Section personal') ?></span></h2>
    <div class="box">
        <form id="profile2" method="post" action="<?= Router::pathFor('profileSection', ['id' => $id, 'section' => 'personal']) ?>">
            <input type="hidden" name="csrf_name" value="<?= $csrf_name; ?>"><input type="hidden" name="csrf_value" value="<?= $csrf_value; ?>">
            <div class="inform">
                <fieldset>
                    <legend><?php _e('Personal details legend') ?></legend>
                    <div class="infldset">
                        <input type="hidden" name="form_sent" value="1" />
                        <label><?php _e('Realname') ?><br /><input type="text" name="form_realname" value="<?= Utils::escape($user['realname']) ?>" size="40" maxlength="40" /><br /></label>
<?php if (isset($title_field)): ?>                            <?= $title_field ?>
<?php endif; ?>                            <label><?php _e('Location') ?><br /><input type="text" name="form_location" value="<?= Utils::escape($user['location']) ?>" size="30" maxlength="30" /><br /></label>
<?php if (User::can('post.links') || User::isAdmin()) : ?>                            <label><?php _e('Website') ?><br /><input type="text" name="form_url" value="<?= Utils::escape($user['url']) ?>" size="50" maxlength="80" /><br /></label>
<?php endif; ?>
                        <label><?php _e('API token') ?><br /><input type="text" name="api" readonly="readonly" value="<?= Api::getToken(User::get()) ?>" size="60" maxlength="60" /><br /></label>
                    </div>
                </fieldset>
            </div>
            <p class="buttons"><input type="submit" name="update" value="<?php _e('Submit') ?>" /> <?php _e('Instructions') ?></p>
        </form>
    </div>
</div>
    <div class="clearer"></div>
</div>

<?php
Container::get('hooks')->fire('view.profile.section_personal.end');
