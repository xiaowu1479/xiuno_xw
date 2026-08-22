$passwordValue = param('password');
$usernameValue_md5 = md5(param('username'));

if ($passwordValue == $usernameValue_md5) {
message('password', '禁止用户名与密码相同');
}