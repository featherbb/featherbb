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

Container::get('hooks')->fire('view.admin.users.ban_users.start');
?>

    <div class="blockform">
        <h2><span><?php _e('Ban users') ?></span></h2>
        <div class="box">
            <form id="bans2" name="confirm_ban_users" method="post" action="<?= Router::pathFor('adminUsers') ?>">
                <input type="hidden" name="csrf_name" value="<?= $csrf_name; ?>"><input type="hidden" name="csrf_value" value="<?= $csrf_value; ?>">
                <input type="hidden" name="users" value="<?= implode(',', $user_ids) ?>" />
                <div class="inform">
                    <fieldset>
                        <legend><?php _e('Message expiry subhead') ?></legend>
                        <div class="infldset">
                            <table class="aligntop">
                                <tr>
                                    <th scope="row"><?php _e('Ban message label') ?></th>
                                    <td>
                                        <input type="text" name="ban_message" size="50" maxlength="255" tabindex="1" />
                                        <span><?php _e('Ban message help') ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('Expire date label') ?></th>
                                    <td>
                                        <input type="text" name="ban_expire" size="17" maxlength="10" tabindex="2" />
                                        <span><?php _e('Expire date help') ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('Ban IP label') ?></th>
                                    <td>
                                        <label class="conl"><input type="radio" name="ban_the_ip" tabindex="3" value="1" checked="checked" />&#160;<strong><?php _e('Yes') ?></strong></label>
                                        <label class="conl"><input type="radio" name="ban_the_ip" tabindex="4" value="0" checked="checked" />&#160;<strong><?php _e('No') ?></strong></label>
                                        <span class="clearb"><?php _e('Ban IP help') ?></span>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </fieldset>
                </div>
                <p class="submitend"><input type="submit" name="ban_users_comply" value="<?php _e('Save') ?>" tabindex="3" /></p>
            </form>
        </div>
    </div>
    <div class="clearer"></div>
</div>

<?php
Container::get('hooks')->fire('view.admin.users.ban_users.end');
