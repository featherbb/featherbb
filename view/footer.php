<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

?>
</div>

<div id="brdfooter" class="block">
	<h2><span><?php _e('Board footer') ?></span></h2>
	<div class="box">
<?php

if (isset($footer_style) && ($footer_style == 'viewforum' || $footer_style == 'viewtopic') && $is_admmod) {
    echo "\t\t".'<div id="modcontrols" class="inbox">'."\n";

    if ($footer_style == 'viewforum') {
        echo "\t\t\t".'<dl>'."\n";
        echo "\t\t\t\t".'<dt><strong>'.__('Mod controls').'</strong></dt>'."\n";
        echo "\t\t\t\t".'<dd><span><a href="'.get('moderate/forum/'.$forum_id.'/page/'.$p.'/').'">'.__('Moderate forum').'</a></span></dd>'."\n";
        echo "\t\t\t".'</dl>'."\n";
    } elseif ($footer_style == 'viewtopic') {
        if (isset($pid)) {
            $parameter = 'param/'.$pid.'/';
        } elseif (isset($p) && $p != 1) {
            $parameter = 'param/'.$p.'/';
        } else {
            $parameter = '';
        }


        echo "\t\t\t".'<dl>'."\n";
        echo "\t\t\t\t".'<dt><strong>'.__('Mod controls').'</strong></dt>'."\n";
        // TODO: all
        //echo "\t\t\t\t".'<dd><span><a href="'.get('moderate/topic/'.$id.'/forum/'.$forum_id.'/action/moderate/param/'.$p).'">'.__('Moderate topic').'</a>'.($num_pages > 1 ? ' (<a href="'.get('moderate/topic/'.$id.'/forum/'.$forum_id.'/action/moderate/'.$parameter.'/all/').'">'.__('All').'</a>)' : '').'</span></dd>'."\n";
        echo "\t\t\t\t".'<dd><span><a href="'.get('moderate/topic/'.$id.'/forum/'.$forum_id.'/action/moderate/page/'.$p.'/').'">'.__('Moderate topic').'</a></span></dd>'."\n";
        echo "\t\t\t\t".'<dd><span><a href="'.get('moderate/topic/'.$id.'/forum/'.$forum_id.'/action/move/'.$parameter).'">'.__('Move topic').'</a></span></dd>'."\n";

        if ($cur_topic['closed'] == '1') {
            echo "\t\t\t\t".'<dd><span><a href="'.get('moderate/topic/'.$id.'/forum/'.$forum_id.'/action/open/'.$parameter).'">'.__('Open topic').'</a></span></dd>'."\n";
        } else {
            echo "\t\t\t\t".'<dd><span><a href="'.get('moderate/topic/'.$id.'/forum/'.$forum_id.'/action/close/'.$parameter).'">'.__('Close topic').'</a></span></dd>'."\n";
        }

        if ($cur_topic['sticky'] == '1') {
            echo "\t\t\t\t".'<dd><span><a href="'.get('moderate/topic/'.$id.'/forum/'.$forum_id.'/action/unstick/'.$parameter).'">'.__('Unstick topic').'</a></span></dd>'."\n";
        } else {
            echo "\t\t\t\t".'<dd><span><a href="'.get('moderate/topic/'.$id.'/forum/'.$forum_id.'/action/stick/'.$parameter).'">'.__('Stick topic').'</a></span></dd>'."\n";
        }

        echo "\t\t\t".'</dl>'."\n";
    }

    echo "\t\t\t".'<div class="clearer"></div>'."\n\t\t".'</div>'."\n";
}

?>
		<div id="brdfooternav" class="inbox">

