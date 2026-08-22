<?php
!defined('DEBUG') AND exit('Access Denied.');
include _include(APP_PATH.'plugin/sg_sign/model/sg_sign.func.php');
$active = param(1);
$active == 'list' ? $active = 'list' : $active = 'default';
$kv = kv_get('sg_sign');
switch ($kv['sign9']) {
case 'credits': 	$Unit = lang('sg_sign1'); 	break;
case 'golds': 	$Unit = lang('sg_sign2'); 	break;
case 'rmbs': 	$Unit = lang('sg_sign3'); 	break;
}
$sg_sign_set = sg_sign_read('sg_sign_set', 'id', 1);
$userinfo = sg_sign_read('sg_sign', 'uid', $uid);
$today = strtotime('today');
$yesterday = strtotime('yesterday');
$t = $time - $sg_sign_set['time'];
if($t > 86400){
sg_sign_update('sg_sign_set', 'id', 1, array('sg_sign_top'=>'','sg_sign_one'=>'','sg_sign'=>0,'time'=>$today));
$sg_sign_set['sg_sign_top'] = '';
$sg_sign_set['sg_sign_one'] = '';
$sg_sign_set['sg_sign'] = 0;
}
	if($method == 'GET') {
		if($active == 'list') {
		$kv['sign11'] != 1 AND message(0, lang('sg_check in list').lang('close'));
		$pagesize = 10;
		$page = param(2, 1);
		$pagination = pagination(url("sg_sign-list-{page}"), $sg_sign_set['sg_signnum'], $page, $pagesize);
		//if($gid==1){ 仅管理员查看所有签到列表
		$sg_signlist = sg_sign_find('sg_sign', 'uid', array(), array('stime'=>-1), $page, $pagesize);
		//}else{ 仅自己能看自己的签到列表
		//$sg_signlist = sg_sign_find('sg_sign', 'uid', array('uid'=>$uid), array('stime'=>-1), $page, $pagesize);
		//}
		}else{
		$uidarr = explode(',',$sg_sign_set['sg_sign_top']);
		$sg_signlist = sg_sign_find('sg_sign', 'uid', array('uid'=>$uidarr), array('id'=>1), 1, 10);
		}
		empty($uid) AND message(0, '<a href="'.url('user-login').'">'.lang('sg_please login to check in').'</a>');
		include _include(APP_PATH.'plugin/sg_sign/htm/sg_sign.htm');
	} elseif($method == 'POST') {
		empty($uid) AND message(0, lang('sg_please login to check in'));
		$kv = kv_get('sg_sign');
		$Credit = $kv['sign1'];
		$number = $sg_sign_set['sg_sign'];
		$number += 1;
		$username = $sg_sign_set['sg_sign_one'];
		$sg_sign_top = $sg_sign_set['sg_sign_top'];
		$sg_signnum =$sg_sign_set['sg_signnum'];
		$times =$sg_sign_set['time'];
		if($number == 1){
			$Credit += $kv['sign5'];
		}else if($number >= 2 && $number <= 5) {
			$Credit += $kv['sign6'];
		}
		if($userinfo){
			if($userinfo['stime'] > $today){
				message(-1, lang('sg_checked today'));
			}else{
				if($userinfo['stime'] > $yesterday && $userinfo['stime'] < $today){
					if($userinfo['keepdays'] == 2){
						$Credit += $kv['sign2'];
						$message = lang('sg_bonus for checking in for 3 days').$kv['sign2'].$Unit.'，';
					}else if($userinfo['keepdays'] == 6){
						$Credit += $kv['sign3'];
						$message = lang('sg_bonus for checking in for 7 days').$kv['sign3'].$Unit.'，';
					}else if($userinfo['keepdays'] == 14) {
						$Credit += $kv['sign4'];
						$message = lang('sg_bonus for checking in for 15 days').$kv['sign4'].$Unit.'，';
					}else if($userinfo['keepdays'] >= 14){
						$Credit += rand($kv['sign7'], $kv['sign8']);
						$message = lang('sg_bonus for checking in for more than 15 days').rand($kv['sign7'], $kv['sign8']).$Unit.'，';
					}else{
						$message = '';
					}
					$keepdays = $userinfo['keepdays']+1;
				}else{
					$message = '';
					$keepdays = 1;
				}
				sg_sign_update('sg_sign', 'uid', $uid, array('id'=>$number,'stime'=>time(),'counts+'=>1,'credits+'=>$Credit,'todaycredits'=>$Credit,'keepdays'=>$keepdays ));
				$message = lang('sg_check in successfully', array('number'=>$number, 'message'=>$message, 'Credit'=>$Credit.$Unit));
			}
		}else{
			sg_sign_create('sg_sign', array('id'=>$number, 'uid'=>$uid, 'stime'=>time(), 'counts'=>1, 'credits'=>$Credit, 'todaycredits'=>$Credit, 'keepdays'=>1, 'user'=>$user['username'] ));
			$sg_signnum =$sg_sign_set['sg_signnum']+1;
			$message = lang('sg_check in successfully', array('number'=>$number, 'message'=>'', 'Credit'=>$Credit.$Unit));
		}
		if($number == 1){
			$username = $user['username'];
			$sg_sign_top = $uid;
			$times = $today;
		}else if($number >= 2 && $number <= 10){
			$sg_sign_top = $sg_sign_set['sg_sign_top'].','.$uid;
		}
		$setarr = array('sg_sign'=>$sg_sign_set['sg_sign']+1,'sg_signnum'=>$sg_signnum,'sg_sign_one'=>$username,'sg_sign_top'=>$sg_sign_top,'time'=>$times);
		sg_sign_update('sg_sign_set', 'id', 1, $setarr);
		sg_sign_update('user', 'uid', $uid, array($kv['sign9'].'+'=>$Credit ));
		message(0, $message);
	}
?>