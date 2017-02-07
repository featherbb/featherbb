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
    <h2><span><?= __('Login') ?></span></h2>
    <div class="box">
        <form id="login" method="post" action="<?= Router::pathFor('login') ?>">
            <input type="hidden" name="csrf_name" value="<?= $csrf_name; ?>"><input type="hidden" name="csrf_value" value="<?= $csrf_value; ?>">
            <div class="inform">
                <fieldset>
                    <legend><?= __('Login legend') ?></legend>
                    <div class="infldset">
                        <input type="hidden" name="form_sent" value="1" />
                        <label class="conl required"><strong><?= __('Username') ?> <span><?= __('Required') ?></span></strong><br /><input type="text" name="req_username" size="25" maxlength="25" tabindex="1" required autofocus /><br /></label>
                        <label class="conl required"><strong><?= __('Password') ?> <span><?= __('Required') ?></span></strong><br /><input type="password" name="req_password" size="25" tabindex="2" required /><br /></label>

                        <div class="rbox clearb">
                            <label><input type="checkbox" name="save_pass" value="1" tabindex="3" checked="checked" /><?= __('Remember me') ?><br /></label>
                        </div>

                        <p class="clearb"><?= __('Login info') ?></p>
                        <p class="actions"><span><a href="<?= Router::pathFor('register') ?>" tabindex="5"><?= __('Not registered') ?></a></span> <span><a href="<?= Router::pathFor('resetPassword') ?>" tabindex="6"><?= __('Forgotten pass') ?></a></span></p>
                    </div>
                </fieldset>
            </div>
            <p class="buttons"><input type="submit" name="login" value="<?= __('Login') ?>" tabindex="4" /></p>
        </form>
    </div>
</div>

<?php
Container::get('hooks')->fire('view.login.form.end');
