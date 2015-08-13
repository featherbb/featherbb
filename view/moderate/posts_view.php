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
			<li><a href="<?php echo get_base_url() ?>"><?php echo $lang_common['Index'] ?></a></li>
			<li><span>»&#160;</span><a href="<?php echo get_link('forum/'.$fid.'/'.$url_forum.'/') ?>"><?php echo feather_escape($cur_topic['forum_name']) ?></a></li>
			<li><span>»&#160;</span><a href="<?php echo get_link('topic/'.$id.'/'.$url_topic.'/') ?>"><?php echo feather_escape($cur_topic['subject']) ?></a></li>
			<li><span>»&#160;</span><strong><?php echo $lang_misc['Moderate'] ?></strong></li>
		</ul>
		<div class="pagepost">
			<p class="pagelink conl"><?php echo $paging_links ?></p>
		</div>
		<div class="clearer"></div>
	</div>
</div>

<form method="post" action="">
<input type="hidden" name="<?php echo $csrf_key; ?>" value="<?php echo $csrf_token; ?>">
<?php
$post_count = 0; // Keep track of post numbers
foreach ($post_data as $post) {
    $post_count++;
    ?>
	<div id="p<?php echo $post['id'] ?>" class="blockpost<?php if ($post['id'] == $cur_topic['first_post_id']) {
    echo ' firstpost';
}
    ?><?php echo($post_count % 2 == 0) ? ' roweven' : ' rowodd' ?><?php if ($post_count == 1) {
    echo ' blockpost1';
}
    ?>">
		<h2><span><span class="conr">#<?php echo($start_from + $post_count) ?></span> <a href="<?php echo get_link('post/'.$post['id'].'/#p'.$post['id']) ?>"><?php echo format_time($post['posted']) ?></a></span></h2>
		<div class="box">
			<div class="inbox">
				<div class="postbody">
					<div class="postleft">
						<dl>
							<dt><strong><?php echo $post['poster_disp'] ?></strong></dt>
							<dd class="usertitle"><strong><?php echo $post['user_title'] ?></strong></dd>
						</dl>
					</div>
					<div class="postright">
						<h3 class="nosize"><?php echo $lang_common['Message'] ?></h3>
						<div class="postmsg">
							<?php echo $post['message']."\n" ?>
	<?php if ($post['edited'] != '') {
    echo "\t\t\t\t\t\t".'<p class="postedit"><em>'.$lang_topic['Last edit'].' '.feather_escape($post['edited_by']).' ('.format_time($post['edited']).')</em></p>'."\n";
}
    ?>
						</div>
					</div>
				</div>
			</div>
			<div class="inbox">
				<div class="postfoot clearb">
					<div class="postfootright"><?php echo($post['id'] != $cur_topic['first_post_id']) ? '<p class="multidelete"><label><strong>'.$lang_misc['Select'].'</strong>&#160;<input type="checkbox" name="posts['.$post['id'].']" value="1" /></label></p>' : '<p>'.$lang_misc['Cannot select first'].'</p>' ?></div>
				</div>
			</div>
		</div>
	</div>
<?php

}
?>

<div class="postlinksb">
	<div class="inbox crumbsplus">
		<div class="pagepost">
			<p class="pagelink conl"><?php echo $paging_links ?></p>
			<p class="conr modbuttons"><input type="submit" name="split_posts" value="<?php echo $lang_misc['Split'] ?>"<?php echo $button_status ?> /> <input type="submit" name="delete_posts" value="<?php echo $lang_misc['Delete'] ?>"<?php echo $button_status ?> /></p>
			<div class="clearer"></div>
		</div>
		<ul class="crumbs">
			<li><a href="<?php echo get_base_url() ?>"><?php echo $lang_common['Index'] ?></a></li>
			<li><span>»&#160;</span><a href="<?php echo get_link('forum/'.$fid.'/'.$url_forum.'/') ?>"><?php echo feather_escape($cur_topic['forum_name']) ?></a></li>
			<li><span>»&#160;</span><a href="<?php echo get_link('topic/'.$id.'/'.$url_topic.'/') ?>"><?php echo feather_escape($cur_topic['subject']) ?></a></li>
			<li><span>»&#160;</span><strong><?php echo $lang_misc['Moderate'] ?></strong></li>
		</ul>
		<div class="clearer"></div>
	</div>
</div>
</form>