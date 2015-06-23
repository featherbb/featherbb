<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */
 
function zap_report($post_data)
{
	global $db, $lang_admin_reports, $pun_user;

	confirm_referrer('admin_reports.php');

    $zap_id = intval(key($post_data['zap_id']));

    $result = $db->query('SELECT zapped FROM '.$db->prefix.'reports WHERE id='.$zap_id) or error('Unable to fetch report info', __FILE__, __LINE__, $db->error());
    $zapped = $db->result($result);

    if ($zapped == '') {
        $db->query('UPDATE '.$db->prefix.'reports SET zapped='.time().', zapped_by='.$pun_user['id'].' WHERE id='.$zap_id) or error('Unable to zap report', __FILE__, __LINE__, $db->error());
    }

    // Delete old reports (which cannot be viewed anyway)
    $result = $db->query('SELECT zapped FROM '.$db->prefix.'reports WHERE zapped IS NOT NULL ORDER BY zapped DESC LIMIT 10,1') or error('Unable to fetch read reports to delete', __FILE__, __LINE__, $db->error());
    if ($db->num_rows($result) > 0) {
        $zapped_threshold = $db->result($result);
        $db->query('DELETE FROM '.$db->prefix.'reports WHERE zapped <= '.$zapped_threshold) or error('Unable to delete old read reports', __FILE__, __LINE__, $db->error());
    }

    redirect('admin_reports.php', $lang_admin_reports['Report zapped redirect']);
}

function check_reports()
{
    global $db;
    
    $result = $db->query('SELECT r.id, r.topic_id, r.forum_id, r.reported_by, r.created, r.message, p.id AS pid, t.subject, f.forum_name, u.username AS reporter FROM '.$db->prefix.'reports AS r LEFT JOIN '.$db->prefix.'posts AS p ON r.post_id=p.id LEFT JOIN '.$db->prefix.'topics AS t ON r.topic_id=t.id LEFT JOIN '.$db->prefix.'forums AS f ON r.forum_id=f.id LEFT JOIN '.$db->prefix.'users AS u ON r.reported_by=u.id WHERE r.zapped IS NULL ORDER BY created DESC') or error('Unable to fetch report list', __FILE__, __LINE__, $db->error());
    if ($db->num_rows($result)) {
        $is_report = true;
    } else {
        $is_report = false;
    }
    
    return $is_report;
}

function check_zapped_reports()
{
    global $db;
    
    $result = $db->query('SELECT r.id, r.topic_id, r.forum_id, r.reported_by, r.message, r.zapped, r.zapped_by AS zapped_by_id, p.id AS pid, t.subject, f.forum_name, u.username AS reporter, u2.username AS zapped_by FROM '.$db->prefix.'reports AS r LEFT JOIN '.$db->prefix.'posts AS p ON r.post_id=p.id LEFT JOIN '.$db->prefix.'topics AS t ON r.topic_id=t.id LEFT JOIN '.$db->prefix.'forums AS f ON r.forum_id=f.id LEFT JOIN '.$db->prefix.'users AS u ON r.reported_by=u.id LEFT JOIN '.$db->prefix.'users AS u2 ON r.zapped_by=u2.id WHERE r.zapped IS NOT NULL ORDER BY zapped DESC LIMIT 10') or error('Unable to fetch report list', __FILE__, __LINE__, $db->error());
    if ($db->num_rows($result)) {
        $is_report_zapped = true;
    } else {
        $is_report_zapped = false;
    }
    
    return $is_report_zapped;
}

