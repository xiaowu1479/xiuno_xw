 <?php exit;
    global $postlist_orderby;
	if($postlist_orderby == 'desc'){
		global $thread;
		$postlist = post__find(array('tid'=>$tid), $orderby = array('pid'=>-1), $page, $pagesize);
		$floor = $thread['posts'] - ($page - 1) * $pagesize + 1;
	    if($page == 1){
	    	$r = post__read($thread['firstpid']);
			$postlist += array($thread['firstpid'] =>$r);
		}
		foreach($postlist as &$post) {
			$post['floor'] = $floor--;
			post_format($post, $thread);
	    }
		return $postlist;	    
	}	
?>


