<?php
class Authorization {
    public static function updateMain($action = 'check') {
        return [
            'code' => 0,
            'msg' => '当前已经是最新版，无需更新'
        ];
    }
    public static function checkIsAuth() { return true; }
    public static function validateAndSaveAuth($auth_code) { return ['code'=>0, 'msg'=>'授权成功']; }
    public static function checkInfo($type='auth') { return true; }
}