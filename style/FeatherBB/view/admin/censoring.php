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

	<div class="blockform">
		<h2><span><?php echo $lang_admin_censoring['Censoring head'] ?></span></h2>
		<div class="box">
			<form id="censoring" method="post" action="<?php echo get_link('admin/censoring/') ?>">
				<input type="hidden" name="<?php echo $csrf_key; ?>" value="<?php echo $csrf_token; ?>">
				<div class="inform">
					<fieldset>
						<legend><?php echo $lang_admin_censoring['Add word subhead'] ?></legend>
						<div class="infldset">
							<p><?php echo $lang_admin_censoring['Add word info'].' '.($feather_config['o_censoring'] == '1' ? sprintf($lang_admin_censoring['Censoring enabled'], '<a href="'.get_link('admin/options#censoring').'">'.$lang_admin_common['Options'].'</a>') : sprintf($lang_admin_censoring['Censoring disabled'], '<a href="'.get_link('admin/options#censoring').'">'.$lang_admin_common['Options'].'</a>')) ?></p>
							<table>
							<thead>
								<tr>
									<th class="tcl" scope="col"><?php echo $lang_admin_censoring['Censored word label'] ?></th>
									<th class="tc2" scope="col"><?php echo $lang_admin_censoring['Replacement label'] ?></th>
									<th class="hidehead" scope="col"><?php echo $lang_admin_censoring['Action label'] ?></th>
								</tr>
							</thead>
							<tbody>
								<tr>
									<td class="tcl"><input type="text" name="new_search_for" size="24" maxlength="60" tabindex="1" /></td>
									<td class="tc2"><input type="text" name="new_replace_with" size="24" maxlength="60" tabindex="2" /></td>
									<td><input type="submit" name="add_word" value="<?php echo $lang_admin_common['Add'] ?>" tabindex="3" /></td>
								</tr>
							</tbody>
							</table>
						</div>
					</fieldset>
				</div>
				<div class="inform">
					<fieldset>
						<legend><?php echo $lang_admin_censoring['Edit remove subhead'] ?></legend>
						<div class="infldset">
<?php
if (!empty($word_data)) {
    ?>
							<table>
							<thead>
								<tr>
									<th class="tcl" scope="col"><?php echo $lang_admin_censoring['Censored word label'] ?></th>
									<th class="tc2" scope="col"><?php echo $lang_admin_censoring['Replacement label'] ?></th>
									<th class="hidehead" scope="col"><?php echo $lang_admin_censoring['Action label'] ?></th>
								</tr>
							</thead>
							<tbody>
<?php

    foreach ($word_data as $word) {
        echo "\t\t\t\t\t\t\t\t".'<tr><td class="tcl"><input type="text" name="search_for['.$word['id'].']" value="'.feather_escape($word['search_for']).'" size="24" maxlength="60" /></td><td class="tc2"><input type="text" name="replace_with['.$word['id'].']" value="'.feather_escape($word['replace_with']).'" size="24" maxlength="60" /></td><td><input type="submit" name="update['.$word['id'].']" value="'.$lang_admin_common['Update'].'" />&#160;<input type="submit" name="remove['.$word['id'].']" value="'.$lang_admin_common['Remove'].'" /></td></tr>'."\n";
    }

    ?>
							</tbody>
							</table>
<?php

} else {
    echo "\t\t\t\t\t\t\t".'<p>'.$lang_admin_censoring['No words in list'].'</p>'."\n";
}

?>
						</div>
					</fieldset>
				</div>
			</form>
		</div>
	</div>
	<div class="clearer"></div>
</div>