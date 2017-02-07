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
        <h2><span><?= __('Options head') ?></span></h2>
        <div class="box">
            <form method="post" action="<?= Router::pathFor('adminOptions') ?>">
                <input type="hidden" name="csrf_name" value="<?= $csrf_name; ?>"><input type="hidden" name="csrf_value" value="<?= $csrf_value; ?>">
                <p class="submittop"><input type="submit" name="save" value="<?= __('Save changes') ?>" /></p>
                <div class="inform">
                    <input type="hidden" name="form_sent" value="1" />
                    <fieldset>
                        <legend><?= __('Essentials subhead') ?></legend>
                        <div class="infldset">
                            <table class="aligntop">
                                <tr>
                                    <th scope="row"><?= __('Board title label') ?></th>
                                    <td>
                                        <input type="text" name="form_board_title" size="50" maxlength="255" value="<?= Utils::escape(ForumSettings::get('o_board_title')) ?>" />
                                        <span><?= __('Board title help') ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?= __('Board desc label') ?></th>
                                    <td>
                                        <textarea name="form_board_desc" cols="60" rows="3"><?= Utils::escape(ForumSettings::get('o_board_desc')) ?></textarea>
                                        <span><?= __('Board desc help') ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?= __('Base URL label') ?></th>
                                    <td>
                                        <input type="text" name="form_base_url" size="50" maxlength="100" value="<?= Utils::escape(ForumSettings::get('o_base_url')) ?>" />
                                        <span><?= __('Base URL help') ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?= __('Timezone label') ?></th>
                                    <td>
                                        <select name="form_default_timezone">
                                            <option value="-12"<?php if (ForumSettings::get('timezone') == -12) {echo ' selected="selected"';} ?>><?= __('UTC-12:00') ?></option>
                                            <option value="-11"<?php if (ForumSettings::get('timezone') == -11) {echo ' selected="selected"';} ?>><?= __('UTC-11:00') ?></option>
                                            <option value="-10"<?php if (ForumSettings::get('timezone') == -10) {echo ' selected="selected"';} ?>><?= __('UTC-10:00') ?></option>
                                            <option value="-9.5"<?php if (ForumSettings::get('timezone') == -9.5) {echo ' selected="selected"';} ?>><?= __('UTC-09:30') ?></option>
                                            <option value="-9"<?php if (ForumSettings::get('timezone') == -9) {echo ' selected="selected"';} ?>><?= __('UTC-09:00') ?></option>
                                            <option value="-8.5"<?php if (ForumSettings::get('timezone') == -8.5) {echo ' selected="selected"';} ?>><?= __('UTC-08:30') ?></option>
                                            <option value="-8"<?php if (ForumSettings::get('timezone') == -8) {echo ' selected="selected"';} ?>><?= __('UTC-08:00') ?></option>
                                            <option value="-7"<?php if (ForumSettings::get('timezone') == -7) {echo ' selected="selected"';} ?>><?= __('UTC-07:00') ?></option>
                                            <option value="-6"<?php if (ForumSettings::get('timezone') == -6) {echo ' selected="selected"';} ?>><?= __('UTC-06:00') ?></option>
                                            <option value="-5"<?php if (ForumSettings::get('timezone') == -5) {echo ' selected="selected"';} ?>><?= __('UTC-05:00') ?></option>
                                            <option value="-4"<?php if (ForumSettings::get('timezone') == -4) {echo ' selected="selected"';} ?>><?= __('UTC-04:00') ?></option>
                                            <option value="-3.5"<?php if (ForumSettings::get('timezone') == -3.5) {echo ' selected="selected"';} ?>><?= __('UTC-03:30') ?></option>
                                            <option value="-3"<?php if (ForumSettings::get('timezone') == -3) {echo ' selected="selected"';} ?>><?= __('UTC-03:00') ?></option>
                                            <option value="-2"<?php if (ForumSettings::get('timezone') == -2) {echo ' selected="selected"';} ?>><?= __('UTC-02:00') ?></option>
                                            <option value="-1"<?php if (ForumSettings::get('timezone') == -1) {echo ' selected="selected"';} ?>><?= __('UTC-01:00') ?></option>
                                            <option value="0"<?php if (ForumSettings::get('timezone') == 0) {echo ' selected="selected"';} ?>><?= __('UTC') ?></option>
                                            <option value="1"<?php if (ForumSettings::get('timezone') == 1) {echo ' selected="selected"';} ?>><?= __('UTC+01:00') ?></option>
                                            <option value="2"<?php if (ForumSettings::get('timezone') == 2) {echo ' selected="selected"';} ?>><?= __('UTC+02:00') ?></option>
                                            <option value="3"<?php if (ForumSettings::get('timezone') == 3) {echo ' selected="selected"';} ?>><?= __('UTC+03:00') ?></option>
                                            <option value="3.5"<?php if (ForumSettings::get('timezone') == 3.5) {echo ' selected="selected"';} ?>><?= __('UTC+03:30') ?></option>
                                            <option value="4"<?php if (ForumSettings::get('timezone') == 4) {echo ' selected="selected"';} ?>><?= __('UTC+04:00') ?></option>
                                            <option value="4.5"<?php if (ForumSettings::get('timezone') == 4.5) {echo ' selected="selected"';} ?>><?= __('UTC+04:30') ?></option>
                                            <option value="5"<?php if (ForumSettings::get('timezone') == 5) {echo ' selected="selected"';} ?>><?= __('UTC+05:00') ?></option>
                                            <option value="5.5"<?php if (ForumSettings::get('timezone') == 5.5) {echo ' selected="selected"';} ?>><?= __('UTC+05:30') ?></option>
                                            <option value="5.75"<?php if (ForumSettings::get('timezone') == 5.75) {echo ' selected="selected"';} ?>><?= __('UTC+05:45') ?></option>
                                            <option value="6"<?php if (ForumSettings::get('timezone') == 6) {echo ' selected="selected"';} ?>><?= __('UTC+06:00') ?></option>
                                            <option value="6.5"<?php if (ForumSettings::get('timezone') == 6.5) {echo ' selected="selected"';} ?>><?= __('UTC+06:30') ?></option>
                                            <option value="7"<?php if (ForumSettings::get('timezone') == 7) {echo ' selected="selected"';} ?>><?= __('UTC+07:00') ?></option>
                                            <option value="8"<?php if (ForumSettings::get('timezone') == 8) {echo ' selected="selected"';} ?>><?= __('UTC+08:00') ?></option>
                                            <option value="8.75"<?php if (ForumSettings::get('timezone') == 8.75) {echo ' selected="selected"';} ?>><?= __('UTC+08:45') ?></option>
                                            <option value="9"<?php if (ForumSettings::get('timezone') == 9) {echo ' selected="selected"';} ?>><?= __('UTC+09:00') ?></option>
                                            <option value="9.5"<?php if (ForumSettings::get('timezone') == 9.5) {echo ' selected="selected"';} ?>><?= __('UTC+09:30') ?></option>
                                            <option value="10"<?php if (ForumSettings::get('timezone') == 10) {echo ' selected="selected"';} ?>><?= __('UTC+10:00') ?></option>
                                            <option value="10.5"<?php if (ForumSettings::get('timezone') == 10.5) {echo ' selected="selected"';} ?>><?= __('UTC+10:30') ?></option>
                                            <option value="11"<?php if (ForumSettings::get('timezone') == 11) {echo ' selected="selected"';} ?>><?= __('UTC+11:00') ?></option>
                                            <option value="11.5"<?php if (ForumSettings::get('timezone') == 11.5) {echo ' selected="selected"';} ?>><?= __('UTC+11:30') ?></option>
                                            <option value="12"<?php if (ForumSettings::get('timezone') == 12) {echo ' selected="selected"';} ?>><?= __('UTC+12:00') ?></option>
                                            <option value="12.75"<?php if (ForumSettings::get('timezone') == 12.75) {echo ' selected="selected"';} ?>><?= __('UTC+12:45') ?></option>
                                            <option value="13"<?php if (ForumSettings::get('timezone') == 13) {echo ' selected="selected"';} ?>><?= __('UTC+13:00') ?></option>
                                            <option value="14"<?php if (ForumSettings::get('timezone') == 14) {echo ' selected="selected"';} ?>><?= __('UTC+14:00') ?></option>
                                        </select>
                                        <span><?= __('Timezone help') ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?= __('DST label') ?></th>
                                    <td>
                                        <label class="conl"><input type="radio" name="form_default_dst" value="1"<?php if (ForumSettings::get('dst') == '1') {echo ' checked="checked"';} ?> />&#160;<strong><?= __('Yes') ?></strong></label>
                                        <label class="conl"><input type="radio" name="form_default_dst" value="0"<?php if (ForumSettings::get('dst') == '0') {echo ' checked="checked"';} ?> />&#160;<strong><?= __('No') ?></strong></label>
                                        <span class="clearb"><?= __('DST help') ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?= __('Language label') ?></th>
                                    <td>
                                        <select name="form_default_lang">
                                            <?= $languages; ?>
                                        </select>
                                        <span><?= __('Language help') ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?= __('Default style label') ?></th>
                                    <td>
                                        <select name="form_default_style">
                                            <?= $styles; ?>
                                        </select>
                                        <span><?= __('Default style help') ?></span>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </fieldset>
                </div>
