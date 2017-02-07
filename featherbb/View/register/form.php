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

Container::get('hooks')->fire('view.register.form.start');

// If there are errors, we display them
if (!empty($errors)) {
    ?>
<div id="posterror" class="block">
    <h2><span><?= __('Registration errors') ?></span></h2>
    <div class="box">
        <div class="inbox error-info">
            <p><?= __('Registration errors info') ?></p>
            <ul class="error-list">
<?php

    foreach ($errors as $cur_error) {
        echo "\t\t\t\t".'<li><strong>'.$cur_error.'</strong></li>'."\n";
    }
    ?>
            </ul>
        </div>
    </div>
</div>

<?php

}
?>
<div id="regform" class="blockform">
    <h2><span><?= __('Register') ?></span></h2>
    <div class="box">
        <form id="register" method="post" action="">
        <input type="hidden" name="csrf_name" value="<?= $csrf_name; ?>"><input type="hidden" name="csrf_value" value="<?= $csrf_value; ?>">
            <div class="inform">
                <div class="forminfo">
                    <h3><?= __('Important information') ?></h3>
                    <p><?= __('Desc 1') ?></p>
                    <p><?= __('Desc 2') ?></p>
                </div>
                <fieldset>
                    <legend><?= __('Username legend') ?></legend>
                    <div class="infldset">
                        <input type="hidden" name="form_sent" value="1" />
                        <input type="hidden" name="username" value="" />
                        <input type="hidden" name="password" value="" />
                        <label class="required"><strong><?= __('Username') ?> <span><?= __('Required') ?></span></strong><br /><input type="text" name="req_user" value="<?= Input::post('req_user'); ?>" size="25" maxlength="25" required autofocus /><br /></label>
                    </div>
                </fieldset>
            </div>
<?php if (ForumSettings::get('o_regs_verify') == '0'): ?>
            <div class="inform">
                <fieldset>
                    <legend><?= __('Pass legend') ?></legend>
                    <div class="infldset">
                        <label class="conl required"><strong><?= __('Password') ?> <span><?= __('Required') ?></span></strong><br /><input type="password" name="req_password1" value="<?= Input::post('req_password1') ?>" size="16" required /><br /></label>
                        <label class="conl required"><strong><?= __('Confirm pass') ?> <span><?= __('Required') ?></span></strong><br /><input type="password" name="req_password2" value="<?= Input::post('req_password2'); ?>" size="16" required /><br /></label>
                        <p class="clearb"><?= __('Pass info') ?></p>
                    </div>
                </fieldset>
            </div>
<?php endif; ?>
            <div class="inform">
                <fieldset>
                    <legend><?php echo(ForumSettings::get('o_regs_verify') == '1') ? __('Email legend 2') : __('Email legend') ?></legend>
                    <div class="infldset">
<?php if (ForumSettings::get('o_regs_verify') == '1'): ?>
                        <p><?= __('Email info') ?></p>
<?php endif; ?>
                       <label class="required"><strong><?= __('Email') ?> <span><?= __('Required') ?></span></strong><br />
                        <input type="email" name="req_email1" value="<?= Input::post('req_email1'); ?>" size="50" maxlength="80" required /><br /></label>
<?php if (ForumSettings::get('o_regs_verify') == '1'): ?>
                        <label class="required"><strong><?= __('Confirm email') ?> <span><?= __('Required') ?></span></strong><br />
                        <input type="email" name="req_email2" value="<?= Input::post('req_email2'); ?>" size="50" maxlength="80" required /><br /></label>
<?php endif; ?>
                    </div>
                </fieldset>
            </div>
<?php
        // Only display the language selection box if there's more than one language available
        if (count($languages) > 1) {
            ?>
            <div class="inform">
                <fieldset>
                    <legend><?= __('Localisation legend') ?></legend>
                    <div class="infldset">
                            <label><?= __('Language') ?>
                            <br /><select name="language">
<?php

            foreach ($languages as $temp) {
                if (ForumSettings::get('language') == $temp) {
                    echo "\t\t\t\t\t\t\t\t".'<option value="'.$temp.'" selected="selected">'.$temp.'</option>'."\n";
                } else {
                    echo "\t\t\t\t\t\t\t\t".'<option value="'.$temp.'">'.$temp.'</option>'."\n";
                }
            }

            ?>
                            </select>
                            <br /></label>
                    </div>
                </fieldset>
            </div>
<?php

        }
?>
            <div class="inform">
                <fieldset>
                    <legend><?= __('Robot title') ?></legend>
                    <div class="infldset">
                        <p><?= __('Robot info')    ?></p>
                        <label class="required"><strong><?= sprintf(__('Robot question'), $question[$index_questions]) ?> <span><?= __('Required') ?></span></strong><br /><input name="captcha" id="captcha" type="text" size="10" maxlength="30" required /><input name="captcha_q" value="<?= $qencoded ?>" type="hidden" /></label>
                    </div>
                </fieldset>
            </div>
            <p class="buttons"><input type="submit" name="register" value="<?= __('Register') ?>" /></p>
        </form>
    </div>
</div>

<?php
Container::get('hooks')->fire('view.register.form.end');
