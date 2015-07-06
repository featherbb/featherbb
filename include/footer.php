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

// Render the footer
if (isset($this)) {
    
    // If no footer style has been specified, we use the default (only copyright/debug info)
    $footer_style = isset($footer_style) ? $footer_style : null;
    
    $id = isset($id) ? $id : null;
    $p = isset($p) ? $p : null;
    $pid = isset($pid) ? $pid : null;
    
    $forum_id = isset($forum_id) ? $forum_id : null;
    
    $num_pages = isset($num_pages) ? $num_pages : null;
    
    $this->feather->render('footer.php', array(
                        'lang_common' => $lang_common,
                        'id' => $id,
                        'p' => $p,
                        'pid' => $pid,
                        'feather_user' => $this->user,
                        'feather_config' => $this->config,
                        'feather_start' => $this->start,
                        'footer_style' => $footer_style,
                        'forum_id' => $forum_id,
                        'num_pages' => $num_pages,
                        'feather' => $this->feather,
                        )
                );
}

// End the transaction
$db->end_transaction();


// Close the db connection (and free up any result data)
$db->close();

// If we reached this far, we shouldn't execute more code
if (isset($this)) {
    $this->feather->stop();
}
else {
    $feather = \Slim\Slim::getInstance();
    $feather->stop();
}