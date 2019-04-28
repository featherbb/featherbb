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
use FeatherBB\Core\Interfaces\ForumEnv;
use FeatherBB\Core\Interfaces\ForumSettings;
use FeatherBB\Core\Interfaces\Hooks;
use FeatherBB\Core\Interfaces\Input;
use FeatherBB\Core\Interfaces\Router;
use FeatherBB\Core\Interfaces\User;
use FeatherBB\Core\Track;
use FeatherBB\Core\Url;
use FeatherBB\Core\Utils;

class Forum
{
    // Returns basic informations about the forum
    public function getForumInfo($id)
    {
        $id = Hooks::fire('model.forum.get_info_forum_start', $id);

        $curForum['where'] = [
            ['fp.read_forum' => 'IS NULL'],
            ['fp.read_forum' => 1]
        ];

        if (!User::get()->is_guest) {
            $curForum['select'] = ['f.forum_name', 'f.redirect_url', 'f.moderators', 'f.num_topics', 'f.sort_by', 'fp.post_topics', 'is_subscribed' => 's.user_id'];

            $curForum = DB::table('forums')->tableAlias('f')
                            ->selectMany($curForum['select'])
                            ->leftOuterJoin('forum_subscriptions', 'f.id=s.forum_id AND s.user_id='.User::get()->id, 's')
                            ->leftOuterJoin('forum_perms', 'fp.forum_id=f.id AND fp.group_id='.User::get()->g_id, 'fp')
                            ->whereAnyIs($curForum['where'])
                            ->where('f.id', $id);
        } else {
            $curForum['select'] = ['f.forum_name', 'f.redirect_url', 'f.moderators', 'f.num_topics', 'f.sort_by', 'fp.post_topics'];

            $curForum = DB::table('forums')->tableAlias('f')
                            ->selectMany($curForum['select'])
                            ->selectExpr(0, 'is_subscribed')
                            ->leftOuterJoin('forum_perms', 'fp.forum_id=f.id AND fp.group_id='.User::get()->g_id, 'fp')
                            ->whereAnyIs($curForum['where'])
                            ->where('f.id', $id);
        }

        $curForum = Hooks::fireDB('model.forum.get_info_forum_query', $curForum);
        $curForum = $curForum->findOne();

        if (!$curForum) {
            throw new Error(__('Bad request'), '404');
        }

        $curForum['forum_url'] = Url::slug($curForum['forum_name']);
        $curForum = Hooks::fire('model.forum.get_info_forum', $curForum);

        return $curForum;
    }

    public static function getModerators($fid)
    {
        $moderators = DB::table('forums')
                        ->where('id', $fid);
        $moderators = Hooks::fireDB('model.forum.get_moderators', $moderators);
        $moderators = $moderators->findOneCol('moderators');

        return $moderators;
    }

    public static function getId($tid)
    {
        $fid = DB::table('topics')
            ->where('id', $tid);
        $fid = Hooks::fireDB('model.forum.get_moderators', $fid);
        $fid = $fid->findOneCol('forum_id');

        return $fid;
    }

    // Returns the text required by the query to sort the forum
    public function sortForumBy($sortBySql)
    {
        $sortBySql = Hooks::fire('model.forum.sort_forum_by_start', $sortBySql);

        switch ($sortBySql) {
            case 0:
                $sortBy = 'last_post DESC';
                break;
            case 1:
                $sortBy = 'posted DESC';
                break;
            case 2:
                $sortBy = 'subject ASC';
                break;
            default:
                $sortBy = 'last_post DESC';
                break;
        }

        $sortBy = Hooks::fire('model.forum.sort_forum_by', $sortBy);

        return $sortBy;
    }

