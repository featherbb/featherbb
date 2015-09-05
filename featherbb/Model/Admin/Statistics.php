<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace FeatherBB\Model\Admin;

use FeatherBB\Utils;
use DB;

class Statistics
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

    public function get_server_load()
    {
        if (@file_exists('/proc/loadavg') && is_readable('/proc/loadavg')) {
            // We use @ just in case
            $fh = @fopen('/proc/loadavg', 'r');
            $load_averages = @fread($fh, 64);
            @fclose($fh);

            if (($fh = @fopen('/proc/loadavg', 'r'))) {
                $load_averages = fread($fh, 64);
                fclose($fh);
            } else {
                $load_averages = '';
            }

            $load_averages = @explode(' ', $load_averages);
            $load_averages = $this->hook->fire('model.statistics.get_server_load.load_averages', $load_averages);

            $server_load = isset($load_averages[2]) ? $load_averages[0].' '.$load_averages[1].' '.$load_averages[2] : __('Not available');
        } elseif (!in_array(PHP_OS, array('WINNT', 'WIN32')) && preg_match('%averages?: ([0-9\.]+),?\s+([0-9\.]+),?\s+([0-9\.]+)%i', @exec('uptime'), $load_averages)) {
            $server_load = $load_averages[1].' '.$load_averages[2].' '.$load_averages[3];
        } else {
            $server_load = __('Not available');
        }

        $server_load = $this->hook->fire('model.statistics.get_server_load.server_load', $server_load);
        return $server_load;
    }

    public function get_num_online()
    {
        $num_online = DB::for_table('online')->where('idle', 0)
                            ->count('user_id');

        $num_online = $this->hook->fire('model.statistics.get_num_online.num_online', $num_online);
        return $num_online;
    }

    public function get_total_size()
    {
        $total = array();

        if ($this->feather->forum_settings['db_type'] == 'mysql' || $this->feather->forum_settings['db_type'] == 'mysqli' || $this->feather->forum_settings['db_type'] == 'mysql_innodb' || $this->feather->forum_settings['db_type'] == 'mysqli_innodb') {
            // Calculate total db size/row count
            $result = DB::for_table('users')->raw_query('SHOW TABLE STATUS LIKE \''.$this->feather->forum_settings['db_prefix'].'%\'')->find_many();
            $result = $this->hook->fire('model.statistics.get_total_size.raw_data', $result);

            $total['size'] = $total['records'] = 0;
            foreach ($result as $status) {
                $total['records'] += $status['Rows'];
                $total['size'] += $status['Data_length'] + $status['Index_length'];
            }

            $total['size'] = $this->feather->utils->file_size($total['size']);
        }

        $total = $this->hook->fire('model.statistics.get_total_size.total', $total);
        return $total;
    }

    public function get_php_accelerator()
    {
        if (function_exists('mmcache')) {
            $php_accelerator = '<a href="http://'.__('Turck MMCache link').'">'.__('Turck MMCache').'</a>';
        } elseif (isset($_PHPA)) {
            $php_accelerator = '<a href="http://'.__('ionCube PHP Accelerator link').'">'.__('ionCube PHP Accelerator').'</a>';
        } elseif (ini_get('apc.enabled')) {
            $php_accelerator ='<a href="http://'.__('Alternative PHP Cache (APC) link').'">'.__('Alternative PHP Cache (APC)').'</a>';
        } elseif (ini_get('zend_optimizer.optimization_level')) {
            $php_accelerator = '<a href="http://'.__('Zend Optimizer link').'">'.__('Zend Optimizer').'</a>';
        } elseif (ini_get('eaccelerator.enable')) {
            $php_accelerator = '<a href="http://'.__('eAccelerator link').'">'.__('eAccelerator').'</a>';
        } elseif (ini_get('xcache.cacher')) {
            $php_accelerator = '<a href="http://'.__('XCache link').'">'.__('XCache').'</a>';
        } else {
            $php_accelerator = __('NA');
        }

        $php_accelerator = $this->hook->fire('model.statistics.get_php_accelerator', $php_accelerator);
        return $php_accelerator;
    }
}
