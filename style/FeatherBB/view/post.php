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

<div class="linkst">
	<div class="inbox">
		<ul class="crumbs">
			<li><a href="<?php echo get_base_url() ?>"><?php echo $lang_common['Index'] ?></a></li>
			<li><span>»&#160;</span><a href="<?php echo get_link('forum/'.$cur_posting['id'].'/'.$url_forum.'/') ?>"><?php echo feather_escape($cur_posting['forum_name']) ?></a></li>
<?php if ($feather->request->post('req_subject')): ?>			<li><span>»&#160;</span><?php echo feather_escape($feather->request->post('req_subject')) ?></li>
<?php endif; ?>
<?php if (isset($cur_posting['subject'])): ?>			<li><span>»&#160;</span><a href="<?php echo get_link('topic/'.$tid.'/'.$url_topic.'/') ?>"><?php echo feather_escape($cur_posting['subject']) ?></a></li>
<?php endif; ?>			<li><span>»&#160;</span><strong><?php echo $action ?></strong></li>
		</ul>
	</div>
</div>

<?php

// If there are errors, we display them
if (!empty($errors)) {
    ?>
<div id="posterror" class="block">
	<h2><span><?php echo $lang_post['Post errors'] ?></span></h2>
	<div class="box">
		<div class="inbox error-info">
			<p><?php echo $lang_post['Post errors info'] ?></p>
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

} elseif ($feather->request->post('preview')) {
    require_once FEATHER_ROOT.'include/parser.php';
    $preview_message = parse_message($post['message'], $post['hide_smilies']);

    ?>
<div id="postpreview" class="blockpost">
	<h2><span><?php echo $lang_post['Post preview'] ?></span></h2>
	<div class="box">
		<div class="inbox">
			<div class="postbody">
				<div class="postright">
					<div class="postmsg">
						<?php echo $preview_message."\n" ?>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<?php

}


$cur_index = 1;

?>
<!-- Init BBcode editor toolbar -->
<script>
    var baseUrl = '<?php echo feather_escape(get_base_url(true)); ?>',
        langBbeditor = JSON.parse('<?= json_encode($lang_bbeditor); ?>');
</script>
<script src="<?php echo get_base_url() ?>/js/bbeditor.js"></script>

<div id="postform" class="blockform">
	<h2><span><?php echo $action ?></span></h2>
	<div class="box">
		<?php echo $form."\n" ?>
			<div class="inform">
				<fieldset>
					<legend><?php echo $lang_common['Write message legend'] ?></legend>
					<div class="infldset txtarea">
						<input type="hidden" name="form_sent" value="1" />
                                                <input type="hidden" name="<?php echo $csrf_key; ?>" value="<?php echo $csrf_token; ?>">
<?php
if ($feather->user->is_guest) {
    $email_label = ($feather_config['p_force_guest_email'] == '1') ? '<strong>'.$lang_common['Email'].' <span>'.$lang_common['Required'].'</span></strong>' : $lang_common['Email'];
    $email_form_name = ($feather_config['p_force_guest_email'] == '1') ? 'req_email' : 'email';
    ?>
						<label class="conl required"><strong><?php echo $lang_post['Guest name'] ?> <span><?php echo $lang_common['Required'] ?></span></strong><br /><input type="text" name="req_username" value="<?php if ($feather->request->post('req_username')) {
    echo feather_escape($post['username']);
}
    ?>" size="25" maxlength="25" tabindex="<?php echo $cur_index++ ?>" /><br /></label>
						<label class="conl<?php echo($feather_config['p_force_guest_email'] == '1') ? ' required' : '' ?>"><?php echo $email_label ?><br /><input type="text" name="<?php echo $email_form_name ?>" value="<?php if ($feather->request->post($email_form_name)) {
    echo feather_escape($post['email']);
}
    ?>" size="50" maxlength="80" tabindex="<?php echo $cur_index++ ?>" /><br /></label>
						<div class="clearer"></div>
