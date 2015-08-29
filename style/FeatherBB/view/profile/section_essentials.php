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
	<h2><span><?php echo feather_escape($user['username']).' - '.__('Section essentials') ?></span></h2>
	<div class="box">
		<form id="profile1" method="post" action="<?php echo $feather->url->get('user/'.$id.'/section/essentials/') ?>" onsubmit="return process_form(this)">
			<input type="hidden" name="<?php echo $csrf_key; ?>" value="<?php echo $csrf_token; ?>">
			<div class="inform">
				<fieldset>
					<legend><?php _e('Username and pass legend') ?></legend>
					<div class="infldset">
						<input type="hidden" name="form_sent" value="1" />
						<?php echo $user_disp['username_field'] ?>
<?php if ($feather->user->id == $id || $feather->user->g_id == FEATHER_ADMIN || ($user['g_moderator'] == '0' && $feather->user->g_mod_change_passwords == '1')): ?>							<p class="actions"><span><a href="<?php echo $feather->url->get('user/'.$id.'/action/change_pass/') ?>"><?php _e('Change pass') ?></a></span></p>
<?php endif; ?>						</div>
				</fieldset>
			</div>
			<div class="inform">
				<fieldset>
					<legend><?php _e('Email legend') ?></legend>
					<div class="infldset">
						<?php echo $user_disp['email_field'] ?>
					</div>
				</fieldset>
			</div>
			<div class="inform">
				<fieldset>
					<legend><?php _e('Localisation legend') ?></legend>
					<div class="infldset">
						<p><?php _e('Time zone info') ?></p>
						<label><?php _e('Time zone')."\n" ?>
						<br /><select name="form_timezone">
							<option value="-12"<?php if ($user['timezone'] == -12) {
    echo ' selected="selected"';
} ?>><?php _e('UTC-12:00') ?></option>
							<option value="-11"<?php if ($user['timezone'] == -11) {
    echo ' selected="selected"';
} ?>><?php _e('UTC-11:00') ?></option>
							<option value="-10"<?php if ($user['timezone'] == -10) {
    echo ' selected="selected"';
} ?>><?php _e('UTC-10:00') ?></option>
							<option value="-9.5"<?php if ($user['timezone'] == -9.5) {
    echo ' selected="selected"';
} ?>><?php _e('UTC-09:30') ?></option>
							<option value="-9"<?php if ($user['timezone'] == -9) {
    echo ' selected="selected"';
} ?>><?php _e('UTC-09:00') ?></option>
							<option value="-8.5"<?php if ($user['timezone'] == -8.5) {
    echo ' selected="selected"';
} ?>><?php _e('UTC-08:30') ?></option>
							<option value="-8"<?php if ($user['timezone'] == -8) {
    echo ' selected="selected"';
} ?>><?php _e('UTC-08:00') ?></option>
							<option value="-7"<?php if ($user['timezone'] == -7) {
    echo ' selected="selected"';
} ?>><?php _e('UTC-07:00') ?></option>
							<option value="-6"<?php if ($user['timezone'] == -6) {
    echo ' selected="selected"';
} ?>><?php _e('UTC-06:00') ?></option>
							<option value="-5"<?php if ($user['timezone'] == -5) {
    echo ' selected="selected"';
} ?>><?php _e('UTC-05:00') ?></option>
							<option value="-4"<?php if ($user['timezone'] == -4) {
    echo ' selected="selected"';
} ?>><?php _e('UTC-04:00') ?></option>
							<option value="-3.5"<?php if ($user['timezone'] == -3.5) {
    echo ' selected="selected"';
} ?>><?php _e('UTC-03:30') ?></option>
							<option value="-3"<?php if ($user['timezone'] == -3) {
    echo ' selected="selected"';
} ?>><?php _e('UTC-03:00') ?></option>
							<option value="-2"<?php if ($user['timezone'] == -2) {
    echo ' selected="selected"';
} ?>><?php _e('UTC-02:00') ?></option>
							<option value="-1"<?php if ($user['timezone'] == -1) {
    echo ' selected="selected"';
} ?>><?php _e('UTC-01:00') ?></option>
							<option value="0"<?php if ($user['timezone'] == 0) {
    echo ' selected="selected"';
} ?>><?php _e('UTC') ?></option>
							<option value="1"<?php if ($user['timezone'] == 1) {
    echo ' selected="selected"';
} ?>><?php _e('UTC+01:00') ?></option>
							<option value="2"<?php if ($user['timezone'] == 2) {
    echo ' selected="selected"';
} ?>><?php _e('UTC+02:00') ?></option>
							<option value="3"<?php if ($user['timezone'] == 3) {
    echo ' selected="selected"';
} ?>><?php _e('UTC+03:00') ?></option>
							<option value="3.5"<?php if ($user['timezone'] == 3.5) {
    echo ' selected="selected"';
} ?>><?php _e('UTC+03:30') ?></option>
							<option value="4"<?php if ($user['timezone'] == 4) {
    echo ' selected="selected"';
} ?>><?php _e('UTC+04:00') ?></option>
							<option value="4.5"<?php if ($user['timezone'] == 4.5) {
    echo ' selected="selected"';
} ?>><?php _e('UTC+04:30') ?></option>
							<option value="5"<?php if ($user['timezone'] == 5) {
    echo ' selected="selected"';
} ?>><?php _e('UTC+05:00') ?></option>
							<option value="5.5"<?php if ($user['timezone'] == 5.5) {
    echo ' selected="selected"';
} ?>><?php _e('UTC+05:30') ?></option>
							<option value="5.75"<?php if ($user['timezone'] == 5.75) {
    echo ' selected="selected"';
} ?>><?php _e('UTC+05:45') ?></option>
							<option value="6"<?php if ($user['timezone'] == 6) {
    echo ' selected="selected"';
} ?>><?php _e('UTC+06:00') ?></option>
							<option value="6.5"<?php if ($user['timezone'] == 6.5) {
    echo ' selected="selected"';
} ?>><?php _e('UTC+06:30') ?></option>
							<option value="7"<?php if ($user['timezone'] == 7) {
    echo ' selected="selected"';
} ?>><?php _e('UTC+07:00') ?></option>
							<option value="8"<?php if ($user['timezone'] == 8) {
    echo ' selected="selected"';
} ?>><?php _e('UTC+08:00') ?></option>
							<option value="8.75"<?php if ($user['timezone'] == 8.75) {
    echo ' selected="selected"';
} ?>><?php _e('UTC+08:45') ?></option>
							<option value="9"<?php if ($user['timezone'] == 9) {
    echo ' selected="selected"';
} ?>><?php _e('UTC+09:00') ?></option>
							<option value="9.5"<?php if ($user['timezone'] == 9.5) {
    echo ' selected="selected"';
} ?>><?php _e('UTC+09:30') ?></option>
							<option value="10"<?php if ($user['timezone'] == 10) {
    echo ' selected="selected"';
} ?>><?php _e('UTC+10:00') ?></option>
							<option value="10.5"<?php if ($user['timezone'] == 10.5) {
    echo ' selected="selected"';
} ?>><?php _e('UTC+10:30') ?></option>
							<option value="11"<?php if ($user['timezone'] == 11) {
    echo ' selected="selected"';
} ?>><?php _e('UTC+11:00') ?></option>
							<option value="11.5"<?php if ($user['timezone'] == 11.5) {
    echo ' selected="selected"';
} ?>><?php _e('UTC+11:30') ?></option>
							<option value="12"<?php if ($user['timezone'] == 12) {
    echo ' selected="selected"';
} ?>><?php _e('UTC+12:00') ?></option>
							<option value="12.75"<?php if ($user['timezone'] == 12.75) {
    echo ' selected="selected"';
} ?>><?php _e('UTC+12:45') ?></option>
							<option value="13"<?php if ($user['timezone'] == 13) {
    echo ' selected="selected"';
} ?>><?php _e('UTC+13:00') ?></option>
							<option value="14"<?php if ($user['timezone'] == 14) {
    echo ' selected="selected"';
} ?>><?php _e('UTC+14:00') ?></option>
						</select>
						<br /></label>
						<div class="rbox">
							<label><input type="checkbox" name="form_dst" value="1"<?php if ($user['dst'] == '1') {
    echo ' checked="checked"';
} ?> /><?php _e('DST') ?><br /></label>
						</div>
						<label><?php _e('Time format') ?>

						<br /><select name="form_time_format">
