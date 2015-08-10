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
		<h2><span><?php echo $lang_admin_groups['Add groups head'] ?></span></h2>
		<div class="box">
				<div class="inform">
					<fieldset>
                        <form id="groups" method="post" action="<?php echo get_link('admin/groups/add/') ?>">
                        <input type="hidden" name="<?php echo $csrf_key; ?>" value="<?php echo $csrf_token; ?>">
						<legend><?php echo $lang_admin_groups['Add group subhead'] ?></legend>
						<div class="infldset">
							<table class="aligntop">
								<tr>
									<th scope="row"><?php echo $lang_admin_groups['New group label'] ?><div><input type="submit" name="add_group" value="<?php echo $lang_admin_common['Add'] ?>" tabindex="2" /></div></th>
									<td>
										<select id="base_group" name="base_group" tabindex="1">
<?php

foreach ($groups as $cur_group) {
    if ($cur_group['g_id'] != FEATHER_ADMIN && $cur_group['g_id'] != FEATHER_GUEST) {
        if ($cur_group['g_id'] == $feather_config['o_default_user_group']) {
            echo "\t\t\t\t\t\t\t\t\t\t\t".'<option value="'.$cur_group['g_id'].'" selected="selected">'.feather_escape($cur_group['g_title']).'</option>'."\n";
        } else {
            echo "\t\t\t\t\t\t\t\t\t\t\t".'<option value="'.$cur_group['g_id'].'">'.feather_escape($cur_group['g_title']).'</option>'."\n";
        }
    }
}

?>
										</select>
										<span><?php echo $lang_admin_groups['New group help'] ?></span>
									</td>
								</tr>
							</table>
						</div>
                        </form>
					</fieldset>
				</div>
				<div class="inform">
					<fieldset>
                        <form id="groups" method="post" action="<?php echo get_link('admin/groups/') ?>">
                        	<input type="hidden" name="<?php echo $csrf_key; ?>" value="<?php echo $csrf_token; ?>">
						<legend><?php echo $lang_admin_groups['Default group subhead'] ?></legend>
						<div class="infldset">
							<table class="aligntop">
								<tr>
									<th scope="row"><?php echo $lang_admin_groups['Default group label'] ?><div><input type="submit" name="set_default_group" value="<?php echo $lang_admin_common['Save'] ?>" tabindex="4" /></div></th>
									<td>
										<select id="default_group" name="default_group" tabindex="3">
<?php

foreach ($groups as $cur_group) {
    if ($cur_group['g_id'] > FEATHER_GUEST && $cur_group['g_moderator'] == 0) {
        if ($cur_group['g_id'] == $feather_config['o_default_user_group']) {
            echo "\t\t\t\t\t\t\t\t\t\t\t".'<option value="'.$cur_group['g_id'].'" selected="selected">'.feather_escape($cur_group['g_title']).'</option>'."\n";
        } else {
            echo "\t\t\t\t\t\t\t\t\t\t\t".'<option value="'.$cur_group['g_id'].'">'.feather_escape($cur_group['g_title']).'</option>'."\n";
        }
    }
}

?>
										</select>
										<span><?php echo $lang_admin_groups['Default group help'] ?></span>
									</td>
								</tr>
							</table>
						</div>
                        </form>
					</fieldset>
				</div>
		</div>

		<h2 class="block2"><span><?php echo $lang_admin_groups['Existing groups head'] ?></span></h2>
		<div class="box">
			<div class="fakeform">
				<div class="inform">
					<fieldset>
						<legend><?php echo $lang_admin_groups['Edit groups subhead'] ?></legend>
						<div class="infldset">
							<p><?php echo $lang_admin_groups['Edit groups info'] ?></p>
							<table>
<?php
foreach ($groups as $cur_group) {
    echo "\t\t\t\t\t\t\t\t".'<tr><th scope="row"><a href="'.get_link('admin/groups/edit/'.$cur_group['g_id'].'/').'" tabindex="'.$cur_index++.'">'.$lang_admin_groups['Edit link'].'</a>'.(($cur_group['g_id'] > FEATHER_MEMBER) ? ' | <a href="'.get_link('admin/groups/delete/'.$cur_group['g_id'].'/').'" tabindex="'.$cur_index++.'">'.$lang_admin_groups['Delete link'].'</a>' : '').'</th><td>'.feather_escape($cur_group['g_title']).'</td></tr>'."\n";
}

?>
							</table>
						</div>
					</fieldset>
				</div>
			</div>
		</div>
	</div>
	<div class="clearer"></div>
</div>