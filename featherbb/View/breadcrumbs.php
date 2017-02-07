<?php

/**
 * Copyright (C) 2015-2016 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

use FeatherBB\Core\Url;
use FeatherBB\Core\Utils;

// Make sure no one attempts to run this script "directly"
if (!isset($feather)) {
    exit;
}

Container::get('hooks')->fire('view.breadcrumbs.start');

if(!empty($crumbs)): ?>
            <div class="linkst">
                <div class="inbox">
                    <ul class="crumbs">
                        <li><a href="<?= Url::base() ?>"><?= __('Index') ?></a></li>
<?php foreach ($crumbs as $link => $text) { ?>
                        <li><span>»&#160;</span><?= (!is_int($link)) ? '<a href="'.$link.'">' : '<strong>' ?><?= Utils::escape($text) ?><?= (!is_int($link)) ? '</a>' : '</strong>' ?></li>
<?php } ?>
<?php if (isset($rightCrumb) && !empty($rightCrumb)) { ?>
                        <li class="right"><span><a href="<?= $rightCrumb['link'] ?>"><?= Utils::escape($rightCrumb['text']) ?></a></span></li>
<?php }  ?>
                    </ul>
                    <div class="pagepost"></div>
                    <div class="clearer"></div>
                </div>
            </div>
<?php endif;
Container::get('hooks')->fire('view.breadcrumbs.end');
