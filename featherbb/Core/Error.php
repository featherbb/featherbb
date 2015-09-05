<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace FeatherBB;

class Error extends \Exception
{
    public function __construct($message, $code = 200, $no_back_link = false)
    {
        $this->feather = \Slim\Slim::getInstance();
        return $this->error($message, $code, $no_back_link);
    }

    protected function error($msg, $http_status = null, $no_back_link)
    {
        // Did we receive a custom header?
        if (!is_null($http_status)) {
            header('HTTP/1.1 ' . $http_status);
        }

        $http_status = (int) $http_status;
        if ($http_status > 0) {
            $this->feather->response->setStatus($http_status);
        }

        // Overwrite existing body
        $this->feather->response->setBody('');

        if (!defined('FEATHER_HEADER')) {
            $this->feather->view2->setPageInfo(array(
                'title' => array(\FeatherBB\Utils::escape($this->feather->config['o_board_title']), __('Info')),
            ));
        }

        $this->feather->view2->setPageInfo(array(
            'msg'    =>    $msg,
            'no_back_link'    => $no_back_link,
        ))->addTemplate('message.php')->display();

        // Don't display anything after a message
        $this->feather->stop();
    }
}
