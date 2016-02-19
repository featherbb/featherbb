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

Container::get('hooks')->fire('view.login.form.start');
?>

<div class="blockform">
    <h2><span><?php _e('Login') ?></span></h2>
    <div class="box">
        <form id="login" method="post" action="<?= Router::pathFor('login') ?>" onsubmit="return process_form(this)">
            <input type="hidden" name="csrf_name" value="<?= $csrf_name; ?>"><input type="hidden" name="csrf_value" value="<?= $csrf_value; ?>">
            <div class="inform">
                <fieldset>
                    <legend><?php _e('Login legend') ?></legend>
                    <div class="infldset">
                        <input type="hidden" name="form_sent" value="1" />
                        <label class="conl required"><strong><?php _e('Username') ?> <span><?php _e('Required') ?></span></strong><br /><input type="text" name="req_username" size="25" maxlength="25" tabindex="1" /><br /></label>
                        <label class="conl required"><strong><?php _e('Password') ?> <span><?php _e('Required') ?></span></strong><br /><input type="password" name="req_password" size="25" tabindex="2" /><br /></label>

                        <div class="rbox clearb">
                            <label><input type="checkbox" name="save_pass" value="1" tabindex="3" checked="checked" /><?php _e('Remember me') ?><br /></label>
                        </div>

                        <p class="clearb"><?php _e('Login info') ?></p>
                        <p class="actions"><span><a href="<?= Router::pathFor('register') ?>" tabindex="5"><?php _e('Not registered') ?></a></span> <span><a href="<?= Router::pathFor('resetPassword') ?>" tabindex="6"><?php _e('Forgotten pass') ?></a></span></p>
                    </div>
                </fieldset>
            </div>
            <p class="buttons"><input type="submit" name="login" value="<?php _e('Login') ?>" tabindex="4" /></p>
        </form>
    </div>
</div>

<?php
Container::get('hooks')->fire('view.login.form.end');
