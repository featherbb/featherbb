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

Container::get('hooks')->fire('view.admin.groups.add_edit_group.start');
?>

    <div class="blockform">
        <h2><span><?php _e('Group settings head') ?></span></h2>
        <div class="box">
            <form id="groups2" method="post" action="">
                <input type="hidden" name="csrf_name" value="<?= $csrf_name; ?>"><input type="hidden" name="csrf_value" value="<?= $csrf_value; ?>">
                <p class="submittop"><input type="submit" name="add_edit_group" value="<?php _e('Save') ?>" /></p>
                <div class="inform">
                    <input type="hidden" name="mode" value="<?= $group['mode'] ?>" />
<?php if ($group['mode'] == 'edit'): ?>                    <input type="hidden" name="group_id" value="<?= $id ?>" />
<?php endif; ?><?php if ($group['mode'] == 'add'): ?>                    <input type="hidden" name="base_group" value="<?= $group['base_group'] ?>" />
<?php endif; ?>                    <fieldset>
                        <legend><?php _e('Group settings subhead') ?></legend>
                        <div class="infldset">
                            <p><?php _e('Group settings info') ?></p>
                            <table class="aligntop">
                                <tr>
                                    <th scope="row"><?php _e('Group title label') ?></th>
                                    <td>
                                        <input type="text" name="req_title" size="25" maxlength="50" value="<?php if ($group['mode'] == 'edit') {echo Utils::escape($group['info']['g_title']);} ?>" tabindex="1" required="required" autofocus />
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('User title label') ?></th>
                                    <td>
                                        <input type="text" name="user_title" size="25" maxlength="50" value="<?= Utils::escape($group['info']['g_user_title']) ?>" tabindex="2" />
                                        <span><?php printf(__('User title help'), ($group['info']['g_id'] != ForumEnv::get('FEATHER_GUEST') ? __('Member') : __('Guest'))) ?></span>
                                    </td>
                                </tr>
<?php if ($group['info']['g_id'] != ForumEnv::get('FEATHER_ADMIN')): if ($group['info']['g_id'] != ForumEnv::get('FEATHER_GUEST')): ?>
                                <tr>
                                    <th scope="row"><?php _e('Promote users label') ?></th>
                                    <td>
                                        <select name="promote_next_group" tabindex="3">
                                            <option value="0"><?php _e('Disable promotion') ?></option>
                                            <?= $group_list ?>
                                        </select>
                                        <input type="text" name="promote_min_posts" size="5" maxlength="10" value="<?= Utils::escape($group['info']['g_promote_min_posts']) ?>" tabindex="4" />
                                        <span><?php printf(__('Promote users help'), __('Disable promotion')) ?></span>
                                    </td>
                                </tr>
