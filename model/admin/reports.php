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

        $result = \ORM::for_table($this->feather->prefix.'reports')
            ->select('zapped')
            ->where('id', $zap_id)
            ->find_one_col('zapped');

        if (!$result) {
            \ORM::for_table($this->feather->prefix.'reports')
                ->where('id', $zap_id)
                ->find_one()
                ->set(array('zapped' => time(),
                            'zapped_by' => $this->user->id))
                ->save();
        }

        $threshold = \ORM::for_table($this->feather->prefix.'reports')
            ->select('zapped')
            ->where_not_null('zapped')
            ->order_by_desc('zapped')
            ->offset(10)
            ->find_one_col('zapped');
        
        if ($threshold) {
            \ORM::for_table($this->feather->prefix.'reports')
                ->where_lte('zapped', $threshold)
                ->delete_many();
        }

        redirect(get_link('admin/reports/'), $lang_admin_reports['Report zapped redirect']);
    }

    public function check_reports()
    {
        $reports = \ORM::for_table($this->feather->prefix.'reports')
                        ->table_alias('r')
                        ->left_outer_join($this->feather->prefix.'posts', array('r.post_id', '=', 'p.id'), 'p')
                        ->left_outer_join($this->feather->prefix.'topics', array('r.topic_id', '=', 't.id'), 't')
                        ->left_outer_join($this->feather->prefix.'forums', array('r.forum_id', '=', 'f.id'), 'f')
                        ->left_outer_join($this->feather->prefix.'users', array('r.reported_by', '=', 'u.id'), 'u')
                        ->where_null('r.zapped')
                        ->count();

        // Filter params removed as we only count things
        return (bool) $reports;
    }

    public function check_zapped_reports()
    {
        $zapped_reports = \ORM::for_table($this->feather->prefix.'reports')
                        ->table_alias('r')
                        ->left_outer_join($this->feather->prefix.'posts', array('r.post_id', '=', 'p.id'), 'p')
                        ->left_outer_join($this->feather->prefix.'topics', array('r.topic_id', '=', 't.id'), 't')
                        ->left_outer_join($this->feather->prefix.'forums', array('r.forum_id', '=', 'f.id'), 'f')
                        ->left_outer_join($this->feather->prefix.'users', array('r.reported_by', '=', 'u.id'), 'u')
                        ->left_outer_join($this->feather->prefix.'users', array('r.zapped_by', '=', 'u2.id'), 'u2')
                        ->where_not_null('r.zapped')
                        ->count();

        // Filter params removed as we only count things
        return (bool) $zapped_reports;
    }

    public function get_reports()
    {
        global $lang_admin_reports;

        $select_reports = array('r.id', 'r.topic_id', 'r.forum_id', 'r.reported_by', 'r.created', 'r.message', 'pid' => 'p.id', 't.subject', 'f.forum_name', 'reporter' => 'u.username');
        $reports = \ORM::for_table($this->feather->prefix.'reports')
                        ->table_alias('r')
                        ->select_many($select_reports)
                        ->left_outer_join($this->feather->prefix.'posts', array('r.post_id', '=', 'p.id'), 'p')
                        ->left_outer_join($this->feather->prefix.'topics', array('r.topic_id', '=', 't.id'), 't')
                        ->left_outer_join($this->feather->prefix.'forums', array('r.forum_id', '=', 'f.id'), 'f')
                        ->left_outer_join($this->feather->prefix.'users', array('r.reported_by', '=', 'u.id'), 'u')
                        ->where_null('r.zapped')
                        ->order_by_desc('created')
                        ->find_result_set();

        foreach ($reports as $cur_report) {
            var_dump($cur_report);
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

        $select_zapped_reports = array('r.id', 'r.topic_id', 'r.forum_id', 'r.reported_by', 'r.message', 'r.zapped', 'zapped_by_id' => 'r.zapped_by', 'pid' => 'p.id', 't.subject', 'f.forum_name', 'reporter' => 'u.username', 'zapped_by' => 'u2.username');
        $zapped_reports = \ORM::for_table($this->feather->prefix.'reports')
                            ->table_alias('r')
                            ->select_many($select_zapped_reports)
                            ->left_outer_join($this->feather->prefix.'posts', array('r.post_id', '=', 'p.id'), 'p')
                            ->left_outer_join($this->feather->prefix.'topics', array('r.topic_id', '=', 't.id'), 't')
                            ->left_outer_join($this->feather->prefix.'forums', array('r.forum_id', '=', 'f.id'), 'f')
                            ->left_outer_join($this->feather->prefix.'users', array('r.reported_by', '=', 'u.id'), 'u')
                            ->left_outer_join($this->feather->prefix.'users', array('r.zapped_by', '=', 'u2.id'), 'u2')
                        ->where_not_null('r.zapped')
                        ->order_by_desc('zapped')
                        ->limit(10)
                        ->find_result_set();

        foreach ($zapped_reports as $cur_report) {
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