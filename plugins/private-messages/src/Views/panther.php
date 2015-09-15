<?php
/**
 * Copyright (C) 2015 Panther (https://www.pantherforum.org)
 * based on code by FluxBB copyright (C) 2008-2012 FluxBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 3 or higher
 */


if (isset($_POST['delete']))
{
	if (!isset($_POST['topics']))
		message($lang_pm['Select more than one topic']);

	$topics = isset($_POST['topics']) && is_array($_POST['topics']) ? array_map('intval', $_POST['topics']) : array_map('intval', explode(',', $_POST['topics']));

	if (empty($topics))
		message($lang_pm['Select more than one topic']);

	if (isset($_POST['delete_comply']))
	{
		confirm_referrer('pms_inbox.php');

		$markers = array();
		$data = array($panther_user['id']);
		for ($i = 0; $i < count($topics); $i++)
		{
			$markers[] = '?';
			$data[] = $topics[$i];
		}

		$ps = $db->run('SELECT SUM(c.num_replies) FROM '.$db->prefix.'conversations AS c INNER JOIN '.$db->prefix.'pms_data AS cd ON c.id=cd.topic_id AND cd.user_id=? WHERE c.id IN ('.implode(',', $markers).')', $data);
		$num_pms = ($ps->fetchColumn() + count($markers));	// The number of topic posts and the number of replies from all topics

		$db->run('UPDATE '.$db->prefix.'pms_data SET deleted=1 WHERE user_id=? AND topic_id IN ('.implode(',', $markers).')', $data);
		$update = array(
			':markers'	=>	$num_pms,
			':id'	=>	$panther_user['id'],
		);

		$db->run('UPDATE '.$db->prefix.'users SET num_pms=num_pms-:markers WHERE id=:id', $update);
		unset($data[0]);

		// Now check if anyone left in the conversation has any of these topics undeleted. If so, then we leave them. Otherwise, actually delete them.
		foreach (array_values($data) as $tid)
		{
			$delete = array(
				':id'	=>	$tid,
			);

			$ps = $db->select('pms_data', 1, $delete, 'topic_id=:id AND deleted=0');
			if ($ps->rowCount())	// People are still left
				continue;

			$db->delete('pms_data', 'topic_id=:id', $delete);
			$db->delete('conversations', 'id=:id', $delete);
			$db->delete('messages', 'topic_id=:id', $delete);
		}

		redirect(panther_link($panther_url['inbox']), $lang_pm['Messages deleted']);
	}
	else
	{
		$page_title = array(panther_htmlspecialchars($panther_config['o_board_title']), $lang_common['PM'], $lang_pm['PM Inbox']);
		define('PANTHER_ACTIVE_PAGE', 'pm');
		require PANTHER_ROOT.'header.php';

		$pm_tpl = panther_template('delete_messages.tpl');
		$search = array(
			'{index_link}' => panther_link($panther_url['index']),
			'{index}' => $lang_common['Index'],
			'{inbox_link}' => panther_link($panther_url['inbox']),
			'{inbox}' => $lang_common['PM'],
			'{my_messages}' => $lang_pm['My messages'],
			'{send_message_link}' => panther_link($panther_url['send_message']),
			'{send_message}' => $lang_pm['Send message'],
			'{pm_menu}' => generate_pm_menu(),
			'{form_action}' => panther_link($panther_url['inbox']),
			'{topics}' => implode(',', $topics),
			'{delete_messages_comply}' => $lang_pm['Delete messages comply'],
			'{delete}' => $lang_pm['Delete button'],
			'{go_back}' => $lang_common['Go back'],
			'{csrf_token}' => generate_csrf_token(),
		);

		echo str_replace(array_keys($search), array_values($search), $pm_tpl);
		require PANTHER_ROOT.'footer.php';
	}
}
elseif (isset($_POST['move']))
{
	if (!isset($_POST['topics']))
		message($lang_pm['Select more than one topic']);

	$topics	= isset($_POST['topics']) && is_array($_POST['topics']) ? array_map('intval', $_POST['topics']) : array_map('intval', explode(',', $_POST['topics']));

	if (empty($topics))
		message($lang_pm['Select more than one topic']);

	if (isset($_POST['move_comply']))
	{
		confirm_referrer('pms_inbox.php');
		$folder = isset($_POST['folder']) ? intval($_POST['folder']) : 1;

		$markers = array();
		$update = array($folder);
		for ($i = 0; $i < count($topics); $i++)
		{
			$markers[] = '?';
			$update[] = $topics[$i];
		}

		$data = array(
			':fid'	=>	$folder,
			':uid'	=>	$panther_user['id'],
		);

		$ps = $db->select('folders', 1, $data, 'id=:fid AND user_id=:uid OR user_id=1');
		if (!$ps->rowCount())
			message($lang_common['No permission']);	// Then they don't have permission to move them to this folder

		$update[] = $panther_user['id'];
		$ps = $db->run('UPDATE '.$db->prefix.'pms_data SET folder_id=? WHERE topic_id IN ('.implode(',', $markers).') AND user_id=?', $update);
		redirect(panther_link($panther_url['inbox']), $lang_pm['Messages moved']);
	}

	$data = array(
		':uid'	=>	$panther_user['id'],
	);

	$ps = $db->select('folders', 'name, id', $data, 'user_id=:uid OR user_id=1', 'id, user_id ASC');
	if (!$ps->rowCount())
		message($lang_pm['No available folders']);

	$folders = array();
	foreach ($ps as $folder)
		$folders[] = '<option value="'.$folder['id'].'">'.panther_htmlspecialchars($folder['name']).'</option>';

	$page_title = array(panther_htmlspecialchars($panther_config['o_board_title']), $lang_common['PM'], $lang_pm['PM Inbox']);
	define('PANTHER_ACTIVE_PAGE', 'pm');
	require PANTHER_ROOT.'header.php';

	$pm_tpl = panther_template('move_messages.tpl');
	$search = array(
		'{index_link}' => panther_link($panther_url['index']),
		'{index}' => $lang_common['Index'],
		'{inbox_link}' => panther_link($panther_url['inbox']),
		'{inbox}' => $lang_common['PM'],
		'{my_messages}' => $lang_pm['My messages'],
		'{send_message_link}' => panther_link($panther_url['send_message']),
		'{send_message}' => $lang_pm['Send message'],
		'{pm_menu}' => generate_pm_menu(),
		'{form_action}' => panther_link($panther_url['inbox']),
		'{topics}' => implode(',', $topics),
		'{folders}' => implode("\n\t\t\t\t\t\t\t\t", $folders),
		'{move_messages_comply}' => $lang_pm['Move messages comply'],
		'{move}' => $lang_pm['Move button'],
		'{go_back}' => $lang_common['Go back'],
		'{csrf_token}' => generate_csrf_token(),
	);

	echo str_replace(array_keys($search), array_values($search), $pm_tpl);
	require PANTHER_ROOT.'footer.php';
}

