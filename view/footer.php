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
	<h2><span><?php echo $lang_common['Board footer'] ?></span></h2>
	<div class="box">
<?php

if (isset($footer_style) && ($footer_style == 'viewforum' || $footer_style == 'viewtopic') && $is_admmod) {
    echo "\t\t".'<div id="modcontrols" class="inbox">'."\n";

    if ($footer_style == 'viewforum') {
        echo "\t\t\t".'<dl>'."\n";
        echo "\t\t\t\t".'<dt><strong>'.$lang_forum['Mod controls'].'</strong></dt>'."\n";
        echo "\t\t\t\t".'<dd><span><a href="'.get_link('moderate/forum/'.$forum_id.'/page/'.$p.'/').'">'.$lang_common['Moderate forum'].'</a></span></dd>'."\n";
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
        echo "\t\t\t\t".'<dt><strong>'.$lang_topic['Mod controls'].'</strong></dt>'."\n";
        // TODO: all
        //echo "\t\t\t\t".'<dd><span><a href="'.get_link('moderate/topic/'.$id.'/forum/'.$forum_id.'/action/moderate/param/'.$p).'">'.$lang_common['Moderate topic'].'</a>'.($num_pages > 1 ? ' (<a href="'.get_link('moderate/topic/'.$id.'/forum/'.$forum_id.'/action/moderate/'.$parameter.'/all/').'">'.$lang_common['All'].'</a>)' : '').'</span></dd>'."\n";
        echo "\t\t\t\t".'<dd><span><a href="'.get_link('moderate/topic/'.$id.'/forum/'.$forum_id.'/action/moderate/page/'.$p.'/').'">'.$lang_common['Moderate topic'].'</a></span></dd>'."\n";
        echo "\t\t\t\t".'<dd><span><a href="'.get_link('moderate/topic/'.$id.'/forum/'.$forum_id.'/action/move/'.$parameter).'">'.$lang_common['Move topic'].'</a></span></dd>'."\n";

        if ($cur_topic['closed'] == '1') {
            echo "\t\t\t\t".'<dd><span><a href="'.get_link('moderate/topic/'.$id.'/forum/'.$forum_id.'/action/open/'.$parameter).'">'.$lang_common['Open topic'].'</a></span></dd>'."\n";
        } else {
            echo "\t\t\t\t".'<dd><span><a href="'.get_link('moderate/topic/'.$id.'/forum/'.$forum_id.'/action/close/'.$parameter).'">'.$lang_common['Close topic'].'</a></span></dd>'."\n";
        }

        if ($cur_topic['sticky'] == '1') {
            echo "\t\t\t\t".'<dd><span><a href="'.get_link('moderate/topic/'.$id.'/forum/'.$forum_id.'/action/unstick/'.$parameter).'">'.$lang_common['Unstick topic'].'</a></span></dd>'."\n";
        } else {
            echo "\t\t\t\t".'<dd><span><a href="'.get_link('moderate/topic/'.$id.'/forum/'.$forum_id.'/action/stick/'.$parameter).'">'.$lang_common['Stick topic'].'</a></span></dd>'."\n";
        }

        echo "\t\t\t".'</dl>'."\n";
    }

    echo "\t\t\t".'<div class="clearer"></div>'."\n\t\t".'</div>'."\n";
}

?>
		<div id="brdfooternav" class="inbox">
<?php

echo "\t\t\t".'<div class="conl">'."\n";

// Display the "Jump to" drop list
if ($feather_config['o_quickjump'] == '1') {
    // Load cached quick jump
    if (file_exists(FORUM_CACHE_DIR.'cache_quickjump_'.$feather->user->g_id.'.php')) {
        include FORUM_CACHE_DIR.'cache_quickjump_'.$feather->user->g_id.'.php';
    }

    if (!defined('FEATHER_QJ_LOADED')) {
        if (!defined('FORUM_CACHE_FUNCTIONS_LOADED')) {
            require FEATHER_ROOT.'include/cache.php';
        }

        generate_quickjump_cache($feather->user->g_id);
        require FORUM_CACHE_DIR.'cache_quickjump_'.$feather->user->g_id.'.php';
    }
}

echo "\t\t\t".'</div>'."\n";

?>
			<div class="conr">
<?php

if ($footer_style == 'index') {
    if ($feather_config['o_feed_type'] == '1') {
        echo "\t\t\t\t".'<p id="feedlinks"><span class="rss"><a href="'.get_base_url().'/extern.php?action=feed&amp;type=rss">'.$lang_common['RSS active topics feed'].'</a></span></p>'."\n";
    } elseif ($feather_config['o_feed_type'] == '2') {
        echo "\t\t\t\t".'<p id="feedlinks"><span class="atom"><a href="'.get_base_url().'/extern.php?action=feed&amp;type=atom">'.$lang_common['Atom active topics feed'].'</a></span></p>'."\n";
    }
} elseif ($footer_style == 'viewforum') {
    if ($feather_config['o_feed_type'] == '1') {
        echo "\t\t\t\t".'<p id="feedlinks"><span class="rss"><a href="'.get_base_url().'/extern.php?action=feed&amp;fid='.$forum_id.'&amp;type=rss">'.$lang_common['RSS forum feed'].'</a></span></p>'."\n";
    } elseif ($feather_config['o_feed_type'] == '2') {
        echo "\t\t\t\t".'<p id="feedlinks"><span class="atom"><a href="'.get_base_url().'/extern.php?action=feed&amp;fid='.$forum_id.'&amp;type=atom">'.$lang_common['Atom forum feed'].'</a></span></p>'."\n";
    }
} elseif ($footer_style == 'viewtopic') {
    if ($feather_config['o_feed_type'] == '1') {
        echo "\t\t\t\t".'<p id="feedlinks"><span class="rss"><a href="'.get_base_url().'/extern.php?action=feed&amp;tid='.$id.'&amp;type=rss">'.$lang_common['RSS topic feed'].'</a></span></p>'."\n";
    } elseif ($feather_config['o_feed_type'] == '2') {
        echo "\t\t\t\t".'<p id="feedlinks"><span class="atom"><a href="'.get_base_url().'/extern.php?action=feed&amp;tid='.$id.'&amp;type=atom">'.$lang_common['Atom topic feed'].'</a></span></p>'."\n";
    }
}

?>
				<p id="poweredby"><?php printf($lang_common['Powered by'], '<a href="http://featherbb.org/">FeatherBB</a>'.(($feather_config['o_show_version'] == '1') ? ' '.$feather_config['o_cur_version'] : '')) ?></p>
			</div>
			<div class="clearer"></div>
		</div>
	</div>
</div>
<?php

// Display debug info (if enabled/defined)
if (defined('FEATHER_DEBUG')) {
    echo '<p id="debugtime">[ ';

    // Calculate script generation time
    $time_diff = sprintf('%.3f', get_microtime() - $feather_start);
    echo sprintf($lang_common['Querytime'], $time_diff, count(\DB::get_query_log()[0]));

    if (function_exists('memory_get_usage')) {
        echo ' - '.sprintf($lang_common['Memory usage'], file_size(memory_get_usage()));

        if (function_exists('memory_get_peak_usage')) {
            echo ' '.sprintf($lang_common['Peak usage'], file_size(memory_get_peak_usage()));
        }
    }

    echo ' ]</p>'."\n";
}
// Display executed queries (if enabled)
if (defined('FEATHER_SHOW_QUERIES')) {
    display_saved_queries();
}
?>
</div>
<div class="end-box"></div>
</div>

</body>
</html>
