<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace App\Controller\Admin;

class Forums
{
    public function __construct()
    {
        $this->feather = \Slim\Slim::getInstance();
        $this->start = $this->feather->start;
        $this->config = $this->feather->config;
        $this->user = $this->feather->user;
        $this->request = $this->feather->request;
        $this->model = new \App\Model\Admin\forums();
        load_textdomain('featherbb', $this->feather->forum_env['FEATHER_ROOT'].'app/lang/'.$this->user->language.'/admin/forums.mo');
    }

    public function __autoload($class_name)
    {
        require $this->feather->forum_env['FEATHER_ROOT'] . $class_name . '.php';
    }

    //
    // CRUD
    //

    public function add_forum()
    {
        $cat_id = (int) $this->request->post('cat');

        if ($cat_id < 1) {
            redirect($this->feather->url->get('admin/forums/'), __('Must be valid category'));
        }

        if ($fid = $this->model->add_forum($cat_id, __('New forum'))) {
            // Regenerate the quick jump cache
            $this->feather->cache->store('quickjump', \App\Model\Cache::get_quickjump());
            redirect($this->feather->url->get('admin/forums/edit/'.$fid.'/'), __('Forum added redirect'));
        } else {
            redirect($this->feather->url->get('admin/forums/'), __('Unable to add forum'));
        }
    }

    public function edit_forum($forum_id)
    {
        if($this->request->isPost()) {
            if ($this->request->post('save') && $this->request->post('read_forum_old')) {

                // Forums parameters / TODO : better handling of wrong parameters
                $forum_data = array('forum_name' => $this->feather->utils->escape($this->request->post('forum_name')),
                                    'forum_desc' => $this->request->post('forum_desc') ? $this->feather->utils->linebreaks($this->feather->utils->trim($this->request->post('forum_desc'))) : NULL,
                                    'cat_id' => (int) $this->request->post('cat_id'),
                                    'sort_by' => (int) $this->request->post('sort_by'),
                                    'redirect_url' => $this->feather->url->is_valid($this->request->post('redirect_url')) ? $this->feather->utils->escape($this->request->post('redirect_url')) : NULL);

                if ($forum_data['forum_name'] == '') {
                    redirect($this->feather->url->get('admin/forums/edit/'.$forum_id.'/'), __('Must enter name message'));
                }
                if ($forum_data['cat_id'] < 1) {
                    redirect($this->feather->url->get('admin/forums/edit/'.$forum_id.'/'), __('Must be valid category'));
                }

                $this->model->update_forum($forum_id, $forum_data);

                // Permissions
                $permissions = $this->model->get_default_group_permissions(false);
                foreach($permissions as $perm_group) {
                    $permissions_data = array('group_id' => $perm_group['g_id'],
                                                'forum_id' => $forum_id);
                    if ($perm_group['g_read_board'] == '1' && isset($this->request->post('read_forum_new')[$perm_group['g_id']]) && $this->request->post('read_forum_new')[$perm_group['g_id']] == '1') {
                        $permissions_data['read_forum'] = '1';
                    }
                    else {
                        $permissions_data['read_forum'] = '0';
                    }

                    $permissions_data['post_replies'] = (isset($this->request->post('post_replies_new')[$perm_group['g_id']])) ? '1' : '0';
                    $permissions_data['post_topics'] = (isset($this->request->post('post_topics_new')[$perm_group['g_id']])) ? '1' : '0';
                    // Check if the new settings differ from the old
                    if ($permissions_data['read_forum'] != $this->request->post('read_forum_old')[$perm_group['g_id']] ||
                        $permissions_data['post_replies'] != $this->request->post('post_replies_old')[$perm_group['g_id']] ||
                        $permissions_data['post_topics'] != $this->request->post('post_topics_old')[$perm_group['g_id']]) {
                            // If there is no group permissions override for this forum
                            if ($permissions_data['read_forum'] == '1' && $permissions_data['post_replies'] == $perm_group['g_post_replies'] && $permissions_data['post_topics'] == $perm_group['g_post_topics']) {
                                $this->model->delete_permissions($forum_id, $perm_group['g_id']);
                            } else {
                            // Run an UPDATE and see if it affected a row, if not, INSERT
                                $this->model->update_permissions($permissions_data);
                            }
                    }
                }

                // Regenerate the quick jump cache
                $this->feather->cache->store('quickjump', \App\Model\Cache::get_quickjump());

                redirect($this->feather->url->get('admin/forums/edit/'.$forum_id.'/'), __('Forum updated redirect'));

            } elseif ($this->request->post('revert_perms')) {
                $this->model->delete_permissions($forum_id);

                // Regenerate the quick jump cache
                $this->feather->cache->store('quickjump', \App\Model\Cache::get_quickjump());

                redirect($this->feather->url->get('admin/forums/edit/'.$forum_id.'/'), __('Perms reverted redirect'));
            }

        } else {
            \FeatherBB\AdminUtils::generateAdminMenu('forums');

            $this->feather->view2->setPageInfo(array(
                    'title'    =>    array($this->feather->utils->escape($this->config['o_board_title']), __('Admin'), __('Forums')),
                    'active_page'    =>    'admin',
                    'admin_console'    =>    true,
                    'perm_data' => $this->model->get_permissions($forum_id),
                    'cur_index'     =>  7,
                    'cur_forum' => $this->model->get_forum_info($forum_id),
                    'forum_data' => $this->model->get_forums(),
                )
            )->addTemplate('admin/forums/permissions.php')->display();
        }
    }

    public function delete_forum($forum_id)
    {
        if($this->request->isPost()) {
            $this->model->delete_forum($forum_id);
            // Regenerate the quick jump cache
            $this->feather->cache->store('quickjump', \App\Model\Cache::get_quickjump());

            redirect($this->feather->url->get('admin/forums/'), __('Forum deleted redirect'));

        } else { // If the user hasn't confirmed

            \FeatherBB\AdminUtils::generateAdminMenu('forums');

            $this->feather->view2->setPageInfo(array(
                    'title'    =>    array($this->feather->utils->escape($this->config['o_board_title']), __('Admin'), __('Forums')),
                    'active_page'    =>    'admin',
                    'admin_console'    =>    true,
                    'cur_forum' => $this->model->get_forum_info($forum_id),
                )
            )->addTemplate('admin/forums/delete_forum.php')->display();
        }
    }

    // -- //

    public function edit_positions()
    {
        foreach ($this->request->post('position') as $forum_id => $position) {
            $position = (int) $this->feather->utils->trim($position);
            $this->model->update_positions($forum_id, $position);
        }

        // Regenerate the quick jump cache
        $this->feather->cache->store('quickjump', \App\Model\Cache::get_quickjump());

        redirect($this->feather->url->get('admin/forums/'), __('Forums updated redirect'));
    }

    public function display()
    {
        if ($this->request->post('update_positions')) {
            $this->edit_positions();
        }

        \FeatherBB\AdminUtils::generateAdminMenu('forums');

        $categories_model = new \App\Model\Admin\Categories();
        $this->feather->view2->setPageInfo(array(
                'title' => array($this->feather->utils->escape($this->config['o_board_title']), __('Admin'), __('Forums')),
                'active_page' => 'admin',
                'admin_console' => true,
                'cat_list' => $categories_model->get_cat_list(),
                'forum_data' => $this->model->get_forums(),
                'cur_index' => 4,
            )
        )->addTemplate('admin/forums/admin_forums.php')->display();
    }
}
