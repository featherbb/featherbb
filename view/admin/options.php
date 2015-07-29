<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */
 
// Make sure no one attempts to run this script "directly"
if (!defined('FEATHER')) {
    exit;
}
?>

	<div class="blockform">
		<h2><span><?php echo $lang_admin_options['Options head'] ?></span></h2>
		<div class="box">
			<form method="post" action="<?php echo get_link('admin/options/') ?>">
				<input type="hidden" name="<?php echo $csrf_key; ?>" value="<?php echo $csrf_token; ?>">
				<p class="submittop"><input type="submit" name="save" value="<?php echo $lang_admin_common['Save changes'] ?>" /></p>
				<div class="inform">
					<input type="hidden" name="form_sent" value="1" />
					<fieldset>
						<legend><?php echo $lang_admin_options['Essentials subhead'] ?></legend>
						<div class="infldset">
							<table class="aligntop">
								<tr>
									<th scope="row"><?php echo $lang_admin_options['Board title label'] ?></th>
									<td>
										<input type="text" name="form_board_title" size="50" maxlength="255" value="<?php echo feather_escape($feather_config['o_board_title']) ?>" />
										<span><?php echo $lang_admin_options['Board title help'] ?></span>
									</td>
								</tr>
								<tr>
									<th scope="row"><?php echo $lang_admin_options['Board desc label'] ?></th>
									<td>
										<textarea name="form_board_desc" cols="60" rows="3"><?php echo feather_escape($feather_config['o_board_desc']) ?></textarea>
										<span><?php echo $lang_admin_options['Board desc help'] ?></span>
									</td>
								</tr>
								<tr>
									<th scope="row"><?php echo $lang_admin_options['Base URL label'] ?></th>
									<td>
										<input type="text" name="form_base_url" size="50" maxlength="100" value="<?php echo feather_escape($feather_config['o_base_url']) ?>" />
										<span><?php echo $lang_admin_options['Base URL help'] ?></span>
									</td>
								</tr>
								<tr>
									<th scope="row"><?php echo $lang_admin_options['Timezone label'] ?></th>
									<td>
										<select name="form_default_timezone">
											<option value="-12"<?php if ($feather_config['o_default_timezone'] == -12) {
    echo ' selected="selected"';
} ?>><?php echo $lang_admin_options['UTC-12:00'] ?></option>
											<option value="-11"<?php if ($feather_config['o_default_timezone'] == -11) {
    echo ' selected="selected"';
} ?>><?php echo $lang_admin_options['UTC-11:00'] ?></option>
											<option value="-10"<?php if ($feather_config['o_default_timezone'] == -10) {
    echo ' selected="selected"';
} ?>><?php echo $lang_admin_options['UTC-10:00'] ?></option>
											<option value="-9.5"<?php if ($feather_config['o_default_timezone'] == -9.5) {
    echo ' selected="selected"';
} ?>><?php echo $lang_admin_options['UTC-09:30'] ?></option>
											<option value="-9"<?php if ($feather_config['o_default_timezone'] == -9) {
    echo ' selected="selected"';
} ?>><?php echo $lang_admin_options['UTC-09:00'] ?></option>
											<option value="-8.5"<?php if ($feather_config['o_default_timezone'] == -8.5) {
    echo ' selected="selected"';
} ?>><?php echo $lang_admin_options['UTC-08:30'] ?></option>
											<option value="-8"<?php if ($feather_config['o_default_timezone'] == -8) {
    echo ' selected="selected"';
} ?>><?php echo $lang_admin_options['UTC-08:00'] ?></option>
											<option value="-7"<?php if ($feather_config['o_default_timezone'] == -7) {
    echo ' selected="selected"';
} ?>><?php echo $lang_admin_options['UTC-07:00'] ?></option>
											<option value="-6"<?php if ($feather_config['o_default_timezone'] == -6) {
    echo ' selected="selected"';
} ?>><?php echo $lang_admin_options['UTC-06:00'] ?></option>
											<option value="-5"<?php if ($feather_config['o_default_timezone'] == -5) {
    echo ' selected="selected"';
} ?>><?php echo $lang_admin_options['UTC-05:00'] ?></option>
											<option value="-4"<?php if ($feather_config['o_default_timezone'] == -4) {
    echo ' selected="selected"';
} ?>><?php echo $lang_admin_options['UTC-04:00'] ?></option>
											<option value="-3.5"<?php if ($feather_config['o_default_timezone'] == -3.5) {
    echo ' selected="selected"';
} ?>><?php echo $lang_admin_options['UTC-03:30'] ?></option>
											<option value="-3"<?php if ($feather_config['o_default_timezone'] == -3) {
    echo ' selected="selected"';
} ?>><?php echo $lang_admin_options['UTC-03:00'] ?></option>
											<option value="-2"<?php if ($feather_config['o_default_timezone'] == -2) {
    echo ' selected="selected"';
} ?>><?php echo $lang_admin_options['UTC-02:00'] ?></option>
											<option value="-1"<?php if ($feather_config['o_default_timezone'] == -1) {
    echo ' selected="selected"';
} ?>><?php echo $lang_admin_options['UTC-01:00'] ?></option>
											<option value="0"<?php if ($feather_config['o_default_timezone'] == 0) {
    echo ' selected="selected"';
} ?>><?php echo $lang_admin_options['UTC'] ?></option>
											<option value="1"<?php if ($feather_config['o_default_timezone'] == 1) {
    echo ' selected="selected"';
} ?>><?php echo $lang_admin_options['UTC+01:00'] ?></option>
											<option value="2"<?php if ($feather_config['o_default_timezone'] == 2) {
    echo ' selected="selected"';
} ?>><?php echo $lang_admin_options['UTC+02:00'] ?></option>
											<option value="3"<?php if ($feather_config['o_default_timezone'] == 3) {
    echo ' selected="selected"';
} ?>><?php echo $lang_admin_options['UTC+03:00'] ?></option>
											<option value="3.5"<?php if ($feather_config['o_default_timezone'] == 3.5) {
    echo ' selected="selected"';
} ?>><?php echo $lang_admin_options['UTC+03:30'] ?></option>
											<option value="4"<?php if ($feather_config['o_default_timezone'] == 4) {
    echo ' selected="selected"';
} ?>><?php echo $lang_admin_options['UTC+04:00'] ?></option>
											<option value="4.5"<?php if ($feather_config['o_default_timezone'] == 4.5) {
    echo ' selected="selected"';
} ?>><?php echo $lang_admin_options['UTC+04:30'] ?></option>
											<option value="5"<?php if ($feather_config['o_default_timezone'] == 5) {
    echo ' selected="selected"';
} ?>><?php echo $lang_admin_options['UTC+05:00'] ?></option>
											<option value="5.5"<?php if ($feather_config['o_default_timezone'] == 5.5) {
    echo ' selected="selected"';
} ?>><?php echo $lang_admin_options['UTC+05:30'] ?></option>
											<option value="5.75"<?php if ($feather_config['o_default_timezone'] == 5.75) {
    echo ' selected="selected"';
} ?>><?php echo $lang_admin_options['UTC+05:45'] ?></option>
											<option value="6"<?php if ($feather_config['o_default_timezone'] == 6) {
    echo ' selected="selected"';
} ?>><?php echo $lang_admin_options['UTC+06:00'] ?></option>
											<option value="6.5"<?php if ($feather_config['o_default_timezone'] == 6.5) {
    echo ' selected="selected"';
} ?>><?php echo $lang_admin_options['UTC+06:30'] ?></option>
											<option value="7"<?php if ($feather_config['o_default_timezone'] == 7) {
    echo ' selected="selected"';
} ?>><?php echo $lang_admin_options['UTC+07:00'] ?></option>
											<option value="8"<?php if ($feather_config['o_default_timezone'] == 8) {
    echo ' selected="selected"';
} ?>><?php echo $lang_admin_options['UTC+08:00'] ?></option>
											<option value="8.75"<?php if ($feather_config['o_default_timezone'] == 8.75) {
    echo ' selected="selected"';
} ?>><?php echo $lang_admin_options['UTC+08:45'] ?></option>
											<option value="9"<?php if ($feather_config['o_default_timezone'] == 9) {
    echo ' selected="selected"';
} ?>><?php echo $lang_admin_options['UTC+09:00'] ?></option>
											<option value="9.5"<?php if ($feather_config['o_default_timezone'] == 9.5) {
    echo ' selected="selected"';
} ?>><?php echo $lang_admin_options['UTC+09:30'] ?></option>
											<option value="10"<?php if ($feather_config['o_default_timezone'] == 10) {
    echo ' selected="selected"';
} ?>><?php echo $lang_admin_options['UTC+10:00'] ?></option>
											<option value="10.5"<?php if ($feather_config['o_default_timezone'] == 10.5) {
    echo ' selected="selected"';
} ?>><?php echo $lang_admin_options['UTC+10:30'] ?></option>
											<option value="11"<?php if ($feather_config['o_default_timezone'] == 11) {
    echo ' selected="selected"';
} ?>><?php echo $lang_admin_options['UTC+11:00'] ?></option>
											<option value="11.5"<?php if ($feather_config['o_default_timezone'] == 11.5) {
    echo ' selected="selected"';
} ?>><?php echo $lang_admin_options['UTC+11:30'] ?></option>
											<option value="12"<?php if ($feather_config['o_default_timezone'] == 12) {
    echo ' selected="selected"';
} ?>><?php echo $lang_admin_options['UTC+12:00'] ?></option>
											<option value="12.75"<?php if ($feather_config['o_default_timezone'] == 12.75) {
    echo ' selected="selected"';
} ?>><?php echo $lang_admin_options['UTC+12:45'] ?></option>
											<option value="13"<?php if ($feather_config['o_default_timezone'] == 13) {
    echo ' selected="selected"';
} ?>><?php echo $lang_admin_options['UTC+13:00'] ?></option>
											<option value="14"<?php if ($feather_config['o_default_timezone'] == 14) {
    echo ' selected="selected"';
} ?>><?php echo $lang_admin_options['UTC+14:00'] ?></option>
										</select>
										<span><?php echo $lang_admin_options['Timezone help'] ?></span>
									</td>
								</tr>
								<tr>
									<th scope="row"><?php echo $lang_admin_options['DST label'] ?></th>
									<td>
										<label class="conl"><input type="radio" name="form_default_dst" value="1"<?php if ($feather_config['o_default_dst'] == '1') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?php echo $lang_admin_common['Yes'] ?></strong></label>
										<label class="conl"><input type="radio" name="form_default_dst" value="0"<?php if ($feather_config['o_default_dst'] == '0') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?php echo $lang_admin_common['No'] ?></strong></label>
										<span class="clearb"><?php echo $lang_admin_options['DST help'] ?></span>
									</td>
								</tr>
								<tr>
									<th scope="row"><?php echo $lang_admin_options['Language label'] ?></th>
									<td>
										<select name="form_default_lang">
