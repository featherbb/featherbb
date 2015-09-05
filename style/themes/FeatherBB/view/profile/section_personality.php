<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */
 
use FeatherBB\Utils;

// Make sure no one attempts to run this script "directly"
if (!defined('FEATHER')) {
    exit;
}

?>
<div class="blockform">
	<h2><span><?php echo Utils::escape($user['username']).' - '.__('Section personality') ?></span></h2>
	<div class="box">
		<form id="profile4" method="post" action="<?php echo $feather->url->get('user/'.$id.'/section/personality/') ?>">
			<input type="hidden" name="<?php echo $csrf_key; ?>" value="<?php echo $csrf_token; ?>">
			<div><input type="hidden" name="form_sent" value="1" /></div>
<?php if ($feather->forum_settings['o_avatars'] == '1'): ?>				<div class="inform">
				<fieldset id="profileavatar">
					<legend><?php _e('Avatar legend') ?></legend>
					<div class="infldset">
<?php if ($user_avatar): ?>							<div class="useravatar"><?php echo $user_avatar ?></div>
<?php endif; ?>							<p><?php _e('Avatar info') ?></p>
						<p class="clearb actions"><?php echo $avatar_field ?></p>
					</div>
				</fieldset>
			</div>
<?php endif; if ($feather->forum_settings['o_signatures'] == '1'): ?>				<div class="inform">
				<fieldset>
					<legend><?php _e('Signature legend') ?></legend>
					<div class="infldset">
						<p><?php _e('Signature info') ?></p>
						<div class="txtarea">
							<label><?php printf(__('Sig max size'), Utils::forum_number_format($feather->forum_settings['p_sig_length']), $feather->forum_settings['p_sig_lines']) ?><br />
							<textarea name="signature" rows="4" cols="65"><?php echo Utils::escape($user['signature']) ?></textarea><br /></label>
						</div>
						<ul class="bblinks">
							<li><span><a href="<?php echo $feather->url->get('help/#bbcode') ?>" onclick="window.open(this.href); return false;"><?php _e('BBCode') ?></a> <?php echo($feather->forum_settings['p_sig_bbcode'] == '1') ? __('on') : __('off'); ?></span></li>
							<li><span><a href="<?php echo $feather->url->get('help/#url') ?>" onclick="window.open(this.href); return false;"><?php _e('url tag') ?></a> <?php echo($feather->forum_settings['p_sig_bbcode'] == '1' && $feather->user->g_post_links == '1') ? __('on') : __('off'); ?></span></li>
							<li><span><a href="<?php echo $feather->url->get('help/#img') ?>" onclick="window.open(this.href); return false;"><?php _e('img tag') ?></a> <?php echo($feather->forum_settings['p_sig_bbcode'] == '1' && $feather->forum_settings['p_sig_img_tag'] == '1') ? __('on') : __('off'); ?></span></li>
							<li><span><a href="<?php echo $feather->url->get('help/#smilies') ?>" onclick="window.open(this.href); return false;"><?php _e('Smilies') ?></a> <?php echo($feather->forum_settings['o_smilies_sig'] == '1') ? __('on') : __('off'); ?></span></li>
						</ul>
						<?php echo $signature_preview ?>
					</div>
				</fieldset>
			</div>
<?php endif; ?>				<p class="buttons"><input type="submit" name="update" value="<?php _e('Submit') ?>" /> <?php _e('Instructions') ?></p>
		</form>
	</div>
</div>
	<div class="clearer"></div>
</div>