    // Returns forum action
    public function getForumActions($forumId, $forumUrl, $isSubscribed)
    {
        $forumActions = [];

        $forumActions = Hooks::fire('model.forum.get_page_head_start', $forumActions, $forumId, $forumUrl, $isSubscribed);

        if (!User::get()->is_guest) {
            if (ForumSettings::get('o_forum_subscriptions') == 1) {
                if ($isSubscribed) {
                    $forumActions[] = '<span>'.__('Is subscribed').' - </span><a href="'.Router::pathFor('unsubscribeForum', ['id' => $forumId, 'name' => $forumUrl]).'">'.__('Unsubscribe').'</a>';
                } else {
                    $forumActions[] = '<a href="'.Router::pathFor('subscribeForum', ['id' => $forumId, 'name' => $forumUrl]).'">'.__('Subscribe').'</a>';
                }
            }

            $forumActions[] = '<a href="'.Router::pathFor('markForumRead', ['id' => $forumId, 'name' => $forumUrl]).'">'.__('Mark forum read').'</a>';
        }

        $forumActions = Hooks::fire('model.forum.get_page_head', $forumActions);

        return $forumActions;
    }

    // Returns the elements needed to display topics
    public function printTopics($forumId, $sortBy, $startFrom)
    {
        $forumId = Hooks::fire('model.forum.print_topics_start', $forumId, $sortBy, $startFrom);

        // Get topic/forum tracking data
        if (!User::get()->is_guest) {
            $trackedTopics = Track::getTrackedTopics();
        }

        // Retrieve a list of topic IDs, LIMIT is (really) expensive so we only fetch the IDs here then later fetch the remaining data
        $result = DB::table('topics')
                        ->select('id')
                        ->where('forum_id', $forumId)
                        ->orderByDesc('sticky')
                        ->orderByExpr($sortBy)
                        ->orderByDesc('id')
                        ->limit(User::getPref('disp.topics'))
                        ->offset($startFrom);
        $result = Hooks::fire('model.forum.print_topics_ids_query', $result);
        $result = $result->findMany();

        $forumData = [];

        // If there are topics in this forum
        if ($result) {
            $topicIds = [];
            foreach ($result as $curTopicId) {
                $topicIds[] = $curTopicId['id'];
            }

            // Fetch list of topics to display on this page
            if (User::get()->is_guest || ForumSettings::get('o_show_dot') == 0) {
                // Without "the dot"
                $result['select'] = ['id', 'poster', 'subject', 'posted', 'last_post', 'last_post_id', 'last_poster', 'num_views', 'num_replies', 'closed', 'sticky', 'moved_to'];

                $result = DB::table('topics')
                            ->selectMany($result['select'])
                            ->whereIn('id', $topicIds)
                            ->orderByDesc('sticky')
                            ->orderByExpr($sortBy)
                            ->orderByDesc('id');
            } else {
                // With "the dot"
                $result['select'] = ['has_posted' => 'p.poster_id', 't.id', 't.subject', 't.poster', 't.posted', 't.last_post', 't.last_post_id', 't.last_poster', 't.num_views', 't.num_replies', 't.closed', 't.sticky', 't.moved_to'];

                $result = DB::table('topics')
                            ->tableAlias('t')
                            ->selectMany($result['select'])
                            ->leftOuterJoin('posts', ['t.id', '=', 'p.topic_id'], 'p')
                            ->leftOuterJoin('posts', ['p.poster_id', '=', User::get()->id], null, true)
                            ->whereIn('t.id', $topicIds)
                            ->groupBy('t.id')
                            ->orderByDesc('sticky')
                            ->orderByExpr($sortBy)
                            ->orderByDesc('id');
            }

            $result = Hooks::fireDB('model.forum.print_topics_query', $result);
            $result = $result->findMany();

            $topicCount = 0;
            foreach ($result as $curTopic) {
                ++$topicCount;
                $statusText = [];
                $curTopic['item_status'] = ($topicCount % 2 == 0) ? 'roweven' : 'rowodd';
                $curTopic['icon_type'] = 'icon';
                $urlSubject = Url::slug($curTopic['subject']);

                if (is_null($curTopic['moved_to'])) {
                    $curTopic['last_post_formatted'] = '<a href="'.Router::pathFor('viewPost', ['id' => $curTopic['id'], 'name' => $urlSubject, 'pid' => $curTopic['last_post_id']]).'#p'.$curTopic['last_post_id'].'">'.Utils::formatTime($curTopic['last_post']).'</a> <span class="byuser">'.__('by').' '.Utils::escape($curTopic['last_poster']).'</span>';
                } else {
                    $curTopic['last_post_formatted'] = '- - -';
                }

                if (ForumSettings::get('o_censoring') == 1) { // TODO: correct ?
                    $curTopic['subject'] = Utils::censor($curTopic['subject']);
                }

                if ($curTopic['moved_to'] != 0) {
                    $curTopic['subject_formatted'] = '<a href="'.Router::pathFor('Topic', ['id' => $curTopic['moved_to'], 'name' => $urlSubject]).'">'.Utils::escape($curTopic['subject']).'</a> <span class="byuser">'.__('by').' '.Utils::escape($curTopic['poster']).'</span>';
                    $statusText[] = '<span class="movedtext">'.__('Moved').'</span>';
                    $curTopic['item_status'] .= ' imoved';
                } else {
                    $curTopic['subject_formatted'] = '<a href="'.Router::pathFor('Topic', ['id' => $curTopic['id'], 'name' => $urlSubject]).'">'.Utils::escape($curTopic['subject']).'</a> <span class="byuser">'.__('by').' '.Utils::escape($curTopic['poster']).'</span>';
                }

                // Include separate icon, label and background for sticky and closed topics
                if ($curTopic['sticky'] == 1) {
                    $curTopic['item_status'] .= ' isticky';
                    if ($curTopic['closed'] == 1) {
                        $statusText[] = '<span class="stickytext">'.__('Sticky and closed').'</span>';
                        $curTopic['icon_type'] = 'icon icon-closed';
                    } else {
                        $statusText[] = '<span class="stickytext">'.__('Sticky').'</span>';
                        $curTopic['icon_type'] = 'icon icon-sticky';
                    }
                } elseif ($curTopic['closed'] == 1) {
                    $statusText[] = '<span class="closedtext">'.__('Closed').'</span>';
                    $curTopic['item_status'] .= ' iclosed';
                    $curTopic['icon_type'] = 'icon icon-closed';
                }
                
                if (!User::get()->is_guest && $curTopic['last_post'] > User::get()->last_visit && (!isset($trackedTopics['topics'][$curTopic['id']]) || $trackedTopics['topics'][$curTopic['id']] < $curTopic['last_post']) && (!isset($trackedTopics['forums'][$forumId]) || $trackedTopics['forums'][$forumId] < $curTopic['last_post']) && is_null($curTopic['moved_to'])) {
                    $curTopic['item_status'] .= ' inew';
                    $curTopic['icon_type'] = 'icon icon-new';
                    $curTopic['subject_formatted'] = '<strong>'.$curTopic['subject_formatted'].'</strong>';
                    $subjectNewPosts = '<span class="newtext">[ <a href="'.Router::pathFor('topicAction', ['id' => $curTopic['id'], 'name' => $urlSubject, 'action' => 'new']).'" title="'.__('New posts info').'">'.__('New posts').'</a> ]</span>';
                } else {
                    $subjectNewPosts = null;
                }

                // Insert the status text before the subject
                $curTopic['subject_formatted'] = implode(' ', $statusText).' '.$curTopic['subject_formatted'];

                // Should we display the dot or not? :)
                if (!User::get()->is_guest && ForumSettings::get('o_show_dot') == 1) {
                    if ($curTopic['has_posted'] == User::get()->id) {
                        $curTopic['subject_formatted'] = '<strong class="ipost">·&#160;</strong>'.$curTopic['subject_formatted'];
                        $curTopic['item_status'] .= ' iposted';
                    }
                }

                $numPagesTopic = ceil(($curTopic['num_replies'] + 1) / User::getPref('disp.posts'));

                if ($numPagesTopic > 1) {
                    $subjectMultipage = '<span class="pagestext">[ '.Url::paginate($numPagesTopic, -1, 'topic/'.$curTopic['id'].'/'.$urlSubject.'/#').' ]</span>';
                } else {
                    $subjectMultipage = null;
                }

                // Should we show the "New posts" and/or the multipage links?
                if (!empty($subjectNewPosts) || !empty($subjectMultipage)) {
                    $curTopic['subject_formatted'] .= !empty($subjectNewPosts) ? ' '.$subjectNewPosts : '';
                    $curTopic['subject_formatted'] .= !empty($subjectMultipage) ? ' '.$subjectMultipage : '';
                }

                $forumData[] = $curTopic;
            }
        }

        $forumData = Hooks::fire('model.forum.print_topics', $forumData);

        return $forumData;
    }

