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

$feather->hooks->fire('view.admin.groups.delete_group.start');
?>

    <div class="blockform">
        <h2><span><?php _e('Delete group head') ?></span></h2>
        <div class="box">
            <form id="groups" method="post" action="<?= $feather->urlFor('deleteGroup', ['id' => $id]) ?>">
                <input type="hidden" name="<?= $csrf_key; ?>" value="<?= $csrf_token; ?>">
                <div class="inform">
                    <fieldset>
                        <legend><?php _e('Move users subhead') ?></legend>
                        <div class="infldset">
                            <p><?php printf(__('Move users info'), Utils::escape($group_info['title']), Utils::forum_number_format($group_info['members'])) ?></p>
                            <label><?php _e('Move users label') ?>
                            <select name="move_to_group">
                                <?= $group_list_delete; ?>
                            </select>
                            <br /></label>
                        </div>
                    </fieldset>
                </div>
                <p class="buttons"><input type="submit" name="del_group" value="<?php _e('Delete group') ?>" /><a href="javascript:history.go(-1)"><?php _e('Go back') ?></a></p>
            </form>
        </div>
    </div>
    <div class="clearer"></div>
</div>

<?php
$feather->hooks->fire('view.admin.groups.delete_group.end');
