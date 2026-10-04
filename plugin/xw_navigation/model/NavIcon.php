<?php
!defined('DEBUG') AND exit('Access Denied');

/**
 * 站点图标（favicon）抓取 + 本地缓存
 *
 * 原实现直接用 https://www.google.com/s2/favicons 取图标：
 *   - 国内网络访问不到 google.com；
 *   - 若本机装了 Steamcommunity302/Watt Toolkit 之类工具，
 *     www.google.com 会被 hosts 劫持到本地服务，返回一段 text/plain 错误提示，
 *     浏览器拿到的不是图片，<img> 直接加载失败 —— 所以「留空自动获取图标」看起来是坏的。
 *
 * 现改为服务端按多个「国内可达」源依次抓取，命中后落到 upload/nav_icon/ 本地缓存，
 * 前台只引用本地文件，既快又不依赖访客网络。
 */
class NavIcon {

    const DIR = 'upload/nav_icon/';     // 相对站点根目录
    const MAX_BYTES = 512000;           // 单个图标限制 500KB
    const CONNECT_TIMEOUT = 3;
    const TIMEOUT = 6;

    /** 支持的图片后缀（按查缓存的优先级） */
    public static function exts() {
        return array('png', 'svg', 'ico', 'jpg', 'gif', 'webp');
    }

    /** 候选图标源：按顺序尝试，命中即停 */
    public static function sources($host) {
        $h = urlencode($host);
        return array(
            'https://'.$host.'/favicon.ico',
            'https://favicon.im/'.$host,
            'https://favicon.zhusl.com/ico?url='.$h,
            'https://www.faviconextractor.com/favicon/'.$host.'?larger=true',
        );
    }

    /** 从链接中解析主机名（兼容用户没写 http:// 的情况） */
    public static function host($url) {
        $url = trim(strval($url));
        if($url === '') return '';
        $host = parse_url($url, PHP_URL_HOST);
        if(!$host) $host = parse_url('http://'.ltrim($url, '/'), PHP_URL_HOST);
        return $host ? strtolower($host) : '';
    }

    /** 本地缓存目录下的文件名（不含后缀） */
    private static function basename($host) {
        return md5($host);
    }

    /**
     * 把库里存的图标值转成可直接放进 src 的地址
     * 图标存的是相对站点根的路径（如 upload/nav_icon/xx.ico），
     * 而后台页面入口是 /admin/index.php，直接用相对路径会变成 /admin/upload/... 404，
     * 所以这里统一转成「相对站点根的绝对路径」。
     * 外部地址（http/https/data/以 / 开头）原样返回。
     */
    public static function url($icon) {
        $icon = trim(strval($icon));
        if($icon === '') return '';
        if(preg_match('#^(https?:)?//#i', $icon) || strpos($icon, 'data:') === 0 || substr($icon, 0, 1) === '/') {
            return $icon;
        }
        $dir = str_replace('\\', '/', strval(dirname(isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '/index.php')));
        $dir = rtrim($dir, '/');
        // 后台入口位于 /admin/ 下，需要回退一级才是站点根
        if(defined('ADMIN_PATH')) {
            if(substr($dir, -6) === '/admin') $dir = substr($dir, 0, -6);
            elseif($dir === '/admin') $dir = '';
        }
        return $dir.'/'.$icon;
    }
    
    /** 已缓存则返回相对路径，否则返回 '' */
    public static function cached($url) {
        $host = self::host($url);
        if($host === '') return '';
        $name = self::basename($host);
        foreach(self::exts() as $ext) {
            if(is_file(APP_PATH.self::DIR.$name.'.'.$ext)) return self::DIR.$name.'.'.$ext;
        }
        return '';
    }

    /** icon 已有值（FA 类名 / 图片地址）直接返回；留空才抓取 */
    public static function ensure($url, $icon = '') {
        $icon = trim(strval($icon));
        if($icon !== '') return $icon;
        return self::fetch($url);
    }

    /** 抓取图标：先查本地缓存，再按源顺序下载，成功保存并返回相对路径，失败返回 '' */
    public static function fetch($url) {
        $host = self::host($url);
        if($host === '') return '';
        $cached = self::cached($url);
        if($cached !== '') return $cached;

        $dir = APP_PATH.self::DIR;
        is_dir($dir) OR @mkdir($dir, 0755, TRUE);
        if(!is_dir($dir)) return '';
        $name = self::basename($host);

        foreach(self::sources($host) as $src) {
            $bin = self::download($src);
            if($bin === '') continue;
            $ext = self::detect($bin);
            if($ext === '') continue;   // 拿到的不是图片（例如错误页 / HTML）
            if(@file_put_contents($dir.$name.'.'.$ext, $bin) === FALSE) return '';
            return self::DIR.$name.'.'.$ext;
        }
        return '';
    }

    /** 下载，返回二进制内容；HTTP 非 200 / 空 / 超限 / 内容不是图片均返回 '' */
    private static function download($url) {
        $ua = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';
        $bin = '';
        if(function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, array(
                CURLOPT_RETURNTRANSFER => TRUE,
                CURLOPT_FOLLOWLOCATION => TRUE,
                CURLOPT_MAXREDIRS => 3,
                CURLOPT_CONNECTTIMEOUT => self::CONNECT_TIMEOUT,
                CURLOPT_TIMEOUT => self::TIMEOUT,
                CURLOPT_SSL_VERIFYPEER => FALSE,
                CURLOPT_SSL_VERIFYHOST => 0,
                CURLOPT_USERAGENT => $ua,
                CURLOPT_HTTPHEADER => array('Accept: image/avif,image/webp,image/*,*/*;q=0.8'),
            ));
            $bin = curl_exec($ch);
            $code = intval(curl_getinfo($ch, CURLINFO_HTTP_CODE));
            curl_close($ch);
            if($code !== 200) return '';
        } else {
            $ctx = stream_context_create(array(
                'http' => array('timeout' => self::TIMEOUT, 'user_agent' => $ua),
                'ssl' => array('verify_peer' => FALSE, 'verify_peer_name' => FALSE),
            ));
            $bin = @file_get_contents($url, FALSE, $ctx);
        }
        if(!is_string($bin) || $bin === '') return '';
        if(strlen($bin) > self::MAX_BYTES) return '';
        if(self::detect($bin) === '') return '';    // 图片魔数校验，挡掉 HTML 错误页
        return $bin;
    }

    /** 依据文件头判断真实图片类型，识别不出（HTML/文本）返回 '' */
    public static function detect($bin) {
        if(strncmp($bin, "\x89PNG\r\n\x1a\n", 8) === 0) return 'png';
        if(strncmp($bin, "\xFF\xD8\xFF", 3) === 0) return 'jpg';
        if(strncmp($bin, 'GIF87a', 6) === 0 || strncmp($bin, 'GIF89a', 6) === 0) return 'gif';
        if(strncmp($bin, 'RIFF', 4) === 0 && substr($bin, 8, 4) === 'WEBP') return 'webp';
        if(strncmp($bin, "\x00\x00\x01\x00", 4) === 0) return 'ico';   // ICO/CUR
        $head = strtolower(substr($bin, 0, 600));
        if(strpos($head, '<svg') !== FALSE) return 'svg';
        return '';
    }
}
