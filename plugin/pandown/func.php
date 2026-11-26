<?php
!defined('DEBUG') AND exit('Access Denied.');

// 网盘链接解析工具函数（前台展示与异步转存共用）

if(!function_exists('pandown_extract_url')) {
function pandown_extract_url($text) {
    $url_pattern = '#https?://[^\s<>"\'\x{4e00}-\x{9fa5}]+#u';
    if(preg_match($url_pattern, $text, $matches)) {
        return $matches[0];
    }
    $patterns = array(
        '#pan\.baidu\.com/s/[a-zA-Z0-9\-]+#i',
        '#yun\.baidu\.com/s/[a-zA-Z0-9\-]+#i',
        '#pan\.quark\.cn/s/[a-zA-Z0-9]+#i',
        '#kuake\.com/s/[a-zA-Z0-9]+#i',
        '#drive\.uc\.cn/s/[a-zA-Z0-9]+#i',
        '#uc\.cn/s/[a-zA-Z0-9]+#i',
        '#pan\.xunlei\.com/s/[a-zA-Z0-9_\-]+#i',
        '#xunlei\.com/s/[a-zA-Z0-9_\-]+#i',
        '#aliyundrive\.com/s/[a-zA-Z0-9]+#i',
        '#aliyundrive\.net/s/[a-zA-Z0-9]+#i',
        '#guangyapan\.com/s/[a-zA-Z0-9_]+#i',
    );
    foreach($patterns as $pattern) {
        if(preg_match($pattern, $text, $matches)) {
            $url = $matches[0];
            if(!preg_match('#^https?://#i', $url)) {
                $url = 'https://'.$url;
            }
            return $url;
        }
    }
    return $text;
}
}

if(!function_exists('pandown_detect_type')) {
function pandown_detect_type($url) {
    $url_lower = strtolower($url);
    $types = array(
        '百度网盘' => array('pan.baidu.com', 'yun.baidu.com'),
        '夸克网盘' => array('pan.quark.cn', 'kuake.com'),
        'UC网盘'   => array('drive.uc.cn', 'uc.cn', 'www.uc.cn'),
        '迅雷网盘' => array('pan.xunlei.com', 'xunlei.com'),
        '阿里云盘' => array('aliyundrive.com', 'aliyundrive.net'),
        '光鸭网盘' => array('guangyapan.com'),
    );
    foreach($types as $name => $domains) {
        foreach($domains as $domain) {
            if(strpos($url_lower, $domain) !== false) {
                return $name;
            }
        }
    }
    return '网盘';
}
}

// 允许转存的网盘域名（白名单）。异步转存只在命中白名单时才把链接交给网盘系统，
// 避免帖子正文里塞任意 URL 让转存服务去请求内网地址。
if(!function_exists('pandown_allowed_domains')) {
function pandown_allowed_domains() {
    return array(
        'pan.baidu.com', 'yun.baidu.com', 'eyun.baidu.com',
        'pan.quark.cn', 'kuake.com',
        'drive.uc.cn', 'uc.cn',
        'pan.xunlei.com', 'xunlei.com',
        'aliyundrive.com', 'aliyundrive.net', 'alipan.com',
        'guangyapan.com',
    );
}
}

if(!function_exists('pandown_url_allowed')) {
function pandown_url_allowed($url) {
    $host = parse_url((string)$url, PHP_URL_HOST);
    if(empty($host)) return false;
    $host = strtolower($host);
    foreach(pandown_allowed_domains() as $domain) {
        // 精确匹配或子域名匹配，避免 evil-pan.baidu.com.attacker.com 这类后缀绕过
        if($host === $domain || substr($host, -strlen($domain) - 1) === '.'.$domain) {
            return true;
        }
    }
    return false;
}
}

// 失败日志（静默给用户看，但要能事后排查：转存超时 / 密钥错 / 网盘容量不足等）
if(!function_exists('pandown_log')) {
function pandown_log($msg) {
    $file = APP_PATH.'log/pandown_resolve.log';
    if(is_file($file) && filesize($file) > 1048576) @unlink($file); // 超过 1M 直接重建，避免无限增长
    @file_put_contents($file, date('Y-m-d H:i:s').' '.$msg."\n", FILE_APPEND | LOCK_EX);
}
}

// 生成二维码并返回相对路径（按 URL + 倍率缓存）
if(!function_exists('pandown_qr_url')) {
function pandown_qr_url($url, $qr_scale = 8) {
    $qrlib = APP_PATH.'plugin/pandown/includes/phpqrcode/qrlib.php';
    if(!is_file($qrlib)) return '';
    include_once $qrlib;

    $cache_dir = APP_PATH.'plugin/pandown/cache/';
    if(!is_dir($cache_dir)) {
        @mkdir($cache_dir, 0777, TRUE);
    }

    // 顺手回收 7 天前的旧图（转存会产生新链接、新二维码，不清理会只增不减）。
    // 用标记文件控制频率，最多每小时扫一次目录。
    $gc_mark = $cache_dir.'.gc';
    if(!is_file($gc_mark) || filemtime($gc_mark) < time() - 3600) {
        @touch($gc_mark);
        $expire = time() - 7 * 86400;
        foreach((array)@glob($cache_dir.'*.png') as $old) {
            if(@filemtime($old) < $expire) @unlink($old);
        }
    }

    $hash = md5($url.'|qr'.$qr_scale);
    $cache_file = $cache_dir.$hash.'.png';
    if(!is_file($cache_file)) {
        QRcode::png($url, $cache_file, QR_ECLEVEL_L, $qr_scale);
    }
    return 'plugin/pandown/cache/'.$hash.'.png';
}
}

// 每 IP 限流：$window 秒内最多 $max 次，返回 true 表示本次被限制
if(!function_exists('pandown_rate_limited')) {
function pandown_rate_limited($ip, $max = 30, $window = 60) {
    $dir = APP_PATH.'plugin/pandown/cache/rate/';
    if(!is_dir($dir)) {
        @mkdir($dir, 0777, TRUE);
    }
    $file = $dir.md5((string)$ip).'.txt';
    $fp = @fopen($file, 'c+');
    if(!$fp) return false;

    $limited = false;
    if(flock($fp, LOCK_EX)) {
        $now = time();
        $raw = trim((string)stream_get_contents($fp));
        $hits = array();
        if($raw !== '') {
            foreach(explode(' ', $raw) as $t) {
                $t = (int)$t;
                if($t > $now - $window) {
                    $hits[] = $t;
                }
            }
        }
        if(count($hits) >= $max) {
            $limited = true;
        } else {
            $hits[] = $now;
            ftruncate($fp, 0);
            rewind($fp);
            fwrite($fp, implode(' ', $hits));
            fflush($fp);
        }
        flock($fp, LOCK_UN);
    }
    fclose($fp);
    return $limited;
}
}
