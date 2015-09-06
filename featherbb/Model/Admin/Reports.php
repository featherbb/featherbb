<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace FeatherBB\Model\Admin;

use FeatherBB\Core\Utils;
use FeatherBB\Core\Url;
use DB;

class Reports
{
    public function __construct()
    {
        $this->feather = \Slim\Slim::getInstance();
        $this->start = $this->feather->start;
        $this->config = $this->feather->config;
        $this->user = $this->feather->user;
        $this->request = $this->feather->request;
        $this->hook = $this->feather->hooks;
    }

    public function zap_report()
    {
        $zap_id = intval(key($this->request->post('zap_id')));
        $zap_id = $this->hook->fire('reports.zap_report.zap_id', $zap_id);

        $result = DB::for_table('reports')->where('id', $zap_id);
        $result = $this->hook->fireDB('reports.zap_report.query', $result);
        $result = $result->find_one_col('zapped');

        $set_zap_report = array('zapped' => time(), 'zapped_by' => $this->user->id);
        $set_zap_report = $this->hook->fire('reports.zap_report.set_zap_report', $set_zap_report);

        // Update report to indicate it has been zapped
        if (!$result) {
            DB::for_table('reports')
                ->where('id', $zap_id)
                ->find_one()
                ->set($set_zap_report)
                ->save();
        }

        // Remove zapped reports to keep only last 10
        $threshold = DB::for_table('reports')
            ->where_not_null('zapped')
            ->order_by_desc('zapped')
            ->offset(10)
            ->limit(1)
            ->find_one_col('zapped');

        if ($threshold) {
            DB::for_table('reports')
                ->where_lte('zapped', $threshold)
                ->delete_many();
        }

<<<<<<< HEAD
        $this->feather->url->redirect($this->feather->urlFor('adminReports'), __('Report zapped redirect'));
=======
        redirect(Url::get('admin/reports/'), __('Report zapped redirect'));
>>>>>>> development
    }

    public function get_reports()
    {
        $reports = array();
        $select_reports = array('r.id', 'r.topic_id', 'r.forum_id', 'r.reported_by', 'r.created', 'r.message', 'pid' => 'p.id', 't.subject', 'f.forum_name', 'reporter' => 'u.username');
        $reports = DB::for_table('reports')
            ->table_alias('r')
            ->select_many($select_reports)
            ->left_outer_join('posts', array('r.post_id', '=', 'p.id'), 'p')
            ->left_outer_join('topics', array('r.topic_id', '=', 't.id'), 't')
            ->left_outer_join('forums', array('r.forum_id', '=', 'f.id'), 'f')
            ->left_outer_join('users', array('r.reported_by', '=', 'u.id'), 'u')
            ->where_null('r.zapped')
            ->order_by_desc('created');
        $reports = $this->hook->fireDB('reports.get_reports.query', $reports);
        $reports = $reports->find_array();

        $reports = $this->hook->fire('reports.get_reports', $reports);
        return $reports;
    }

    public function get_zapped_reports()
    {
        $zapped_reports = array();
        $select_zapped_reports = array('r.id', 'r.topic_id', 'r.forum_id', 'r.reported_by', 'r.message', 'r.zapped', 'zapped_by_id' => 'r.zapped_by', 'pid' => 'p.id', 't.subject', 'f.forum_name', 'reporter' => 'u.username', 'zapped_by' => 'u2.username');
        $zapped_reports = DB::for_table('reports')
            ->table_alias('r')
            ->select_many($select_zapped_reports)
            ->left_outer_join('posts', array('r.post_id', '=', 'p.id'), 'p')
            ->left_outer_join('topics', array('r.topic_id', '=', 't.id'), 't')
            ->left_outer_join('forums', array('r.forum_id', '=', 'f.id'), 'f')
            ->left_outer_join('users', array('r.reported_by', '=', 'u.id'), 'u')
            ->left_outer_join('users', array('r.zapped_by', '=', 'u2.id'), 'u2')
            ->where_not_null('r.zapped')
            ->order_by_desc('zapped')
            ->limit(10);
        $zapped_reports = $this->hook->fireDB('reports.get_zapped_reports.query', $zapped_reports);
        $zapped_reports = $zapped_reports->find_array();

        $zapped_reports = $this->hook->fire('reports.get_zapped_reports', $zapped_reports);
        return $zapped_reports;
    }
}
