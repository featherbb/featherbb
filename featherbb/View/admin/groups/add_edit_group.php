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

Container::get('hooks')->fire('view.admin.groups.add_edit_group.start');
?>

    <div class="blockform">
        <h2><span><?= __('Group settings head') ?></span></h2>
        <div class="box">
            <form id="groups2" method="post" action="">
                <input type="hidden" name="csrf_name" value="<?= $csrf_name; ?>"><input type="hidden" name="csrf_value" value="<?= $csrf_value; ?>">
                <p class="submittop"><input type="submit" name="add_edit_group" value="<?= __('Save') ?>" /></p>
                <div class="inform">
                    <input type="hidden" name="mode" value="<?= $group['mode'] ?>" />
<?php if ($group['mode'] == 'edit'): ?>                    <input type="hidden" name="group_id" value="<?= $id ?>" />
<?php endif; ?><?php if ($group['mode'] == 'add'): ?>                    <input type="hidden" name="base_group" value="<?= $group['base_group'] ?>" />
<?php endif; ?>                    <fieldset>
                        <legend><?= __('Group settings subhead') ?></legend>
                        <div class="infldset">
                            <p><?= __('Group settings info') ?></p>
                            <table class="aligntop">
                                <tr>
                                    <th scope="row"><?= __('Group title label') ?></th>
                                    <td>
                                        <input type="text" name="req_title" size="25" maxlength="50" value="<?php if ($group['mode'] == 'edit') {
    echo Utils::escape($group['info']['g_title']);
} ?>" tabindex="1" required="required" autofocus />
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?= __('User title label') ?></th>
                                    <td>
                                        <input type="text" name="user_title" size="25" maxlength="50" value="<?= Utils::escape($group['info']['g_user_title']) ?>" tabindex="2" />
                                        <span><?php printf(__('User title help'), ($group['info']['g_id'] != ForumEnv::get('FEATHER_GUEST') ? __('Member') : __('Guest'))) ?></span>
                                    </td>
                                </tr>
<?php if ($group['info']['g_id'] != ForumEnv::get('FEATHER_ADMIN')): if ($group['info']['g_id'] != ForumEnv::get('FEATHER_GUEST')): ?>
                                <tr>
                                    <th scope="row"><?= __('Promote users label') ?></th>
                                    <td>
                                        <select name="promote_next_group" tabindex="3">
                                            <option value="0"><?= __('Disable promotion') ?></option>
                                            <?= $group_list ?>
                                        </select>
                                        <input type="text" name="promote_min_posts" size="5" maxlength="10" value="<?= Utils::escape($group['prefs']['promote.min_posts']) ?>" tabindex="4" />
                                        <span><?php printf(__('Promote users help'), __('Disable promotion')) ?></span>
                                    </td>
                                </tr>