<?php if ($group['mode'] != 'edit' || ForumSettings::get('o_default_user_group') != $group['info']['g_id']): ?>
                                <tr>
                                    <th scope="row"> <?php _e('Mod privileges label') ?></th>
                                    <td>
                                        <label class="conl"><input type="radio" name="moderator" value="1"<?php if ($group['info']['g_moderator'] == '1') {echo ' checked="checked"';} ?> tabindex="5" />&#160;<strong><?php _e('Yes') ?></strong></label>
                                        <label class="conl"><input type="radio" name="moderator" value="0"<?php if ($group['info']['g_moderator'] == '0') {echo ' checked="checked"';} ?> tabindex="6" />&#160;<strong><?php _e('No') ?></strong></label>
                                        <span class="clearb"><?php _e('Mod privileges help') ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('Edit profile label') ?></th>
                                    <td>
                                        <label class="conl"><input type="radio" name="mod_edit_users" value="1"<?php if (isset($group['perms']['mod.edit_users'])) {echo ' checked="checked"';} ?> tabindex="7" />&#160;<strong><?php _e('Yes') ?></strong></label>
                                        <label class="conl"><input type="radio" name="mod_edit_users" value="0"<?php if (!isset($group['perms']['mod.edit_users'])) {echo ' checked="checked"';} ?> tabindex="8" />&#160;<strong><?php _e('No') ?></strong></label>
                                        <span class="clearb"><?php _e('Edit profile help') ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('Rename users label') ?></th>
                                    <td>
                                        <label class="conl"><input type="radio" name="mod_rename_users" value="1"<?php if (isset($group['perms']['mod.rename_users'])) {echo ' checked="checked"';} ?> tabindex="9" />&#160;<strong><?php _e('Yes') ?></strong></label>
                                        <label class="conl"><input type="radio" name="mod_rename_users" value="0"<?php if (!isset($group['perms']['mod.rename_users'])) {echo ' checked="checked"';} ?> tabindex="10" />&#160;<strong><?php _e('No') ?></strong></label>
                                        <span class="clearb"><?php _e('Rename users help') ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('Change passwords label') ?></th>
                                    <td>
                                        <label class="conl"><input type="radio" name="mod_change_passwords" value="1"<?php if (isset($group['perms']['mod.change_passwords'])) {echo ' checked="checked"';} ?> tabindex="11" />&#160;<strong><?php _e('Yes') ?></strong></label>
                                        <label class="conl"><input type="radio" name="mod_change_passwords" value="0"<?php if (!isset($group['perms']['mod.change_passwords'])) {echo ' checked="checked"';} ?> tabindex="12" />&#160;<strong><?php _e('No') ?></strong></label>
                                        <span class="clearb"><?php _e('Change passwords help') ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('Mod promote users label') ?></th>
                                    <td>
                                        <label class="conl"><input type="radio" name="mod_promote_users" value="1"<?php if (isset($group['perms']['mod.promote_users'])) {echo ' checked="checked"';} ?> tabindex="13" />&#160;<strong><?php _e('Yes') ?></strong></label>
                                        <label class="conl"><input type="radio" name="mod_promote_users" value="0"<?php if (!isset($group['perms']['mod.promote_users'])) {echo ' checked="checked"';} ?> tabindex="14" />&#160;<strong><?php _e('No') ?></strong></label>
                                        <span class="clearb"><?php _e('Mod promote users help') ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('Ban users label') ?></th>
                                    <td>
                                        <label class="conl"><input type="radio" name="mod_ban_users" value="1"<?php if (isset($group['perms']['mod.ban_users'])) {echo ' checked="checked"';} ?> tabindex="15" />&#160;<strong><?php _e('Yes') ?></strong></label>
                                        <label class="conl"><input type="radio" name="mod_ban_users" value="0"<?php if (!isset($group['perms']['mod.ban_users'])) {echo ' checked="checked"';} ?> tabindex="16" />&#160;<strong><?php _e('No') ?></strong></label>
                                        <span class="clearb"><?php _e('Ban users help') ?></span>
                                    </td>
                                </tr>
<?php endif; endif; ?>
                                <tr>
                                    <th scope="row"><?php _e('Read board label') ?></th>
                                    <td>
                                        <label class="conl"><input type="radio" name="read_board" value="1"<?php if (isset($group['perms']['board.read'])) {echo ' checked="checked"';} ?> tabindex="17" />&#160;<strong><?php _e('Yes') ?></strong></label>
                                        <label class="conl"><input type="radio" name="read_board" value="0"<?php if (!isset($group['perms']['board.read'])) {echo ' checked="checked"';} ?> tabindex="18" />&#160;<strong><?php _e('No') ?></strong></label>
                                        <span class="clearb"><?php _e('Read board help') ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('View user info label') ?></th>
                                    <td>
                                        <label class="conl"><input type="radio" name="view_users" value="1"<?php if (isset($group['perms']['users.view'])) {echo ' checked="checked"';} ?> tabindex="19" />&#160;<strong><?php _e('Yes') ?></strong></label>
                                        <label class="conl"><input type="radio" name="view_users" value="0"<?php if (!isset($group['perms']['users.view'])) {echo ' checked="checked"';} ?> tabindex="20" />&#160;<strong><?php _e('No') ?></strong></label>
                                        <span class="clearb"><?php _e('View user info help') ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('Post replies label') ?></th>
                                    <td>
                                        <label class="conl"><input type="radio" name="post_replies" value="1"<?php if (isset($group['perms']['topic.reply'])) {echo ' checked="checked"';} ?> tabindex="21" />&#160;<strong><?php _e('Yes') ?></strong></label>
                                        <label class="conl"><input type="radio" name="post_replies" value="0"<?php if (!isset($group['perms']['topic.reply'])) {echo ' checked="checked"';} ?> tabindex="22" />&#160;<strong><?php _e('No') ?></strong></label>
                                        <span class="clearb"><?php _e('Post replies help') ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('Post topics label') ?></th>
                                    <td>
                                        <label class="conl"><input type="radio" name="post_topics" value="1"<?php if (isset($group['perms']['topic.post'])) {echo ' checked="checked"';} ?> tabindex="23" />&#160;<strong><?php _e('Yes') ?></strong></label>
                                        <label class="conl"><input type="radio" name="post_topics" value="0"<?php if (!isset($group['perms']['topic.post'])) {echo ' checked="checked"';} ?> tabindex="24" />&#160;<strong><?php _e('No') ?></strong></label>
                                        <span class="clearb"><?php _e('Post topics help') ?></span>
                                    </td>
                                </tr>
