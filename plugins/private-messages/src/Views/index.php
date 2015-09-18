<?php

/**
* Copyright (C) 2015 FeatherBB
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

?>
<? if (!empty($conversations)) { ?>
            <div class="block">
                <form method="post" action="<?= $feather->request()->getPath(); ?>" id="topics">
                    <input type="hidden" name="<?= $csrf_key; ?>" value="<?= $csrf_token; ?>">
                    <input type="hidden" name="p" value="1" />
                    <input type="hidden" name="inbox_id" value="<?= $current_inbox_id ?>" />
                    <div id="vf" class="blocktable">
                        <div class="box">
                            <div class="inbox">
                                <table>
                                    <thead>
                                        <tr>
                                            <th class="tcl" scope="col">Messages</th>
                                            <th class="tc2" scope="col">Sender</th>
                                            <th class="tc2" scope="col">Receiver</th>
                                            <th class="tc2" scope="col">Replies</th>
                                            <th class="tcr" scope="col">Last Post</th>
                                            <th class="tcmod" scope="col"><input type="checkbox" onclick="#" /></th>
                                        </tr>
                                    </thead>
                                    <tbody>
    <? $count = 1;
    foreach ($conversations as $conv) { ?>
                                        <tr class="<?=($count % 2 == 0) ? 'roweven ' : 'rowodd '?>inew">
                                            <td class="tcl">
                                                <div class="icon <?= (!$conv['viewed'] ? 'icon-new' : '')?>"><div class="nosize">1</div></div>
                                                <div class="tclcon">
                                                    <div>
                                                        <strong><a href="<?= $feather->urlFor('Conversations.show', ['tid' => $conv['id']])?>"><?= Utils::escape($conv['subject'])?></a></strong> <? ($conv['viewed'] ? '<span class="newtext">[ <a href="#" title="Go to the first new post in this topic.">New posts</a> ]</span>' : '')?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="tcl"><a href="<?= $feather->urlFor('userProfile', ['id' => $conv['poster_id']]) ?>"><span><?= Utils::escape($conv['poster'])?></span></a></td>
                                            <td class="tc2"><? if (isset($conv['receivers']) && is_array($conv['receivers'])) {
                                                foreach ($conv['receivers'] as $uid => $name) { ?>
                                                    <a href="<?= $feather->urlFor('userProfile', ['id' => $uid]) ?>"><span><?= Utils::escape($name)?></span></a>
                                                <? } }?>
                                            </td>
                                            <td class="tc2"><?= (int) $conv['num_replies']?></td>
                                            <td class="tcr"><?= ($conv['last_post'] ? '<a href="#">'.$feather->utils->format_time($conv['last_post']).'</a>' : 'Never')?> <span class="byuser">by <a href="<?= $feather->urlFor('userProfile', ['id' => 2])?>"><?= Utils::escape($conv['last_poster'])?></a></span></td>
                                            <td class="tcmod"><input type="checkbox" name="topics[]" value="<?= $conv['id']; ?>" /></td>
                                        </tr>
    <?
        ++$count;
    } ?>

                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="pagepost">
                        <p class="pagelink conl"><span class="pages-label"><?php _e('Pages'); ?></span><?= $paging_links; ?></p>
                        <p class="conr"><input type="submit" name="move" value="Move" />&#160;<input type="submit" name="delete" value="Delete" /></p>
                    </div>
                </form>
            </div>

<? } else { ?>
            <div class="block">
            	<h2><span><?php _e('Info') ?></span></h2>
            	<div class="box">
            		<div class="inbox info">
                        <p>You have no conversations in this inbox.</p>
                    </div>
            	</div>
            </div>
<? } ?>
            <div class="clearer"></div>
