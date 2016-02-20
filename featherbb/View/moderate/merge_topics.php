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

Container::get('hooks')->fire('view.moderate.merge_topics.start');
?>

<div class="blockform">
    <h2><span><?php _e('Merge topics') ?></span></h2>
    <div class="box">
        <form method="post" action="">
            <input type="hidden" name="csrf_name" value="<?= $csrf_name; ?>"><input type="hidden" name="csrf_value" value="<?= $csrf_value; ?>">
            <input type="hidden" name="topics" value="<?= implode(',', array_map('intval', array_keys($topics))) ?>" />
            <div class="inform">
                <fieldset>
                    <legend><?php _e('Confirm merge legend') ?></legend>
                    <div class="infldset">
                        <div class="rbox">
                            <label><input type="checkbox" name="with_redirect" value="1" /><?php _e('Leave redirect') ?><br /></label>
                        </div>
                    </div>
                </fieldset>
            </div>
            <p class="buttons"><input type="submit" name="merge_topics_comply" value="<?php _e('Merge') ?>" /> <a href="javascript:history.go(-1)"><?php _e('Go back') ?></a></p>
        </form>
    </div>
</div>

<?php
Container::get('hooks')->fire('view.moderate.merge_topics.end');
