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
use FeatherBB\Core\DB;

class PrivateMessages
{
    protected $feather, $request, $model, $crumbs;

    public function __construct()
    {
        $this->feather = \Slim\Slim::getInstance();
        $this->request = $this->feather->request;
        $this->model = new \FeatherBB\Plugins\Model\PrivateMessages();
        load_textdomain('private_messages', dirname(dirname(__FILE__)).'/lang/'.$this->feather->user->language.'/private-messages.mo');
        load_textdomain('featherbb', $this->feather->forum_env['FEATHER_ROOT'].'featherbb/lang/'.$this->feather->user->language.'/misc.mo');
        $this->feather->template->addTemplatesDirectory(dirname(dirname(__FILE__)).'/Views', 5)->setPageInfo(['active_page' => 'navextra1']);
        $this->crumbs =array(
            $this->feather->urlFor('Conversations.home') => __('PMS', 'private_messages')
        );
    }

    public function index($fid = 2, $page = 1)
    {
        // Set default page to "Inbox" folder
        $fid = !empty($fid) ? intval($fid) : 2;
        $uid = intval($this->feather->user->id);

        if ($action = $this->request->post('action')) {
            switch ($action) {
                case 'move':
                    $this->move();
                    break;
                case 'delete':
                    $this->delete();
                    break;
                case 'read':
                    $this->markRead();
                    break;
                case 'unread':
                    $this->markRead(0);
                    break;
                default:
                    Url::redirect($this->feather->urlFor('Conversations.home', ['inbox_id' => $this->request->post('inbox_id')]));
                    break;
            }
        }

        if ($inboxes = $this->model->getInboxes($this->feather->user->id)) {
            if (!in_array($fid, array_keys($inboxes))) {
                throw new Error(__('Wrong folder owner', 'private_messages'), 403);
            }
        }
        // Page data
        $num_pages = ceil($inboxes[$fid]['nb_msg'] / $this->feather->user['disp_topics']);
        $p = (!isset($page) || $page <= 1 || $page > $num_pages) ? 1 : intval($page);
        $start_from = $this->feather->user['disp_topics'] * ($p - 1);
        $paging_links = Url::paginate($num_pages, $p, $this->feather->urlFor('Conversations.home', ['id' => $fid]).'/#');

        // Make breadcrumbs
        $this->crumbs[$this->feather->urlFor('Conversations.home', ['inbox_id' => $fid])] = $inboxes[$fid]['name'];
        $this->crumbs[] = __('My conversations', 'private_messages');
        Utils::generateBreadcrumbs($this->crumbs, array(
            'link' => $this->feather->urlFor('Conversations.send'),
            'text' => __('Send', 'private_messages')
        ));

        $this->feather->template->addAsset('js', 'style/imports/common.js', array('type' => 'text/javascript'));
        $this->feather->template
            ->setPageInfo(array(
                'title' => array(Utils::escape($this->feather->config['o_board_title']), __('PMS', 'private_messages'), $inboxes[$fid]['name']),
                'admin_console' => true,
                'inboxes' => $inboxes,
                'current_inbox_id' => $fid,
                'paging_links' => $paging_links,
                'rightLink' => ['link' => $this->feather->urlFor('Conversations.send'), 'text' => __('Send', 'private_messages')],
                'conversations' => $this->model->getConversations($fid, $uid, $this->feather->user['disp_topics'], $start_from)
            )
        )
        ->addTemplate('menu.php')
        ->addTemplate('index.php')->display();
    }

