<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace model\admin;

class categories
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
 
    public function add_category()
    {
        global $lang_admin_categories;

        confirm_referrer(get_link_r('admin/categories/'));

        $new_cat_name = feather_trim($this->request->post('new_cat_name'));
        if ($new_cat_name == '') {
            message($lang_admin_categories['Must enter name message']);
        }

        $this->db->query('INSERT INTO '.$this->db->prefix.'categories (cat_name) VALUES(\''.$this->db->escape($new_cat_name).'\')') or error('Unable to create category', __FILE__, __LINE__, $this->db->error());

        redirect(get_link('admin/categories/'), $lang_admin_categories['Category added redirect']);
    }

    public function delete_category($cat_to_delete)
    {
        global $lang_admin_categories;

        @set_time_limit(0);

        $result = $this->db->query('SELECT id FROM '.$this->db->prefix.'forums WHERE cat_id='.$cat_to_delete) or error('Unable to fetch forum list', __FILE__, __LINE__, $this->db->error());
        $num_forums = $this->db->num_rows($result);

        for ($i = 0; $i < $num_forums; ++$i) {
            $cur_forum = $this->db->result($result, $i);

            // Prune all posts and topics
            $this->maintenance = new \model\admin\maintenance();
            $this->maintenance->prune($cur_forum, 1, -1);

            // Delete the forum
            $this->db->query('DELETE FROM '.$this->db->prefix.'forums WHERE id='.$cur_forum) or error('Unable to delete forum', __FILE__, __LINE__, $this->db->error());
        }

        // Locate any "orphaned redirect topics" and delete them
        $result = $this->db->query('SELECT t1.id FROM '.$this->db->prefix.'topics AS t1 LEFT JOIN '.$this->db->prefix.'topics AS t2 ON t1.moved_to=t2.id WHERE t2.id IS NULL AND t1.moved_to IS NOT NULL') or error('Unable to fetch redirect topics', __FILE__, __LINE__, $this->db->error());
        $num_orphans = $this->db->num_rows($result);

        if ($num_orphans) {
            for ($i = 0; $i < $num_orphans; ++$i) {
                $orphans[] = $this->db->result($result, $i);
            }

            $this->db->query('DELETE FROM '.$this->db->prefix.'topics WHERE id IN('.implode(',', $orphans).')') or error('Unable to delete redirect topics', __FILE__, __LINE__, $this->db->error());
        }

        // Delete the category
        $this->db->query('DELETE FROM '.$this->db->prefix.'categories WHERE id='.$cat_to_delete) or error('Unable to delete category', __FILE__, __LINE__, $this->db->error());

        // Regenerate the quick jump cache
        if (!defined('FORUM_CACHE_FUNCTIONS_LOADED')) {
            require FEATHER_ROOT.'include/cache.php';
        }

        generate_quickjump_cache();

        redirect(get_link('admin/categories/'), $lang_admin_categories['Category deleted redirect']);
    }

    public function get_category_name($cat_to_delete)
    {
        $result = $this->db->query('SELECT cat_name FROM '.$this->db->prefix.'categories WHERE id='.$cat_to_delete) or error('Unable to fetch category info', __FILE__, __LINE__, $this->db->error());
        $cat_name = $this->db->result($result);

        return $cat_name;
    }

    public function update_categories($categories)
    {
        global $lang_admin_categories;

        foreach ($categories as $cat_id => $cur_cat) {
            $cur_cat['name'] = feather_trim($cur_cat['name']);
            $cur_cat['order'] = feather_trim($cur_cat['order']);

            if ($cur_cat['name'] == '') {
                message($lang_admin_categories['Must enter name message']);
            }

            if ($cur_cat['order'] == '' || preg_match('%[^0-9]%', $cur_cat['order'])) {
                message($lang_admin_categories['Must enter integer message']);
            }

            $this->db->query('UPDATE '.$this->db->prefix.'categories SET cat_name=\''.$this->db->escape($cur_cat['name']).'\', disp_position='.$cur_cat['order'].' WHERE id='.intval($cat_id)) or error('Unable to update category', __FILE__, __LINE__, $this->db->error());
        }

        // Regenerate the quick jump cache
        if (!defined('FORUM_CACHE_FUNCTIONS_LOADED')) {
            require FEATHER_ROOT.'include/cache.php';
        }

        generate_quickjump_cache();

        redirect(get_link('admin/categories/'), $lang_admin_categories['Categories updated redirect']);
    }

    public function get_cat_list()
    {
        $cat_list = array();

        $result = $this->db->query('SELECT id, cat_name, disp_position FROM '.$this->db->prefix.'categories ORDER BY disp_position') or error('Unable to fetch category list', __FILE__, __LINE__, $this->db->error());
        $num_cats = $this->db->num_rows($result);

        for ($i = 0; $i < $num_cats; ++$i) {
            $cat_list[] = $this->db->fetch_assoc($result);
        }

        return $cat_list;
    }
}