<?php
        foreach ($languages as $temp) {
            if ($feather_config['o_default_lang'] == $temp) {
                echo "\t\t\t\t\t\t\t\t\t\t\t".'<option value="'.$temp.'" selected="selected">'.$temp.'</option>'."\n";
            } else {
                echo "\t\t\t\t\t\t\t\t\t\t\t".'<option value="'.$temp.'">'.$temp.'</option>'."\n";
            }
        }

?>
										</select>
										<span><?php echo $lang_admin_options['Language help'] ?></span>
									</td>
								</tr>
								<tr>
									<th scope="row"><?php echo $lang_admin_options['Default style label'] ?></th>
									<td>
										<select name="form_default_style">
											<?php echo $styles; ?>
										</select>
										<span><?php echo $lang_admin_options['Default style help'] ?></span>
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
						<legend><?php echo $lang_admin_options['Timeouts subhead'] ?></legend>
						<div class="infldset">
							<table class="aligntop">
								<tr>
									<th scope="row"><?php echo $lang_admin_options['Time format label'] ?></th>
									<td>
										<input type="text" name="form_time_format" size="25" maxlength="25" value="<?php echo feather_escape($feather_config['o_time_format']) ?>" />
										<span><?php printf($lang_admin_options['Time format help'], gmdate($feather_config['o_time_format'], $timestamp), '<a href="http://www.php.net/manual/en/function.date.php">'.$lang_admin_options['PHP manual'].'</a>') ?></span>
									</td>
								</tr>
								<tr>
									<th scope="row"><?php echo $lang_admin_options['Date format label'] ?></th>
									<td>
										<input type="text" name="form_date_format" size="25" maxlength="25" value="<?php echo feather_escape($feather_config['o_date_format']) ?>" />
										<span><?php printf($lang_admin_options['Date format help'], gmdate($feather_config['o_date_format'], $timestamp), '<a href="http://www.php.net/manual/en/function.date.php">'.$lang_admin_options['PHP manual'].'</a>') ?></span>
									</td>
								</tr>
								<tr>
									<th scope="row"><?php echo $lang_admin_options['Visit timeout label'] ?></th>
									<td>
										<input type="text" name="form_timeout_visit" size="5" maxlength="5" value="<?php echo $feather_config['o_timeout_visit'] ?>" />
										<span><?php echo $lang_admin_options['Visit timeout help'] ?></span>
									</td>
								</tr>
								<tr>
									<th scope="row"><?php echo $lang_admin_options['Online timeout label'] ?></th>
									<td>
										<input type="text" name="form_timeout_online" size="5" maxlength="5" value="<?php echo $feather_config['o_timeout_online'] ?>" />
										<span><?php echo $lang_admin_options['Online timeout help'] ?></span>
									</td>
								</tr>
								<tr>
									<th scope="row"><?php echo $lang_admin_options['Redirect time label'] ?></th>
									<td>
										<input type="text" name="form_redirect_delay" size="3" maxlength="3" value="<?php echo $feather_config['o_redirect_delay'] ?>" />
										<span><?php echo $lang_admin_options['Redirect time help'] ?></span>
									</td>
								</tr>
							</table>
						</div>
					</fieldset>
				</div>
				<div class="inform">
					<fieldset>
						<legend><?php echo $lang_admin_options['Display subhead'] ?></legend>
						<div class="infldset">
							<table class="aligntop">
								<tr>
									<th scope="row"><?php echo $lang_admin_options['Version number label'] ?></th>
									<td>
										<label class="conl"><input type="radio" name="form_show_version" value="1"<?php if ($feather_config['o_show_version'] == '1') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?php echo $lang_admin_common['Yes'] ?></strong></label>
										<label class="conl"><input type="radio" name="form_show_version" value="0"<?php if ($feather_config['o_show_version'] == '0') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?php echo $lang_admin_common['No'] ?></strong></label>
										<span class="clearb"><?php echo $lang_admin_options['Version number help'] ?></span>
									</td>
								</tr>
								<tr>
									<th scope="row"><?php echo $lang_admin_options['Info in posts label'] ?></th>
									<td>
										<label class="conl"><input type="radio" name="form_show_user_info" value="1"<?php if ($feather_config['o_show_user_info'] == '1') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?php echo $lang_admin_common['Yes'] ?></strong></label>
										<label class="conl"><input type="radio" name="form_show_user_info" value="0"<?php if ($feather_config['o_show_user_info'] == '0') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?php echo $lang_admin_common['No'] ?></strong></label>
										<span class="clearb"><?php echo $lang_admin_options['Info in posts help'] ?></span>
									</td>
								</tr>
								<tr>
									<th scope="row"><?php echo $lang_admin_options['Post count label'] ?></th>
									<td>
										<label class="conl"><input type="radio" name="form_show_post_count" value="1"<?php if ($feather_config['o_show_post_count'] == '1') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?php echo $lang_admin_common['Yes'] ?></strong></label>
										<label class="conl"><input type="radio" name="form_show_post_count" value="0"<?php if ($feather_config['o_show_post_count'] == '0') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?php echo $lang_admin_common['No'] ?></strong></label>
										<span class="clearb"><?php echo $lang_admin_options['Post count help'] ?></span>
									</td>
								</tr>
								<tr>
									<th scope="row"><?php echo $lang_admin_options['Smilies label'] ?></th>
									<td>
										<label class="conl"><input type="radio" name="form_smilies" value="1"<?php if ($feather_config['o_smilies'] == '1') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?php echo $lang_admin_common['Yes'] ?></strong></label>
										<label class="conl"><input type="radio" name="form_smilies" value="0"<?php if ($feather_config['o_smilies'] == '0') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?php echo $lang_admin_common['No'] ?></strong></label>
										<span class="clearb"><?php echo $lang_admin_options['Smilies help'] ?></span>
									</td>
								</tr>
								<tr>
									<th scope="row"><?php echo $lang_admin_options['Smilies sigs label'] ?></th>
									<td>
										<label class="conl"><input type="radio" name="form_smilies_sig" value="1"<?php if ($feather_config['o_smilies_sig'] == '1') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?php echo $lang_admin_common['Yes'] ?></strong></label>
										<label class="conl"><input type="radio" name="form_smilies_sig" value="0"<?php if ($feather_config['o_smilies_sig'] == '0') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?php echo $lang_admin_common['No'] ?></strong></label>
										<span class="clearb"><?php echo $lang_admin_options['Smilies sigs help'] ?></span>
									</td>
								</tr>
								<tr>
									<th scope="row"><?php echo $lang_admin_options['Clickable links label'] ?></th>
									<td>
										<label class="conl"><input type="radio" name="form_make_links" value="1"<?php if ($feather_config['o_make_links'] == '1') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?php echo $lang_admin_common['Yes'] ?></strong></label>
										<label class="conl"><input type="radio" name="form_make_links" value="0"<?php if ($feather_config['o_make_links'] == '0') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?php echo $lang_admin_common['No'] ?></strong></label>
										<span class="clearb"><?php echo $lang_admin_options['Clickable links help'] ?></span>
									</td>
								</tr>
								<tr>
									<th scope="row"><?php echo $lang_admin_options['Topic review label'] ?></th>
									<td>
										<input type="text" name="form_topic_review" size="3" maxlength="3" value="<?php echo $feather_config['o_topic_review'] ?>" />
										<span><?php echo $lang_admin_options['Topic review help'] ?></span>
									</td>
								</tr>
								<tr>
									<th scope="row"><?php echo $lang_admin_options['Topics per page label'] ?></th>
									<td>
										<input type="text" name="form_disp_topics_default" size="3" maxlength="2" value="<?php echo $feather_config['o_disp_topics_default'] ?>" />
										<span><?php echo $lang_admin_options['Topics per page help'] ?></span>
									</td>
								</tr>
								<tr>
									<th scope="row"><?php echo $lang_admin_options['Posts per page label'] ?></th>
									<td>
										<input type="text" name="form_disp_posts_default" size="3" maxlength="2" value="<?php echo $feather_config['o_disp_posts_default'] ?>" />
										<span><?php echo $lang_admin_options['Posts per page help'] ?></span>
									</td>
								</tr>
								<tr>
									<th scope="row"><?php echo $lang_admin_options['Indent label'] ?></th>
									<td>
										<input type="text" name="form_indent_num_spaces" size="3" maxlength="3" value="<?php echo $feather_config['o_indent_num_spaces'] ?>" />
										<span><?php echo $lang_admin_options['Indent help'] ?></span>
									</td>
								</tr>
								<tr>
									<th scope="row"><?php echo $lang_admin_options['Quote depth label'] ?></th>
									<td>
										<input type="text" name="form_quote_depth" size="3" maxlength="3" value="<?php echo $feather_config['o_quote_depth'] ?>" />
										<span><?php echo $lang_admin_options['Quote depth help'] ?></span>
									</td>
								</tr>
							</table>
						</div>
					</fieldset>
				</div>
				<div class="inform">
					<fieldset>
						<legend><?php echo $lang_admin_options['Features subhead'] ?></legend>
						<div class="infldset">
							<table class="aligntop">
								<tr>
									<th scope="row"><?php echo $lang_admin_options['Quick post label'] ?></th>
									<td>
										<label class="conl"><input type="radio" name="form_quickpost" value="1"<?php if ($feather_config['o_quickpost'] == '1') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?php echo $lang_admin_common['Yes'] ?></strong></label>
										<label class="conl"><input type="radio" name="form_quickpost" value="0"<?php if ($feather_config['o_quickpost'] == '0') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?php echo $lang_admin_common['No'] ?></strong></label>
										<span class="clearb"><?php echo $lang_admin_options['Quick post help'] ?></span>
									</td>
								</tr>
								<tr>
									<th scope="row"><?php echo $lang_admin_options['Users online label'] ?></th>
									<td>
										<label class="conl"><input type="radio" name="form_users_online" value="1"<?php if ($feather_config['o_users_online'] == '1') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?php echo $lang_admin_common['Yes'] ?></strong></label>
										<label class="conl"><input type="radio" name="form_users_online" value="0"<?php if ($feather_config['o_users_online'] == '0') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?php echo $lang_admin_common['No'] ?></strong></label>
										<span class="clearb"><?php echo $lang_admin_options['Users online help'] ?></span>
									</td>
								</tr>
								<tr>
									<th scope="row"><a name="censoring"></a><?php echo $lang_admin_options['Censor words label'] ?></th>
									<td>
										<label class="conl"><input type="radio" name="form_censoring" value="1"<?php if ($feather_config['o_censoring'] == '1') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?php echo $lang_admin_common['Yes'] ?></strong></label>
										<label class="conl"><input type="radio" name="form_censoring" value="0"<?php if ($feather_config['o_censoring'] == '0') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?php echo $lang_admin_common['No'] ?></strong></label>
										<span class="clearb"><?php printf($lang_admin_options['Censor words help'], '<a href="'.get_link('admin/censoring/').'">'.$lang_admin_common['Censoring'].'</a>') ?></span>
									</td>
								</tr>
								<tr>
									<th scope="row"><a name="signatures"></a><?php echo $lang_admin_options['Signatures label'] ?></th>
									<td>
										<label class="conl"><input type="radio" name="form_signatures" value="1"<?php if ($feather_config['o_signatures'] == '1') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?php echo $lang_admin_common['Yes'] ?></strong></label>
										<label class="conl"><input type="radio" name="form_signatures" value="0"<?php if ($feather_config['o_signatures'] == '0') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?php echo $lang_admin_common['No'] ?></strong></label>
										<span class="clearb"><?php echo $lang_admin_options['Signatures help'] ?></span>
									</td>
								</tr>
								<tr>
									<th scope="row"><?php echo $lang_admin_options['User has posted label'] ?></th>
									<td>
										<label class="conl"><input type="radio" name="form_show_dot" value="1"<?php if ($feather_config['o_show_dot'] == '1') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?php echo $lang_admin_common['Yes'] ?></strong></label>
										<label class="conl"><input type="radio" name="form_show_dot" value="0"<?php if ($feather_config['o_show_dot'] == '0') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?php echo $lang_admin_common['No'] ?></strong></label>
										<span class="clearb"><?php echo $lang_admin_options['User has posted help'] ?></span>
									</td>
								</tr>
								<tr>
									<th scope="row"><?php echo $lang_admin_options['Topic views label'] ?></th>
									<td>
										<label class="conl"><input type="radio" name="form_topic_views" value="1"<?php if ($feather_config['o_topic_views'] == '1') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?php echo $lang_admin_common['Yes'] ?></strong></label>
										<label class="conl"><input type="radio" name="form_topic_views" value="0"<?php if ($feather_config['o_topic_views'] == '0') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?php echo $lang_admin_common['No'] ?></strong></label>
										<span class="clearb"><?php echo $lang_admin_options['Topic views help'] ?></span>
									</td>
								</tr>
								<tr>
									<th scope="row"><?php echo $lang_admin_options['Quick jump label'] ?></th>
									<td>
										<label class="conl"><input type="radio" name="form_quickjump" value="1"<?php if ($feather_config['o_quickjump'] == '1') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?php echo $lang_admin_common['Yes'] ?></strong></label>
										<label class="conl"><input type="radio" name="form_quickjump" value="0"<?php if ($feather_config['o_quickjump'] == '0') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?php echo $lang_admin_common['No'] ?></strong></label>
										<span class="clearb"><?php echo $lang_admin_options['Quick jump help'] ?></span>
									</td>
								</tr>
								<tr>
									<th scope="row"><?php echo $lang_admin_options['GZip label'] ?></th>
									<td>
										<label class="conl"><input type="radio" name="form_gzip" value="1"<?php if ($feather_config['o_gzip'] == '1') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?php echo $lang_admin_common['Yes'] ?></strong></label>
										<label class="conl"><input type="radio" name="form_gzip" value="0"<?php if ($feather_config['o_gzip'] == '0') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?php echo $lang_admin_common['No'] ?></strong></label>
										<span class="clearb"><?php echo $lang_admin_options['GZip help'] ?></span>
									</td>
								</tr>
								<tr>
									<th scope="row"><?php echo $lang_admin_options['Search all label'] ?></th>
									<td>
										<label class="conl"><input type="radio" name="form_search_all_forums" value="1"<?php if ($feather_config['o_search_all_forums'] == '1') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?php echo $lang_admin_common['Yes'] ?></strong></label>
										<label class="conl"><input type="radio" name="form_search_all_forums" value="0"<?php if ($feather_config['o_search_all_forums'] == '0') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?php echo $lang_admin_common['No'] ?></strong></label>
										<span class="clearb"><?php echo $lang_admin_options['Search all help'] ?></span>
									</td>
								</tr>
								<tr>
									<th scope="row"><?php echo $lang_admin_options['Menu items label'] ?></th>
									<td>
										<textarea name="form_additional_navlinks" rows="3" cols="55"><?php echo feather_escape($feather_config['o_additional_navlinks']) ?></textarea>
										<span><?php echo $lang_admin_options['Menu items help'] ?></span>
									</td>
								</tr>
							</table>
						</div>
					</fieldset>
				</div>
				<div class="inform">
					<fieldset>
						<legend><?php echo $lang_admin_options['Feed subhead'] ?></legend>
						<div class="infldset">
							<table class="aligntop">
								<tr>
									<th scope="row"><?php echo $lang_admin_options['Default feed label'] ?></th>
									<td>
										<label class="conl"><input type="radio" name="form_feed_type" value="0"<?php if ($feather_config['o_feed_type'] == '0') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?php echo $lang_admin_options['None'] ?></strong></label>
										<label class="conl"><input type="radio" name="form_feed_type" value="1"<?php if ($feather_config['o_feed_type'] == '1') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?php echo $lang_admin_options['RSS'] ?></strong></label>
										<label class="conl"><input type="radio" name="form_feed_type" value="2"<?php if ($feather_config['o_feed_type'] == '2') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?php echo $lang_admin_options['Atom'] ?></strong></label>
										<span class="clearb"><?php echo $lang_admin_options['Default feed help'] ?></span>
									</td>
								</tr>
								<tr>
									<th scope="row"><?php echo $lang_admin_options['Feed TTL label'] ?></th>
									<td>
										<select name="form_feed_ttl">
											<option value="0"<?php if ($feather_config['o_feed_ttl'] == '0') {
    echo ' selected="selected"';
} ?>><?php echo $lang_admin_options['No cache'] ?></option>
											<?php echo $times ?>
										</select>
										<span><?php echo $lang_admin_options['Feed TTL help'] ?></span>
									</td>
								</tr>
							</table>
						</div>
					</fieldset>
				</div>
				<div class="inform">
					<fieldset>
						<legend><?php echo $lang_admin_options['Reports subhead'] ?></legend>
						<div class="infldset">
							<table class="aligntop">
								<tr>
									<th scope="row"><?php echo $lang_admin_options['Reporting method label'] ?></th>
									<td>
										<label class="conl"><input type="radio" name="form_report_method" value="0"<?php if ($feather_config['o_report_method'] == '0') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?php echo $lang_admin_options['Internal'] ?></strong></label>
										<label class="conl"><input type="radio" name="form_report_method" value="1"<?php if ($feather_config['o_report_method'] == '1') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?php echo $lang_admin_options['By e-mail'] ?></strong></label>
										<label class="conl"><input type="radio" name="form_report_method" value="2"<?php if ($feather_config['o_report_method'] == '2') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?php echo $lang_admin_options['Both'] ?></strong></label>
										<span class="clearb"><?php echo $lang_admin_options['Reporting method help'] ?></span>
									</td>
								</tr>
								<tr>
									<th scope="row"><?php echo $lang_admin_options['Mailing list label'] ?></th>
									<td>
										<textarea name="form_mailing_list" rows="5" cols="55"><?php echo feather_escape($feather_config['o_mailing_list']) ?></textarea>
										<span><?php echo $lang_admin_options['Mailing list help'] ?></span>
									</td>
								</tr>
							</table>
						</div>
					</fieldset>
				</div>
				<div class="inform">
					<fieldset>
						<legend><?php echo $lang_admin_options['Avatars subhead'] ?></legend>
						<div class="infldset">
							<table class="aligntop">
								<tr>
									<th scope="row"><?php echo $lang_admin_options['Use avatars label'] ?></th>
									<td>
										<label class="conl"><input type="radio" name="form_avatars" value="1"<?php if ($feather_config['o_avatars'] == '1') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?php echo $lang_admin_common['Yes'] ?></strong></label>
										<label class="conl"><input type="radio" name="form_avatars" value="0"<?php if ($feather_config['o_avatars'] == '0') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?php echo $lang_admin_common['No'] ?></strong></label>
										<span class="clearb"><?php echo $lang_admin_options['Use avatars help'] ?></span>
									</td>
								</tr>
								<tr>
									<th scope="row"><?php echo $lang_admin_options['Upload directory label'] ?></th>
									<td>
										<input type="text" name="form_avatars_dir" size="35" maxlength="50" value="<?php echo feather_escape($feather_config['o_avatars_dir']) ?>" />
										<span><?php echo $lang_admin_options['Upload directory help'] ?></span>
									</td>
								</tr>
								<tr>
									<th scope="row"><?php echo $lang_admin_options['Max width label'] ?></th>
									<td>
										<input type="text" name="form_avatars_width" size="5" maxlength="5" value="<?php echo $feather_config['o_avatars_width'] ?>" />
										<span><?php echo $lang_admin_options['Max width help'] ?></span>
									</td>
								</tr>
								<tr>
									<th scope="row"><?php echo $lang_admin_options['Max height label'] ?></th>
									<td>
										<input type="text" name="form_avatars_height" size="5" maxlength="5" value="<?php echo $feather_config['o_avatars_height'] ?>" />
										<span><?php echo $lang_admin_options['Max height help'] ?></span>
									</td>
								</tr>
								<tr>
									<th scope="row"><?php echo $lang_admin_options['Max size label'] ?></th>
									<td>
										<input type="text" name="form_avatars_size" size="6" maxlength="6" value="<?php echo $feather_config['o_avatars_size'] ?>" />
										<span><?php echo $lang_admin_options['Max size help'] ?></span>
									</td>
								</tr>
							</table>
						</div>
					</fieldset>
				</div>
				<div class="inform">
					<fieldset>
						<legend><?php echo $lang_admin_options['E-mail subhead'] ?></legend>
						<div class="infldset">
							<table class="aligntop">
								<tr>
									<th scope="row"><?php echo $lang_admin_options['Admin e-mail label'] ?></th>
									<td>
										<input type="text" name="form_admin_email" size="50" maxlength="80" value="<?php echo feather_escape($feather_config['o_admin_email']) ?>" />
										<span><?php echo $lang_admin_options['Admin e-mail help'] ?></span>
									</td>
								</tr>
								<tr>
									<th scope="row"><?php echo $lang_admin_options['Webmaster e-mail label'] ?></th>
									<td>
										<input type="text" name="form_webmaster_email" size="50" maxlength="80" value="<?php echo feather_escape($feather_config['o_webmaster_email']) ?>" />
										<span><?php echo $lang_admin_options['Webmaster e-mail help'] ?></span>
									</td>
								</tr>
								<tr>
									<th scope="row"><?php echo $lang_admin_options['Forum subscriptions label'] ?></th>
									<td>
										<label class="conl"><input type="radio" name="form_forum_subscriptions" value="1"<?php if ($feather_config['o_forum_subscriptions'] == '1') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?php echo $lang_admin_common['Yes'] ?></strong></label>
										<label class="conl"><input type="radio" name="form_forum_subscriptions" value="0"<?php if ($feather_config['o_forum_subscriptions'] == '0') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?php echo $lang_admin_common['No'] ?></strong></label>
										<span class="clearb"><?php echo $lang_admin_options['Forum subscriptions help'] ?></span>
									</td>
								</tr>
								<tr>
									<th scope="row"><?php echo $lang_admin_options['Topic subscriptions label'] ?></th>
									<td>
										<label class="conl"><input type="radio" name="form_topic_subscriptions" value="1"<?php if ($feather_config['o_topic_subscriptions'] == '1') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?php echo $lang_admin_common['Yes'] ?></strong></label>
										<label class="conl"><input type="radio" name="form_topic_subscriptions" value="0"<?php if ($feather_config['o_topic_subscriptions'] == '0') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?php echo $lang_admin_common['No'] ?></strong></label>
										<span class="clearb"><?php echo $lang_admin_options['Topic subscriptions help'] ?></span>
									</td>
								</tr>
								<tr>
									<th scope="row"><?php echo $lang_admin_options['SMTP address label'] ?></th>
									<td>
										<input type="text" name="form_smtp_host" size="30" maxlength="100" value="<?php echo feather_escape($feather_config['o_smtp_host']) ?>" />
										<span><?php echo $lang_admin_options['SMTP address help'] ?></span>
									</td>
								</tr>
								<tr>
									<th scope="row"><?php echo $lang_admin_options['SMTP username label'] ?></th>
									<td>
										<input type="text" name="form_smtp_user" size="25" maxlength="50" value="<?php echo feather_escape($feather_config['o_smtp_user']) ?>" />
										<span><?php echo $lang_admin_options['SMTP username help'] ?></span>
									</td>
								</tr>
								<tr>
									<th scope="row"><?php echo $lang_admin_options['SMTP password label'] ?></th>
									<td>
										<label><input type="checkbox" name="form_smtp_change_pass" value="1" />&#160;<?php echo $lang_admin_options['SMTP change password help'] ?></label>
