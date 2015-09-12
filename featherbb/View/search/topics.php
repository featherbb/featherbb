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

<?php
$topic_count = 0;
foreach ($search_results as $cur_search) {
    ++$topic_count;
    ?>
                            <tr class="<?php echo $cur_search['item_status'] ?>">
                            	<td class="tcl">
                            		<div class="<?php echo $cur_search['icon_type'] ?>"><div class="nosize"><?php echo Utils::forum_number_format($topic_count + $cur_search['start_from']) ?></div></div>
                            		<div class="tclcon">
                            			<div>
                            				<?php echo $cur_search['subject']."\n" ?>
                            			</div>
                            		</div>
                            	</td>
                            	<td class="tc2"><?php echo $cur_search['forum'] ?></td>
                            	<td class="tc3"><?php echo Utils::forum_number_format($cur_search['num_replies']) ?></td>
                            	<td class="tcr"><?php echo '<a href="'.$feather->urlFor('viewPost', ['pid' => $cur_search['last_post_id']]).'#p'.$cur_search['last_post_id'].'">'.$feather->utils->format_time($cur_search['last_post']).'</a> <span class="byuser">'.__('by').' '.Utils::escape($cur_search['last_poster']) ?></span></td>
                            </tr>
<?php } ?>
