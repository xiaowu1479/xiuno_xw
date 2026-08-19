<?php
// 授权信息类
class AuthInfo{
    private static $authcode;
    const VERSION = '2504133441';
    const EDITION = '3.4.41';
    const PUBLIC_KEY = '-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEArub5CSl07mHZ4mlazvef
jOGeXyz9oSBTni6dCF+l6vB+TtSAr+4EPcIjgQ5ZF6ITXhk8ahWuzaIiw9hzAB8+
RNDFnX3l6DbR4ldbsrID6rkasX4XlL+a3E+NohF+fsLTmjYA+70qhk0GrUlAzx3H
N5TKv19u2JezRBtk1K1SR3Fn3BI5FE/BhM2df5AVwDGg0sGXEngwh8TWZOXgm6Up
rxx2GnXhNGtmltZzKC4HSeTu5cCB2WpPrxaYKmXoDj6nL8FY+f/5fUnefSNaJqV2
bCQfNGke1Zl6O9baBu/hUsuYwHVJQoymLiCI15/XUrxCK8zi71WXAkC5UXJLYqEZ
ywIDAQAB
-----END PUBLIC KEY-----';
    
    public static function init($authcode) {
        self::$authcode = $authcode;
    }

    public static function getAuthcode() {
        // 绕过授权，直接返回固定字符串
        return 'bypass_authcode';
    }
}
