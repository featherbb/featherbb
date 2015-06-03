<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */
 
function get_info_delete($id)
{
	global $db, $lang_common, $pun_user;
	
	$result = $db->query('SELECT f.id AS fid, f.forum_name, f.moderators, f.redirect_url, fp.post_replies, fp.post_topics, t.id AS tid, t.subject, t.first_post_id, t.closed, p.posted, p.poster, p.poster_id, p.message, p.hide_smilies FROM '.$db->prefix.'posts AS p INNER JOIN '.$db->prefix.'topics AS t ON t.id=p.topic_id INNER JOIN '.$db->prefix.'forums AS f ON f.id=t.forum_id LEFT JOIN '.$db->prefix.'forum_perms AS fp ON (fp.forum_id=f.id AND fp.group_id='.$pun_user['g_id'].') WHERE (fp.read_forum IS NULL OR fp.read_forum=1) AND p.id='.$id) or error('Unable to fetch post info', __FILE__, __LINE__, $db->error());
	if (!$db->num_rows($result))
		message($lang_common['Bad request'], false, '404 Not Found');

	$cur_post = $db->fetch_assoc($result);
	
	return $cur_post;
}

function handle_deletion($is_topic_post, $id, $tid, $fid)
{
	global $db, $lang_delete;
	
	// Make sure they got here from the site
	confirm_referrer('delete.php');

	require PUN_ROOT.'include/search_idx.php';

	if ($is_topic_post)
	{
		// Delete the topic and all of its posts
		delete_topic($tid);
		update_forum($fid);

		redirect('viewforum.php?id='.$fid, $lang_delete['Topic del redirect']);
	}
	else
	{
		// Delete just this one post
		delete_post($id, $tid);
		update_forum($fid);

		// Redirect towards the previous post
		$result = $db->query('SELECT id FROM '.$db->prefix.'posts WHERE topic_id='.$tid.' AND id < '.$id.' ORDER BY id DESC LIMIT 1') or error('Unable to fetch post info', __FILE__, __LINE__, $db->error());
		$post_id = $db->result($result);

		redirect('viewtopic.php?pid='.$post_id.'#p'.$post_id, $lang_delete['Post del redirect']);
	}
}