<?php

/**
 * Copyright (C) 2015-2016 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

use FeatherBB\Core\Utils;

// Make sure no one attempts to run this script "directly"
if (!isset($feather)) {
    exit;
}

Container::get('hooks')->fire('view.admin.users.search_ip.start');
?>

<div class="linkst">
    <div class="inbox crumbsplus">
        <ul class="crumbs">
            <li><a href="<?= Router::pathFor('adminIndex') ?>"><?= __('Admin').' '.__('Index') ?></a></li>
            <li><span>»&#160;</span><a href="<?= Router::pathFor('adminUsers') ?>"><?= __('Users') ?></a></li>
            <li><span>»&#160;</span><strong><?= __('Results head') ?></strong></li>
        </ul>
        <div class="pagepost">
            <p class="pagelink"><?= $paging_links ?></p>
        </div>
        <div class="clearer"></div>
    </div>
</div>

<div id="users1" class="blocktable">
    <h2><span><?= __('Results head') ?></span></h2>
    <div class="box">
        <div class="inbox">
            <table>
            <thead>
                <tr>
                    <th class="tcl" scope="col"><?= __('Results IP address head') ?></th>
                    <th class="tc2" scope="col"><?= __('Results last used head') ?></th>
                    <th class="tc3" scope="col"><?= __('Results times found head') ?></th>
                    <th class="tcr" scope="col"><?= __('Results action head') ?></th>
                </tr>
            </thead>
            <tbody>
<?php
        foreach ($ip_data as $ip) {
            ?>
                <tr>
                    <td class="tcl"><a href="<?= Router::pathFor('getHostIp', ['ip' => Utils::escape($ip['poster_ip'])]) ?>"><?= Utils::escape($ip['poster_ip']) ?></a></td>
                    <td class="tc2"><?= Utils::format_time($ip['last_used']) ?></td>
                    <td class="tc3"><?= $ip['used_times'] ?></td>
                    <td class="tcr"><a href="<?= Router::pathFor('usersIpShow', ['ip' => $ip['poster_ip']]) ?>"><?= __('Results find more link') ?></a></td>
                </tr>
<?php

        }
        if (empty($ip_data)):
            echo "\t\t\t\t".'<tr><td class="tcl" colspan="4">'.__('Results no posts found').'</td></tr>'."\n";
        endif;

    ?>
            </tbody>
            </table>
        </div>
    </div>
</div>

<div class="linksb">
    <div class="inbox crumbsplus">
        <div class="pagepost">
            <p class="pagelink"><?= $paging_links ?></p>
        </div>
        <ul class="crumbs">
            <li><a href="<?= Router::pathFor('adminIndex') ?>"><?= __('Admin').' '.__('Index') ?></a></li>
            <li><span>»&#160;</span><a href="<?= Router::pathFor('adminUsers') ?>"><?= __('Users') ?></a></li>
            <li><span>»&#160;</span><strong><?= __('Results head') ?></strong></li>
        </ul>
        <div class="clearer"></div>
    </div>
</div>

<?php
Container::get('hooks')->fire('view.admin.users.search_ip.end');