$data = array(
	':uid'	=>	$panther_user['id'],
	':fid'	=>	$box_id,
);

// Check we own this box (hackers are everywhere =) )
$ps = $db->select('folders', 'name', $data, '(user_id=:uid OR user_id=1) AND id=:fid');
if (!$ps->rowCount())
	message($lang_common['Bad request']);
else
	$box_name = panther_htmlspecialchars($ps->fetchColumn());

$data = array(
	':fid'	=>	$box_id,
	':uid'	=>	$panther_user['id'],
);

$ps = $db->run('SELECT COUNT(c.id) FROM '.$db->prefix.'conversations AS c INNER JOIN '.$db->prefix.'pms_data AS cd ON c.id=cd.topic_id WHERE cd.user_id=:uid AND cd.deleted=0 AND (cd.folder_id=:fid '.(($box_id == 1) ? 'OR cd.viewed=0)' : ')'), $data);
$messages = $ps->fetchColumn();

$num_pages = ceil($messages / $panther_user['disp_topics']);

$p = (!isset($_GET['p']) || $_GET['p'] <= 1 || $_GET['p'] > $num_pages) ? 1 : intval($_GET['p']);
$start_from = $panther_user['disp_topics'] * ($p - 1);

$paging_links = '<span class="pages-label">'.$lang_common['Pages'].' </span>'.paginate($num_pages, $p, $panther_url['box'], array($box_id));

