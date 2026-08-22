<?php exit;

$medal_check_menu = array(
		'medalcheck' => array(
		'url'=>url('medal_check'), 
		'text'=>lang('medal_check'), 
		'icon'=>'icon-check', 
		'tab'=> array(
		    'check'=>array('url'=>url('medal_check'), 'text'=>'审核用户'.lang('medal').'申请'),
		 )
	));
$menu += $medal_check_menu;

?>