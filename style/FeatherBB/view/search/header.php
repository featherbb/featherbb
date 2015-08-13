<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */
 
// Make sure no one attempts to run this script "directly"
if (!defined('FEATHER')) {
    exit;
}

?>
<div class="linkst">
	<div class="inbox crumbsplus">
		<ul class="crumbs">
			<li><a href="<?php echo get_base_url() ?>"><?php echo $lang_common['Index'] ?></a></li>
			<li><span>»&#160;</span><a href="<?php echo get_link('search/') ?>"><?php echo $search['crumbs_text']['show_as'] ?></a></li>
			<li><span>»&#160;</span><strong><?php echo $search['crumbs_text']['search_type'] ?></strong></li>
		</ul>
		<div class="pagepost">
			<p class="pagelink"><?php echo $search['paging_links'] ?></p>
		</div>
		<div class="clearer"></div>
	</div>
</div>

<?php
if ($search['show_as'] == 'topics') :
?>
<div id="vf" class="blocktable">
	<h2><span><?php echo $lang_search['Search results'] ?></span></h2>
	<div class="box">
		<div class="inbox">
			<table>
			<thead>
				<tr>
					<th class="tcl" scope="col"><?php echo $lang_common['Topic'] ?></th>
					<th class="tc2" scope="col"><?php echo $lang_common['Forum'] ?></th>
					<th class="tc3" scope="col"><?php echo $lang_common['Replies'] ?></th>
					<th class="tcr" scope="col"><?php echo $lang_common['Last post'] ?></th>
				</tr>
			</thead>
			<tbody>
<?php
endif;
