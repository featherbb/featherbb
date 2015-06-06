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
	<div class="blockform">
		<h2><span><?php echo pun_htmlspecialchars($user['username']).' - '.$lang_profile['Section admin'] ?></span></h2>
		<div class="box">
			<form id="profile7" method="post" action="profile.php?section=admin&amp;id=<?php echo $id ?>">
				<div class="inform">
				<input type="hidden" name="form_sent" value="1" />
					<fieldset>
<?php

		if ($pun_user['g_moderator'] == '1')
		{

?>
						<legend><?php echo $lang_profile['Delete ban legend'] ?></legend>
						<div class="infldset">
							<p><input type="submit" name="ban" value="<?php echo $lang_profile['Ban user'] ?>" /></p>
						</div>
					</fieldset>
				</div>
<?php

		}
		else
		{
			if ($pun_user['id'] != $id)
			{

?>
						<legend><?php echo $lang_profile['Group membership legend'] ?></legend>
						<div class="infldset">
							<select id="group_id" name="group_id">
<?php
							get_group_list($user);
?>
							</select>
							<input type="submit" name="update_group_membership" value="<?php echo $lang_profile['Save'] ?>" />
						</div>
					</fieldset>
				</div>
				<div class="inform">
					<fieldset>
<?php

			}

?>
						<legend><?php echo $lang_profile['Delete ban legend'] ?></legend>
						<div class="infldset">
							<input type="submit" name="delete_user" value="<?php echo $lang_profile['Delete user'] ?>" /> <input type="submit" name="ban" value="<?php echo $lang_profile['Ban user'] ?>" />
						</div>
					</fieldset>
				</div>
<?php

			if ($user['g_moderator'] == '1' || $user['g_id'] == PUN_ADMIN)
			{

?>
				<div class="inform">
					<fieldset>
						<legend><?php echo $lang_profile['Set mods legend'] ?></legend>
						<div class="infldset">
							<p><?php echo $lang_profile['Moderator in info'] ?></p>
<?php
								get_forum_list($id);
?>
								</div>
							</div>
							<br class="clearb" /><input type="submit" name="update_forums" value="<?php echo $lang_profile['Update forums'] ?>" />
						</div>
					</fieldset>
				</div>
<?php

			}
		}

?>
			</form>
		</div>
	</div>
<?php
endif;
?>
	<div class="clearer"></div>
</div>