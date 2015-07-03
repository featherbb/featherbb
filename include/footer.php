<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

// Make sure no one attempts to run this script "directly"
if (!defined('PUN')) {
    exit;
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