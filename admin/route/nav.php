<?php

!defined('DEBUG') AND exit('Access Denied.');

// XIUNO XW 新增：自定义导航菜单管理

$action = param(1);

// hook admin_nav_start.php

if(empty($action) || $action == 'list') {
	
	// hook admin_nav_list_get_post.php
	
	if($method == 'GET') {
		
		// hook admin_nav_list_get_start.php
		
		$header['title']        = lang('nav_admin');
		$header['mobile_title'] = lang('nav_admin');
	
		$navlist = nav_find(array(), array('rank'=>-1, 'id'=>1));
		$maxid = nav_maxid();
		$forumlist = forum_list_cache();
		
		// hook admin_nav_list_get_end.php
		
		include _include(ADMIN_PATH."view/htm/nav_list.htm");
	
	} elseif($method == 'POST') {
		
		$idarr = param('id', array(0));
		$namearr = param('name', array(''));
		$urlarr = param('url', array(''));
		$targetarr = param('target', array(0));
		$rankarr = param('rank', array(0));
		
		// hook admin_nav_list_post_start.php
		
		$navlist = nav_find();
		
		$arrlist = array();
		foreach($idarr as $k=>$v) {
			$arr = array(
				'name'=>array_value($namearr, $k),
				'url'=>array_value($urlarr, $k),
				'target'=>array_value($targetarr, $k) ? 1 : 0,
				'rank'=>intval(array_value($rankarr, $k))
			);
			
			if(!isset($navlist[$k])) {
				// hook admin_nav_list_add_before.php
				nav_create($arr);
			} else {
				// hook admin_nav_list_update_before.php
				nav_update($k, $arr);
			}
			
			// hook admin_nav_list_post_loop_end.php
		}
		
		// 删除 / delete
		$deletearr = array_diff_key($navlist, $idarr);
		foreach($deletearr as $k=>$v) {
			// hook admin_nav_list_delete_before.php
			nav_delete($k);
			// hook admin_nav_list_delete_end.php
		}
		
		// hook admin_nav_list_post_end.php
		
		message(0, lang('save_successfully'));
	}

}

// hook admin_nav_end.php

?>