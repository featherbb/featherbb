<?php

/**
 * Copyright (C) 2015-2017 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace FeatherBB\Model\Admin;

use FeatherBB\Core\Database as DB;
use FeatherBB\Core\Interfaces\ForumSettings;
use FeatherBB\Core\Interfaces\Hooks;
use FeatherBB\Core\Utils;

class Statistics
{
    public function serverLoad()
    {
        if (@file_exists('/proc/loadavg') && is_readable('/proc/loadavg')) {
            // We use @ just in case
            $fh = @fopen('/proc/loadavg', 'r');
            $loadAverages = @fread($fh, 64);
            @fclose($fh);

            if (($fh = @fopen('/proc/loadavg', 'r'))) {
                $loadAverages = fread($fh, 64);
                fclose($fh);
            } else {
                $loadAverages = '';
            }

            $loadAverages = @explode(' ', $loadAverages);
            $loadAverages = Hooks::fire('model.admin.model.statistics.get_server_load.load_averages', $loadAverages);

            $serverLoad = isset($loadAverages[2]) ? $loadAverages[0].' '.$loadAverages[1].' '.$loadAverages[2] : __('Not available');
        } elseif (!in_array(PHP_OS, ['WINNT', 'WIN32']) && preg_match('%averages?: ([0-9\.]+),?\s+([0-9\.]+),?\s+([0-9\.]+)%i', @exec('uptime'), $loadAverages)) {
            $serverLoad = $loadAverages[1].' '.$loadAverages[2].' '.$loadAverages[3];
        } else {
            $serverLoad = __('Not available');
        }

        $serverLoad = Hooks::fire('model.admin.model.statistics.get_server_load.server_load', $serverLoad);
        return $serverLoad;
    }

    public function numOnline()
    {
        $numOnline = DB::table('online')->where('idle', 0)
                            ->count('user_id');

        $numOnline = Hooks::fire('model.admin.model.statistics.get_num_online.num_online', $numOnline);
        return $numOnline;
    }

    public function totalSize()
    {
        $total = [];

        if (ForumSettings::get('db_type') == 'mysql' || ForumSettings::get('db_type') == 'mysqli' || ForumSettings::get('db_type') == 'mysql_innodb' || ForumSettings::get('db_type') == 'mysqli_innodb') {
            // Calculate total db size/row count
            $result = DB::table('users')->rawQuery('SHOW TABLE STATUS LIKE \''.ForumSettings::get('db_prefix').'%\'')->findMany();
            $result = Hooks::fire('model.admin.model.statistics.get_total_size.raw_data', $result);

            $total['size'] = $total['records'] = 0;
            foreach ($result as $status) {
                $total['records'] += $status['Rows'];
                $total['size'] += $status['Data_length'] + $status['Index_length'];
            }

            $total['size'] = Utils::fileSize($total['size']);
        }

        $total = Hooks::fire('model.admin.model.statistics.get_total_size.total', $total);
        return $total;
    }

    public function phpAccelerator()
    {
        if (function_exists('mmcache')) {
            $phpAccelerator = '<a href="http://'.__('Turck MMCache link').'">'.__('Turck MMCache').'</a>';
        } elseif (isset($_pHPA)) {
            $phpAccelerator = '<a href="http://'.__('ionCube PHP Accelerator link').'">'.__('ionCube PHP Accelerator').'</a>';
        } elseif (ini_get('apc.enabled')) {
            $phpAccelerator ='<a href="http://'.__('Alternative PHP Cache (APC) link').'">'.__('Alternative PHP Cache (APC)').'</a>';
        } elseif (ini_get('zend_optimizer.optimization_level')) {
            $phpAccelerator = '<a href="http://'.__('Zend Optimizer link').'">'.__('Zend Optimizer').'</a>';
        } elseif (ini_get('eaccelerator.enable')) {
            $phpAccelerator = '<a href="http://'.__('eAccelerator link').'">'.__('eAccelerator').'</a>';
        } elseif (ini_get('xcache.cacher')) {
            $phpAccelerator = '<a href="http://'.__('XCache link').'">'.__('XCache').'</a>';
        } else {
            $phpAccelerator = __('NA');
        }

        $phpAccelerator = Hooks::fire('model.admin.model.statistics.get_php_accelerator', $phpAccelerator);
        return $phpAccelerator;
    }
}
