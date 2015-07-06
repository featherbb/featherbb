<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace controller\admin;

class reports
{
    public function __construct()
    {
        $this->feather = \Slim\Slim::getInstance();
        $this->db = $this->feather->db;
        $this->start = $this->feather->start;
        $this->config = $this->feather->config;
        $this->user = $this->feather->user;
    }
    
    public function display()
    {
        global $lang_common, $lang_admin_common, $lang_admin_reports, $feather_config, $feather_user, $db;

        require FEATHER_ROOT.'include/common_admin.php';

        if (!$feather_user['is_admmod']) {
            message($lang_common['No permission'], false, '403 Forbidden');
        }

        define('FEATHER_ADMIN_CONSOLE', 1);

        // Load the admin_options.php language file
        require FEATHER_ROOT.'lang/'.$admin_language.'/reports.php';

        // Load the report.php model file
        require FEATHER_ROOT.'model/admin/reports.php';

        // Zap a report
        if ($this->feather->request->isPost()) {
            zap_report($this->feather);
        }

        $page_title = array(pun_htmlspecialchars($feather_config['o_board_title']), $lang_admin_common['Admin'], $lang_admin_common['Reports']);

        define('FEATHER_ACTIVE_PAGE', 'admin');

        require FEATHER_ROOT.'include/header.php';

        generate_admin_menu('reports');

        $this->feather->render('admin/reports.php', array(
                'lang_admin_reports'    =>    $lang_admin_reports,
                'lang_admin_common'    =>    $lang_admin_common,
                'is_report'    =>    check_reports(),
                'is_report_zapped'    =>    check_zapped_reports(),
                'report_data'   =>  get_reports(),
                'report_zapped_data'   =>  get_zapped_reports(),
            )
        );

        require FEATHER_ROOT.'include/footer.php';
    }
}
