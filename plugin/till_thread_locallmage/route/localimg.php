<?php
!defined('DEBUG') AND exit('Access Denied.');

// 图片本地化：下载远程图片 → 「限宽 + 条件转 WebP」→ 存 upload/tmp/
// （发帖时由核心 attach_assoc_post() 归档到 upload/attach/年月/）
// 优化逻辑与参数在公共库 imagex.inc.php 里，上传通道 hook/attach_create_save_before.php 共用同一份
include_once APP_PATH.'plugin/till_thread_locallmage/imagex.inc.php';

$url = param('url');
$message = param('message');

user_login_check();

$myhost = strtolower(trim($_SERVER['HTTP_HOST']));

//preg_match_all('#<img[^>]+src="(http://.*?)"#i', $message, $m);
//if(!empty($m[1])) {
//  $n = 0;
//  $myhost = strtolower(trim($_SERVER['HTTP_HOST']));
//  foreach($m[1] as $k=>$url) {
$urlarr = parse_url($url);
if (strtolower($urlarr['host']) == $myhost || (strpos($url, 'http') != 0 && strpos($url, '//') != 0)) {
  message(0, htmlspecialchars_decode($url));
  die;
}
$ext = 'png';
//if(!in_array($ext, array('gif', 'jpg', 'png', 'bmp', 'jpeg'))) {
//	message(0,htmlspecialchars_decode($url));die;
//}
$tmpanme = $uid . '_' . xn_rand(15) . '.' . $ext;
$tmpfile = $conf['upload_path'] . 'tmp/' . $tmpanme;
$tmpurl = $conf['upload_url'] . 'tmp/' . $tmpanme;

sess_restart();
empty($_SESSION['tmp_files']) and $_SESSION['tmp_files'] = array();
$n = count($_SESSION['tmp_files']);
//$refererarr = parse_url($url);
//$referer = $refererarr['scheme'] . '://' . $refererarr['host'];
$arrContextOptions = array(
  'ssl' => array(
    'verify_peer' => false,
    'verify_peer_name' => false,
  ),
  'http' => array(
    //'header'=>array("Referer: {$referer}"),
    'timeout' => 60,
  ),
  'https' => array(
    //'header'=>array("Referer: {$referer}"),
    'timeout' => 60,
  ),
);
$imgdata = file_get_contents($url, false, stream_context_create($arrContextOptions));
$filesize = strlen($imgdata);
if ($filesize < 10) {
  message(0, htmlspecialchars_decode($url));
  die;
}
file_put_contents_try($tmpfile, $imgdata);

// 限宽 + 条件转 WebP（不划算或命中回退条件时保持原文件）
$img = xw_localimg_optimize($tmpfile, $tmpurl, xw_localimg_cfg());
$destsize = filesize($img['path']);
list($width, $height) = getimagesize($img['path']);

$attach = array(
  'url' => $img['url'],
  'path' => $img['path'],
  'orgfilename' => file_name($url),
  'filetype' => 'image',
  'filesize' => $destsize,
  'width' => $width,
  'height' => $height,
  'isimage' => 1,
  'downloads' => 0,
  'aid' => '_' . $n
);

$_SESSION['tmp_files'][$n] = $attach;

unset($attach['path']);
$url = $attach['url'];
//$message = str_replace($file['url'], $desturl, $message);
// }

message(0, htmlspecialchars_decode($url));
die;
//}

//message(-1,htmlspecialchars_decode($message));
