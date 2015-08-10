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
	<h2><span><?php echo feather_escape($user['username']).' - '.$lang_profile['Section personality'] ?></span></h2>
	<div class="box">
		<form id="profile4" method="post" action="<?php echo get_link('user/'.$id.'/section/personality/') ?>">
			<input type="hidden" name="<?php echo $csrf_key; ?>" value="<?php echo $csrf_token; ?>">
			<div><input type="hidden" name="form_sent" value="1" /></div>
<?php if ($feather_config['o_avatars'] == '1'): ?>				<div class="inform">
				<fieldset id="profileavatar">
					<legend><?php echo $lang_profile['Avatar legend'] ?></legend>
					<div class="infldset">
<?php if ($user_avatar): ?>							<div class="useravatar"><?php echo $user_avatar ?></div>
<?php endif; ?>							<p><?php echo $lang_profile['Avatar info'] ?></p>
						<p class="clearb actions"><?php echo $avatar_field ?></p>
					</div>
				</fieldset>
			</div>
<?php endif; if ($feather_config['o_signatures'] == '1'): ?>				<div class="inform">
				<fieldset>
					<legend><?php echo $lang_profile['Signature legend'] ?></legend>
					<div class="infldset">
						<p><?php echo $lang_profile['Signature info'] ?></p>
						<div class="txtarea">
							<label><?php printf($lang_profile['Sig max size'], forum_number_format($feather_config['p_sig_length']), $feather_config['p_sig_lines']) ?><br />
							<textarea name="signature" rows="4" cols="65"><?php echo feather_escape($user['signature']) ?></textarea><br /></label>
						</div>
						<ul class="bblinks">
							<li><span><a href="<?php echo get_link('help/#bbcode') ?>" onclick="window.open(this.href); return false;"><?php echo $lang_common['BBCode'] ?></a> <?php echo($feather_config['p_sig_bbcode'] == '1') ? $lang_common['on'] : $lang_common['off']; ?></span></li>
							<li><span><a href="<?php echo get_link('help/#url') ?>" onclick="window.open(this.href); return false;"><?php echo $lang_common['url tag'] ?></a> <?php echo($feather_config['p_sig_bbcode'] == '1' && $feather->user->g_post_links == '1') ? $lang_common['on'] : $lang_common['off']; ?></span></li>
							<li><span><a href="<?php echo get_link('help/#img') ?>" onclick="window.open(this.href); return false;"><?php echo $lang_common['img tag'] ?></a> <?php echo($feather_config['p_sig_bbcode'] == '1' && $feather_config['p_sig_img_tag'] == '1') ? $lang_common['on'] : $lang_common['off']; ?></span></li>
							<li><span><a href="<?php echo get_link('help/#smilies') ?>" onclick="window.open(this.href); return false;"><?php echo $lang_common['Smilies'] ?></a> <?php echo($feather_config['o_smilies_sig'] == '1') ? $lang_common['on'] : $lang_common['off']; ?></span></li>
						</ul>
						<?php echo $signature_preview ?>
					</div>
				</fieldset>
			</div>
<?php endif; ?>				<p class="buttons"><input type="submit" name="update" value="<?php echo $lang_common['Submit'] ?>" /> <?php echo $lang_profile['Instructions'] ?></p>
		</form>
	</div>
</div>
	<div class="clearer"></div>
</div>
