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

Container::get('hooks')->fire('view.admin.groups.admin_groups.start');
?>

    <div class="blockform">
        <h2><span><?= __('Add groups head') ?></span></h2>
        <div class="box">
                <div class="inform">
                    <fieldset>
                        <form id="groups" method="post" action="<?= Router::pathFor('addGroup') ?>">
                        <input type="hidden" name="csrf_name" value="<?= $csrf_name; ?>"><input type="hidden" name="csrf_value" value="<?= $csrf_value; ?>">
                        <legend><?= __('Add group subhead') ?></legend>
                        <div class="infldset">
                            <table class="aligntop">
                                <tr>
                                    <th scope="row"><?= __('New group label') ?><div><input type="submit" name="add_group" value="<?= __('Add') ?>" tabindex="2" /></div></th>
                                    <td>
                                        <select id="base_group" name="base_group" tabindex="1">
<?php

foreach ($groups as $cur_group) {
    if ($cur_group['g_id'] != ForumEnv::get('FEATHER_ADMIN') && $cur_group['g_id'] != ForumEnv::get('FEATHER_GUEST')) {
        if ($cur_group['g_id'] == ForumSettings::get('o_default_user_group')) {
            echo "\t\t\t\t\t\t\t\t\t\t\t".'<option value="'.$cur_group['g_id'].'" selected="selected">'.Utils::escape($cur_group['g_title']).'</option>'."\n";
        } else {
            echo "\t\t\t\t\t\t\t\t\t\t\t".'<option value="'.$cur_group['g_id'].'">'.Utils::escape($cur_group['g_title']).'</option>'."\n";
        }
    }
}

?>
                                        </select>
                                        <span><?= __('New group help') ?></span>
                                    </td>
                                </tr>
                            </table>
                        </div>
                        </form>
                    </fieldset>
                </div>
                <div class="inform">
                    <fieldset>
                        <form id="groups" method="post" action="<?= Router::pathFor('adminGroups') ?>">
                            <input type="hidden" name="csrf_name" value="<?= $csrf_name; ?>"><input type="hidden" name="csrf_value" value="<?= $csrf_value; ?>">
                        <legend><?= __('Default group subhead') ?></legend>
                        <div class="infldset">
                            <table class="aligntop">
                                <tr>
                                    <th scope="row"><?= __('Default group label') ?><div><input type="submit" name="set_default_group" value="<?= __('Save') ?>" tabindex="4" /></div></th>
                                    <td>
                                        <select id="default_group" name="default_group" tabindex="3">
<?php

foreach ($groups as $cur_group) {
    if ($cur_group['g_id'] > ForumEnv::get('FEATHER_GUEST') && $cur_group['is_moderator'] == 0) {
        if ($cur_group['g_id'] == ForumSettings::get('o_default_user_group')) {
            echo "\t\t\t\t\t\t\t\t\t\t\t".'<option value="'.$cur_group['g_id'].'" selected="selected">'.Utils::escape($cur_group['g_title']).'</option>'."\n";
        } else {
            echo "\t\t\t\t\t\t\t\t\t\t\t".'<option value="'.$cur_group['g_id'].'">'.Utils::escape($cur_group['g_title']).'</option>'."\n";
        }
    }
}

?>
                                        </select>
                                        <span><?= __('Default group help') ?></span>
                                    </td>
                                </tr>
                            </table>
                        </div>
                        </form>
                    </fieldset>
                </div>
        </div>

        <h2 class="block2"><span><?= __('Existing groups head') ?></span></h2>
        <div class="box">
            <div class="fakeform">
                <div class="inform">
                    <fieldset>
                        <legend><?= __('Edit groups subhead') ?></legend>
                        <div class="infldset">
                            <p><?= __('Edit groups info') ?></p>
                            <table>
<?php
foreach ($groups as $cur_group) {
    echo "\t\t\t\t\t\t\t\t".'<tr><th scope="row"><a href="'.Router::pathFor('editGroup', ['id' => $cur_group['g_id']]).'" tabindex="'.$cur_index++.'">'.__('Edit link').'</a>'.(($cur_group['g_id'] > ForumEnv::get('FEATHER_MEMBER')) ? ' | <a href="'.Router::pathFor('deleteGroup', ['id' => $cur_group['g_id']]).'" tabindex="'.$cur_index++.'">'.__('Delete link').'</a>' : '').'</th><td>'.Utils::escape($cur_group['g_title']).'</td></tr>'."\n";
}

?>
                            </table>
                        </div>
                    </fieldset>
                </div>
            </div>
        </div>
    </div>
    <div class="clearer"></div>
</div>

<?php
Container::get('hooks')->fire('view.admin.groups.admin_groups.end');
