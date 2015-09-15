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
            <form method="post" action="http://localhost/panther/pms_inbox.php" id="topics" name="posttopic">
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
                                        <th class="tcmod" scope="col"><input type="checkbox" onclick="javascript:select_checkboxes('topics', this, '')" /></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr class="rowodd inew">
                                        <td class="tcl">
                                            <div class="icon icon-new"><div class="nosize">1</div></div>
                                            <div class="tclcon">
                                                <div>
                                                    <strong><a href="http://localhost/panther/pms_view.php?tid=1">test</a></strong> <span class="newtext">[ <a href="http://localhost/panther/pms_view.php?tid=1&action=new" title="Go to the first new post in this topic.">New posts</a> ]</span>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="tcl"><a href="http://localhost/panther/profile.php?id=2"><span class="gid1">hooger</span></a></td>
                                        <td class="tc2"><a href="http://localhost/panther/profile.php?id=2"><span class="gid1">hooger</span></a></td>
                                        <td class="tc2">1</td>
                                        <td class="tcr"><span class="byuser_avatar"><img src="https://www.gravatar.com/avatar.php?gravatar_id=f52f8e0527efe38014d71baa938ab708&amp;size=32" width="32" height="32" alt="" /></span><a href="http://localhost/panther/pms_view.php?pid=0#p0">Never</a> <span class="byuser">by <a href="http://localhost/panther/profile.php?id=2"><span class="gid1">hooger</span></a></span></td>
                                        <td class="tcmod"><input type="checkbox" name="topics[]" value="1" /></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="pagepost">
                    <p class="pagelink conl"><span class="pages-label">Pages: </span><strong class="item1">1</strong></p>
                    <p class="postlink conr"><input type="submit" name="move" value="Move" />&#160;<input type="submit" name="delete" value="Delete" /></p>
                </div>
            </form>
        </div>
        <div class="clearer"></div>
    </div>
