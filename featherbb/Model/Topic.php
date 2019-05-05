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
use FeatherBB\Core\Interfaces\Parser;
use FeatherBB\Core\Interfaces\Prefs;
use FeatherBB\Core\Interfaces\Router;
use FeatherBB\Core\Interfaces\User;
use FeatherBB\Core\Track;
use FeatherBB\Core\Url;
use FeatherBB\Core\Utils;

class Topic
{
    //
    // Delete a topic and all of its posts
    //
    public static function delete($topicId)
    {
        // Delete the topic and any redirect topics
        $whereDeleteTopic = [
            ['id' => $topicId],
            ['moved_to' => $topicId]
        ];

        DB::table('topics')
            ->whereAnyIs($whereDeleteTopic)
            ->deleteMany();

        // Delete posts in topic
        DB::table('posts')
            ->where('topic_id', $topicId)
            ->deleteMany();

        // Delete any subscriptions for this topic
        DB::table('topic_subscriptions')
            ->where('topic_id', $topicId)
            ->deleteMany();
    }

    // Redirect to a post in particular
    public function redirectToPost($postId)
    {
        $postId = Hooks::fire('model.topic.redirect_to_post', $postId);

        $result['select'] = ['topic_id', 'posted'];

        $result = DB::table('posts')
                      ->selectMany($result['select'])
                      ->where('id', $postId);
        $result = Hooks::fireDB('model.topic.redirect_to_post_query', $result);
        $result = $result->findOne();

        if (!$result) {
            throw new Error(__('Bad request'), 404);
        }

        $post['topic_id'] = $result['topic_id'];
        $posted = $result['posted'];

        // Determine on which page the post is located (depending on $forumUser['disp_posts'])
        $numPosts = DB::table('posts')
                        ->where('topic_id', $post['topic_id'])
                        ->whereLt('posted', $posted)
                        ->count('id');

        $numPosts = Hooks::fire('model.topic.redirect_to_post_num', $numPosts);

        $post['get_p'] = ceil(($numPosts + 1) / User::getPref('disp.posts'));

        $post = Hooks::fire('model.topic.redirect_to_post', $post);

        return $post;
    }

    // Redirect to new posts or last post
    public function handleActions($topicId, $topicSubject, $action)
    {
        $action = Hooks::fire('model.topic.handle_actions_start', $action, $topicId);

        // If action=new, we redirect to the first new post (if any)
        if ($action == 'new') {
            if (!User::get()->is_guest) {
                // We need to check if this topic has been viewed recently by the user
                $trackedTopics = Track::getTrackedTopics();
                $lastViewed = isset($trackedTopics['topics'][$topicId]) ? $trackedTopics['topics'][$topicId] : User::get()->last_visit;

                $firstNewPostId = DB::table('posts')
                                        ->where('topic_id', $topicId)
                                        ->whereGt('posted', $lastViewed)
                                        ->min('id');

                $firstNewPostId = Hooks::fire('model.topic.handle_actions_first_new', $firstNewPostId);

                if ($firstNewPostId) {
                    return Router::redirect(Router::pathFor('viewPost', ['id' => $topicId, 'name' => $topicSubject, 'pid' => $firstNewPostId]).'#p'.$firstNewPostId);
                }
            }

            // If there is no new post, we go to the last post
            $action = 'last';
        }

        // If action=last, we redirect to the last post
        if ($action == 'last') {
            $lastPostId = DB::table('posts')
                                ->where('topic_id', $topicId)
                                ->max('id');

            $lastPostId = Hooks::fire('model.topic.handle_actions_last_post', $lastPostId);

            if ($lastPostId) {
                return Router::redirect(Router::pathFor('viewPost', ['id' => $topicId, 'name' => $topicSubject, 'pid' => $lastPostId]).'#p'.$lastPostId);
            }
        }

        Hooks::fire('model.topic.handle_actions', $action, $topicId);
    }

    // Gets some info about the topic
    public function getInfoTopic($id)
    {
        $curTopic['where'] = [
            ['fp.read_forum' => 'IS NULL'],
            ['fp.read_forum' => '1']
        ];

        if (!User::get()->is_guest) {
            $selectGetInfoTopic = ['t.subject', 't.closed', 't.num_replies', 't.sticky', 't.first_post_id', 'forum_id' => 'f.id', 'f.forum_name', 'f.moderators', 'fp.post_replies', 'is_subscribed' => 's.user_id'];

            $curTopic = DB::table('topics')
                ->tableAlias('t')
                ->selectMany($selectGetInfoTopic)
                ->innerJoin('forums', ['f.id', '=', 't.forum_id'], 'f')
                ->leftOuterJoin('topic_subscriptions', 't.id=s.topic_id AND s.user_id='.User::get()->id, 's')
                ->leftOuterJoin('forum_perms', 'fp.forum_id=f.id AND fp.group_id='.User::get()->g_id, 'fp')
                ->whereAnyIs($curTopic['where'])
                ->where('t.id', $id)
                ->whereNull('t.moved_to');
        } else {
            $selectGetInfoTopic = ['t.subject', 't.closed', 't.num_replies', 't.sticky', 't.first_post_id', 'forum_id' => 'f.id', 'f.forum_name', 'f.moderators', 'fp.post_replies'];

            $curTopic = DB::table('topics')
                            ->tableAlias('t')
                            ->selectMany($selectGetInfoTopic)
                            ->selectExpr(0, 'is_subscribed')
                            ->innerJoin('forums', ['f.id', '=', 't.forum_id'], 'f')
                            ->leftOuterJoin('forum_perms', 'fp.forum_id=f.id AND fp.group_id='.User::get()->g_id, 'fp')
                            ->whereAnyIs($curTopic['where'])
                            ->where('t.id', $id)
                            ->whereNull('t.moved_to');
        }

        $curTopic = Hooks::fireDB('model.topic.get_info_topic_query', $curTopic);
        $curTopic = $curTopic->findOne();

        if (!$curTopic) {
            throw new Error(__('Bad request'), 404);
        }

        $curTopic = Hooks::fire('model.topic.get_info_topic', $curTopic);

        return $curTopic;
    }