<?php if ($group['info']['g_id'] != ForumEnv::get('FEATHER_GUEST')): ?>
                                <tr>
                                    <th scope="row"><?php _e('Edit posts label') ?></th>
                                    <td>
                                        <label class="conl"><input type="radio" name="edit_posts" value="1"<?php if (isset($group['perms']['post.edit'])) {echo ' checked="checked"';} ?> tabindex="25" />&#160;<strong><?php _e('Yes') ?></strong></label>
                                        <label class="conl"><input type="radio" name="edit_posts" value="0"<?php if (!isset($group['perms']['post.edit'])) {echo ' checked="checked"';} ?> tabindex="26" />&#160;<strong><?php _e('No') ?></strong></label>
                                        <span class="clearb"><?php _e('Edit posts help') ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('Delete posts label') ?></th>
                                    <td>
                                        <label class="conl"><input type="radio" name="delete_posts" value="1"<?php if (isset($group['perms']['post.delete'])) {echo ' checked="checked"';} ?> tabindex="27" />&#160;<strong><?php _e('Yes') ?></strong></label>
                                        <label class="conl"><input type="radio" name="delete_posts" value="0"<?php if (!isset($group['perms']['post.delete'])) {echo ' checked="checked"';} ?> tabindex="28" />&#160;<strong><?php _e('No') ?></strong></label>
                                        <span class="clearb"><?php _e('Delete posts help') ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('Delete topics label') ?></th>
                                    <td>
                                        <label class="conl"><input type="radio" name="delete_topics" value="1"<?php if (isset($group['perms']['topic.delete'])) {echo ' checked="checked"';} ?> tabindex="29" />&#160;<strong><?php _e('Yes') ?></strong></label>
                                        <label class="conl"><input type="radio" name="delete_topics" value="0"<?php if (!isset($group['perms']['topic.delete'])) {echo ' checked="checked"';} ?> tabindex="30" />&#160;<strong><?php _e('No') ?></strong></label>
                                        <span class="clearb"><?php _e('Delete topics help') ?></span>
                                    </td>
                                </tr>
<?php endif; ?>
                                <tr>
                                    <th scope="row"><?php _e('Post links label') ?></th>
                                    <td>
                                        <label class="conl"><input type="radio" name="post_links" value="1"<?php if (isset($group['perms']['post.links'])) {echo ' checked="checked"';} ?> tabindex="31" />&#160;<strong><?php _e('Yes') ?></strong></label>
                                        <label class="conl"><input type="radio" name="post_links" value="0"<?php if (!isset($group['perms']['post.links'])) {echo ' checked="checked"';} ?> tabindex="32" />&#160;<strong><?php _e('No') ?></strong></label>
                                        <span class="clearb"><?php _e('Post links help') ?></span>
                                    </td>
                                </tr>
<?php if ($group['info']['g_id'] != ForumEnv::get('FEATHER_GUEST')): ?>
                                <tr>
                                    <th scope="row"><?php _e('Set own title label') ?></th>
                                    <td>
                                        <label class="conl"><input type="radio" name="set_title" value="1"<?php if (isset($group['perms']['user.set_title'])) {echo ' checked="checked"';} ?> tabindex="33" />&#160;<strong><?php _e('Yes') ?></strong></label>
                                        <label class="conl"><input type="radio" name="set_title" value="0"<?php if (!isset($group['perms']['user.set_title'])) {echo ' checked="checked"';} ?> tabindex="34" />&#160;<strong><?php _e('No') ?></strong></label>
                                        <span class="clearb"><?php _e('Set own title help') ?></span>
                                    </td>
                                </tr>
