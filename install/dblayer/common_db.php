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


// Load the appropriate DB layer class
switch ($db_type) {
    case 'mysql':
        require_once FEATHER_ROOT.'install/dblayer/mysql.php';
        break;

    case 'mysql_innodb':
        require_once FEATHER_ROOT.'install/dblayer/mysql_innodb.php';
        break;

    case 'mysqli':
        require_once FEATHER_ROOT.'install/dblayer/mysqli.php';
        break;

    case 'mysqli_innodb':
        require_once FEATHER_ROOT.'install/dblayer/mysqli_innodb.php';
        break;

    case 'pgsql':
        require_once FEATHER_ROOT.'install/dblayer/pgsql.php';
        break;

    case 'sqlite':
        require_once FEATHER_ROOT.'install/dblayer/sqlite.php';
        break;
        
    case 'sqlite3':
        require_once FEATHER_ROOT.'install/dblayer/sqlite3.php';
        break;

    default:
        error('\''.$db_type.'\' is not a valid database type. Please check settings in config.php.', __FILE__, __LINE__);
        break;
}