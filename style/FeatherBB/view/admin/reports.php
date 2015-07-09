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
		<h2><span><?php echo $lang_admin_reports['New reports head'] ?></span></h2>
		<div class="box">
			<form method="post" action="<?php echo get_link('admin/reports/') ?>">
<?php
if ($is_report) {
    foreach ($report_data as $report) {
        ?>
				<div class="inform">
					<fieldset>
						<legend><?php printf($lang_admin_reports['Report subhead'], format_time($report['created'])) ?></legend>
						<div class="infldset">
							<table class="aligntop">
								<tr>
									<th scope="row"><?php printf($lang_admin_reports['Reported by'], $report['reporter_disp']) ?></th>
									<td class="location"><?php echo implode(' ', $report['report_location']) ?></td>
								</tr>
								<tr>
									<th scope="row"><?php echo $lang_admin_reports['Reason'] ?><div><input type="submit" name="zap_id[<?php echo $report['id'] ?>]" value="<?php echo $lang_admin_reports['Zap'] ?>" /></div></th>
									<td><?php echo $report['post'] ?></td>
								</tr>
							</table>
						</div>
					</fieldset>
				</div>
<?php

    }
} else {
    ?>
				<div class="inform">
					<fieldset>
						<legend><?php echo $lang_admin_common['None'] ?></legend>
						<div class="infldset">
							<p><?php echo $lang_admin_reports['No new reports'] ?></p>
						</div>
					</fieldset>
				</div>
<?php

}

?>
			</form>
		</div>
	</div>

	<div class="blockform block2">
		<h2><span><?php echo $lang_admin_reports['Last 10 head'] ?></span></h2>
		<div class="box">
			<div class="fakeform">
<?php

if ($is_report_zapped) {
    foreach ($report_zapped_data as $report) {
        ?>
				<div class="inform">
					<fieldset>
						<legend><?php printf($lang_admin_reports['Zapped subhead'], format_time($report['zapped']), $report['zapped_by_disp']) ?></legend>
						<div class="infldset">
							<table class="aligntop">
								<tr>
									<th scope="row"><?php printf($lang_admin_reports['Reported by'], $report['reporter_disp']) ?></th>
									<td class="location"><?php echo implode(' ', $report['report_location']) ?></td>
								</tr>
								<tr>
									<th scope="row"><?php echo $lang_admin_reports['Reason'] ?></th>
									<td><?php echo $report['post'] ?></td>
								</tr>
							</table>
						</div>
					</fieldset>
				</div>
<?php

    }
} else {
    ?>
				<div class="inform">
					<fieldset>
						<legend><?php echo $lang_admin_common['None'] ?></legend>
						<div class="infldset">
							<p><?php echo $lang_admin_reports['No zapped reports'] ?></p>
						</div>
					</fieldset>
				</div>
<?php

}

?>
			</div>
		</div>
	</div>
	<div class="clearer"></div>
</div>