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

Container::get('hooks')->fire('view.admin.bans.add.start');
?>

<div class="blockform">
    <h2><span><?php _e('Ban advanced head') ?></span></h2>
    <div class="box">
        <form id="bans2" method="post" action="">
            <div class="inform">
                <input type="hidden" name="csrf_name" value="<?= $csrf_name; ?>"><input type="hidden" name="csrf_value" value="<?= $csrf_value; ?>">
                <input type="hidden" name="mode" value="<?= $ban['mode'] ?>" />
                <?php if ($ban['mode'] == 'edit'): ?>                <input type="hidden" name="ban_id" value="<?= $ban['id'] ?>" />
                <?php endif; ?>
                <?php if ($ban['mode'] == 'add' && isset($ban['user_id'])): ?><input type="hidden" name="ban_user_id" value="<?= $ban['user_id'] ?>" />
                <?php endif; ?><fieldset>
                    <legend><?php _e('Ban advanced subhead') ?></legend>
                    <div class="infldset">
                        <table class="aligntop">
                            <tr>
                                <th scope="row"><?php _e('Username label') ?></th>
                                <td>
                                    <input type="text" name="ban_user" size="25" maxlength="25" value="<?php if (isset($ban['ban_user'])) {
                                        echo Utils::escape($ban['ban_user']);
                                    } ?>" tabindex="1" autofocus />
                                    <span><?php _e('Username help') ?></span>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('IP label') ?></th>
                                <td>
                                    <input type="text" name="ban_ip" size="45" maxlength="255" value="<?php if (isset($ban['ip'])) {
                                        echo Utils::escape($ban['ip']);
                                    } ?>" tabindex="2" />
                                        <span><?php _e('IP help') ?><?php if ($ban['ban_user'] != '' && isset($ban['user_id'])) {
                                                printf(' '.__('IP help link'), '<a href="'.Router::pathFor('usersIpStats', ['id' => $ban['user_id']]).'">'.__('here').'</a>');
                                            } ?></span>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('E-mail label') ?></th>
                                <td>
                                    <input type="text" name="ban_email" size="40" maxlength="80" value="<?php if (isset($ban['email'])) {
                                        echo Utils::escape($ban['email']);
                                    } ?>" tabindex="3" />
                                    <span><?php _e('E-mail help') ?></span>
                                </td>
                            </tr>
                        </table>
                        <p class="topspace"><strong class="warntext"><?php _e('Ban IP range info') ?></strong></p>
                    </div>
                </fieldset>
            </div>
            <div class="inform">
                <fieldset>
                    <legend><?php _e('Message expiry subhead') ?></legend>
                    <div class="infldset">
                        <table class="aligntop">
                            <tr>
                                <th scope="row"><?php _e('Ban message label') ?></th>
                                <td>
                                    <input type="text" name="ban_message" size="50" maxlength="255" value="<?php if (isset($ban['message'])) {
                                        echo Utils::escape($ban['message']);
                                    } ?>" tabindex="4" />
                                    <span><?php _e('Ban message help') ?></span>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Expire date label') ?></th>
                                <td>
                                    <input type="text" name="ban_expire" size="17" maxlength="10" value="<?php if (isset($ban['expire'])) {
                                        echo $ban['expire'];
                                    } ?>" tabindex="5" />
                                    <span><?php _e('Expire date help') ?></span>
                                </td>
                            </tr>
                        </table>
                    </div>
                </fieldset>
            </div>
            <?php Container::get('hooks')->fire('view.admin.bans.add.form'); ?>
            <p class="submitend"><input type="submit" name="add_edit_ban" value="<?php _e('Save') ?>" tabindex="6" /></p>
        </form>
    </div>
</div>
<div class="clearer"></div>
</div>

<?php
Container::get('hooks')->fire('view.admin.bans.add.end');
