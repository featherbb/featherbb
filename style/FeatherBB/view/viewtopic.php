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
	<div class="inbox crumbsplus">
		<ul class="crumbs">
			<li><a href="<?php echo get_base_url() ?>/"><?php echo $lang_common['Index'] ?></a></li>
			<li><span>»&#160;</span><a href="<?php echo get_link('forum/'.$cur_topic['forum_id'].'/'.$url_forum.'/') ?>"><?php echo feather_escape($cur_topic['forum_name']) ?></a></li>
			<li><span>»&#160;</span><strong><a href="<?php echo get_link('topic/'.$id.'/'.$url_topic.'/') ?>"><?php echo feather_escape($cur_topic['subject']) ?></a></strong></li>
		</ul>
		<div class="pagepost">
			<p class="pagelink conl"><?php echo $paging_links ?></p>
<?php echo $post_link ?>
		</div>
		<div class="clearer"></div>
	</div>
</div>

<?php
$post_count = 1;
foreach ($post_data as $post) {
    ?>
<div id="p<?php echo $post['id'] ?>" class="blockpost<?php echo($post_count % 2 == 0) ? ' roweven' : ' rowodd' ?><?php if ($post['id'] == $cur_topic['first_post_id']) {
    echo ' firstpost';
}
    ?><?php if ($post_count == 1) {
    echo ' blockpost1';
}
    ?>">
	<h2><span><span class="conr">#<?php echo($start_from + $post_count) ?></span> <a href="<?php echo get_link('post/'.$post['id'].'/#p'.$post['id']) ?>"><?php echo format_time($post['posted']) ?></a></span></h2>
	<div class="box">
		<div class="inbox">
			<div class="postbody">
				<div class="postleft">
					<dl>
						<dt><strong><?php echo $post['username_formatted'] ?></strong></dt>
						<dd class="usertitle"><strong><?php echo $post['user_title_formatted'] ?></strong></dd>
<?php if ($post['user_avatar'] != '') {
    echo "\t\t\t\t\t\t".'<dd class="postavatar">'.$post['user_avatar'].'</dd>'."\n";
}
    ?>
<?php if (count($post['user_info'])) {
    echo "\t\t\t\t\t\t".implode("\n\t\t\t\t\t\t", $post['user_info'])."\n";
}
    ?>
<?php if (count($post['user_contacts'])) {
    echo "\t\t\t\t\t\t".'<dd class="usercontacts">'.implode(' ', $post['user_contacts']).'</dd>'."\n";
}
    ?>
					</dl>
				</div>
				<div class="postright">
					<h3><?php if ($post['id'] != $cur_topic['first_post_id']) {
    echo $lang_topic['Re'].' ';
}
    ?><?php echo feather_escape($cur_topic['subject']) ?></h3>
					<div class="postmsg">
						<?php echo $post['message']."\n" ?>
<?php if ($post['edited'] != '') {
    echo "\t\t\t\t\t\t".'<p class="postedit"><em>'.$lang_topic['Last edit'].' '.feather_escape($post['edited_by']).' ('.format_time($post['edited']).')</em></p>'."\n";
}
    ?>
					</div>
<?php if ($post['signature_formatted'] != '') {
    echo "\t\t\t\t\t".'<div class="postsignature postmsg"><hr />'.$post['signature_formatted'].'</div>'."\n";
}
    ?>
				</div>
			</div>
		</div>
		<div class="inbox">
			<div class="postfoot clearb">
				<div class="postfootleft"><?php if ($post['poster_id'] > 1) {
    echo '<p>'.$post['is_online_formatted'].'</p>';
}
    ?></div>
<?php if (count($post['post_actions'])) {
    echo "\t\t\t\t".'<div class="postfootright">'."\n\t\t\t\t\t".'<ul>'."\n\t\t\t\t\t\t".implode("\n\t\t\t\t\t\t", $post['post_actions'])."\n\t\t\t\t\t".'</ul>'."\n\t\t\t\t".'</div>'."\n";
}
    ?>
			</div>
		</div>
	</div>
</div>
<?php
    ++$post_count;
}
?>

<div class="postlinksb">
	<div class="inbox crumbsplus">
		<div class="pagepost">
			<p class="pagelink conl"><?php echo $paging_links ?></p>
<?php echo $post_link ?>
		</div>
		<ul class="crumbs">
			<li><a href="<?php echo get_base_url() ?>/"><?php echo $lang_common['Index'] ?></a></li>
			<li><span>»&#160;</span><a href="<?php echo get_link('forum/'.$cur_topic['forum_id'].'/'.$url_forum.'/') ?>"><?php echo feather_escape($cur_topic['forum_name']) ?></a></li>
			<li><span>»&#160;</span><strong><a href="<?php echo get_link('topic/'.$id.'/'.$url_topic.'/') ?>"><?php echo feather_escape($cur_topic['subject']) ?></a></strong></li>
		</ul>
<?php echo $subscraction ?>
		<div class="clearer"></div>
	</div>
</div>

<?php

