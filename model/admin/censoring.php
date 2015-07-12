<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace model\admin;

class censoring
{
    public function __construct()
    {
        $this->feather = \Slim\Slim::getInstance();
        $this->db = $this->feather->db;
        $this->start = $this->feather->start;
        $this->config = $this->feather->config;
        $this->user = $this->feather->user;
        $this->request = $this->feather->request;
    }

    public function add_word()
    {
        global $lang_admin_censoring;

        confirm_referrer(get_link_r('admin/censoring/'));

        $search_for = feather_trim($this->request->post('new_search_for'));
        $replace_with = feather_trim($this->request->post('new_replace_with'));

        if ($search_for == '') {
            message($lang_admin_censoring['Must enter word message']);
        }

        $this->db->query('INSERT INTO '.$this->db->prefix.'censoring (search_for, replace_with) VALUES (\''.$this->db->escape($search_for).'\', \''.$this->db->escape($replace_with).'\')') or error('Unable to add censor word', __FILE__, __LINE__, $this->db->error());

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

        confirm_referrer(get_link_r('admin/censoring/'));

        $id = intval(key($this->request->post('update')));

        $search_for = feather_trim($this->request->post('search_for')[$id]);
        $replace_with = feather_trim($this->request->post('replace_with')[$id]);

        if ($search_for == '') {
            message($lang_admin_censoring['Must enter word message']);
        }

        $this->db->query('UPDATE '.$this->db->prefix.'censoring SET search_for=\''.$this->db->escape($search_for).'\', replace_with=\''.$this->db->escape($replace_with).'\' WHERE id='.$id) or error('Unable to update censor word', __FILE__, __LINE__, $this->db->error());

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

        confirm_referrer(get_link_r('admin/censoring/'));

        $id = intval(key($this->request->post('remove')));

        $this->db->query('DELETE FROM '.$this->db->prefix.'censoring WHERE id='.$id) or error('Unable to delete censor word', __FILE__, __LINE__, $this->db->error());

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

        $result = $this->db->query('SELECT id, search_for, replace_with FROM '.$this->db->prefix.'censoring ORDER BY id') or error('Unable to fetch censor word list', __FILE__, __LINE__, $this->db->error());

        while ($cur_word = $this->db->fetch_assoc($result)) {
            $word_data[] = $cur_word;
        }

        return $word_data;
    }
}