    // Generates the post link
    public function postLink($topicId, $closed, $postReplies, $isAdmmod)
    {
        $closed = Hooks::fire('model.topic.get_post_link_start', $closed, $topicId, $postReplies, $isAdmmod);

        if ($closed == '0') {
            if (($postReplies == '' && User::can('topic.reply')) || $postReplies == '1' || $isAdmmod) {
                $postLink = "\t\t\t".'<p class="postlink conr"><a href="'.Router::pathFor('newReply', ['tid' => $topicId]).'">'.__('Post reply').'</a></p>'."\n";
            } else {
                $postLink = '';
            }
        } else {
            $postLink = __('Topic closed');

            if ($isAdmmod) {
                $postLink .= ' / <a href="'.Router::pathFor('newReply', ['tid' => $topicId]).'">'.__('Post reply').'</a>';
            }

            $postLink = "\t\t\t".'<p class="postlink conr">'.$postLink.'</p>'."\n";
        }

        $postLink = Hooks::fire('model.topic.get_post_link_start', $postLink, $topicId, $closed, $postReplies, $isAdmmod);

        return $postLink;
    }

    // Should we display the quickpost?
    public function isQuickpost($postReplies, $closed, $isAdmmod)
    {
        $quickpost = false;
        if (ForumSettings::get('o_quickpost') == '1' && ($postReplies == '1' || ($postReplies == '' && User::can('topic.reply'))) && ($closed == '0' || $isAdmmod)) {
            $quickpost = true;
        }

        $quickpost = Hooks::fire('model.topic.is_quickpost', $quickpost, $postReplies, $closed, $isAdmmod);

        return $quickpost;
    }

    public function subscribe($topicId)
    {
        $topicId = Hooks::fire('model.topic.subscribe_topic_start', $topicId);

        if (ForumSettings::get('o_topic_subscriptions') != '1') {
            throw new Error(__('No permission'), 403);
        }

        // Make sure the user can view the topic
        $authorized['where'] = [
            ['fp.read_forum' => 'IS NULL'],
            ['fp.read_forum' => '1']
        ];

        $authorized = DB::table('topics')
                        ->tableAlias('t')
                        ->leftOuterJoin('forum_perms', 'fp.forum_id=t.forum_id AND fp.group_id='.User::get()->g_id, 'fp')
                        ->whereAnyIs($authorized['where'])
                        ->where('t.id', $topicId)
                        ->whereNull('t.moved_to');
        $authorized = Hooks::fireDB('model.topic.subscribe_topic_authorized_query', $authorized);
        $authorized = $authorized->findOne();

        if (!$authorized) {
            throw new Error(__('Bad request'), 404);
        }

        $isSubscribed = DB::table('topic_subscriptions')
                        ->where('user_id', User::get()->id)
                        ->where('topic_id', $topicId);
        $isSubscribed = Hooks::fireDB('model.topic.subscribe_topic_is_subscribed_query', $isSubscribed);
        $isSubscribed = $isSubscribed->findOne();

        if ($isSubscribed) {
            throw new Error(__('Already subscribed topic'), 400);
        }

        $subscription['insert'] = [
            'user_id' => User::get()->id,
            'topic_id'  => $topicId
        ];

        // Insert the subscription
        $subscription = DB::table('topic_subscriptions')
                                    ->create()
                                    ->set($subscription['insert']);
        $subscription = Hooks::fireDB('model.topic.subscribe_topic_query', $subscription);
        $subscription = $subscription->save();

        return $subscription;
    }

    public function unsubscribe($topicId)
    {
        $topicId = Hooks::fire('model.topic.unsubscribe_topic_start', $topicId);

        if (ForumSettings::get('o_topic_subscriptions') != '1') {
            throw new Error(__('No permission'), 403);
        }

        $isSubscribed = DB::table('topic_subscriptions')
                            ->where('user_id', User::get()->id)
                            ->where('topic_id', $topicId);
        $isSubscribed = Hooks::fireDB('model.topic.unsubscribe_topic_subscribed_query', $isSubscribed);
        $isSubscribed = $isSubscribed->findOne();

        if (!$isSubscribed) {
            throw new Error(__('Not subscribed topic'), 400);
        }

        // Delete the subscription
        $delete = DB::table('topic_subscriptions')
                    ->where('user_id', User::get()->id)
                    ->where('topic_id', $topicId);
        $delete = Hooks::fireDB('model.topic.unsubscribe_topic_query', $delete);
        $delete = $delete->deleteMany();

        return $delete;
    }

