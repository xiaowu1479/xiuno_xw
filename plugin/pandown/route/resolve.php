<?php
!defined('DEBUG') AND exit('Access Denied.');
!defined('APP_PATH') AND exit('Access Denied.');

// 异步转存接口：/?pandown-resolve.htm
// 前端只传 pid + idx，原网盘链接从帖子正文中取，避免被用来请求任意地址。
// 任何异常（无权查看、限流、未配置、网盘容量不足、cookie/token 失效等）都静默返回原链，不提示用户。

if(!function_exists('pandown_route_json')) {
function pandown_route_json($data) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
}

if(strtolower($_SERVER['REQUEST_METHOD']) != 'post') {
    header('HTTP/1.1 405 Method Not Allowed');
    exit;
}

if(param(1) != 'resolve') {
    http_404();
    exit;
}

include_once APP_PATH.'plugin/pandown/func.php';

$fallback = array('code' => 0, 'resolved' => 0);

$pid = intval(param('pid', 0));
$idx = intval(param('idx', 0));
if($pid <= 0 || $idx < 0) {
    pandown_route_json($fallback);
}

$post = post__read($pid);
// 只处理首帖：网盘按钮只在首帖渲染，接口也应当只对首帖开放
if(empty($post) || empty($post['message_fmt']) || empty($post['isfirst'])) {
    pandown_route_json($fallback);
}

// 权限校验：必须能看这个板块，否则可以用 pid + idx 直接取到受限帖里的原链接
$thread = thread__read($post['tid']);
if(empty($thread)) {
    pandown_route_json($fallback);
}
$cur_gid = isset($gid) ? intval($gid) : 0;
if(!forum_access_user($thread['fid'], $cur_gid, 'allowread')) {
    pandown_route_json($fallback);
}

// 与前台同样的匹配规则：兼容 &quot;，但只对捕获到的 URL 解码，不对整篇解码
if(!preg_match_all('/\[pd\s+url=(?:"|&quot;)(.*?)(?:"|&quot;)\]/iu', $post['message_fmt'], $matches) || !isset($matches[1][$idx])) {
    pandown_route_json($fallback);
}

$original = pandown_extract_url(htmlspecialchars_decode($matches[1][$idx]));

// 拼装后的原链接必须是 http(s)
if(!preg_match('#^https?://#i', $original)) {
    pandown_route_json($fallback);
}

// 只允许白名单网盘域名，否则等于让转存系统去请求任意地址（内网探测 / 攻击跳板）
if(!pandown_url_allowed($original)) {
    pandown_route_json($fallback);
}

$setting = setting_get('pandown_setting');
if(!is_array($setting)) $setting = array();

$xinyue_url = trim((string)(isset($setting['xinyue_url']) ? $setting['xinyue_url'] : ''));
$xinyue_key = trim((string)(isset($setting['xinyue_key']) ? $setting['xinyue_key'] : ''));
$xinyue_on = !empty($setting['xinyue_enable']);
// 回源地址：域名走 CDN 但本机访问 CDN 不通时，把 TCP 连接直连到内网地址（域名/证书不变）
$xinyue_host = trim((string)(isset($setting['xinyue_host']) ? $setting['xinyue_host'] : ''));

if(!$xinyue_on || $xinyue_url === '' || $xinyue_key === '') {
    pandown_route_json($fallback);
}

if(!function_exists('curl_init')) {
    pandown_log('curl 扩展未安装，无法转存');
    pandown_route_json($fallback);
}

// 每 IP 每分钟最多 30 次（正常点击不会受限，仅防恶意刷）
$client_ip = function_exists('ip') ? ip() : (isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '');
if(pandown_rate_limited($client_ip, 30, 60)) {
    pandown_route_json($fallback);
}

