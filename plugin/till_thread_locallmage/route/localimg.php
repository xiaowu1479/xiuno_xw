<?php
!defined('DEBUG') AND exit('Access Denied.');

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
list($width, $height) = getimagesize($tmpfile);
$attach = array(
  'url' => $tmpurl,
  'path' => $tmpfile,
  'orgfilename' => file_name($url),
  'filetype' => 'image',
  'filesize' => $filesize,
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
