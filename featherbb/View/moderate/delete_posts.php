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

Container::get('hooks')->fire('view.moderate.delete_posts.start');
?>

<div class="blockform">
    <h2><span><?= __('Delete posts') ?></span></h2>
    <div class="box">
        <form method="post" action="">
            <input type="hidden" name="csrf_name" value="<?= $csrf_name; ?>"><input type="hidden" name="csrf_value" value="<?= $csrf_value; ?>">
            <div class="inform">
                <fieldset>
                    <legend><?= __('Confirm delete legend') ?></legend>
                    <div class="infldset">
                        <input type="hidden" name="posts" value="<?= implode(',', array_map('intval', array_keys($posts))) ?>" />
                        <p><?= __('Delete posts comply') ?></p>
                    </div>
                </fieldset>
            </div>
            <p class="buttons"><input type="submit" name="delete_posts_comply" value="<?= __('Delete') ?>" /> <a href="javascript:history.go(-1)"><?= __('Go back') ?></a></p>
        </form>
    </div>
</div>

<?php
Container::get('hooks')->fire('view.moderate.delete_posts.end');
