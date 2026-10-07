<?php exit;
case 'iqismart_localimg': 
	// 图片本地化路由（XIUNO XW 修正）
	// 原写法把目录名硬编码成 till_thread_localImage（大写 I），而磁盘上是 till_thread_locallmage，
	// 在区分大小写的系统/卷上 include 会直接失败 → 访问该路由只返回空白页（本地化功能无反应）。
	// 这里先探测真实目录名，两种写法都能命中。
	$_xw_localimg_dir = is_dir(APP_PATH.'plugin/till_thread_localImage') ? 'till_thread_localImage' : 'till_thread_locallmage';
	include _include(APP_PATH.'plugin/'.$_xw_localimg_dir.'/route/localimg.php');
	break;
