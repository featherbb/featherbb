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

        <div class="linkst">
            <div class="inbox">
                <ul class="crumbs">
                    <li><a href="<?= Url::base() ?>"><?php _e('Index') ?></a></li>
                    <li><span>»&#160;</span><a href="<?= $feather->urlFor('Conversations.home') ?>"><?= _e('PMs', 'private_messages') ?></a></li>
                    <li><span>»&#160;</span><a href="<?= $feather->urlFor('Conversations.home', ['inbox_id' => $current_inbox_id]) ?>"><?= Utils::escape($inboxes[$current_inbox_id]['name']) ?></a></li>
                    <li><span>»&#160;</span><strong><?= (isset($cur_conv) && isset($cur_conv['subject']) ? $cur_conv['subject'] : _e('My conversations', 'private_messages'))?></strong></li>
                    <?php if (isset($rightLink) && !empty($rightLink)) { ?>
                        <li class="right"><span><a href="<?= $rightLink['link'] ?>"><?= $rightLink['text'] ?></a></span></li>
                    <?php }  ?>
                </ul>
                <div class="pagepost"></div>
                <div class="clearer"></div>
            </div>
        </div>

        <div id="adminconsole" class="block2col">
            <div id="adminmenu" class="blockmenu">
                <h2><span><?php _e('Folders', 'private_messages') ?></span></h2>
                <div class="box">
                    <div class="inbox">
                        <ul>
                            <?php if(!empty($inboxes)):
                                foreach ($inboxes as $iid => $data) { ?>
                                <li<?= ($iid == $current_inbox_id) ? ' class="isactive"' : ''; ?>>
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
                            <li><a href="#">Blocked Users</a></li>
                            <li><a href="#">My Folders</a></li>
                        </ul>
                    </div>
                </div>
            </div>
