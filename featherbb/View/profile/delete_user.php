<?php

/**
 * Copyright (C) 2015-2016 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

use FeatherBB\Core\Utils;

// Make sure no one attempts to run this script "directly"
if (!isset($feather)) {
    exit;
}

Container::get('hooks')->fire('view.profile.delete_user.start');
?>

<div class="blockform">
    <h2><span><?php _e('Confirm delete user') ?></span></h2>
    <div class="box">
        <form id="confirm_del_user" method="post" action="<?= $feather->urlFor('userProfile', ['id' => $id]) ?>">
            <input type="hidden" name="<?= $csrf_key; ?>" value="<?= $csrf_token; ?>">
            <div class="inform">
                <fieldset>
                    <legend><?php _e('Confirm delete legend') ?></legend>
                    <div class="infldset">
                        <p><?php _e('Confirmation info').' <strong>'.Utils::escape($username).'</strong>.' ?></p>
                        <div class="rbox">
                            <label><input type="checkbox" name="delete_posts" value="1" checked="checked" /><?php _e('Delete posts') ?><br /></label>
                        </div>
                        <p class="warntext"><strong><?php _e('Delete warning') ?></strong></p>
                    </div>
                </fieldset>
            </div>
            <p class="buttons"><input type="submit" name="delete_user_comply" value="<?php _e('Delete') ?>" /> <a href="javascript:history.go(-1)"><?php _e('Go back') ?></a></p>
        </form>
    </div>
</div>

<?php
Container::get('hooks')->fire('view.profile.delete_user.end');
