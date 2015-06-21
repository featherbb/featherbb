<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */
 
// Make sure no one attempts to run this script "directly"
if (!defined('PUN'))
	exit;
?>

<div class="linkst">
	<div class="inbox crumbsplus">
		<ul class="crumbs">
			<li><a href="admin_index.php"><?php echo $lang_admin_common['Admin'].' '.$lang_admin_common['Index'] ?></a></li>
			<li><span>»&#160;</span><a href="admin_users.php"><?php echo $lang_admin_common['Users'] ?></a></li>
			<li><span>»&#160;</span><strong><?php echo $lang_admin_users['Results head'] ?></strong></li>
		</ul>
		<div class="pagepost">
			<p class="pagelink"><?php echo $paging_links ?></p>
		</div>
		<div class="clearer"></div>
	</div>
</div>

<div id="users1" class="blocktable">
	<h2><span><?php echo $lang_admin_users['Results head'] ?></span></h2>
	<div class="box">
		<div class="inbox">
			<table>
			<thead>
				<tr>
					<th class="tcl" scope="col"><?php echo $lang_admin_users['Results IP address head'] ?></th>
					<th class="tc2" scope="col"><?php echo $lang_admin_users['Results last used head'] ?></th>
					<th class="tc3" scope="col"><?php echo $lang_admin_users['Results times found head'] ?></th>
					<th class="tcr" scope="col"><?php echo $lang_admin_users['Results action head'] ?></th>
				</tr>
			</thead>
			<tbody>
<?php
        foreach ($ip_data as $ip) {
            ?>
				<tr>
					<td class="tcl"><a href="moderate.php?get_host=<?php echo pun_htmlspecialchars($ip['poster_ip']) ?>"><?php echo pun_htmlspecialchars($ip['poster_ip']) ?></a></td>
					<td class="tc2"><?php echo format_time($ip['last_used']) ?></td>
					<td class="tc3"><?php echo $ip['used_times'] ?></td>
					<td class="tcr"><a href="admin_users.php?show_users=<?php echo pun_htmlspecialchars($ip['poster_ip']) ?>"><?php echo $lang_admin_users['Results find more link'] ?></a></td>
				</tr>
<?php

        }
		if (empty($ip_data)):
			echo "\t\t\t\t".'<tr><td class="tcl" colspan="4">'.$lang_admin_users['Results no posts found'].'</td></tr>'."\n";
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
			<p class="pagelink"><?php echo $paging_links ?></p>
		</div>
		<ul class="crumbs">
			<li><a href="admin_index.php"><?php echo $lang_admin_common['Admin'].' '.$lang_admin_common['Index'] ?></a></li>
			<li><span>»&#160;</span><a href="admin_users.php"><?php echo $lang_admin_common['Users'] ?></a></li>
			<li><span>»&#160;</span><strong><?php echo $lang_admin_users['Results head'] ?></strong></li>
		</ul>
		<div class="clearer"></div>
	</div>
</div>