    public function delete()
    {
        if (!$this->request->post('topics'))
    		throw new Error(__('No conv selected', 'private_messages'), 403);

    	$topics = $this->request->post('topics') && is_array($this->request->post('topics')) ? array_map('intval', $this->request->post('topics')) : array_map('intval', explode(',', $this->request->post('topics')));

    	if (empty($topics))
    		throw new Error(__('No conv selected', 'private_messages'), 403);

    	if ( $this->request->post('delete_comply') )
    	{
            $uid = intval($this->feather->user->id);
            $this->model->delete($topics, $uid);

            Url::redirect($this->feather->urlFor('Conversations.home'), __('Conversations deleted', 'private_messages'));
    	}
    	else
    	{
            // Display confirm delete form
            $this->feather->template
                ->setPageInfo(array(
                    'title' => array(Utils::escape($this->feather->config['o_board_title']), __('PMS', 'private_messages')),
                    'topics' => $topics,
                )
            )
            ->addTemplate('delete.php')->display();
    	}
        die();
    }

    public function move()
    {
        if (!$this->request->post('topics'))
    		throw new Error(__('No conv selected', 'private_messages'), 403);

    	$topics = $this->request->post('topics') && is_array($this->request->post('topics')) ? array_map('intval', $this->request->post('topics')) : array_map('intval', explode(',', $this->request->post('topics')));

    	if (empty($topics))
    		throw new Error(__('No conv selected', 'private_messages'), 403);

        $uid = intval($this->feather->user->id);

    	if ( $this->request->post('move_comply') )
    	{
            $move_to = $this->request->post('move_to') ? intval($this->request->post('move_to')) : 2;

            if ( $this->model->move($topics, $move_to, $uid) ) {
                Url::redirect($this->feather->urlFor('Conversations.home', ['inbox_id' => $move_to]), __('Conversations moved', 'private_messages'));
            } else {
                throw new Error(__('Error Move', 'private_messages'), 403);
            }
    	}

        // Display move form
        if ($inboxes = $this->model->getUserFolders($uid)) {
            $this->feather->template
                ->setPageInfo(array(
                    'title' => array(Utils::escape($this->feather->config['o_board_title']), __('PMS', 'private_messages')),
                    'topics' => $topics,
                    'inboxes' => $inboxes,
                )
            )
            ->addTemplate('move.php')->display();
        } else {
            throw new Error('No inboxes', 404);
        }

        die();
    }

