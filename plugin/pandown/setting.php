<?php
!defined('DEBUG') AND exit('Access Denied.');

$action = param(3);
if ($method == 'GET') {
    $setting = setting_get('pandown_setting');
    include _include(APP_PATH.'plugin/pandown/setting.htm');
} elseif ($method == 'POST') {
    $op = param('op');
    if ($op == 'save') {
        $setting = setting_get('pandown_setting');
        $setting['clear_qrcode'] = param('clear_qrcode', 0);
        setting_set('pandown_setting', $setting);
        message(0, '设置保存成功');
    } elseif ($op == 'clear') {
        $cache_dir = APP_PATH.'plugin/pandown/cache/';
        $files = glob($cache_dir.'*.png');
        $count = 0;
        if ($files) {
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                    $count++;
                }
            }
        }
        message(0, "已清除 {$count} 个二维码缓存文件");
    }
    message(-1, '操作失败');
}