<?php
// Display the "Jump to" drop list
if ($feather_config['o_quickjump'] == '1' && !empty($quickjump)) { ?>
			<div class="conl">
			<form id="qjump" method="get" action="">
				<div><label><span><?php _e('Jump to') ?><br /></span></label>
					<select name="id" onchange="window.location=('<?php echo get('forum/') ?>'+this.options[this.selectedIndex].value)">
<?php
		foreach ($quickjump[(int) $feather->user->g_id] as $cat_id => $cat_data) {
			echo "\t\t\t\t\t\t\t".'<optgroup label="'.feather_escape($cat_data['cat_name']).'">'."\n";
			foreach ($cat_data['cat_forums'] as $forum) {
				echo "\t\t\t\t\t\t\t\t".'<option value="'.$forum['forum_id'].'/'.$feather->url->url_friendly($forum['forum_name']).'"'.($forum_id == 2 ? ' selected="selected"' : '').'>'.$forum['forum_name'].'</option>'."\n";
			}
			echo "\t\t\t\t\t\t\t".'</optgroup>'."\n";
		} ?>
					</select>
					<noscript><input type="submit" value="<?php _e('Go') ?>" accesskey="g" /></noscript>
				</div>
			</form>
			</div>
<?php } ?>

			<div class="conr">
<?php

if ($footer_style == 'index') {
    if ($feather_config['o_feed_type'] == '1') {
        echo "\t\t\t\t".'<p id="feedlinks"><span class="rss"><a href="'.$feather->url->base().'/extern.php?action=feed&amp;type=rss">'.__('RSS active topics feed').'</a></span></p>'."\n";
    } elseif ($feather_config['o_feed_type'] == '2') {
        echo "\t\t\t\t".'<p id="feedlinks"><span class="atom"><a href="'.$feather->url->base().'/extern.php?action=feed&amp;type=atom">'.__('Atom active topics feed').'</a></span></p>'."\n";
    }
} elseif ($footer_style == 'viewforum') {
    if ($feather_config['o_feed_type'] == '1') {
        echo "\t\t\t\t".'<p id="feedlinks"><span class="rss"><a href="'.$feather->url->base().'/extern.php?action=feed&amp;fid='.$forum_id.'&amp;type=rss">'.__('RSS forum feed').'</a></span></p>'."\n";
    } elseif ($feather_config['o_feed_type'] == '2') {
        echo "\t\t\t\t".'<p id="feedlinks"><span class="atom"><a href="'.$feather->url->base().'/extern.php?action=feed&amp;fid='.$forum_id.'&amp;type=atom">'.__('Atom forum feed').'</a></span></p>'."\n";
    }
} elseif ($footer_style == 'viewtopic') {
    if ($feather_config['o_feed_type'] == '1') {
        echo "\t\t\t\t".'<p id="feedlinks"><span class="rss"><a href="'.$feather->url->base().'/extern.php?action=feed&amp;tid='.$id.'&amp;type=rss">'.__('RSS topic feed').'</a></span></p>'."\n";
    } elseif ($feather_config['o_feed_type'] == '2') {
        echo "\t\t\t\t".'<p id="feedlinks"><span class="atom"><a href="'.$feather->url->base().'/extern.php?action=feed&amp;tid='.$id.'&amp;type=atom">'.__('Atom topic feed').'</a></span></p>'."\n";
    }
}

?>
				<p id="poweredby"><?php printf(__('Powered by'), '<a href="http://featherbb.org/">FeatherBB</a>'.(($feather_config['o_show_version'] == '1') ? ' '.$feather_config['o_cur_version'] : '')) ?></p>
			</div>
			<div class="clearer"></div>
		</div>
	</div>
</div>
<?php

// Display debug info (if enabled/defined)
if ($feather->forum_env['FEATHER_SHOW_INFO']) {
    $feather->debug->info();
}

// Display executed queries (if enabled)
if ($feather->forum_env['FEATHER_SHOW_QUERIES']) {
    $feather->debug->queries();
}
?>

</section>

<script src="<?php echo $feather->url->base() ?>/style/themes/FeatherBB/phone.min.js"></script>

</body>
</html>
