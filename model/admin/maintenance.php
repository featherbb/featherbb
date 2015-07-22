<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace model\admin;

class maintenance
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
 
    public function rebuild()
    {
        global $db_type, $lang_admin_maintenance;

        $per_page = $this->request->get('i_per_page') ? intval($this->request->get('i_per_page')) : 0;

        // Check per page is > 0
        if ($per_page < 1) {
            message($lang_admin_maintenance['Posts must be integer message']);
        }

        @set_time_limit(0);

        // If this is the first cycle of posts we empty the search index before we proceed
        if ($this->request->get('i_empty_index')) {
            // This is the only potentially "dangerous" thing we can do here, so we check the referer
            confirm_referrer(get_link_r('admin/maintenance/'));

            $this->db->truncate_table('search_matches') or error('Unable to empty search index match table', __FILE__, __LINE__, $this->db->error());
            $this->db->truncate_table('search_words') or error('Unable to empty search index words table', __FILE__, __LINE__, $this->db->error());

            // Reset the sequence for the search words (not needed for SQLite)
            switch ($db_type) {
                case 'mysql':
                case 'mysqli':
                case 'mysql_innodb':
                case 'mysqli_innodb':
                    $result = $this->db->query('ALTER TABLE '.$this->db->prefix.'search_words auto_increment=1') or error('Unable to update table auto_increment', __FILE__, __LINE__, $this->db->error());
                    break;

                case 'pgsql';
                    $result = $this->db->query('SELECT setval(\''.$this->db->prefix.'search_words_id_seq\', 1, false)') or error('Unable to update sequence', __FILE__, __LINE__, $this->db->error());
            }
        }
    }

    public function get_query_str()
    {
        global $lang_admin_maintenance;

        $query_str = '';

        $per_page = $this->request->get('i_per_page') ? intval($this->request->get('i_per_page')) : 0;
        $start_at = $this->request->get('i_start_at') ? intval($this->request->get('i_start_at')) : 0;

        require FEATHER_ROOT.'include/search_idx.php';

        // Fetch posts to process this cycle
        $result = $this->db->query('SELECT p.id, p.message, t.subject, t.first_post_id FROM '.$this->db->prefix.'posts AS p INNER JOIN '.$this->db->prefix.'topics AS t ON t.id=p.topic_id WHERE p.id >= '.$start_at.' ORDER BY p.id ASC LIMIT '.$per_page) or error('Unable to fetch posts', __FILE__, __LINE__, $this->db->error());

        $end_at = 0;
        while ($cur_item = $this->db->fetch_assoc($result)) {
            echo '<p><span>'.sprintf($lang_admin_maintenance['Processing post'], $cur_item['id']).'</span></p>'."\n";

            if ($cur_item['id'] == $cur_item['first_post_id']) {
                update_search_index('post', $cur_item['id'], $cur_item['message'], $cur_item['subject']);
            } else {
                update_search_index('post', $cur_item['id'], $cur_item['message']);
            }

            $end_at = $cur_item['id'];
        }

        // Check if there is more work to do
        if ($end_at > 0) {
            $result = $this->db->query('SELECT id FROM '.$this->db->prefix.'posts WHERE id > '.$end_at.' ORDER BY id ASC LIMIT 1') or error('Unable to fetch next ID', __FILE__, __LINE__, $this->db->error());

            if ($this->db->num_rows($result) > 0) {
                $query_str = '?action=rebuild&i_per_page='.$per_page.'&i_start_at='.$this->db->result($result);
            }
        }

        $this->db->end_transaction();
        $this->db->close();

        return $query_str;
    }

    //
    // Delete topics from $forum_id that are "older than" $prune_date (if $prune_sticky is 1, sticky topics will also be deleted)
    //
    public function prune($forum_id, $prune_sticky, $prune_date)
    {
        

        $extra_sql = ($prune_date != -1) ? ' AND last_post<'.$prune_date : '';

        if (!$prune_sticky) {
            $extra_sql .= ' AND sticky=\'0\'';
        }

        // Fetch topics to prune
        $result = $this->db->query('SELECT id FROM '.$this->db->prefix.'topics WHERE forum_id='.$forum_id.$extra_sql, true) or error('Unable to fetch topics', __FILE__, __LINE__, $this->db->error());

        $topic_ids = '';
        while ($row = $this->db->fetch_row($result)) {
            $topic_ids .= (($topic_ids != '') ? ',' : '').$row[0];
        }

        if ($topic_ids != '') {
            // Fetch posts to prune
            $result = $this->db->query('SELECT id FROM '.$this->db->prefix.'posts WHERE topic_id IN('.$topic_ids.')', true) or error('Unable to fetch posts', __FILE__, __LINE__, $this->db->error());

            $post_ids = '';
            while ($row = $this->db->fetch_row($result)) {
                $post_ids .= (($post_ids != '') ? ',' : '').$row[0];
            }

            if ($post_ids != '') {
                // Delete topics
                $this->db->query('DELETE FROM '.$this->db->prefix.'topics WHERE id IN('.$topic_ids.')') or error('Unable to prune topics', __FILE__, __LINE__, $this->db->error());
                // Delete subscriptions
                $this->db->query('DELETE FROM '.$this->db->prefix.'topic_subscriptions WHERE topic_id IN('.$topic_ids.')') or error('Unable to prune subscriptions', __FILE__, __LINE__, $this->db->error());
                // Delete posts
                $this->db->query('DELETE FROM '.$this->db->prefix.'posts WHERE id IN('.$post_ids.')') or error('Unable to prune posts', __FILE__, __LINE__, $this->db->error());

                // We removed a bunch of posts, so now we have to update the search index
                require_once FEATHER_ROOT.'include/search_idx.php';
                strip_search_index($post_ids);
            }
        }
    }

    public function prune_comply($prune_from, $prune_sticky)
    {
        global $lang_admin_maintenance;

        confirm_referrer(get_link_r('admin/maintenance/'));

        $prune_days = intval($this->request->post('prune_days'));
        $prune_date = ($prune_days) ? time() - ($prune_days * 86400) : -1;

        @set_time_limit(0);

        if ($prune_from == 'all') {
            $result = $this->db->query('SELECT id FROM '.$this->db->prefix.'forums') or error('Unable to fetch forum list', __FILE__, __LINE__, $this->db->error());
            $num_forums = $this->db->num_rows($result);

            for ($i = 0; $i < $num_forums; ++$i) {
                $fid = $this->db->result($result, $i);

                $this->prune($fid, $prune_sticky, $prune_date);
                update_forum($fid);
            }
        } else {
            $prune_from = intval($prune_from);
            $this->prune($prune_from, $prune_sticky, $prune_date);
            update_forum($prune_from);
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

        redirect(get_link('admin/maintenance/'), $lang_admin_maintenance['Posts pruned redirect']);
    }

    public function get_info_prune($prune_sticky, $prune_from)
    {
        global $lang_admin_maintenance;

        $prune = array();

        $prune['days'] = feather_trim($this->request->post('req_prune_days'));
        if ($prune['days'] == '' || preg_match('%[^0-9]%', $prune['days'])) {
            message($lang_admin_maintenance['Days must be integer message']);
        }

        $prune['date'] = time() - ($prune['days'] * 86400);

        // Concatenate together the query for counting number of topics to prune
        $sql = 'SELECT COUNT(id) FROM '.$this->db->prefix.'topics WHERE last_post<'.$prune['date'].' AND moved_to IS NULL';

        if ($prune_sticky == '0') {
            $sql .= ' AND sticky=0';
        }

        if ($prune_from != 'all') {
            $prune_from = intval($prune_from);
            $sql .= ' AND forum_id='.$prune_from;

            // Fetch the forum name (just for cosmetic reasons)
            $result = $this->db->query('SELECT forum_name FROM '.$this->db->prefix.'forums WHERE id='.$prune_from) or error('Unable to fetch forum name', __FILE__, __LINE__, $this->db->error());
            $prune['forum'] = '"'.feather_escape($this->db->result($result)).'"';
        } else {
            $prune['forum'] = $lang_admin_maintenance['All forums'];
        }

        $result = $this->db->query($sql) or error('Unable to fetch topic prune count', __FILE__, __LINE__, $this->db->error());
        $prune['num_topics'] = $this->db->result($result);

        if (!$prune['num_topics']) {
            message(sprintf($lang_admin_maintenance['No old topics message'], $prune['days']));
        }

        return $prune;
    }

    public function get_categories()
    {
        
        
        $output = '';

        $result = $this->db->query('SELECT c.id AS cid, c.cat_name, f.id AS fid, f.forum_name FROM '.$this->db->prefix.'categories AS c INNER JOIN '.$this->db->prefix.'forums AS f ON c.id=f.cat_id WHERE f.redirect_url IS NULL ORDER BY c.disp_position, c.id, f.disp_position') or error('Unable to fetch category/forum list', __FILE__, __LINE__, $this->db->error());

        $cur_category = 0;
        while ($forum = $this->db->fetch_assoc($result)) {
            if ($forum['cid'] != $cur_category) {
                // Are we still in the same category?

                if ($cur_category) {
                    $output .= "\t\t\t\t\t\t\t\t\t\t\t".'</optgroup>'."\n";
                }

                $output .=  "\t\t\t\t\t\t\t\t\t\t\t".'<optgroup label="'.feather_escape($forum['cat_name']).'">'."\n";
                $cur_category = $forum['cid'];
            }

            $output .=  "\t\t\t\t\t\t\t\t\t\t\t\t".'<option value="'.$forum['fid'].'">'.feather_escape($forum['forum_name']).'</option>'."\n";
        }
        
        return $output;
    }
    
    public function get_first_id()
    {
        $first_id = '';
        $result = $this->db->query('SELECT id FROM '.$this->db->prefix.'posts ORDER BY id ASC LIMIT 1') or error('Unable to fetch topic info', __FILE__, __LINE__, $this->db->error());
        if ($this->db->num_rows($result)) {
            $first_id = $this->db->result($result);
        }
        
        return $first_id;
    }
}