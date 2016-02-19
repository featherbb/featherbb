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

Container::get('hooks')->fire('view.login.password_forgotten.start');

// If there are errors, we display them
if (!empty($errors)) {
    ?>
<div id="posterror" class="block">
    <h2><span><?php _e('New password errors') ?></span></h2>
    <div class="box">
        <div class="inbox error-info">
            <p><?php _e('New passworderrors info') ?></p>
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
<div class="blockform">
    <h2><span><?php _e('Request pass') ?></span></h2>
    <div class="box">
        <form id="request_pass" method="post" action="<?= Router::pathFor('resetPassword') ?>" onsubmit="this.request_pass.disabled=true;if(process_form(this)){return true;}else{this.request_pass.disabled=false;return false;}">
            <input type="hidden" name="csrf_name" value="<?= $csrf_name; ?>"><input type="hidden" name="csrf_value" value="<?= $csrf_value; ?>">
            <div class="inform">
                <fieldset>
                    <legend><?php _e('Request pass legend') ?></legend>
                    <div class="infldset">
                        <input type="hidden" name="form_sent" value="1" />
                        <label class="required"><strong><?php _e('Email') ?> <span><?php _e('Required') ?></span></strong><br /><input id="req_email" type="text" name="req_email" size="50" maxlength="80" /><br /></label>
                        <p><?php _e('Request pass info') ?></p>
                    </div>
                </fieldset>
            </div>
            <p class="buttons"><input type="submit" name="request_pass" value="<?php _e('Submit') ?>" /><?php if (empty($errors)): ?> <a href="javascript:history.go(-1)"><?php _e('Go back') ?></a><?php endif; ?></p>
        </form>
    </div>
</div>

<?php
Container::get('hooks')->fire('view.login.password_forgotten.end');
