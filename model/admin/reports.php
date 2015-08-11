<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace model\admin;

use DB;

class reports
{
    public function __construct()
    {
        $this->feather = \Slim\Slim::getInstance();
        $this->start = $this->feather->start;
        $this->config = $this->feather->config;
        $this->user = $this->feather->user;
        $this->request = $this->feather->request;
    }

    public function zap_report()
    {
        global $lang_admin_reports;

        $zap_id = intval(key($this->request->post('zap_id')));

        $result = DB::for_table('reports')
            ->where('id', $zap_id)
            ->find_one_col('zapped');

        $set_zap_report = array('zapped' => time(),
            'zapped_by' => $this->user->id);

        if (!$result) {
            DB::for_table('reports')
                ->where('id', $zap_id)
                ->find_one()
                ->set($set_zap_report)
                ->save();
        }

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

        redirect(get_link('admin/reports/'), $lang_admin_reports['Report zapped redirect']);
    }

    public function get_reports()
    {
        global $lang_admin_reports;

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
            ->order_by_desc('created')
            ->find_array();

        return $reports;
    }

    public function get_zapped_reports()
    {
        global $lang_admin_reports;

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
            ->limit(10)
            ->find_array();

        return $zapped_reports;
    }
}