<?php
// var_dump($messages, $cur_conv);
?>

        <div class="block">
            <?php
                $message_count = 1;
                foreach ($messages as $message) {
            ?>
            <div id="p<?= $message['id'] ?>" class="blockpost<?= ($message_count % 2 == 0) ? ' roweven' : ' rowodd' ?><?= ($message['id'] == $cur_conv['first_post_id']) ? ' firstpost' : ''; ?><?= ($message_count == 1) ? ' blockpost1' : ''; ?>">
                <h2><span class="conr">#<?= ($start_from + $message_count) ?></span> <a href="<?= $feather->urlFor('viewPost', ['pid' => $message['id']]).'#p'.$message['id'] ?>"><?= $feather->utils->format_time($message['sent']) ?></a></h2>
                <div class="box">
                    <div class="inbox">
                        <div class="postbody">
                            <div class="postleft">
                                <dl>
                                    <dt><strong><a href="<?= $feather->urlFor('userProfile', ['id' => $message['poster_id']]) ?>"><span><?= $feather->utils->escape($message['username'])?></span></a></strong></dt>
                                    <dd class="usertitle"><strong><?= $feather->utils->get_title($message) ?></strong></dd>
                                </dl>
                            </div>
                            <div class="postright">
                                <h3><?php if ($message['id'] != $cur_conv['first_post_id']) { _e('Re').' '; } ?>
                                    <?= $feather->utils->escape($cur_conv['subject']) ?>
                                </h3>
                                <div class="postmsg">
                                    <p>
                                        <?= $feather->utils->escape($message['message'])."\n" ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="inbox">
                        <div class="postfoot clearb">
                            <div class="postfootleft">
                                <?php if ($message['poster_id'] > 1) {
                                    echo '<p>'.($message['is_online'] == $message['poster_id']) ? '<strong>'.__('Online').'</strong>' : ('<span>'.__('Offline').'</span>').'</p>';
                                } ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php
                ++$message_count;
            }
            ?>
        </div>
        <div class="clearer"></div>
