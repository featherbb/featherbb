<?



// Move one or more topics
if (!empty($feather->request->post('move_topics') || !empty($feather->request->post('move_topics_to')) {
    if (!empty($feather->request->post('move_topics_to'))) {
        move_topics_to($feather, $tid, $fid);
    }
	
	$topics = !empty($feather->request->post('topics')) ? $feather->request->post('topics') : array();
	if (empty($topics)) {
		message($lang_misc['No topics selected']);
	}

	$topics = implode(',', array_map('intval', array_keys($topics)));

    // Check if there are enough forums to move the topic
    check_move_possible();

    $page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), $lang_misc['Moderate']);
	if (!defined('PUN_ACTIVE_PAGE')) {
		define('PUN_ACTIVE_PAGE', 'moderate');
	}
	require PUN_ROOT.'include/header.php';
	
	$feather->render('header.php', array(
		'lang_common' => $lang_common,
		'page_title' => $page_title,
		'p' => $p,
		'pun_user' => $pun_user,
		'pun_config' => $pun_config,
		'_SERVER'	=>	$_SERVER,
		'page_head'		=>	'',
		'navlinks'		=>	$navlinks,
		'page_info'		=>	$page_info,
		'db'		=>	$db,
		)
	);

    
	$feather->render('moderate/move_topics.php', array(
		'action'	=>	'multi',
		'id'	=>	$id,
		'topics'	=>	$id,
		'lang_misc'	=>	$lang_misc,
		'lang_common'	=>	$lang_common,
		)
	);

	$feather->render('footer.php', array(
		'lang_common' => $lang_common,
		'pun_user' => $pun_user,
		'pun_config' => $pun_config,
		'pun_start' => $pun_start,
		'footer_style' => 'moderate',
		'forum_id' => $id,
		)
	);

	require PUN_ROOT.'include/footer.php';
}

// Merge two or more topics
elseif (!empty($feather->request->post('merge_topics') || !empty($feather->request->post('merge_topics_comply')) {
    if (!empty($feather->request->post('merge_topics_comply'))) {
        merge_topics($feather, $fid);
    }

    $topics = !empty($feather->request->post('topics')) ? $feather->request->post('topics') : array();
    if (count($topics) < 2) {
        message($lang_misc['Not enough topics selected']);
    }

    $page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), $lang_misc['Moderate']);
	if (!defined('PUN_ACTIVE_PAGE')) {
		define('PUN_ACTIVE_PAGE', 'moderate');
	}
	require PUN_ROOT.'include/header.php';
	
	$feather->render('header.php', array(
		'lang_common' => $lang_common,
		'page_title' => $page_title,
		'p' => $p,
		'pun_user' => $pun_user,
		'pun_config' => $pun_config,
		'_SERVER'	=>	$_SERVER,
		'page_head'		=>	'',
		'navlinks'		=>	$navlinks,
		'page_info'		=>	$page_info,
		'db'		=>	$db,
		)
	);
    
	$feather->render('moderate/merge_topics.php', array(
		'id'	=>	$id,
		'topics'	=>	$topics,
		'lang_misc'	=>	$lang_misc,
		'lang_common'	=>	$lang_common,
		)
	);
    
	$feather->render('footer.php', array(
		'lang_common' => $lang_common,
		'pun_user' => $pun_user,
		'pun_config' => $pun_config,
		'pun_start' => $pun_start,
		'footer_style' => 'moderate',
		'forum_id' => $id,
		)
	);

	require PUN_ROOT.'include/footer.php';

}

// Delete one or more topics
elseif (isset($_POST['delete_topics']) || isset($_POST['delete_topics_comply'])) {
    $topics = !empty($feather->request->post('topics')) ? $feather->request->post('topics') : array();
    if (empty($topics)) {
        message($lang_misc['No topics selected']);
    }

    if (isset($_POST['delete_topics_comply'])) {
        delete_topics($topics, $fid);
    }

    $page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), $lang_misc['Moderate']);
	if (!defined('PUN_ACTIVE_PAGE')) {
		define('PUN_ACTIVE_PAGE', 'moderate');
	}
	require PUN_ROOT.'include/header.php';
	
	$feather->render('header.php', array(
		'lang_common' => $lang_common,
		'page_title' => $page_title,
		'p' => $p,
		'pun_user' => $pun_user,
		'pun_config' => $pun_config,
		'_SERVER'	=>	$_SERVER,
		'page_head'		=>	'',
		'navlinks'		=>	$navlinks,
		'page_info'		=>	$page_info,
		'db'		=>	$db,
		)
	);


	$feather->render('moderate/delete_topics.php', array(
		'id'	=>	$id,
		'topics'	=>	$topics,
		'lang_misc'	=>	$lang_misc,
		'lang_common'	=>	$lang_common,
		)
	);

	$feather->render('footer.php', array(
		'lang_common' => $lang_common,
		'pun_user' => $pun_user,
		'pun_config' => $pun_config,
		'pun_start' => $pun_start,
		'footer_style' => 'moderate',
		'forum_id' => $id,
		)
	);

	require PUN_ROOT.'include/footer.php';

}


// Open or close one or more topics
elseif (!empty($feather->request->post('open'))) || !empty($feather->request->post('close')))) {
    $action = (!empty($feather->request->post('open')) ? 0 : 1;

    // There could be an array of topic IDs in $_POST
    if (!empty($feather->request->post('open')) || !empty($feather->request->post('close'))) {
        confirm_referrer('moderate.php');

        $topics = !empty($feather->request->post('topics')) ? @array_map('intval', @array_keys($feather->request->post('topics'))) : array();
        if (empty($topics)) {
            message($lang_misc['No topics selected']);
        }

        $db->query('UPDATE '.$db->prefix.'topics SET closed='.$action.' WHERE id IN('.implode(',', $topics).') AND forum_id='.$fid) or error('Unable to close topics', __FILE__, __LINE__, $db->error());

        $redirect_msg = ($action) ? $lang_misc['Close topics redirect'] : $lang_misc['Open topics redirect'];
        redirect('moderate.php?fid='.$fid, $redirect_msg);
    }
}