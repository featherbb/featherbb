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
    protected $feather, $request, $model;

    public function __construct()
    {
        $this->feather = \Slim\Slim::getInstance();
        $this->request = $this->feather->request;
        $this->model = new \FeatherBB\Plugins\Model\PrivateMessages();
        load_textdomain('private_messages', dirname(dirname(__FILE__)).'/lang/'.$this->feather->user->language.'/private-messages.mo');
        $this->feather->template->addTemplatesDirectory(dirname(dirname(__FILE__)).'/Views', 5);
    }


    public function index($fid = 2, $page = 1)
    {
        // Set default page to "Inbox" folder
        $fid = !empty($fid) ? intval($fid) : 2;
        $uid = intval($this->feather->user->id);

        // Check if current user owns the folder
        if (!$inbox = $this->model->checkFolderOwner($fid, $uid)) {
            throw new Error(__('Wrong folder owner', 'private_messages'), 403);
        }

        // Display delete confirm form
        if ($this->request->post('delete')) {
            $this->delete();
        }

        // Get all inboxes owned by the user and count messages in it
        $inboxes = $this->model->getUserFolders($uid);
        foreach ($inboxes as $inbox) {
            $inbox->nbMsg = $this->model->countMessages($inbox->id, $uid);
        }


        // Page data
        $num_pages = ceil($inboxes[$fid]->nbMsg / $this->feather->user['disp_topics']);
        $p = (!isset($page) || $page <= 1 || $page > $num_pages) ? 1 : intval($page);
        $start_from = $this->feather->user['disp_topics'] * ($p - 1);
        $paging_links = '<span class="pages-label">'.__('Pages').' </span>'.Url::paginate($num_pages, $p, $this->feather->urlFor('Conversations', ['id' => $fid]).'/#');

        // Fetch conversations from current inbox
        $limit = $this->feather->user['disp_topics'];
        $messages = $this->model->getMessages($fid, $uid, $limit, $start_from);

        // And display the view
        $this->feather->template
            ->setPageInfo(array(
                'title' => array(Utils::escape($this->feather->config['o_board_title']), __('PMS', 'private_messages'), $inbox->name),
                'active_page' => 'navextra1',
                'admin_console' => true,
                'inboxes' => $inboxes,
                'current_inbox' => $inboxes[$fid-1]
            )
        )
        ->addTemplate('menu.php')
        ->addTemplate('index.php')->display();
    }

    public function move($fid = 2, $page = 1)
    {
        echo $fid."ok";
        return;
    }

    public function delete()
    {
        if (!$this->request->post('topics'))
    		throw new Error(__('Select more than one topic', 'private_messages'), 403);

    	$topics = $this->request->post('topics') && is_array($this->request->post('topics')) ? array_map('intval', $this->request->post('topics')) : array_map('intval', explode(',', $this->request->post('topics')));

    	if (empty($topics))
    		throw new Error(__('Select more than one topic', 'private_messages'), 403);

    	if ( $this->request->post('delete_comply') )
    	{
            // TODO: replace with CSRF
    		// confirm_referrer('pms_inbox.php');

            $uid = intval($this->feather->user->id);
            $this->model->delete($topics, $uid);

    		redirect(panther_link($panther_url['inbox']), $lang_pm['Messages deleted']);
    	}
    	// else
    	// {
    	// 	$page_title = array(panther_htmlspecialchars($panther_config['o_board_title']), $lang_common['PM'], $lang_pm['PM Inbox']);
    	// 	define('PANTHER_ACTIVE_PAGE', 'pm');
    	// 	require PANTHER_ROOT.'header.php';
        //
    	// 	$pm_tpl = panther_template('delete_messages.tpl');
    	// 	$search = array(
    	// 		'{index_link}' => panther_link($panther_url['index']),
    	// 		'{index}' => $lang_common['Index'],
    	// 		'{inbox_link}' => panther_link($panther_url['inbox']),
    	// 		'{inbox}' => $lang_common['PM'],
    	// 		'{my_messages}' => $lang_pm['My messages'],
    	// 		'{send_message_link}' => panther_link($panther_url['send_message']),
    	// 		'{send_message}' => $lang_pm['Send message'],
    	// 		'{pm_menu}' => generate_pm_menu(),
    	// 		'{form_action}' => panther_link($panther_url['inbox']),
    	// 		'{topics}' => implode(',', $topics),
    	// 		'{delete_messages_comply}' => $lang_pm['Delete messages comply'],
    	// 		'{delete}' => $lang_pm['Delete button'],
    	// 		'{go_back}' => $lang_common['Go back'],
    	// 		'{csrf_token}' => generate_csrf_token(),
    	// 	);
        //
    	// 	echo str_replace(array_keys($search), array_values($search), $pm_tpl);
    	// 	require PANTHER_ROOT.'footer.php';
    	// }
    }

    public function update($fid = 2, $page = 1)
    {

    }

}