    public function markRead($read = true)
    {
        $viewed = ($read == true) ? '1' : '0';

        if (!$this->request->post('topics'))
    		throw new Error(__('No conv selected', 'private_messages'), 403);

    	$topics = $this->request->post('topics') && is_array($this->request->post('topics')) ? array_map('intval', $this->request->post('topics')) : array_map('intval', explode(',', $this->request->post('topics')));

    	if (empty($topics))
    		throw new Error(__('No conv selected', 'private_messages'), 403);

        $this->model->updateConversation($topics, $this->feather->user->id, ['viewed' => $viewed]);

        Url::redirect($this->feather->urlFor('Conversations.home', ['inbox_id' => $this->request->post('inbox_id')]));
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

            // Preview message
            if ($this->feather->request->post('preview')) {
                // Make breadcrumbs
                $this->crumbs[] = __('Reply', 'private_messages');
                $this->crumbs[] = __('Preview');
                Utils::generateBreadcrumbs($this->crumbs);

                $this->feather->hooks->fire('conversationsPlugin.send.preview');
                $msg = $this->feather->parser->parse_message($data['req_message'], $data['smilies']);
                $this->feather->template->setPageInfo(array(
                    'parsed_message' => $msg,
                    'username' => Utils::escape($data['username']),
                    'subject' => Utils::escape($data['subject']),
                    'message' => Utils::escape($data['req_message'])
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
                $data['req_message'] = Utils::trim(Utils::censor($data['req_message']));
                if (empty($data['req_message'])) {
                    throw new Error('No message or censored message', 400);
                } else if (Utils::strlen($data['req_message']) > $this->feather->forum_env['FEATHER_MAX_POSTSIZE']) {
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
                        'message'	=>	$data['req_message'],
                        'hide_smilies'	=>	$data['smilies'],
                        'sent'	=>	$this->feather->now,
                    );
                    if ($conv) {
                        // Reply to an existing conversation
                        if ($msg_id = $this->model->addMessage($msg_data, $conv_id)) {
                            Url::redirect($this->feather->urlFor('Conversations.home'), sprintf(__('Reply success', 'private_messages'), $conv->subject));
                        }
                    } else {
                        // Add message in conversation + add receiver (create new conversation)
                        if ($msg_id = $this->model->addMessage($msg_data, $conv_id, array($user->id, $this->feather->user->id))) {
                            Url::redirect($this->feather->urlFor('Conversations.home'), sprintf(__('Send success', 'private_messages'), $user->username));
                        }
                    }
                } else {
                    throw new Error('Unable to create conversation');
                }
            }
        } else {
            $this->feather->hooks->fire('conversationsPlugin.send.display');
            // New conversation
            if (!is_null($uid)) {
                if ($uid < 2) {
                    throw new Error('Wrong user ID', 400);
                }
                if ($user = $this->model->getUserByID($uid)) {
                    $this->feather->template->setPageInfo(array('username' => Utils::escape($user->username)));
                } else {
                    throw new Error('Unable to find user', 400);
                }
            }
            // Reply
            if (!is_null($conv_id)) {
                if ($conv_id < 1) {
                    throw new Error('Wrong conversation ID', 400);
                }
                if ($conv = $this->model->getConversation($conv_id, $this->feather->user->id)) {
                    $inbox = DB::for_table('pms_folders')->find_one($conv->folder_id);
                    $this->crumbs[$this->feather->urlFor('Conversations.home', ['inbox_id' => $inbox['id']])] = $inbox['name'];
                    $this->crumbs[] = __('Reply', 'private_messages');
                    $this->crumbs[] = $conv['subject'];
                    Utils::generateBreadcrumbs($this->crumbs);
                    return $this->feather->template->setPageInfo(array(
                        'current_inbox' => $inbox,
                        'conv' => $conv,
                        'msg_data' => $this->model->getMessagesFromConversation($conv_id, $this->feather->user->id, 5)
                    ))->addTemplate('reply.php')->display();
                } else {
                    throw new Error('Unknown conversation ID', 400);
                }
            }
            $this->crumbs[] = __('Send', 'private_messages');
            if(isset($user)) $this->crumbs[] = $user->username;
            Utils::generateBreadcrumbs($this->crumbs);
            $this->feather->template->addTemplate('send.php')->display();
        }
    }

    public function reply($conv_id = null)
    {
        return $this->send(null, $conv_id);
    }

    public function show($conv_id = null, $page = null)
    {
        // First checks
        if ($conv_id < 1) {
            throw new Error('Wrong conversation ID', 400);
        }
        if (!$conv = $this->model->getConversation($conv_id, $this->feather->user->id)) {
            throw new Error('Unknown conversation ID', 404);
        } else if ($this->model->isDeleted($conv_id, $this->feather->user->id)) {
            throw new Error('The conversation has been deleted', 404);
        }

        // Set conversation as viewed
        if ($conv['viewed'] == 0) {
            if (!$this->model->setViewed($conv_id, $this->feather->user->id)) {
                throw new Error('Unable to set conversation as viewed', 500);
            }
        }

        $num_pages = ceil($conv['num_replies'] / $this->feather->user['disp_topics']);
        $p = (!is_null($page) || $page <= 1 || $page > $num_pages) ? 1 : intval($page);
        $start_from = $this->feather->user['disp_topics'] * ($p - 1);
        $paging_links = Url::paginate($num_pages, $p, $this->feather->urlFor('Conversations.show', ['tid' => $conv_id]).'/#');

        $inboxes = $this->model->getInboxes($this->feather->user->id);

        $this->crumbs[$this->feather->urlFor('Conversations.home', ['inbox_id' => $conv['folder_id']])] = $inboxes[$conv['folder_id']]['name'];
        $this->crumbs[] = __('My conversations', 'private_messages');
        $this->crumbs[] = $conv['subject'];
        Utils::generateBreadcrumbs($this->crumbs, array(
            'link' => $this->feather->urlFor('Conversations.reply', ['tid' => $conv['id']]),
            'text' => __('Reply', 'private_messages')
        ));

        $this->feather->template
            ->setPageInfo(array(
                'title' => array(Utils::escape($this->feather->config['o_board_title']), __('PMS', 'private_messages'), $this->model->getUserFolders($this->feather->user->id)[$conv['folder_id']]['name'], Utils::escape($conv['subject'])),
                'admin_console' => true,
                'current_inbox_id' => $conv['folder_id'],
                'inboxes' => $inboxes,
                'paging_links' => $paging_links,
                'start_from' => $start_from,
                'cur_conv' => $conv,
                'rightLink' => ['link' => $this->feather->urlFor('Conversations.reply', ['tid' => $conv['id']]), 'text' => __('Reply', 'private_messages')],
                'messages' => $this->model->getMessages($conv['id'], $this->feather->user['disp_topics'], $start_from)
            )
        )
        ->addTemplate('menu.php')
        ->addTemplate('show.php')->display();
    }

