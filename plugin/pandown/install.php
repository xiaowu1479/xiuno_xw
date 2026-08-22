<?php
!defined('DEBUG') AND exit('Forbidden');
// 缓存目录
$cache_dir = APP_PATH . 'plugin/pandown/cache/';
if(!is_dir($cache_dir)) {
    mkdir($cache_dir, 0777, TRUE);
}
// 初始化设置
setting_set('pandown_setting', array(
    'qr_size' => 4,
    'error_level' => 'L',
    'clear_qrcode' => 0,
));
?>