    public function displayTopicsModerate($fid, $sortBy, $startFrom)
    {
        Hooks::fire('model.forum.display_topics_start', $fid, $sortBy, $startFrom);

        $topicData = [];

        // Get topic/forum tracking data
        if (!User::get()->is_guest) {
            $trackedTopics = Track::getTrackedTopics();
        }

        // Retrieve a list of topic IDs, LIMIT is (really) expensive so we only fetch the IDs here then later fetch the remaining data
        $result = DB::table('topics')->select('id')
                    ->where('forum_id', $fid)
                    ->orderByExpr('sticky DESC, '.$sortBy)
                    ->limit(User::getPref('disp.topics'))
                    ->offset($startFrom);
        $result = Hooks::fireDB('model.forum.display_topics_list_ids', $result);
        $result = $result->findMany();

        // If there are topics in this forum
        if ($result) {
            foreach ($result as $id) {
                $topicIds[] = $id['id'];
            }

            unset($result);
            // Select topics
            $result['select'] = ['id', 'poster', 'subject', 'posted', 'last_post', 'last_post_id', 'last_poster', 'num_views', 'num_replies', 'closed', 'sticky', 'moved_to'];
            $result = DB::table('topics')->selectMany($result['select'])
                        ->whereIn('id', $topicIds)
                        ->orderByDesc('sticky')
                        ->orderByExpr($sortBy)
                        ->orderByDesc('id');
            $result = Hooks::fireDB('model.forum.display_topics_query', $result);
            $result = $result->findMany();

            $topicCount = 0;
            foreach ($result as $curTopic) {
                ++$topicCount;
                $statusText = [];
                $curTopic['item_status'] = ($topicCount % 2 == 0) ? 'roweven' : 'rowodd';
                $curTopic['icon_type'] = 'icon';
                $urlTopic = Url::slug($curTopic['subject']);

                if (is_null($curTopic['moved_to'])) {
                    $curTopic['last_post_disp'] = '<a href="'.Router::pathFor('viewPost', ['id' => $curTopic['id'], 'name' => $urlTopic, 'pid' => $curTopic['last_post_id']]).'#p'.$curTopic['last_post_id'].'">'.Utils::formatTime($curTopic['last_post']).'</a> <span class="byuser">'.__('by').' '.Utils::escape($curTopic['last_poster']).'</span>';
                    $curTopic['ghost_topic'] = false;
                } else {
                    $curTopic['last_post_disp'] = '- - -';
                    $curTopic['ghost_topic'] = true;
                }

                if (ForumSettings::get('o_censoring') == 1) {
                    $curTopic['subject'] = Utils::censor($curTopic['subject']);
                }

                if ($curTopic['moved_to'] != 0) {
                    $curTopic['subject_disp'] = '<a href="'.Router::pathFor('Topic', ['id' => $curTopic['moved_to'], 'name' => $urlTopic]).'">'.Utils::escape($curTopic['subject']).'</a> <span class="byuser">'.__('by').' '.Utils::escape($curTopic['poster']).'</span>';
                    $statusText[] = '<span class="movedtext">'.__('Moved').'</span>';
                    $curTopic['item_status'] .= ' imoved';
                } else {
                    $curTopic['subject_disp'] = '<a href="'.Router::pathFor('Topic', ['id' => $curTopic['id'], 'name' => $urlTopic]).'">'.Utils::escape($curTopic['subject']).'</a> <span class="byuser">'.__('by').' '.Utils::escape($curTopic['poster']).'</span>';
                }

                // Include separate icon, label and background for sticky and closed topics
                if ($curTopic['sticky'] == 1) {
                    $curTopic['item_status'] .= ' isticky';
                    if ($curTopic['closed'] == 1) {
                        $statusText[] = '<span class="stickytext">'.__('Sticky and closed').'</span>';
                        $curTopic['icon_type'] = 'icon icon-closed';
                    } else {
                        $statusText[] = '<span class="stickytext">'.__('Sticky').'</span>';
                        $curTopic['icon_type'] = 'icon icon-sticky';
                    }
                } elseif ($curTopic['closed'] == 1) {
                    $statusText[] = '<span class="closedtext">'.__('Closed').'</span>';
                    $curTopic['item_status'] .= ' iclosed';
                    $curTopic['icon_type'] = 'icon icon-closed';
                }
                
                if (!$curTopic['ghost_topic'] && $curTopic['last_post'] > User::get()->last_visit && (!isset($trackedTopics['topics'][$curTopic['id']]) || $trackedTopics['topics'][$curTopic['id']] < $curTopic['last_post']) && (!isset($trackedTopics['forums'][$fid]) || $trackedTopics['forums'][$fid] < $curTopic['last_post'])) {
                    $curTopic['item_status'] .= ' inew';
                    $curTopic['icon_type'] = 'icon icon-new';
                    $curTopic['subject_disp'] = '<strong>'.$curTopic['subject_disp'].'</strong>';
                    $subjectNewPosts = '<span class="newtext">[ <a href="'.Router::pathFor('topicAction', ['id' => $curTopic['id'], 'name' => $urlTopic, 'action' => 'new']).'" title="'.__('New posts info').'">'.__('New posts').'</a> ]</span>';
                } else {
                    $subjectNewPosts = null;
                }

                // Insert the status text before the subject
                $curTopic['subject_disp'] = implode(' ', $statusText).' '.$curTopic['subject_disp'];

                $numPagesTopic = ceil(($curTopic['num_replies'] + 1) / User::getPref('disp.posts'));

                if ($numPagesTopic > 1) {
                    $subjectMultipage = '<span class="pagestext">[ '.Url::paginate($numPagesTopic, -1, 'topic/'.$curTopic['id'].'/'.$urlTopic.'/#').' ]</span>';
                } else {
                    $subjectMultipage = null;
                }

                // Should we show the "New posts" and/or the multipage links?
                if (!empty($subjectNewPosts) || !empty($subjectMultipage)) {
                    $curTopic['subject_disp'] .= !empty($subjectNewPosts) ? ' '.$subjectNewPosts : '';
                    $curTopic['subject_disp'] .= !empty($subjectMultipage) ? ' '.$subjectMultipage : '';
                }

                $topicData[] = $curTopic;
            }
        }

        $topicData = Hooks::fire('model.forum.display_topics', $topicData);

        return $topicData;
    }

