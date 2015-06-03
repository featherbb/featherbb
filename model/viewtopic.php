<?php

/**
 * Copyright (C) 2008-2012 FluxBB
 * based on code by Rickard Andersson copyright (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */
 
function redirect_to_post($post_id)
{
	global $db, $pun_user, $lang_common;
	
	$result = $db->query('SELECT topic_id, posted FROM '.$db->prefix.'posts WHERE id='.$post_id) or error('Unable to fetch topic ID', __FILE__, __LINE__, $db->error());
	if (!$db->num_rows($result))
		message($lang_common['Bad request'], false, '404 Not Found');

	list($post['topic_id'], $posted) = $db->fetch_row($result);

	// Determine on which page the post is located (depending on $forum_user['disp_posts'])
	$result = $db->query('SELECT COUNT(id) FROM '.$db->prefix.'posts WHERE topic_id='.$post['topic_id'].' AND posted<'.$posted) or error('Unable to count previous posts', __FILE__, __LINE__, $db->error());
	$num_posts = $db->result($result) + 1;
	
	$post['get_p'] = ceil($num_posts / $pun_user['disp_posts']);

	return $post;
}

function handle_actions($action, $topic_id)
{
	global $db, $pun_user;
	
	// If action=new, we redirect to the first new post (if any)
	if ($action == 'new')
	{
		if (!$pun_user['is_guest'])
		{
			// We need to check if this topic has been viewed recently by the user
			$tracked_topics = get_tracked_topics();
			$last_viewed = isset($tracked_topics['topics'][$topic_id]) ? $tracked_topics['topics'][$topic_id] : $pun_user['last_visit'];

			$result = $db->query('SELECT MIN(id) FROM '.$db->prefix.'posts WHERE topic_id='.$topic_id.' AND posted>'.$last_viewed) or error('Unable to fetch first new post info', __FILE__, __LINE__, $db->error());
			$first_new_post_id = $db->result($result);

			if ($first_new_post_id)
			{
				header('Location: viewtopic.php?pid='.$first_new_post_id.'#p'.$first_new_post_id);
				exit;
			}
		}

		// If there is no new post, we go to the last post
		$action = 'last';
	}

	// If action=last, we redirect to the last post
	if ($action == 'last')
	{
		$result = $db->query('SELECT MAX(id) FROM '.$db->prefix.'posts WHERE topic_id='.$topic_id) or error('Unable to fetch last post info', __FILE__, __LINE__, $db->error());
		$last_post_id = $db->result($result);

		if ($last_post_id)
		{
			header('Location: viewtopic.php?pid='.$last_post_id.'#p'.$last_post_id);
			exit;
		}
	}
}

function get_info_topic($id)
{
	global $db, $pun_user, $lang_common;
	
	if (!$pun_user['is_guest'])
		$result = $db->query('SELECT t.subject, t.closed, t.num_replies, t.sticky, t.first_post_id, f.id AS forum_id, f.forum_name, f.moderators, fp.post_replies, s.user_id AS is_subscribed FROM '.$db->prefix.'topics AS t INNER JOIN '.$db->prefix.'forums AS f ON f.id=t.forum_id LEFT JOIN '.$db->prefix.'topic_subscriptions AS s ON (t.id=s.topic_id AND s.user_id='.$pun_user['id'].') LEFT JOIN '.$db->prefix.'forum_perms AS fp ON (fp.forum_id=f.id AND fp.group_id='.$pun_user['g_id'].') WHERE (fp.read_forum IS NULL OR fp.read_forum=1) AND t.id='.$id.' AND t.moved_to IS NULL') or error('Unable to fetch topic info', __FILE__, __LINE__, $db->error());
	else
		$result = $db->query('SELECT t.subject, t.closed, t.num_replies, t.sticky, t.first_post_id, f.id AS forum_id, f.forum_name, f.moderators, fp.post_replies, 0 AS is_subscribed FROM '.$db->prefix.'topics AS t INNER JOIN '.$db->prefix.'forums AS f ON f.id=t.forum_id LEFT JOIN '.$db->prefix.'forum_perms AS fp ON (fp.forum_id=f.id AND fp.group_id='.$pun_user['g_id'].') WHERE (fp.read_forum IS NULL OR fp.read_forum=1) AND t.id='.$id.' AND t.moved_to IS NULL') or error('Unable to fetch topic info', __FILE__, __LINE__, $db->error());

	if (!$db->num_rows($result))
		message($lang_common['Bad request'], false, '404 Not Found');

	$cur_topic = $db->fetch_assoc($result);
	
	return $cur_topic;
}