<?php
                            foreach (array_unique($forum_time_formats) as $key => $time_format) {
                                echo "\t\t\t\t\t\t\t\t".'<option value="'.$key.'"';
                                if ($user['time_format'] == $key) {
                                    echo ' selected="selected"';
                                }
                                echo '>'. format_time(time(), false, null, $time_format, true, true);
                                if ($key == 0) {
                                    echo ' ('.__('Default').')';
                                }
                                echo "</option>\n";
                            }
                            ?>
						</select>
						<br /></label>
						<label><?php _e('Date format') ?>

						<br /><select name="form_date_format">
<?php
                            foreach (array_unique($forum_date_formats) as $key => $date_format) {
                                echo "\t\t\t\t\t\t\t\t".'<option value="'.$key.'"';
                                if ($user['date_format'] == $key) {
                                    echo ' selected="selected"';
                                }
                                echo '>'. format_time(time(), true, $date_format, null, false, true);
                                if ($key == 0) {
                                    echo ' ('.__('Default').')';
                                }
                                echo "</option>\n";
                            }
                            ?>
						</select>
						<br /></label>

<?php

    $languages = forum_list_langs();

    // Only display the language selection box if there's more than one language available
    if (count($languages) > 1) {
        ?>
						<label><?php _e('Language') ?>
						<br /><select name="form_language">
<?php

        foreach ($languages as $temp) {
            if ($user['language'] == $temp) {
                echo "\t\t\t\t\t\t\t\t".'<option value="'.$temp.'" selected="selected">'.$temp.'</option>'."\n";
            } else {
                echo "\t\t\t\t\t\t\t\t".'<option value="'.$temp.'">'.$temp.'</option>'."\n";
            }
        }

        ?>
						</select>
						<br /></label>
<?php

    }

?>
					</div>
				</fieldset>
			</div>
			<div class="inform">
				<fieldset>
					<legend><?php _e('User activity') ?></legend>
					<div class="infldset">
						<p><?php printf(__('Registered info'), format_time($user['registered'], true).(($feather->user->is_admmod) ? ' (<a href="'.$feather->url->get('admin/users/show-users/ip/'.$user['registration_ip'].'/').'">'.feather_escape($user['registration_ip']).'</a>)' : '')) ?></p>
						<p><?php printf(__('Last post info'), format_time($user['last_post'])) ?></p>
						<p><?php printf(__('Last visit info'), format_time($user['last_visit'])) ?></p>
						<?php echo $user_disp['posts_field'] ?>
<?php if ($feather->user->is_admmod): ?>							<label><?php _e('Admin note') ?><br />
						<input id="admin_note" type="text" name="admin_note" value="<?php echo feather_escape($user['admin_note']) ?>" size="30" maxlength="30" /><br /></label>
<?php endif; ?>						</div>
				</fieldset>
			</div>
			<p class="buttons"><input type="submit" name="update" value="<?php _e('Submit') ?>" /> <?php _e('Instructions') ?></p>
		</form>
	</div>
</div>
	<div class="clearer"></div>
</div>