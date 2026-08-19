<?php exit;
if($page != 1) return;

if(empty($first) || empty($first['message_fmt'])) return;

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
        '#pan\.xunlei\.cn/s/[a-zA-Z0-9]+#i',
        '#xunlei\.com/s/[a-zA-Z0-9]+#i',
        '#aliyundrive\.com/s/[a-zA-Z0-9]+#i',
        '#aliyundrive\.net/s/[a-zA-Z0-9]+#i',
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

function pandown_detect_type($url) {
    $url_lower = strtolower($url);
    $types = array(
        '百度网盘' => array('pan.baidu.com', 'yun.baidu.com'),
        '夸克网盘' => array('pan.quark.cn', 'kuake.com'),
        'UC网盘'   => array('drive.uc.cn', 'uc.cn', 'www.uc.cn'),
        '迅雷网盘' => array('pan.xunlei.cn', 'xunlei.com'),
        '阿里云盘' => array('aliyundrive.com', 'aliyundrive.net'),
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

$first['message_fmt'] = htmlspecialchars_decode($first['message_fmt']);

$preg_pd = preg_match_all('/\[pd\s+url="(.*?)"\]/i', $first['message_fmt'], $matches);
if(!empty($preg_pd) && !empty($matches[0])) {
    $GLOBALS['has_pandown'] = true;

    $qrlib = APP_PATH.'plugin/pandown/includes/phpqrcode/qrlib.php';
    if(!is_file($qrlib)) return;
    include_once $qrlib;

    $cache_dir = APP_PATH.'plugin/pandown/cache/';
    if(!is_dir($cache_dir)) {
        mkdir($cache_dir, 0777, TRUE);
    }

    for($i = 0; $i < count($matches[0]); $i++) {
        $tag = $matches[0][$i];
        $raw = $matches[1][$i];
        $url = pandown_extract_url($raw);
        $type_name = pandown_detect_type($url);

        $hash = md5($url);
        $cache_file = $cache_dir . $hash . '.png';
        if(!is_file($cache_file)) {
            QRcode::png($url, $cache_file, QR_ECLEVEL_L, 4);
        }
        $qr_url = 'plugin/pandown/cache/' . $hash . '.png';

        $safe_url = htmlspecialchars($url, ENT_QUOTES);
        $html = '<div class="pandown-download">'
            . '<a href="javascript:void(0)" class="pandown-btn btn btn-primary" data-url="'.$safe_url.'" data-qr="'.$qr_url.'">'
            . '<span class="pandown-text">'.$type_name.'</span>'
            . '</a>'
            . '</div>';

        $first['message_fmt'] = str_replace($tag, $html, $first['message_fmt']);
    }
}
?>
