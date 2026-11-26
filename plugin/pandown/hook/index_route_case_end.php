<?php exit;
// 插件路由：/?pandown-resolve.htm（异步转存网盘链接）
case 'pandown':
    is_file(APP_PATH.'plugin/pandown/route/resolve.php') AND include APP_PATH.'plugin/pandown/route/resolve.php';
    break;