function get_post_link($topic_id, $closed, $post_replies, $is_admmod)
{
	global $db, $pun_user, $lang_topic;
	
	if ($closed == '0')
	{
		if (($post_replies == '' && $pun_user['g_post_replies'] == '1') || $post_replies == '1' || $is_admmod)
			$post_link = "\t\t\t".'<p class="postlink conr"><a href="post.php?tid='.$id.'">'.$lang_topic['Post reply'].'</a></p>'."\n";
		else
			$post_link = '';
	}
	else
	{
		$post_link = $lang_topic['Topic closed'];

		if ($is_admmod)
			$post_link .= ' / <a href="post.php?tid='.$topic_id.'">'.$lang_topic['Post reply'].'</a>';

		$post_link = "\t\t\t".'<p class="postlink conr">'.$post_link.'</p>'."\n";
	}
	
	return $post_link;
}

function is_quickpost($post_replies, $closed, $is_admmod)
{
	global $pun_config, $pun_user;
	
	$quickpost = false;
	if ($pun_config['o_quickpost'] == '1' && ($post_replies == '1' || ($post_replies == '' && $pun_user['g_post_replies'] == '1')) && ($closed == '0' || $is_admmod))
	{
		// Load the post.php language file
		require PUN_ROOT.'lang/'.$pun_user['language'].'/post.php';

		$required_fields = array('req_message' => $lang_common['Message']);
		if ($pun_user['is_guest'])
		{
			$required_fields['req_username'] = $lang_post['Guest name'];
			if ($pun_config['p_force_guest_email'] == '1')
				$required_fields['req_email'] = $lang_common['Email'];
		}
		$quickpost = true;
	}
	
	return $quickpost;
}

function get_subscraction($is_subscribed, $topic_id)
{
	global $pun_user, $pun_config, $lang_topic;
	
	if (!$pun_user['is_guest'] && $pun_config['o_topic_subscriptions'] == '1')
	{
		if ($is_subscribed)
			// I apologize for the variable naming here. It's a mix of subscription and action I guess :-)
			$subscraction = "\t\t".'<p class="subscribelink clearb"><span>'.$lang_topic['Is subscribed'].' - </span><a href="misc.php?action=unsubscribe&amp;tid='.$topic_id.'">'.$lang_topic['Unsubscribe'].'</a></p>'."\n";
		else
			$subscraction = "\t\t".'<p class="subscribelink clearb"><a href="misc.php?action=subscribe&amp;tid='.$topic_id.'">'.$lang_topic['Subscribe'].'</a></p>'."\n";
	}
	else
		$subscraction = '';
	
	return $subscraction;
}

