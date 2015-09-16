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

        // // Display delete confirm form
        // if ($this->request->post('delete')) {
        //     $this->delete();
        // }

        // Get all inboxes owned by the user and count messages in it
        if ($inboxes = $this->model->getUserFolders($uid)) {
            if (!in_array($fid, array_keys($inboxes))) {
                throw new Error(__('Wrong folder owner', 'private_messages'), 403);
            }
            foreach ($inboxes as $iid => $data) {
                $inboxes[$iid]['nb_msg'] = $this->model->countMessages($iid, $uid);
            }
        } else {
            throw new Error('No inbox', 404);
        }

        // Page data
        $num_pages = ceil($inboxes[$fid]['nb_msg'] / $this->feather->user['disp_topics']);
        $p = (!isset($page) || $page <= 1 || $page > $num_pages) ? 1 : intval($page);
        $start_from = $this->feather->user['disp_topics'] * ($p - 1);
        $paging_links = Url::paginate($num_pages, $p, $this->feather->urlFor('Conversations.home', ['id' => $fid]).'/#');

        $this->feather->template
            ->setPageInfo(array(
                'title' => array(Utils::escape($this->feather->config['o_board_title']), __('PMS', 'private_messages'), $inboxes[$fid]['name']),
                'active_page' => 'navextra1',
                'admin_console' => true,
                'inboxes' => $inboxes,
                'current_inbox_id' => $fid,
                'paging_links' => $paging_links,
                'conversations' => $this->model->getConversations($fid, $uid, $this->feather->user['disp_topics'], $start_from)
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

            $redirect_id = $this->request->post('inbox_id');
            Url::redirect($this->feather->urlFor('Conversations', ['id' => $redirect_id]), __('Conversations deleted', 'private_messages'));
    	}
    	else
    	{
            $this->feather->template
                ->setPageInfo(array(
                    'title' => array(Utils::escape($this->feather->config['o_board_title']), __('PMS', 'private_messages'), $inboxes[$fid]['name']),
                    'active_page' => 'navextra1',
                    'admin_console' => true,
                    'inboxes' => $inboxes,
                    'current_inbox_id' => $fid,
                    'paging_links' => $paging_links,
                    'conversations' => $this->model->getConversations($fid, $uid, $this->feather->user['disp_topics'], $start_from)
                )
            )
            ->addTemplate('menu.php')
            ->addTemplate('delete.php')->display();

    		$page_title = array(panther_htmlspecialchars($this->feather->forum_settings['o_board_title']), $lang_common['PM'], $lang_pm['PM Inbox']);
    		define('PANTHER_ACTIVE_PAGE', 'pm');
    		require PANTHER_ROOT.'header.php';

    		$pm_tpl = panther_template('delete_messages.tpl');
    		$search = array(
    			'{index_link}' => panther_link($panther_url['index']),
    			'{index}' => $lang_common['Index'],
    			'{inbox_link}' => panther_link($panther_url['inbox']),
    			'{inbox}' => $lang_common['PM'],
    			'{my_messages}' => $lang_pm['My messages'],
    			'{send_message_link}' => panther_link($panther_url['send_message']),
    			'{send_message}' => $lang_pm['Send message'],
    			'{pm_menu}' => generate_pm_menu(),
    			'{form_action}' => panther_link($panther_url['inbox']),
    			'{topics}' => implode(',', $topics),
    			'{delete_messages_comply}' => $lang_pm['Delete messages comply'],
    			'{delete}' => $lang_pm['Delete button'],
    			'{go_back}' => $lang_common['Go back'],
    			'{csrf_token}' => generate_csrf_token(),
    		);

    		echo str_replace(array_keys($search), array_values($search), $pm_tpl);
    		require PANTHER_ROOT.'footer.php';
    	}
    }

    public function update($fid = 2, $page = 1)
    {

    }

    public function send($uid = null, $conv_id = null)
    {
        if ($this->feather->request->isPost()) {
            // First raw validation
            $data = array_merge(array(
                'username' => null,
                'subject' => null,
                'message' => null,
                'smilies' => 0,
                'preview' => null,
            ), $this->feather->request->post());
            $data = array_map(array('FeatherBB\Core\Utils', 'trim'), $data);

            $conv = false;
            if (!is_null($conv_id)) {
                if ($conv_id < 1) {
                    throw new Error('Wrong conversation ID', 400);
                }
                if (!$conv = $this->model->getConversation($conv_id, $this->feather->user->id)) {
                    throw new Error('Unknown conversation ID', 400);
                }
            }

            if ($this->feather->request->post('preview')) {
                $msg = $this->feather->parser->parse_message($data['message'], $data['smilies']);
                $this->feather->template->setPageInfo(array(
                    'parsed_message' => $msg,
                    'username' => Utils::escape($data['username']),
                    'subject' => Utils::escape($data['subject']),
                    'message' => Utils::escape($data['message'])
                    ))->addTemplate('send.php')->display();
            } else {
                // Prevent flood
                if (!is_null($data['preview']) && $this->feather->user['last_post'] != '' && ($this->feather->now - $this->feather->user['last_post']) < $this->feather->user['g_post_flood']) {
                    throw new Error(sprintf($lang_post['Flood start'], $this->feather->user['g_post_flood'], $this->feather->user['g_post_flood'] - ($this->feather->now - $this->feather->user['last_post'])), 429);
                }

                if (!$conv) {
                    // Validate username / TODO : allow multiple usernames
                    if (!$user = $this->model->isAllowed($data['username'])) {
                        throw new Error('You can\'t send an PM to '.($data['username'] ? $data['username'] : 'nobody'), 400);
                    }

                    // Avoid self messages
                    if ($user->id == $this->feather->user->id) {
                        throw new Error('No self message', 403);
                    }

                    // Validate subject
                    if ($this->feather->forum_settings['o_censoring'] == '1')
                    $data['subject'] = Utils::trim(Utils::censor($data['subject']));
                    if (empty($data['subject'])) {
                        throw new Error('No subject or censored subject', 400);
                    } else if (Utils::strlen($data['subject']) > 70) {
                        throw new Error('Too long subject', 400);
                    } else if ($this->feather->forum_settings['p_subject_all_caps'] == '0' && Utils::is_all_uppercase($data['subject']) && !$this->feather->user->is_admmod) {
                        throw new Error('All caps subject forbidden', 400);
                    }
                }

                // TODO : inbox full

                // Validate message
                if ($this->feather->forum_settings['o_censoring'] == '1')
                $data['message'] = Utils::trim(Utils::censor($data['message']));
                if (empty($data['message'])) {
                    throw new Error('No message or censored message', 400);
                } else if (Utils::strlen($data['message']) > $this->feather->forum_env['FEATHER_MAX_POSTSIZE']) {
                    throw new Error('Too long message', 400);
                } else if ($this->feather->forum_settings['p_subject_all_caps'] == '0' && Utils::is_all_uppercase($data['subject']) && !$this->feather->user->is_admmod) {
                    throw new Error('All caps message forbidden', 400);
                }

                // Send ... TODO : when perms will be ready
                // Check if the receiver has the PM enabled
                // Check if he has reached his max limit of PM
                // Block feature ?

                if (!$conv) {
                    $conv_data = array(
                        'subject'	=>	$data['subject'],
                        'poster'	=>	$this->feather->user->username,
                        'poster_id'	=>	$this->feather->user->id,
                        'num_replies'	=>	0,
                        'last_post'	=>	$this->feather->now,
                        'last_poster'	=>	$this->feather->user->username);
                    $conv_id = $this->model->addConversation($conv_data);
                }
                if ($conv_id) {
                    $msg_data = array(
                        'poster'	=>	$this->feather->user->username,
                        'poster_id'	=>	$this->feather->user->id,
                        'poster_ip'	=>	$this->feather->request->getIp(),
                        'message'	=>	$data['message'],
                        'hide_smilies'	=>	$data['smilies'],
                        'sent'	=>	$this->feather->now,
                    );
                    if ($conv) {
                        if ($msg_id = $this->model->addMessage($msg_data, $conv_id)) {
                            Url::redirect($this->feather->urlFor('Conversations.home'), 'You have successfully responded to the conversation '.$conv->subject.' !');
                        }
                    } else {
                        // Add message in conversation + add receiver
                        if ($msg_id = $this->model->addMessage($msg_data, $conv_id, array($user->id, $this->feather->user->id))) {
                            Url::redirect($this->feather->urlFor('Conversations.home'), 'Your PM has been sent to '.$user->username.' !');
                        }
                    }
                } else {
                    throw new Error('Unable to create conversation');
                }
            }
        } else {
            if (!is_null($uid)) {
                if ($uid < 2) {
                    throw new Error('Wrong user id', 400);
                }
                if ($user = $this->model->getUserByID($uid)) {
                    $this->feather->template->setPageInfo(array('username' => Utils::escape($user->username)));
                } else {
                    throw new Error('Unable to find user', 400);
                }
            }
            if (!is_null($conv_id)) {
                if ($conv_id < 1) {
                    throw new Error('Wrong conversation ID', 400);
                }
                if ($conv = $this->model->getConversation($conv_id, $this->feather->user->id)) {
                    $this->feather->template->setPageInfo(array(
                        'conv' => $conv,
                        'msg_data' => $this->model->getMessagesFromConversation($conv_id, $this->feather->user->id)
                        ))->addTemplate('reply.php')->display();
                    } else {
                        throw new Error('Unknown conversation ID', 400);
                    }
            }
            $this->feather->template->addTemplate('send.php')->display();
        }
    }

    public function reply($conv_id = null) {
        $this->send(null, $conv_id);
    }
}
