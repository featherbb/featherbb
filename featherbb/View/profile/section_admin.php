<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

use FeatherBB\Core\Utils;

// Make sure no one attempts to run this script "directly"
if (!isset($feather)) {
    exit;
}

$feather->hooks->fire('view.profile.section_admin.start');
?>
    <div class="blockform">
        <h2><span><?= Utils::escape($user['username']).' - '.__('Section admin') ?></span></h2>
        <div class="box">
            <form id="profile7" method="post" action="<?= $feather->urlFor('profileSection', ['id' => $id, 'section' => 'admin'])?>">
                <input type="hidden" name="<?= $csrf_key; ?>" value="<?= $csrf_token; ?>">
                <div class="inform">
                <input type="hidden" name="form_sent" value="1" />
                    <fieldset>
<?php

        if ($feather->user->g_moderator == '1') {
            ?>
                        <legend><?php _e('Delete ban legend') ?></legend>
                        <div class="infldset">
                            <p><input type="submit" name="ban" value="<?php _e('Ban user') ?>" /></p>
                        </div>
                    </fieldset>
                </div>
<?php

        } else {
            if ($feather->user->id != $id) {
                ?>
                        <legend><?php _e('Group membership legend') ?></legend>
                        <div class="infldset">
                            <select id="group_id" name="group_id">
                                                            <?= $group_list ?>
                            </select>
                            <input type="submit" name="update_group_membership" value="<?php _e('Save') ?>" />
                        </div>
                    </fieldset>
                </div>
                <div class="inform">
                    <fieldset>
<?php

            }

            ?>
                        <legend><?php _e('Delete ban legend') ?></legend>
                        <div class="infldset">
                            <input type="submit" name="delete_user" value="<?php _e('Delete user') ?>" /> <input type="submit" name="ban" value="<?php _e('Ban user') ?>" />
                        </div>
                    </fieldset>
                </div>
<?php

            if ($user['g_moderator'] == '1' || $user['g_id'] == $feather->forum_env['FEATHER_ADMIN']) {
                ?>
                <div class="inform">
                    <fieldset>
                        <legend><?php _e('Set mods legend') ?></legend>
                        <div class="infldset">
                            <p><?php _e('Moderator in info') ?></p>
                                                        <?= $forum_list ?>
                                </div>
                            </div>
                            <br class="clearb" /><input type="submit" name="update_forums" value="<?php _e('Update forums') ?>" />
                        </div>
                    </fieldset>
                </div>
<?php

            }
        }

?>
            </form>
        </div>
    </div>
    <div class="clearer"></div>
</div>

<?php
$feather->hooks->fire('view.profile.section_admin.end');
