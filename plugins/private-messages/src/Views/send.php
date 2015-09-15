<?php

/**
* Copyright (C) 2015 FeatherBB
* based on code by (C) 2008-2015 FluxBB
* and Rickard Andersson (C) 2002-2008 PunBB
* License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher.
*/
use FeatherBB\Core\Url;
use FeatherBB\Core\Utils;

// Make sure no one attempts to run this script "directly"
if (!isset($feather)) {
    exit;
}

?>

<div class="linkst">
	<div class="inbox crumbsplus">
		<ul class="crumbs">
			<li><a href="#">Index</a></li>
			<li><span>»&#160;</span><a href="#">PMS</a></li>
			<li><span>»&#160;</span><strong>Send message</strong></li>
		</ul>
		<div class="pagepost"></div>
		<div class="clearer"></div>
	</div>
</div>
	<div id="postform" class="blockform">
		<h2><span>Send a message</span></h2>
		<div class="box">
			<form id="post" method="post" action="" onsubmit="return process_form(this)">
				<div class="inform">
					<fieldset>
						<legend>Compose your message</legend>
						<div class="infldset txtarea">
<? if ($csrf_key) echo "\t\t\t\t\t\t\t".'<input type="hidden" name="'.$csrf_key.'" value="'.$csrf_token.'"/>'."\n" ?>
                            <label class="required"><strong>Send to <span>{required}</span></strong><br /></label>
                            <input type="text" name="username" placeholder="Username" size="25" tabindex="1" required/><br />
                            <div class="clearer"></div>
                            <label class="required"><strong>Subject <span>{required}</span></strong><br /></label>
                            <input class="longinput" type="text" name="subject" placeholder="Subject" size="80" maxlength="70" tabindex="2" required/><br />
                            <label class="required"><strong><?php _e('Message') ?> <span><?php _e('Required') ?></span></strong><br /></label>
                            <textarea name="message" id="message" rows="20" cols="95" tabindex="2" required></textarea><br />
                            <ul class="bblinks">
    							<li><span><a href="<?= $feather->urlFor('help').'#bbcode' ?>" onclick="window.open(this.href); return false;"><?php _e('BBCode') ?>ok</a> <?php echo($feather->forum_settings['p_message_bbcode'] == '1') ? __('on') : __('off'); ?></span></li>
    							<li><span><a href="<?= $feather->urlFor('help').'#url' ?>" onclick="window.open (this.href); return false;"><?php _e('url tag') ?></a> <?php echo($feather->forum_settings['p_message_bbcode'] == '1' && $feather->user->g_post_links == '1') ? __('on') : __('off'); ?></span></li>
    							<li><span><a href="<?= $feather->urlFor('help').'#img' ?>" onclick="window.open(this.href); return false;"><?php _e('img tag') ?></a> <?php echo($feather->forum_settings['p_message_bbcode'] == '1' && $feather->forum_settings['p_message_img_tag'] == '1') ? __('on') : __('off'); ?></span></li>
    							<li><span><a href="<?= $feather->urlFor('help').'#smilies' ?>" onclick="window.open(this.href); return false;"><?php _e('Smilies') ?></a> <?php echo($feather->forum_settings['o_smilies'] == '1') ? __('on') : __('off'); ?></span></li>
    						</ul>
						</div>
					</fieldset>
				</div>
				<div class="inform">
					<fieldset>
						<legend><?php _e('Options') ?></legend>
						<div class="infldset">
							<div class="rbox">
								<label><input type="checkbox" name="smilies" value="1" tabindex="3" />{hide_smilies}<br /></label>
							</div>
						</div>
					</fieldset>
				</div>
                <p class="buttons"><input type="submit" name="submit" value="<?php _e('Submit') ?>" tabindex="4" accesskey="s" /> <input type="submit" name="preview" value="<?php _e('Preview') ?>" tabindex="5" accesskey="p" /> <a href="javascript:history.go(-1)"><?php _e('Go back') ?></a></p>
			</form>
		</div>
	</div>
</div>