    //
    // Update posts, topics, last_post, last_post_id and last_poster for a forum
    //
    public static function update($forumId)
    {
        $statsQuery = DB::table('topics')
                            ->where('forum_id', $forumId)
                            ->selectExpr('COUNT(id)', 'total_topics')
                            ->selectExpr('SUM(num_replies)', 'total_replies')
                            ->findOne();

        $numTopics = intval($statsQuery['total_topics']);
        $numReplies = intval($statsQuery['total_replies']);

        $numPosts = $numReplies + $numTopics; // $numPosts is only the sum of all replies (we have to add the topic posts)

        $selectUpdateForum = ['last_post', 'last_post_id', 'last_poster'];

        $result = DB::table('topics')->selectMany($selectUpdateForum)
                    ->where('forum_id', $forumId)
                    ->whereNull('moved_to')
                    ->orderByDesc('last_post')
                    ->findOne();

        if ($result) {
            // There are topics in the forum
            $insertUpdateForum = [
                'num_topics' => $numTopics,
                'num_posts'  => $numPosts,
                'last_post'  => $result['last_post'],
                'last_post_id'  => $result['last_post_id'],
                'last_poster'  => $result['last_poster'],
            ];
        } else {
            // There are no topics
            $insertUpdateForum = [
                'num_topics' => $numTopics,
                'num_posts'  => $numPosts,
                'last_post'  => 'NULL',
                'last_post_id'  => 'NULL',
                'last_poster'  => 'NULL',
            ];
        }
        DB::table('forums')
            ->where('id', $forumId)
            ->findOne()
            ->set($insertUpdateForum)
            ->save();
    }

