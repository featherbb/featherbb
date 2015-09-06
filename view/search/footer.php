<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */
 
// Make sure no one attempts to run this script "directly"
if (!defined('FEATHER')) {
    exit;
}

if ($search['show_as'] == 'topics') :
?>
			</tbody>
		</table>
		</div>
	</div>
</div>

<?php
endif;
?>

<div class="postlinksb">
	<div class="inbox crumbsplus">
		<div class="pagepost">
			<p class="pagelink"><?php echo $search['paging_links'] ?></p>
		</div>
		<ul class="crumbs">
			<li><a href="<?php echo $feather->url->base() ?>"><?php _e('Index') ?></a></li>
			<li><span>»&#160;</span><a href="<?php echo $feather->url->get('search/') ?>"><?php echo $search['crumbs_text']['show_as'] ?></a></li>
			<li><span>»&#160;</span><strong><?php echo $search['crumbs_text']['search_type'] ?></strong></li>
		</ul>
<?php echo(!empty($search['forum_actions']) ? "\t\t".'<p class="subscribelink clearb">'.implode(' - ', $search['forum_actions']).'</p>'."\n" : '') ?>
		<div class="clearer"></div>
	</div>
</div>