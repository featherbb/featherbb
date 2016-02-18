<?php

/**
 * Copyright (C) 2015-2016 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

use FeatherBB\Core\Random;
use FeatherBB\Core\Utils;

// Make sure no one attempts to run this script "directly"
if (!isset($feather)) {
    exit;
}

Container::get('hooks')->fire('view.admin.options.start');
?>

    <div class="blockform">
        <h2><span><?php _e('Options head') ?></span></h2>
        <div class="box">
            <form method="post" action="<?= $feather->urlFor('adminOptions') ?>">
                <input type="hidden" name="<?= $csrf_key; ?>" value="<?= $csrf_token; ?>">
                <p class="submittop"><input type="submit" name="save" value="<?php _e('Save changes') ?>" /></p>
                <div class="inform">
                    <input type="hidden" name="form_sent" value="1" />
                    <fieldset>
                        <legend><?php _e('Essentials subhead') ?></legend>
                        <div class="infldset">
                            <table class="aligntop">
                                <tr>
                                    <th scope="row"><?php _e('Board title label') ?></th>
                                    <td>
                                        <input type="text" name="form_board_title" size="50" maxlength="255" value="<?= Utils::escape($feather->forum_settings['o_board_title']) ?>" />
                                        <span><?php _e('Board title help') ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('Board desc label') ?></th>
                                    <td>
                                        <textarea name="form_board_desc" cols="60" rows="3"><?= Utils::escape($feather->forum_settings['o_board_desc']) ?></textarea>
                                        <span><?php _e('Board desc help') ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('Base URL label') ?></th>
                                    <td>
                                        <input type="text" name="form_base_url" size="50" maxlength="100" value="<?= Utils::escape($feather->forum_settings['o_base_url']) ?>" />
                                        <span><?php _e('Base URL help') ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('Timezone label') ?></th>
                                    <td>
                                        <select name="form_default_timezone">
                                            <option value="-12"<?php if ($feather->forum_settings['o_default_timezone'] == -12) {
    echo ' selected="selected"';
} ?>><?php _e('UTC-12:00') ?></option>
                                            <option value="-11"<?php if ($feather->forum_settings['o_default_timezone'] == -11) {
    echo ' selected="selected"';
} ?>><?php _e('UTC-11:00') ?></option>
                                            <option value="-10"<?php if ($feather->forum_settings['o_default_timezone'] == -10) {
    echo ' selected="selected"';
} ?>><?php _e('UTC-10:00') ?></option>
                                            <option value="-9.5"<?php if ($feather->forum_settings['o_default_timezone'] == -9.5) {
    echo ' selected="selected"';
} ?>><?php _e('UTC-09:30') ?></option>
                                            <option value="-9"<?php if ($feather->forum_settings['o_default_timezone'] == -9) {
    echo ' selected="selected"';
} ?>><?php _e('UTC-09:00') ?></option>
                                            <option value="-8.5"<?php if ($feather->forum_settings['o_default_timezone'] == -8.5) {
    echo ' selected="selected"';
} ?>><?php _e('UTC-08:30') ?></option>
                                            <option value="-8"<?php if ($feather->forum_settings['o_default_timezone'] == -8) {
    echo ' selected="selected"';
} ?>><?php _e('UTC-08:00') ?></option>
                                            <option value="-7"<?php if ($feather->forum_settings['o_default_timezone'] == -7) {
    echo ' selected="selected"';
} ?>><?php _e('UTC-07:00') ?></option>
                                            <option value="-6"<?php if ($feather->forum_settings['o_default_timezone'] == -6) {
    echo ' selected="selected"';
} ?>><?php _e('UTC-06:00') ?></option>
                                            <option value="-5"<?php if ($feather->forum_settings['o_default_timezone'] == -5) {
    echo ' selected="selected"';
} ?>><?php _e('UTC-05:00') ?></option>
                                            <option value="-4"<?php if ($feather->forum_settings['o_default_timezone'] == -4) {
    echo ' selected="selected"';
} ?>><?php _e('UTC-04:00') ?></option>
                                            <option value="-3.5"<?php if ($feather->forum_settings['o_default_timezone'] == -3.5) {
    echo ' selected="selected"';
} ?>><?php _e('UTC-03:30') ?></option>
                                            <option value="-3"<?php if ($feather->forum_settings['o_default_timezone'] == -3) {
    echo ' selected="selected"';
} ?>><?php _e('UTC-03:00') ?></option>
                                            <option value="-2"<?php if ($feather->forum_settings['o_default_timezone'] == -2) {
    echo ' selected="selected"';
} ?>><?php _e('UTC-02:00') ?></option>
                                            <option value="-1"<?php if ($feather->forum_settings['o_default_timezone'] == -1) {
    echo ' selected="selected"';
} ?>><?php _e('UTC-01:00') ?></option>
                                            <option value="0"<?php if ($feather->forum_settings['o_default_timezone'] == 0) {
    echo ' selected="selected"';
} ?>><?php _e('UTC') ?></option>
                                            <option value="1"<?php if ($feather->forum_settings['o_default_timezone'] == 1) {
    echo ' selected="selected"';
} ?>><?php _e('UTC+01:00') ?></option>
                                            <option value="2"<?php if ($feather->forum_settings['o_default_timezone'] == 2) {
    echo ' selected="selected"';
} ?>><?php _e('UTC+02:00') ?></option>
                                            <option value="3"<?php if ($feather->forum_settings['o_default_timezone'] == 3) {
    echo ' selected="selected"';
} ?>><?php _e('UTC+03:00') ?></option>
                                            <option value="3.5"<?php if ($feather->forum_settings['o_default_timezone'] == 3.5) {
    echo ' selected="selected"';
} ?>><?php _e('UTC+03:30') ?></option>
                                            <option value="4"<?php if ($feather->forum_settings['o_default_timezone'] == 4) {
    echo ' selected="selected"';
} ?>><?php _e('UTC+04:00') ?></option>
                                            <option value="4.5"<?php if ($feather->forum_settings['o_default_timezone'] == 4.5) {
    echo ' selected="selected"';
} ?>><?php _e('UTC+04:30') ?></option>
                                            <option value="5"<?php if ($feather->forum_settings['o_default_timezone'] == 5) {
    echo ' selected="selected"';
} ?>><?php _e('UTC+05:00') ?></option>
                                            <option value="5.5"<?php if ($feather->forum_settings['o_default_timezone'] == 5.5) {
    echo ' selected="selected"';
} ?>><?php _e('UTC+05:30') ?></option>
                                            <option value="5.75"<?php if ($feather->forum_settings['o_default_timezone'] == 5.75) {
    echo ' selected="selected"';
} ?>><?php _e('UTC+05:45') ?></option>
                                            <option value="6"<?php if ($feather->forum_settings['o_default_timezone'] == 6) {
    echo ' selected="selected"';
} ?>><?php _e('UTC+06:00') ?></option>
                                            <option value="6.5"<?php if ($feather->forum_settings['o_default_timezone'] == 6.5) {
    echo ' selected="selected"';
} ?>><?php _e('UTC+06:30') ?></option>
                                            <option value="7"<?php if ($feather->forum_settings['o_default_timezone'] == 7) {
    echo ' selected="selected"';
} ?>><?php _e('UTC+07:00') ?></option>
                                            <option value="8"<?php if ($feather->forum_settings['o_default_timezone'] == 8) {
    echo ' selected="selected"';
} ?>><?php _e('UTC+08:00') ?></option>
                                            <option value="8.75"<?php if ($feather->forum_settings['o_default_timezone'] == 8.75) {
    echo ' selected="selected"';
} ?>><?php _e('UTC+08:45') ?></option>
                                            <option value="9"<?php if ($feather->forum_settings['o_default_timezone'] == 9) {
    echo ' selected="selected"';
} ?>><?php _e('UTC+09:00') ?></option>
                                            <option value="9.5"<?php if ($feather->forum_settings['o_default_timezone'] == 9.5) {
    echo ' selected="selected"';
} ?>><?php _e('UTC+09:30') ?></option>
                                            <option value="10"<?php if ($feather->forum_settings['o_default_timezone'] == 10) {
    echo ' selected="selected"';
} ?>><?php _e('UTC+10:00') ?></option>
                                            <option value="10.5"<?php if ($feather->forum_settings['o_default_timezone'] == 10.5) {
    echo ' selected="selected"';
} ?>><?php _e('UTC+10:30') ?></option>
                                            <option value="11"<?php if ($feather->forum_settings['o_default_timezone'] == 11) {
    echo ' selected="selected"';
} ?>><?php _e('UTC+11:00') ?></option>
                                            <option value="11.5"<?php if ($feather->forum_settings['o_default_timezone'] == 11.5) {
    echo ' selected="selected"';
} ?>><?php _e('UTC+11:30') ?></option>
                                            <option value="12"<?php if ($feather->forum_settings['o_default_timezone'] == 12) {
    echo ' selected="selected"';
} ?>><?php _e('UTC+12:00') ?></option>
                                            <option value="12.75"<?php if ($feather->forum_settings['o_default_timezone'] == 12.75) {
    echo ' selected="selected"';
} ?>><?php _e('UTC+12:45') ?></option>
                                            <option value="13"<?php if ($feather->forum_settings['o_default_timezone'] == 13) {
    echo ' selected="selected"';
} ?>><?php _e('UTC+13:00') ?></option>
                                            <option value="14"<?php if ($feather->forum_settings['o_default_timezone'] == 14) {
    echo ' selected="selected"';
} ?>><?php _e('UTC+14:00') ?></option>
                                        </select>
                                        <span><?php _e('Timezone help') ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('DST label') ?></th>
                                    <td>
                                        <label class="conl"><input type="radio" name="form_default_dst" value="1"<?php if ($feather->forum_settings['o_default_dst'] == '1') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?php _e('Yes') ?></strong></label>
                                        <label class="conl"><input type="radio" name="form_default_dst" value="0"<?php if ($feather->forum_settings['o_default_dst'] == '0') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?php _e('No') ?></strong></label>
                                        <span class="clearb"><?php _e('DST help') ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('Language label') ?></th>
                                    <td>
                                        <select name="form_default_lang">
                                            <?= $languages; ?>
                                        </select>
                                        <span><?php _e('Language help') ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('Default style label') ?></th>
                                    <td>
                                        <select name="form_default_style">
                                            <?= $styles; ?>
                                        </select>
                                        <span><?php _e('Default style help') ?></span>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </fieldset>
                </div>
<?php

    $diff = ($feather->user->timezone + $feather->user->dst) * 3600;
    $timestamp = time() + $diff;

?>
                <div class="inform">
                    <fieldset>
                        <legend><?php _e('Timeouts subhead') ?></legend>
                        <div class="infldset">
                            <table class="aligntop">
                                <tr>
                                    <th scope="row"><?php _e('Time format label') ?></th>
                                    <td>
                                        <input type="text" name="form_time_format" size="25" maxlength="25" value="<?= Utils::escape($feather->forum_settings['o_time_format']) ?>" />
                                        <span><?php printf(__('Time format help'), gmdate($feather->forum_settings['o_time_format'], $timestamp), '<a href="http://www.php.net/manual/en/function.date.php">'.__('PHP manual').'</a>') ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('Date format label') ?></th>
                                    <td>
                                        <input type="text" name="form_date_format" size="25" maxlength="25" value="<?= Utils::escape($feather->forum_settings['o_date_format']) ?>" />
                                        <span><?php printf(__('Date format help'), gmdate($feather->forum_settings['o_date_format'], $timestamp), '<a href="http://www.php.net/manual/en/function.date.php">'.__('PHP manual').'</a>') ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('Visit timeout label') ?></th>
                                    <td>
                                        <input type="text" name="form_timeout_visit" size="5" maxlength="5" value="<?= $feather->forum_settings['o_timeout_visit'] ?>" />
                                        <span><?php _e('Visit timeout help') ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('Online timeout label') ?></th>
                                    <td>
                                        <input type="text" name="form_timeout_online" size="5" maxlength="5" value="<?= $feather->forum_settings['o_timeout_online'] ?>" />
                                        <span><?php _e('Online timeout help') ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('Redirect time label') ?></th>
                                    <td>
                                        <input type="text" name="form_redirect_delay" size="3" maxlength="3" value="<?= $feather->forum_settings['o_redirect_delay'] ?>" />
                                        <span><?php _e('Redirect time help') ?></span>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </fieldset>
                </div>
                <div class="inform">
                    <fieldset>
                        <legend><?php _e('Display subhead') ?></legend>
                        <div class="infldset">
                            <table class="aligntop">
                                <tr>
                                    <th scope="row"><?php _e('Version number label') ?></th>
                                    <td>
                                        <label class="conl"><input type="radio" name="form_show_version" value="1"<?php if ($feather->forum_settings['o_show_version'] == '1') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?php _e('Yes') ?></strong></label>
                                        <label class="conl"><input type="radio" name="form_show_version" value="0"<?php if ($feather->forum_settings['o_show_version'] == '0') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?php _e('No') ?></strong></label>
                                        <span class="clearb"><?php _e('Version number help') ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('Info in posts label') ?></th>
                                    <td>
                                        <label class="conl"><input type="radio" name="form_show_user_info" value="1"<?php if ($feather->forum_settings['o_show_user_info'] == '1') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?php _e('Yes') ?></strong></label>
                                        <label class="conl"><input type="radio" name="form_show_user_info" value="0"<?php if ($feather->forum_settings['o_show_user_info'] == '0') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?php _e('No') ?></strong></label>
                                        <span class="clearb"><?php _e('Info in posts help') ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('Post count label') ?></th>
                                    <td>
                                        <label class="conl"><input type="radio" name="form_show_post_count" value="1"<?php if ($feather->forum_settings['o_show_post_count'] == '1') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?php _e('Yes') ?></strong></label>
                                        <label class="conl"><input type="radio" name="form_show_post_count" value="0"<?php if ($feather->forum_settings['o_show_post_count'] == '0') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?php _e('No') ?></strong></label>
                                        <span class="clearb"><?php _e('Post count help') ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('Smilies label') ?></th>
                                    <td>
                                        <label class="conl"><input type="radio" name="form_smilies" value="1"<?php if ($feather->forum_settings['o_smilies'] == '1') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?php _e('Yes') ?></strong></label>
                                        <label class="conl"><input type="radio" name="form_smilies" value="0"<?php if ($feather->forum_settings['o_smilies'] == '0') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?php _e('No') ?></strong></label>
                                        <span class="clearb"><?php _e('Smilies help') ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('Smilies sigs label') ?></th>
                                    <td>
                                        <label class="conl"><input type="radio" name="form_smilies_sig" value="1"<?php if ($feather->forum_settings['o_smilies_sig'] == '1') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?php _e('Yes') ?></strong></label>
                                        <label class="conl"><input type="radio" name="form_smilies_sig" value="0"<?php if ($feather->forum_settings['o_smilies_sig'] == '0') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?php _e('No') ?></strong></label>
                                        <span class="clearb"><?php _e('Smilies sigs help') ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('Clickable links label') ?></th>
                                    <td>
                                        <label class="conl"><input type="radio" name="form_make_links" value="1"<?php if ($feather->forum_settings['o_make_links'] == '1') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?php _e('Yes') ?></strong></label>
                                        <label class="conl"><input type="radio" name="form_make_links" value="0"<?php if ($feather->forum_settings['o_make_links'] == '0') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?php _e('No') ?></strong></label>
                                        <span class="clearb"><?php _e('Clickable links help') ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('Topic review label') ?></th>
                                    <td>
                                        <input type="text" name="form_topic_review" size="3" maxlength="3" value="<?= $feather->forum_settings['o_topic_review'] ?>" />
                                        <span><?php _e('Topic review help') ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('Topics per page label') ?></th>
                                    <td>
                                        <input type="text" name="form_disp_topics_default" size="3" maxlength="2" value="<?= $feather->forum_settings['o_disp_topics_default'] ?>" />
                                        <span><?php _e('Topics per page help') ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('Posts per page label') ?></th>
                                    <td>
                                        <input type="text" name="form_disp_posts_default" size="3" maxlength="2" value="<?= $feather->forum_settings['o_disp_posts_default'] ?>" />
                                        <span><?php _e('Posts per page help') ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('Indent label') ?></th>
                                    <td>
                                        <input type="text" name="form_indent_num_spaces" size="3" maxlength="3" value="<?= $feather->forum_settings['o_indent_num_spaces'] ?>" />
                                        <span><?php _e('Indent help') ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('Quote depth label') ?></th>
                                    <td>
                                        <input type="text" name="form_quote_depth" size="3" maxlength="3" value="<?= $feather->forum_settings['o_quote_depth'] ?>" />
                                        <span><?php _e('Quote depth help') ?></span>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </fieldset>
                </div>
                <div class="inform">
                    <fieldset>
                        <legend><?php _e('Features subhead') ?></legend>
                        <div class="infldset">
                            <table class="aligntop">
                                <tr>
                                    <th scope="row"><?php _e('Quick post label') ?></th>
                                    <td>
                                        <label class="conl"><input type="radio" name="form_quickpost" value="1"<?php if ($feather->forum_settings['o_quickpost'] == '1') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?php _e('Yes') ?></strong></label>
                                        <label class="conl"><input type="radio" name="form_quickpost" value="0"<?php if ($feather->forum_settings['o_quickpost'] == '0') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?php _e('No') ?></strong></label>
                                        <span class="clearb"><?php _e('Quick post help') ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('Users online label') ?></th>
                                    <td>
                                        <label class="conl"><input type="radio" name="form_users_online" value="1"<?php if ($feather->forum_settings['o_users_online'] == '1') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?php _e('Yes') ?></strong></label>
                                        <label class="conl"><input type="radio" name="form_users_online" value="0"<?php if ($feather->forum_settings['o_users_online'] == '0') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?php _e('No') ?></strong></label>
                                        <span class="clearb"><?php _e('Users online help') ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><a name="censoring"></a><?php _e('Censor words label') ?></th>
                                    <td>
                                        <label class="conl"><input type="radio" name="form_censoring" value="1"<?php if ($feather->forum_settings['o_censoring'] == '1') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?php _e('Yes') ?></strong></label>
                                        <label class="conl"><input type="radio" name="form_censoring" value="0"<?php if ($feather->forum_settings['o_censoring'] == '0') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?php _e('No') ?></strong></label>
                                        <span class="clearb"><?php printf(__('Censor words help'), '<a href="'.$feather->urlFor('adminCensoring').'">'.__('Censoring').'</a>') ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><a name="signatures"></a><?php _e('Signatures label') ?></th>
                                    <td>
                                        <label class="conl"><input type="radio" name="form_signatures" value="1"<?php if ($feather->forum_settings['o_signatures'] == '1') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?php _e('Yes') ?></strong></label>
                                        <label class="conl"><input type="radio" name="form_signatures" value="0"<?php if ($feather->forum_settings['o_signatures'] == '0') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?php _e('No') ?></strong></label>
                                        <span class="clearb"><?php _e('Signatures help') ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('User has posted label') ?></th>
                                    <td>
                                        <label class="conl"><input type="radio" name="form_show_dot" value="1"<?php if ($feather->forum_settings['o_show_dot'] == '1') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?php _e('Yes') ?></strong></label>
                                        <label class="conl"><input type="radio" name="form_show_dot" value="0"<?php if ($feather->forum_settings['o_show_dot'] == '0') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?php _e('No') ?></strong></label>
                                        <span class="clearb"><?php _e('User has posted help') ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('Topic views label') ?></th>
                                    <td>
                                        <label class="conl"><input type="radio" name="form_topic_views" value="1"<?php if ($feather->forum_settings['o_topic_views'] == '1') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?php _e('Yes') ?></strong></label>
                                        <label class="conl"><input type="radio" name="form_topic_views" value="0"<?php if ($feather->forum_settings['o_topic_views'] == '0') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?php _e('No') ?></strong></label>
                                        <span class="clearb"><?php _e('Topic views help') ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('Quick jump label') ?></th>
                                    <td>
                                        <label class="conl"><input type="radio" name="form_quickjump" value="1"<?php if ($feather->forum_settings['o_quickjump'] == '1') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?php _e('Yes') ?></strong></label>
                                        <label class="conl"><input type="radio" name="form_quickjump" value="0"<?php if ($feather->forum_settings['o_quickjump'] == '0') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?php _e('No') ?></strong></label>
                                        <span class="clearb"><?php _e('Quick jump help') ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('GZip label') ?></th>
                                    <td>
                                        <label class="conl"><input type="radio" name="form_gzip" value="1"<?php if ($feather->forum_settings['o_gzip'] == '1') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?php _e('Yes') ?></strong></label>
                                        <label class="conl"><input type="radio" name="form_gzip" value="0"<?php if ($feather->forum_settings['o_gzip'] == '0') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?php _e('No') ?></strong></label>
                                        <span class="clearb"><?php _e('GZip help') ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('Search all label') ?></th>
                                    <td>
                                        <label class="conl"><input type="radio" name="form_search_all_forums" value="1"<?php if ($feather->forum_settings['o_search_all_forums'] == '1') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?php _e('Yes') ?></strong></label>
                                        <label class="conl"><input type="radio" name="form_search_all_forums" value="0"<?php if ($feather->forum_settings['o_search_all_forums'] == '0') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?php _e('No') ?></strong></label>
                                        <span class="clearb"><?php _e('Search all help') ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('Menu items label') ?></th>
                                    <td>
                                        <textarea name="form_additional_navlinks" rows="3" cols="55"><?= Utils::escape($feather->forum_settings['o_additional_navlinks']) ?></textarea>
                                        <span><?php _e('Menu items help') ?></span>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </fieldset>
                </div>
                <div class="inform">
                    <fieldset>
                        <legend><?php _e('Feed subhead') ?></legend>
                        <div class="infldset">
                            <table class="aligntop">
                                <tr>
                                    <th scope="row"><?php _e('Default feed label') ?></th>
                                    <td>
                                        <label class="conl"><input type="radio" name="form_feed_type" value="0"<?php if ($feather->forum_settings['o_feed_type'] == '0') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?php _e('None') ?></strong></label>
                                        <label class="conl"><input type="radio" name="form_feed_type" value="1"<?php if ($feather->forum_settings['o_feed_type'] == '1') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?php _e('RSS') ?></strong></label>
                                        <label class="conl"><input type="radio" name="form_feed_type" value="2"<?php if ($feather->forum_settings['o_feed_type'] == '2') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?php _e('Atom') ?></strong></label>
                                        <span class="clearb"><?php _e('Default feed help') ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('Feed TTL label') ?></th>
                                    <td>
                                        <select name="form_feed_ttl">
                                            <option value="0"<?php if ($feather->forum_settings['o_feed_ttl'] == '0') {
    echo ' selected="selected"';
} ?>><?php _e('No cache') ?></option>
                                            <?= $times ?>
                                        </select>
                                        <span><?php _e('Feed TTL help') ?></span>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </fieldset>
                </div>
                <div class="inform">
                    <fieldset>
                        <legend><?php _e('Reports subhead') ?></legend>
                        <div class="infldset">
                            <table class="aligntop">
                                <tr>
                                    <th scope="row"><?php _e('Reporting method label') ?></th>
                                    <td>
                                        <label class="conl"><input type="radio" name="form_report_method" value="0"<?php if ($feather->forum_settings['o_report_method'] == '0') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?php _e('Internal') ?></strong></label>
                                        <label class="conl"><input type="radio" name="form_report_method" value="1"<?php if ($feather->forum_settings['o_report_method'] == '1') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?php _e('By e-mail') ?></strong></label>
                                        <label class="conl"><input type="radio" name="form_report_method" value="2"<?php if ($feather->forum_settings['o_report_method'] == '2') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?php _e('Both') ?></strong></label>
                                        <span class="clearb"><?php _e('Reporting method help') ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('Mailing list label') ?></th>
                                    <td>
                                        <textarea name="form_mailing_list" rows="5" cols="55"><?= Utils::escape($feather->forum_settings['o_mailing_list']) ?></textarea>
                                        <span><?php _e('Mailing list help') ?></span>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </fieldset>
                </div>
                <div class="inform">
                    <fieldset>
                        <legend><?php _e('Avatars subhead') ?></legend>
                        <div class="infldset">
                            <table class="aligntop">
                                <tr>
                                    <th scope="row"><?php _e('Use avatars label') ?></th>
                                    <td>
                                        <label class="conl"><input type="radio" name="form_avatars" value="1"<?php if ($feather->forum_settings['o_avatars'] == '1') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?php _e('Yes') ?></strong></label>
                                        <label class="conl"><input type="radio" name="form_avatars" value="0"<?php if ($feather->forum_settings['o_avatars'] == '0') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?php _e('No') ?></strong></label>
                                        <span class="clearb"><?php _e('Use avatars help') ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('Upload directory label') ?></th>
                                    <td>
                                        <input type="text" name="form_avatars_dir" size="35" maxlength="50" value="<?= Utils::escape($feather->forum_settings['o_avatars_dir']) ?>" />
                                        <span><?php _e('Upload directory help') ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('Max width label') ?></th>
                                    <td>
                                        <input type="text" name="form_avatars_width" size="5" maxlength="5" value="<?= $feather->forum_settings['o_avatars_width'] ?>" />
                                        <span><?php _e('Max width help') ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('Max height label') ?></th>
                                    <td>
                                        <input type="text" name="form_avatars_height" size="5" maxlength="5" value="<?= $feather->forum_settings['o_avatars_height'] ?>" />
                                        <span><?php _e('Max height help') ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('Max size label') ?></th>
                                    <td>
                                        <input type="text" name="form_avatars_size" size="6" maxlength="6" value="<?= $feather->forum_settings['o_avatars_size'] ?>" />
                                        <span><?php _e('Max size help') ?></span>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </fieldset>
                </div>
                <div class="inform">
                    <fieldset>
                        <legend><?php _e('E-mail subhead') ?></legend>
                        <div class="infldset">
                            <table class="aligntop">
                                <tr>
                                    <th scope="row"><?php _e('Admin e-mail label') ?></th>
                                    <td>
                                        <input type="text" name="form_admin_email" size="50" maxlength="80" value="<?= Utils::escape($feather->forum_settings['o_admin_email']) ?>" />
                                        <span><?php _e('Admin e-mail help') ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('Webmaster e-mail label') ?></th>
                                    <td>
                                        <input type="text" name="form_webmaster_email" size="50" maxlength="80" value="<?= Utils::escape($feather->forum_settings['o_webmaster_email']) ?>" />
                                        <span><?php _e('Webmaster e-mail help') ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('Forum subscriptions label') ?></th>
                                    <td>
                                        <label class="conl"><input type="radio" name="form_forum_subscriptions" value="1"<?php if ($feather->forum_settings['o_forum_subscriptions'] == '1') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?php _e('Yes') ?></strong></label>
                                        <label class="conl"><input type="radio" name="form_forum_subscriptions" value="0"<?php if ($feather->forum_settings['o_forum_subscriptions'] == '0') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?php _e('No') ?></strong></label>
                                        <span class="clearb"><?php _e('Forum subscriptions help') ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('Topic subscriptions label') ?></th>
                                    <td>
                                        <label class="conl"><input type="radio" name="form_topic_subscriptions" value="1"<?php if ($feather->forum_settings['o_topic_subscriptions'] == '1') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?php _e('Yes') ?></strong></label>
                                        <label class="conl"><input type="radio" name="form_topic_subscriptions" value="0"<?php if ($feather->forum_settings['o_topic_subscriptions'] == '0') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?php _e('No') ?></strong></label>
                                        <span class="clearb"><?php _e('Topic subscriptions help') ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('SMTP address label') ?></th>
                                    <td>
                                        <input type="text" name="form_smtp_host" size="30" maxlength="100" value="<?= Utils::escape($feather->forum_settings['o_smtp_host']) ?>" />
                                        <span><?php _e('SMTP address help') ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('SMTP username label') ?></th>
                                    <td>
                                        <input type="text" name="form_smtp_user" size="25" maxlength="50" value="<?= Utils::escape($feather->forum_settings['o_smtp_user']) ?>" />
                                        <span><?php _e('SMTP username help') ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('SMTP password label') ?></th>
                                    <td>
                                        <label><input type="checkbox" name="form_smtp_change_pass" value="1" />&#160;<?php _e('SMTP change password help') ?></label>
<?php $smtp_pass = !empty($feather->forum_settings['o_smtp_pass']) ? Random::key(Utils::strlen($feather->forum_settings['o_smtp_pass']), true) : ''; ?>
                                        <input type="password" name="form_smtp_pass1" size="25" maxlength="50" value="<?= $smtp_pass ?>" />
                                        <input type="password" name="form_smtp_pass2" size="25" maxlength="50" value="<?= $smtp_pass ?>" />
                                        <span><?php _e('SMTP password help') ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('SMTP SSL label') ?></th>
                                    <td>
                                        <label class="conl"><input type="radio" name="form_smtp_ssl" value="1"<?php if ($feather->forum_settings['o_smtp_ssl'] == '1') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?php _e('Yes') ?></strong></label>
                                        <label class="conl"><input type="radio" name="form_smtp_ssl" value="0"<?php if ($feather->forum_settings['o_smtp_ssl'] == '0') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?php _e('No') ?></strong></label>
                                        <span class="clearb"><?php _e('SMTP SSL help') ?></span>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </fieldset>
                </div>
                <div class="inform">
                    <fieldset>
                        <legend><?php _e('Registration subhead') ?></legend>
                        <div class="infldset">
                            <table class="aligntop">
                                <tr>
                                    <th scope="row"><?php _e('Allow new label') ?></th>
                                    <td>
                                        <label class="conl"><input type="radio" name="form_regs_allow" value="1"<?php if ($feather->forum_settings['o_regs_allow'] == '1') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?php _e('Yes') ?></strong></label>
                                        <label class="conl"><input type="radio" name="form_regs_allow" value="0"<?php if ($feather->forum_settings['o_regs_allow'] == '0') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?php _e('No') ?></strong></label>
                                        <span class="clearb"><?php _e('Allow new help') ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('Verify label') ?></th>
                                    <td>
                                        <label class="conl"><input type="radio" name="form_regs_verify" value="1"<?php if ($feather->forum_settings['o_regs_verify'] == '1') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?php _e('Yes') ?></strong></label>
                                        <label class="conl"><input type="radio" name="form_regs_verify" value="0"<?php if ($feather->forum_settings['o_regs_verify'] == '0') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?php _e('No') ?></strong></label>
                                        <span class="clearb"><?php _e('Verify help') ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('Report new label') ?></th>
                                    <td>
                                        <label class="conl"><input type="radio" name="form_regs_report" value="1"<?php if ($feather->forum_settings['o_regs_report'] == '1') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?php _e('Yes') ?></strong></label>
                                        <label class="conl"><input type="radio" name="form_regs_report" value="0"<?php if ($feather->forum_settings['o_regs_report'] == '0') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?php _e('No') ?></strong></label>
                                        <span class="clearb"><?php _e('Report new help') ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('Use rules label') ?></th>
                                    <td>
                                        <label class="conl"><input type="radio" name="form_rules" value="1"<?php if ($feather->forum_settings['o_rules'] == '1') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?php _e('Yes') ?></strong></label>
                                        <label class="conl"><input type="radio" name="form_rules" value="0"<?php if ($feather->forum_settings['o_rules'] == '0') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?php _e('No') ?></strong></label>
                                        <span class="clearb"><?php _e('Use rules help') ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('Rules label') ?></th>
                                    <td>
                                        <textarea name="form_rules_message" rows="10" cols="55"><?= Utils::escape($feather->forum_settings['o_rules_message']) ?></textarea>
                                        <span><?php _e('Rules help') ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('E-mail default label') ?></th>
                                    <td>
                                        <span><?php _e('E-mail default help') ?></span>
                                        <label><input type="radio" name="form_default_email_setting" id="form_default_email_setting_0" value="0"<?php if ($feather->forum_settings['o_default_email_setting'] == '0') {
    echo ' checked="checked"';
} ?> />&#160;<?php _e('Display e-mail label') ?></label>
                                        <label><input type="radio" name="form_default_email_setting" id="form_default_email_setting_1" value="1"<?php if ($feather->forum_settings['o_default_email_setting'] == '1') {
    echo ' checked="checked"';
} ?> />&#160;<?php _e('Hide allow form label') ?></label>
                                        <label><input type="radio" name="form_default_email_setting" id="form_default_email_setting_2" value="2"<?php if ($feather->forum_settings['o_default_email_setting'] == '2') {
    echo ' checked="checked"';
} ?> />&#160;<?php _e('Hide both label') ?></label>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </fieldset>
                </div>
                <div class="inform">
                    <fieldset>
                        <legend><?php _e('Announcement subhead') ?></legend>
                        <div class="infldset">
                            <table class="aligntop">
                                <tr>
                                    <th scope="row"><?php _e('Display announcement label') ?></th>
                                    <td>
                                        <label class="conl"><input type="radio" name="form_announcement" value="1"<?php if ($feather->forum_settings['o_announcement'] == '1') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?php _e('Yes') ?></strong></label>
                                        <label class="conl"><input type="radio" name="form_announcement" value="0"<?php if ($feather->forum_settings['o_announcement'] == '0') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?php _e('No') ?></strong></label>
                                        <span class="clearb"><?php _e('Display announcement help') ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('Announcement message label') ?></th>
                                    <td>
                                        <textarea name="form_announcement_message" rows="5" cols="55"><?= Utils::escape($feather->forum_settings['o_announcement_message']) ?></textarea>
                                        <span><?php _e('Announcement message help') ?></span>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </fieldset>
                </div>
                <div class="inform">
                    <fieldset>
                        <legend><?php _e('Maintenance subhead') ?></legend>
                        <div class="infldset">
                            <table class="aligntop">
                                <tr>
                                    <th scope="row"><a name="maintenance"></a><?php _e('Maintenance mode label') ?></th>
                                    <td>
                                        <label class="conl"><input type="radio" name="form_maintenance" value="1"<?php if ($feather->forum_settings['o_maintenance'] == '1') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?php _e('Yes') ?></strong></label>
                                        <label class="conl"><input type="radio" name="form_maintenance" value="0"<?php if ($feather->forum_settings['o_maintenance'] == '0') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?php _e('No') ?></strong></label>
                                        <span class="clearb"><?php _e('Maintenance mode help') ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('Maintenance message label') ?></th>
                                    <td>
                                        <textarea name="form_maintenance_message" rows="5" cols="55"><?= Utils::escape($feather->forum_settings['o_maintenance_message']) ?></textarea>
                                        <span><?php _e('Maintenance message help') ?></span>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </fieldset>
                </div>
                <?php Container::get('hooks')->fire('view.admin.options.form'); ?>
                <p class="submitend"><input type="submit" name="save" value="<?php _e('Save changes') ?>" /></p>
            </form>
        </div>
    </div>
    <div class="clearer"></div>
</div>

<?php
Container::get('hooks')->fire('view.admin.options.end');
