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

if (!empty($conversations)) { ?>
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
                                            <th class="tcl" scope="col"><?php _e('Title') ?></th>
                                            <th class="tc2" scope="col"><?php _e('Sender', 'private_messages') ?></th>
                                            <th class="tc2" scope="col"><?php _e('Receiver', 'private_messages') ?></th>
                                            <th class="tc2" scope="col"><?php _e('Replies') ?></th>
                                            <th class="tcr" scope="col"><?php _e('Last post') ?></th>
                                            <th class="tcmod" scope="col">
                                                <a href="#" onclick="return select_checkboxes('topics', this, '<input type=\'checkbox\' checked />')"><input type="checkbox" /></a>
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
    <?php $count = 1;
    foreach ($conversations as $conv) { ?>
                                        <tr class="<?=($count % 2 == 0) ? 'roweven ' : 'rowodd '?>inew">
                                            <td class="tcl">
                                                <div class="icon <?= (!$conv['viewed'] ? 'icon-new' : '')?>"><div class="nosize">1</div></div>
                                                <div class="tclcon">
                                                    <div>
                                                        <strong><a href="<?= $feather->urlFor('Conversations.show', ['tid' => $conv['id']])?>"><?= Utils::escape($conv['subject'])?></a></strong> <?php ($conv['viewed'] ? '<span class="newtext">[ <a href="#" title="Go to the first new post in this topic.">New posts</a> ]</span>' : '')?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="tc2"><a href="<?= $feather->urlFor('userProfile', ['id' => $conv['poster_id']]) ?>"><span><?= Utils::escape($conv['poster'])?></span></a></td>
                                            <td class="tc2"><?php if (isset($conv['receivers']) && is_array($conv['receivers'])) {
                                                foreach ($conv['receivers'] as $uid => $name) { ?>
                                                    <a href="<?= $feather->urlFor('userProfile', ['id' => $uid]) ?>"><span><?= Utils::escape($name)?></span></a>
                                                <?php } }?>
                                            </td>
                                            <td class="tc2"><?= (int) $conv['num_replies']?></td>
                                            <td class="tcr"><?= ($conv['last_post'] ? '<a href="#">'.$feather->utils->format_time($conv['last_post']).'</a>' : 'Never')?> <span class="byuser">by <a href="<?= $feather->urlFor('userProfile', ['id' => 2])?>"><?= Utils::escape($conv['last_poster'])?></a></span></td>
                                            <td class="tcmod"><input type="checkbox" name="topics[]" value="<?= $conv['id']; ?>" /></td>
                                        </tr>
    <?php
        ++$count;
    } ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="postlinksb">
                        <div class="inbox crumbsplus">
                            <div class="pagepost">
                                <p class="pagelink conl"><span class="pages-label"><?php _e('Pages'); ?></span><?= $paging_links; ?></p>
                                <p class="postlink conr">
                                    <select class="" name="action">
                                        <option value="-1" selected><?php _e('Select action', 'private_messages') ?></option>
                                        <option value="move"><?php _e('Move') ?></option>
                                        <option value="delete"><?php _e('Delete') ?></option>
                                        <option value="read"><?php _e('Mark read', 'private_messages') ?></option>
                                        <option value="unread"><?php _e('Mark unread', 'private_messages') ?></option>
                                    </select>
                                    <input type="submit" name="submit" value="<?php _e('Submit') ?>" />
                                </p>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

<?php } else { ?>
            <div class="block">
                <h2><span><?php _e('Info') ?></span></h2>
                <div class="box">
                    <div class="inbox info">
                        <p><?php _e('Empty inbox', 'private_messages') ?></p>
                    </div>
                </div>
            </div>
<?php } ?>
            <div class="clearer"></div>