    public function unsubscribe($forumId)
    {
        $forumId = Hooks::fire('model.forum.unsubscribe_forum_start', $forumId);

        if (ForumSettings::get('o_forum_subscriptions') != 1) {
            throw new Error(__('No permission'), 403);
        }

        $isSubscribed = DB::table('forum_subscriptions')
            ->where('user_id', User::get()->id)
            ->where('forum_id', $forumId);
        $isSubscribed = Hooks::fireDB('model.forum.unsubscribe_forum_subscribed_query', $isSubscribed);
        $isSubscribed = $isSubscribed->findOne();

        if (!$isSubscribed) {
            throw new Error(__('Not subscribed forum'), 400);
        }

        // Delete the subscription
        $delete = DB::table('forum_subscriptions')
            ->where('user_id', User::get()->id)
            ->where('forum_id', $forumId);
        $delete = Hooks::fireDB('model.forum.unsubscribe_forum_query', $delete);
        $delete->deleteMany();
    }

    public function subscribe($forumId)
    {
        $forumId = Hooks::fire('model.forum.subscribe_forum_start', $forumId);

        if (ForumSettings::get('o_forum_subscriptions') != 1) {
            throw new Error(__('No permission'), 403);
        }

        // Make sure the user can view the forum
        $authorized['where'] = [
            ['fp.read_forum' => 'IS NULL'],
            ['fp.read_forum' => 1]
        ];

        $authorized = DB::table('forums')
                        ->tableAlias('f')
                        ->leftOuterJoin('forum_perms', 'fp.forum_id=f.id AND fp.group_id='.User::get()->g_id, 'fp')
                        ->whereAnyIs($authorized['where'])
                        ->where('f.id', $forumId);
        $authorized = Hooks::fireDB('model.forum.subscribe_forum_authorized_query', $authorized);
        $authorized = $authorized->findOne();

        if (!$authorized) {
            throw new Error(__('Bad request'), 404);
        }

        $isSubscribed = DB::table('forum_subscriptions')
            ->where('user_id', User::get()->id)
            ->where('forum_id', $forumId);
        $isSubscribed = Hooks::fireDB('model.forum.subscribe_forum_subscribed_query', $isSubscribed);
        $isSubscribed = $isSubscribed->findOne();

        if ($isSubscribed) {
            throw new Error(__('Already subscribed forum'), 400);
        }

        // Insert the subscription
        $subscription['insert'] = [
            'user_id' => User::get()->id,
            'forum_id'  => $forumId
        ];
        $subscription = DB::table('forum_subscriptions')
                            ->create()
                            ->set($subscription['insert']);
        $subscription = Hooks::fireDB('model.forum.subscribe_forum_query', $subscription);
        $subscription->save();
    }