    public function blocked()
    {
    	// $required_fields = array('req_username' => $lang_common['Username']);
    	// $focus_element = array('block', 'req_username');

    	// $page_title = array(panther_htmlspecialchars($panther_config['o_board_title']), $lang_common['PM'], $lang_pm['My blocked']);
    	// define('PANTHER_ACTIVE_PAGE', 'index');
    	// require PANTHER_ROOT.'header.php';
    	//
    	// echo generate_pm_menu('blocked');

    	// If there are errors, we display them
    	// if (!empty($errors))
    	// {
    	// 	$form_errors = array();
    	// 	foreach ($errors as $cur_error)
    	// 		$form_errors[] = "\t\t\t\t".'<li><strong>'.$cur_error.'</strong></li>'."\n";
        //
    	// 	$error_tpl = panther_template('inline_errors.tpl');
    	// 	$search = array(
    	// 		'{errors}' => $lang_pm['Block errors'],
    	// 		'{errors_info}' => $lang_pm['Block errors info'],
    	// 		'{error_list}' => implode("\n", $form_errors),
    	// 	);
    	//
    	// 	$error_tpl = str_replace(array_keys($search), array_values($search), $error_tpl).'<br />'."\n";
    	// }
    	// else
    	// 	$error_tpl = '';

    	// if ($ps->rowCount())
    	// {
    	// 	$blocked_rows = array();
    	// 	$blocked_row_tpl = panther_template('pm_blocked_row.tpl');
    	// 	foreach ($ps as $cur_block)
    	// 	{
    	// 		$data = array(
    	// 			':id'	=>	$cur_block['block_id'],
    	// 		);
    	//
    	// 		$search = array(
    	// 			'{name}' => colourize_group($cur_block['username'], $cur_block['group_id'], $cur_block['block_id']),
    	// 			'{id}' => $cur_block['id'],
    	// 			'{remove}' => $lang_pm['Remove'],
    	// 		);
    	//
    	// 		$blocked_rows[] = str_replace(array_keys($search), array_values($search), $blocked_row_tpl);
    	// 	}
        //
    	// 	$blocked_tpl = panther_template('blocked_content.tpl');
    	// 	$search = array(
    	// 		'{form_action}' => panther_link($panther_url['pms_blocked']),
    	// 		'{my_folders}' => $lang_pm['My blocked'],
    	// 		'{username}' => $lang_common['Username'],
    	// 		'{actions}' => $lang_pm['Actions'],
    	// 		'{blocked_content}' => implode("\n", $blocked_rows),
    	// 	);
        //
    	// 	$blocked_tpl = str_replace(array_keys($search), array_values($search), $blocked_tpl);
    	// }
    	// else
    	// 	$blocked_tpl = '';
        //
    	// $pm_tpl = panther_template('pm_blocked.tpl');
    	// $search = array(
    	// 	'{errors}' => $error_tpl,
    	// 	'{my_blocked}' => $lang_pm['My blocked'],
    	// 	'{form_action}' => panther_link($panther_url['pms_blocked']),
    	// 	'{add_block}' => $lang_pm['Add block'],
    	// 	'{username}' => $lang_common['Username'],
    	// 	'{username_value}' => (isset($username)) ? panther_htmlspecialchars($username) : '',
    	// 	'{blocked_users}' => $blocked_tpl,
    	// 	'{add}' => $lang_pm['Add'],
    	// );

        $errors = array();

        $username = $this->request->post('req_username') ? Utils::trim(Utils::escape($this->request->post('req_username'))) : '';
        if ($this->request->post('add_block'))
    	{
    		if ($username == $this->feather->user->username)
    			$errors[] = __('No block self', 'private_messages');

    		if (!($user_infos = $this->model->getUserByName($username)) || $username == __('Guest'))
    			$errors[] = sprintf(__('No user name message', 'private_messages'), Utils::escape($username));

    		if (empty($errors))
    		{
    			if ($user_infos->group_id == $this->feather->forum_env['FEATHER_ADMIN'])
    				$errors[] = sprintf(__('User is admin', 'private_messages'), Utils::escape($username));
    			elseif ($user_infos->group_id == $this->feather->forum_env['FEATHER_MOD'])
    				$errors[] = sprintf(__('User is mod', 'private_messages'), Utils::escape($username));

    			if ($this->model->checkBlock($this->feather->user->id, $user_infos->id))
    				$errors[] = sprintf(__('Already blocked', 'private_messages'), Utils::escape($username));
    		}

    		if (empty($errors))
    		{
    			$insert = array(
    				'user_id'	=>	$this->feather->user->id,
    				'block_id'	=>	$user_infos->id,
    			);

    			$this->model->addBlock($insert);
    			Url::redirect($this->feather->urlFor('Conversations.blocked'), __('Block added', 'private_messages'));
    		}
    	}
    	else if ($this->request->post('remove_block'))
    	{
            // var_dump($this->request->post('remove_block'));
    		$id = intval(key($this->request->post('remove_block')));
    		// Before we do anything, check we blocked this user
    		if (!$this->model->checkBlock(intval($this->feather->user->id), $id))
    			throw new Error(__('No permission'), 403);

    		$this->model->removeBlock(intval($this->feather->user->id), $id);
    		Url::redirect($this->feather->urlFor('Conversations.blocked'), __('Block removed', 'private_messages'));
    	}

        Utils::generateBreadcrumbs(array(
            $this->feather->urlFor('Conversations.home') => __('PMS', 'private_messages'),
            __('Options'),
            __('Blocked Users', 'private_messages')
        ));

        $this->feather->template
            ->setPageInfo(array(
                'title' => array(Utils::escape($this->feather->config['o_board_title']), __('PMS', 'private_messages'), __('Blocked Users', 'private_messages')),
                'admin_console' => true,
                'errors' => $errors,
                'username' => $username,
                'required_fields' => array('req_username' => __('Add block', 'private_messages')),
                'blocks' => $this->model->getBlocked($this->feather->user->id),
                'inboxes' => $this->model->getInboxes($this->feather->user->id)
            )
        )
        ->addTemplate('menu.php')
        ->addTemplate('blocked.php')->display();
    }

    public function folders()
    {

    }

    public function generateMenu($page = '')
    {
        $inboxes = $this->model->getInboxes($this->feather->user->id);
        $crumbs = [
                __('PMS', 'private_messages')
            ];

        $this->feather->template->setPageInfo(array(
            'page'    =>    $page,
            'crumbs'    =>    $crumbs,
            // 'is_admin'    =>    $is_admin,
            'inboxes'    =>    $inboxes,
            ), 1
        )->addTemplate('blocked.php');
    }

}