    // Subscraction link
    public function getSubscraction($isSubscribed, $topicId, $topicSubject)
    {
        if (!User::get()->is_guest && ForumSettings::get('o_topic_subscriptions') == '1') {
            if ($isSubscribed) {
                // I apologize for the variable naming here. It's a mix of subscription and action I guess :-)
                $subscraction = "\t\t".'<p class="subscribelink clearb"><span>'.__('Is subscribed').' - </span><a href="'.Router::pathFor('unsubscribeTopic', ['id' => $topicId, 'name' => $topicSubject]).'">'.__('Unsubscribe').'</a></p>'."\n";
            } else {
                $subscraction = "\t\t".'<p class="subscribelink clearb"><a href="'.Router::pathFor('subscribeTopic', ['id' => $topicId, 'name' => $topicSubject]).'">'.__('Subscribe').'</a></p>'."\n";
            }
        } else {
            $subscraction = '';
        }

        $subscraction = Hooks::fire('model.topic.get_subscraction', $subscraction, $isSubscribed, $topicId, $topicSubject);

        return $subscraction;
    }

    public function setSticky($id, $value)
    {
        $sticky = DB::table('topics')
                            ->findOne($id)
                            ->set('sticky', $value);
        $sticky = Hooks::fireDB('model.topic.stick_topic', $sticky);
        $sticky->save();

        return $sticky;
    }

    public function setClosed($id, $value)
    {
        $closed = DB::table('topics')
                            ->findOne($id)
                            ->set('closed', $value);
        $closed = Hooks::fireDB('model.topic.stick_topic', $closed);
        $closed->save();

        return $closed;
    }