$data = array(
	':uid'	=>	$panther_user['id'],
	':fid'	=>	$box_id,
	':start'=>	$start_from,
);

$ps = $db->run('SELECT c.id, c.subject, c.poster, c.poster_id, c.num_replies, c.last_post, c.last_poster, c.last_post_id, cd.viewed, u.group_id AS poster_gid, u.email, u.use_gravatar, l.id AS last_poster_id, l.group_id AS last_poster_gid FROM '.$db->prefix.'conversations AS c INNER JOIN '.$db->prefix.'pms_data AS cd ON c.id=cd.topic_id LEFT JOIN '.$db->prefix.'users AS u ON u.id=c.poster_id LEFT JOIN '.$db->prefix.'users AS l ON l.username=c.last_poster WHERE cd.user_id=:uid AND cd.deleted=0 AND (cd.folder_id=:fid '.(($box_id == 1) ? 'OR cd.viewed=0)' : ')').'ORDER BY c.last_post DESC LIMIT :start, '.$panther_user['disp_topics'], $data);

$page_title = array(panther_htmlspecialchars($panther_config['o_board_title']), $lang_common['PM'], $lang_pm['PM Inbox']);
$page_head = array('js' => "\t".'<script src="'.$panther_config['o_js_dir'].'common.js"></script>');
define('PANTHER_ALLOW_INDEX', 1);
define('PANTHER_ACTIVE_PAGE', 'index');
require PANTHER_ROOT.'header.php';

