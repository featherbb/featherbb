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

Container::get('hooks')->fire('view.admin.forums.delete.start');
?>

    <div class="blockform">
        <h2><span><?php _e('Confirm delete head') ?></span></h2>
        <div class="box">
            <form method="post" action="<?= Router::pathFor('deleteForum', ['id' => $cur_forum['id']]) ?>">
                <input type="hidden" name="csrf_name" value="<?= $csrf_name; ?>"><input type="hidden" name="csrf_value" value="<?= $csrf_value; ?>">
                <div class="inform">
                    <fieldset>
                        <legend><?php _e('Confirm delete subhead') ?></legend>
                        <div class="infldset">
                            <p><?php printf(__('Confirm delete info'), $cur_forum['forum_name']) ?></p>
                            <p class="warntext"><?php _e('Confirm delete warn') ?></p>
                        </div>
                    </fieldset>
                </div>
                <p class="buttons"><input type="submit" name="del_forum_comply" value="<?php _e('Delete') ?>" /><a href="javascript:history.go(-1)"><?php _e('Go back') ?></a></p>
            </form>
        </div>
    </div>
    <div class="clearer"></div>
</div>

<?php
Container::get('hooks')->fire('view.admin.forums.admin_forums.end');
