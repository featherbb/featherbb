<?php

/**
 * Copyright (C) 2015-2019 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace FeatherBB\Model\Admin;

use FeatherBB\Core\Database as DB;
use FeatherBB\Core\Interfaces\Hooks;
use FeatherBB\Core\Interfaces\User;

class Reports
{
    public function zap($zapId)
    {
        $zapId = Hooks::fire('model.admin.reports.zap_report.zap_id', $zapId);

        $result = DB::table('reports')->where('id', $zapId);
        $result = Hooks::fireDB('model.admin.reports.zap_report.query', $result);
        $result = $result->findOneCol('zapped');

        $setZapReport = ['zapped' => time(), 'zapped_by' => User::get()->id];
        $setZapReport = Hooks::fire('model.admin.reports.set_zap_report', $setZapReport);

        // Update report to indicate it has been zapped
        if (!$result) {
            DB::table('reports')
                ->where('id', $zapId)
                ->findOne()
                ->set($setZapReport)
                ->save();
        }

        // Remove zapped reports to keep only last 10
        $threshold = DB::table('reports')
            ->whereNotNull('zapped')
            ->orderByDesc('zapped')
            ->offset(10)
            ->limit(1)
            ->findOneCol('zapped');

        if ($threshold) {
            DB::table('reports')
                ->whereLte('zapped', $threshold)
                ->deleteMany();
        }

        return true;
    }

    public static function hasReports()
    {
        Hooks::fire('get_reports_start');

        $resultHeader = DB::table('reports')->whereNull('zapped');
        $resultHeader = Hooks::fireDB('get_reports_query', $resultHeader);

        return (bool) $resultHeader->findOne();
    }

    public function reports()
    {
        $reports = [];
        $selectReports = ['r.id', 'r.topic_id', 'r.forum_id', 'r.reported_by', 'r.created', 'r.message', 'pid' => 'p.id', 't.subject', 'f.forum_name', 'reporter' => 'u.username'];
        $reports = DB::table('reports')
            ->tableAlias('r')
            ->selectMany($selectReports)
            ->leftOuterJoin('posts', ['r.post_id', '=', 'p.id'], 'p')
            ->leftOuterJoin('topics', ['r.topic_id', '=', 't.id'], 't')
            ->leftOuterJoin('forums', ['r.forum_id', '=', 'f.id'], 'f')
            ->leftOuterJoin('users', ['r.reported_by', '=', 'u.id'], 'u')
            ->whereNull('r.zapped')
            ->orderByDesc('created');
        $reports = Hooks::fireDB('model.admin.reports.get_reports.query', $reports);
        $reports = $reports->findArray();

        $reports = Hooks::fire('model.admin.reports.get_reports', $reports);
        return $reports;
    }

    public function zappedReports()
    {
        $zappedReports = [];
        $selectZappedReports = ['r.id', 'r.topic_id', 'r.forum_id', 'r.reported_by', 'r.message', 'r.zapped', 'zapped_by_id' => 'r.zapped_by', 'pid' => 'p.id', 't.subject', 'f.forum_name', 'reporter' => 'u.username', 'zapped_by' => 'u2.username'];
        $zappedReports = DB::table('reports')
            ->tableAlias('r')
            ->selectMany($selectZappedReports)
            ->leftOuterJoin('posts', ['r.post_id', '=', 'p.id'], 'p')
            ->leftOuterJoin('topics', ['r.topic_id', '=', 't.id'], 't')
            ->leftOuterJoin('forums', ['r.forum_id', '=', 'f.id'], 'f')
            ->leftOuterJoin('users', ['r.reported_by', '=', 'u.id'], 'u')
            ->leftOuterJoin('users', ['r.zapped_by', '=', 'u2.id'], 'u2')
            ->whereNotNull('r.zapped')
            ->orderByDesc('zapped')
            ->limit(10);
        $zappedReports = Hooks::fireDB('model.admin.reports.get_zapped_reports.query', $zappedReports);
        $zappedReports = $zappedReports->findArray();

        $zappedReports = Hooks::fire('model.admin.reports.get_zapped_reports', $zappedReports);
        return $zappedReports;
    }
}
