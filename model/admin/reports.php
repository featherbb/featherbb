<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace model\admin;

class reports
{
    public function __construct()
    {
        $this->feather = \Slim\Slim::getInstance();
        $this->db = $this->feather->db;
        $this->start = $this->feather->start;
        $this->config = $this->feather->config;
        $this->user = $this->feather->user;
        $this->request = $this->feather->request;
    }
 
    public function zap_report()
    {
        global $lang_admin_reports;

        

        $zap_id = intval(key($this->request->post('zap_id')));

        $result = $this->db->query('SELECT zapped FROM '.$this->db->prefix.'reports WHERE id='.$zap_id) or error('Unable to fetch report info', __FILE__, __LINE__, $this->db->error());
        $zapped = $this->db->result($result);

        if ($zapped == '') {
            $this->db->query('UPDATE '.$this->db->prefix.'reports SET zapped='.time().', zapped_by='.$this->user->id.' WHERE id='.$zap_id) or error('Unable to zap report', __FILE__, __LINE__, $this->db->error());
        }

        // Delete old reports (which cannot be viewed anyway)
        $result = $this->db->query('SELECT zapped FROM '.$this->db->prefix.'reports WHERE zapped IS NOT NULL ORDER BY zapped DESC LIMIT 10,1') or error('Unable to fetch read reports to delete', __FILE__, __LINE__, $this->db->error());
        if ($this->db->num_rows($result) > 0) {
            $zapped_threshold = $this->db->result($result);
            $this->db->query('DELETE FROM '.$this->db->prefix.'reports WHERE zapped <= '.$zapped_threshold) or error('Unable to delete old read reports', __FILE__, __LINE__, $this->db->error());
        }

        redirect(get_link('admin/reports/'), $lang_admin_reports['Report zapped redirect']);
    }

    public function check_reports()
    {
        $result = $this->db->query('SELECT r.id, r.topic_id, r.forum_id, r.reported_by, r.created, r.message, p.id AS pid, t.subject, f.forum_name, u.username AS reporter FROM '.$this->db->prefix.'reports AS r LEFT JOIN '.$this->db->prefix.'posts AS p ON r.post_id=p.id LEFT JOIN '.$this->db->prefix.'topics AS t ON r.topic_id=t.id LEFT JOIN '.$this->db->prefix.'forums AS f ON r.forum_id=f.id LEFT JOIN '.$this->db->prefix.'users AS u ON r.reported_by=u.id WHERE r.zapped IS NULL ORDER BY created DESC') or error('Unable to fetch report list', __FILE__, __LINE__, $this->db->error());
        if ($this->db->num_rows($result)) {
            $is_report = true;
        } else {
            $is_report = false;
        }

        return $is_report;
    }

    public function check_zapped_reports()
    {
        $result = $this->db->query('SELECT r.id, r.topic_id, r.forum_id, r.reported_by, r.message, r.zapped, r.zapped_by AS zapped_by_id, p.id AS pid, t.subject, f.forum_name, u.username AS reporter, u2.username AS zapped_by FROM '.$this->db->prefix.'reports AS r LEFT JOIN '.$this->db->prefix.'posts AS p ON r.post_id=p.id LEFT JOIN '.$this->db->prefix.'topics AS t ON r.topic_id=t.id LEFT JOIN '.$this->db->prefix.'forums AS f ON r.forum_id=f.id LEFT JOIN '.$this->db->prefix.'users AS u ON r.reported_by=u.id LEFT JOIN '.$this->db->prefix.'users AS u2 ON r.zapped_by=u2.id WHERE r.zapped IS NOT NULL ORDER BY zapped DESC LIMIT 10') or error('Unable to fetch report list', __FILE__, __LINE__, $this->db->error());
        if ($this->db->num_rows($result)) {
            $is_report_zapped = true;
        } else {
            $is_report_zapped = false;
        }

        return $is_report_zapped;
    }