function get_reports()
{
	global $db, $lang_admin_reports;
	
	$report_data = array();
	
	$result = $db->query('SELECT r.id, r.topic_id, r.forum_id, r.reported_by, r.created, r.message, p.id AS pid, t.subject, f.forum_name, u.username AS reporter FROM '.$db->prefix.'reports AS r LEFT JOIN '.$db->prefix.'posts AS p ON r.post_id=p.id LEFT JOIN '.$db->prefix.'topics AS t ON r.topic_id=t.id LEFT JOIN '.$db->prefix.'forums AS f ON r.forum_id=f.id LEFT JOIN '.$db->prefix.'users AS u ON r.reported_by=u.id WHERE r.zapped IS NULL ORDER BY created DESC') or error('Unable to fetch report list', __FILE__, __LINE__, $db->error());
	
    while ($cur_report = $db->fetch_assoc($result)) {
        $cur_report['reporter_disp'] = ($cur_report['reporter'] != '') ? '<a href="profile.php?id='.$cur_report['reported_by'].'">'.pun_htmlspecialchars($cur_report['reporter']).'</a>' : $lang_admin_reports['Deleted user'];
        $forum = ($cur_report['forum_name'] != '') ? '<span><a href="viewforum.php?id='.$cur_report['forum_id'].'">'.pun_htmlspecialchars($cur_report['forum_name']).'</a></span>' : '<span>'.$lang_admin_reports['Deleted'].'</span>';
        $topic = ($cur_report['subject'] != '') ? '<span>»&#160;<a href="viewtopic.php?id='.$cur_report['topic_id'].'">'.pun_htmlspecialchars($cur_report['subject']).'</a></span>' : '<span>»&#160;'.$lang_admin_reports['Deleted'].'</span>';
        $cur_report['post'] = str_replace("\n", '<br />', pun_htmlspecialchars($cur_report['message']));
        $post_id = ($cur_report['pid'] != '') ? '<span>»&#160;<a href="viewtopic.php?pid='.$cur_report['pid'].'#p'.$cur_report['pid'].'">'.sprintf($lang_admin_reports['Post ID'], $cur_report['pid']).'</a></span>' : '<span>»&#160;'.$lang_admin_reports['Deleted'].'</span>';
        $cur_report['report_location'] = array($forum, $topic, $post_id);
		
		$report_data[] = $cur_report;
	}
	
	return $report_data;
}

function get_zapped_reports()
{
	global $db, $lang_admin_reports;
	
	$report_zapped_data = array();
	
	$result = $db->query('SELECT r.id, r.topic_id, r.forum_id, r.reported_by, r.message, r.zapped, r.zapped_by AS zapped_by_id, p.id AS pid, t.subject, f.forum_name, u.username AS reporter, u2.username AS zapped_by FROM '.$db->prefix.'reports AS r LEFT JOIN '.$db->prefix.'posts AS p ON r.post_id=p.id LEFT JOIN '.$db->prefix.'topics AS t ON r.topic_id=t.id LEFT JOIN '.$db->prefix.'forums AS f ON r.forum_id=f.id LEFT JOIN '.$db->prefix.'users AS u ON r.reported_by=u.id LEFT JOIN '.$db->prefix.'users AS u2 ON r.zapped_by=u2.id WHERE r.zapped IS NOT NULL ORDER BY zapped DESC LIMIT 10') or error('Unable to fetch report list', __FILE__, __LINE__, $db->error());
	
    while ($cur_report = $db->fetch_assoc($result)) {
        $cur_report['reporter_disp'] = ($cur_report['reporter'] != '') ? '<a href="profile.php?id='.$cur_report['reported_by'].'">'.pun_htmlspecialchars($cur_report['reporter']).'</a>' : $lang_admin_reports['Deleted user'];
        $forum = ($cur_report['forum_name'] != '') ? '<span><a href="viewforum.php?id='.$cur_report['forum_id'].'">'.pun_htmlspecialchars($cur_report['forum_name']).'</a></span>' : '<span>'.$lang_admin_reports['Deleted'].'</span>';
        $topic = ($cur_report['subject'] != '') ? '<span>»&#160;<a href="viewtopic.php?id='.$cur_report['topic_id'].'">'.pun_htmlspecialchars($cur_report['subject']).'</a></span>' : '<span>»&#160;'.$lang_admin_reports['Deleted'].'</span>';
        $cur_report['post'] = str_replace("\n", '<br />', pun_htmlspecialchars($cur_report['message']));
        $post_id = ($cur_report['pid'] != '') ? '<span>»&#160;<a href="viewtopic.php?pid='.$cur_report['pid'].'#p'.$cur_report['pid'].'">'.sprintf($lang_admin_reports['Post ID'], $cur_report['pid']).'</a></span>' : '<span>»&#160;'.$lang_admin_reports['Deleted'].'</span>';
        $cur_report['zapped_by_disp'] = ($cur_report['zapped_by'] != '') ? '<a href="profile.php?id='.$cur_report['zapped_by_id'].'">'.pun_htmlspecialchars($cur_report['zapped_by']).'</a>' : $lang_admin_reports['NA'];
        $cur_report['zapped_by_disp'] = ($cur_report['zapped_by'] != '') ? '<strong>'.pun_htmlspecialchars($cur_report['zapped_by']).'</strong>' : $lang_admin_reports['NA'];
        $cur_report['report_location'] = array($forum, $topic, $post_id);
		
		$report_zapped_data[] = $cur_report;
	}
	
	return $report_zapped_data;
}