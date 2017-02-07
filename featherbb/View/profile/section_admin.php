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

Container::get('hooks')->fire('view.profile.section_admin.start');
?>
                <div class="blockform">
                    <h2><span><?= Utils::escape($user['username']).' - '.__('Section admin') ?></span></h2>
                    <div class="box">
                        <form id="profile7" method="post" action="<?= Router::pathFor('profileSection', ['id' => $id, 'section' => 'admin'])?>">
                            <input type="hidden" name="csrf_name" value="<?= $csrf_name; ?>"><input type="hidden" name="csrf_value" value="<?= $csrf_value; ?>">
                            <div class="inform">
                            <input type="hidden" name="form_sent" value="1" />
                                <fieldset>
<?php

        if (!User::isAdmin()) {
            ?>
                                    <legend><?= __('Delete ban legend') ?></legend>
                                    <div class="infldset">
                                        <p><input type="submit" name="ban" value="<?= __('Ban user') ?>" /></p>
                                    </div>
                                </fieldset>
                            </div>
<?php

        } else {
            if (User::get()->id != $id) {
                ?>
                                    <legend><?= __('Group membership legend') ?></legend>
                                    <div class="infldset">
                                        <select id="group_id" name="group_id">
<?= $group_list ?>
                                        </select>
                                        <input type="submit" name="update_group_membership" value="<?= __('Save') ?>" />
                                    </div>
                                </fieldset>
                            </div>
                            <div class="inform">
                                <fieldset>
<?php

            } ?>
                                    <legend><?= __('Delete ban legend') ?></legend>
                                    <div class="infldset">
                                        <input type="submit" name="delete_user" value="<?= __('Delete user') ?>" /> <input type="submit" name="ban" value="<?= __('Ban user') ?>" />
                                    </div>
                                </fieldset>
                            </div>
<?php

            if (Container::get('perms')->getGroupPermissions($user['g_id'], 'mod.is_mod') || $user['g_id'] == ForumEnv::get('FEATHER_ADMIN')) {
                ?>
                            <div class="inform">
                                <fieldset>
                                    <legend><?= __('Set mods legend') ?></legend>
                                    <div class="infldset">
                                        <p><?= __('Moderator in info') ?></p>
<?= $forum_list ?>
                                            </div>
                                        </div>
                                        <br class="clearb" /><input type="submit" name="update_forums" value="<?= __('Update forums') ?>" />
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
Container::get('hooks')->fire('view.profile.section_admin.end');
