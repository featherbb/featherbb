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

Container::get('hooks')->fire('view.search.footer.start');

if ($footer['show_as'] == 'topics') :
?>
            </tbody>
        </table>
        </div>
    </div>
</div>

<?php endif; ?>

<div class="postlinksb">
    <div class="inbox crumbsplus">
        <div class="pagepost">
            <p class="pagelink"><?= $footer['paging_links'] ?></p>
        </div>
        <ul class="crumbs">
            <li><a href="<?= Url::base() ?>"><?= __('Index') ?></a></li>
            <li><span>»&#160;</span><a href="<?= Router::pathFor('search') ?>"><?= $footer['crumbs_text']['show_as'] ?></a></li>
            <li><span>»&#160;</span><strong><?= $footer['crumbs_text']['search_type'] ?></strong></li>
        </ul>
<?php echo(!empty($footer['forum_actions']) ? "\t\t".'<p class="subscribelink clearb">'.implode(' - ', $footer['forum_actions']).'</p>'."\n" : '') ?>
        <div class="clearer"></div>
    </div>
</div>

<?php
Container::get('hooks')->fire('view.search.footer.end');
