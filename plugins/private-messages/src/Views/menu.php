<?php

/**
* Copyright (C) 2015-2016 FeatherBB
* based on code by (C) 2008-2015 FluxBB
* and Rickard Andersson (C) 2002-2008 PunBB
* License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher.
*/
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
<?php
if(!empty($inboxes)):
    $totalMsg = 0;
    foreach ($inboxes as $iid => $inbox):
        $totalMsg += $inbox['nb_msg'];
?>
                                    <li<?= ($page == $inbox['name']) ? ' class="isactive"' : ''; ?>>
                                        <a href="<?= $feather->urlFor('Conversations.home', ['inbox_id' => $iid]) ?>"><?= Utils::escape($inbox['name']) ?><?= (intval($inbox['nb_msg']) > 0) ? ' ('.$inbox['nb_msg'].')' : ''; ?></a>
                                    </li>
<?php endforeach;
endif; ?>
                            </ul>
                        </div>
                    </div>
                    <h2><span><?php _e('Storage', 'private_messages') ?></span></h2>
                    <div class="box">
                        <div class="inbox">
                            <ul>
                                <li class="big">Inbox: 0% full</li>
                                <li><div id="pm_bar_style" style="width:0;"></div></li>
                                <li class="big">Quota: <?= $totalMsg ?> / &infin;</li>
                            </ul>
                        </div>
                    </div>
                    <br />
                    <h2><span><?php _e('Options', 'private_messages') ?></span></h2>
                    <div class="box">
                        <div class="inbox">
                            <ul>
                                <li<?= ($page == 'blocked') ? ' class="isactive"' : ''; ?>><a href="<?= $feather->urlFor('Conversations.blocked') ?>"><?php _e('Blocked Users', 'private_messages') ?></a></li>
                                <li<?= ($page == 'folders') ? ' class="isactive"' : ''; ?>><a href="<?= $feather->urlFor('Conversations.folders') ?>"><?php _e('My Folders', 'private_messages') ?></a></li>
                            </ul>
                        </div>
                    </div>
                </div>
