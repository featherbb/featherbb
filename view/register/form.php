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

// If there are errors, we display them
if (!empty($errors)) {
    ?>
<div id="posterror" class="block">
	<h2><span><?php echo $lang_register['Registration errors'] ?></span></h2>
	<div class="box">
		<div class="inbox error-info">
			<p><?php echo $lang_register['Registration errors info'] ?></p>
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
	<h2><span><?php echo $lang_register['Register'] ?></span></h2>
	<div class="box">
		<form id="register" method="post" action="" onsubmit="this.register.disabled=true;if(process_form(this)){return true;}else{this.register.disabled=false;return false;}">
		<input type="hidden" name="<?php echo $csrf_key; ?>" value="<?php echo $csrf_token; ?>">
			<div class="inform">
				<div class="forminfo">
					<h3><?php echo $lang_common['Important information'] ?></h3>
					<p><?php echo $lang_register['Desc 1'] ?></p>
					<p><?php echo $lang_register['Desc 2'] ?></p>
				</div>
				<fieldset>
					<legend><?php echo $lang_register['Username legend'] ?></legend>
					<div class="infldset">
						<input type="hidden" name="form_sent" value="1" />
						<input type="hidden" name="username" value="" />
						<input type="hidden" name="password" value="" />
						<label class="required"><strong><?php echo $lang_common['Username'] ?> <span><?php echo $lang_common['Required'] ?></span></strong><br /><input type="text" name="req_user" value="<?php if ($feather->request->post('req_user')) {
    echo feather_escape($feather->request->post('req_user'));
} ?>" size="25" maxlength="25" /><br /></label>
					</div>
				</fieldset>
			</div>
<?php if ($feather_config['o_regs_verify'] == '0'): ?>			<div class="inform">
				<fieldset>
					<legend><?php echo $lang_register['Pass legend'] ?></legend>
					<div class="infldset">
						<label class="conl required"><strong><?php echo $lang_common['Password'] ?> <span><?php echo $lang_common['Required'] ?></span></strong><br /><input type="password" name="req_password1" value="<?php if ($feather->request->post('req_password1')) {
    echo feather_escape($feather->request->post('req_password1'));
} ?>" size="16" /><br /></label>
						<label class="conl required"><strong><?php echo $lang_prof_reg['Confirm pass'] ?> <span><?php echo $lang_common['Required'] ?></span></strong><br /><input type="password" name="req_password2" value="<?php if ($feather->request->post('req_password2')) {
    echo feather_escape($feather->request->post('req_password2'));
} ?>" size="16" /><br /></label>
						<p class="clearb"><?php echo $lang_register['Pass info'] ?></p>
					</div>
				</fieldset>
			</div>
<?php endif; ?>			<div class="inform">
				<fieldset>
					<legend><?php echo($feather_config['o_regs_verify'] == '1') ? $lang_prof_reg['Email legend 2'] : $lang_prof_reg['Email legend'] ?></legend>
					<div class="infldset">
<?php if ($feather_config['o_regs_verify'] == '1'): ?>						<p><?php echo $lang_register['Email info'] ?></p>
<?php endif; ?>						<label class="required"><strong><?php echo $lang_common['Email'] ?> <span><?php echo $lang_common['Required'] ?></span></strong><br />
						<input type="text" name="req_email1" value="<?php if ($feather->request->post('req_email1')) {
    echo feather_escape($feather->request->post('req_email1'));
} ?>" size="50" maxlength="80" /><br /></label>
<?php if ($feather_config['o_regs_verify'] == '1'): ?>						<label class="required"><strong><?php echo $lang_register['Confirm email'] ?> <span><?php echo $lang_common['Required'] ?></span></strong><br />
						<input type="text" name="req_email2" value="<?php if ($feather->request->post('req_email2')) {
    echo feather_escape($feather->request->post('req_email2'));
} ?>" size="50" maxlength="80" /><br /></label>
<?php endif; ?>					</div>
				</fieldset>
			</div>
<?php
        // Only display the language selection box if there's more than one language available
        if (count($languages) > 1) {
            ?>
			<div class="inform">
				<fieldset>
					<legend><?php echo $lang_prof_reg['Localisation legend'] ?></legend>
					<div class="infldset">
							<label><?php echo $lang_prof_reg['Language'] ?>
							<br /><select name="language">
<?php

            foreach ($languages as $temp) {
                if ($feather_config['o_default_lang'] == $temp) {
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
					<legend><?php echo $lang_antispam['Robot title'] ?></legend>
					<div class="infldset">
						<p><?php echo $lang_antispam['Robot info']    ?></p>
						<label class="required"><strong><?php echo sprintf($lang_antispam['Robot question'], $question[$index_questions]) ?> <span><?php echo $lang_common['Required'] ?></span></strong><br /><input name="captcha" id="captcha" type="text" size="10" maxlength="30" /><input name="captcha_q" value="<?php echo $qencoded ?>" type="hidden" /></label>
					</div>
				</fieldset>
			</div>
			<p class="buttons"><input type="submit" name="register" value="<?php echo $lang_register['Register'] ?>" /></p>
		</form>
	</div>
</div>