    public function checkMove()
    {
        Hooks::fire('model.topic.check_move_possible_start');

        $result['select'] = ['cid' => 'c.id', 'c.cat_name', 'fid' => 'f.id', 'f.forum_name'];
        $result['where'] = [
            ['fp.post_topics' => 'IS NULL'],
            ['fp.post_topics' => '1']
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
        $result = Hooks::fireDB('model.topic.check_move_possible', $result);
        $result = $result->findMany();

        if (count($result) < 2) {
            return false;
        }
        return true;
    }

    public function getForumListMove($fid)
    {
        $output = '';

        $selectGetForumListMove = ['cid' => 'c.id', 'c.cat_name', 'fid' => 'f.id', 'f.forum_name'];
        $whereGetForumListMove = [
            ['fp.post_topics' => 'IS NULL'],
            ['fp.post_topics' => '1']
        ];
        $orderByGetForumListMove = ['c.disp_position', 'c.id', 'f.disp_position'];

        $result = DB::table('categories')
                    ->tableAlias('c')
                    ->selectMany($selectGetForumListMove)
                    ->innerJoin('forums', ['c.id', '=', 'f.cat_id'], 'f')
                    ->leftOuterJoin('forum_perms', 'fp.forum_id=f.id AND fp.group_id='.User::get()->g_id, 'fp')
                    ->whereAnyIs($whereGetForumListMove)
                    ->whereNull('f.redirect_url')
                    ->orderByMany($orderByGetForumListMove);
        $result = Hooks::fireDB('model.topic.get_forum_list_move_query', $result);
        $result = $result->findResultSet();

        $curCategory = 0;

        foreach ($result as $curForum) {
            if ($curForum->fid != $fid) {
                if ($curForum->cid != $curCategory) {
                    // A new category since last iteration?

                    if ($curCategory) {
                        $output .= "\t\t\t\t\t\t\t".'</optgroup>'."\n";
                    }

                    $output .= "\t\t\t\t\t\t\t".'<optgroup label="'.Utils::escape($curForum->cat_name).'">'."\n";
                    $curCategory = $curForum->cid;
                }

                $output .= "\t\t\t\t\t\t\t\t".'<option value="'.$curForum->fid.'">'.Utils::escape($curForum->forum_name).'</option>'."\n";
            }
        }

        $output = Hooks::fire('model.topic.get_forum_list_move', $output);

        return $output;
    }

    public function getSplitForumList($id)
    {
        $output = '';

        $result['select'] = ['cid' => 'c.id', 'c.cat_name', 'fid' => 'f.id', 'f.forum_name'];
        $result['where'] = [
            ['fp.post_topics' => 'IS NULL'],
            ['fp.post_topics' => '1']
        ];
        $orderByGetForumListSplit = ['c.disp_position', 'c.id', 'f.disp_position'];

        $result = DB::table('categories')
                    ->tableAlias('c')
                    ->selectMany($result['select'])
                    ->innerJoin('forums', ['c.id', '=', 'f.cat_id'], 'f')
                    ->leftOuterJoin('forum_perms', 'fp.forum_id=f.id AND fp.group_id='.User::get()->g_id, 'fp')
                    ->whereAnyIs($result['where'])
                    ->whereNull('f.redirect_url')
                    ->orderByMany($orderByGetForumListSplit);
        $result = Hooks::fireDB('model.topic.get_forum_list_split_query', $result);
        $result = $result->findResultSet();

        $curCategory = 0;

        foreach ($result as $curForum) {
            if ($curForum->cid != $curCategory) {
                // A new category since last iteration?

                if ($curCategory) {
                    $output .= "\t\t\t\t\t\t\t".'</optgroup>'."\n";
                }

                $output .= "\t\t\t\t\t\t\t".'<optgroup label="'.Utils::escape($curForum->cat_name).'">'."\n";
                $curCategory = $curForum->cid;
            }

            $output .= "\t\t\t\t\t\t\t\t".'<option value="'.$curForum->fid.'"'.($id == $curForum->fid ? ' selected="selected"' : '').'>'.Utils::escape($curForum->forum_name).'</option>'."\n";
        }

        $output = Hooks::fire('model.topic.get_forum_list_split', $output);

        return $output;
    }

    public function moveTo($fid, $newFid, $tid = null)
    {
        Hooks::fire('model.topic.move_to_start', $fid, $newFid, $tid);

        $topics = is_string($tid) ? [$tid] : $tid;
        $newFid = intval($newFid);
        $fid = intval($fid);

        if (empty($topics) || $newFid < 1) {
            throw new Error(__('Bad request'), 400);
        }

        // Verify that the topic IDs are valid
        $result = DB::table('topics')
                    ->whereIn('id', $topics)
                    ->where('forum_id', $fid);
        $result = Hooks::fireDB('model.topic.move_to_topic_valid', $result);
        $result = $result->findMany();

        if (count($result) != count($topics)) {
            throw new Error(__('Bad request'), 400);
        }

        // Verify that the move to forum ID is valid
        $authorized['where'] = [
            ['fp.post_topics' => 'IS NULL'],
            ['fp.post_topics' => '1']
        ];

        $authorized = DB::table('forums')
                        ->tableAlias('f')
                        ->leftOuterJoin('forum_perms', 'fp.forum_id='.$newFid.' AND fp.group_id='.User::get()->g_id, 'fp')
                        ->whereAnyIs($authorized['where'])
                        ->whereNull('f.redirect_url');
        $authorized = Hooks::fireDB('model.topic.move_to_authorized', $authorized);
        $authorized = $authorized->findOne();

        if (!$authorized) {
            throw new Error(__('Bad request'), 404);
        }

        // Delete any redirect topics if there are any (only if we moved/copied the topic back to where it was once moved from)
        $deleteRedirect = DB::table('topics')
                                ->where('forum_id', $newFid)
                                ->whereIn('moved_to', $topics);
        $deleteRedirect = Hooks::fireDB('model.topic.move_to_delete_redirect', $deleteRedirect);
        $deleteRedirect->deleteMany();

        // Move the topic(s)
        $moveTopics = DB::table('topics')->whereIn('id', $topics)
                        ->findResultSet()
                        ->set('forum_id', $newFid);
        $moveTopics = Hooks::fireDB('model.topic.move_to_query', $moveTopics);
        $moveTopics->save();

        // Should we create redirect topics?
        if (Input::post('with_redirect')) {
            foreach ($topics as $curTopic) {
                // Fetch info for the redirect topic
                $movedTo['select'] = ['poster', 'subject', 'posted', 'last_post'];

                $movedTo = DB::table('topics')->selectMany($movedTo['select'])
                                ->where('id', $curTopic);
                $movedTo = Hooks::fireDB('model.topic.move_to_fetch_redirect', $movedTo);
                $movedTo = $movedTo->findOne();

                // Create the redirect topic
                $insertMoveTo = [
                    'poster' => $movedTo['poster'],
                    'subject'  => $movedTo['subject'],
                    'posted'  => $movedTo['posted'],
                    'last_post'  => $movedTo['last_post'],
                    'moved_to'  => $curTopic,
                    'forum_id'  => $fid,
                ];

                // Insert the report
                $moveTo = DB::table('topics')
                                    ->create()
                                    ->set($insertMoveTo);
                $moveTo = Hooks::fireDB('model.topic.move_to_redirect', $moveTo);
                $moveTo = $moveTo->save();
            }
        }

        Forum::update($fid); // Update the forum FROM which the topic was moved
        Forum::update($newFid); // Update the forum TO which the topic was moved
    }

    public function deletePosts($tid, $fid)
    {
        $posts = Input::post('posts', []);
        $posts = Hooks::fire('model.topic.delete_posts_start', $posts, $tid, $fid);

        if (empty($posts)) {
            throw new Error(__('No posts selected'), 404);
        }

        if (Input::post('delete_posts_comply')) {
            if (@preg_match('%[^0-9,]%', $posts)) {
                throw new Error(__('Bad request'), 400);
            }

            // Verify that the post IDs are valid
            $postsArray = explode(',', $posts);

            $result = DB::table('posts')
                ->whereIn('id', $postsArray)
                ->where('topic_id', $tid);

            if (User::get()->g_id != ForumEnv::get('FEATHER_ADMIN')) {
                $result->whereNotIn('poster_id', Utils::getAdminIds());
            }

            $result = Hooks::fireDB('model.topic.delete_posts_first_query', $result);
            $result = $result->findMany();

            if (count($result) != substr_count($posts, ',') + 1) {
                throw new Error(__('Bad request'), 400);
            }

            // Delete the posts
            $deletePosts = DB::table('posts')
                                ->whereIn('id', $postsArray);
            $deletePosts = Hooks::fireDB('model.topic.delete_posts_query', $deletePosts);
            $deletePosts = $deletePosts->deleteMany();

            $search = new \FeatherBB\Core\Search();
            $search->stripSearchIndex($posts);

            // Get last_post, last_post_id, and last_poster for the topic after deletion
            $lastPost['select'] = ['id', 'poster', 'posted'];

            $lastPost = DB::table('posts')
                ->selectMany($lastPost['select'])
                ->where('topic_id', $tid);
            $lastPost = Hooks::fireDB('model.topic.delete_posts_last_post_query', $lastPost);
            $lastPost = $lastPost->findOne();

            // How many posts did we just delete?
            $numPostsDeleted = substr_count($posts, ',') + 1;

            // Update the topic
            $updateTopic['insert'] = [
                'last_post' => User::get()->id,
                'last_post_id'  => $lastPost['id'],
                'last_poster'  => $lastPost['poster'],
            ];

            $updateTopic = DB::table('topics')->where('id', $tid)
                ->findOne()
                ->set($updateTopic['insert'])
                ->setExpr('num_replies', 'num_replies-'.$numPostsDeleted);
            $updateTopic = Hooks::fireDB('model.topic.delete_posts_update_topic_query', $updateTopic);
            $topicSubject = Url::slug($updateTopic->subject);
            $updateTopic = $updateTopic->save();

            Forum::update($fid);
            return Router::redirect(Router::pathFor('Topic', ['id' => $tid, 'name' => $topicSubject]), __('Delete posts redirect'));
        } else {
            $posts = Hooks::fire('model.topic.delete_posts', $posts);
            return $posts;
        }
    }

    public function getTopicInfo($fid, $tid)
    {
        // Fetch some info about the topic
        $curTopic['select'] = ['forum_id' => 'f.id', 'f.forum_name', 't.subject', 't.num_replies', 't.first_post_id'];
        $curTopic['where'] = [
            ['fp.read_forum' => 'IS NULL'],
            ['fp.read_forum' => '1']
        ];

        $curTopic = DB::table('topics')
            ->tableAlias('t')
            ->selectMany($curTopic['select'])
            ->innerJoin('forums', ['f.id', '=', 't.forum_id'], 'f')
            ->leftOuterJoin('forum_perms', 'fp.forum_id=f.id AND fp.group_id='.User::get()->g_id, 'fp')
            ->whereAnyIs($curTopic['where'])
            ->where('f.id', $fid)
            ->where('t.id', $tid)
            ->whereNull('t.moved_to');
        $curTopic = Hooks::fireDB('model.topic.get_topic_info', $curTopic);
        $curTopic = $curTopic->findOne();

        if (!$curTopic) {
            throw new Error(__('Bad request'), 404);
        }

        return $curTopic;
    }

    public function splitPosts($tid, $fid, $p = null)
    {
        $posts = Input::post('posts') ? Input::post('posts') : [];
        $posts = Hooks::fire('model.topic.split_posts_start', $posts, $tid, $fid);
        if (empty($posts)) {
            throw new Error(__('No posts selected'), 404);
        }

        if (Input::post('split_posts_comply')) {
            if (@preg_match('%[^0-9,]%', $posts)) {
                throw new Error(__('Bad request'), 400);
            }

            $moveToForum = Input::post('move_to_forum') ? intval(Input::post('move_to_forum')) : 0;
            if ($moveToForum < 1) {
                throw new Error(__('Bad request'), 400);
            }

            // How many posts did we just split off?
            $numPostsSplitted = substr_count($posts, ',') + 1;

            // Verify that the post IDs are valid
            $postsArray = explode(',', $posts);

            $result = DB::table('posts')
                ->whereIn('id', $postsArray)
                ->where('topic_id', $tid);
            $result = Hooks::fireDB('model.topic.split_posts_first_query', $result);
            $result = $result->findMany();

            if (count($result) != $numPostsSplitted) {
                throw new Error(__('Bad request'), 400);
            }

            unset($result);

            // Verify that the move to forum ID is valid
            $result['where'] = [
                ['fp.post_topics' => 'IS NULL'],
                ['fp.post_topics' => '1']
            ];

            $result = DB::table('forums')
                        ->tableAlias('f')
                        ->leftOuterJoin('forum_perms', 'fp.forum_id='.$moveToForum.' AND fp.group_id='.User::get()->g_id, 'fp')
                        ->whereAnyIs($result['where'])
                        ->whereNull('f.redirect_url');
            $result = Hooks::fireDB('model.topic.split_posts_second_query', $result);
            $result = $result->findOne();

            if (!$result) {
                throw new Error(__('Bad request'), 404);
            }

            // Check subject
            $newSubject = Input::post('new_subject');

            if ($newSubject == '') {
                throw new Error(__('No subject'), 400);
            } elseif (Utils::strlen($newSubject) > 70) {
                throw new Error(__('Too long subject'), 400);
            }

            // Get data from the new first post
            $selectFirstPost = ['id', 'poster', 'posted'];

            $firstPostData = DB::table('posts')
                ->selectMany($selectFirstPost)
                ->whereIn('id', $postsArray)
                ->orderByAsc('id')
                ->findOne();

            // Create the new topic
            $topic['insert'] = [
                'poster' => $firstPostData['poster'],
                'subject'  => $newSubject,
                'posted'  => $firstPostData['posted'],
                'first_post_id'  => $firstPostData['id'],
                'forum_id'  => $moveToForum,
            ];

            $topic = DB::table('topics')
                ->create()
                ->set($topic['insert']);
            $topic = Hooks::fireDB('model.topic.split_posts_topic_query', $topic);
            $topic->save();

            $newTid = DB::getDb()->lastInsertId(ForumEnv::get('DB_PREFIX').'topics');

            // Move the posts to the new topic
            $movePosts = DB::table('posts')->whereIn('id', $postsArray)
                ->findResultSet()
                ->set('topic_id', $newTid);
            $movePosts = Hooks::fireDB('model.topic.split_posts_move_query', $movePosts);
            $movePosts->save();

            // Apply every subscription to both topics
            DB::table('topic_subscriptions')->rawQuery('INSERT INTO '.ForumEnv::get('DB_PREFIX').'topic_subscriptions (user_id, topic_id) SELECT user_id, '.$newTid.' FROM '.ForumEnv::get('DB_PREFIX').'topic_subscriptions WHERE topic_id=:tid', ['tid' => $tid]);

            // Get last_post, last_post_id, and last_poster from the topic and update it
            $lastOldPostData['select'] = ['id', 'poster', 'posted'];

            $lastOldPostData = DB::table('posts')
                ->selectMany($lastOldPostData['select'])
                ->where('topic_id', $tid)
                ->orderByDesc('id');
            $lastOldPostData = Hooks::fireDB('model.topic.split_posts_last_old_post_data_query', $lastOldPostData);
            $lastOldPostData = $lastOldPostData->findOne();

            // Update the old topic
            $updateOldTopic['insert'] = [
                'last_post' => $lastOldPostData['posted'],
                'last_post_id'  => $lastOldPostData['id'],
                'last_poster'  => $lastOldPostData['poster'],
            ];

            $updateOldTopic = DB::table('topics')
                                ->where('id', $tid)
                                ->findOne()
                                ->set($updateOldTopic['insert'])
                                ->setExpr('num_replies', 'num_replies-'.$numPostsSplitted);
            $updateOldTopic = Hooks::fireDB('model.topic.split_posts_update_old_topic_query', $updateOldTopic);
            $updateOldTopic->save();

            // Get last_post, last_post_id, and last_poster from the new topic and update it
            $lastNewPostData['select'] = ['id', 'poster', 'posted'];

            $lastNewPostData = DB::table('posts')
                                    ->selectMany($lastNewPostData['select'])
                                    ->where('topic_id', $newTid)
                                    ->orderByDesc('id');
            $lastNewPostData = Hooks::fireDB('model.topic.split_posts_last_new_post_query', $lastNewPostData);
            $lastNewPostData = $lastNewPostData->findOne();

            // Update the new topic
            $updateNewTopic['insert'] = [
                'last_post' => $lastNewPostData['posted'],
                'last_post_id'  => $lastNewPostData['id'],
                'last_poster'  => $lastNewPostData['poster'],
            ];

            $updateNewTopic = DB::table('topics')
                ->where('id', $newTid)
                ->findOne()
                ->set($updateNewTopic['insert'])
                ->setExpr('num_replies', $numPostsSplitted-1);
            $updateNewTopic = Hooks::fireDB('model.topic.split_posts_update_new_topic_query', $updateNewTopic);
            $updateNewTopic = $updateNewTopic->save();

            Forum::update($fid);
            Forum::update($moveToForum);

            return Router::redirect(Router::pathFor('Topic', ['id' => $newTid, 'name' => Url::slug($newSubject)]), __('Split posts redirect'));
        }

        $posts = Hooks::fire('model.topic.split_posts', $posts);
        return $posts;
    }

    // Prints the posts
    public function printPosts($topicId, $startFrom, $curTopic, $isAdmmod)
    {
        $postData = [];

        $postData = Hooks::fire('model.topic.print_posts_start', $postData, $topicId, $startFrom, $curTopic, $isAdmmod);

        $postCount = 0; // Keep track of post numbers

        // Retrieve a list of post IDs, LIMIT is (really) expensive so we only fetch the IDs here then later fetch the remaining data
        $result = DB::table('posts')
                    ->select('id')
                    ->where('topic_id', $topicId)
                    ->orderBy('id')
                    ->limit(User::getPref('disp.topics'))
                    ->offset($startFrom);
        $result = Hooks::fireDB('model.topic.print_posts_ids_query', $result);
        $result = $result->findMany();

        $postIds = [];
        foreach ($result as $curPostId) {
            $postIds[] = $curPostId['id'];
        }

        if (empty($postIds)) {
            throw new Error('The post table and topic table seem to be out of sync!', 500);
        }

        // Retrieve the posts (and their respective poster/online status)
        $result['select'] = ['u.email', 'u.title', 'u.url', 'u.location', 'u.signature', 'email_setting' => 'pr.preference_value', 'u.num_posts', 'u.registered', 'u.admin_note', 'p.id','username' => 'p.poster', 'p.poster_id', 'p.poster_ip', 'p.poster_email', 'p.message', 'p.hide_smilies', 'p.posted', 'p.edited', 'p.edited_by', 'g.g_id', 'g.g_user_title', 'is_online' => 'o.user_id'];

        $result = DB::table('posts')
                    ->tableAlias('p')
                    ->selectMany($result['select'])
                    ->innerJoin('users', ['u.id', '=', 'p.poster_id'], 'u')
                    ->innerJoin('groups', ['g.g_id', '=', 'u.group_id'], 'g')
                    ->leftOuterJoin('online', 'o.user_id!=1 AND o.idle=0 AND o.user_id=u.id', 'o')
                    ->leftOuterJoin('preferences', 'pr.user=u.id AND pr.preference_name=\'email.setting\'', 'pr')
                    ->whereIn('p.id', $postIds)
                    ->orderBy('p.id');
        $result = Hooks::fireDB('model.topic.print_posts_query', $result);
        $result = $result->findArray();

        foreach ($result as $curPost) {
            $postCount++;
            $curPost['user_avatar'] = '';
            $curPost['user_info'] = [];
            $curPost['user_contacts'] = [];
            $curPost['post_actions'] = [];
            $curPost['is_online_formatted'] = '';
            $curPost['signature_formatted'] = '';
            $curPost['promote.next_group'] = Prefs::getGroupPreferences($curPost['g_id'], 'promote.next_group');

            // If the poster is a registered user
            if ($curPost['poster_id'] > 1) {
                if (User::can('users.view')) {
                    $curPost['username_formatted'] = '<a href="'.Router::pathFor('userProfile', ['id' => $curPost['poster_id']]).'/">'.Utils::escape($curPost['username']).'</a>';
                } else {
                    $curPost['username_formatted'] = Utils::escape($curPost['username']);
                }

                $curPost['user_title_formatted'] = Utils::getTitle($curPost);

                if (ForumSettings::get('o_censoring') == '1') {
                    $curPost['user_title_formatted'] = Utils::censor($curPost['user_title_formatted']);
                }

                // Format the online indicator
                $curPost['is_online_formatted'] = ($curPost['is_online'] == $curPost['poster_id']) ? '<strong>'.__('Online').'</strong>' : '<span>'.__('Offline').'</span>';

                if (ForumSettings::get('o_avatars') == '1' && User::getPref('show.avatars') != '0') {
                    if (isset($avatarCache[$curPost['poster_id']])) {
                        $curPost['user_avatar'] = $avatarCache[$curPost['poster_id']];
                    } else {
                        $curPost['user_avatar'] = $avatarCache[$curPost['poster_id']] = Utils::generateAvatarMarkup($curPost['poster_id']);
                    }
                }

                // We only show location, register date, post count and the contact links if "Show user info" is enabled
                if (ForumSettings::get('o_show_user_info') == '1') {
                    if ($curPost['location'] != '') {
                        if (ForumSettings::get('o_censoring') == '1') {
                            $curPost['location'] = Utils::censor($curPost['location']);
                        }

                        $curPost['user_info'][] = '<dd><span>'.__('From').' '.Utils::escape($curPost['location']).'</span></dd>';
                    }

                    $curPost['user_info'][] = '<dd><span>'.__('Registered topic').' '.Utils::formatTime($curPost['registered'], true).'</span></dd>';

                    if (ForumSettings::get('o_show_post_count') == '1' || User::isAdminMod()) {
                        $curPost['user_info'][] = '<dd><span>'.__('Posts topic').' '.Utils::forumNumberFormat($curPost['num_posts']).'</span></dd>';
                    }

                    // Now let's deal with the contact links (Email and URL)
                    if (!isset($curPost['email_setting']) || is_null($curPost['email_setting'])) {
                        $curPost['email_setting'] = ForumSettings::get('email.setting');
                    }
                    if ((($curPost['email_setting'] == '0' && !User::get()->is_guest) || User::isAdminMod()) && User::can('email.send')) {
                        $curPost['user_contacts'][] = '<span class="email"><a href="mailto:'.Utils::escape($curPost['email']).'">'.__('Email').'</a></span>';
                    } elseif ($curPost['email_setting'] == '1' && !User::get()->is_guest && User::can('email.send')) {
                        $curPost['user_contacts'][] = '<span class="email"><a href="'.Router::pathFor('email', ['id' => $curPost['poster_id']]).'">'.__('Email').'</a></span>';
                    }

                    if ($curPost['url'] != '') {
                        if (ForumSettings::get('o_censoring') == '1') {
                            $curPost['url'] = Utils::censor($curPost['url']);
                        }

                        $curPost['user_contacts'][] = '<span class="website"><a href="'.Utils::escape($curPost['url']).'" rel="nofollow">'.__('Website').'</a></span>';
                    }
                }

                if (User::isAdmin() || (User::isAdminMod() && User::can('mod.promote_users'))) {
                    if ($curPost['promote.next_group']) {
                        $curPost['user_info'][] = '<dd><span><a href="'.Router::pathFor('profileAction', ['id' => $curPost['poster_id'], 'action' => 'promote', 'pid' => $curPost['id']]).'">'.__('Promote user').'</a></span></dd>';
                    }
                }

                if (User::isAdminMod()) {
                    $curPost['user_info'][] = '<dd><span><a href="'.Router::pathFor('getPostHost', ['pid' => $curPost['id']]).'" title="'.Utils::escape($curPost['poster_ip']).'">'.__('IP address logged').'</a></span></dd>';

                    if ($curPost['admin_note'] != '') {
                        $curPost['user_info'][] = '<dd><span>'.__('Note').' <strong>'.Utils::escape($curPost['admin_note']).'</strong></span></dd>';
                    }
                }
            }
            // If the poster is a guest (or a user that has been deleted)
            else {
                $curPost['username_formatted'] = Utils::escape($curPost['username']);
                $curPost['user_title_formatted'] = Utils::getTitle($curPost);

                if (User::isAdminMod()) {
                    $curPost['user_info'][] = '<dd><span><a href="'.Router::pathFor('getPostHost', ['pid' => $curPost['id']]).'" title="'.Utils::escape($curPost['poster_ip']).'">'.__('IP address logged').'</a></span></dd>';
                }

                if (ForumSettings::get('o_show_user_info') == '1' && $curPost['poster_email'] != '' && !User::get()->is_guest && User::can('email.send')) {
                    $curPost['user_contacts'][] = '<span class="email"><a href="mailto:'.Utils::escape($curPost['poster_email']).'">'.__('Email').'</a></span>';
                }
            }

            // Generation post action array (quote, edit, delete etc.)
            if (!$isAdmmod) {
                if (!User::get()->is_guest) {
                    $curPost['post_actions'][] = '<li class="postreport"><span><a href="'.Router::pathFor('report', ['id' => $curPost['id']]).'">'.__('Report').'</a></span></li>';
                }

                if ($curTopic['closed'] == '0') {
                    if ($curPost['poster_id'] == User::get()->id) {
                        if ((($startFrom + $postCount) == 1 && User::can('topic.delete')) || (($startFrom + $postCount) > 1 && User::can('post.delete'))) {
                            $curPost['post_actions'][] = '<li class="postdelete"><span><a href="'.Router::pathFor('deletePost', ['id' => $curPost['id']]).'">'.__('Delete').'</a></span></li>';
                        }
                        if (User::can('post.edit')) {
                            $curPost['post_actions'][] = '<li class="postedit"><span><a href="'.Router::pathFor('editPost', ['id' => $curPost['id']]).'">'.__('Edit').'</a></span></li>';
                        }
                    }

                    if (($curTopic['post_replies'] == '' && User::can('topic.reply')) || $curTopic['post_replies'] == '1') {
                        $curPost['post_actions'][] = '<li class="postquote"><span><a href="'.Router::pathFor('newQuoteReply', ['tid' => $topicId, 'qid' => $curPost['id']]).'">'.__('Quote').'</a></span></li>';
                    }
                }
            } else {
                $curPost['post_actions'][] = '<li class="postreport"><span><a href="'.Router::pathFor('report', ['id' => $curPost['id']]).'">'.__('Report').'</a></span></li>';
                if (User::isAdmin() || !in_array($curPost['poster_id'], Utils::getAdminIds())) {
                    $curPost['post_actions'][] = '<li class="postdelete"><span><a href="'.Router::pathFor('deletePost', ['id' => $curPost['id']]).'">'.__('Delete').'</a></span></li>';
                    $curPost['post_actions'][] = '<li class="postedit"><span><a href="'.Router::pathFor('editPost', ['id' => $curPost['id']]).'">'.__('Edit').'</a></span></li>';
                }
                $curPost['post_actions'][] = '<li class="postquote"><span><a href="'.Router::pathFor('newQuoteReply', ['tid' => $topicId, 'qid' => $curPost['id']]).'">'.__('Quote').'</a></span></li>';
            }

            // Perform the main parsing of the message (BBCode, smilies, censor words etc)
            $curPost['message'] = Parser::parseMessage($curPost['message'], $curPost['hide_smilies']);

            // Do signature parsing/caching
            if (ForumSettings::get('o_signatures') == '1' && $curPost['signature'] != '' && User::getPref('show.sig') != '0') {
                // if (isset($avatarCache[$curPost['poster_id']])) {
                //     $curPost['signature_formatted'] = $avatarCache[$curPost['poster_id']];
                // } else {
                    $curPost['signature_formatted'] = Parser::parseSignature($curPost['signature']);
                //     $avatarCache[$curPost['poster_id']] = $curPost['signature_formatted'];
                // }
            }
            $curPost = Hooks::fire('model.print_posts.one', $curPost);
            $postData[] = $curPost;
        }

        $postData = Hooks::fire('model.topic.print_posts', $postData);

        return $postData;
    }

    public function moderateDisplayPosts($tid, $startFrom)
    {
        Hooks::fire('model.disp.topics_posts_view_start', $tid, $startFrom);

        $postData = [];

        $postCount = 0; // Keep track of post numbers

        // Retrieve a list of post IDs, LIMIT is (really) expensive so we only fetch the IDs here then later fetch the remaining data
        $findIds = DB::table('posts')->select('id')
            ->where('topic_id', $tid)
            ->orderBy('id')
            ->limit(User::getPref('disp.posts'))
            ->offset($startFrom);
        $findIds = Hooks::fireDB('model.disp.topics_posts_view_find_ids', $findIds);
        $findIds = $findIds->findMany();

        foreach ($findIds as $id) {
            $postIds[] = $id['id'];
        }

        // Retrieve the posts (and their respective poster)
        $result['select'] = ['u.title', 'u.num_posts', 'g.g_id', 'g.g_user_title', 'p.id', 'p.poster', 'p.poster_id', 'p.message', 'p.hide_smilies', 'p.posted', 'p.edited', 'p.edited_by'];

        $result = DB::table('posts')
                    ->tableAlias('p')
                    ->selectMany($result['select'])
                    ->innerJoin('users', ['u.id', '=', 'p.poster_id'], 'u')
                    ->innerJoin('groups', ['g.g_id', '=', 'u.group_id'], 'g')
                    ->whereIn('p.id', $postIds)
                    ->orderBy('p.id');
        $result = Hooks::fireDB('model.disp.topics_posts_view_query', $result);
        $result = $result->findMany();

        foreach ($result as $curPost) {
            $postCount++;

            // If the poster is a registered user
            if ($curPost->posterId > 1) {
                if (User::can('users.view')) {
                    $curPost->posterDisp = '<a href="'.Router::pathFor('userProfile', ['id' => $curPost->posterId]).'">'.Utils::escape($curPost->poster).'</a>';
                } else {
                    $curPost->posterDisp = Utils::escape($curPost->poster);
                }

                // Utils::get_title() requires that an element 'username' be present in the array
                $curPost->username = $curPost->poster;
                $curPost->userTitle = Utils::getTitle($curPost);

                if (ForumSettings::get('o_censoring') == '1') {
                    $curPost->userTitle = Utils::censor($curPost->userTitle);
                }
            }
            // If the poster is a guest (or a user that has been deleted)
            else {
                $curPost->posterDisp = Utils::escape($curPost->poster);
                $curPost->userTitle = __('Guest');
            }

            // Perform the main parsing of the message (BBCode, smilies, censor words etc)
            $curPost->message = Parser::parseMessage($curPost->message, $curPost->hideSmilies);

            $postData[] = $curPost;
        }

        $postData = Hooks::fire('model.disp.topics_posts_view', $postData);

        return $postData;
    }

    public function incrementViews($id)
    {
        if (ForumSettings::get('o_topic_views') == '1') {
            $query = DB::table('topics')
                        ->where('id', $id)
                        ->findOne()
                        ->setExpr('num_views', 'num_views+1');
            $query = Hooks::fire('model.topic.increment_views', $query);
            $query = $query->save();
        }
    }
}