<?php

}
if ($fid): ?>
						<label class="required"><strong><?php echo $lang_common['Subject'] ?> <span><?php echo $lang_common['Required'] ?></span></strong><br /><input class="longinput" type="text" name="req_subject" value="<?php if ($feather->request->post('req_subject')) {
    echo feather_escape($post['subject']);
} ?>" size="80" maxlength="70" tabindex="<?php echo $cur_index++ ?>" /><br /></label>
<?php endif; ?>						<label class="required"><strong><?php echo $lang_common['Message'] ?> <span><?php echo $lang_common['Required'] ?></span></strong><br />
                        <script>postEditorToolbar('req_message');</script>
						<textarea name="req_message" id="req_message" rows="20" cols="95" tabindex="<?php echo $cur_index++ ?>"><?php echo($feather->request->post('req_message')) ? feather_linebreaks(feather_trim(feather_escape($feather->request->post('req_message')))) : (isset($quote) ? $quote : ''); ?></textarea><br /></label>
						<ul class="bblinks">
							<li><span><a href="<?php echo get_link('help/#bbcode') ?>" onclick="window.open(this.href); return false;"><?php echo $lang_common['BBCode'] ?></a> <?php echo($feather_config['p_message_bbcode'] == '1') ? $lang_common['on'] : $lang_common['off']; ?></span></li>
							<li><span><a href="<?php echo get_link('help/#url') ?>" onclick="window.open (this.href); return false;"><?php echo $lang_common['url tag'] ?></a> <?php echo($feather_config['p_message_bbcode'] == '1' && $feather->user->g_post_links == '1') ? $lang_common['on'] : $lang_common['off']; ?></span></li>
							<li><span><a href="<?php echo get_link('help/#img') ?>" onclick="window.open(this.href); return false;"><?php echo $lang_common['img tag'] ?></a> <?php echo($feather_config['p_message_bbcode'] == '1' && $feather_config['p_message_img_tag'] == '1') ? $lang_common['on'] : $lang_common['off']; ?></span></li>
							<li><span><a href="<?php echo get_link('help/#smilies') ?>" onclick="window.open(this.href); return false;"><?php echo $lang_common['Smilies'] ?></a> <?php echo($feather_config['o_smilies'] == '1') ? $lang_common['on'] : $lang_common['off']; ?></span></li>
						</ul>
					</div>
				</fieldset>
<?php
if (!empty($checkboxes)) {
    ?>
			</div>
			<div class="inform">
				<fieldset>
					<legend><?php echo $lang_common['Options'] ?></legend>
					<div class="infldset">
						<div class="rbox">
							<?php echo implode("\n\t\t\t\t\t\t\t", $checkboxes)."\n" ?>
						</div>
					</div>
				</fieldset>
<?php

}

?>
			</div>
			<?php if ($feather->user->is_guest) : ?>
			<div class="inform">
				<fieldset>
					<legend><?php echo $lang_antispam['Robot title'] ?></legend>
					<div class="infldset">
						<p><?php echo $lang_antispam['Robot info']    ?></p>
						<label class="required"><strong><?php
                             $question = array_keys($lang_antispam_questions);
                             $qencoded = md5($question[$index_questions]);
                             echo sprintf($lang_antispam['Robot question'], $question[$index_questions]);?>
							 <span><?php echo $lang_common['Required'] ?></span></strong>
							 <br />
							 <input	name="captcha" id="captcha"	type="text"	size="10" maxlength="30" /><input name="captcha_q" value="<?php echo $qencoded ?>" type="hidden" /><br />
						</label>
					</div>
				</fieldset>
			</div>
			<?php endif; ?>
			<p class="buttons"><input type="submit" name="submit" value="<?php echo $lang_common['Submit'] ?>" tabindex="<?php echo $cur_index++ ?>" accesskey="s" /> <input type="submit" name="preview" value="<?php echo $lang_post['Preview'] ?>" tabindex="<?php echo $cur_index++ ?>" accesskey="p" /> <a href="javascript:history.go(-1)"><?php echo $lang_common['Go back'] ?></a></p>
		</form>
	</div>
</div>


<?php
if ($tid && $feather_config['o_topic_review'] != '0') :
?>
<div id="postreview">
	<h2><span><?php echo $lang_post['Topic review'] ?></span></h2>

	<?php
    // Set background switching on
    $post_count = 0;

    foreach ($post_data as $post) {
        ++$post_count;
        ?>
	<div class="blockpost">
	<div class="box<?php echo($post_count % 2 == 0) ? ' roweven' : ' rowodd' ?>">
		<div class="inbox">
			<div class="postbody">
				<div class="postleft">
					<dl>
						<dt><strong><?php echo feather_escape($post['poster']) ?></strong></dt>
						<dd><span><?php echo format_time($post['posted']) ?></span></dd>
					</dl>
				</div>
				<div class="postright">
					<div class="postmsg">
						<?php echo $post['message']."\n" ?>
					</div>
				</div>
			</div>
			<div class="clearer"></div>
		</div>
	</div>
</div>
	<?php

    }
    ?>

</div>
<?php endif;?>
