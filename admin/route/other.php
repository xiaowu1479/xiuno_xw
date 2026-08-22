<?php

!defined('DEBUG') AND exit('Access Denied.');

$action = param(1, 'cache');

// hook admin_other_start.php

if($action == 'cache') {
	
	// hook admin_other_cache_get_post.php
	
	if($method == 'GET') {
		
		// hook admin_other_cache_get_end.php
		
		$input = array();
		$input['clear_tmp'] = form_checkbox('clear_tmp', 1);
		$input['clear_cache'] = form_checkbox('clear_cache', 1);
		include _include(ADMIN_PATH.'view/htm/other_cache.htm');
		
	} else {
		
		$clear_tmp = param('clear_tmp');
		$clear_cache = param('clear_cache');
		
		$clear_cache AND cache_truncate();
		$clear_cache AND $runtime = NULL;
		
		$clear_tmp AND rmdir_recusive($conf['tmp_path'], 1);
	
		// hook admin_other_cache_post_end.php
		
		message(0, lang('admin_clear_successfully'));
	}
}

if($action == 'update') {
	
	// hook admin_other_update_get_post.php
	
	if($method == 'GET') {
		
		// hook admin_other_update_get_start.php
		
		$force = param('force', 0);
		$upinfo = admin_update_check($force);
		
		// 更新备份列表（回滚用）
		$backup_list = admin_update_backup_list();
		
		$header['title'] = lang('admin_other_update');
		$header['mobile_title'] = lang('admin_other_update');
		
		// hook admin_other_update_get_end.php
		
		include _include(ADMIN_PATH.'view/htm/other_update.htm');
		
	} else {
		
		// hook admin_other_update_post_end.php
		
		message(0, lang('modify_successfully'));
	}
}

if($action == 'update_do') {
	
	// hook admin_other_update_do_get_post.php
	
	admin_update_do();
}

if($action == 'update_rollback') {
	
	// hook admin_other_update_rollback_get_post.php
	
	$dirname = param('dir');
	admin_update_rollback($dirname);
}

if($action == 'update_backup_delete') {
	
	// hook admin_other_update_backup_delete_get_post.php
	
	$dirname = param('dir');
	admin_update_backup_delete($dirname);
}

// hook admin_other_end.php

?>
