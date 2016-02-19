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

Container::get('hooks')->fire('view.moderate.moderator_forum.start');
?>

<div class="linkst">
    <div class="inbox crumbsplus">
        <ul class="crumbs">
            <li><a href="<?= Url::base() ?>"><?php _e('Index') ?></a></li>
            <li><span>»&#160;</span><a href="<?= Router::pathFor('Forum', ['id' => $id, 'name' => $url_forum]) ?>"><?= Utils::escape($cur_forum['forum_name']) ?></a></li>
            <li><span>»&#160;</span><strong><?php _e('Moderate') ?></strong></li>
        </ul>
        <div class="pagepost">
            <p class="pagelink conl"><?= $paging_links ?></p>
        </div>
        <div class="clearer"></div>
    </div>
</div>

<form method="post" action="<?= Router::pathFor('dealPosts', ['fid' => $id, 'page' => $p]) ?>">
<input type="hidden" name="csrf_name" value="<?= $csrf_name; ?>"><input type="hidden" name="csrf_value" value="<?= $csrf_value; ?>">
<input type="hidden" name="page" value="<?= Utils::escape($p) ?>" />
<div id="vf" class="blocktable">
    <h2><span><?= Utils::escape($cur_forum['forum_name']) ?></span></h2>
    <div class="box">
        <div class="inbox">
            <table>
            <thead>
                <tr>
                    <th class="tcl" scope="col"><?php _e('Topic') ?></th>
                    <th class="tc2" scope="col"><?php _e('Replies') ?></th>
<?php if (ForumSettings::get('o_topic_views') == '1'): ?>                    <th class="tc3" scope="col"><?php _e('Views') ?></th>
<?php endif; ?>                    <th class="tcr"><?php _e('Last post') ?></th>
                    <th class="tcmod" scope="col"><?php _e('Select') ?></th>
                </tr>
            </thead>
            <tbody>

            <?php
            $topic_count = 0;
            $button_status = '';
            foreach ($topic_data as $topic) {
                ++$topic_count;
                ?>
                            <tr class="<?= $topic['item_status'] ?>">
                    <td class="tcl">
                        <div class="<?= $topic['icon_type'] ?>"><div class="nosize"><?= Utils::forum_number_format($topic_count + $start_from) ?></div></div>
                        <div class="tclcon">
                            <div>
                                <?= $topic['subject_disp']."\n" ?>
                            </div>
                        </div>
                    </td>
                    <td class="tc2"><?php echo(!$topic['ghost_topic']) ? Utils::forum_number_format($topic['num_replies']) : '-' ?></td>
<?php if (ForumSettings::get('o_topic_views') == '1'): ?>                    <td class="tc3"><?php echo(!$topic['ghost_topic']) ? Utils::forum_number_format($topic['num_views']) : '-' ?></td>
<?php endif;
                ?>                    <td class="tcr"><?= $topic['last_post_disp'] ?></td>
                    <td class="tcmod"><input type="checkbox" name="topics[<?= $topic['id'] ?>]" value="1" /></td>
                </tr>
            <?php

            }
            if (empty($topic_data)):
                $colspan = (ForumSettings::get('o_topic_views') == '1') ? 5 : 4;
                $button_status = ' disabled="disabled"';
                echo "\t\t\t\t\t".'<tr><td class="tcl" colspan="'.$colspan.'">'.__('Empty forum').'</td></tr>'."\n";
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
            <p class="pagelink conl"><?= $paging_links ?></p>
            <p class="conr modbuttons"><input type="submit" name="move_topics" value="<?php _e('Move') ?>"<?= $button_status ?> /> <input type="submit" name="delete_topics" value="<?php _e('Delete') ?>"<?= $button_status ?> /> <input type="submit" name="merge_topics" value="<?php _e('Merge') ?>"<?= $button_status ?> /> <input type="submit" name="open" value="<?php _e('Open') ?>"<?= $button_status ?> /> <input type="submit" name="close" value="<?php _e('Close') ?>"<?= $button_status ?> /></p>
            <div class="clearer"></div>
        </div>
        <ul class="crumbs">
            <li><a href="<?= Url::base() ?>"><?php _e('Index') ?></a></li>
            <li><span>»&#160;</span><a href="<?= Router::pathFor('Forum', ['id' => $id, 'name' => $url_forum]) ?>"><?= Utils::escape($cur_forum['forum_name']) ?></a></li>
            <li><span>»&#160;</span><strong><?php _e('Moderate') ?></strong></li>
        </ul>
        <div class="clearer"></div>
    </div>
</div>
</form>

<?php
Container::get('hooks')->fire('view.moderate.moderator_forum.end');