function get_page_head($topic_id, $num_pages, $p)
{
	global $pun_config, $lang_common;
	
	$page_head = array();
	$page_head['canonical'] = '<link rel="canonical" href="viewtopic.php?id='.$topic_id.($p == 1 ? '' : '&amp;p='.$p).'" title="'.sprintf($lang_common['Page'], $p).'" />';

	if ($num_pages > 1)
	{
		if ($p > 1)
			$page_head['prev'] = '<link rel="prev" href="viewtopic.php?id='.$topic_id.($p == 2 ? '' : '&amp;p='.($p - 1)).'" title="'.sprintf($lang_common['Page'], $p - 1).'" />';
		if ($p < $num_pages)
			$page_head['next'] = '<link rel="next" href="viewtopic.php?id='.$topic_id.'&amp;p='.($p + 1).'" title="'.sprintf($lang_common['Page'], $p + 1).'" />';
	}

	if ($pun_config['o_feed_type'] == '1')
		$page_head['feed'] = '<link rel="alternate" type="application/rss+xml" href="extern.php?action=feed&amp;tid='.$topic_id.'&amp;type=rss" title="'.$lang_common['RSS topic feed'].'" />';
	else if ($pun_config['o_feed_type'] == '2')
		$page_head['feed'] = '<link rel="alternate" type="application/atom+xml" href="extern.php?action=feed&amp;tid='.$topic_id.'&amp;type=atom" title="'.$lang_common['Atom topic feed'].'" />';
	
	return $page_head;
}

