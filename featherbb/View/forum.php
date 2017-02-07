<?php

/**
 * Copyright (C) 2015-2017 FeatherBB
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

Container::get('hooks')->fire('view.forum.start');
?>
<div class="linkst">
    <div class="inbox crumbsplus">
        <ul class="crumbs">
            <li><a href="<?= Url::base() ?>/"><?= __('Index') ?></a></li>
            <li><span>»&#160;</span><strong><a href="<?php Router::pathFor('Forum', ['id' => $id, 'name' => $url_forum]) ?>"><?= Utils::escape($cur_forum['forum_name']) ?></a></strong></li>
        </ul>
        <div class="pagepost">
            <p class="pagelink conl"><?= $paging_links ?></p>
<?= $post_link ?>
        </div>
        <div class="clearer"></div>
    </div>
</div>

<div id="vf" class="blocktable">
    <h2><span><?= Utils::escape($cur_forum['forum_name']) ?></span></h2>
    <div class="box">
        <div class="inbox">
            <table>
            <thead>
                <tr>
                    <th class="tcl" scope="col"><?= __('Topic') ?></th>
                    <th class="tc2" scope="col"><?= __('Replies') ?></th>
<?php if (ForumSettings::get('o_topic_views') == '1'): ?>                    <th class="tc3" scope="col"><?= __('Views') ?></th>
<?php endif; ?>                    <th class="tcr" scope="col"><?= __('Last post') ?></th>
                </tr>
            </thead>
            <tbody>
            <?php
            $topic_count = 0;
            foreach ($forum_data as $topic) {
                ++$topic_count; ?>
                    <tr class="<?= $topic['item_status'] ?>">
                        <td class="tcl">
                            <div class="<?= $topic['icon_type'] ?>"><div class="nosize"><?= Utils::forum_number_format($topic_count + $start_from) ?></div></div>
                            <div class="tclcon">
                                <div>
                                    <?= $topic['subject_formatted']."\n" ?>
                                </div>
                            </div>
                        </td>
                        <td class="tc2"><?php echo(is_null($topic['moved_to'])) ? Utils::forum_number_format($topic['num_replies']) : '-' ?></td>
    <?php if (ForumSettings::get('o_topic_views') == '1'): ?>                    <td class="tc3"><?php echo(is_null($topic['moved_to'])) ? Utils::forum_number_format($topic['num_views']) : '-' ?></td>
    <?php endif; ?>                    <td class="tcr"><?= $topic['last_post_formatted'] ?></td>
                    </tr>
            <?php

            }
            if (empty($forum_data)):
            ?>
                    <tr class="rowodd inone">
                        <td class="tcl" colspan="<?php echo(ForumSettings::get('o_topic_views') == 1) ? 4 : 3 ?>">
                            <div class="icon inone"><div class="nosize"><!-- --></div></div>
                            <div class="tclcon">
                                <div>
                                    <strong><?= __('Empty forum') ?></strong>
                                </div>
                            </div>
                        </td>
                    </tr>
            <?php
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
<?= $post_link ?>
<?php
if (isset($active_page) && ($active_page == 'Forum') && $is_admmod) {
                echo "\t\t\t".'<div id="modcontrols" class="inbox">'."\n";
                echo "\t\t\t\t".'<dl>'."\n";
                echo "\t\t\t\t\t".'<dt><strong>'.__('Mod controls').'</strong></dt>'."\n";
                echo "\t\t\t\t\t".'<dd><span><a href="'.Router::pathFor('moderateForum', ['id' => $fid, 'name' => $url_forum, 'page' => $page_number]).'">'.__('Moderate forum').'</a></span></dd>'."\n";
                echo "\t\t\t\t".'</dl>'."\n";
                echo "\t\t\t".'</div>'."\n";
            }
Container::get('hooks')->fire('view.forum.mod.actions');
?>
        </div>
        <ul class="crumbs">
            <li><a href="<?= Url::base() ?>/"><?= __('Index') ?></a></li>
            <li><span>»&#160;</span><strong><a href="<?php Router::pathFor('Forum', ['id' => $id, 'name' => $url_forum]) ?>"><?= Utils::escape($cur_forum['forum_name']) ?></a></strong></li>
        </ul>
<?php echo(!empty($forum_actions) ? "\t\t".'<p class="subscribelink clearb">'.implode(' - ', $forum_actions).'</p>'."\n" : '') ?>
        <div class="clearer"></div>
    </div>
</div>
<?php
Container::get('hooks')->fire('view.forum.end');
