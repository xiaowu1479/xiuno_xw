<?php
if (isset($light_config['disc_page']) && $light_config['disc_page'] == 1) {
$header['title'] = $light_config['disc_page_title'].'-'.$conf['sitename'];
$header['mobile_title'] = $light_config['disc_page_title'];
include _include(APP_PATH.'plugin/zaesky_theme_light/view/htm/discovery.htm');
}else{
  message(0, jump('该功能已关闭', url('index')));
}
?>