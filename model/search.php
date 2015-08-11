<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace model;

use DB;

class search
{
    public function __construct()
    {
        $this->feather = \Slim\Slim::getInstance();
        $this->start = $this->feather->start;
        $this->config = $this->feather->config;
        $this->user = $this->feather->user;
        $this->request = $this->feather->request;
    }
 
 
    public function get_search_results()
    {
        global $db_type, $lang_common, $lang_search;

        $search = array();

        $action = ($this->request->get('action')) ? $this->request->get('action') : null;
        $forums = $this->request->get('forums') ? (is_array($this->request->get('forums')) ? $this->request->get('forums') : array_filter(explode(',', $this->request->get('forums')))) : ($this->request->get('forums') ? array($this->request->get('forums')) : array());
        $sort_dir = ($this->request->get('sort_dir') && $this->request->get('sort_dir') == 'DESC') ? 'DESC' : 'ASC';

        $forums = array_map('intval', $forums);

        // Allow the old action names for backwards compatibility reasons
        if ($action == 'show_user') {
            $action = 'show_user_posts';
        } elseif ($action == 'show_24h') {
            $action = 'show_recent';
        }

        // If a search_id was supplied
        if ($this->request->get('search_id')) {
            $search_id = intval($this->request->get('search_id'));
            if ($search_id < 1) {
                message($lang_common['Bad request'], '404');
            }
        }
        // If it's a regular search (keywords and/or author)
        elseif ($action == 'search') {
            $keywords = ($this->request->get('keywords')) ? utf8_strtolower(feather_trim($this->request->get('keywords'))) : null;
            $author = ($this->request->get('author')) ? utf8_strtolower(feather_trim($this->request->get('author'))) : null;

            if (preg_match('%^[\*\%]+$%', $keywords) || (feather_strlen(str_replace(array('*', '%'), '', $keywords)) < FEATHER_SEARCH_MIN_WORD && !is_cjk($keywords))) {
                $keywords = '';
            }

            if (preg_match('%^[\*\%]+$%', $author) || feather_strlen(str_replace(array('*', '%'), '', $author)) < 2) {
                $author = '';
            }

            if (!$keywords && !$author) {
                message($lang_search['No terms']);
            }

            if ($author) {
                $author = str_replace('*', '%', $author);
            }

            $show_as = ($this->request->get('show_as') && $this->request->get('show_as') == 'topics') ? 'topics' : 'posts';
            $sort_by = ($this->request->get('sort_by')) ? intval($this->request->get('sort_by')) : 0;
            $search_in = (!$this->request->get('search_in') || $this->request->get('search_in') == '0') ? 0 : (($this->request->get('search_in') == '1') ? 1 : -1);
        }
        // If it's a user search (by ID)
        elseif ($action == 'show_user_posts' || $action == 'show_user_topics' || $action == 'show_subscriptions') {
            $user_id = ($this->request->get('user_id')) ? intval($this->request->get('user_id')) : $this->user->id;
            if ($user_id < 2) {
                message($lang_common['Bad request'], '404');
            }

            // Subscribed topics can only be viewed by admins, moderators and the users themselves
            if ($action == 'show_subscriptions' && !$this->user->is_admmod && $user_id != $this->user->id) {
                message($lang_common['No permission'], '403');
            }
        } elseif ($action == 'show_recent') {
            $interval = $this->request->get('value') ? intval($this->request->get('value')) : 86400;
        } elseif ($action == 'show_replies') {
            if ($this->user->is_guest) {
                message($lang_common['Bad request'], '404');
            }
        } elseif ($action != 'show_new' && $action != 'show_unanswered') {
            message($lang_common['Bad request'], '404');
        }


        // If a valid search_id was supplied we attempt to fetch the search results from the db
        if (isset($search_id)) {
            $ident = ($this->user->is_guest) ? get_remote_address() : $this->user->username;

            $search_data = DB::for_table('search_cache')
                                ->where('id', $search_id)
                                ->where('ident', $ident)
                                ->find_one_col('search_data');

            if ($search_data) {
                $temp = unserialize($search_data);

                $search_ids = unserialize($temp['search_ids']);
                $num_hits = $temp['num_hits'];
                $sort_by = $temp['sort_by'];
                $sort_dir = $temp['sort_dir'];
                $show_as = $temp['show_as'];
                $search_type = $temp['search_type'];

                unset($temp);
            } else {
                message($lang_search['No hits']);
            }
        } else {
            $keyword_results = $author_results = array();

            // Search a specific forum?
            $forum_sql = (!empty($forums) || (empty($forums) && $this->config['o_search_all_forums'] == '0' && !$this->user->is_admmod)) ? ' AND t.forum_id IN ('.implode(',', $forums).')' : '';

            if (!empty($author) || !empty($keywords)) {
                // Flood protection
                if ($this->user->last_search && (time() - $this->user->last_search) < $this->user->g_search_flood && (time() - $this->user->last_search) >= 0) {
                    message(sprintf($lang_search['Search flood'], $this->user->g_search_flood, $this->user->g_search_flood - (time() - $this->user->last_search)));
                }

                if (!$this->user->is_guest) {
                    DB::for_table('users')->where('id', $this->user->id)
                                                        ->update_many('last_search', time());
                } else {
                    DB::for_table('online')->where('ident', get_remote_address())
                                                         ->update_many('last_search', time());
                }

                switch ($sort_by) {
                    case 1:
                        $sort_by_sql = ($show_as == 'topics') ? 't.poster' : 'p.poster';
                        $sort_type = SORT_STRING;
                        break;

                    case 2:
                        $sort_by_sql = 't.subject';
                        $sort_type = SORT_STRING;
                        break;

                    case 3:
                        $sort_by_sql = 't.forum_id';
                        $sort_type = SORT_NUMERIC;
                        break;

                    case 4:
                        $sort_by_sql = 't.last_post';
                        $sort_type = SORT_NUMERIC;
                        break;

                    default:
                        $sort_by_sql = ($show_as == 'topics') ? 't.last_post' : 'p.posted';
                        $sort_type = SORT_NUMERIC;
                        break;
                }

                // If it's a search for keywords
                if ($keywords) {
                    // split the keywords into words
                    $keywords_array = split_words($keywords, false);

                    if (empty($keywords_array)) {
                        message($lang_search['No hits']);
                    }

                    // Should we search in message body or topic subject specifically?
                    $search_in_cond = ($search_in) ? (($search_in > 0) ? ' AND m.subject_match = 0' : ' AND m.subject_match = 1') : '';

                    $word_count = 0;
                    $match_type = 'and';

                    $sort_data = array();
                    foreach ($keywords_array as $cur_word) {
                        switch ($cur_word) {
                            case 'and':
                            case 'or':
                            case 'not':
                                $match_type = $cur_word;
                                break;

                            default:
                            {
                                if (is_cjk($cur_word)) {
                                    $where_cond = str_replace('*', '%', $cur_word);
                                    $where_cond_cjk = ($search_in ? (($search_in > 0) ? 'p.message LIKE %:where_cond%' : 't.subject LIKE %:where_cond%') : 'p.message LIKE %:where_cond% OR t.subject LIKE %:where_cond%');

                                    $result = DB::for_table('posts')->raw_query('SELECT p.id AS post_id, p.topic_id, '.$sort_by_sql.' AS sort_by FROM '.$this->feather->prefix.'posts AS p INNER JOIN '.$this->feather->prefix.'topics AS t ON t.id=p.topic_id LEFT JOIN '.$this->feather->prefix.'forum_perms AS fp ON (fp.forum_id=t.forum_id AND fp.group_id='.$this->user->g_id.') WHERE ('.$where_cond_cjk.') AND (fp.read_forum IS NULL OR fp.read_forum=1)'.$forum_sql, array(':where_cond' => $where_cond))->find_many();
                                } else {
                                    $result = DB::for_table('posts')->raw_query('SELECT m.post_id, p.topic_id, '.$sort_by_sql.' AS sort_by FROM '.$this->feather->prefix.'search_words AS w INNER JOIN '.$this->feather->prefix.'search_matches AS m ON m.word_id = w.id INNER JOIN '.$this->feather->prefix.'posts AS p ON p.id=m.post_id INNER JOIN '.$this->feather->prefix.'topics AS t ON t.id=p.topic_id LEFT JOIN '.$this->feather->prefix.'forum_perms AS fp ON (fp.forum_id=t.forum_id AND fp.group_id='.$this->user->g_id.') WHERE w.word LIKE :where_cond'.$search_in_cond.' AND (fp.read_forum IS NULL OR fp.read_forum=1)'.$forum_sql, array(':where_cond' => str_replace('*', '%', $cur_word)))->find_many();
                                }

                                $row = array();
                                foreach($result as $temp) {
                                    $row[$temp['post_id']] = $temp['topic_id'];

                                    if (!$word_count) {
                                        $keyword_results[$temp['post_id']] = $temp['topic_id'];
                                        $sort_data[$temp['post_id']] = $temp['sort_by'];
                                    } elseif ($match_type == 'or') {
                                        $keyword_results[$temp['post_id']] = $temp['topic_id'];
                                        $sort_data[$temp['post_id']] = $temp['sort_by'];
                                    } elseif ($match_type == 'not') {
                                        unset($keyword_results[$temp['post_id']]);
                                        unset($sort_data[$temp['post_id']]);
                                    }
                                }

                                if ($match_type == 'and' && $word_count) {
                                    foreach ($keyword_results as $post_id => $topic_id) {
                                        if (!isset($row[$post_id])) {
                                            unset($keyword_results[$post_id]);
                                            unset($sort_data[$post_id]);
                                        }
                                    }
                                }

                                ++$word_count;
                                $pdo = DB::get_db();
                                $pdo = null;

                                break;
                            }
                        }
                    }

                    // Sort the results - annoyingly array_multisort re-indexes arrays with numeric keys, so we need to split the keys out into a separate array then combine them again after
                    $post_ids = array_keys($keyword_results);
                    $topic_ids = array_values($keyword_results);

                    array_multisort(array_values($sort_data), $sort_dir == 'DESC' ? SORT_DESC : SORT_ASC, $sort_type, $post_ids, $topic_ids);

                    // combine the arrays back into a key=>value array (array_combine is PHP5 only unfortunately)
                    $num_results = count($keyword_results);
                    $keyword_results = array();
                    for ($i = 0;$i < $num_results;$i++) {
                        $keyword_results[$post_ids[$i]] = $topic_ids[$i];
                    }

                    unset($sort_data, $post_ids, $topic_ids);
                }

                // If it's a search for author name (and that author name isn't Guest)
                if ($author && $author != 'guest' && $author != utf8_strtolower($lang_common['Guest'])) {
                    $username_exists = DB::for_table('users')->select('id')->where_like('username', $author)->find_many();

                    if ($username_exists) {
                        $user_ids = array();
                        foreach ($username_exists as $row) {
                            $user_ids[] = $row['id'];
                        }

                        $result = DB::for_table('posts')->raw_query('SELECT p.id AS post_id, p.topic_id FROM '.$this->feather->prefix.'posts AS p INNER JOIN '.$this->feather->prefix.'topics AS t ON t.id=p.topic_id LEFT JOIN '.$this->feather->prefix.'forum_perms AS fp ON (fp.forum_id=t.forum_id AND fp.group_id='.$this->user->g_id.') WHERE (fp.read_forum IS NULL OR fp.read_forum=1) AND p.poster_id IN('.implode(',', $user_ids).')'.$forum_sql.' ORDER BY '.$sort_by_sql.' '.$sort_dir)->find_many();

                        foreach($result as $temp) {
                            $author_results[$temp['post_id']] = $temp['topic_id'];
                        }

                        $pdo = DB::get_db();
                        $pdo = null;
                    }
                }

                // If we searched for both keywords and author name we want the intersection between the results
                if ($author && $keywords) {
                    $search_ids = array_intersect_assoc($keyword_results, $author_results);
                    $search_type = array('both', array($keywords, feather_trim($this->request->get('author'))), implode(',', $forums), $search_in);
                } elseif ($keywords) {
                    $search_ids = $keyword_results;
                    $search_type = array('keywords', $keywords, implode(',', $forums), $search_in);
                } else {
                    $search_ids = $author_results;
                    $search_type = array('author', feather_trim($this->request->get('author')), implode(',', $forums), $search_in);
                }

                unset($keyword_results, $author_results);

                if ($show_as == 'topics') {
                    $search_ids = array_values($search_ids);
                } else {
                    $search_ids = array_keys($search_ids);
                }

                $search_ids = array_unique($search_ids);

                $num_hits = count($search_ids);
                if (!$num_hits) {
                    message($lang_search['No hits']);
                }
            } elseif ($action == 'show_new' || $action == 'show_recent' || $action == 'show_replies' || $action == 'show_user_posts' || $action == 'show_user_topics' || $action == 'show_subscriptions' || $action == 'show_unanswered') {
                $search_type = array('action', $action);
                $show_as = 'topics';
                // We want to sort things after last post
                $sort_by = 0;
                $sort_dir = 'DESC';

                $where_search_action = array(
                    array('fp.read_forum' => 'IS NULL'),
                    array('fp.read_forum' => '1')
                );

                // If it's a search for new posts since last visit
                if ($action == 'show_new') {
                    if ($this->user->is_guest) {
                        message($lang_common['No permission'], '403');
                    }

                    $result = DB::for_table('topics')
                                ->table_alias('t')
                                ->select('t.id')
                                ->left_outer_join('forum_perms', array('fp.forum_id', '=', 't.forum_id'), 'fp')
                                ->left_outer_join('forum_perms', array('fp.group_id', '=', $this->user->g_id), null, true)
                                ->where_any_is($where_search_action)
                                ->where_gt('t.last_post', $this->user->last_visit)
                                ->where_null('t.moved_to')
                                ->order_by_desc('t.last_post');


                    if ($this->request->get('fid')) {
                        $result = $result->where('t.forum_id', intval($this->request->get('fid')));
                    }

                    $result = $result->find_many();

                    $num_hits = count($result);

                    if (!$num_hits) {
                        message($lang_search['No new posts']);
                    }
                }
                // If it's a search for recent posts (in a certain time interval)
                elseif ($action == 'show_recent') {
                    $result = DB::for_table('topics')
                                ->table_alias('t')
                                ->select('t.id')
                                ->left_outer_join('forum_perms', array('fp.forum_id', '=', 't.forum_id'), 'fp')
                                ->left_outer_join('forum_perms', array('fp.group_id', '=', $this->user->g_id), null, true)
                                ->where_any_is($where_search_action)
                                ->where_gt('t.last_post', time() - $interval)
                                ->where_null('t.moved_to')
                                ->order_by_desc('t.last_post');

                    if ($this->request->get('fid')) {
                        $result = $result->where('t.forum_id', intval($this->request->get('fid')));
                    }

                    $result = $result->find_many();

                    $num_hits = count($result);

                    if (!$num_hits) {
                        message($lang_search['No recent posts']);
                    }
                }
                // If it's a search for topics in which the user has posted
                elseif ($action == 'show_replies') {
                    $result = DB::for_table('topics')
                                ->table_alias('t')
                                ->select('t.id')
                                ->inner_join('posts', array('t.id', '=', 'p.topic_id'), 'p')
                                ->left_outer_join('forum_perms', array('fp.forum_id', '=', 't.forum_id'), 'fp')
                                ->left_outer_join('forum_perms', array('fp.group_id', '=', $this->user->g_id), null, true)
                                ->where_any_is($where_search_action)
                                ->where('p.poster_id', $this->user->id)
                                ->group_by('t.id');

                    if ($db_type == 'pgsql') {
                        $result = $result->group_by('t.last_post');
                    }

                    $result = $result->find_many();

                    $num_hits = count($result);

                    if (!$num_hits) {
                        message($lang_search['No user posts']);
                    }
                }
                // If it's a search for posts by a specific user ID
                elseif ($action == 'show_user_posts') {
                    $show_as = 'posts';

                    $result = DB::for_table('posts')
                                ->table_alias('p')
                                ->select('p.id')
                                ->inner_join('topics', array('p.topic_id', '=', 't.id'), 't')
                                ->left_outer_join('forum_perms', array('fp.forum_id', '=', 't.forum_id'), 'fp')
                                ->left_outer_join('forum_perms', array('fp.group_id', '=', $this->user->g_id), null, true)
                                ->where_any_is($where_search_action)
                                ->where('p.poster_id', $user_id)
                                ->order_by_desc('p.posted');

                    $result = $result->find_many();

                    $num_hits = count($result);

                    if (!$num_hits) {
                        message($lang_search['No user posts']);
                    }

                    // Pass on the user ID so that we can later know whose posts we're searching for
                    $search_type[2] = $user_id;
                }
                // If it's a search for topics by a specific user ID
                elseif ($action == 'show_user_topics') {
                    $result = DB::for_table('topics')
                                ->table_alias('t')
                                ->select('t.id')
                                ->inner_join('posts', array('t.first_post_id', '=', 'p.id'), 'p')
                                ->left_outer_join('forum_perms', array('fp.forum_id', '=', 't.forum_id'), 'fp')
                                ->left_outer_join('forum_perms', array('fp.group_id', '=', $this->user->g_id), null, true)
                                ->where_any_is($where_search_action)
                                ->where('p.poster_id', $user_id)
                                ->order_by_desc('t.last_post');

                    $result = $result->find_many();

                    $num_hits = count($result);

                    if (!$num_hits) {
                        message($lang_search['No user topics']);
                    }

                    // Pass on the user ID so that we can later know whose topics we're searching for
                    $search_type[2] = $user_id;
                }
                // If it's a search for subscribed topics
                elseif ($action == 'show_subscriptions') {
                    if ($this->user->is_guest) {
                        message($lang_common['Bad request'], '404');
                    }

                    $result = DB::for_table('topics')
                                ->table_alias('t')
                                ->select('t.id')
                                ->inner_join('topic_subscriptions', array('t.id', '=', 's.topic_id'), 's')
                                ->inner_join('topic_subscriptions', array('s.user_id', '=', $user_id), null, true)
                                ->left_outer_join('forum_perms', array('fp.forum_id', '=', 't.forum_id'), 'fp')
                                ->left_outer_join('forum_perms', array('fp.group_id', '=', $this->user->g_id), null, true)
                                ->where_any_is($where_search_action)
                                ->order_by_desc('t.last_post');

                    $result = $result->find_many();

                    $num_hits = count($result);

                    if (!$num_hits) {
                        message($lang_search['No subscriptions']);
                    }

                    // Pass on user ID so that we can later know whose subscriptions we're searching for
                    $search_type[2] = $user_id;
                }
                // If it's a search for unanswered posts
                else {
                    $result = DB::for_table('topics')
                                ->table_alias('t')
                                ->select('t.id')
                                ->left_outer_join('forum_perms', array('fp.forum_id', '=', 't.forum_id'), 'fp')
                                ->left_outer_join('forum_perms', array('fp.group_id', '=', $this->user->g_id), null, true)
                                ->where('t.num_replies', 0)
                                ->where_null('t.moved_to')
                                ->where_any_is($where_search_action)
                                ->order_by_desc('t.last_post');

                    $result = $result->find_many();

                    $num_hits = count($result);

                    if (!$num_hits) {
                        message($lang_search['No unanswered']);
                    }
                }

                $search_ids = array();
                foreach($result as $row) {
                    $search_ids[] = $row['id'];
                }

                $pdo = DB::get_db();
                $pdo = null;
            } else {
                message($lang_common['Bad request'], '404');
            }


            // Prune "old" search results
            $old_searches = array();
            $result = DB::for_table('online')->select('ident')->find_many();

            if ($result) {
                foreach($result as $row) {
                    $old_searches[] = $row['ident'];
                }

                DB::for_table('search_cache')->where_not_in('ident', $old_searches)->delete_many();
            }

            // Fill an array with our results and search properties
            $temp = serialize(array(
                'search_ids'        => serialize($search_ids),
                'num_hits'            => $num_hits,
                'sort_by'            => $sort_by,
                'sort_dir'            => $sort_dir,
                'show_as'            => $show_as,
                'search_type'        => $search_type
            ));
            $search_id = mt_rand(1, 2147483647);

            $ident = ($this->user->is_guest) ? get_remote_address() : $this->user->username;

            $insert_cache = array(
                'id'   =>  $search_id,
                'ident'  =>  $ident,
                'search_data'  =>  $temp,
            );

            DB::for_table('search_cache')
                ->create()
                ->set($insert_cache)
                ->save();

            if ($search_type[0] != 'action') {
                $this->db->end_transaction();
                $this->db->close();

                // Redirect the user to the cached result page
                header('Location: '.get_link('search/?search_id='.$search_id));
                exit;
            }
        }

        // If we're on the new posts search, display a "mark all as read" link
        if (!$this->user->is_guest && $search_type[0] == 'action' && $search_type[1] == 'show_new') {
            $search['forum_actions'][] = '<a href="'.get_link('mark-read/').'">'.$lang_common['Mark all as read'].'</a>';
        }

        // Fetch results to display
        if (!empty($search_ids)) {
            // We have results
            $search['is_result'] = true;

            switch ($sort_by) {
                case 1:
                    $sort_by_sql = ($show_as == 'topics') ? 't.poster' : 'p.poster';
                    break;

                case 2:
                    $sort_by_sql = 't.subject';
                    break;

                case 3:
                    $sort_by_sql = 't.forum_id';
                    break;

                default:
                    $sort_by_sql = ($show_as == 'topics') ? 't.last_post' : 'p.posted';
                    break;
            }

            // Determine the topic or post offset (based on $_GET['p'])
            $per_page = ($show_as == 'posts') ? $this->user->disp_posts : $this->user->disp_topics;
            $num_pages = ceil($num_hits / $per_page);

            $p = (!$this->request->get('p') || $this->request->get('p') <= 1 || $this->request->get('p') > $num_pages) ? 1 : intval($this->request->get('p'));
            $start_from = $per_page * ($p - 1);
            $search['start_from'] = $start_from;

            // Generate paging links
            $search['paging_links'] = '<span class="pages-label">'.$lang_common['Pages'].' </span>'.paginate_old($num_pages, $p, '?search_id='.$search_id);

            // throw away the first $start_from of $search_ids, only keep the top $per_page of $search_ids
            $search_ids = array_slice($search_ids, $start_from, $per_page);

            // Run the query and fetch the results
            if ($show_as == 'posts') {
                $select_search_post = array('pid' => 'p.id', 'pposter' => 'p.poster', 'pposted' => 'p.posted', 'p.poster_id', 'p.message', 'p.hide_smilies', 'tid' => 't.id', 't.poster', 't.subject', 't.first_post_id', 't.last_post', 't.last_post_id', 't.last_poster', 't.num_replies', 't.forum_id', 'f.forum_name');

                $result = DB::for_table('posts')
                                ->table_alias('p')
                                ->select_many($select_search_post)
                                ->inner_join('topics', array('t.id', '=', 'p.topic_id'), 't')
                                ->inner_join('forums', array('f.id', '=', 't.forum_id'), 'f')
                                ->where_in('p.id', $search_ids)
                                ->order_by($sort_by_sql, $sort_dir)
                                ->find_many();

            } else {

                $select_search_topic = array('tid' => 't.id', 't.poster', 't.subject', 't.last_post', 't.last_post_id', 't.last_poster', 't.num_replies', 't.closed', 't.sticky', 't.forum_id', 'f.forum_name');

                $result = DB::for_table('topics')
                    ->table_alias('t')
                    ->select_many($select_search_topic)
                    ->inner_join('forums', array('f.id', '=', 't.forum_id'), 'f')
                    ->where_in('t.id', $search_ids)
                    ->order_by($sort_by_sql, $sort_dir)
                    ->find_many();
            }

            $search['search_set'] = array();
            foreach($result as $row) {
                $search['search_set'][] = $row;
            }

            $search['crumbs_text']['show_as'] = $lang_search['Search'];

            if ($search_type[0] == 'action') {
                if ($search_type[1] == 'show_user_topics') {
                    $search['crumbs_text']['search_type'] = '<a href="'.get_link('search/?action=show_user_topics&amp;user_id='.$search_type[2]).'">'.sprintf($lang_search['Quick search show_user_topics'], feather_escape($search['search_set'][0]['poster'])).'</a>';
                } elseif ($search_type[1] == 'show_user_posts') {
                    $search['crumbs_text']['search_type'] = '<a href="'.get_link('search/?action=show_user_posts&amp;user_id='.$search_type[2]).'">'.sprintf($lang_search['Quick search show_user_posts'], feather_escape($search['search_set'][0]['pposter'])).'</a>';
                } elseif ($search_type[1] == 'show_subscriptions') {
                    // Fetch username of subscriber
                    $subscriber_id = $search_type[2];
                    $subscriber_name = DB::for_table('users')->where('id', $subscriber_id)->find_one_col('username');

                    if (!$subscriber_name) {
                        message($lang_common['Bad request'], '404');
                    }

                    $search['crumbs_text']['search_type'] = '<a href="'.get_link('search/?action=show_subscription&amp;user_id='.$subscriber_id).'">'.sprintf($lang_search['Quick search show_subscriptions'], feather_escape($subscriber_name)).'</a>';
                } else {
                    $search_url = str_replace('_', '/', $search_type[1]);
                    $search['crumbs_text']['search_type'] = '<a href="'.get_link('search/'.$search_url.'/').'">'.$lang_search['Quick search '.$search_type[1]].'</a>';
                }
            } else {
                $keywords = $author = '';

                if ($search_type[0] == 'both') {
                    list($keywords, $author) = $search_type[1];
                    $search['crumbs_text']['search_type'] = sprintf($lang_search['By both show as '.$show_as], feather_escape($keywords), feather_escape($author));
                } elseif ($search_type[0] == 'keywords') {
                    $keywords = $search_type[1];
                    $search['crumbs_text']['search_type'] = sprintf($lang_search['By keywords show as '.$show_as], feather_escape($keywords));
                } elseif ($search_type[0] == 'author') {
                    $author = $search_type[1];
                    $search['crumbs_text']['search_type'] = sprintf($lang_search['By user show as '.$show_as], feather_escape($author));
                }

                $search['crumbs_text']['search_type'] = '<a href="'.get_link('search/?action=search&amp;keywords='.urlencode($keywords).'&amp;author='.urlencode($author).'&amp;forums='.$search_type[2].'&amp;search_in='.$search_type[3].'&amp;sort_by='.$sort_by.'&amp;sort_dir='.$sort_dir.'&amp;show_as='.$show_as).'">'.$search['crumbs_text']['search_type'].'</a>';
            }
        }

        $search['show_as'] = $show_as;

        return $search;
    }

