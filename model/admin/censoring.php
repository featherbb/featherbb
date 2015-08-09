<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace model\admin;

use DB;

class censoring
{
    public function __construct()
    {
        $this->feather = \Slim\Slim::getInstance();
        $this->start = $this->feather->start;
        $this->config = $this->feather->config;
        $this->user = $this->feather->user;
        $this->request = $this->feather->request;
    }

    public function add_word()
    {
        global $lang_admin_censoring;

        $search_for = feather_trim($this->request->post('new_search_for'));
        $replace_with = feather_trim($this->request->post('new_replace_with'));

        if ($search_for == '') {
            message($lang_admin_censoring['Must enter word message']);
        }

        $set_search_word = array('search_for' => $search_for,
                                'replace_with' => $replace_with);

        DB::for_table('censoring')
            ->create()
            ->set($set_search_word)
            ->save();

        // Regenerate the censoring cache
        if (!defined('FORUM_CACHE_FUNCTIONS_LOADED')) {
            require FEATHER_ROOT.'include/cache.php';
        }

        generate_censoring_cache();

        redirect(get_link('admin/censoring/'), $lang_admin_censoring['Word added redirect']);
    }

    public function update_word()
    {
        global $lang_admin_censoring;

        $id = intval(key($this->request->post('update')));

        $search_for = feather_trim($this->request->post('search_for')[$id]);
        $replace_with = feather_trim($this->request->post('replace_with')[$id]);

        if ($search_for == '') {
            message($lang_admin_censoring['Must enter word message']);
        }

        $set_search_word = array('search_for' => $search_for,
                                'replace_with' => $replace_with);

        DB::for_table('censoring')
            ->find_one($id)
            ->set($set_search_word)
            ->save();

        // Regenerate the censoring cache
        if (!defined('FORUM_CACHE_FUNCTIONS_LOADED')) {
            require FEATHER_ROOT.'include/cache.php';
        }

        generate_censoring_cache();

        redirect(get_link('admin/censoring/'), $lang_admin_censoring['Word updated redirect']);
    }

    public function remove_word()
    {
        global $lang_admin_censoring;

        $id = intval(key($this->request->post('remove')));

        DB::for_table('censoring')
            ->find_one($id)
            ->delete();

        // Regenerate the censoring cache
        if (!defined('FORUM_CACHE_FUNCTIONS_LOADED')) {
            require FEATHER_ROOT.'include/cache.php';
        }

        generate_censoring_cache();

        redirect(get_link('admin/censoring/'),  $lang_admin_censoring['Word removed redirect']);
    }

    public function get_words()
    {
        $word_data = array();

        $word_data = DB::for_table('censoring')
                        ->order_by_asc('id')
                        ->find_array();

        return $word_data;
    }
}