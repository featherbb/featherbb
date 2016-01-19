<?php

/**
 * Copyright (C) 2015-2016 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace FeatherBB\Plugins\Controller;


use FeatherBB\Core\Database as DB;
use FeatherBB\Core\Error;
use FeatherBB\Core\Url;
use FeatherBB\Core\Utils;

class PrivateMessages
{
    protected $feather, $request, $model, $crumbs, $inboxes;

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

        if ($this->inboxes = $this->model->getInboxes($this->feather->user->id)) {
            if (!in_array($fid, array_keys($this->inboxes))) {
                throw new Error(__('Wrong folder owner', 'private_messages'), 403);
            }
        }
        // Page data
        $num_pages = ceil($this->inboxes[$fid]['nb_msg'] / $this->feather->user['disp_topics']);
        $p = (!isset($page) || $page <= 1 || $page > $num_pages) ? 1 : intval($page);
        $start_from = $this->feather->user['disp_topics'] * ($p - 1);
        $paging_links = Url::paginate($num_pages, $p, $this->feather->urlFor('Conversations.home', ['id' => $fid]).'/#');

        // Make breadcrumbs
        $this->crumbs[$this->feather->urlFor('Conversations.home', ['inbox_id' => $fid])] = $this->inboxes[$fid]['name'];
        $this->crumbs[] = __('My conversations', 'private_messages');
        Utils::generateBreadcrumbs($this->crumbs, array(
            'link' => $this->feather->urlFor('Conversations.send'),
            'text' => __('Send', 'private_messages')
        ));

        $this->generateMenu($this->inboxes[$fid]['name']);
        $this->feather->template->addAsset('js', 'style/imports/common.js', array('type' => 'text/javascript'));
        $this->feather->template
            ->setPageInfo(array(
                'title' => array(Utils::escape($this->feather->config['o_board_title']), __('PMS', 'private_messages'), $this->inboxes[$fid]['name']),
                'admin_console' => true,
                'inboxes' => $this->inboxes,
                'current_inbox_id' => $fid,
                'paging_links' => $paging_links,
                'rightLink' => ['link' => $this->feather->urlFor('Conversations.send'), 'text' => __('Send', 'private_messages')],
                'conversations' => $this->model->getConversations($fid, $uid, $this->feather->user['disp_topics'], $start_from)
            )
        )
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
                if (!is_null($data['preview']) && $this->feather->user['last_post'] != '' && ($this->feather->now - $this->feather->user['last_post']) < $this->feather->prefs->get($this->feather->user, 'post.min_interval')) {
                    throw new Error(sprintf(__('Flood start'), $this->feather->prefs->get($this->feather->user, 'post.min_interval'), $this->feather->prefs->get($this->feather->user, 'post.min_interval') - ($this->feather->now - $this->feather->user['last_post'])), 429);
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
                        'subject'    =>    $data['subject'],
                        'poster'    =>    $this->feather->user->username,
                        'poster_id'    =>    $this->feather->user->id,
                        'num_replies'    =>    0,
                        'last_post'    =>    $this->feather->now,
                        'last_poster'    =>    $this->feather->user->username);
                    $conv_id = $this->model->addConversation($conv_data);
                }
                if ($conv_id) {
                    $msg_data = array(
                        'poster'    =>    $this->feather->user->username,
                        'poster_id'    =>    $this->feather->user->id,
                        'poster_ip'    =>    $this->feather->request->getIp(),
                        'message'    =>    $data['req_message'],
                        'hide_smilies'    =>    $data['smilies'],
                        'sent'    =>    $this->feather->now,
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

        $this->inboxes = $this->model->getInboxes($this->feather->user->id);

        $this->crumbs[$this->feather->urlFor('Conversations.home', ['inbox_id' => $conv['folder_id']])] = $this->inboxes[$conv['folder_id']]['name'];
        $this->crumbs[] = __('My conversations', 'private_messages');
        $this->crumbs[] = $conv['subject'];
        Utils::generateBreadcrumbs($this->crumbs, array(
            'link' => $this->feather->urlFor('Conversations.reply', ['tid' => $conv['id']]),
            'text' => __('Reply', 'private_messages')
        ));
        $this->generateMenu($this->inboxes[$conv['folder_id']]['name']);
        $this->feather->template
            ->setPageInfo(array(
                'title' => array(Utils::escape($this->feather->config['o_board_title']), __('PMS', 'private_messages'), $this->model->getUserFolders($this->feather->user->id)[$conv['folder_id']]['name'], Utils::escape($conv['subject'])),
                'admin_console' => true,
                'paging_links' => $paging_links,
                'start_from' => $start_from,
                'cur_conv' => $conv,
                'rightLink' => ['link' => $this->feather->urlFor('Conversations.reply', ['tid' => $conv['id']]), 'text' => __('Reply', 'private_messages')],
                'messages' => $this->model->getMessages($conv['id'], $this->feather->user['disp_topics'], $start_from)
            )
        )
        ->addTemplate('show.php')->display();
    }

    public function blocked()
    {
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
                    'user_id'    =>    $this->feather->user->id,
                    'block_id'    =>    $user_infos->id,
                );

                $this->model->addBlock($insert);
                Url::redirect($this->feather->urlFor('Conversations.blocked'), __('Block added', 'private_messages'));
            }
        }
        else if ($this->request->post('remove_block'))
        {
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

        $this->generateMenu('blocked');
        $this->feather->template
            ->setPageInfo(array(
                'title' => array(Utils::escape($this->feather->config['o_board_title']), __('PMS', 'private_messages'), __('Blocked Users', 'private_messages')),
                'admin_console' => true,
                'errors' => $errors,
                'username' => $username,
                'required_fields' => array('req_username' => __('Add block', 'private_messages')),
                'blocks' => $this->model->getBlocked($this->feather->user->id),
            )
        )
        ->addTemplate('blocked.php')->display();
    }

    public function folders()
    {
        $errors = array();

        if ($this->request->post('add_folder'))
        {
            $folder = $this->request->post('req_folder') ? Utils::trim(Utils::escape($this->request->post('req_folder'))) : '';

            if ($folder == '')
                $errors[] = __('No folder name', 'private_messages');
            else if (Utils::strlen($folder) < 4)
                $errors[] = __('Folder too short', 'private_messages');
            else if (Utils::strlen($folder) > 30)
                $errors[] = __('Folder too long', 'private_messages');
            else if ($this->feather->forum_settings['o_censoring'] == '1' && Utils::censor($folder) == '')
                $errors[] = __('No folder after censoring', 'private_messages');

            // TODO: Check perms when ready
            // $data = array(
            //     ':uid'    =>    $panther_user['id'],
            // );
            //
            // if ($panther_user['g_pm_folder_limit'] != 0)
            // {
            //     $ps = $db->select('folders', 'COUNT(id)', $data, 'user_id=:uid');
            //     $num_folders = $ps->fetchColumn();
            //
            //     if ($num_folders >= $panther_user['g_pm_folder_limit'])
            //         $errors[] = sprintf($lang_pm['Folder limit'], $panther_user['g_pm_folder_limit']);
            // }

            if (empty($errors))
            {
                $insert = array(
                    'user_id'    =>    $this->feather->user->id,
                    'name'    =>    $folder
                );

                $this->model->addFolder($insert);
                Url::redirect($this->feather->urlFor('Conversations.folders'), __('Folder added', 'private_messages'));
            }
        }
        else if ($this->request->post('update_folder'))
        {
            $id = intval(key($this->request->post('update_folder')));
            var_dump($id);

            $errors = array();
            $folder = Utils::trim($this->request->post('folder')[$id]);

            if ($folder == '')
                $errors[] = __('No folder name', 'private_messages');
            else if (Utils::strlen($folder) < 4)
                $errors[] = __('Folder too short', 'private_messages');
            else if (Utils::strlen($folder) > 30)
                $errors[] = __('Folder too long', 'private_messages');
            else if ($this->feather->forum_settings['o_censoring'] == '1' && Utils::censor($folder) == '')
                $errors[] = __('No folder after censoring', 'private_messages');

            if (empty($errors))
            {
                $update = array(
                    'name'    =>    $folder,
                );

                if ($this->model->updateFolder($this->feather->user->id, $id, $update))
                    Url::redirect($this->feather->urlFor('Conversations.folders'), __('Folder updated', 'private_messages'));
                else
                    throw new Error(__('Error'), 403);
            }
        }
        else if ($this->request->post('remove_folder'))
        {
            $id = intval(key($this->request->post('remove_folder')));
            // Before we do anything, check we blocked this user
            if (!$this->model->checkFolderOwner($id, intval($this->feather->user->id)))
                throw new Error(__('No permission'), 403);

            if ($this->model->removeFolder($this->feather->user->id, $id))
                Url::redirect($this->feather->urlFor('Conversations.folders'), __('Folder removed', 'private_messages'));
            else
                throw new Error(__('Error'), 403);
        }

        Utils::generateBreadcrumbs(array(
            $this->feather->urlFor('Conversations.home') => __('PMS', 'private_messages'),
            __('Options'),
            __('My Folders', 'private_messages')
        ));

        $this->generateMenu('folders');
        $this->feather->template
            ->setPageInfo(array(
                'title' => array(Utils::escape($this->feather->config['o_board_title']), __('PMS', 'private_messages'), __('Blocked Users', 'private_messages')),
                'admin_console' => true,
                'errors' => $errors
            )
        )
        ->addTemplate('folders.php')->display();
    }

    public function generateMenu($page = '')
    {
        if (!isset($this->inboxes))
            $this->inboxes = $this->model->getInboxes($this->feather->user->id);

        $this->feather->template->setPageInfo(array(
            'page'    =>    $page,
            'inboxes'    =>    $this->inboxes,
            ), 1
        )->addTemplate('menu.php');
        return $this->inboxes;
    }

}
