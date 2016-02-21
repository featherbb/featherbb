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

Container::get('hooks')->fire('view.profile.change_mail.start');
?>

<div class="blockform">
    <h2><span><?php _e('Change email') ?></span></h2>
    <div class="box">
        <form id="change_email" method="post" action="<?= Router::pathFor('profileAction', ['id' => $id, 'action' => 'change_email']) ?>" onsubmit="return process_form(this)">
            <input type="hidden" name="csrf_name" value="<?= $csrf_name; ?>"><input type="hidden" name="csrf_value" value="<?= $csrf_value; ?>">
            <div class="inform">
                <fieldset>
                    <legend><?php _e('Email legend') ?></legend>
                    <div class="infldset">
                        <input type="hidden" name="form_sent" value="1" />
                        <label class="required"><strong><?php _e('New email') ?> <span><?php _e('Required') ?></span></strong><br /><input type="text" name="req_new_email" size="50" maxlength="80" /><br /></label>
                        <label class="required"><strong><?php _e('Password') ?> <span><?php _e('Required') ?></span></strong><br /><input type="password" name="req_password" size="16" /><br /></label>
                        <p><?php _e('Email instructions') ?></p>
                    </div>
                </fieldset>
            </div>
            <p class="buttons"><input type="submit" name="new_email" value="<?php _e('Submit') ?>" /> <a href="javascript:history.go(-1)"><?php _e('Go back') ?></a></p>
        </form>
    </div>
</div>

<?php
Container::get('hooks')->fire('view.profile.change_mail.end');
