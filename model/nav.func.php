<?php

// hook model_nav_start.php

// ------------> 最原生的 CURD，无关联其他数据。（Xiuno_xw 新增：自定义导航菜单）

function nav__create($arr) {
	// hook model_nav__create_start.php
	$r = db_create('nav', $arr);
	// hook model_nav__create_end.php
	return $r;
}

function nav__update($id, $arr) {
	// hook model_nav__update_start.php
	$r = db_update('nav', array('id'=>$id), $arr);
	// hook model_nav__update_end.php
	return $r;
}

function nav__read($id) {
	// hook model_nav__read_start.php
	$nav = db_find_one('nav', array('id'=>$id));
	// hook model_nav__read_end.php
	return $nav;
}

function nav__delete($id) {
	// hook model_nav__delete_start.php
	$r = db_delete('nav', array('id'=>$id));
	// hook model_nav__delete_end.php
	return $r;
}

function nav__find($cond = array(), $orderby = array(), $page = 1, $pagesize = 1000) {
	// hook model_nav__find_start.php
	$navlist = db_find('nav', $cond, $orderby, $page, $pagesize, 'id');
	// hook model_nav__find_end.php
	return $navlist;
}

// ------------> 关联 CURD，主要是强相关的数据，比如缓存。

function nav_create($arr) {
	// hook model_nav_create_start.php
	$r = nav__create($arr);
	nav_list_cache_delete();
	// hook model_nav_create_end.php
	return $r;
}

function nav_update($id, $arr) {
	// hook model_nav_update_start.php
	$r = nav__update($id, $arr);
	nav_list_cache_delete();
	// hook model_nav_update_end.php
	return $r;
}

function nav_read($id) {
	// hook model_nav_read_start.php
	$nav = nav__read($id);
	// hook model_nav_read_end.php
	return $nav;
}

function nav_delete($id) {
	// hook model_nav_delete_start.php
	$r = nav__delete($id);
	nav_list_cache_delete();
	// hook model_nav_delete_end.php
	return $r;
}

function nav_find($cond = array(), $orderby = array('rank'=>-1), $page = 1, $pagesize = 1000) {
	// hook model_nav_find_start.php
	$navlist = nav__find($cond, $orderby, $page, $pagesize);
	// hook model_nav_find_end.php
	return $navlist;
}

function nav_maxid() {
	// hook model_nav_maxid_start.php
	$n = db_maxid('nav', 'id');
	// hook model_nav_maxid_end.php
	return $n;
}

// 从缓存中读取导航列表
function nav_list_cache() {
	global $conf, $navlist;
	$navlist = cache_get('navlist');
	
	// hook model_nav_list_cache_start.php
	
	if($navlist === NULL) {
		$navlist = nav_find();
		cache_set('navlist', $navlist, 60);
	}
	// hook model_nav_list_cache_end.php
	return $navlist;
}

// 更新 navlist 缓存
function nav_list_cache_delete() {
	global $conf;
	static $deleted = FALSE;
	if($deleted) return;
	
	// hook model_nav_list_cache_delete_start.php
	
	cache_delete('navlist');
	$deleted = TRUE;
	// hook model_nav_list_cache_delete_end.php
}

// hook model_nav_end.php

?>