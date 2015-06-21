<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */
 
// Make sure no one attempts to run this script "directly"
if (!defined('PUN')) {
    exit;
}
?>

<div class="linkst">
	<div class="inbox crumbsplus">
		<ul class="crumbs">
			<li><a href="admin_index.php"><?php echo $lang_admin_common['Admin'].' '.$lang_admin_common['Index'] ?></a></li>
			<li><span>»&#160;</span><a href="admin_bans.php"><?php echo $lang_admin_common['Bans'] ?></a></li>
			<li><span>»&#160;</span><strong><?php echo $lang_admin_bans['Results head'] ?></strong></li>
		</ul>
		<div class="pagepost">
			<p class="pagelink"><?php echo $paging_links ?></p>
		</div>
		<div class="clearer"></div>
	</div>
</div>


<div id="bans1" class="blocktable">
	<h2><span><?php echo $lang_admin_bans['Results head'] ?></span></h2>
	<div class="box">
		<div class="inbox">
			<table>
			<thead>
				<tr>
					<th class="tcl" scope="col"><?php echo $lang_admin_bans['Results username head'] ?></th>
					<th class="tc2" scope="col"><?php echo $lang_admin_bans['Results e-mail head'] ?></th>
					<th class="tc3" scope="col"><?php echo $lang_admin_bans['Results IP address head'] ?></th>
					<th class="tc4" scope="col"><?php echo $lang_admin_bans['Results expire head'] ?></th>
					<th class="tc5" scope="col"><?php echo $lang_admin_bans['Results message head'] ?></th>
					<th class="tc6" scope="col"><?php echo $lang_admin_bans['Results banned by head'] ?></th>
					<th class="tcr" scope="col"><?php echo $lang_admin_bans['Results actions head'] ?></th>
				</tr>
			</thead>
			<tbody>
<?php

        foreach ($ban_data as $cur_ban) {
            ?>
				<tr>
					<td class="tcl"><?php echo($cur_ban['username'] != '') ? pun_htmlspecialchars($cur_ban['username']) : '&#160;' ?></td>
					<td class="tc2"><?php echo($cur_ban['email'] != '') ? pun_htmlspecialchars($cur_ban['email']) : '&#160;' ?></td>
					<td class="tc3"><?php echo($cur_ban['ip'] != '') ? pun_htmlspecialchars($cur_ban['ip']) : '&#160;' ?></td>
					<td class="tc4"><?php echo format_time($cur_ban['expire'], true) ?></td>
					<td class="tc5"><?php echo($cur_ban['message'] != '') ? pun_htmlspecialchars($cur_ban['message']) : '&#160;' ?></td>
					<td class="tc6"><?php echo($cur_ban['ban_creator_username'] != '') ? '<a href="profile.php?id='.$cur_ban['ban_creator'].'">'.pun_htmlspecialchars($cur_ban['ban_creator_username']).'</a>' : $lang_admin_bans['Unknown'] ?></td>
					<td class="tcr"><?php echo '<a href="admin_bans.php?edit_ban='.$cur_ban['id'].'">'.$lang_admin_common['Edit'].'</a> | <a href="admin_bans.php?del_ban='.$cur_ban['id'].'">'.$lang_admin_common['Remove'].'</a>' ?></td>
				</tr>
<?php

        }
        if (empty($ban_data)) {
            echo "\t\t\t\t".'<tr><td class="tcl" colspan="7">'.$lang_admin_bans['No match'].'</td></tr>'."\n";
        }

?>
			</tbody>
			</table>
		</div>
	</div>
</div>

<div class="linksb">
	<div class="inbox crumbsplus">
		<div class="pagepost">
			<p class="pagelink"><?php echo $paging_links ?></p>
		</div>
		<ul class="crumbs">
			<li><a href="admin_index.php"><?php echo $lang_admin_common['Admin'].' '.$lang_admin_common['Index'] ?></a></li>
			<li><span>»&#160;</span><a href="admin_bans.php"><?php echo $lang_admin_common['Bans'] ?></a></li>
			<li><span>»&#160;</span><strong><?php echo $lang_admin_bans['Results head'] ?></strong></li>
		</ul>
		<div class="clearer"></div>
	</div>
</div>