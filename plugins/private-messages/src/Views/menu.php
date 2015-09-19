<?php

/**
* Copyright (C) 2015 FeatherBB
* based on code by (C) 2008-2015 FluxBB
* and Rickard Andersson (C) 2002-2008 PunBB
* License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher.
*/
use FeatherBB\Core\Url;
use FeatherBB\Core\Utils;

// Make sure no one attempts to run this script "directly"
if (!isset($feather)) {
    exit;
}

?>

            <div id="adminconsole" class="block2col">
                <div id="adminmenu" class="blockmenu">
                    <h2><span><?php _e('Folders', 'private_messages') ?></span></h2>
                    <div class="box">
                        <div class="inbox">
                            <ul>
                                <?php if(!empty($inboxes)):
                                    foreach ($inboxes as $iid => $data) { ?>
                                    <li<?= ($iid == 'ok') ? ' class="isactive"' : ''; ?>>
                                        <a href="<?= $feather->urlFor('Conversations.home', ['inbox_id' => $iid]) ?>"><?= Utils::escape($data['name']) ?><?= (intval($data['nb_msg']) > 0) ? ' ('.$data['nb_msg'].')' : ''; ?></a>
                                    </li>
                                <?php } endif; ?>
                            </ul>
                        </div>
                    </div>
                    <h2><span><?php _e('Storage', 'private_messages') ?></span></h2>
                    <div class="box">
                        <div class="inbox">
                            <ul>
                                <li class="big">Inbox: 0% full</li>
                                <li><div id="pm_bar_style" style="width:0px;"></div></li>
                                <li class="big">Quota: 0 / &infin;</li>
                            </ul>
                        </div>
                    </div>
                    <br />
                    <h2><span><?php _e('Options', 'private_messages') ?></span></h2>
                    <div class="box">
                        <div class="inbox">
                            <ul>
                                <li><a href="<?= $feather->urlFor('Conversations.blocked') ?>"><?php _e('Blocked Users', 'private_messages') ?></a></li>
                                <li><a href="<?= $feather->urlFor('Conversations.folders') ?>"><?php _e('My Folders', 'private_messages') ?></a></li>
                            </ul>
                        </div>
                    </div>
                </div>
