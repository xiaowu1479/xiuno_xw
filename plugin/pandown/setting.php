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
        if (!is_array($setting)) $setting = array();
        $setting['clear_qrcode'] = param('clear_qrcode', 0);
        $setting['pc_full'] = param('pc_full', 0);
        // 对接网盘搜索系统（xinyue）：点击按钮时服务端实时转存
        $setting['xinyue_enable'] = param('xinyue_enable', 0) ? 1 : 0;
        $setting['xinyue_url'] = trim((string)param('xinyue_url', '', FALSE));
        $setting['xinyue_key'] = trim((string)param('xinyue_key', '', FALSE));
        $setting['xinyue_timeout'] = intval(param('xinyue_timeout', 30));
        // 回源地址（可选）：域名被 CDN 代理且本机访问 CDN 不通时，把 TCP 连接直连到内网地址
        $setting['xinyue_host'] = trim((string)param('xinyue_host', '', FALSE));
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
        // 顺带清理限流记录
        $rate_files = glob($cache_dir.'rate/*.txt');
        if ($rate_files) {
            foreach ($rate_files as $file) {
                if (is_file($file)) unlink($file);
            }
        }
        message(0, "已清除 {$count} 个二维码缓存文件");
    }
    message(-1, '操作失败');
}