<?php $smtp_pass = !empty($feather_config['o_smtp_pass']) ? random_key(feather_strlen($feather_config['o_smtp_pass']), true) : ''; ?>
										<input type="password" name="form_smtp_pass1" size="25" maxlength="50" value="<?php echo $smtp_pass ?>" />
										<input type="password" name="form_smtp_pass2" size="25" maxlength="50" value="<?php echo $smtp_pass ?>" />
										<span><?php echo $lang_admin_options['SMTP password help'] ?></span>
									</td>
								</tr>
								<tr>
									<th scope="row"><?php echo $lang_admin_options['SMTP SSL label'] ?></th>
									<td>
										<label class="conl"><input type="radio" name="form_smtp_ssl" value="1"<?php if ($feather_config['o_smtp_ssl'] == '1') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?php echo $lang_admin_common['Yes'] ?></strong></label>
										<label class="conl"><input type="radio" name="form_smtp_ssl" value="0"<?php if ($feather_config['o_smtp_ssl'] == '0') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?php echo $lang_admin_common['No'] ?></strong></label>
										<span class="clearb"><?php echo $lang_admin_options['SMTP SSL help'] ?></span>
									</td>
								</tr>
							</table>
						</div>
					</fieldset>
				</div>
				<div class="inform">
					<fieldset>
						<legend><?php echo $lang_admin_options['Registration subhead'] ?></legend>
						<div class="infldset">
							<table class="aligntop">
								<tr>
									<th scope="row"><?php echo $lang_admin_options['Allow new label'] ?></th>
									<td>
										<label class="conl"><input type="radio" name="form_regs_allow" value="1"<?php if ($feather_config['o_regs_allow'] == '1') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?php echo $lang_admin_common['Yes'] ?></strong></label>
										<label class="conl"><input type="radio" name="form_regs_allow" value="0"<?php if ($feather_config['o_regs_allow'] == '0') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?php echo $lang_admin_common['No'] ?></strong></label>
										<span class="clearb"><?php echo $lang_admin_options['Allow new help'] ?></span>
									</td>
								</tr>
								<tr>
									<th scope="row"><?php echo $lang_admin_options['Verify label'] ?></th>
									<td>
										<label class="conl"><input type="radio" name="form_regs_verify" value="1"<?php if ($feather_config['o_regs_verify'] == '1') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?php echo $lang_admin_common['Yes'] ?></strong></label>
										<label class="conl"><input type="radio" name="form_regs_verify" value="0"<?php if ($feather_config['o_regs_verify'] == '0') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?php echo $lang_admin_common['No'] ?></strong></label>
										<span class="clearb"><?php echo $lang_admin_options['Verify help'] ?></span>
									</td>
								</tr>
								<tr>
									<th scope="row"><?php echo $lang_admin_options['Report new label'] ?></th>
									<td>
										<label class="conl"><input type="radio" name="form_regs_report" value="1"<?php if ($feather_config['o_regs_report'] == '1') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?php echo $lang_admin_common['Yes'] ?></strong></label>
										<label class="conl"><input type="radio" name="form_regs_report" value="0"<?php if ($feather_config['o_regs_report'] == '0') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?php echo $lang_admin_common['No'] ?></strong></label>
										<span class="clearb"><?php echo $lang_admin_options['Report new help'] ?></span>
									</td>
								</tr>
								<tr>
									<th scope="row"><?php echo $lang_admin_options['Use rules label'] ?></th>
									<td>
										<label class="conl"><input type="radio" name="form_rules" value="1"<?php if ($feather_config['o_rules'] == '1') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?php echo $lang_admin_common['Yes'] ?></strong></label>
										<label class="conl"><input type="radio" name="form_rules" value="0"<?php if ($feather_config['o_rules'] == '0') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?php echo $lang_admin_common['No'] ?></strong></label>
										<span class="clearb"><?php echo $lang_admin_options['Use rules help'] ?></span>
									</td>
								</tr>
								<tr>
									<th scope="row"><?php echo $lang_admin_options['Rules label'] ?></th>
									<td>
										<textarea name="form_rules_message" rows="10" cols="55"><?php echo feather_escape($feather_config['o_rules_message']) ?></textarea>
										<span><?php echo $lang_admin_options['Rules help'] ?></span>
									</td>
								</tr>
								<tr>
									<th scope="row"><?php echo $lang_admin_options['E-mail default label'] ?></th>
									<td>
										<span><?php echo $lang_admin_options['E-mail default help'] ?></span>
										<label><input type="radio" name="form_default_email_setting" id="form_default_email_setting_0" value="0"<?php if ($feather_config['o_default_email_setting'] == '0') {
    echo ' checked="checked"';
} ?> />&#160;<?php echo $lang_admin_options['Display e-mail label'] ?></label>
										<label><input type="radio" name="form_default_email_setting" id="form_default_email_setting_1" value="1"<?php if ($feather_config['o_default_email_setting'] == '1') {
    echo ' checked="checked"';
} ?> />&#160;<?php echo $lang_admin_options['Hide allow form label'] ?></label>
										<label><input type="radio" name="form_default_email_setting" id="form_default_email_setting_2" value="2"<?php if ($feather_config['o_default_email_setting'] == '2') {
    echo ' checked="checked"';
} ?> />&#160;<?php echo $lang_admin_options['Hide both label'] ?></label>
									</td>
								</tr>
							</table>
						</div>
					</fieldset>
				</div>
				<div class="inform">
					<fieldset>
						<legend><?php echo $lang_admin_options['Announcement subhead'] ?></legend>
						<div class="infldset">
							<table class="aligntop">
								<tr>
									<th scope="row"><?php echo $lang_admin_options['Display announcement label'] ?></th>
									<td>
										<label class="conl"><input type="radio" name="form_announcement" value="1"<?php if ($feather_config['o_announcement'] == '1') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?php echo $lang_admin_common['Yes'] ?></strong></label>
										<label class="conl"><input type="radio" name="form_announcement" value="0"<?php if ($feather_config['o_announcement'] == '0') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?php echo $lang_admin_common['No'] ?></strong></label>
										<span class="clearb"><?php echo $lang_admin_options['Display announcement help'] ?></span>
									</td>
								</tr>
								<tr>
									<th scope="row"><?php echo $lang_admin_options['Announcement message label'] ?></th>
									<td>
										<textarea name="form_announcement_message" rows="5" cols="55"><?php echo feather_escape($feather_config['o_announcement_message']) ?></textarea>
										<span><?php echo $lang_admin_options['Announcement message help'] ?></span>
									</td>
								</tr>
							</table>
						</div>
					</fieldset>
				</div>
				<div class="inform">
					<fieldset>
						<legend><?php echo $lang_admin_options['Maintenance subhead'] ?></legend>
						<div class="infldset">
							<table class="aligntop">
								<tr>
									<th scope="row"><a name="maintenance"></a><?php echo $lang_admin_options['Maintenance mode label'] ?></th>
									<td>
										<label class="conl"><input type="radio" name="form_maintenance" value="1"<?php if ($feather_config['o_maintenance'] == '1') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?php echo $lang_admin_common['Yes'] ?></strong></label>
										<label class="conl"><input type="radio" name="form_maintenance" value="0"<?php if ($feather_config['o_maintenance'] == '0') {
    echo ' checked="checked"';
} ?> />&#160;<strong><?php echo $lang_admin_common['No'] ?></strong></label>
										<span class="clearb"><?php echo $lang_admin_options['Maintenance mode help'] ?></span>
									</td>
								</tr>
								<tr>
									<th scope="row"><?php echo $lang_admin_options['Maintenance message label'] ?></th>
									<td>
										<textarea name="form_maintenance_message" rows="5" cols="55"><?php echo feather_escape($feather_config['o_maintenance_message']) ?></textarea>
										<span><?php echo $lang_admin_options['Maintenance message help'] ?></span>
									</td>
								</tr>
							</table>
						</div>
					</fieldset>
				</div>
				<p class="submitend"><input type="submit" name="save" value="<?php echo $lang_admin_common['Save changes'] ?>" /></p>
			</form>
		</div>
	</div>
	<div class="clearer"></div>
</div>