    public function closeMultiple($action, $topics)
    {
        $closeMultipleTopics = DB::table('topics')
                                    ->whereIn('id', $topics);
        $closeMultipleTopics = Hooks::fireDB('model.forum.open_topic', $closeMultipleTopics);
        $closeMultipleTopics = $closeMultipleTopics->updateMany('closed', $action);
    }

    public function stickMultiple($action, $topics)
    {
        $stickMultipleTopics = DB::table('topics')
                                    ->whereIn('id', $topics);
        $stickMultipleTopics = Hooks::fireDB('model.forum.stick_topic', $stickMultipleTopics);
        $stickMultipleTopics = $stickMultipleTopics->updateMany('sticky', $action);
    }

    public function delete($topics, $fid)
    {
        Hooks::fire('model.forum.delete_topics', $topics, $fid);

        if (@preg_match('%[^0-9,]%', $topics)) {
            throw new Error(__('Bad request'), 400);
        }

        $topicsSql = explode(',', $topics);

        // Verify that the topic IDs are valid
        $result = DB::table('topics')
                    ->whereIn('id', $topicsSql)
                    ->where('forum_id', $fid);
        $result = Hooks::fireDB('model.forum.delete_topics_verify_id', $result);
        $result = $result->findMany();

        if (count($result) != substr_count($topics, ',') + 1) {
            throw new Error(__('Bad request'), 400);
        }

        // Verify that the posts are not by admins
        if (User::get()->g_id != ForumEnv::get('FEATHER_ADMIN')) {
            $authorized = DB::table('posts')
                            ->whereIn('topic_id', $topicsSql)
                            ->where('poster_id', Utils::getAdminIds());
            $authorized = Hooks::fireDB('model.forum.delete_topics_authorized', $authorized);
            $authorized = $authorized->findMany();
            if ($authorized) {
                throw new Error(__('No permission'), 403);
            }
        }

        // Delete the topics
        $deleteTopics = DB::table('topics')
                            ->whereIn('id', $topicsSql);
        $deleteTopics = Hooks::fireDB('model.forum.delete_topics_query', $deleteTopics);
        $deleteTopics = $deleteTopics->deleteMany();

        // Delete any redirect topics
        $deleteRedirectTopics = DB::table('topics')
                                    ->whereIn('moved_to', $topicsSql);
        $deleteRedirectTopics = Hooks::fireDB('model.forum.delete_topics_redirect', $deleteRedirectTopics);
        $deleteRedirectTopics = $deleteRedirectTopics->deleteMany();

        // Delete any subscriptions
        $deleteSubscriptions = DB::table('topic_subscriptions')
                                    ->whereIn('topic_id', $topicsSql);
        $deleteSubscriptions = Hooks::fireDB('model.forum.delete_topics_subscriptions', $deleteSubscriptions);
        $deleteSubscriptions = $deleteSubscriptions->deleteMany();

        // Create a list of the post IDs in this topic and then strip the search index
        $findIds = DB::table('posts')
                        ->select('id')
                        ->whereIn('topic_id', $topicsSql);
        $findIds = Hooks::fireDB('model.forum.delete_topics_find_ids', $findIds);
        $findIds = $findIds->findMany();

        $idsPost = [];

        foreach ($findIds as $id) {
            $idsPost[] = $id['id'];
        }

        $postIds = implode(', ', $idsPost);

        // We have to check that we actually have a list of post IDs since we could be deleting just a redirect topic
        if ($postIds != '') {
            $search = new \FeatherBB\Core\Search();
            $search->stripSearchIndex($postIds);
        }

        // Delete posts
        $deletePosts = DB::table('posts')
                            ->whereIn('topic_id', $topicsSql);
        $deletePosts = Hooks::fireDB('model.forum.delete_topics_delete_posts', $deletePosts);
        $deletePosts = $deletePosts->deleteMany();

        self::update($fid);
    }

