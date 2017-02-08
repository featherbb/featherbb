<?php

/**
 * Copyright (C) 2015-2017 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace FeatherBB\Model;

use FeatherBB\Core\Database as DB;
use FeatherBB\Core\Track;
use FeatherBB\Core\Url;
use FeatherBB\Core\Utils;

class Index
{
    // Returns forum action
    public function forumActions()
    {
        Container::get('hooks')->fire('model.index.get_forum_actions_start');

        $forumActions = [];

        // Display a "mark all as read" link
        if (!User::get()->is_guest) {
            $forumActions[] = '<a href="'.Router::pathFor('markRead').'">'.__('Mark all as read').'</a>';
        }

        $forumActions = Container::get('hooks')->fire('model.index.get_forum_actions', $forumActions);

        return $forumActions;
    }

    // Detects if a "new" icon has to be displayed
    protected function getNewPosts()
    {
        Container::get('hooks')->fire('model.index.get_new_posts_start');

        $query['select'] = ['f.id', 'f.last_post'];
        $query['where'] = [
            ['fp.read_forum' => 'IS NULL'],
            ['fp.read_forum' => '1']
        ];

        $query = DB::table('forums')
            ->tableAlias('f')
            ->selectMany($query['select'])
            ->leftOuterJoin('forum_perms', 'fp.forum_id=f.id AND fp.group_id='.User::get()->g_id, 'fp')
            ->whereAnyIs($query['where'])
            ->whereGt('f.last_post', User::get()->last_visit);

        $query = Container::get('hooks')->fireDB('model.index.query_get_new_posts', $query);

        $query = $query->findResultSet();

        $forums = $newTopics = [];
        $trackedTopics = Track::getTrackedTopics();

        foreach ($query as $curForum) {
            if (!isset($trackedTopics['forums'][$curForum->id]) || $trackedTopics['forums'][$curForum->id] < $curForum->last_post) {
                $forums[$curForum->id] = $curForum->last_post;
            }
        }

        if (!empty($forums)) {
            if (empty($trackedTopics['topics'])) {
                $newTopics = $forums;
            } else {
                $query['select'] = ['forum_id', 'id', 'last_post'];

                $query = DB::table('topics')
                    ->selectMany($query['select'])
                    ->whereIn('forum_id', array_keys($forums))
                    ->whereGt('last_post', User::get()->last_visit)
                    ->whereNull('moved_to');

                $query = Container::get('hooks')->fireDB('model.index.get_new_posts_query', $query);

                $query = $query->findResultSet();

                foreach ($query as $curTopic) {
                    if (!isset($newTopics[$curTopic->forum_id]) && (!isset($trackedTopics['forums'][$curTopic->forum_id]) || $trackedTopics['forums'][$curTopic->forum_id] < $forums[$curTopic->forum_id]) && (!isset($trackedTopics['topics'][$curTopic->id]) || $trackedTopics['topics'][$curTopic->id] < $curTopic->last_post)) {
                        $newTopics[$curTopic->forum_id] = $forums[$curTopic->forum_id];
                    }
                }
            }
        }

        $newTopics = Container::get('hooks')->fire('model.index.get_new_posts', $newTopics);

        return $newTopics;
    }

    // Returns the elements needed to display categories and their forums
    public function printCategoriesForums()
    {
        Container::get('hooks')->fire('model.index.print_categories_forums_start');

        // Get list of forums and topics with new posts since last visit
        if (!User::get()->is_guest) {
            $newTopics = $this->getNewPosts();
        }

        $query['select'] = ['cid' => 'c.id', 'c.cat_name', 'fid' => 'f.id', 'f.forum_name', 'f.forum_desc', 'f.redirect_url', 'f.moderators', 'f.num_topics', 'f.num_posts', 'f.last_post', 'f.last_post_id', 'last_post_tid' => 't.id', 'last_post_subject' => 't.subject', 'f.last_poster'];
        $query['where'] = [
            ['fp.read_forum' => 'IS NULL'],
            ['fp.read_forum' => '1']
        ];
        $query['order_by'] = ['c.disp_position', 'c.id', 'f.disp_position'];

        $query = DB::table('categories')
            ->tableAlias('c')
            ->selectMany($query['select'])
            ->innerJoin('forums', ['c.id', '=', 'f.cat_id'], 'f')
            ->leftOuterJoin('topics', ['t.last_post_id', '=', 'f.last_post_id'], 't')
            ->leftOuterJoin('forum_perms', 'fp.forum_id=f.id AND fp.group_id='.User::get()->g_id, 'fp')
            ->whereAnyIs($query['where'])
            ->whereNull('t.moved_to')
            ->orderByMany($query['order_by']);

        $query = Container::get('hooks')->fireDB('model.index.query_print_categories_forums', $query);

        $query = $query->findResultSet();

        $indexData = [];
        $i = 0;
        foreach ($query as $curForum) {
            if ($i == 0) {
                $curForum->cur_category = 0;
                $curForum->forum_count_formatted = 0;
            }

            if (isset($curForum->cur_category)) {
                $curCat = $curForum->cur_category;
            } else {
                $curCat = 0;
            }

            if ($curForum->cid != $curCat) {
                // A new category since last iteration?
                $curForum->forum_count_formatted = 0;
                $curForum->cur_category = $curForum->cid;
            }

            ++$curForum->forum_count_formatted;

            $curForum->item_status = ($curForum->forum_count_formatted % 2 == 0) ? 'roweven' : 'rowodd';
            $forum_fieldNew = '';
            $curForum->icon_type = 'icon';

            // Are there new posts since our last visit?
            if (isset($newTopics[$curForum->fid])) {
                $curForum->item_status .= ' inew';
                $forum_fieldNew = '<span class="newtext">[ <a href="'.Router::pathFor('quickSearch', ['show' => 'new&amp;fid='.$curForum->fid]).'">'.__('New posts').'</a> ]</span>';
                $curForum->icon_type = 'icon icon-new';
            }

            // Is this a redirect forum?
            if ($curForum->redirect_url != '') {
                $curForum->forum_field = '<h3><span class="redirtext">'.__('Link to').'</span> <a href="'.Utils::escape($curForum->redirect_url).'" title="'.__('Link to').' '.Utils::escape($curForum->redirect_url).'">'.Utils::escape($curForum->forum_name).'</a></h3>';
                $curForum->num_topics_formatted = $curForum->num_posts_formatted = '-';
                $curForum->item_status .= ' iredirect';
                $curForum->icon_type = 'icon';
            } else {
                $forumName = Url::slug($curForum->forum_name);
                $curForum->forum_field = '<h3><a href="'.Router::pathFor('Forum', ['id' => $curForum->fid, 'name' => $forumName]).'">'.Utils::escape($curForum->forum_name).'</a>'.(!empty($forum_fieldNew) ? ' '.$forum_fieldNew : '').'</h3>';
                $curForum->num_topics_formatted = $curForum->num_topics;
                $curForum->num_posts_formatted = $curForum->num_posts;
            }

            if ($curForum->forum_desc != '') {
                $curForum->forum_field .= "\n\t\t\t\t\t\t\t\t".'<div class="forumdesc">'.$curForum->forum_desc.'</div>';
            }

            // If there is a last_post/last_poster
            if ($curForum->last_post != '') {
                $curForum->last_post_formatted = '<a href="'.Router::pathFor('viewPost', ['id' => $curForum->last_post_tid, 'name' => Url::slug($curForum->last_post_subject), 'pid' => $curForum->last_post_id]).'#p'.$curForum->last_postId.'">'.Container::get('utils')->formatTime($curForum->last_post).'</a> <span class="byuser">'.__('by').' '.Utils::escape($curForum->last_poster).'</span>';
            } elseif ($curForum->redirect_url != '') {
                $curForum->last_post_formatted = '- - -';
            } else {
                $curForum->last_post_formatted = __('Never');
            }

            if ($curForum->moderators != '') {
                $modsArray = unserialize($curForum->moderators);
                $moderators = [];

                foreach ($modsArray as $modUsername => $modId) {
                    if (User::can('users.view')) {
                        $moderators[] = '<a href="'.Router::pathFor('userProfile', ['id' => $modId]).'">'.Utils::escape($modUsername).'</a>';
                    } else {
                        $moderators[] = Utils::escape($modUsername);
                    }
                }

                $curForum->moderatorsFormatted = "\t\t\t\t\t\t\t\t".'<p class="modlist">(<em>'.__('Moderated by').'</em> '.implode(', ', $moderators).')</p>'."\n";
            } else {
                $curForum->moderatorsFormatted = '';
            }

            $indexData[] = $curForum;
            ++$i;
        }

        $indexData = Container::get('hooks')->fire('model.index.print_categories_forums', $indexData);

        return $indexData;
    }

    // Returns the elements needed to display stats
    public function stats()
    {
        Container::get('hooks')->fire('model.index.collect_stats_start');

        // Collect some statistics from the database
        if (!Container::get('cache')->isCached('users_info')) {
            Container::get('cache')->store('users_info', Cache::getUsersInfo());
        }

        $stats = Container::get('cache')->retrieve('users_info');

        $query = DB::table('forums')
            ->selectExpr('SUM(num_topics)', 'total_topics')
            ->selectExpr('SUM(num_posts)', 'total_posts');

        $query = Container::get('hooks')->fireDB('model.index.collect_stats_query', $query);

        $query = $query->findOne();

        $stats['total_topics'] = intval($query['total_topics']);
        $stats['total_posts'] = intval($query['total_posts']);

        if (User::can('users.view')) {
            $stats['newest_user'] = '<a href="'.Router::pathFor('userProfile', ['id' => $stats['last_user']['id']]).'">'.Utils::escape($stats['last_user']['username']).'</a>';
        } else {
            $stats['newest_user'] = Utils::escape($stats['last_user']['username']);
        }

        $stats = Container::get('hooks')->fire('model.index.collect_stats', $stats);

        return $stats;
    }

    // Returns the elements needed to display users online
    public function usersOnline()
    {
        Container::get('hooks')->fire('model.index.fetch_users_online_start');

        // Fetch users online info and generate strings for output
        $online = [];
        $online['num_guests'] = 0;

        $query['select'] = ['user_id', 'ident'];
        $query['where'] = ['idle' => '0'];
        $query['order_by'] = ['ident'];

        $query = DB::table('online')
            ->selectMany($query['select'])
            ->where($query['where'])
            ->orderByMany($query['order_by']);

        $query = Container::get('hooks')->fireDB('model.index.query_fetch_users_online', $query);

        $query = $query->findResultSet();

        foreach ($query as $userOnline) {
            if ($userOnline->user_id > 1) {
                if (User::can('users.view')) {
                    $online['users'][] = "\n\t\t\t\t".'<dd><a href="'.Router::pathFor('userProfile', ['id' => $userOnline->user_id]).'">'.Utils::escape($userOnline->ident).'</a>';
                } else {
                    $online['users'][] = "\n\t\t\t\t".'<dd>'.Utils::escape($userOnline->ident);
                }
            } else {
                ++$online['num_guests'];
            }
        }

        if (isset($online['users'])) {
            $online['num_users'] = count($online['users']);
        } else {
            $online['num_users'] = 0;
        }

        $online = Container::get('hooks')->fire('model.index.fetch_users_online', $online);

        return $online;
    }
}
