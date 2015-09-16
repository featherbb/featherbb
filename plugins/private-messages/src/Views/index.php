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
<style media="screen">
div#pm_bar {
    border: 1px solid #336699;
    width: 100px;
    height: 10px;
    text-align: right;
    display:inline-block;
}

div#pm_bar_style {
    background-color: #336699;
    height: 10px;
    display:block;
}
</style>

        <div class="block">
            <form method="post" action="#" id="topics">
                <input type="hidden" name="<?= $csrf_key; ?>" value="<?= $csrf_token; ?>">
                <input type="hidden" name="p" value="1" />
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
                                    <tr class="rowodd inew">
                                        <td class="tcl">
                                            <div class="icon icon-new"><div class="nosize">1</div></div>
                                            <div class="tclcon">
                                                <div>
                                                    <strong><a href="<?= $feather->urlFor('Conversations', ['id' => 1])?>">test</a></strong> <span class="newtext">[ <a href="#" title="Go to the first new post in this topic.">New posts</a> ]</span>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="tcl"><a href="<?= $feather->urlFor('userProfile', ['id' => 2]) ?>"><span>hooger</span></a></td>
                                        <td class="tc2"><a href="<?= $feather->urlFor('userProfile', ['id' => 2]) ?>"><span>hooger</span></a></td>
                                        <td class="tc2">1</td>
                                        <td class="tcr"><a href="#">Never</a> <span class="byuser">by <a href="<?= $feather->urlFor('userProfile', ['id' => 2]) ?>"><span>hooger</span></a></span></td>
                                        <td class="tcmod"><input type="checkbox" name="topics[]" value="1" /></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="pagepost">
                    <p class="pagelink conl"><span class="pages-label">Pages: </span><strong class="item1">1</strong></p>
                    <p class="conr"><input type="submit" name="move" value="Move" />&#160;<input type="submit" name="delete" value="Delete" /></p>
                </div>
            </form>
        </div>
        <div class="clearer"></div>
    </div>
