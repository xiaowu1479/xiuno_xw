<?php

!defined('DEBUG') AND exit('Access Denied.');

// hook captcha_start.php

// 生成验证码字符（去掉易混淆的 0 O 1 I L）
$captcha_charset = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
$code = '';
for($i = 0; $i < 5; $i++) {
	$code .= $captcha_charset[mt_rand(0, strlen($captcha_charset) - 1)];
}

// 存入 session，用后即焚（每次图片请求都会刷新，旧码作废）
$_SESSION['captcha_code'] = $code;

// 禁止缓存，防止验证码复用
header('Content-Type: image/png');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

// GD 画图
$w = 128;
$h = 42;
$img = imagecreatetruecolor($w, $h);

// 背景
$bg = imagecolorallocate($img, 245, 247, 250);
imagefilledrectangle($img, 0, 0, $w, $h, $bg);

// 干扰线
for($i = 0; $i < 6; $i++) {
	$c = imagecolorallocate($img, mt_rand(150, 220), mt_rand(150, 220), mt_rand(150, 220));
	imageline($img, mt_rand(0, $w), mt_rand(0, $h), mt_rand(0, $w), mt_rand(0, $h), $c);
}

// 干扰点
for($i = 0; $i < 100; $i++) {
	$c = imagecolorallocate($img, mt_rand(120, 230), mt_rand(120, 230), mt_rand(120, 230));
	imagesetpixel($img, mt_rand(0, $w), mt_rand(0, $h), $c);
}

// 字符
$chars = str_split($code);
$x = 12;
foreach($chars as $ch) {
	$c = imagecolorallocate($img, mt_rand(25, 90), mt_rand(25, 90), mt_rand(25, 90));
	imagestring($img, 6, $x, mt_rand(5, 16), $ch, $c);
	$x += 21;
}

// hook captcha_end.php

imagepng($img);
imagedestroy($img);

exit;

?>
