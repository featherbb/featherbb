<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */
 
use FeatherBB\Core\Utils;
use FeatherBB\Core\Url;

// Make sure no one attempts to run this script "directly"
if (!defined('FEATHER')) {
    exit;
}

?>
<div id="viewprofile" class="block">
	<h2><span><?php _e('Profile') ?></span></h2>
	<div class="box">
		<div class="fakeform">
			<div class="inform">
				<fieldset>
				<legend><?php _e('Section personal') ?></legend>
					<div class="infldset">
						<dl>
							<?php echo implode("\n\t\t\t\t\t\t\t", $user_info['personal'])."\n" ?>
						</dl>
						<div class="clearer"></div>
					</div>
				</fieldset>
			</div>
<?php if (!empty($user_info['messaging'])): ?>			<div class="inform">
				<fieldset>
				<legend><?php _e('Section messaging') ?></legend>
					<div class="infldset">
						<dl>
							<?php echo implode("\n\t\t\t\t\t\t\t", $user_info['messaging'])."\n" ?>
						</dl>
						<div class="clearer"></div>
					</div>
				</fieldset>
			</div>
<?php endif; if (!empty($user_info['personality'])): ?>			<div class="inform">
				<fieldset>
				<legend><?php _e('Section personality') ?></legend>
					<div class="infldset">
						<dl>
							<?php echo implode("\n\t\t\t\t\t\t\t", $user_info['personality'])."\n" ?>
						</dl>
						<div class="clearer"></div>
					</div>
				</fieldset>
			</div>
<?php endif; ?>			<div class="inform">
				<fieldset>
				<legend><?php _e('User activity') ?></legend>
					<div class="infldset">
						<dl>
							<?php echo implode("\n\t\t\t\t\t\t\t", $user_info['activity'])."\n" ?>
						</dl>
						<div class="clearer"></div>
					</div>
				</fieldset>
			</div>
		</div>
	</div>
</div>