    public function display_search_results($search)
    {
        global $lang_forum, $lang_common, $lang_topic, $lang_search, $pd;

        // Get topic/forum tracking data
        if (!$this->user->is_guest) {
            $tracked_topics = get_tracked_topics();
        }

        $post_count = $topic_count = 0;

        foreach ($search['search_set'] as $cur_search) {
            $forum = '<a href="'.get_link('forum/'.$cur_search['forum_id'].'/'.url_friendly($cur_search['forum_name']).'/').'">'.feather_escape($cur_search['forum_name']).'</a>';
            $url_topic = url_friendly($cur_search['subject']);

            if ($this->config['o_censoring'] == '1') {
                $cur_search['subject'] = censor_words($cur_search['subject']);
            }

            if ($search['show_as'] == 'posts') {
                ++$post_count;
                $cur_search['icon_type'] = 'icon';

                if (!$this->user->is_guest && $cur_search['last_post'] > $this->user->last_visit && (!isset($tracked_topics['topics'][$cur_search['tid']]) || $tracked_topics['topics'][$cur_search['tid']] < $cur_search['last_post']) && (!isset($tracked_topics['forums'][$cur_search['forum_id']]) || $tracked_topics['forums'][$cur_search['forum_id']] < $cur_search['last_post'])) {
                    $cur_search['item_status'] = 'inew';
                    $cur_search['icon_type'] = 'icon icon-new';
                    $cur_search['icon_text'] = $lang_topic['New icon'];
                } else {
                    $cur_search['item_status'] = '';
                    $cur_search['icon_text'] = '<!-- -->';
                }

                if ($this->config['o_censoring'] == '1') {
                    $cur_search['message'] = censor_words($cur_search['message']);
                }

                $cur_search['message'] = parse_message($cur_search['message'], $cur_search['hide_smilies']);
                $pposter = feather_escape($cur_search['pposter']);

                if ($cur_search['poster_id'] > 1 && $this->user->g_view_users == '1') {
                    $cur_search['pposter_disp'] = '<strong><a href="'.get_link('user/'.$cur_search['poster_id'].'/').'">'.$pposter.'</a></strong>';
                } else {
                    $cur_search['pposter_disp'] = '<strong>'.$pposter.'</strong>';
                }

                $this->feather->render('search/posts.php', array(
                    'post_count' => $post_count,
                    'url_topic' => $url_topic,
                    'cur_search' => $cur_search,
                    'forum' => $forum,
                    'lang_common' => $lang_common,
                    'lang_search' => $lang_search,
                    'lang_topic' => $lang_topic,
                    )
                );
            } else {
                ++$topic_count;
                $status_text = array();
                $cur_search['item_status'] = ($topic_count % 2 == 0) ? 'roweven' : 'rowodd';
                $cur_search['icon_type'] = 'icon';

                $subject = '<a href="'.get_link('topic/'.$cur_search['tid'].'/'.$url_topic.'/').'">'.feather_escape($cur_search['subject']).'</a> <span class="byuser">'.$lang_common['by'].' '.feather_escape($cur_search['poster']).'</span>';

                if ($cur_search['sticky'] == '1') {
                    $cur_search['item_status'] .= ' isticky';
                    $status_text[] = '<span class="stickytext">'.$lang_forum['Sticky'].'</span>';
                }

                if ($cur_search['closed'] != '0') {
                    $status_text[] = '<span class="closedtext">'.$lang_forum['Closed'].'</span>';
                    $cur_search['item_status'] .= ' iclosed';
                }

                if (!$this->user->is_guest && $cur_search['last_post'] > $this->user->last_visit && (!isset($tracked_topics['topics'][$cur_search['tid']]) || $tracked_topics['topics'][$cur_search['tid']] < $cur_search['last_post']) && (!isset($tracked_topics['forums'][$cur_search['forum_id']]) || $tracked_topics['forums'][$cur_search['forum_id']] < $cur_search['last_post'])) {
                    $cur_search['item_status'] .= ' inew';
                    $cur_search['icon_type'] = 'icon icon-new';
                    $subject = '<strong>'.$subject.'</strong>';
                    $subject_new_posts = '<span class="newtext">[ <a href="'.get_link('topic/'.$cur_search['tid'].'/action/new/').'" title="'.$lang_common['New posts info'].'">'.$lang_common['New posts'].'</a> ]</span>';
                } else {
                    $subject_new_posts = null;
                }

                // Insert the status text before the subject
                $subject = implode(' ', $status_text).' '.$subject;

                $num_pages_topic = ceil(($cur_search['num_replies'] + 1) / $this->user->disp_posts);

                if ($num_pages_topic > 1) {
                    $subject_multipage = '<span class="pagestext">[ '.paginate($num_pages_topic, -1, 'topic/'.$cur_search['tid'].'/'.$url_topic.'/#').' ]</span>';
                } else {
                    $subject_multipage = null;
                }

                // Should we show the "New posts" and/or the multipage links?
                if (!empty($subject_new_posts) || !empty($subject_multipage)) {
                    $subject .= !empty($subject_new_posts) ? ' '.$subject_new_posts : '';
                    $subject .= !empty($subject_multipage) ? ' '.$subject_multipage : '';
                }

                if (!isset($cur_search['start_from'])) {
                    $start_from = 0;
                } else {
                    $start_from = $cur_search['start_from'];
                }

                $this->feather->render('search/topics.php', array(
                    'cur_search' => $cur_search,
                    'start_from' => $start_from,
                    'topic_count' => $topic_count,
                    'subject' => $subject,
                    'forum' => $forum,
                    'lang_common' => $lang_common,
                    )
                );
            }
        }
    }