    public function get_reports()
    {
        global $lang_admin_reports;

        $report_data = array();

        $result = $this->db->query('SELECT r.id, r.topic_id, r.forum_id, r.reported_by, r.created, r.message, p.id AS pid, t.subject, f.forum_name, u.username AS reporter FROM '.$this->db->prefix.'reports AS r LEFT JOIN '.$this->db->prefix.'posts AS p ON r.post_id=p.id LEFT JOIN '.$this->db->prefix.'topics AS t ON r.topic_id=t.id LEFT JOIN '.$this->db->prefix.'forums AS f ON r.forum_id=f.id LEFT JOIN '.$this->db->prefix.'users AS u ON r.reported_by=u.id WHERE r.zapped IS NULL ORDER BY created DESC') or error('Unable to fetch report list', __FILE__, __LINE__, $this->db->error());

        while ($cur_report = $this->db->fetch_assoc($result)) {
            $cur_report['reporter_disp'] = ($cur_report['reporter'] != '') ? '<a href="'.get_link('users/'.$cur_report['reported_by'].'/').'">'.feather_escape($cur_report['reporter']).'</a>' : $lang_admin_reports['Deleted user'];
            $forum = ($cur_report['forum_name'] != '') ? '<span><a href="'.get_link('forum/'.$cur_report['forum_id'].'/'.url_friendly($cur_report['forum_name']).'/').'">'.feather_escape($cur_report['forum_name']).'</a></span>' : '<span>'.$lang_admin_reports['Deleted'].'</span>';
            $topic = ($cur_report['subject'] != '') ? '<span>»&#160;<a href="'.get_link('forum/'.$cur_report['topic_id'].'/'.url_friendly($cur_report['subject'])).'">'.feather_escape($cur_report['subject']).'</a></span>' : '<span>»&#160;'.$lang_admin_reports['Deleted'].'</span>';
            $cur_report['post'] = str_replace("\n", '<br />', feather_escape($cur_report['message']));
            $post_id = ($cur_report['pid'] != '') ? '<span>»&#160;<a href="'.get_link('post/'.$cur_report['pid'].'/#p'.$cur_report['pid']).'">'.sprintf($lang_admin_reports['Post ID'], $cur_report['pid']).'</a></span>' : '<span>»&#160;'.$lang_admin_reports['Deleted'].'</span>';
            $cur_report['report_location'] = array($forum, $topic, $post_id);

            $report_data[] = $cur_report;
        }

        return $report_data;
    }

    public function get_zapped_reports()
    {
        global $lang_admin_reports;

        $report_zapped_data = array();

        $result = $this->db->query('SELECT r.id, r.topic_id, r.forum_id, r.reported_by, r.message, r.zapped, r.zapped_by AS zapped_by_id, p.id AS pid, t.subject, f.forum_name, u.username AS reporter, u2.username AS zapped_by FROM '.$this->db->prefix.'reports AS r LEFT JOIN '.$this->db->prefix.'posts AS p ON r.post_id=p.id LEFT JOIN '.$this->db->prefix.'topics AS t ON r.topic_id=t.id LEFT JOIN '.$this->db->prefix.'forums AS f ON r.forum_id=f.id LEFT JOIN '.$this->db->prefix.'users AS u ON r.reported_by=u.id LEFT JOIN '.$this->db->prefix.'users AS u2 ON r.zapped_by=u2.id WHERE r.zapped IS NOT NULL ORDER BY zapped DESC LIMIT 10') or error('Unable to fetch report list', __FILE__, __LINE__, $this->db->error());

        while ($cur_report = $this->db->fetch_assoc($result)) {
            $cur_report['reporter_disp'] = ($cur_report['reporter'] != '') ? '<a href="'.get_link('users/'.$cur_report['reported_by'].'/').'">'.feather_escape($cur_report['reporter']).'</a>' : $lang_admin_reports['Deleted user'];
            $forum = ($cur_report['forum_name'] != '') ? '<span><a href="'.get_link('forum/'.$cur_report['forum_id'].'/'.url_friendly($cur_report['forum_name']).'/').'">'.feather_escape($cur_report['forum_name']).'</a></span>' : '<span>'.$lang_admin_reports['Deleted'].'</span>';
            $topic = ($cur_report['subject'] != '') ? '<span>»&#160;<a href="'.get_link('forum/'.$cur_report['topic_id'].'/'.url_friendly($cur_report['subject'])).'">'.feather_escape($cur_report['subject']).'</a></span>' : '<span>»&#160;'.$lang_admin_reports['Deleted'].'</span>';
            $cur_report['post'] = str_replace("\n", '<br />', feather_escape($cur_report['message']));
            $post_id = ($cur_report['pid'] != '') ? '<span>»&#160;<a href="'.get_link('post/'.$cur_report['pid'].'/#p'.$cur_report['pid']).'">'.sprintf($lang_admin_reports['Post ID'], $cur_report['pid']).'</a></span>' : '<span>»&#160;'.$lang_admin_reports['Deleted'].'</span>';
            $cur_report['zapped_by_disp'] = ($cur_report['zapped_by'] != '') ? '<a href="'.get_link('user/'.$cur_report['zapped_by_id'].'/').'">'.feather_escape($cur_report['zapped_by']).'</a>' : $lang_admin_reports['NA'];
            $cur_report['zapped_by_disp'] = ($cur_report['zapped_by'] != '') ? '<strong>'.feather_escape($cur_report['zapped_by']).'</strong>' : $lang_admin_reports['NA'];
            $cur_report['report_location'] = array($forum, $topic, $post_id);

            $report_zapped_data[] = $cur_report;
        }

        return $report_zapped_data;
    }
}