if ($messages)
{
	$topic_count = 0;
	$message_rows = array();
	$row_tpl = panther_template('inbox_row.tpl');
	foreach ($ps as $cur_topic)
	{
		$data = array(
			':tid'	=>	$cur_topic['id']
		);

		$users = array();
		$ps1 = $db->run('SELECT cd.user_id AS id, u.username, u.group_id FROM '.$db->prefix.'pms_data AS cd INNER JOIN '.$db->prefix.'users AS u ON cd.user_id=u.id WHERE topic_id=:tid', $data);
		foreach ($ps1 as $user_data)
			$users[] = colourize_group($user_data['username'], $user_data['group_id'], $user_data['id']);

		++$topic_count;
		$status_text = array();
		$item_status = ($topic_count % 2 == 0) ? 'roweven' : 'rowodd';
		$icon_type = 'icon';

		$last_post = '<span class="byuser_avatar">'.generate_avatar_markup($cur_topic['last_poster_id'], $cur_topic['email'], $cur_topic['use_gravatar'], array(32, 32)).'</span><a href="'.panther_link($panther_url['pms_post'], array($cur_topic['last_post_id'])).'">'.format_time($cur_topic['last_post']).'</a> <span class="byuser">'.$lang_common['by'].' '.colourize_group($cur_topic['last_poster'], $cur_topic['last_poster_gid'], $cur_topic['last_poster_id']).'</span>';

		if ($panther_config['o_censoring'] == '1')
			$cur_topic['subject'] = censor_words($cur_topic['subject']);

		$subject = '<a href="'.panther_link($panther_url['pms_view'], array($cur_topic['id'])).'">'.panther_htmlspecialchars($cur_topic['subject']).'</a>';

		if ($cur_topic['viewed'] == 0)
		{
			$item_status .= ' inew';
			$icon_type = 'icon icon-new';
			$subject = '<strong>'.$subject.'</strong>';
			$subject_new_posts = '<span class="newtext">[ <a href="'.panther_link($panther_url['pms_new'], array($cur_topic['id'])).'" title="'.$lang_common['New posts info'].'">'.$lang_common['New posts'].'</a> ]</span>';
		}
		else
			$subject_new_posts = null;

		// Insert the status text before the subject
		$subject = implode(' ', $status_text).' '.$subject;

		$num_pages_topic = ceil(($cur_topic['num_replies'] + 1) / $panther_user['disp_posts']);

		if ($num_pages_topic > 1)
			$subject_multipage = '<span class="pagestext">[ '.paginate($num_pages_topic, -1, $panther_url['pms_paginate'], array($cur_topic['id'])).' ]</span>';
		else
			$subject_multipage = null;

		// Should we show the "New posts" and/or the multipage links?
		if (!empty($subject_new_posts) || !empty($subject_multipage))
		{
			$subject .= !empty($subject_new_posts) ? ' '.$subject_new_posts : '';
			$subject .= !empty($subject_multipage) ? ' '.$subject_multipage : '';
		}

		$search = array(
			'{item_status}' => $item_status,
			'{icon_type}' => $icon_type,
			'{topic_count}' => forum_number_format($topic_count + $start_from),
			'{subject}' => $subject,
			'{poster}' => colourize_group($cur_topic['poster'], $cur_topic['poster_gid'], $cur_topic['poster_id']),
			'{receivers}' => implode('<br />', $users),
			'{num_replies}' => forum_number_format($cur_topic['num_replies']),
			'{last_post}' => $last_post,
			'{id}' => $cur_topic['id'],
		);

		$message_rows[] = str_replace(array_keys($search), array_values($search), $row_tpl);
	}

	$row_tpl = panther_template('inbox_results.tpl');
	$search = array(
		'{form_action}' => panther_link($panther_url['inbox']),
		'{csrf_token}' => generate_csrf_token(),
		'{page}' => $p,
		'{subject}' => $lang_pm['Subject'],
		'{sender}' => $lang_pm['Sender'],
		'{receivers}' => $lang_pm['Receiver'],
		'{replies}' => $lang_pm['Replies'],
		'{last_post}' => $lang_pm['Last post'],
		'{messages}' => implode("\n", $message_rows),
		'{paging_links}' => $paging_links,
		'{move_button}' => ($box_id != 3) ? '<input type="submit" name="move" value="'.$lang_pm['Move button'].'" />&#160;' : '', // If we're not viewing archived messages
		'{delete_button}' => $lang_pm['Delete button'],
	);

	$row_tpl = str_replace(array_keys($search), array_values($search), $row_tpl);
}
else
{
	$row_tpl = panther_template('no_messages.tpl');
	$search = array(
		'{info}' => $lang_common['Info'],
		'{no_messages}' => $lang_pm['No messages in folder'],
	);

	$row_tpl = str_replace(array_keys($search), array_values($search), $row_tpl);
}

$pm_tpl = panther_template('pms_inbox.tpl');
$search = array(
	'{index_link}' => panther_link($panther_url['index']),
	'{index}' => $lang_common['Index'],
	'{inbox_link}' => panther_link($panther_url['inbox']),
	'{pm}' => $lang_common['PM'],
	'{box_link}' => panther_link($panther_url['box'], array($box_id)),
	'{box_name}' => $box_name,
	'{my_messages}' => $lang_pm['My messages'],
	'{send_message_link}' => panther_link($panther_url['send_message']),
	'{send_message}' => $lang_pm['Send message'],
	'{inbox_content}' => generate_pm_menu($box_id)."\n".$row_tpl,
);

echo str_replace(array_keys($search), array_values($search), $pm_tpl);
require PANTHER_ROOT.'footer.php';
