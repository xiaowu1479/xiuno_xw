// 获取磁盘剩余空间
$freeSpace = function_exists('disk_free_space') ? disk_free_space(APP_PATH) : false;
// 获取磁盘总空间
$totalSpace = function_exists('disk_total_space') ? disk_total_space(APP_PATH) : false;

// 初始化已使用空间百分比变量
$usedPercentage = 0;

// 检查是否成功获取到空间信息，并且总空间不为零
if ($freeSpace!== false && $totalSpace!== false && $totalSpace > 0) {
    // 计算已使用空间
    $usedSpace = $totalSpace - $freeSpace;
    // 计算已使用空间百分比
    $usedPercentage = ($usedSpace / $totalSpace) * 100;
}

define('SF_ROOT', APP_PATH.'plugin/zaesky_theme_light/model/');
include(SF_ROOT . 'AuthInfo.php');
include(SF_ROOT . 'Authorization.php');

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if($action == 'checkUpdate') {
    if($method == 'GET') {
        $result = Authorization::updateMain('check');
        message(0, json_encode($result));
    }
}else if($action == 'update') {
    if($method == 'GET') {
        $result = Authorization::updateMain('update');
        message(0, $result['msg']);
    }
}else if($action == 'checkAuth') {
    if($method == 'GET') {
        $result = Authorization::checkIsAuth();
        message(0, json_encode($result));
    }
}
