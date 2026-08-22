<?php 
!defined('DEBUG') AND exit('Access Denied.');
$action = param(3);
if(empty($action)){
	if($method == 'GET'){//设置页面
		include _include(APP_PATH.'plugin/tt_stamp/setting.htm');

	}

}


?>