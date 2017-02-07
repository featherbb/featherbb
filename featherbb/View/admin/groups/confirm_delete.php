<?php

/**
 * Copyright (C) 2015-2017 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

use FeatherBB\Core\Utils;

// Make sure no one attempts to run this script "directly"
if (!isset($feather)) {
    exit;
}

Container::get('hooks')->fire('view.admin.groups.confirm_delete.start');
?>

    <div class="blockform">
        <h2><span><?= __('Group delete head') ?></span></h2>
        <div class="box">
            <form method="post" action="<?= Router::pathFor('deleteGroup', ['id' => $id]) ?>">
                <input type="hidden" name="csrf_name" value="<?= $csrf_name; ?>"><input type="hidden" name="csrf_value" value="<?= $csrf_value; ?>">
                <div class="inform">
                <input type="hidden" name="group_to_delete" value="<?= Router::pathFor('deleteGroup', ['id' => $id]) ?>" />
                    <fieldset>
                        <legend><?= __('Confirm delete subhead') ?></legend>
                        <div class="infldset">
                            <p><?php printf(__('Confirm delete info'), Utils::escape($group_title)) ?></p>
                            <p class="warntext"><?= __('Confirm delete warn') ?></p>
                        </div>
                    </fieldset>
                </div>
                <p class="buttons"><input type="submit" name="del_group_comply" value="<?= __('Delete') ?>" tabindex="1" /><a href="javascript:history.go(-1)" tabindex="2"><?= __('Go back') ?></a></p>
            </form>
        </div>
    </div>
    <div class="clearer"></div>
</div>

<?php
Container::get('hooks')->fire('view.admin.groups.confirm_delete.end');
