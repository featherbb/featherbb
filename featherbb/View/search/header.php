<?php

/**
 * Copyright (C) 2015-2016 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

use FeatherBB\Core\Url;

// Make sure no one attempts to run this script "directly"
if (!isset($feather)) {
    exit;
}

$feather->hooks->fire('view.search.header.start');
?>
<div class="linkst">
    <div class="inbox crumbsplus">
        <ul class="crumbs">
            <li><a href="<?= Url::base() ?>"><?php _e('Index') ?></a></li>
            <li><span>»&#160;</span><a href="<?= $feather->urlFor('search') ?>"><?= $search['crumbs_text']['show_as'] ?></a></li>
            <li><span>»&#160;</span><strong><?= $search['crumbs_text']['search_type'] ?></strong></li>
        </ul>
        <div class="pagepost">
            <p class="pagelink"><?= $search['paging_links'] ?></p>
        </div>
        <div class="clearer"></div>
    </div>
</div>

<?php
if ($search['show_as'] == 'topics') :
?>
<div id="vf" class="blocktable">
    <h2><span><?php _e('Search results') ?></span></h2>
    <div class="box">
        <div class="inbox">
            <table>
            <thead>
                <tr>
                    <th class="tcl" scope="col"><?php _e('Topic') ?></th>
                    <th class="tc2" scope="col"><?php _e('Forum') ?></th>
                    <th class="tc3" scope="col"><?php _e('Replies') ?></th>
                    <th class="tcr" scope="col"><?php _e('Last post') ?></th>
                </tr>
            </thead>
            <tbody>
<?php
endif;

$feather->hooks->fire('view.search.header.end');