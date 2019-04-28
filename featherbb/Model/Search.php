<?php

/**
 * Copyright (C) 2015-2019 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace FeatherBB\Model;

use FeatherBB\Core\Database as DB;
use FeatherBB\Core\Error;
use FeatherBB\Core\Interfaces\Container;
use FeatherBB\Core\Interfaces\ForumEnv;
use FeatherBB\Core\Interfaces\ForumSettings;
use FeatherBB\Core\Interfaces\Hooks;
use FeatherBB\Core\Interfaces\Input;
use FeatherBB\Core\Interfaces\Parser;
use FeatherBB\Core\Interfaces\Router;
use FeatherBB\Core\Interfaces\User;
use FeatherBB\Core\Track;
use FeatherBB\Core\Url;
use FeatherBB\Core\Utils;

class Search
{
    public function __construct()
    {
        $this->search = new \FeatherBB\Core\Search();
    }

    public function getSearchResults($searchId = null)
    {
        $search = [];

        $search = Hooks::fire('model.search.get_search_results_start', $search);

        $action = (Input::query('action')) ? Input::query('action') : null;
        $forums = Input::query('forums') ? (is_array(Input::query('forums')) ? Input::query('forums') : array_filter(explode(',', Input::query('forums')))) : (Input::query('forums') ? [Input::query('forums')] : []);
        $sortDir = (Input::query('sort_dir') && Input::query('sort_dir') == 'DESC') ? 'DESC' : 'ASC';

        $forums = array_map('intval', $forums);

        // Allow the old action names for backwards compatibility reasons
        if ($action == 'show_user') {
            $action = 'show_user_posts';
        } elseif ($action == 'show_24h') {
            $action = 'show_recent';
        }

        // If a search_id was supplied
        if ($searchId) {
            $searchId = intval($searchId);
            if ($searchId < 1) {
                throw new Error(__('Bad request'), 400);
            }
        }
        // If it's a regular search (keywords and/or author)
        elseif ($action == 'search') {
            $keywords = (Input::query('keywords')) ? \utf8\to_lower(Utils::trim(Input::query('keywords'))) : null;
            $author = (Input::query('author')) ? \utf8\to_lower(Utils::trim(Input::query('author'))) : null;

            if (preg_match('%^[\*\%]+$%', $keywords) || (Utils::strlen(str_replace(['*', '%'], '', $keywords)) < ForumEnv::get('FEATHER_SEARCH_MIN_WORD') && !$this->search->isCjk($keywords))) {
                $keywords = '';
            }

            if (preg_match('%^[\*\%]+$%', $author) || Utils::strlen(str_replace(['*', '%'], '', $author)) < 2) {
                $author = '';
            }

            if (!$keywords && !$author) {
                return Router::redirect(Router::pathFor('search'), ['error', __('No terms')]);
            }

            if ($author) {
                $author = str_replace('*', '%', $author);
            }

            $showAs = (Input::query('show_as') && Input::query('show_as') == 'topics') ? 'topics' : 'posts';
            $sortBy = (Input::query('sort_by')) ? intval(Input::query('sort_by')) : 0;
            $searchIn = (!Input::query('search_in') || Input::query('search_in') == 0) ? 0 : ((Input::query('search_in') == 1) ? 1 : -1);
        }
        // If it's a user search (by ID)
        elseif ($action == 'show_user_posts' || $action == 'show_user_topics' || $action == 'show_subscriptions') {
            $userId = (Input::query('user_id')) ? intval(Input::query('user_id')) : User::get()->id;
            if ($userId < 2) {
                throw new Error(__('Bad request'), 404);
            }

            // Subscribed topics can only be viewed by admins, moderators and the users themselves
            if ($action == 'show_subscriptions' && !User::isAdminMod() && $userId != User::get()->id) {
                throw new Error(__('No permission'), 403);
            }
        } elseif ($action == 'show_recent') {
            $interval = Input::query('value') ? intval(Input::query('value')) : 86400;
        } elseif ($action == 'show_replies') {
            if (User::get()->is_guest) {
                throw new Error(__('Bad request'), 404);
            }
        } elseif ($action != 'show_new' && $action != 'show_unanswered') {
            throw new Error(__('Bad request'), 404);
        }


        // If a valid search_id was supplied we attempt to fetch the search results from the db
        if (isset($searchId)) {
            $ident = (User::get()->is_guest) ? Utils::getIp() : User::get()->username;

            $searchData = DB::table('search_cache')
                                ->where('id', $searchId)
                                ->where('ident', $ident);
            $searchData = Hooks::fireDB('model.search.get_search_results_search_data_query', $searchData);
            $searchData = $searchData->findOneCol('search_data');

            if ($searchData) {
                $temp = unserialize($searchData);
                $temp = Hooks::fire('model.search.get_search_results_temp', $temp);

                $searchIds = unserialize($temp['search_ids']);
                $numHits = $temp['num_hits'];
                $sortBy = $temp['sort_by'];
                $sortDir = $temp['sort_dir'];
                $showAs = $temp['show_as'];
                $searchType = $temp['search_type'];

                unset($temp);
            } else {
                return Router::redirect(Router::pathFor('search'), ['error', __('No hits')]);
            }
        } else {
            $keywordResults = $authorResults = [];

            // Search a specific forum?
            $forumSql = (!empty($forums) || (empty($forums) && ForumSettings::get('o_search_all_forums') == 0 && !User::isAdminMod())) ? ' AND t.forum_id IN ('.implode(',', $forums).')' : '';

            if (!empty($author) || !empty($keywords)) {
                // Flood protection
                if (User::get()->last_search && (time() - User::get()->last_search) < User::getPref('search.min_interval') && (time() - User::get()->last_search) >= 0) {
                    throw new Error(sprintf(__('Search flood'), User::getPref('search.min_interval'), User::getPref('search.min_interval') - (time() - User::get()->last_search)), 429);
                }

                if (!User::get()->is_guest) {
                    $updateLastSearch = DB::table('users')
                                            ->where('id', User::get()->id);
                } else {
                    $updateLastSearch = DB::table('online')
                                            ->where('ident', Utils::getIp());
                }
                $updateLastSearch = Hooks::fireDB('model.search.get_search_results_update_last_search', $updateLastSearch);
                $updateLastSearch = $updateLastSearch->updateMany('last_search', time());

                switch ($sortBy) {
                    case 1:
                        $sortBySql = ($showAs == 'topics') ? 't.poster' : 'p.poster';
                        $sortType = SORT_STRING;
                        break;

                    case 2:
                        $sortBySql = 't.subject';
                        $sortType = SORT_STRING;
                        break;

                    case 3:
                        $sortBySql = 't.forum_id';
                        $sortType = SORT_NUMERIC;
                        break;

                    case 4:
                        $sortBySql = 't.last_post';
                        $sortType = SORT_NUMERIC;
                        break;

                    default:
                        $sortBySql = ($showAs == 'topics') ? 't.last_post' : 'p.posted';
                        $sortType = SORT_NUMERIC;
                        break;
                }

                $sortBy = Hooks::fire('model.search.get_search_results_sort_by', $sortBy);

                // If it's a search for keywords
                if ($keywords) {
                    // split the keywords into words
                    $keywordsArray = $this->search->splitWords($keywords, false);
                    $keywordsArray = Hooks::fire('model.search.get_search_results_keywords_array', $keywordsArray);

                    if (empty($keywordsArray)) {
                        return Router::redirect(Router::pathFor('search'), ['error', __('No hits')]);
                    }

                    // Should we search in message body or topic subject specifically?
                    $searchInCond = ($searchIn) ? (($searchIn > 0) ? ' AND m.subject_match = 0' : ' AND m.subject_match = 1') : '';
                    $searchInCond = Hooks::fire('model.search.get_search_results_search_cond', $searchInCond);

                    $wordCount = 0;
                    $matchType = 'and';

                    $sortData = [];
                    foreach ($keywordsArray as $curWord) {
                        switch ($curWord) {
                            case 'and':
                            case 'or':
                            case 'not':
                                $matchType = $curWord;
                                break;

                            default:
                            {
                                if ($this->search->isCjk($curWord)) {
                                    $whereCond = str_replace('*', '%', $curWord);
                                    $whereCondCjk = ($searchIn ? (($searchIn > 0) ? 'p.message LIKE %:where_cond%' : 't.subject LIKE %:where_cond%') : 'p.message LIKE %:where_cond% OR t.subject LIKE %:where_cond%');

                                    $result = DB::table('posts')->rawQuery('SELECT p.id AS post_id, p.topic_id, '.$sortBySql.' AS sort_by FROM '.ForumSettings::get('db_prefix').'posts AS p INNER JOIN '.ForumSettings::get('db_prefix').'topics AS t ON t.id=p.topic_id LEFT JOIN '.ForumSettings::get('db_prefix').'forum_perms AS fp ON (fp.forum_id=t.forum_id AND fp.group_id='.User::get()->g_id.') WHERE ('.$whereCondCjk.') AND (fp.read_forum IS NULL OR fp.read_forum=1)'.$forumSql, [':where_cond' => $whereCond]);
                                } else {
                                    $result = DB::table('posts')->rawQuery('SELECT m.post_id, p.topic_id, '.$sortBySql.' AS sort_by FROM '.ForumSettings::get('db_prefix').'search_words AS w INNER JOIN '.ForumSettings::get('db_prefix').'search_matches AS m ON m.word_id = w.id INNER JOIN '.ForumSettings::get('db_prefix').'posts AS p ON p.id=m.post_id INNER JOIN '.ForumSettings::get('db_prefix').'topics AS t ON t.id=p.topic_id LEFT JOIN '.ForumSettings::get('db_prefix').'forum_perms AS fp ON (fp.forum_id=t.forum_id AND fp.group_id='.User::get()->g_id.') WHERE w.word LIKE :where_cond'.$searchInCond.' AND (fp.read_forum IS NULL OR fp.read_forum=1)'.$forumSql, [':where_cond' => str_replace('*', '%', $curWord)]);
                                }

                                $result = Hooks::fireDB('model.search.get_search_results_search_first_query', $result);
                                $result = $result->findMany();

                                $row = [];
                                foreach ($result as $temp) {
                                    $row[$temp['post_id']] = $temp['topic_id'];

                                    if (!$wordCount) {
                                        $keywordResults[$temp['post_id']] = $temp['topic_id'];
                                        $sortData[$temp['post_id']] = $temp['sort_by'];
                                    } elseif ($matchType == 'or') {
                                        $keywordResults[$temp['post_id']] = $temp['topic_id'];
                                        $sortData[$temp['post_id']] = $temp['sort_by'];
                                    } elseif ($matchType == 'not') {
                                        unset($keywordResults[$temp['post_id']]);
                                        unset($sortData[$temp['post_id']]);
                                    }
                                }

                                if ($matchType == 'and' && $wordCount) {
                                    foreach ($keywordResults as $postId => $topicId) {
                                        if (!isset($row[$postId])) {
                                            unset($keywordResults[$postId]);
                                            unset($sortData[$postId]);
                                        }
                                    }
                                }

                                ++$wordCount;
                                $pdo = DB::getDb();
                                $pdo = null;

                                break;
                            }
                        }
                    }

                    $keywordResults = Hooks::fire('model.search.get_search_results_search_keyword_results', $keywordResults);
                    // Sort the results - annoyingly array_multisort re-indexes arrays with numeric keys, so we need to split the keys out into a separate array then combine them again after
                    $postIds = array_keys($keywordResults);
                    $topicIds = array_values($keywordResults);

                    array_multisort(array_values($sortData), $sortDir == 'DESC' ? SORT_DESC : SORT_ASC, $sortType, $postIds, $topicIds);

                    // combine the arrays back into a key => value array
                    $keywordResults = array_combine($postIds, $topicIds);

                    unset($sortData, $postIds, $topicIds);
                }

                // If it's a search for author name (and that author name isn't Guest)
                if ($author && $author != 'guest' && $author != \utf8\to_lower(__('Guest'))) {
                    $usernameExists = DB::table('users')
                                        ->select('id')
                                        ->whereLike('username', $author);
                    $usernameExists = Hooks::fireDB('model.search.get_search_results_username_exists', $usernameExists);
                    $usernameExists = $usernameExists->findMany();

                    if ($usernameExists) {
                        $userIds = [];
                        foreach ($usernameExists as $row) {
                            $userIds[] = $row['id'];
                        }

                        $result = DB::table('posts')->rawQuery('SELECT p.id AS post_id, p.topic_id FROM '.ForumSettings::get('db_prefix').'posts AS p INNER JOIN '.ForumSettings::get('db_prefix').'topics AS t ON t.id=p.topic_id LEFT JOIN '.ForumSettings::get('db_prefix').'forum_perms AS fp ON (fp.forum_id=t.forum_id AND fp.group_id='.User::get()->g_id.') WHERE (fp.read_forum IS NULL OR fp.read_forum=1) AND p.poster_id IN('.implode(',', $userIds).')'.$forumSql.' ORDER BY '.$sortBySql.' '.$sortDir);
                        $result = Hooks::fireDB('model.search.get_search_results_search_second_query', $result);
                        $result = $result->findMany();

                        foreach ($result as $temp) {
                            $authorResults[$temp['post_id']] = $temp['topic_id'];
                        }

                        $pdo = DB::getDb();
                        $pdo = null;
                    }
                }

                // If we searched for both keywords and author name we want the intersection between the results
                if ($author && $keywords) {
                    $searchIds = array_intersect_assoc($keywordResults, $authorResults);
                    $searchType = ['both', [$keywords, Utils::trim(Input::query('author'))], implode(',', $forums), $searchIn];
                } elseif ($keywords) {
                    $searchIds = $keywordResults;
                    $searchType = ['keywords', $keywords, implode(',', $forums), $searchIn];
                } else {
                    $searchIds = $authorResults;
                    $searchType = ['author', Utils::trim(Input::query('author')), implode(',', $forums), $searchIn];
                }

                $searchIds = Hooks::fire('model.search.get_search_results_search_ids', $searchIds);
                $searchType = Hooks::fire('model.search.get_search_results_search_type', $searchType);

                unset($keywordResults, $authorResults);

                if ($showAs == 'topics') {
                    $searchIds = array_values($searchIds);
                } else {
                    $searchIds = array_keys($searchIds);
                }

                $searchIds = array_unique($searchIds);

                $searchIds = Hooks::fire('model.search.get_search_results_search_ids', $searchIds);
                $searchType = Hooks::fire('model.search.get_search_results_search_type', $searchType);

                $numHits = count($searchIds);
                if (!$numHits) {
                    return Router::redirect(Router::pathFor('search'), ['error', __('No hits')]);
                }
            } elseif ($action == 'show_new' || $action == 'show_recent' || $action == 'show_replies' || $action == 'show_user_posts' || $action == 'show_user_topics' || $action == 'show_subscriptions' || $action == 'show_unanswered') {
                $searchType = ['action', $action];
                $showAs = 'topics';
                // We want to sort things after last post
                $sortBy = 0;
                $sortDir = 'DESC';

                $result['where'] = [
                    ['fp.read_forum' => 'IS NULL'],
                    ['fp.read_forum' => 1]
                ];

                // If it's a search for new posts since last visit
                if ($action == 'show_new') {
                    if (User::get()->is_guest) {
                        throw new Error(__('No permission'), 403);
                    }

                    $result = DB::table('topics')
                                ->tableAlias('t')
                                ->select('t.id')
                                ->leftOuterJoin('forum_perms', 'fp.forum_id=t.forum_id AND fp.group_id='.User::get()->g_id, 'fp')
                                ->whereAnyIs($result['where'])
                                ->whereGt('t.last_post', User::get()->last_visit)
                                ->whereNull('t.moved_to')
                                ->orderByDesc('t.last_post');


                    if (Input::query('fid')) {
                        $result = $result->where('t.forum_id', intval(Input::query('fid')));
                    }

                    $result = Hooks::fireDB('model.search.get_search_results_topic_query', $result);
                    $result = $result->findMany();

                    $numHits = count($result);

                    if (!$numHits) {
                        return Router::redirect(Router::pathFor('home'), __('No new posts'));
                    }
                }
                // If it's a search for recent posts (in a certain time interval)
                elseif ($action == 'show_recent') {
                    $result = DB::table('topics')
                                ->tableAlias('t')
                                ->select('t.id')
                                ->leftOuterJoin('forum_perms', 'fp.forum_id=t.forum_id AND fp.group_id='.User::get()->g_id, 'fp')
                                ->whereAnyIs($result['where'])
                                ->whereGt('t.last_post', time() - $interval)
                                ->whereNull('t.moved_to')
                                ->orderByDesc('t.last_post');

                    if (Input::query('fid')) {
                        $result = $result->where('t.forum_id', intval(Input::query('fid')));
                    }

                    $result = Hooks::fireDB('model.search.get_search_results_topic_query', $result);
                    $result = $result->findMany();

                    $numHits = count($result);

                    if (!$numHits) {
                        return Router::redirect(Router::pathFor('home'), __('No recent posts'));
                    }
                }
                // If it's a search for topics in which the user has posted
                elseif ($action == 'show_replies') {
                    $result = DB::table('topics')
                                ->tableAlias('t')
                                ->select('t.id')
                                ->innerJoin('posts', ['t.id', '=', 'p.topic_id'], 'p')
                                ->leftOuterJoin('forum_perms', 'fp.forum_id=t.forum_id AND fp.group_id='.User::get()->g_id, 'fp')
                                ->whereAnyIs($result['where'])
                                ->where('p.poster_id', User::get()->id)
                                ->groupBy('t.id');

                    if (ForumSettings::get('db_type') == 'pgsql') {
                        $result = $result->groupBy('t.last_post');
                    }

                    $result = Hooks::fireDB('model.search.get_search_results_topic_query', $result);
                    $result = $result->findMany();

                    $numHits = count($result);

                    if (!$numHits) {
                        return Router::redirect(Router::pathFor('home'), __('No user posts'));
                    }
                }
                // If it's a search for posts by a specific user ID
                elseif ($action == 'show_user_posts') {
                    $showAs = 'posts';

                    $result = DB::table('posts')
                                ->tableAlias('p')
                                ->select('p.id')
                                ->innerJoin('topics', ['p.topic_id', '=', 't.id'], 't')
                                ->leftOuterJoin('forum_perms', 'fp.forum_id=t.forum_id AND fp.group_id='.User::get()->g_id, 'fp')
                                ->whereAnyIs($result['where'])
                                ->where('p.poster_id', $userId)
                                ->orderByDesc('p.posted');

                    $result = Hooks::fireDB('model.search.get_search_results_post_query', $result);
                    $result = $result->findMany();

                    $numHits = count($result);

                    if (!$numHits) {
                        return Router::redirect(Router::pathFor('search'), __('No user posts'));
                    }

                    // Pass on the user ID so that we can later know whose posts we're searching for
                    $searchType[2] = $userId;
                }
                // If it's a search for topics by a specific user ID
                elseif ($action == 'show_user_topics') {
                    $result = DB::table('topics')
                                ->tableAlias('t')
                                ->select('t.id')
                                ->innerJoin('posts', ['t.first_post_id', '=', 'p.id'], 'p')
                                ->leftOuterJoin('forum_perms', 'fp.forum_id=t.forum_id AND fp.group_id='.User::get()->g_id, 'fp')
                                ->whereAnyIs($result['where'])
                                ->where('p.poster_id', $userId)
                                ->orderByDesc('t.last_post');

                    $result = Hooks::fireDB('model.search.get_search_results_topic_query', $result);
                    $result = $result->findMany();

                    $numHits = count($result);

                    if (!$numHits) {
                        return Router::redirect(Router::pathFor('search'), __('No user topics'));
                    }

                    // Pass on the user ID so that we can later know whose topics we're searching for
                    $searchType[2] = $userId;
                }
                // If it's a search for subscribed topics
                elseif ($action == 'show_subscriptions') {
                    if (User::get()->is_guest) {
                        throw new Error(__('Bad request'), 404);
                    }

                    $result = DB::table('topics')
                                ->tableAlias('t')
                                ->distinct()
                                ->select('t.id')
                                ->join('topic_subscriptions', 't.id=s.topic_id AND s.user_id='.$userId, 's')
                                ->leftOuterJoin('forum_perms', 'fp.forum_id=t.forum_id AND fp.group_id='.User::get()->g_id, 'fp')
                                ->whereAnyIs($result['where'])
                                ->orderByDesc('t.last_post');

                    $result = Hooks::fireDB('model.search.get_search_results_topic_query', $result);
                    $result = $result->findMany();

                    $numHits = count($result);

                    if (!$numHits) {
                        return Router::redirect(Router::pathFor('search'), __('No subscriptions'));
                    }

                    // Pass on user ID so that we can later know whose subscriptions we're searching for
                    $searchType[2] = $userId;
                }
                // If it's a search for unanswered posts
                else {
                    $result = DB::table('topics')
                                ->tableAlias('t')
                                ->select('t.id')
                                ->leftOuterJoin('forum_perms', 'fp.forum_id=t.forum_id AND fp.group_id='.User::get()->g_id, 'fp')
                                ->where('t.num_replies', 0)
                                ->whereNull('t.moved_to')
                                ->whereAnyIs($result['where'])
                                ->orderByDesc('t.last_post');

                    $result = Hooks::fireDB('model.search.get_search_results_topic_query', $result);
                    $result = $result->findMany();

                    $numHits = count($result);

                    if (!$numHits) {
                        return Router::redirect(Router::pathFor('home'), __('No unanswered'));
                    }
                }

                $searchIds = [];
                foreach ($result as $row) {
                    $searchIds[] = $row['id'];
                }

                $pdo = DB::getDb();
                $pdo = null;
            } else {
                throw new Error(__('Bad request'), 404);
            }


            // Prune "old" search results
            $oldSearches = [];
            $result = DB::table('online')
                        ->select('ident');
            $result = Hooks::fireDB('model.search.get_search_results_prune_search', $result);
            $result = $result->findMany();

            if ($result) {
                foreach ($result as $row) {
                    $oldSearches[] = $row['ident'];
                }

                $deleteCache = DB::table('search_cache')
                                    ->whereNotIn('ident', $oldSearches);
                $deleteCache = Hooks::fireDB('model.search.get_search_results_delete_cache', $deleteCache);
                $deleteCache = $deleteCache->deleteMany();
            }

            // Fill an array with our results and search properties
            $temp = serialize([
                'search_ids'        => serialize($searchIds),
                'num_hits'            => $numHits,
                'sort_by'            => $sortBy,
                'sort_dir'            => $sortDir,
                'show_as'            => $showAs,
                'search_type'        => $searchType
            ]);
            $searchId = mt_rand(1, 2147483647);

            $ident = (User::get()->is_guest) ? Utils::getIp() : User::get()->username;

            $cache['insert'] = [
                'id'   =>  $searchId,
                'ident'  =>  $ident,
                'search_data'  =>  $temp,
            ];

            $cache = DB::table('search_cache')
                        ->create()
                        ->set($cache['insert']);
            $cache = Hooks::fireDB('model.search.get_search_results_update_cache', $cache);
            $cache = $cache->save();

            // Redirect the user to the cached result page
            return Router::redirect(Router::pathFor('search', ['search_id' => $searchId]));
        }

        // If we're on the new posts search, display a "mark all as read" link
        if (!User::get()->is_guest && $searchType[0] == 'action' && $searchType[1] == 'show_new') {
            $search['forum_actions'][] = '<a href="'.Router::pathFor('markRead').'">'.__('Mark all as read').'</a>';
        }

        // Fetch results to display
        if (!empty($searchIds)) {
            // We have results
            $search['is_result'] = true;

            switch ($sortBy) {
                case 1:
                    $sortBySql = ($showAs == 'topics') ? 't.poster' : 'p.poster';
                    break;

                case 2:
                    $sortBySql = 't.subject';
                    break;

                case 3:
                    $sortBySql = 't.forum_id';
                    break;

                default:
                    $sortBySql = ($showAs == 'topics') ? 't.last_post' : 'p.posted';
                    break;
            }

            // Determine the topic or post offset (based on $_gET['p'])
            $perPage = ($showAs == 'posts') ? User::getPref('disp.posts') : User::getPref('disp.topics');
            $numPages = ceil($numHits / $perPage);

            $p = (!Input::query('p') || Input::query('p') <= 1 || Input::query('p') > $numPages) ? 1 : intval(Input::query('p'));
            $startFrom = $perPage * ($p - 1);
            $search['start_from'] = $startFrom;

            // Generate paging links
            $search['paging_links'] = '<span class="pages-label">'.__('Pages').' </span>'.Url::paginateOld($numPages, $p, '?');

            // throw away the first $startFrom of $searchIds, only keep the top $perPage of $searchIds
            $searchIds = array_slice($searchIds, $startFrom, $perPage);

            // Run the query and fetch the results
            if ($showAs == 'posts') {
                $result['select'] = ['pid' => 'p.id', 'pposter' => 'p.poster', 'pposted' => 'p.posted', 'p.poster_id', 'p.message', 'p.hide_smilies', 'tid' => 't.id', 't.poster', 't.subject', 't.first_post_id', 't.last_post', 't.last_post_id', 't.last_poster', 't.num_replies', 't.forum_id', 'f.forum_name'];

                $result = DB::table('posts')
                                ->tableAlias('p')
                                ->selectMany($result['select'])
                                ->innerJoin('topics', ['t.id', '=', 'p.topic_id'], 't')
                                ->innerJoin('forums', ['f.id', '=', 't.forum_id'], 'f')
                                ->whereIn('p.id', $searchIds)
                                ->orderBy($sortBySql, $sortDir);
                $result = Hooks::fireDB('model.search.get_search_results_select_posts_query', $result);
            } else {
                $result['select'] = ['tid' => 't.id', 't.poster', 't.subject', 't.last_post', 't.last_post_id', 't.last_poster', 't.num_replies', 't.closed', 't.sticky', 't.forum_id', 'f.forum_name'];

                $result = DB::table('topics')
                                ->tableAlias('t')
                                ->selectMany($result['select'])
                                ->innerJoin('forums', ['f.id', '=', 't.forum_id'], 'f')
                                ->whereIn('t.id', $searchIds)
                                ->orderBy($sortBySql, $sortDir);
                $result = Hooks::fireDB('model.search.get_search_results_select_topics_query', $result);
            }
            $result = $result->findArray();
            $search['search_set'] = [];
            foreach ($result as $row) {
                $search['search_set'][] = $row;
            }

            $search['crumbs_text']['show_as'] = __('Search');

            if ($searchType[0] == 'action') {
                if ($searchType[1] == 'show_user_topics') {
                    $search['crumbs_text']['search_type'] = '<a href="'.Router::pathFor('search').'?action=show_user_topics&amp;user_id='.$searchType[2].'">'.sprintf(__('Quick search show_user_topics'), Utils::escape($search['search_set'][0]['poster'])).'</a>';
                } elseif ($searchType[1] == 'show_user_posts') {
                    $search['crumbs_text']['search_type'] = '<a href="'.Router::pathFor('search').'?action=show_user_posts&amp;user_id='.$searchType[2].'">'.sprintf(__('Quick search show_user_posts'), Utils::escape($search['search_set'][0]['pposter'])).'</a>';
                } elseif ($searchType[1] == 'show_subscriptions') {
                    // Fetch username of subscriber
                    $subscriberId = $searchType[2];
                    $subscriberName = DB::table('users')
                                            ->where('id', $subscriberId);
                    $subscriberName = Hooks::fireDB('model.search.get_search_results_subscriber_name', $subscriberName);
                    $subscriberName = $subscriberName->findOneCol('username');

                    if (!$subscriberName) {
                        throw new Error(__('Bad request'), 404);
                    }

                    $search['crumbs_text']['search_type'] = '<a href="'.Router::pathFor('search').'?action=show_subscription&amp;user_id='.$subscriberId.'">'.sprintf(__('Quick search show_subscriptions'), Utils::escape($subscriberName)).'</a>';
                } else {
                    $searchUrl = str_replace('_', '/', $searchType[1]);
                    $search['crumbs_text']['search_type'] = '<a href="'.Router::pathFor('search').$searchUrl.'">'.__('Quick search '.$searchType[1]).'</a>';
                }
            } else {
                $keywords = $author = '';

                if ($searchType[0] == 'both') {
                    list($keywords, $author) = $searchType[1];
                    $search['crumbs_text']['search_type'] = sprintf(__('By both show as '.$showAs), Utils::escape($keywords), Utils::escape($author));
                } elseif ($searchType[0] == 'keywords') {
                    $keywords = $searchType[1];
                    $search['crumbs_text']['search_type'] = sprintf(__('By keywords show as '.$showAs), Utils::escape($keywords));
                } elseif ($searchType[0] == 'author') {
                    $author = $searchType[1];
                    $search['crumbs_text']['search_type'] = sprintf(__('By user show as '.$showAs), Utils::escape($author));
                }

                $search['crumbs_text']['search_type'] = '<a href="'.Router::pathFor('search').'?action=search&amp;keywords='.urlencode($keywords).'&amp;author='.urlencode($author).'&amp;forums='.$searchType[2].'&amp;search_in='.$searchType[3].'&amp;sort_by='.$sortBy.'&amp;sort_dir='.$sortDir.'&amp;show_as='.$showAs.'">'.$search['crumbs_text']['search_type'].'</a>';
            }
        }

        $search['show_as'] = $showAs;

        $search = Hooks::fire('model.search.get_search_results', $search);

        return $search;
    }

    public function displaySearchResults($search)
    {
        $search = Hooks::fire('model.search.display_search_results_start', $search);

        // Get topic/forum tracking data
        if (!User::get()->is_guest) {
            $trackedTopics = Track::getTrackedTopics();
        }

        $postCount = $topicCount = 0;

        $display = [];

        foreach ($search['search_set'] as $curSearch) {
            $forumName = Url::slug($curSearch['forum_name']);
            $forum = '<a href="'.Router::pathFor('Forum', ['id' => $curSearch['forum_id'], 'name' => $forumName]).'">'.Utils::escape($curSearch['forum_name']).'</a>';
            $urlTopic = Url::slug($curSearch['subject']);

            if (ForumSettings::get('o_censoring') == 1) {
                $curSearch['subject'] = Utils::censor($curSearch['subject']);
            }

            if ($search['show_as'] == 'posts') {
                ++$postCount;
                $curSearch['icon_type'] = 'icon';

                if (!User::get()->is_guest && $curSearch['last_post'] > User::get()->last_visit && (!isset($trackedTopics['topics'][$curSearch['tid']]) || $trackedTopics['topics'][$curSearch['tid']] < $curSearch['last_post']) && (!isset($trackedTopics['forums'][$curSearch['forum_id']]) || $trackedTopics['forums'][$curSearch['forum_id']] < $curSearch['last_post'])) {
                    $curSearch['item_status'] = 'inew';
                    $curSearch['icon_type'] = 'icon icon-new';
                    $curSearch['icon_text'] = __('New icon');
                } else {
                    $curSearch['item_status'] = '';
                    $curSearch['icon_text'] = '<!-- -->';
                }

                if (ForumSettings::get('o_censoring') == 1) {
                    $curSearch['message'] = Utils::censor($curSearch['message']);
                }

                $curSearch['message'] = Parser::parseMessage($curSearch['message'], $curSearch['hide_smilies']);
                $pposter = Utils::escape($curSearch['pposter']);

                if ($curSearch['poster_id'] > 1 && User::can('users.view')) {
                    $curSearch['pposter_disp'] = '<strong><a href="'.Router::pathFor('userProfile', ['id' => $curSearch['poster_id']]).'">'.$pposter.'</a></strong>';
                } else {
                    $curSearch['pposter_disp'] = '<strong>'.$pposter.'</strong>';
                }
            } else {
                ++$topicCount;
                $statusText = [];
                $curSearch['item_status'] = ($topicCount % 2 == 0) ? 'roweven' : 'rowodd';
                $curSearch['icon_type'] = 'icon';

                $subject = '<a href="'.Router::pathFor('Topic', ['id' => $curSearch['tid'], 'name' => $urlTopic]).'">'.Utils::escape($curSearch['subject']).'</a> <span class="byuser">'.__('by').' '.Utils::escape($curSearch['poster']).'</span>';

                // Include separate icon, label and background for sticky and closed topics
                if ($curSearch['sticky'] == 1) {
                    $curSearch['item_status'] .= ' isticky';
                    if ($curSearch['closed'] == 1) {
                        $curSearch['icon_type'] = 'icon icon-closed';
                        $statusText[] = '<span class="stickytext">'.__('Sticky and closed').'</span>';
                    } else {
                        $curSearch['icon_type'] = 'icon icon-sticky';
                        $statusText[] = '<span class="stickytext">'.__('Sticky').'</span>';
                    }
                } elseif ($curSearch['closed'] == 1) {
                    $statusText[] = '<span class="closedtext">'.__('Closed').'</span>';
                    $curSearch['item_status'] .= ' iclosed';
                    $curSearch['icon_type'] = 'icon icon-closed';
                }

                if (!User::get()->is_guest && $curSearch['last_post'] > User::get()->last_visit && (!isset($trackedTopics['topics'][$curSearch['tid']]) || $trackedTopics['topics'][$curSearch['tid']] < $curSearch['last_post']) && (!isset($trackedTopics['forums'][$curSearch['forum_id']]) || $trackedTopics['forums'][$curSearch['forum_id']] < $curSearch['last_post'])) {
                    $curSearch['item_status'] .= ' inew';
                    $curSearch['icon_type'] = 'icon icon-new';
                    $subject = '<strong>'.$subject.'</strong>';
                    $subjectNewPosts = '<span class="newtext">[ <a href="'.Router::pathFor('topicAction', ['id' => $curSearch['tid'], 'name' => $urlTopic, 'action' => 'new']).'" title="'.__('New posts info').'">'.__('New posts').'</a> ]</span>';
                } else {
                    $subjectNewPosts = null;
                }

                // Insert the status text before the subject
                $subject = implode(' ', $statusText).' '.$subject;

                $numPagesTopic = ceil(($curSearch['num_replies'] + 1) / User::getPref('disp.posts'));

                if ($numPagesTopic > 1) {
                    $subjectMultipage = '<span class="pagestext">[ '.Url::paginate($numPagesTopic, -1, 'topic/'.$curSearch['tid'].'/'.$urlTopic.'/#').' ]</span>';
                } else {
                    $subjectMultipage = null;
                }

                // Should we show the "New posts" and/or the multipage links?
                if (!empty($subjectNewPosts) || !empty($subjectMultipage)) {
                    $subject .= !empty($subjectNewPosts) ? ' '.$subjectNewPosts : '';
                    $subject .= !empty($subjectMultipage) ? ' '.$subjectMultipage : '';
                }

                if (!isset($curSearch['start_from'])) {
                    $curSearch['start_from'] = 0;
                }

                $curSearch['topic_count'] = $topicCount;
                $curSearch['subject'] = $subject;
            }

            $curSearch['post_count'] = $postCount;
            $curSearch['forum'] = $forum;
            $curSearch['url_topic'] = $urlTopic;

            $display['cur_search'][] = $curSearch;
        }
        $display = Hooks::fire('model.search.display_search_results', $display, $search);

        return $display;
    }

    public function forumsList()
    {
        $output = '';

        $output = Hooks::fire('model.search.get_list_forums_start', $output);

        $result['select'] = ['cid' => 'c.id', 'c.cat_name', 'fid' => 'f.id', 'f.forum_name', 'f.redirect_url'];
        $result['where'] = [
            ['fp.read_forum' => 'IS NULL'],
            ['fp.read_forum' => 1]
        ];
        $result['order_by'] = ['c.disp_position', 'c.id', 'f.disp_position'];

        $result = DB::table('categories')
                    ->tableAlias('c')
                    ->selectMany($result['select'])
                    ->innerJoin('forums', ['c.id', '=', 'f.cat_id'], 'f')
                    ->leftOuterJoin('forum_perms', 'fp.forum_id=f.id AND fp.group_id='.User::get()->g_id, 'fp')
                    ->whereAnyIs($result['where'])
                    ->whereNull('f.redirect_url')
                    ->orderByMany($result['order_by']);
        $result = Hooks::fireDB('model.search.get_list_forums_query', $result);
        $result = $result->findMany();

        // We either show a list of forums of which multiple can be selected
        if (ForumSettings::get('o_search_all_forums') == 1 || User::isAdminMod()) {
            $output .= "\t\t\t\t\t\t".'<div class="conl multiselect">'.__('Forum search')."\n";
            $output .= "\t\t\t\t\t\t".'<br />'."\n";
            $output .= "\t\t\t\t\t\t".'<div class="checklist">'."\n";

            $curCategory = 0;
            foreach ($result as $curForum) {
                if ($curForum['cid'] != $curCategory) {
                    // A new category since last iteration?

                    if ($curCategory) {
                        $output .= "\t\t\t\t\t\t\t\t".'</div>'."\n";
                        $output .= "\t\t\t\t\t\t\t".'</fieldset>'."\n";
                    }

                    $output .= "\t\t\t\t\t\t\t".'<fieldset><legend><span>'.Utils::escape($curForum['cat_name']).'</span></legend>'."\n";
                    $output .= "\t\t\t\t\t\t\t\t".'<div class="rbox">';
                    $curCategory = $curForum['cid'];
                }

                $output .= "\t\t\t\t\t\t\t\t".'<label><input type="checkbox" name="forums[]" id="forum-'.$curForum['fid'].'" value="'.$curForum['fid'].'" />'.Utils::escape($curForum['forum_name']).'</label>'."\n";
            }

            if ($curCategory) {
                $output .= "\t\t\t\t\t\t\t\t".'</div>'."\n";
                $output .= "\t\t\t\t\t\t\t".'</fieldset>'."\n";
            }

            $output .= "\t\t\t\t\t\t".'</div>'."\n";
            $output .= "\t\t\t\t\t\t".'</div>'."\n";
        }
        // ... or a simple select list for one forum only
        else {
            $output .= "\t\t\t\t\t\t".'<label class="conl">'.__('Forum search')."\n";
            $output .= "\t\t\t\t\t\t".'<br />'."\n";
            $output .= "\t\t\t\t\t\t".'<select id="forum" name="forum">'."\n";

            $curCategory = 0;
            foreach ($result as $curForum) {
                if ($curForum['cid'] != $curCategory) {
                    // A new category since last iteration?

                    if ($curCategory) {
                        $output .= "\t\t\t\t\t\t\t".'</optgroup>'."\n";
                    }

                    $output .= "\t\t\t\t\t\t\t".'<optgroup label="'.Utils::escape($curForum['cat_name']).'">'."\n";
                    $curCategory = $curForum['cid'];
                }

                $output .= "\t\t\t\t\t\t\t\t".'<option value="'.$curForum['fid'].'">'.Utils::escape($curForum['forum_name']).'</option>'."\n";
            }

            $output .= "\t\t\t\t\t\t\t".'</optgroup>'."\n";
            $output .= "\t\t\t\t\t\t".'</select>'."\n";
            $output .= "\t\t\t\t\t\t".'<br /></label>'."\n";
        }

        $output = Hooks::fire('model.search.get_list_forums', $output);

        return $output;
    }
}