    public function get_list_forums()
    {
        global $lang_search;
        
        $output = '';

        $select_get_list_forums = array('cid' => 'c.id', 'c.cat_name', 'fid' => 'f.id', 'f.forum_name', 'f.redirect_url');
        $where_get_list_forums = array(
            array('fp.read_forum' => 'IS NULL'),
            array('fp.read_forum' => '1')
        );
        $order_by_get_list_forums = array('c.disp_position', 'c.id', 'f.disp_position');

        $result = DB::for_table('categories')
                    ->table_alias('c')
                    ->select_many($select_get_list_forums)
                    ->inner_join('forums', array('c.id', '=', 'f.cat_id'), 'f')
                    ->left_outer_join('forum_perms', array('fp.forum_id', '=', 'f.id'), 'fp')
                    ->left_outer_join('forum_perms', array('fp.group_id', '=', $this->user->g_id), null, true)
                    ->where_any_is($where_get_list_forums)
                    ->where_null('f.redirect_url')
                    ->order_by_many($order_by_get_list_forums)
                    ->find_many();

        // We either show a list of forums of which multiple can be selected
        if ($this->config['o_search_all_forums'] == '1' || $this->user->is_admmod) {
            $output .= "\t\t\t\t\t\t".'<div class="conl multiselect">'.$lang_search['Forum search']."\n";
            $output .= "\t\t\t\t\t\t".'<br />'."\n";
            $output .= "\t\t\t\t\t\t".'<div class="checklist">'."\n";

            $cur_category = 0;
            foreach($result as $cur_forum) {
                if ($cur_forum['cid'] != $cur_category) {
                    // A new category since last iteration?

                    if ($cur_category) {
                        $output .= "\t\t\t\t\t\t\t\t".'</div>'."\n";
                        $output .= "\t\t\t\t\t\t\t".'</fieldset>'."\n";
                    }

                    $output .= "\t\t\t\t\t\t\t".'<fieldset><legend><span>'.feather_escape($cur_forum['cat_name']).'</span></legend>'."\n";
                    $output .= "\t\t\t\t\t\t\t\t".'<div class="rbox">';
                    $cur_category = $cur_forum['cid'];
                }

                $output .= "\t\t\t\t\t\t\t\t".'<label><input type="checkbox" name="forums[]" id="forum-'.$cur_forum['fid'].'" value="'.$cur_forum['fid'].'" />'.feather_escape($cur_forum['forum_name']).'</label>'."\n";
            }

            if ($cur_category) {
                $output .= "\t\t\t\t\t\t\t\t".'</div>'."\n";
                $output .= "\t\t\t\t\t\t\t".'</fieldset>'."\n";
            }

            $output .= "\t\t\t\t\t\t".'</div>'."\n";
            $output .= "\t\t\t\t\t\t".'</div>'."\n";
        }
        // ... or a simple select list for one forum only
        else {
            $output .= "\t\t\t\t\t\t".'<label class="conl">'.$lang_search['Forum search']."\n";
            $output .= "\t\t\t\t\t\t".'<br />'."\n";
            $output .= "\t\t\t\t\t\t".'<select id="forum" name="forum">'."\n";

            $cur_category = 0;
            while ($cur_forum = $this->db->fetch_assoc($result)) {
                if ($cur_forum['cid'] != $cur_category) {
                    // A new category since last iteration?

                    if ($cur_category) {
                        $output .= "\t\t\t\t\t\t\t".'</optgroup>'."\n";
                    }

                    $output .= "\t\t\t\t\t\t\t".'<optgroup label="'.feather_escape($cur_forum['cat_name']).'">'."\n";
                    $cur_category = $cur_forum['cid'];
                }

                $output .= "\t\t\t\t\t\t\t\t".'<option value="'.$cur_forum['fid'].'">'.feather_escape($cur_forum['forum_name']).'</option>'."\n";
            }

            $output .= "\t\t\t\t\t\t\t".'</optgroup>'."\n";
            $output .= "\t\t\t\t\t\t".'</select>'."\n";
            $output .= "\t\t\t\t\t\t".'<br /></label>'."\n";
        }
        
        return $output;
    }
}