<?php endif; ?>
                                <tr>
                                    <th scope="row"><?php _e('User search label') ?></th>
                                    <td>
                                        <label class="conl"><input type="radio" name="search" value="1"<?php if (isset($group['perms']['search.topics'])) {echo ' checked="checked"';} ?> tabindex="35" />&#160;<strong><?php _e('Yes') ?></strong></label>
                                        <label class="conl"><input type="radio" name="search" value="0"<?php if (!isset($group['perms']['search.topics'])) {echo ' checked="checked"';} ?> tabindex="36" />&#160;<strong><?php _e('No') ?></strong></label>
                                        <span class="clearb"><?php _e('User search help') ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('User list search label') ?></th>
                                    <td>
                                        <label class="conl"><input type="radio" name="search_users" value="1"<?php if (isset($group['perms']['search.users'])) {echo ' checked="checked"';} ?> tabindex="37" />&#160;<strong><?php _e('Yes') ?></strong></label>
                                        <label class="conl"><input type="radio" name="search_users" value="0"<?php if (!isset($group['perms']['search.users'])) {echo ' checked="checked"';} ?> tabindex="38" />&#160;<strong><?php _e('No') ?></strong></label>
                                        <span class="clearb"><?php _e('User list search help') ?></span>
                                    </td>
                                </tr>
<?php if ($group['info']['g_id'] != ForumEnv::get('FEATHER_GUEST')): ?>
                                <tr>
                                    <th scope="row"><?php _e('Send e-mails label') ?></th>
                                    <td>
                                        <label class="conl"><input type="radio" name="send_email" value="1"<?php if (isset($group['perms']['email.send'])) {echo ' checked="checked"';} ?> tabindex="39" />&#160;<strong><?php _e('Yes') ?></strong></label>
                                        <label class="conl"><input type="radio" name="send_email" value="0"<?php if (!isset($group['perms']['email.send'])) {echo ' checked="checked"';} ?> tabindex="40" />&#160;<strong><?php _e('No') ?></strong></label>
                                        <span class="clearb"><?php _e('Send e-mails help') ?></span>
                                    </td>
                                </tr>
<?php endif; ?>
                               <tr>
                                    <th scope="row"><?php _e('Post flood label') ?></th>
                                    <td>
                                        <input type="text" name="post_flood" size="5" maxlength="4" value="<?= $group['prefs']['post.min_interval'] ?>" tabindex="41" />
                                        <span><?php _e('Post flood help') ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('Search flood label') ?></th>
                                    <td>
                                        <input type="text" name="search_flood" size="5" maxlength="4" value="<?= $group['prefs']['search.min_interval'] ?>" tabindex="42" />
                                        <span><?php _e('Search flood help') ?></span>
                                    </td>
                                </tr>
<?php if ($group['info']['g_id'] != ForumEnv::get('FEATHER_GUEST')): ?>
                                <tr>
                                    <th scope="row"><?php _e('E-mail flood label') ?></th>
                                    <td>
                                        <input type="text" name="email_flood" size="5" maxlength="4" value="<?= $group['prefs']['email.min_interval'] ?>" tabindex="43" />
                                        <span><?php _e('E-mail flood help') ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('Report flood label') ?></th>
                                    <td>
                                        <input type="text" name="report_flood" size="5" maxlength="4" value="<?= $group['prefs']['report.min_interval'] ?>" tabindex="44" />
                                        <span><?php _e('Report flood help') ?></span>
                                    </td>
                                </tr>
<?php endif; endif; ?>    </table>
<?php Container::get('hooks')->fire('view.admin.groups.add_edit_group.form'); ?>
<?php if ($group['info']['g_moderator'] == '1'): ?>
                            <p class="warntext"><?php _e('Moderator info') ?></p>
<?php endif; ?>
                        </div>
                    </fieldset>
                </div>
                <p class="submitend"><input type="submit" name="add_edit_group" value="<?php _e('Save') ?>" tabindex="45" /></p>
            </form>
        </div>
    </div>
    <div class="clearer"></div>
</div>

<?php
Container::get('hooks')->fire('view.admin.groups.add_edit_group.end');