// Display quick post if enabled
if ($quickpost) {
    $cur_index = 1;

    ?>
<div id="quickpost" class="blockform">
	<h2><span><?php echo $lang_topic['Quick post'] ?></span></h2>
	<div class="box">
		<form id="quickpostform" method="post" action="<?php echo get_link('post/reply/'.$id.'/') ?>" onsubmit="this.submit.disabled=true;if(process_form(this)){return true;}else{this.submit.disabled=false;return false;}">
			<input type="hidden" name="<?php echo $csrf_key; ?>" value="<?php echo $csrf_token; ?>">
			<div class="inform">
				<fieldset>
					<legend><?php echo $lang_common['Write message legend'] ?></legend>
					<div class="infldset txtarea">
						<input type="hidden" name="form_sent" value="1" />
						<input type="hidden" name="pid" value="<?php echo feather_escape($pid) ?>" />
						<input type="hidden" name="page" value="<?php echo feather_escape($p) ?>" />
<?php if ($feather_config['o_topic_subscriptions'] == '1' && ($feather->user->auto_notify == '1' || $cur_topic['is_subscribed'])): ?>						<input type="hidden" name="subscribe" value="1" />
<?php endif;

    if ($feather->user->is_guest) {
        $email_label = ($feather_config['p_force_guest_email'] == '1') ? '<strong>'.$lang_common['Email'].' <span>'.$lang_common['Required'].'</span></strong>' : $lang_common['Email'];
        $email_form_name = ($feather_config['p_force_guest_email'] == '1') ? 'req_email' : 'email';
        ?>
						<label class="conl required"><strong><?php echo $lang_post['Guest name'] ?> <span><?php echo $lang_common['Required'] ?></span></strong><br /><input type="text" name="req_username" size="25" maxlength="25" tabindex="<?php echo $cur_index++ ?>" /><br /></label>
						<label class="conl<?php echo($feather_config['p_force_guest_email'] == '1') ? ' required' : '' ?>"><?php echo $email_label ?><br /><input type="text" name="<?php echo $email_form_name ?>" size="50" maxlength="80" tabindex="<?php echo $cur_index++ ?>" /><br /></label>
						<div class="clearer"></div>
<?php

    echo "\t\t\t\t\t\t".'<label class="required"><strong>'.$lang_common['Message'].' <span>'.$lang_common['Required'].'</span></strong><br />';
    } else {
        echo "\t\t\t\t\t\t".'<label>';
    }

    ?>
<!-- Init BBcode editor toolbar -->
<script>
    var baseUrl = '<?php echo feather_escape(get_base_url(true)); ?>',
        langBbeditor = <?= json_encode($lang_bbeditor, JSON_PRETTY_PRINT); ?>;
</script>
<script src="<?php echo get_base_url() ?>/js/bbeditor.js"></script>
<script>postEditorToolbar('req_message');</script>

<textarea name="req_message" id="req_message" rows="7" cols="75" tabindex="<?php echo $cur_index++ ?>"></textarea></label>
						<ul class="bblinks">
							<li><span><a href="<?php echo get_link('help/#bbcode') ?>" onclick="window.open(this.href); return false;"><?php echo $lang_common['BBCode'] ?></a> <?php echo($feather_config['p_message_bbcode'] == '1') ? $lang_common['on'] : $lang_common['off'];
    ?></span></li>
							<li><span><a href="<?php echo get_link('help/#url') ?>" onclick="window.open(this.href); return false;"><?php echo $lang_common['url tag'] ?></a> <?php echo($feather_config['p_message_bbcode'] == '1' && $feather->user->g_post_links == '1') ? $lang_common['on'] : $lang_common['off'];
    ?></span></li>
							<li><span><a href="<?php echo get_link('help/#img') ?>" onclick="window.open(this.href); return false;"><?php echo $lang_common['img tag'] ?></a> <?php echo($feather_config['p_message_bbcode'] == '1' && $feather_config['p_message_img_tag'] == '1') ? $lang_common['on'] : $lang_common['off'];
    ?></span></li>
							<li><span><a href="<?php echo get_link('help/#smilies') ?>" onclick="window.open(this.href); return false;"><?php echo $lang_common['Smilies'] ?></a> <?php echo($feather_config['o_smilies'] == '1') ? $lang_common['on'] : $lang_common['off'];
    ?></span></li>
						</ul>
					</div>
				</fieldset>
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
    echo sprintf($lang_antispam['Robot question'], $question[$index_questions]);
    ?>
							 <span><?php echo $lang_common['Required'] ?></span></strong>
							 <br />
							 <input	name="captcha" id="captcha"	type="text"	size="10" maxlength="30" /><input name="captcha_q" value="<?php echo $qencoded ?>" type="hidden" /><br />
						</label>
					</div>
				</fieldset>
			</div>
			<?php endif;
    ?>
			<p class="buttons"><input type="submit" name="submit" tabindex="<?php echo $cur_index++ ?>" value="<?php echo $lang_common['Submit'] ?>" accesskey="s" /> <input type="submit" name="preview" value="<?php echo $lang_topic['Preview'] ?>" tabindex="<?php echo $cur_index++ ?>" accesskey="p" /></p>
		</form>
	</div>
</div>
<?php

}