<?php

    $diff = (User::getPref('timezone') + User::getPref('dst')) * 3600;
    $timestamp = time() + $diff;

?>
                <div class="inform">
                    <fieldset>
                        <legend><?= __('Timeouts subhead') ?></legend>
                        <div class="infldset">
                            <table class="aligntop">
                                <tr>
                                    <th scope="row"><?= __('Time format label') ?></th>
                                    <td>
                                        <input type="text" name="form_time_format" size="25" maxlength="25" value="<?= Utils::escape(ForumSettings::get('time_format')) ?>" />
                                        <span><?php printf(__('Time format help'), gmdate(ForumSettings::get('time_format'), $timestamp), '<a href="http://www.php.net/manual/en/function.date.php">'.__('PHP manual').'</a>') ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?= __('Date format label') ?></th>
                                    <td>
                                        <input type="text" name="form_date_format" size="25" maxlength="25" value="<?= Utils::escape(ForumSettings::get('date_format')) ?>" />
                                        <span><?php printf(__('Date format help'), gmdate(ForumSettings::get('date_format'), $timestamp), '<a href="http://www.php.net/manual/en/function.date.php">'.__('PHP manual').'</a>') ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?= __('Visit timeout label') ?></th>
                                    <td>
                                        <input type="text" name="form_timeout_visit" size="5" maxlength="5" value="<?= ForumSettings::get('o_timeout_visit') ?>" />
                                        <span><?= __('Visit timeout help') ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?= __('Online timeout label') ?></th>
                                    <td>
                                        <input type="text" name="form_timeout_online" size="5" maxlength="5" value="<?= ForumSettings::get('o_timeout_online') ?>" />
                                        <span><?= __('Online timeout help') ?></span>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </fieldset>
                </div>
                <div class="inform">
                    <fieldset>
                        <legend><?= __('Display subhead') ?></legend>
                        <div class="infldset">
                            <table class="aligntop">
                                <tr>
                                    <th scope="row"><?= __('Version number label') ?></th>
                                    <td>
                                        <label class="conl"><input type="radio" name="form_show_version" value="1"<?php if (ForumSettings::get('o_show_version') == '1') {echo ' checked="checked"';} ?> />&#160;<strong><?= __('Yes') ?></strong></label>
                                        <label class="conl"><input type="radio" name="form_show_version" value="0"<?php if (ForumSettings::get('o_show_version') == '0') {echo ' checked="checked"';} ?> />&#160;<strong><?= __('No') ?></strong></label>
                                        <span class="clearb"><?= __('Version number help') ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?= __('Info in posts label') ?></th>
                                    <td>
                                        <label class="conl"><input type="radio" name="form_show_user_info" value="1"<?php if (ForumSettings::get('o_show_user_info') == '1') {echo ' checked="checked"';} ?> />&#160;<strong><?= __('Yes') ?></strong></label>
                                        <label class="conl"><input type="radio" name="form_show_user_info" value="0"<?php if (ForumSettings::get('o_show_user_info') == '0') {echo ' checked="checked"';} ?> />&#160;<strong><?= __('No') ?></strong></label>
                                        <span class="clearb"><?= __('Info in posts help') ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?= __('Post count label') ?></th>
                                    <td>
                                        <label class="conl"><input type="radio" name="form_show_post_count" value="1"<?php if (ForumSettings::get('o_show_post_count') == '1') {echo ' checked="checked"';} ?> />&#160;<strong><?= __('Yes') ?></strong></label>
                                        <label class="conl"><input type="radio" name="form_show_post_count" value="0"<?php if (ForumSettings::get('o_show_post_count') == '0') {echo ' checked="checked"';} ?> />&#160;<strong><?= __('No') ?></strong></label>
                                        <span class="clearb"><?= __('Post count help') ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?= __('Smilies label') ?></th>
                                    <td>
                                        <label class="conl"><input type="radio" name="form_smilies" value="1"<?php if (ForumSettings::get('show.smilies') == '1') {echo ' checked="checked"';} ?> />&#160;<strong><?= __('Yes') ?></strong></label>
                                        <label class="conl"><input type="radio" name="form_smilies" value="0"<?php if (ForumSettings::get('show.smilies') == '0') {echo ' checked="checked"';} ?> />&#160;<strong><?= __('No') ?></strong></label>
                                        <span class="clearb"><?= __('Smilies help') ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?= __('Smilies sigs label') ?></th>
                                    <td>
                                        <label class="conl"><input type="radio" name="form_smilies_sig" value="1"<?php if (ForumSettings::get('show.smilies.sig') == '1') {echo ' checked="checked"';} ?> />&#160;<strong><?= __('Yes') ?></strong></label>
                                        <label class="conl"><input type="radio" name="form_smilies_sig" value="0"<?php if (ForumSettings::get('show.smilies.sig') == '0') {echo ' checked="checked"';} ?> />&#160;<strong><?= __('No') ?></strong></label>
                                        <span class="clearb"><?= __('Smilies sigs help') ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?= __('Clickable links label') ?></th>
                                    <td>
                                        <label class="conl"><input type="radio" name="form_make_links" value="1"<?php if (ForumSettings::get('o_make_links') == '1') {echo ' checked="checked"';} ?> />&#160;<strong><?= __('Yes') ?></strong></label>
                                        <label class="conl"><input type="radio" name="form_make_links" value="0"<?php if (ForumSettings::get('o_make_links') == '0') {echo ' checked="checked"';} ?> />&#160;<strong><?= __('No') ?></strong></label>
                                        <span class="clearb"><?= __('Clickable links help') ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?= __('Topic review label') ?></th>
                                    <td>
                                        <input type="text" name="form_topic_review" size="3" maxlength="3" value="<?= ForumSettings::get('o_topic_review') ?>" />
                                        <span><?= __('Topic review help') ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?= __('Topics per page label') ?></th>
                                    <td>
                                        <input type="text" name="form_disp_topics_default" size="3" maxlength="2" value="<?= ForumSettings::get('disp.topics') ?>" />
                                        <span><?= __('Topics per page help') ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?= __('Posts per page label') ?></th>
                                    <td>
                                        <input type="text" name="form_disp_posts_default" size="3" maxlength="2" value="<?= ForumSettings::get('disp.posts') ?>" />
                                        <span><?= __('Posts per page help') ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?= __('Indent label') ?></th>
                                    <td>
                                        <input type="text" name="form_indent_num_spaces" size="3" maxlength="3" value="<?= ForumSettings::get('o_indent_num_spaces') ?>" />
                                        <span><?= __('Indent help') ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?= __('Quote depth label') ?></th>
                                    <td>
                                        <input type="text" name="form_quote_depth" size="3" maxlength="3" value="<?= ForumSettings::get('o_quote_depth') ?>" />
                                        <span><?= __('Quote depth help') ?></span>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </fieldset>
                </div>
                <div class="inform">
                    <fieldset>
                        <legend><?= __('Features subhead') ?></legend>
                        <div class="infldset">
                            <table class="aligntop">
                                <tr>
                                    <th scope="row"><?= __('Quick post label') ?></th>
                                    <td>
                                        <label class="conl"><input type="radio" name="form_quickpost" value="1"<?php if (ForumSettings::get('o_quickpost') == '1') {echo ' checked="checked"';} ?> />&#160;<strong><?= __('Yes') ?></strong></label>
                                        <label class="conl"><input type="radio" name="form_quickpost" value="0"<?php if (ForumSettings::get('o_quickpost') == '0') {echo ' checked="checked"';} ?> />&#160;<strong><?= __('No') ?></strong></label>
                                        <span class="clearb"><?= __('Quick post help') ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?= __('Users online label') ?></th>
                                    <td>
                                        <label class="conl"><input type="radio" name="form_users_online" value="1"<?php if (ForumSettings::get('o_users_online') == '1') {echo ' checked="checked"';} ?> />&#160;<strong><?= __('Yes') ?></strong></label>
                                        <label class="conl"><input type="radio" name="form_users_online" value="0"<?php if (ForumSettings::get('o_users_online') == '0') {echo ' checked="checked"';} ?> />&#160;<strong><?= __('No') ?></strong></label>
                                        <span class="clearb"><?= __('Users online help') ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><a name="censoring"></a><?= __('Censor words label') ?></th>
                                    <td>
                                        <label class="conl"><input type="radio" name="form_censoring" value="1"<?php if (ForumSettings::get('o_censoring') == '1') {echo ' checked="checked"';} ?> />&#160;<strong><?= __('Yes') ?></strong></label>
                                        <label class="conl"><input type="radio" name="form_censoring" value="0"<?php if (ForumSettings::get('o_censoring') == '0') {echo ' checked="checked"';} ?> />&#160;<strong><?= __('No') ?></strong></label>
                                        <span class="clearb"><?php printf(__('Censor words help'), '<a href="'.Router::pathFor('adminCensoring').'">'.__('Censoring').'</a>') ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><a name="signatures"></a><?= __('Signatures label') ?></th>
                                    <td>
                                        <label class="conl"><input type="radio" name="form_signatures" value="1"<?php if (ForumSettings::get('o_signatures') == '1') {echo ' checked="checked"';} ?> />&#160;<strong><?= __('Yes') ?></strong></label>
                                        <label class="conl"><input type="radio" name="form_signatures" value="0"<?php if (ForumSettings::get('o_signatures') == '0') {echo ' checked="checked"';} ?> />&#160;<strong><?= __('No') ?></strong></label>
                                        <span class="clearb"><?= __('Signatures help') ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?= __('User has posted label') ?></th>
                                    <td>
                                        <label class="conl"><input type="radio" name="form_show_dot" value="1"<?php if (ForumSettings::get('o_show_dot') == '1') {echo ' checked="checked"';} ?> />&#160;<strong><?= __('Yes') ?></strong></label>
                                        <label class="conl"><input type="radio" name="form_show_dot" value="0"<?php if (ForumSettings::get('o_show_dot') == '0') {echo ' checked="checked"';} ?> />&#160;<strong><?= __('No') ?></strong></label>
                                        <span class="clearb"><?= __('User has posted help') ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?= __('Topic views label') ?></th>
                                    <td>
                                        <label class="conl"><input type="radio" name="form_topic_views" value="1"<?php if (ForumSettings::get('o_topic_views') == '1') {echo ' checked="checked"';} ?> />&#160;<strong><?= __('Yes') ?></strong></label>
                                        <label class="conl"><input type="radio" name="form_topic_views" value="0"<?php if (ForumSettings::get('o_topic_views') == '0') {echo ' checked="checked"';} ?> />&#160;<strong><?= __('No') ?></strong></label>
                                        <span class="clearb"><?= __('Topic views help') ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?= __('Quick jump label') ?></th>
                                    <td>
                                        <label class="conl"><input type="radio" name="form_quickjump" value="1"<?php if (ForumSettings::get('o_quickjump') == '1') {echo ' checked="checked"';} ?> />&#160;<strong><?= __('Yes') ?></strong></label>
                                        <label class="conl"><input type="radio" name="form_quickjump" value="0"<?php if (ForumSettings::get('o_quickjump') == '0') {echo ' checked="checked"';} ?> />&#160;<strong><?= __('No') ?></strong></label>
                                        <span class="clearb"><?= __('Quick jump help') ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?= __('GZip label') ?></th>
                                    <td>
                                        <label class="conl"><input type="radio" name="form_gzip" value="1"<?php if (ForumSettings::get('o_gzip') == '1') {echo ' checked="checked"';} ?> />&#160;<strong><?= __('Yes') ?></strong></label>
                                        <label class="conl"><input type="radio" name="form_gzip" value="0"<?php if (ForumSettings::get('o_gzip') == '0') {echo ' checked="checked"';} ?> />&#160;<strong><?= __('No') ?></strong></label>
                                        <span class="clearb"><?= __('GZip help') ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?= __('Search all label') ?></th>
                                    <td>
                                        <label class="conl"><input type="radio" name="form_search_all_forums" value="1"<?php if (ForumSettings::get('o_search_all_forums') == '1') {echo ' checked="checked"';} ?> />&#160;<strong><?= __('Yes') ?></strong></label>
                                        <label class="conl"><input type="radio" name="form_search_all_forums" value="0"<?php if (ForumSettings::get('o_search_all_forums') == '0') {echo ' checked="checked"';} ?> />&#160;<strong><?= __('No') ?></strong></label>
                                        <span class="clearb"><?= __('Search all help') ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?= __('Menu items label') ?></th>
                                    <td>
                                        <textarea name="form_additional_navlinks" rows="3" cols="55"><?= Utils::escape(ForumSettings::get('o_additional_navlinks')) ?></textarea>
                                        <span><?= __('Menu items help') ?></span>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </fieldset>
                </div>
                <div class="inform">
                    <fieldset>
                        <legend><?= __('Reports subhead') ?></legend>
                        <div class="infldset">
                            <table class="aligntop">
                                <tr>
                                    <th scope="row"><?= __('Reporting method label') ?></th>
                                    <td>
                                        <label class="conl"><input type="radio" name="form_report_method" value="0"<?php if (ForumSettings::get('o_report_method') == '0') {echo ' checked="checked"';} ?> />&#160;<strong><?= __('Internal') ?></strong></label>
                                        <label class="conl"><input type="radio" name="form_report_method" value="1"<?php if (ForumSettings::get('o_report_method') == '1') {echo ' checked="checked"';} ?> />&#160;<strong><?= __('By e-mail') ?></strong></label>
                                        <label class="conl"><input type="radio" name="form_report_method" value="2"<?php if (ForumSettings::get('o_report_method') == '2') {echo ' checked="checked"';} ?> />&#160;<strong><?= __('Both') ?></strong></label>
                                        <span class="clearb"><?= __('Reporting method help') ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?= __('Mailing list label') ?></th>
                                    <td>
                                        <textarea name="form_mailing_list" rows="5" cols="55"><?= Utils::escape(ForumSettings::get('o_mailing_list')) ?></textarea>
                                        <span><?= __('Mailing list help') ?></span>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </fieldset>
                </div>
                <div class="inform">
                    <fieldset>
                        <legend><?= __('Avatars subhead') ?></legend>
                        <div class="infldset">
                            <table class="aligntop">
                                <tr>
                                    <th scope="row"><?= __('Use avatars label') ?></th>
                                    <td>
                                        <label class="conl"><input type="radio" name="form_avatars" value="1"<?php if (ForumSettings::get('o_avatars') == '1') {echo ' checked="checked"';} ?> />&#160;<strong><?= __('Yes') ?></strong></label>
                                        <label class="conl"><input type="radio" name="form_avatars" value="0"<?php if (ForumSettings::get('o_avatars') == '0') {echo ' checked="checked"';} ?> />&#160;<strong><?= __('No') ?></strong></label>
                                        <span class="clearb"><?= __('Use avatars help') ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?= __('Upload directory label') ?></th>
                                    <td>
                                        <input type="text" name="form_avatars_dir" size="35" maxlength="50" value="<?= Utils::escape(ForumSettings::get('o_avatars_dir')) ?>" />
                                        <span><?= __('Upload directory help') ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?= __('Max width label') ?></th>
                                    <td>
                                        <input type="text" name="form_avatars_width" size="5" maxlength="5" value="<?= ForumSettings::get('o_avatars_width') ?>" />
                                        <span><?= __('Max width help') ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?= __('Max height label') ?></th>
                                    <td>
                                        <input type="text" name="form_avatars_height" size="5" maxlength="5" value="<?= ForumSettings::get('o_avatars_height') ?>" />
                                        <span><?= __('Max height help') ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?= __('Max size label') ?></th>
                                    <td>
                                        <input type="text" name="form_avatars_size" size="6" maxlength="6" value="<?= ForumSettings::get('o_avatars_size') ?>" />
                                        <span><?= __('Max size help') ?></span>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </fieldset>
                </div>
                <div class="inform">
                    <fieldset>
                        <legend><?= __('E-mail subhead') ?></legend>
                        <div class="infldset">
                            <table class="aligntop">
                                <tr>
                                    <th scope="row"><?= __('Admin e-mail label') ?></th>
                                    <td>
                                        <input type="text" name="form_admin_email" size="50" maxlength="80" value="<?= Utils::escape(ForumSettings::get('o_admin_email')) ?>" />
                                        <span><?= __('Admin e-mail help') ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?= __('Webmaster e-mail label') ?></th>
                                    <td>
                                        <input type="text" name="form_webmaster_email" size="50" maxlength="80" value="<?= Utils::escape(ForumSettings::get('o_webmaster_email')) ?>" />
                                        <span><?= __('Webmaster e-mail help') ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?= __('Forum subscriptions label') ?></th>
                                    <td>
                                        <label class="conl"><input type="radio" name="form_forum_subscriptions" value="1"<?php if (ForumSettings::get('o_forum_subscriptions') == '1') {echo ' checked="checked"';} ?> />&#160;<strong><?= __('Yes') ?></strong></label>
                                        <label class="conl"><input type="radio" name="form_forum_subscriptions" value="0"<?php if (ForumSettings::get('o_forum_subscriptions') == '0') {echo ' checked="checked"';} ?> />&#160;<strong><?= __('No') ?></strong></label>
                                        <span class="clearb"><?= __('Forum subscriptions help') ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?= __('Topic subscriptions label') ?></th>
                                    <td>
                                        <label class="conl"><input type="radio" name="form_topic_subscriptions" value="1"<?php if (ForumSettings::get('o_topic_subscriptions') == '1') {echo ' checked="checked"';} ?> />&#160;<strong><?= __('Yes') ?></strong></label>
                                        <label class="conl"><input type="radio" name="form_topic_subscriptions" value="0"<?php if (ForumSettings::get('o_topic_subscriptions') == '0') {echo ' checked="checked"';} ?> />&#160;<strong><?= __('No') ?></strong></label>
                                        <span class="clearb"><?= __('Topic subscriptions help') ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?= __('SMTP address label') ?></th>
                                    <td>
                                        <input type="text" name="form_smtp_host" size="30" maxlength="100" value="<?= Utils::escape(ForumSettings::get('o_smtp_host')) ?>" />
                                        <span><?= __('SMTP address help') ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?= __('SMTP username label') ?></th>
                                    <td>
                                        <input type="text" name="form_smtp_user" size="25" maxlength="50" value="<?= Utils::escape(ForumSettings::get('o_smtp_user')) ?>" />
                                        <span><?= __('SMTP username help') ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?= __('SMTP password label') ?></th>
                                    <td>
                                        <label><input type="checkbox" name="form_smtp_change_pass" value="1" />&#160;<?= __('SMTP change password help') ?></label>
