<?php
// 净化后的安装脚本，无加密、无授权逻辑
require_once 'install_config.php';

class ThemeLightInstaller {
    private static $db;
    private static $tablepre;

    public function __construct($db) {
        self::$db = $db;
        self::$tablepre = isset($db->tablepre) ? $db->tablepre : '';
    }

    public static function install() {
        // 这里只保留必要的安装逻辑，如有需要可补充
        // 例如：调用 install_config.php 里的表结构创建方法
        if (class_exists('ThemeLightHelper') && method_exists('ThemeLightHelper', 'createTables')) {
            ThemeLightHelper::createTables(self::$db, self::$tablepre);
        }

        if (class_exists('ThemeLightHelper') && method_exists('ThemeLightHelper', 'getDefaultConfig')) {
            $default_config = ThemeLightHelper::getDefaultConfig();
            setting_set('admin_light_setting', $default_config);
        }

        return true;
    }
}