<?php if ($group['mode'] != 'edit' || ForumSettings::get('o_default_user_group') != $group['info']['g_id']): ?>
                                <tr>
                                    <th scope="row"> <?= __('Mod privileges label') ?></th>
                                    <td>
                                        <label class="conl"><input type="radio" name="moderator" value="1"<?php if (isset($group['perms']['mod.is_mod'])) {
    echo ' checked="checked"';
} ?> tabindex="5" />&#160;<strong><?= __('Yes') ?></strong></label>
                                        <label class="conl"><input type="radio" name="moderator" value="0"<?php if (!isset($group['perms']['mod.is_mod'])) {
    echo ' checked="checked"';
} ?> tabindex="6" />&#160;<strong><?= __('No') ?></strong></label>
                                        <span class="clearb"><?= __('Mod privileges help') ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?= __('Edit profile label') ?></th>
                                    <td>
                                        <label class="conl"><input type="radio" name="mod_edit_users" value="1"<?php if (isset($group['perms']['mod.edit_users'])) {
    echo ' checked="checked"';
} ?> tabindex="7" />&#160;<strong><?= __('Yes') ?></strong></label>
                                        <label class="conl"><input type="radio" name="mod_edit_users" value="0"<?php if (!isset($group['perms']['mod.edit_users'])) {
    echo ' checked="checked"';
} ?> tabindex="8" />&#160;<strong><?= __('No') ?></strong></label>
                                        <span class="clearb"><?= __('Edit profile help') ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?= __('Rename users label') ?></th>
                                    <td>
                                        <label class="conl"><input type="radio" name="mod_rename_users" value="1"<?php if (isset($group['perms']['mod.rename_users'])) {
    echo ' checked="checked"';
} ?> tabindex="9" />&#160;<strong><?= __('Yes') ?></strong></label>
                                        <label class="conl"><input type="radio" name="mod_rename_users" value="0"<?php if (!isset($group['perms']['mod.rename_users'])) {
    echo ' checked="checked"';
} ?> tabindex="10" />&#160;<strong><?= __('No') ?></strong></label>
                                        <span class="clearb"><?= __('Rename users help') ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?= __('Change passwords label') ?></th>
                                    <td>
                                        <label class="conl"><input type="radio" name="mod_change_passwords" value="1"<?php if (isset($group['perms']['mod.change_passwords'])) {
    echo ' checked="checked"';
} ?> tabindex="11" />&#160;<strong><?= __('Yes') ?></strong></label>
                                        <label class="conl"><input type="radio" name="mod_change_passwords" value="0"<?php if (!isset($group['perms']['mod.change_passwords'])) {
    echo ' checked="checked"';
} ?> tabindex="12" />&#160;<strong><?= __('No') ?></strong></label>
                                        <span class="clearb"><?= __('Change passwords help') ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?= __('Mod promote users label') ?></th>
                                    <td>
                                        <label class="conl"><input type="radio" name="mod_promote_users" value="1"<?php if (isset($group['perms']['mod.promote_users'])) {
    echo ' checked="checked"';
} ?> tabindex="13" />&#160;<strong><?= __('Yes') ?></strong></label>
                                        <label class="conl"><input type="radio" name="mod_promote_users" value="0"<?php if (!isset($group['perms']['mod.promote_users'])) {
    echo ' checked="checked"';
} ?> tabindex="14" />&#160;<strong><?= __('No') ?></strong></label>
                                        <span class="clearb"><?= __('Mod promote users help') ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?= __('Ban users label') ?></th>
                                    <td>
                                        <label class="conl"><input type="radio" name="mod_ban_users" value="1"<?php if (isset($group['perms']['mod.ban_users'])) {
    echo ' checked="checked"';
} ?> tabindex="15" />&#160;<strong><?= __('Yes') ?></strong></label>
                                        <label class="conl"><input type="radio" name="mod_ban_users" value="0"<?php if (!isset($group['perms']['mod.ban_users'])) {
    echo ' checked="checked"';
} ?> tabindex="16" />&#160;<strong><?= __('No') ?></strong></label>
                                        <span class="clearb"><?= __('Ban users help') ?></span>
                                    </td>
                                </tr>
<?php endif; endif; ?>
                                <tr>
                                    <th scope="row"><?= __('Read board label') ?></th>
                                    <td>
                                        <label class="conl"><input type="radio" name="read_board" value="1"<?php if (isset($group['perms']['board.read'])) {
    echo ' checked="checked"';
} ?> tabindex="17" />&#160;<strong><?= __('Yes') ?></strong></label>
                                        <label class="conl"><input type="radio" name="read_board" value="0"<?php if (!isset($group['perms']['board.read'])) {
    echo ' checked="checked"';
} ?> tabindex="18" />&#160;<strong><?= __('No') ?></strong></label>
                                        <span class="clearb"><?= __('Read board help') ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?= __('View user info label') ?></th>
                                    <td>
                                        <label class="conl"><input type="radio" name="view_users" value="1"<?php if (isset($group['perms']['users.view'])) {
    echo ' checked="checked"';
} ?> tabindex="19" />&#160;<strong><?= __('Yes') ?></strong></label>
                                        <label class="conl"><input type="radio" name="view_users" value="0"<?php if (!isset($group['perms']['users.view'])) {
    echo ' checked="checked"';
} ?> tabindex="20" />&#160;<strong><?= __('No') ?></strong></label>
                                        <span class="clearb"><?= __('View user info help') ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?= __('Post replies label') ?></th>
                                    <td>
                                        <label class="conl"><input type="radio" name="post_replies" value="1"<?php if (isset($group['perms']['topic.reply'])) {
    echo ' checked="checked"';
} ?> tabindex="21" />&#160;<strong><?= __('Yes') ?></strong></label>
                                        <label class="conl"><input type="radio" name="post_replies" value="0"<?php if (!isset($group['perms']['topic.reply'])) {
    echo ' checked="checked"';
} ?> tabindex="22" />&#160;<strong><?= __('No') ?></strong></label>
                                        <span class="clearb"><?= __('Post replies help') ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?= __('Post topics label') ?></th>
                                    <td>
                                        <label class="conl"><input type="radio" name="post_topics" value="1"<?php if (isset($group['perms']['topic.post'])) {
    echo ' checked="checked"';
} ?> tabindex="23" />&#160;<strong><?= __('Yes') ?></strong></label>
                                        <label class="conl"><input type="radio" name="post_topics" value="0"<?php if (!isset($group['perms']['topic.post'])) {
    echo ' checked="checked"';
} ?> tabindex="24" />&#160;<strong><?= __('No') ?></strong></label>
                                        <span class="clearb"><?= __('Post topics help') ?></span>
                                    </td>
                                </tr>