<?php $smtp_pass = !empty(ForumSettings::get('o_smtp_pass')) ? Random::key(Utils::strlen(ForumSettings::get('o_smtp_pass')), true) : ''; ?>
                                        <input type="password" name="form_smtp_pass1" size="25" maxlength="50" value="<?= $smtp_pass ?>" />
                                        <input type="password" name="form_smtp_pass2" size="25" maxlength="50" value="<?= $smtp_pass ?>" />
                                        <span><?= __('SMTP password help') ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?= __('SMTP SSL label') ?></th>
                                    <td>
                                        <label class="conl"><input type="radio" name="form_smtp_ssl" value="1"<?php if (ForumSettings::get('o_smtp_ssl') == '1') {echo ' checked="checked"';} ?> />&#160;<strong><?= __('Yes') ?></strong></label>
                                        <label class="conl"><input type="radio" name="form_smtp_ssl" value="0"<?php if (ForumSettings::get('o_smtp_ssl') == '0') {echo ' checked="checked"';} ?> />&#160;<strong><?= __('No') ?></strong></label>
                                        <span class="clearb"><?= __('SMTP SSL help') ?></span>
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
                                    <th scope="row"><?= __('Allow new label') ?></th>
                                    <td>
                                        <label class="conl"><input type="radio" name="form_regs_allow" value="1"<?php if (ForumSettings::get('o_regs_allow') == '1') {echo ' checked="checked"';} ?> />&#160;<strong><?= __('Yes') ?></strong></label>
                                        <label class="conl"><input type="radio" name="form_regs_allow" value="0"<?php if (ForumSettings::get('o_regs_allow') == '0') {echo ' checked="checked"';} ?> />&#160;<strong><?= __('No') ?></strong></label>
                                        <span class="clearb"><?= __('Allow new help') ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?= __('Verify label') ?></th>
                                    <td>
                                        <label class="conl"><input type="radio" name="form_regs_verify" value="1"<?php if (ForumSettings::get('o_regs_verify') == '1') {echo ' checked="checked"';} ?> />&#160;<strong><?= __('Yes') ?></strong></label>
                                        <label class="conl"><input type="radio" name="form_regs_verify" value="0"<?php if (ForumSettings::get('o_regs_verify') == '0') {echo ' checked="checked"';} ?> />&#160;<strong><?= __('No') ?></strong></label>
                                        <span class="clearb"><?= __('Verify help') ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?= __('Report new label') ?></th>
                                    <td>
                                        <label class="conl"><input type="radio" name="form_regs_report" value="1"<?php if (ForumSettings::get('o_regs_report') == '1') {echo ' checked="checked"';} ?> />&#160;<strong><?= __('Yes') ?></strong></label>
                                        <label class="conl"><input type="radio" name="form_regs_report" value="0"<?php if (ForumSettings::get('o_regs_report') == '0') {echo ' checked="checked"';} ?> />&#160;<strong><?= __('No') ?></strong></label>
                                        <span class="clearb"><?= __('Report new help') ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?= __('Use rules label') ?></th>
                                    <td>
                                        <label class="conl"><input type="radio" name="form_rules" value="1"<?php if (ForumSettings::get('o_rules') == '1') {echo ' checked="checked"';} ?> />&#160;<strong><?= __('Yes') ?></strong></label>
                                        <label class="conl"><input type="radio" name="form_rules" value="0"<?php if (ForumSettings::get('o_rules') == '0') {echo ' checked="checked"';} ?> />&#160;<strong><?= __('No') ?></strong></label>
                                        <span class="clearb"><?= __('Use rules help') ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?= __('Rules label') ?></th>
                                    <td>
                                        <textarea name="form_rules_message" rows="10" cols="55"><?= Utils::escape(ForumSettings::get('o_rules_message')) ?></textarea>
                                        <span><?= __('Rules help') ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?= __('E-mail default label') ?></th>
                                    <td>
                                        <span><?= __('E-mail default help') ?></span>
                                        <label><input type="radio" name="form_default_email_setting" id="form_default_email_setting_0" value="0"<?php if (ForumSettings::get('email.setting') == '0') {echo ' checked="checked"';} ?> />&#160;<?= __('Display e-mail label') ?></label>
                                        <label><input type="radio" name="form_default_email_setting" id="form_default_email_setting_1" value="1"<?php if (ForumSettings::get('email.setting') == '1') {echo ' checked="checked"';} ?> />&#160;<?= __('Hide allow form label') ?></label>
                                        <label><input type="radio" name="form_default_email_setting" id="form_default_email_setting_2" value="2"<?php if (ForumSettings::get('email.setting') == '2') {echo ' checked="checked"';} ?> />&#160;<?= __('Hide both label') ?></label>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </fieldset>
                </div>
                <div class="inform">
                    <fieldset>
                        <legend><?= __('Announcement subhead') ?></legend>
                        <div class="infldset">
                            <table class="aligntop">
                                <tr>
                                    <th scope="row"><?= __('Display announcement label') ?></th>
                                    <td>
                                        <label class="conl"><input type="radio" name="form_announcement" value="1"<?php if (ForumSettings::get('o_announcement') == '1') {echo ' checked="checked"';} ?> />&#160;<strong><?= __('Yes') ?></strong></label>
                                        <label class="conl"><input type="radio" name="form_announcement" value="0"<?php if (ForumSettings::get('o_announcement') == '0') {echo ' checked="checked"';} ?> />&#160;<strong><?= __('No') ?></strong></label>
                                        <span class="clearb"><?= __('Display announcement help') ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?= __('Announcement message label') ?></th>
                                    <td>
                                        <textarea name="form_announcement_message" rows="5" cols="55"><?= Utils::escape(ForumSettings::get('o_announcement_message')) ?></textarea>
                                        <span><?= __('Announcement message help') ?></span>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </fieldset>
                </div>
                <div class="inform">
                    <fieldset>
                        <legend><?= __('Maintenance subhead') ?></legend>
                        <div class="infldset">
                            <table class="aligntop">
                                <tr>
                                    <th scope="row"><a name="maintenance"></a><?= __('Maintenance mode label') ?></th>
                                    <td>
                                        <label class="conl"><input type="radio" name="form_maintenance" value="1"<?php if (ForumSettings::get('o_maintenance') == '1') {echo ' checked="checked"';} ?> />&#160;<strong><?= __('Yes') ?></strong></label>
                                        <label class="conl"><input type="radio" name="form_maintenance" value="0"<?php if (ForumSettings::get('o_maintenance') == '0') {echo ' checked="checked"';} ?> />&#160;<strong><?= __('No') ?></strong></label>
                                        <span class="clearb"><?= __('Maintenance mode help') ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?= __('Maintenance message label') ?></th>
                                    <td>
                                        <textarea name="form_maintenance_message" rows="5" cols="55"><?= Utils::escape(ForumSettings::get('o_maintenance_message')) ?></textarea>
                                        <span><?= __('Maintenance message help') ?></span>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </fieldset>
                </div>
                <?php Container::get('hooks')->fire('view.admin.options.form'); ?>
                <p class="submitend"><input type="submit" name="save" value="<?= __('Save changes') ?>" /></p>
            </form>
        </div>
    </div>
    <div class="clearer"></div>
</div>

<?php
Container::get('hooks')->fire('view.admin.options.end');
