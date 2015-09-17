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
            						<dt><strong><?= $message['username'] ?></strong></dt>
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
        <!-- <div id="p3" class="blockpost rowodd firstpost blockpost1">
	<h2><span><span class="conr">#1</span> <a href="/featherbb/post/3/#p3">2015-09-14 10:19:17</a></span></h2>
	<div class="box">
		<div class="inbox">
			<div class="postbody">
				<div class="postleft">
					<dl>
						<dt><strong><a href="/featherbb/user/2/">hooger</a></strong></dt>
						<dd class="usertitle"><strong>Administrator</strong></dd>
						<dd><span>Registered: 2015-09-14</span></dd>
						<dd><span>Posts: 4</span></dd>
						<dd><span><a href="/featherbb/post/get-host/3/" title="::1">IP address logged</a></span></dd>
						<dd class="usercontacts"><span class="email"><a href="mailto:deuh@oij.gt">Email</a></span> | <span class="email"><a href="/featherbb/conversations/send/2/">PM</a></span></dd>
					</dl>
				</div>
				<div class="postright">
					<h3>test 3</h3>
					<div class="postmsg">
						<p>test 3</p>
					</div>
				</div>
			</div>
		</div>
		<div class="inbox">
			<div class="postfoot clearb">
				<div class="postfootleft"><p><strong>Online</strong></p></div>
				<div class="postfootright">
					<ul>
						<li class="postreport"><span><a href="/featherbb/post/report/3/">Report</a></span></li>
						<li class="postdelete"><span><a href="/featherbb/post/delete/3/">Delete</a></span></li>
						<li class="postedit"><span><a href="/featherbb/post/edit/3/">Edit</a></span></li>
						<li class="postquote"><span><a href="/featherbb/post/reply/3//quote/:qid/">Quote</a></span></li>
					</ul>
				</div>
			</div>
		</div>
	</div>
</div> -->
        <div class="clearer"></div>
