<?php

/**
 * Copyright (C) 2015-2016 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

use FeatherBB\Core\Url;
use FeatherBB\Core\Utils;

// Make sure no one attempts to run this script "directly"
if (!isset($feather)) {
    exit;
}

Container::get('hooks')->fire('view.topic.start');
?>

<div class="linkst">
    <div class="inbox crumbsplus">
        <ul class="crumbs">
            <li><a href="<?= Url::base() ?>/"><?= __('Index') ?></a></li>
            <li><span>»&#160;</span><a href="<?= Router::pathFor('Forum', ['id' => $cur_topic['forum_id'], 'name' => $url_forum]) ?>"><?= Utils::escape($cur_topic['forum_name']) ?></a></li>
            <li><span>»&#160;</span><strong><a href="<?= Router::pathFor('Topic', ['id' => $id, 'name' => $url_topic]) ?>"><?= Utils::escape($cur_topic['subject']) ?></a></strong></li>
        </ul>
        <div class="pagepost">
            <p class="pagelink conl"><?= $paging_links ?></p>
<?= $post_link ?>
        </div>
        <div class="clearer"></div>
    </div>
</div>

<?php
$post_count = 1;
foreach ($post_data as $post) {
    ?>
<div id="p<?= $post['id'] ?>" class="blockpost<?= ($post_count % 2 == 0) ? ' roweven' : ' rowodd' ?><?php if ($post['id'] == $cur_topic['first_post_id']) {
    echo ' firstpost';
}
    ?><?php if ($post_count == 1) {
    echo ' blockpost1';
}
    ?>">
    <h2><span><span class="conr">#<?= ($start_from + $post_count) ?></span> <a href="<?= Router::pathFor('viewPost', ['id' => $id, 'name' => $url_topic, 'pid' => $post['id']]).'#p'.$post['id'] ?>"><?= Utils::format_time($post['posted']) ?></a></span></h2>
    <div class="box">
        <div class="inbox">
            <div class="postbody">
                <div class="postleft">
                    <dl>
                        <dt><strong><?= $post['username_formatted'] ?></strong></dt>
                        <dd class="usertitle"><strong><?= $post['user_title_formatted'] ?></strong></dd>
<?php if ($post['user_avatar'] != '') {
    echo "\t\t\t\t\t\t".'<dd class="postavatar">'.$post['user_avatar'].'</dd>'."\n";
}
    ?>
<?php if (count($post['user_info'])) {
    echo "\t\t\t\t\t\t".implode("\n\t\t\t\t\t\t", $post['user_info'])."\n";
}
    ?>
<?php if (count($post['user_contacts'])) {
    echo "\t\t\t\t\t\t".'<dd class="usercontacts">'.implode(' | ', $post['user_contacts']).'</dd>'."\n";
}
    ?>
                    </dl>
                </div>
                <div class="postright">
                    <h3><?php if ($post['id'] != $cur_topic['first_post_id']) {
                            _e('Re');
                            echo ' ';
}
    ?><?= Utils::escape($cur_topic['subject']) ?></h3>
                    <div class="postmsg">
                        <?= $post['message']."\n" ?>
<?php if ($post['edited'] != '') {
    echo "\t\t\t\t\t\t".'<p class="postedit"><em>'.__('Last edit').' '.Utils::escape($post['edited_by']).' ('.Utils::format_time($post['edited']).')</em></p>'."\n";
}
    ?>
                    </div>
<?php if ($post['signature_formatted'] != '') {
    echo "\t\t\t\t\t".'<div class="postsignature postmsg"><hr />'.$post['signature_formatted'].'</div>'."\n";
}
    ?>
                </div>
            </div>
        </div>
        <div class="inbox">
            <div class="postfoot clearb">
                <div class="postfootleft"><?php if ($post['poster_id'] > 1) {
    echo '<p>'.$post['is_online_formatted'].'</p>';
}
    ?></div>
<?php if (count($post['post_actions'])) {
    echo "\t\t\t\t".'<div class="postfootright">'."\n\t\t\t\t\t".'<ul>'."\n\t\t\t\t\t\t".implode("\n\t\t\t\t\t\t", $post['post_actions'])."\n\t\t\t\t\t".'</ul>'."\n\t\t\t\t".'</div>'."\n";
}
    ?>
            </div>
        </div>
    </div>
</div>
<?php
    ++$post_count;
}
?>

<div class="postlinksb">
    <div class="inbox crumbsplus">
        <div class="pagepost">
            <p class="pagelink conl"><?= $paging_links ?></p>
<?= $post_link ?>
<?php
if (isset($active_page) && $active_page == 'Topic' && $is_admmod) {
    if (isset($pid)) {
        $parameter = $pid;
    } elseif (isset($page_number) && $page_number != 1) {
        $parameter = $page_number;
    } else {
        $parameter = '';
    }
    echo "\t\t\t".'<div id="modcontrols" class="inbox">'."\n";
    echo "\t\t\t\t".'<dl>'."\n";
    echo "\t\t\t\t\t".'<dt><strong>'.__('Mod controls').'</strong></dt>'."\n";
    echo "\t\t\t\t\t".'<dd><span><a href="'.Router::pathFor('moderateTopic', ['id' => $tid, 'name' => Url::url_friendly($cur_topic['subject']), 'fid' => $fid, 'page' => $page_number]).'">'.__('Moderate topic').'</a></span></dd>'."\n";
    echo "\t\t\t\t\t".'<dd><span><a href="'.Router::pathFor('moveTopic', ['id' => $tid, 'name' => Url::url_friendly($cur_topic['subject']), 'fid' => $fid]).'">'.__('Move topic').'</a></span></dd>'."\n";

    if ($cur_topic['closed'] == '1') {
        echo "\t\t\t\t\t".'<dd><span><a href="'.Router::pathFor('openTopic', ['id' => $tid, 'name' => Url::url_friendly($cur_topic['subject'])]).'">'.__('Open topic').'</a></span></dd>'."\n";
    } else {
        echo "\t\t\t\t\t".'<dd><span><a href="'.Router::pathFor('closeTopic', ['id' => $tid, 'name' => Url::url_friendly($cur_topic['subject'])]).'">'.__('Close topic').'</a></span></dd>'."\n";
    }

    if ($cur_topic['sticky'] == '1') {
        echo "\t\t\t\t\t".'<dd><span><a href="'.Router::pathFor('unstickTopic', ['id' => $tid, 'name' => Url::url_friendly($cur_topic['subject'])]).'">'.__('Unstick topic').'</a></span></dd>'."\n";
    } else {
        echo "\t\t\t\t\t".'<dd><span><a href="'.Router::pathFor('stickTopic', ['id' => $tid, 'name' => Url::url_friendly($cur_topic['subject'])]).'">'.__('Stick topic').'</a></span></dd>'."\n";
    }

    echo "\t\t\t\t".'</dl>'."\n";
    echo "\t\t\t".'</div>'."\n";
}
Container::get('hooks')->fire('view.topic.mod.actions');
?>
        </div>
        <ul class="crumbs">
            <li><a href="<?= Url::base() ?>/"><?= __('Index') ?></a></li>
            <li><span>»&#160;</span><a href="<?= Router::pathFor('Forum', ['id' => $cur_topic['forum_id'], 'name' => $url_forum]) ?>"><?= Utils::escape($cur_topic['forum_name']) ?></a></li>
            <li><span>»&#160;</span><strong><a href="<?= Router::pathFor('Topic', ['id' => $id, 'name' => $url_topic]) ?>"><?= Utils::escape($cur_topic['subject']) ?></a></strong></li>
        </ul>
<?= $subscraction ?>
        <div class="clearer"></div>
    </div>
</div>

<?php

// Display quick post if enabled
if ($quickpost) {
    $cur_index = 1;

    ?>
<div id="quickpost" class="blockform">
    <h2><span><?= __('Quick post') ?></span></h2>
    <div class="box">
        <form id="quickpostform" method="post" action="<?= Router::pathFor('newReply', ['tid' => $id]) ?>">
            <input type="hidden" name="csrf_name" value="<?= $csrf_name; ?>"><input type="hidden" name="csrf_value" value="<?= $csrf_value; ?>">
            <div class="inform">
                <fieldset>
                    <legend><?= __('Write message legend') ?></legend>
                    <div class="infldset txtarea">
                        <input type="hidden" name="form_sent" value="1" />
                        <input type="hidden" name="pid" value="<?= Utils::escape($pid) ?>" />
                        <input type="hidden" name="page" value="<?= Utils::escape($page_number) ?>" />
<?php if (ForumSettings::get('o_topic_subscriptions') == '1' && (User::getPref('auto_notify') == '1' || $cur_topic['is_subscribed'])): ?>                        <input type="hidden" name="subscribe" value="1" />
<?php endif;

    if (User::get()->is_guest) {
        $email_label = (ForumSettings::get('p_force_guest_email') == '1') ? '<strong>'.__('Email').' <span>'.__('Required').'</span></strong>' : __('Email');
        $email_form_name = (ForumSettings::get('p_force_guest_email') == '1') ? 'req_email' : 'email';
        $required = (ForumSettings::get('p_force_guest_email') == '1') ? ' required="required"' : '';
        ?>
                        <label class="conl required"><strong><?= __('Guest name') ?> <span><?= __('Required') ?></span></strong><br /><input type="text" name="req_username" size="25" maxlength="25" tabindex="<?= $cur_index++ ?>" required="required" /><br /></label>
                        <label class="conl<?= (ForumSettings::get('p_force_guest_email') == '1') ? ' required' : '' ?>"><?= $email_label ?><br /><input type="text" name="<?= $email_form_name ?>" size="50" maxlength="80" tabindex="<?= $cur_index++ ?>" <?= $required; ?> /><br /></label>
                        <div class="clearer"></div>
<?php

    echo "\t\t\t\t\t\t".'<label class="required"><strong>'.__('Message').' <span>'.__('Required').'</span></strong><br />';
    } else {
        echo "\t\t\t\t\t\t".'<label>';
    }

    ?>
                        <textarea name="req_message" id="req_message" rows="7" cols="75" tabindex="<?= $cur_index++ ?>" required="required"></textarea></label>
                        <ul class="bblinks">
                            <li><span><a href="<?= Router::pathFor('help').'#bbcode' ?>" onclick="window.open(this.href); return false;"><?= __('BBCode') ?></a> <?= (ForumSettings::get('p_message_bbcode') == '1') ? __('on') : __('off');?></span></li>
                            <li><span><a href="<?= Router::pathFor('help').'#url' ?>" onclick="window.open(this.href); return false;"><?= __('url tag') ?></a> <?= (ForumSettings::get('p_message_bbcode') == '1' && User::can('post.links')) ? __('on') : __('off');?></span></li>
                            <li><span><a href="<?= Router::pathFor('help').'#img' ?>" onclick="window.open(this.href); return false;"><?= __('img tag') ?></a> <?= (ForumSettings::get('p_message_bbcode') == '1' && ForumSettings::get('p_message_img_tag') == '1') ? __('on') : __('off');?></span></li>
                            <li><span><a href="<?= Router::pathFor('help').'#smilies' ?>" onclick="window.open(this.href); return false;"><?= __('Smilies') ?></a> <?= (ForumSettings::get('show.smilies') == '1') ? __('on') : __('off');?></span></li>
                        </ul>
                    </div>
                </fieldset>
            </div>
            <?php if (User::get()->is_guest) : ?>
            <div class="inform">
                <fieldset>
                    <legend><?= __('Robot title') ?></legend>
                    <div class="infldset">
                        <p><?= __('Robot info')    ?></p>
                        <label class="required"><strong><?php
                             $question = array_keys($lang_antispam_questions);
    $qencoded = md5($question[$index_questions]);
    echo sprintf(__('Robot question'), $question[$index_questions]);
    ?>
                             <span><?= __('Required') ?></span></strong>
                             <br />
                             <input name="captcha" id="captcha" type="text" size="10" maxlength="30" required="required" /><input name="captcha_q" value="<?= $qencoded ?>" type="hidden" /><br />
                        </label>
                    </div>
                </fieldset>
            </div>
            <?php endif;
    ?>
            <p class="buttons"><input type="submit" name="submit" tabindex="<?= $cur_index++ ?>" value="<?= __('Submit') ?>" accesskey="s" /> <input type="submit" name="preview" value="<?= __('Preview') ?>" tabindex="<?= $cur_index++ ?>" accesskey="p" /></p>
        </form>
    </div>
</div>
<?php
}

Container::get('hooks')->fire('view.topic.end');
