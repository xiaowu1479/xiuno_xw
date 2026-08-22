<?php
!defined('DEBUG') AND exit('Access Denied.');
if ($method == 'GET') {
    include _include(APP_PATH . 'plugin/nciaer_autoavatar/setting.htm');
} else {
    cache_delete('nciaer_autoavatar');
    message(0, '提交成功！');
}
