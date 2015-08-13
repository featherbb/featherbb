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
	<h2><span><?php echo __('New reports head') ?></span></h2>
	<div class="box">
		<form method="post" action="<?php echo get_link('admin/reports/') ?>">
			<input type="hidden" name="<?php echo $csrf_key; ?>" value="<?php echo $csrf_token; ?>">
			<?php
			if (!empty($report_data)) {
				foreach ($report_data as $report) {
					?>
					<div class="inform">
						<fieldset>
							<legend><?php printf(__('Report subhead'), format_time($report['created'])) ?></legend>
							<div class="infldset">
								<table class="aligntop">
									<tr>
										<th scope="row"><?php printf(__('Reported by'), ($report['reporter'] != '') ? '<a href="'.get_link('users/'.$report['reported_by'].'/').'">'.feather_escape($report['reporter']).'</a>' : __('Deleted user')) ?></th>
										<td class="location"><?php echo breadcrumbs(array($report['forum_name'] => get_link('forum/'.$report['forum_id'].'/'.url_friendly($report['forum_name']).'/'),
												$report['subject'] => get_link('forum/'.$report['topic_id'].'/'.url_friendly($report['subject'])),
												sprintf(__('Post ID'), $report['pid']) => get_link('post/'.$report['pid'].'/#p'.$report['pid']))) ?></td>
									</tr>
									<tr>
										<th scope="row"><?php echo __('Reason') ?><div><input type="submit" name="zap_id[<?php echo $report['id'] ?>]" value="<?php echo __('Zap') ?>" /></div></th>
										<td><?php echo str_replace("\n", '<br />', feather_escape($report['message'])) ?></td>
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
						<legend><?php echo __('None') ?></legend>
						<div class="infldset">
							<p><?php echo __('No new reports') ?></p>
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
	<h2><span><?php echo __('Last 10 head') ?></span></h2>
	<div class="box">
		<div class="fakeform">
			<?php

			if (!empty($report_zapped_data)) {
				foreach ($report_zapped_data as $report) {
					?>
					<div class="inform">
						<fieldset>
							<legend><?php printf(__('Zapped subhead'), format_time($report['zapped']), ($report['zapped_by'] != '') ? '<a href="'.get_link('user/'.$report['zapped_by_id'].'/').'">'.feather_escape($report['zapped_by']).'</a>' : __('NA')) ?></legend>
							<div class="infldset">
								<table class="aligntop">
									<tr>
										<th scope="row"><?php printf(__('Reported by'), ($report['reporter'] != '') ? '<a href="'.get_link('users/'.$report['reported_by'].'/').'">'.feather_escape($report['reporter']).'</a>' : __('Deleted user')) ?></th>
										<td class="location"><?php echo breadcrumbs(array($report['forum_name'] => get_link('forum/'.$report['forum_id'].'/'.url_friendly($report['forum_name']).'/'),
												$report['subject'] => get_link('forum/'.$report['topic_id'].'/'.url_friendly($report['subject'])),
												sprintf(__('Post ID'), $report['pid']) => get_link('post/'.$report['pid'].'/#p'.$report['pid']))) ?></td>
									</tr>
									<tr>
										<th scope="row"><?php echo __('Reason') ?></th>
										<td><?php echo str_replace("\n", '<br />', feather_escape($report['message'])) ?></td>
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
						<legend><?php echo __('None') ?></legend>
						<div class="infldset">
							<p><?php echo __('No zapped reports') ?></p>
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