    public function merge($fid)
    {
        $fid = Hooks::fire('model.forum.merge_topics_start', $fid);

        if (@preg_match('%[^0-9,]%', Input::post('topics'))) {
            throw new Error(__('Bad request'), 404);
        }

        $topics = explode(',', Input::post('topics'));
        if (count($topics) < 2) {
            throw new Error(__('Not enough topics selected'), 400);
        }

        // Verify that the topic IDs are valid (redirect links will point to the merged topic after the merge)
        $result = DB::table('topics')
                    ->whereIn('id', $topics)
                    ->where('forum_id', $fid);
        $result = Hooks::fireDB('model.forum.merge_topics_topic_ids', $result);
        $result = $result->findMany();

        if (count($result) != count($topics)) {
            throw new Error(__('Bad request'), 400);
        }

        // The topic that we are merging into is the one with the smallest ID
        $mergeToTid = DB::table('topics')
                            ->whereIn('id', $topics)
                            ->where('forum_id', $fid)
                            ->orderByAsc('id')
                            ->findOneCol('id');
        $mergeToTid = Hooks::fire('model.forum.merge_topics_tid', $mergeToTid);

        // Make any redirect topics point to our new, merged topic
        $query = 'UPDATE '.ForumSettings::get('db_prefix').'topics SET moved_to='.$mergeToTid.' WHERE moved_to IN('.implode(',', $topics).')';

        // Should we create redirect topics?
        if (Input::post('with_redirect')) {
            $query .= ' OR (id IN('.implode(',', $topics).') AND id != '.$mergeToTid.')';
        }

        // TODO ?
        DB::table('topics')->rawExecute($query);

        // Merge the posts into the topic
        $mergePosts = DB::table('posts')
                        ->whereIn('topic_id', $topics);
        $mergePosts = Hooks::fireDB('model.forum.merge_topics_merge_posts', $mergePosts);
        $mergePosts = $mergePosts->updateMany('topic_id', $mergeToTid);

        // Update any subscriptions
        $findIds = DB::table('topic_subscriptions')->select('user_id')
                        ->distinct()
                        ->whereIn('topic_id', $topics);
        $findIds = Hooks::fireDB('model.forum.merge_topics_find_ids', $findIds);
        $findIds = $findIds->findMany();

        $subscribedUsers = [];
        foreach ($findIds as $id) {
            $subscribedUsers[] = $id['user_id'];
        }

        // Delete the subscriptions
        $deleteSubscriptions = DB::table('topic_subscriptions')
                                    ->whereIn('topic_id', $topics);
        $deleteSubscriptions = Hooks::fireDB('model.forum.merge_topics_delete_subscriptions', $deleteSubscriptions);
        $deleteSubscriptions = $deleteSubscriptions->deleteMany();

        // If users subscribed to one of the topics, keep subscription for merged topic
        foreach ($subscribedUsers as $curUserId) {
            $subscriptions['insert'] = [
                'topic_id'  =>  $mergeToTid,
                'user_id'   =>  $curUserId,
            ];
            // Insert the subscription
            $subscriptions = DB::table('topic_subscriptions')
                                ->create()
                                ->set($subscriptions['insert']);
            $subscriptions = Hooks::fireDB('model.forum.merge_topics_insert_subscriptions', $subscriptions);
            $subscriptions = $subscriptions->save();
        }

        // Without redirection the old topics are removed
        if (Input::post('with_redirect') == 0) {
            $deleteTopics = DB::table('topics')
                                ->whereIn('id', $topics)
                                ->whereNotEqual('id', $mergeToTid);
            $deleteTopics = Hooks::fireDB('model.forum.merge_topics_delete_topics', $deleteTopics);
            $deleteTopics = $deleteTopics->deleteMany();
        }

        // Count number of replies in the topic
        $numReplies = DB::table('posts')->where('topic_id', $mergeToTid)->count('id') - 1;
        $numReplies = Hooks::fire('model.forum.merge_topics_num_replies', $numReplies);

        // Get last_post, last_post_id and last_poster
        $lastPost['select'] = ['posted', 'id', 'poster'];

        $lastPost = DB::table('posts')
                        ->selectMany($lastPost['select'])
                        ->where('topic_id', $mergeToTid)
                        ->orderByDesc('id');
        $lastPost = Hooks::fireDB('model.forum.merge_topics_last_post', $lastPost);
        $lastPost = $lastPost->findOne();

        // Update topic
        $updateTopic['insert'] = [
            'num_replies' => $numReplies,
            'last_post'  => $lastPost['posted'],
            'last_post_id'  => $lastPost['id'],
            'last_poster'  => $lastPost['poster'],
        ];

        $topic = DB::table('topics')
                    ->where('id', $mergeToTid)
                    ->findOne()
                    ->set($updateTopic['insert']);
        $topic = Hooks::fireDB('model.forum.merge_topics_update_topic', $topic);
        $topic = $topic->save();

        Hooks::fire('model.forum.merge_topics');

        // Update the forum FROM which the topic was moved and redirect
        self::update($fid);
    }

    public static function canModerate($fid)
    {
        $moderators = self::getModerators($fid);
        $modsArray = ($moderators != '') ? unserialize($moderators) : [];

        // Sort out who has permission to moderate
        $permission = (User::isAdmin() || (User::isAdminMod() && array_key_exists(User::get()->username, $modsArray))) ? true : false;

        return $permission;
    }
}
