<?php

// hook admin_func_start.php

// 有部分用户
define('XN_ADMIN_BIND_IP', array_value($conf, 'admin_bind_ip'));

function admin_token_check() {
	global $longip, $time, $useragent, $conf;
	$useragent_md5 = md5($useragent);
	
	//$key = md5($longip.$useragent_md5.$conf['auth_key']); // 有些环境是动态 IP
	$key = md5((XN_ADMIN_BIND_IP ? $longip : '').$useragent_md5.xn_key());
	
	// hook admin_token_check_start.php
	
	$admin_token = param('bbs_admin_token');
	if(empty($admin_token)) {
		$_REQUEST[0] = 'index';
		$_REQUEST[1] = 'login';
	} else {
		$s = xn_decrypt($admin_token, $key);
		if(empty($s)) {
			setcookie('bbs_admin_token', '', 0, '', '', '', TRUE);
			//message(-1, lang('admin_token_error'));
			message(-1, lang('admin_token_expiry'));
		}
		list($_ip, $_time) = explode("\t", $s);
		
		// 后台超过 3600 自动退出。
		// Background / more than 3600 automatic withdrawal.
		//if($_ip != $longip || $time - $_time > 3600) {
		if((XN_ADMIN_BIND_IP && $_ip != $longip || !XN_ADMIN_BIND_IP) && $time - $_time > 3600) {
			setcookie('bbs_admin_token', '', 0, '', '', '', TRUE);
			message(-1, lang('admin_token_expiry'));
		}
		
		// 超过半小时，重新发新令牌，防止过期
		// More than half an hour, reset a new token, prevent expired
		if($time - $_time > 1800) {
			admin_token_set();
		}
	}
	// hook admin_token_check_end.php
}

function admin_token_set() {
	global $longip, $time, $useragent, $conf;
	$useragent_md5 = md5($useragent);
	//$key = md5($longip.$useragent_md5.$conf['auth_key']);
	$key = md5((XN_ADMIN_BIND_IP ? $longip : '').$useragent_md5.xn_key());
	
	// hook admin_token_set_start.php
	
	$admin_token = param('bbs_admin_token');
	$s = "$longip	$time";
	
	$admin_token = xn_encrypt($s, $key);
	setcookie('bbs_admin_token', $admin_token, $time + 3600, '',  '', 0, TRUE);
	
	// hook admin_token_set_end.php
}

function admin_token_clean() {
	global $time;
	setcookie('bbs_admin_token', '', $time - 86400, '', '', 0, TRUE);
	
	// hook admin_token_clean_start.php
}

// bootstrap style
function admin_tab_active($arr, $active) {
	// hook admin_tab_active_start.php
	$s = '';
	foreach ($arr as $k=>$v) {
		$s .= '<a role="button" class="btn btn-secondary'.($active == $k ? ' active' : '').'" href="'.$v['url'].'">'.$v['text'].'</a>';
	}
	// hook admin_tab_active_end.php
	return $s;
}

// 检测远程最新版本 (XIUNO XW)
function admin_update_check($force = FALSE) {
	global $conf;
	$key = 'admin_update_check';
	if(!$force) {
		$cache = cache_get($key);
		if($cache !== NULL) return $cache;
	}
	$result = array(
		'error' => 0,
		'latest' => 0,
		'tag' => '',
		'name' => '',
		'body' => '',
		'published_at' => '',
		'html_url' => '',
		'zipball_url' => '',
	);
	$url = empty($conf['update_check_url']) ? 'https://api.github.com/repos/xiaowu1479/xiuno_xw/releases/latest' : $conf['update_check_url'];
	$opts = array(
		'http' => array(
			'method' => 'GET',
			'timeout' => 5,
			'header' => "User-Agent: XIUNO XW-Update-Check\r\nAccept: application/vnd.github+json\r\n",
		),
	);
	$ctx = stream_context_create($opts);
	$s = @file_get_contents($url, FALSE, $ctx);
	$arr = array();
	if($s !== FALSE) {
		$arr = json_decode($s, TRUE);
	}
	// 无 Release 时（404），回退到 tags 接口，至少能拿到版本号
	if(empty($arr['tag_name']) && strpos($url, 'releases/latest') !== FALSE) {
		$url2 = substr($url, 0, strpos($url, '/releases/latest')).'/tags';
		$s2 = @file_get_contents($url2, FALSE, $ctx);
		if($s2 !== FALSE) {
			$arr2 = json_decode($s2, TRUE);
			if(!empty($arr2[0]['name'])) {
				$arr = array('tag_name'=>$arr2[0]['name'], 'name'=>'', 'body'=>'', 'published_at'=>'', 'html_url'=>'https://github.com/xiaowu1479/xiuno_xw/releases', 'zipball_url'=>isset($arr2[0]['zipball_url']) ? $arr2[0]['zipball_url'] : '');
			}
		}
	}
	if(empty($arr['tag_name'])) {
		$result['error'] = 1;
	} else {
			$tag = trim($arr['tag_name'], 'vV ');
			$result['tag'] = $tag;
			$result['name'] = empty($arr['name']) ? '' : $arr['name'];
			$result['body'] = empty($arr['body']) ? '' : $arr['body'];
			$result['published_at'] = empty($arr['published_at']) ? '' : substr($arr['published_at'], 0, 10);
			$result['html_url'] = empty($arr['html_url']) ? '' : $arr['html_url'];
			$result['zipball_url'] = empty($arr['zipball_url']) ? '' : $arr['zipball_url'];
			$result['latest'] = version_compare($tag, $conf['version'], '>') ? 1 : 0;
	}
	cache_set($key, $result, 3600);
	return $result;
}

// hook admin_func_end.php

?>