// 同一原链接 10 分钟内复用上一次的转存结果：避免反复点击在网盘里生成多份副本，
// 也省掉一次第三方请求。TTL 不宜太长，转存链接失效后自然会重新转存。
$resolved = '';
$pwd = '';
$cache_dir = APP_PATH.'plugin/pandown/cache/resolve/';
$cache_file = $cache_dir.md5($original).'.json';
if(is_file($cache_file) && filemtime($cache_file) > time() - 600) {
    $cached = json_decode((string)file_get_contents($cache_file), TRUE);
    if(!empty($cached['url'])) {
        $resolved = (string)$cached['url'];
        $pwd = isset($cached['pwd']) ? (string)$cached['pwd'] : '';
    }
}

if($resolved === '') {
    $api = rtrim($xinyue_url, '/').'/api/open/transfer';
    $postdata = http_build_query(array(
        'url' => $original,
        'expired_type' => 2,
        'isSave' => 1,
    ));

    $timeout = isset($setting['xinyue_timeout']) ? intval($setting['xinyue_timeout']) : 30;
    $timeout = $timeout >= 5 && $timeout <= 60 ? $timeout : 30;

    $ch = curl_init($api);
    $curl_opts = array(
        CURLOPT_POST => TRUE,
        CURLOPT_POSTFIELDS => $postdata,
        CURLOPT_RETURNTRANSFER => TRUE,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => $timeout,
        CURLOPT_HTTPHEADER => array('X-Api-Key: '.$xinyue_key),
    );

    // 回源直连：只改 TCP 目标地址，Host 头与 TLS SNI 仍是原域名，证书校验照常。
    // 适用于「域名被 CDN 代理、服务器访问该 CDN 超时（errno=28）」的场景。
    if($xinyue_host !== '') {
        $api_host = (string)parse_url($api, PHP_URL_HOST);
        // 系统地址本身写的就是 IP 时无需回源
        if($api_host !== '' && !preg_match('#^\d{1,3}(\.\d{1,3}){3}$#', $api_host)) {
            $api_port = (int)parse_url($api, PHP_URL_PORT);
            if($api_port <= 0) {
                $api_port = strtolower((string)parse_url($api, PHP_URL_SCHEME)) === 'https' ? 443 : 80;
            }
            $curl_opts[CURLOPT_RESOLVE] = array($api_host.':'.$api_port.':'.$xinyue_host);
        }
    }

    curl_setopt_array($ch, $curl_opts);
    $resp = curl_exec($ch);
    $errno = curl_errno($ch);
    $errmsg = curl_error($ch);
    curl_close($ch);

    if($errno || !$resp) {
        pandown_log('转存请求失败 errno='.$errno.' '.$errmsg.' api='.$api.($xinyue_host !== '' ? ' via='.$xinyue_host : '').' url='.$original);
        pandown_route_json($fallback);
    }

    $json = json_decode($resp, TRUE);
    if(empty($json) || !isset($json['code']) || (int)$json['code'] !== 200 || empty($json['data']['share_url'])) {
        pandown_log('转存返回异常 resp='.substr((string)$resp, 0, 200).' url='.$original);
        pandown_route_json($fallback);
    }

    $resolved = (string)$json['data']['share_url'];
    $pwd = isset($json['data']['code']) ? trim((string)$json['data']['code']) : '';

    if(!is_dir($cache_dir)) @mkdir($cache_dir, 0777, TRUE);
    @file_put_contents($cache_file, json_encode(array('url' => $resolved, 'pwd' => $pwd)), LOCK_EX);
}

// 有提取码时拼进链接，扫码后可直达
if($pwd !== '' && strpos($resolved, 'pwd=') === FALSE) {
    $resolved .= (strpos($resolved, '?') === FALSE ? '?' : '&').'pwd='.$pwd;
}

$qr_url = pandown_qr_url($resolved, 8);
if($qr_url === '') {
    pandown_log('二维码生成失败 url='.$resolved);
    pandown_route_json($fallback);
}

pandown_route_json(array(
    'code' => 0,
    'resolved' => 1,
    'url' => $resolved,
    'qr' => $qr_url,
));
