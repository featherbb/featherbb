<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace model\admin;

use DB;

class statistics
{
    public function __construct()
    {
        $this->feather = \Slim\Slim::getInstance();
        $this->start = $this->feather->start;
        $this->config = $this->feather->config;
        $this->user = $this->feather->user;
        $this->request = $this->feather->request;
    }
 
    public function get_server_load()
    {
        global $lang_admin_index;

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
            $server_load = isset($load_averages[2]) ? $load_averages[0].' '.$load_averages[1].' '.$load_averages[2] : $lang_admin_index['Not available'];
        } elseif (!in_array(PHP_OS, array('WINNT', 'WIN32')) && preg_match('%averages?: ([0-9\.]+),?\s+([0-9\.]+),?\s+([0-9\.]+)%i', @exec('uptime'), $load_averages)) {
            $server_load = $load_averages[1].' '.$load_averages[2].' '.$load_averages[3];
        } else {
            $server_load = $lang_admin_index['Not available'];
        }

        return $server_load;
    }

    public function get_num_online()
    {
        $num_online = DB::for_table('online')->where('idle', 0)
                            ->count('user_id');

        return $num_online;
    }

    public function get_total_size()
    {
        global $db_type;

        $total = array();

        if ($db_type == 'mysql' || $db_type == 'mysqli' || $db_type == 'mysql_innodb' || $db_type == 'mysqli_innodb') {
            // Calculate total db size/row count
            $result = DB::for_table('users')->raw_query('SHOW TABLE STATUS LIKE \''.$this->feather->prefix.'%\'')->find_many();

            $total['size'] = $total['records'] = 0;
            foreach ($result as $status) {
                $total['records'] += $status['Rows'];
                $total['size'] += $status['Data_length'] + $status['Index_length'];
            }

            $total['size'] = file_size($total['size']);
        }

        return $total;
    }

    public function get_php_accelerator()
    {
        global $lang_admin_index;

        if (function_exists('mmcache')) {
            $php_accelerator = '<a href="http://'.$lang_admin_index['Turck MMCache link'].'">'.$lang_admin_index['Turck MMCache'].'</a>';
        } elseif (isset($_PHPA)) {
            $php_accelerator = '<a href="http://'.$lang_admin_index['ionCube PHP Accelerator link'].'">'.$lang_admin_index['ionCube PHP Accelerator'].'</a>';
        } elseif (ini_get('apc.enabled')) {
            $php_accelerator ='<a href="http://'.$lang_admin_index['Alternative PHP Cache (APC) link'].'">'.$lang_admin_index['Alternative PHP Cache (APC)'].'</a>';
        } elseif (ini_get('zend_optimizer.optimization_level')) {
            $php_accelerator = '<a href="http://'.$lang_admin_index['Zend Optimizer link'].'">'.$lang_admin_index['Zend Optimizer'].'</a>';
        } elseif (ini_get('eaccelerator.enable')) {
            $php_accelerator = '<a href="http://'.$lang_admin_index['eAccelerator link'].'">'.$lang_admin_index['eAccelerator'].'</a>';
        } elseif (ini_get('xcache.cacher')) {
            $php_accelerator = '<a href="http://'.$lang_admin_index['XCache link'].'">'.$lang_admin_index['XCache'].'</a>';
        } else {
            $php_accelerator = $lang_admin_index['NA'];
        }

        return $php_accelerator;
    }
}