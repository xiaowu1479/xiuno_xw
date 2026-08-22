<?php exit;
$filename = "$uid.png";
$dir = substr(sprintf("%09d", $uid), 0, 3) . '/';
$path = $conf['upload_path'] . 'avatar/' . $dir;
$url = $conf['upload_url'] . 'avatar/' . $dir . $filename;
!is_dir($path) AND (mkdir($path, 0777, TRUE));

$avatar_dir = APP_PATH . 'plugin/nciaer_autoavatar/avatars/';
$avatars = cache_get('nciaer_autoavatar');
if(empty($avatars)) {
    $avatars = array();
    if (file_exists($avatar_dir)) {
        $list = scandir($avatar_dir);
        foreach ($list as $key => $value) {
            if ($value != '.' && $value != '..' && file_ext($value) == 'png') {
                $avatars[] = $value;
            }
        }
    }
    cache_set('nciaer_autoavatar', $avatars, 3600);
}

$avatar = $avatars[array_rand($avatars, 1)];
copy($avatar_dir.$avatar, $path.$filename);

user_update($uid, array('avatar' => $time));