function print_posts($topic_id, $start_from, $cur_topic)
{
	global $db, $pun_user, $pun_config, $lang_topic;
	
	$post_data = array();
	
	$post_count = 0; // Keep track of post numbers

	// Retrieve a list of post IDs, LIMIT is (really) expensive so we only fetch the IDs here then later fetch the remaining data
	$result = $db->query('SELECT id FROM '.$db->prefix.'posts WHERE topic_id='.$topic_id.' ORDER BY id LIMIT '.$start_from.','.$pun_user['disp_posts']) or error('Unable to fetch post IDs', __FILE__, __LINE__, $db->error());

	$post_ids = array();
	for ($i = 0;$cur_post_id = $db->result($result, $i);$i++)
		$post_ids[] = $cur_post_id;

	if (empty($post_ids))
		error('The post table and topic table seem to be out of sync!', __FILE__, __LINE__);

	// Retrieve the posts (and their respective poster/online status)
	$result = $db->query('SELECT u.email, u.title, u.url, u.location, u.signature, u.email_setting, u.num_posts, u.registered, u.admin_note, p.id, p.poster AS username, p.poster_id, p.poster_ip, p.poster_email, p.message, p.hide_smilies, p.posted, p.edited, p.edited_by, g.g_id, g.g_user_title, g.g_promote_next_group, o.user_id AS is_online FROM '.$db->prefix.'posts AS p INNER JOIN '.$db->prefix.'users AS u ON u.id=p.poster_id INNER JOIN '.$db->prefix.'groups AS g ON g.g_id=u.group_id LEFT JOIN '.$db->prefix.'online AS o ON (o.user_id=u.id AND o.user_id!=1 AND o.idle=0) WHERE p.id IN ('.implode(',', $post_ids).') ORDER BY p.id', true) or error('Unable to fetch post info', __FILE__, __LINE__, $db->error());
	while ($cur_post = $db->fetch_assoc($result))
	{
		$post_count++;
		$cur_post['user_avatar'] = '';
		$cur_post['user_info'] = array();
		$cur_post['user_contacts'] = array();
		$cur_post['post_actions'] = array();
		$cur_post['is_online_formatted'] = '';
		$cur_post['signature_formatted'] = '';

		// If the poster is a registered user
		if ($cur_post['poster_id'] > 1)
		{
			if ($pun_user['g_view_users'] == '1')
				$cur_post['username_formatted'] = '<a href="profile.php?id='.$cur_post['poster_id'].'">'.pun_htmlspecialchars($cur_post['username']).'</a>';
			else
				$cur_post['username_formatted'] = pun_htmlspecialchars($cur_post['username']);

			$cur_post['user_title_formatted'] = get_title($cur_post);

			if ($pun_config['o_censoring'] == '1')
				$cur_post['user_title_formatted'] = censor_words($cur_post['user_title_formatted']);

			// Format the online indicator
			$cur_post['is_online_formatted'] = ($cur_post['is_online'] == $cur_post['poster_id']) ? '<strong>'.$lang_topic['Online'].'</strong>' : '<span>'.$lang_topic['Offline'].'</span>';

			if ($pun_config['o_avatars'] == '1' && $pun_user['show_avatars'] != '0')
			{
				if (isset($avatar_cache[$cur_post['poster_id']]))
					$cur_post['user_avatar'] = $avatar_cache[$cur_post['poster_id']];
				else
					$cur_post['user_avatar'] = $avatar_cache[$cur_post['poster_id']] = generate_avatar_markup($cur_post['poster_id']);
			}

			// We only show location, register date, post count and the contact links if "Show user info" is enabled
			if ($pun_config['o_show_user_info'] == '1')
			{
				if ($cur_post['location'] != '')
				{
					if ($pun_config['o_censoring'] == '1')
						$cur_post['location'] = censor_words($cur_post['location']);

					$cur_post['user_info'][] = '<dd><span>'.$lang_topic['From'].' '.pun_htmlspecialchars($cur_post['location']).'</span></dd>';
				}

				$cur_post['user_info'][] = '<dd><span>'.$lang_topic['Registered'].' '.format_time($cur_post['registered'], true).'</span></dd>';

				if ($pun_config['o_show_post_count'] == '1' || $pun_user['is_admmod'])
					$cur_post['user_info'][] = '<dd><span>'.$lang_topic['Posts'].' '.forum_number_format($cur_post['num_posts']).'</span></dd>';

				// Now let's deal with the contact links (Email and URL)
				if ((($cur_post['email_setting'] == '0' && !$pun_user['is_guest']) || $pun_user['is_admmod']) && $pun_user['g_send_email'] == '1')
					$cur_post['user_contacts'][] = '<span class="email"><a href="mailto:'.pun_htmlspecialchars($cur_post['email']).'">'.$lang_common['Email'].'</a></span>';
				else if ($cur_post['email_setting'] == '1' && !$pun_user['is_guest'] && $pun_user['g_send_email'] == '1')
					$cur_post['user_contacts'][] = '<span class="email"><a href="misc.php?email='.$cur_post['poster_id'].'">'.$lang_common['Email'].'</a></span>';

				if ($cur_post['url'] != '')
				{
					if ($pun_config['o_censoring'] == '1')
						$cur_post['url'] = censor_words($cur_post['url']);

					$cur_post['user_contacts'][] = '<span class="website"><a href="'.pun_htmlspecialchars($cur_post['url']).'" rel="nofollow">'.$lang_topic['Website'].'</a></span>';
				}
			}

			if ($pun_user['g_id'] == PUN_ADMIN || ($pun_user['g_moderator'] == '1' && $pun_user['g_mod_promote_users'] == '1'))
			{
				if ($cur_post['g_promote_next_group'])
					$cur_post['user_info'][] = '<dd><span><a href="profile.php?action=promote&amp;id='.$cur_post['poster_id'].'&amp;pid='.$cur_post['id'].'">'.$lang_topic['Promote user'].'</a></span></dd>';
			}

			if ($pun_user['is_admmod'])
			{
				$cur_post['user_info'][] = '<dd><span><a href="moderate.php?get_host='.$cur_post['id'].'" title="'.pun_htmlspecialchars($cur_post['poster_ip']).'">'.$lang_topic['IP address logged'].'</a></span></dd>';

				if ($cur_post['admin_note'] != '')
					$cur_post['user_info'][] = '<dd><span>'.$lang_topic['Note'].' <strong>'.pun_htmlspecialchars($cur_post['admin_note']).'</strong></span></dd>';
			}
		}
		// If the poster is a guest (or a user that has been deleted)
		else
		{
			$cur_post['username_formatted'] = pun_htmlspecialchars($cur_post['username']);
			$cur_post['user_title_formatted'] = get_title($cur_post);

			if ($pun_user['is_admmod'])
				$cur_post['user_info'][] = '<dd><span><a href="moderate.php?get_host='.$cur_post['id'].'" title="'.pun_htmlspecialchars($cur_post['poster_ip']).'">'.$lang_topic['IP address logged'].'</a></span></dd>';

			if ($pun_config['o_show_user_info'] == '1' && $cur_post['poster_email'] != '' && !$pun_user['is_guest'] && $pun_user['g_send_email'] == '1')
				$cur_post['user_contacts'][] = '<span class="email"><a href="mailto:'.pun_htmlspecialchars($cur_post['poster_email']).'">'.$lang_common['Email'].'</a></span>';
		}

		// Generation post action array (quote, edit, delete etc.)
		if (!$is_admmod)
		{
			if (!$pun_user['is_guest'])
				$cur_post['post_actions'][] = '<li class="postreport"><span><a href="misc.php?report='.$cur_post['id'].'">'.$lang_topic['Report'].'</a></span></li>';

			if ($cur_topic['closed'] == '0')
			{
				if ($cur_post['poster_id'] == $pun_user['id'])
				{
					if ((($start_from + $post_count) == 1 && $pun_user['g_delete_topics'] == '1') || (($start_from + $post_count) > 1 && $pun_user['g_delete_posts'] == '1'))
						$cur_post['post_actions'][] = '<li class="postdelete"><span><a href="delete.php?id='.$cur_post['id'].'">'.$lang_topic['Delete'].'</a></span></li>';
					if ($pun_user['g_edit_posts'] == '1')
						$cur_post['post_actions'][] = '<li class="postedit"><span><a href="edit.php?id='.$cur_post['id'].'">'.$lang_topic['Edit'].'</a></span></li>';
				}

				if (($cur_topic['post_replies'] == '' && $pun_user['g_post_replies'] == '1') || $cur_topic['post_replies'] == '1')
					$cur_post['post_actions'][] = '<li class="postquote"><span><a href="post.php?tid='.$topic_id.'&amp;qid='.$cur_post['id'].'">'.$lang_topic['Quote'].'</a></span></li>';
			}
		}
		else
		{
			$cur_post['post_actions'][] = '<li class="postreport"><span><a href="misc.php?report='.$cur_post['id'].'">'.$lang_topic['Report'].'</a></span></li>';
			if ($pun_user['g_id'] == PUN_ADMIN || !in_array($cur_post['poster_id'], $admin_ids))
			{
				$cur_post['post_actions'][] = '<li class="postdelete"><span><a href="delete.php?id='.$cur_post['id'].'">'.$lang_topic['Delete'].'</a></span></li>';
				$cur_post['post_actions'][] = '<li class="postedit"><span><a href="edit.php?id='.$cur_post['id'].'">'.$lang_topic['Edit'].'</a></span></li>';
			}
			$cur_post['post_actions'][] = '<li class="postquote"><span><a href="post.php?tid='.$topic_id.'&amp;qid='.$cur_post['id'].'">'.$lang_topic['Quote'].'</a></span></li>';
		}

		// Perform the main parsing of the message (BBCode, smilies, censor words etc)
		$cur_post['message'] = parse_message($cur_post['message'], $cur_post['hide_smilies']);

		// Do signature parsing/caching
		if ($pun_config['o_signatures'] == '1' && $cur_post['signature'] != '' && $pun_user['show_sig'] != '0')
		{
			if (isset($avatar_cache[$cur_post['poster_id']]))
				$cur_post['signature_formatted'] = $avatar_cache[$cur_post['poster_id']];
			else
			{
				$cur_post['signature_formatted'] = parse_signature($cur_post['signature']);
				$avatar_cache[$cur_post['poster_id']] = $cur_post['signature_formatted'];
			}
		}
		
		$post_data[] = $cur_post;
	}
	
	return $post_data;
}