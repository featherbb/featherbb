<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace FeatherBB\Core;

class Error extends \Exception
{
    protected $backlink;

    public function __construct($message, $code = 400, $backlink = true)
    {
        parent::__construct($message, $code);
        $this->backlink = (bool) $backlink;
        $this->feather = \Slim\Slim::getInstance();
        $this->display($message, $code);
    }

    public function hasBacklink()
    {
        return $this->backlink;
    }

    private function display($message, $code)
    {
        $error = array(
            'code' => $code,
            'message' => $message,
            'back' => true,
        );

        // Hide internal mechanism
        /*if (!in_array(get_class($e), array('FeatherBB\Core\Error'))) {
            $error['message'] = 'There was an internal error'; // TODO : translation
        }

        if (method_exists($e, 'hasBacklink')) {
            $error['back'] = $e->hasBacklink();
        }*/
        $this->feather->response->setStatus($code);
        $this->feather->response->setBody(''); // Reset buffer
        $this->feather->template->setPageInfo(array(
            'title' => array(\FeatherBB\Core\Utils::escape($this->feather->forum_settings['o_board_title']), __('Error')),
            'msg'    =>    $error['message'],
            'backlink'    => $error['back'],
        ))->addTemplate('error.php')->display();
        $this->feather->stop();
    }
}
