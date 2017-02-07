<?php

/**
 * Copyright (C) 2015-2016 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

use FeatherBB\Core\Url;use FeatherBB\Core\Utils;

// Make sure no one attempts to run this script "directly"
if (!isset($feather)) {
    exit;
}

Container::get('hooks')->fire('view.footer.start');
?>
        </div>
    </section>
    <footer class="container">
        <div id="brdfooter" class="block">
            <h2><span><?= __('Board footer') ?></span></h2>
            <div class="box">
                <div id="brdfooternav" class="inbox">
<?php
// Display the "Jump to" drop list
if (ForumSettings::get('o_quickjump') == '1' && !empty($quickjump)) { ?>
                    <div class="conl">
                        <form id="qjump" method="get" action="">
                            <div>
                                <label><span><?= __('Jump to') ?><br /></span></label>
                                <select name="id" onchange="window.location=(this.options[this.selectedIndex].value)">
<?php
                foreach ($quickjump[(int) User::get()->g_id] as $cat_id => $cat_data) {
                    echo "\t\t\t\t\t\t\t\t\t".'<optgroup label="'.Utils::escape($cat_data['cat_name']).'">'."\n";
                    foreach ($cat_data['cat_forums'] as $forum) {
                        echo "\t\t\t\t\t\t\t\t\t\t".'<option value="'.Router::pathFor('Forum', ['id' => $forum['forum_id'], 'name' => Url::url_friendly($forum['forum_name'])]).'"'.($fid == 2 ? ' selected="selected"' : '').'>'.$forum['forum_name'].'</option>'."\n";
                    }
                    echo "\t\t\t\t\t\t\t\t\t".'</optgroup>'."\n";
                }
?>
                                </select>
                            </div>
                        </form>
                    </div>
<?php } ?>
                    <div class="conr">
                        <p id="poweredby"><?php printf(__('Powered by'), '<a href="http://featherbb.org/">FeatherBB</a>'.((ForumSettings::get('o_show_version') == '1') ? ' '.ForumSettings::get('o_cur_version') : '')) ?></p>
                    </div>
                    <div class="clearer"></div>
                </div>
            </div>
        </div>
<?php

// Display debug info (if enabled/defined)
if (!empty($exec_info)) { ?>
        <p id="debugtime">[ <?= sprintf(__('Querytime'), round($exec_info['exec_time'], 6), $exec_info['nb_queries']).' - '.sprintf(__('Memory usage'), $exec_info['mem_usage']).' '.sprintf(__('Peak usage'), $exec_info['mem_peak_usage'])?> ]</p>
<?php }
if (!empty($queries_info)) { ?>
        <div id="debug" class="blocktable">
            <h2><span><?= __('Debug table') ?></span></h2>
            <div class="box">
                <div class="inbox">
                    <table>
                        <thead>
                            <tr>
                                <th class="tcl" scope="col"><?= __('Query times') ?></th>
                                <th class="tcr" scope="col"><?= __('Query') ?></th>
                            </tr>
                        </thead>
                        <tbody>
<?php foreach ($queries_info['raw'] as $time => $sql) {
    echo "\t\t\t\t\t\t".'<tr>'."\n";
    echo "\t\t\t\t\t\t\t".'<td class="tcl">'.Utils::escape(round($time, 8)).'</td>'."\n";
    echo "\t\t\t\t\t\t\t".'<td class="tcr">'.Utils::escape($sql).'</td>'."\n";
    echo "\t\t\t\t\t\t".'</tr>'."\n";
} ?>
                            <tr>
                                <td class="tcl" colspan="2"><?= sprintf(__('Total query time'), round($queries_info['total_time'], 7)).' s' ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
<?php } ?>
    </footer>
<!-- JS -->
<script type="text/javascript">
    var baseUrl = '<?= Utils::escape(Url::base()); ?>',
        bodyId = 'pun<?= $active_page; ?>',
        phpVars = <?= isset($jsVars) ? json_encode($jsVars) : json_encode([]); ?>;
</script>
<?php
if (!empty($assets['js'])) {
    foreach ($assets['js'] as $script) {
        echo '<script ';
        foreach ($script['params'] as $key => $value) {
            echo $key.'="'.$value.'" ';
        }
        echo 'src="'.Utils::escape(Url::base()).'/'.$script['file'].'"></script>'."\n";
    }
}
Container::get('hooks')->fire('view.footer.before.html.tag'); ?>
</body>
</html>
<?php
Container::get('hooks')->fire('view.footer.end');
