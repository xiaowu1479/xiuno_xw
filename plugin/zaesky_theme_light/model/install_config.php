<?php
// ThemeLightHelper.php

class ThemeLightHelper {
    public static function createTables($db) {
        $tablepre = $db->tablepre;

        // 添加用户背景图字段
        $sql = "ALTER TABLE ".$tablepre."user ADD COLUMN bgimg CHAR(64) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '0' COMMENT '系统背景'";
        db_exec($sql);

        // 创建授权码表
        $sql = "CREATE TABLE IF NOT EXISTS `".$tablepre."authCode` (
            `code` VARCHAR(32) NULL
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8;";
        db_exec($sql);
        $sql = "CREATE TABLE IF NOT EXISTS `".$tablepre."site_likes` (
             `id` INT(11) AUTO_INCREMENT PRIMARY KEY,
            `user_id` INT(11) NOT NULL,
            `like_date` DATE NOT NULL,
            `like_count` INT(11) NOT NULL DEFAULT 0
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8;";
        db_exec($sql);
        $sql = "CREATE TABLE IF NOT EXISTS `".$tablepre."site_total_likes` (
            `id` INT(11) AUTO_INCREMENT PRIMARY KEY,
            `total_like_count` INT(11) NOT NULL DEFAULT 0
       ) ENGINE=MyISAM DEFAULT CHARSET=utf8;";
       db_exec($sql);
    }

    public static function getDefaultConfig() {
        return array(
            "side_nav_switch" => 1,
            "thread_list_style" => 1,
            "thread_reply_reload" => 0,
            "window_no_console" => 0,
            "thread_top_nav" => 0,
            "nav_search_form" => 0,
            "body_font_style" => 0,
            "header_gold_count" => 0,
            "groupicon_display" => 0,
            "post_show_first_floor" => 0,
            "post_show_floors" => 1,
            "index_userinfo" => 1,
            "post_show_time" => 0,
            "post_func_position" => 0,
            "post_form_position" => 0,
            "thread_left_switch" => 1,
            "thread_left_func" => 0,
            "thread_left_tools" => 1,
            "back_top" => 1,
            "thread_top_ind" => 1,
            "thread_summary" => 1,
            "thread_summary_word" => 100,
            "login_guide_icon" => "fas fa-grin-wink",
            "login_guide_1" => "你好！欢迎来访！",
            "login_guide_2" => "请登录后探索更多精彩内容！",
            "site_annoucement_switch" => 0,
            "site_annoucement_position" => 1,
            "site_annoucement_style" => 0,
            "site_annoucement_content" => "",
            "site_annoucement_icon" => "fas fa-bullhorn",
            "login_annoucement_content" => "",
            "comment_annoucement_content" => "",
            "user_online" => 0,
            "new_thread" => 0,
            "dock_switch" => 0,
            "dock_func_3" => 0,
            "thread_forum_name" => 1,
            "thread_last_reply" => 1,
            "site_info_bg" => '',
            "site_info_logo" => '/view/img/favicon.ico',
            "site_info_switch" => 1,
            "thread_user_bg" => 1,
            "site_info_total" => 1,
            "thread_summary_pic" => 3,
            "navbar_cate" => 1,
            "thread_subject_size" => '14',
            "thread_quick_at" => 0,
            "disc_page" => 0,
            "disc_page_title" => '发现',
            "disc_page_icon" => 'fas fa-atom',
            "disc_page_banner" => '',
            "disc_page_banner_a" => 'https://www.noteweb.top/',
            "user_bg_switch" => 0,
            "user_bg_num" => '9',
            "dark_mode_switch" => 0,
            "dark_mode_time_1" => '19',
            "dark_mode_time_2" => '7',
            "navbar_create_icon" => 1,
            "thread_user_del" => 0,
            "thread_user_upd" => 0,
            "theme_copyright_switch" => 1,
            "pic_lazyload_switch" => 0,
            "new_threadlist" => 1,
            "thread_footer_info" => 0,
            "thread_footer_info_content" => '',
            "thread_emo_func" => 1,
            "config_exc_info" => 1,
            "contact_qq" => 1,
            "contact_qq_number" => '',
            "contact_wx" => 1,
            "contact_wx_img" => '',
            "contact_email" => 1,
            "contact_email_number" => '',
            "site_bg_custom" => 0,
            "site_bg_custom_img" => '',
            "site_bg_cover" => 0,
            "site_bg_scroll" => 0,
            "show_all_reply" => 1,
            "disc_page_index" => 0,
            "navbar_cate_func" => 1,
            "disc_nav_bbs" => 1,
            "func_left_custom" => 0,
            "func_left_custom_content" => '',
            "pre_next_thread" => 0,
            "user_info_card" => 1,
            "thread_post_annoucement" => '',
            "login_page_banner" => '',
            "footer_one_content" => '我是一级标题，可以显示站点名称或其他',
            "footer_two_content" => '我是二级标题，可以用来显示链接或者其他站点简介等信息。',
            "footer_icon_show" => 1,
            "index_side_height" => 1,
            "index_side_usercard" => 1,
            "index_side_usercard_position" => 1,
        );
    }
}