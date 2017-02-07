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

Container::get('hooks')->fire('view.admin.permissions.start');
?>

    <div class="blockform">
        <h2><span><?= __('Permissions head') ?></span></h2>
        <div class="box">
            <form method="post" action="<?= Router::pathFor('adminPermissions') ?>">
                <input type="hidden" name="csrf_name" value="<?= $csrf_name; ?>"><input type="hidden" name="csrf_value" value="<?= $csrf_value; ?>">
                <p class="submittop"><input type="submit" name="save" value="<?= __('Save changes') ?>" /></p>
                <div class="inform">
                    <input type="hidden" name="form_sent" value="1" />
                    <fieldset>
                        <legend><?= __('Posting subhead') ?></legend>
                        <div class="infldset">
                            <table class="aligntop">
                                <tr>
                                    <th scope="row"><?= __('BBCode label') ?></th>
                                    <td>
                                        <label class="conl"><input type="radio" name="form[message_bbcode]" value="1"<?php if (ForumSettings::get('p_message_bbcode') == '1') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?= __('Yes') ?></strong></label>
                                        <label class="conl"><input type="radio" name="form[message_bbcode]" value="0"<?php if (ForumSettings::get('p_message_bbcode') == '0') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?= __('No') ?></strong></label>
                                        <span class="clearb"><?= __('BBCode help') ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?= __('Image tag label') ?></th>
                                    <td>
                                        <label class="conl"><input type="radio" name="form[message_img_tag]" value="1"<?php if (ForumSettings::get('p_message_img_tag') == '1') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?= __('Yes') ?></strong></label>
                                        <label class="conl"><input type="radio" name="form[message_img_tag]" value="0"<?php if (ForumSettings::get('p_message_img_tag') == '0') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?= __('No') ?></strong></label>
                                        <span class="clearb"><?= __('Image tag help') ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?= __('All caps message label') ?></th>
                                    <td>
                                        <label class="conl"><input type="radio" name="form[message_all_caps]" value="1"<?php if (ForumSettings::get('p_message_all_caps') == '1') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?= __('Yes') ?></strong></label>
                                        <label class="conl"><input type="radio" name="form[message_all_caps]" value="0"<?php if (ForumSettings::get('p_message_all_caps') == '0') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?= __('No') ?></strong></label>
                                        <span class="clearb"><?= __('All caps message help') ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?= __('All caps subject label') ?></th>
                                    <td>
                                        <label class="conl"><input type="radio" name="form[subject_all_caps]" value="1"<?php if (ForumSettings::get('p_subject_all_caps') == '1') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?= __('Yes') ?></strong></label>
                                        <label class="conl"><input type="radio" name="form[subject_all_caps]" value="0"<?php if (ForumSettings::get('p_subject_all_caps') == '0') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?= __('No') ?></strong></label>
                                        <span class="clearb"><?= __('All caps subject help') ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?= __('Require e-mail label') ?></th>
                                    <td>
                                        <label class="conl"><input type="radio" name="form[force_guest_email]" value="1"<?php if (ForumSettings::get('p_force_guest_email') == '1') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?= __('Yes') ?></strong></label>
                                        <label class="conl"><input type="radio" name="form[force_guest_email]" value="0"<?php if (ForumSettings::get('p_force_guest_email') == '0') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?= __('No') ?></strong></label>
                                        <span class="clearb"><?= __('Require e-mail help') ?></span>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </fieldset>
                </div>
                <div class="inform">
                    <fieldset>
                        <legend><?= __('Signatures subhead') ?></legend>
                        <div class="infldset">
                            <table class="aligntop">
                                <tr>
                                    <th scope="row"><?= __('BBCode sigs label') ?></th>
                                    <td>
                                        <label class="conl"><input type="radio" name="form[sig_bbcode]" value="1"<?php if (ForumSettings::get('p_sig_bbcode') == '1') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?= __('Yes') ?></strong></label>
                                        <label class="conl"><input type="radio" name="form[sig_bbcode]" value="0"<?php if (ForumSettings::get('p_sig_bbcode') == '0') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?= __('No') ?></strong></label>
                                        <span class="clearb"><?= __('BBCode sigs help') ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?= __('Image tag sigs label') ?></th>
                                    <td>
                                        <label class="conl"><input type="radio" name="form[sig_img_tag]" value="1"<?php if (ForumSettings::get('p_sig_img_tag') == '1') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?= __('Yes') ?></strong></label>
                                        <label class="conl"><input type="radio" name="form[sig_img_tag]" value="0"<?php if (ForumSettings::get('p_sig_img_tag') == '0') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?= __('No') ?></strong></label>
                                        <span class="clearb"><?= __('Image tag sigs help') ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?= __('All caps sigs label') ?></th>
                                    <td>
                                        <label class="conl"><input type="radio" name="form[sig_all_caps]" value="1"<?php if (ForumSettings::get('p_sig_all_caps') == '1') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?= __('Yes') ?></strong></label>
                                        <label class="conl"><input type="radio" name="form[sig_all_caps]" value="0"<?php if (ForumSettings::get('p_sig_all_caps') == '0') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?= __('No') ?></strong></label>
                                        <span class="clearb"><?= __('All caps sigs help') ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?= __('Max sig length label') ?></th>
                                    <td>
                                        <input type="text" name="form[sig_length]" size="5" maxlength="5" value="<?= ForumSettings::get('p_sig_length') ?>" />
                                        <span class="clearb"><?= __('Max sig length help') ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?= __('Max sig lines label') ?></th>
                                    <td>
                                        <input type="text" name="form[sig_lines]" size="3" maxlength="3" value="<?= ForumSettings::get('p_sig_lines') ?>" />
                                        <span class="clearb"><?= __('Max sig lines help') ?></span>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </fieldset>
                </div>
                <div class="inform">
                    <fieldset>
                        <legend><?= __('Registration subhead') ?></legend>
                        <div class="infldset">
                            <table class="aligntop">
                                <tr>
                                    <th scope="row"><?= __('Banned e-mail label') ?></th>
                                    <td>
                                        <label class="conl"><input type="radio" name="form[allow_banned_email]" value="1"<?php if (ForumSettings::get('p_allow_banned_email') == '1') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?= __('Yes') ?></strong></label>
                                        <label class="conl"><input type="radio" name="form[allow_banned_email]" value="0"<?php if (ForumSettings::get('p_allow_banned_email') == '0') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?= __('No') ?></strong></label>
                                        <span class="clearb"><?= __('Banned e-mail help') ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?= __('Duplicate e-mail label') ?></th>
                                    <td>
                                        <label class="conl"><input type="radio" name="form[allow_dupe_email]" value="1"<?php if (ForumSettings::get('p_allow_dupe_email') == '1') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?= __('Yes') ?></strong></label>
                                        <label class="conl"><input type="radio" name="form[allow_dupe_email]" value="0"<?php if (ForumSettings::get('p_allow_dupe_email') == '0') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?= __('No') ?></strong></label>
                                        <span class="clearb"><?= __('Duplicate e-mail help') ?></span>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </fieldset>
                </div>
                <?php Container::get('hooks')->fire('view.admin.permissions.form'); ?>
                <p class="submitend"><input type="submit" name="save" value="<?= __('Save changes') ?>" /></p>
            </form>
        </div>
    </div>
    <div class="clearer"></div>
</div>

<?php
Container::get('hooks')->fire('view.admin.permissions.end');
