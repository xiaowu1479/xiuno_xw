<?php exit;
$setting = setting_get('pandown_setting');
if (empty($setting['clear_qrcode'])) return;

$firstpid = $thread['firstpid'];
if (empty($firstpid)) return;

$first = post_read_cache($firstpid);
if (empty($first)) return;

$message = $first['message'] ?? $first['message_fmt'] ?? '';
if (empty($message)) return;

preg_match_all('/\[pd\s+url="(.*?)"\]/i', $message, $matches);
if (empty($matches[1])) return;

$cache_dir = APP_PATH.'plugin/pandown/cache/';
foreach ($matches[1] as $url) {
    $hash = md5(trim($url));
    $cache_file = $cache_dir . $hash . '.png';
    if (is_file($cache_file)) {
        unlink($cache_file);
    }
}
