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

Container::get('hooks')->fire('view.admin.users.delete_users.start');
?>

    <div class="blockform">
        <h2><span><?= __('Delete users') ?></span></h2>
        <div class="box">
            <form name="confirm_del_users" method="post" action="<?= Router::pathFor('adminUsers') ?>">
                <input type="hidden" name="csrf_name" value="<?= $csrf_name; ?>"><input type="hidden" name="csrf_value" value="<?= $csrf_value; ?>">
                <input type="hidden" name="users" value="<?= implode(',', $user_ids) ?>" />
                <div class="inform">
                    <fieldset>
                        <legend><?= __('Confirm delete legend') ?></legend>
                        <div class="infldset">
                            <p><?= __('Confirm delete info') ?></p>
                            <div class="rbox">
                                <label><input type="checkbox" name="delete_posts" value="1" checked="checked" /><?= __('Delete posts') ?><br /></label>
                            </div>
                            <p class="warntext"><strong><?= __('Delete warning') ?></strong></p>
                        </div>
                    </fieldset>
                </div>
                <p class="buttons"><input type="submit" name="delete_users_comply" value="<?= __('Delete') ?>" /> <a href="javascript:history.go(-1)"><?= __('Go back') ?></a></p>
            </form>
        </div>
    </div>
    <div class="clearer"></div>
</div>

<?php
Container::get('hooks')->fire('view.admin.users.delete_users.end');