<?php if ($group['info']['g_id'] != ForumEnv::get('FEATHER_GUEST')): ?>
                                <tr>
                                    <th scope="row"><?= __('Edit posts label') ?></th>
                                    <td>
                                        <label class="conl"><input type="radio" name="edit_posts" value="1"<?php if (isset($group['perms']['post.edit'])) {
    echo ' checked="checked"';
} ?> tabindex="25" />&#160;<strong><?= __('Yes') ?></strong></label>
                                        <label class="conl"><input type="radio" name="edit_posts" value="0"<?php if (!isset($group['perms']['post.edit'])) {
    echo ' checked="checked"';
} ?> tabindex="26" />&#160;<strong><?= __('No') ?></strong></label>
                                        <span class="clearb"><?= __('Edit posts help') ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?= __('Delete posts label') ?></th>
                                    <td>
                                        <label class="conl"><input type="radio" name="delete_posts" value="1"<?php if (isset($group['perms']['post.delete'])) {
    echo ' checked="checked"';
} ?> tabindex="27" />&#160;<strong><?= __('Yes') ?></strong></label>
                                        <label class="conl"><input type="radio" name="delete_posts" value="0"<?php if (!isset($group['perms']['post.delete'])) {
    echo ' checked="checked"';
} ?> tabindex="28" />&#160;<strong><?= __('No') ?></strong></label>
                                        <span class="clearb"><?= __('Delete posts help') ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?= __('Delete topics label') ?></th>
                                    <td>
                                        <label class="conl"><input type="radio" name="delete_topics" value="1"<?php if (isset($group['perms']['topic.delete'])) {
    echo ' checked="checked"';
} ?> tabindex="29" />&#160;<strong><?= __('Yes') ?></strong></label>
                                        <label class="conl"><input type="radio" name="delete_topics" value="0"<?php if (!isset($group['perms']['topic.delete'])) {
    echo ' checked="checked"';
} ?> tabindex="30" />&#160;<strong><?= __('No') ?></strong></label>
                                        <span class="clearb"><?= __('Delete topics help') ?></span>
                                    </td>
                                </tr>
<?php endif; ?>
                                <tr>
                                    <th scope="row"><?= __('Post links label') ?></th>
                                    <td>
                                        <label class="conl"><input type="radio" name="post_links" value="1"<?php if (isset($group['perms']['post.links'])) {
    echo ' checked="checked"';
} ?> tabindex="31" />&#160;<strong><?= __('Yes') ?></strong></label>
                                        <label class="conl"><input type="radio" name="post_links" value="0"<?php if (!isset($group['perms']['post.links'])) {
    echo ' checked="checked"';
} ?> tabindex="32" />&#160;<strong><?= __('No') ?></strong></label>
                                        <span class="clearb"><?= __('Post links help') ?></span>
                                    </td>
                                </tr>
<?php if ($group['info']['g_id'] != ForumEnv::get('FEATHER_GUEST')): ?>
                                <tr>
                                    <th scope="row"><?= __('Set own title label') ?></th>
                                    <td>
                                        <label class="conl"><input type="radio" name="set_title" value="1"<?php if (isset($group['perms']['user.set_title'])) {
    echo ' checked="checked"';
} ?> tabindex="33" />&#160;<strong><?= __('Yes') ?></strong></label>
                                        <label class="conl"><input type="radio" name="set_title" value="0"<?php if (!isset($group['perms']['user.set_title'])) {
    echo ' checked="checked"';
} ?> tabindex="34" />&#160;<strong><?= __('No') ?></strong></label>
                                        <span class="clearb"><?= __('Set own title help') ?></span>
                                    </td>
                                </tr>
