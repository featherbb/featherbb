<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace FeatherBB;
use DB;

class Debug
{
    //
    // Display executed queries (if enabled)
    //
    public function queries()
    {
        ?>
        <div id="debug" class="blocktable">
            <h2><span><?php _e('Debug table') ?></span></h2>
            <div class="box">
                <div class="inbox">
                    <table>
                        <thead>
                        <tr>
                            <th class="tcl" scope="col"><?php _e('Query times') ?></th>
                            <th class="tcr" scope="col"><?php _e('Query') ?></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php
                        $query_time_total = 0;
                        $i = 0;
                        foreach (DB::get_query_log()[1] as $query) {
                            $query_time = DB::get_query_log()[0][$i];
                            ?>
                            <tr>
                                <td class="tcl"><?php echo feather_escape(round($query_time, 6)) ?></td>
                                <td class="tcr"><?php echo feather_escape($query) ?></td>
                            </tr>
                            <?php
                            $query_time_total .= $query_time;
                            ++$i;
                        }
                        ?>
                        <tr>
                            <td class="tcl" colspan="2"><?php printf(__('Total query time'), round($query_time_total, 6).' s') ?></td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php
    }

    public function info() {
        $feather = \Slim\Slim::getInstance();
        echo '<p id="debugtime">[ ';

        // Calculate script generation time
        $time_diff = sprintf('%.3f', get_microtime() - $feather->start);
        echo sprintf(__('Querytime'), $time_diff, count(\DB::get_query_log()[0]));

        if (function_exists('memory_get_usage')) {
            echo ' - '.sprintf(__('Memory usage'), file_size(memory_get_usage()));

            if (function_exists('memory_get_peak_usage')) {
                echo ' '.sprintf(__('Peak usage'), file_size(memory_get_peak_usage()));
            }
        }

        echo ' ]</p>'."\n";
    }
}