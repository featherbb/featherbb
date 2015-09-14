<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace FeatherBB\Plugins\Controller;


use FeatherBB\Core\Url;
use FeatherBB\Core\Utils;
use FeatherBB\Core\Error;
use FeatherBB\Core\Track;
use DB;

class PrivateMessages
{
    public function __construct()
    {
        $this->feather = \Slim\Slim::getInstance();
        $this->model = new \FeatherBB\Plugins\Model\PrivateMessages();
        load_textdomain('private_messages', dirname(dirname(__FILE__)).'/lang/'.$this->feather->user->language.'/private-messages.mo');
    }


    public function index($fid = 2, $page = 1)
    {
        // Set default page to "Inbox" folder
        $fid = !empty($fid) ? intval($fid) : 2;
        $uid = intval($this->feather->user->id);
        // Check if current user owns the folder
        if (!$inbox = $this->model->checkFolderOwner($fid, $uid)) {
            throw new Error(__('Wrong folder owner'), 403);
        }
        // echo $inbox->name;

        $nbMessages = $this->model->countMessages($fid, $uid);

        $num_pages = ceil($nbMessages / $this->feather->user['disp_topics']);

        $p = (!isset($page) || $page <= 1 || $page > $num_pages) ? 1 : intval($page);
        $start_from = $this->feather->user['disp_topics'] * ($p - 1);

        $paging_links = '<span class="pages-label">'.__('Pages').' </span>'.Url::paginate($num_pages, $p, $this->feather->urlFor('Conversations', ['id' => $fid]).'/#');

        $limit = $this->feather->user['disp_topics'];
        $de = $this->model->getMessages($fid, $uid, $limit, $start_from);
        var_dump($de);
        //
        // $data = array(
        // 	':uid'	=>	$this->feather->user['id'],
        // 	':fid'	=>	$box_id,
        // 	':start'=>	$start_from,
        // );

        // var_dump($this->feather->user);
        // ->name('FP.pms.Conversations');
    }


}
