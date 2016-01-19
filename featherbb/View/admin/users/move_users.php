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

$feather->hooks->fire('view.admin.users.move_users.start');
?>

    <div class="blockform">
        <h2><span><?php _e('Move users') ?></span></h2>
        <div class="box">
            <form name="confirm_move_users" method="post" action="<?= $feather->urlFor('adminUsers') ?>">
                <input type="hidden" name="<?= $csrf_key; ?>" value="<?= $csrf_token; ?>">
                <input type="hidden" name="users" value="<?= implode(',', $move['user_ids']) ?>" />
                <div class="inform">
                    <fieldset>
                        <legend><?php _e('Move users subhead') ?></legend>
                        <div class="infldset">
                            <table class="aligntop">
                                <tr>
                                    <th scope="row"><?php _e('New group label') ?></th>
                                    <td>
                                        <select name="new_group" tabindex="1">
<?php foreach ($move['all_groups'] as $gid => $group) : ?>                                            <option value="<?= $gid ?>"><?= Utils::escape($group) ?></option>
<?php endforeach;
    ?>
                                        </select>
                                        <span><?php _e('New group help') ?></span>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </fieldset>
                </div>
                <p class="submitend"><input type="submit" name="move_users_comply" value="<?php _e('Save') ?>" tabindex="2" /></p>
            </form>
        </div>
    </div>
    <div class="clearer"></div>
</div>

<?php
$feather->hooks->fire('view.admin.users.move_users.end');