<?php endif; ?>
                                <tr>
                                    <th scope="row"><?= __('User search label') ?></th>
                                    <td>
                                        <label class="conl"><input type="radio" name="search" value="1"<?php if (isset($group['perms']['search.topics'])) {
    echo ' checked="checked"';
} ?> tabindex="35" />&#160;<strong><?= __('Yes') ?></strong></label>
                                        <label class="conl"><input type="radio" name="search" value="0"<?php if (!isset($group['perms']['search.topics'])) {
    echo ' checked="checked"';
} ?> tabindex="36" />&#160;<strong><?= __('No') ?></strong></label>
                                        <span class="clearb"><?= __('User search help') ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?= __('User list search label') ?></th>
                                    <td>
                                        <label class="conl"><input type="radio" name="search_users" value="1"<?php if (isset($group['perms']['search.users'])) {
    echo ' checked="checked"';
} ?> tabindex="37" />&#160;<strong><?= __('Yes') ?></strong></label>
                                        <label class="conl"><input type="radio" name="search_users" value="0"<?php if (!isset($group['perms']['search.users'])) {
    echo ' checked="checked"';
} ?> tabindex="38" />&#160;<strong><?= __('No') ?></strong></label>
                                        <span class="clearb"><?= __('User list search help') ?></span>
                                    </td>
                                </tr>
<?php if ($group['info']['g_id'] != ForumEnv::get('FEATHER_GUEST')): ?>
                                <tr>
                                    <th scope="row"><?= __('Send e-mails label') ?></th>
                                    <td>
                                        <label class="conl"><input type="radio" name="send_email" value="1"<?php if (isset($group['perms']['email.send'])) {
    echo ' checked="checked"';
} ?> tabindex="39" />&#160;<strong><?= __('Yes') ?></strong></label>
                                        <label class="conl"><input type="radio" name="send_email" value="0"<?php if (!isset($group['perms']['email.send'])) {
    echo ' checked="checked"';
} ?> tabindex="40" />&#160;<strong><?= __('No') ?></strong></label>
                                        <span class="clearb"><?= __('Send e-mails help') ?></span>
                                    </td>
                                </tr>
<?php endif; ?>
                               <tr>
                                    <th scope="row"><?= __('Post flood label') ?></th>
                                    <td>
                                        <input type="text" name="post_flood" size="5" maxlength="4" value="<?= $group['prefs']['post.min_interval'] ?>" tabindex="41" />
                                        <span><?= __('Post flood help') ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?= __('Search flood label') ?></th>
                                    <td>
                                        <input type="text" name="search_flood" size="5" maxlength="4" value="<?= $group['prefs']['search.min_interval'] ?>" tabindex="42" />
                                        <span><?= __('Search flood help') ?></span>
                                    </td>
                                </tr>
<?php if ($group['info']['g_id'] != ForumEnv::get('FEATHER_GUEST')): ?>
                                <tr>
                                    <th scope="row"><?= __('E-mail flood label') ?></th>
                                    <td>
                                        <input type="text" name="email_flood" size="5" maxlength="4" value="<?= $group['prefs']['email.min_interval'] ?>" tabindex="43" />
                                        <span><?= __('E-mail flood help') ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?= __('Report flood label') ?></th>
                                    <td>
                                        <input type="text" name="report_flood" size="5" maxlength="4" value="<?= $group['prefs']['report.min_interval'] ?>" tabindex="44" />
                                        <span><?= __('Report flood help') ?></span>
                                    </td>
                                </tr>
<?php endif; endif; ?>    </table>
<?php Container::get('hooks')->fire('view.admin.groups.add_edit_group.form'); ?>
<?php if (isset($group['perms']['mod.is_mod'])): ?>
                            <p class="warntext"><?= __('Moderator info') ?></p>
<?php endif; ?>
                        </div>
                    </fieldset>
                </div>
                <p class="submitend"><input type="submit" name="add_edit_group" value="<?= __('Save') ?>" tabindex="45" /></p>
            </form>
        </div>
    </div>
    <div class="clearer"></div>
</div>

<?php
Container::get('hooks')->fire('view.admin.groups.add_edit_group.end');
