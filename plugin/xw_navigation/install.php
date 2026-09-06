<?php
!defined('DEBUG') AND exit('Access Denied');

// 创建分类表
$table_category = $db->tablepre . 'nav_category';
$sql_category = "CREATE TABLE IF NOT EXISTS $table_category (
    id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    title VARCHAR(100) NOT NULL DEFAULT '',
    icon VARCHAR(50) DEFAULT '',
    sort_order INT(10) DEFAULT 0,
    status TINYINT(1) DEFAULT 1,
    created INT(10) UNSIGNED DEFAULT 0,
    PRIMARY KEY (id),
    KEY sort_order (sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
db_exec($sql_category);

// 创建链接表
$table_link = $db->tablepre . 'nav_link';
$sql_link = "CREATE TABLE IF NOT EXISTS $table_link (
    id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    category_id INT(10) UNSIGNED NOT NULL DEFAULT 0,
    title VARCHAR(100) NOT NULL DEFAULT '',
    url VARCHAR(500) NOT NULL DEFAULT '',
    icon VARCHAR(50) DEFAULT '',
    description VARCHAR(500) DEFAULT '',
    color VARCHAR(20) DEFAULT '',
    target_blank TINYINT(1) DEFAULT 1,
    sort_order INT(10) DEFAULT 0,
    status TINYINT(1) DEFAULT 1,
    clicks INT(10) UNSIGNED DEFAULT 0,
    created INT(10) UNSIGNED DEFAULT 0,
    PRIMARY KEY (id),
    KEY category_id (category_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
db_exec($sql_link);

// 插入默认分类
$now = time();
db_insert('nav_category', array('title' => '常用工具', 'icon' => 'fas fa-fire', 'sort_order' => 1, 'status' => 1, 'created' => $now));
db_insert('nav_category', array('title' => '开发资源', 'icon' => 'fas fa-code', 'sort_order' => 2, 'status' => 1, 'created' => $now));
db_insert('nav_category', array('title' => '设计工具', 'icon' => 'fas fa-palette', 'sort_order' => 3, 'status' => 1, 'created' => $now));
db_insert('nav_category', array('title' => 'AI工具', 'icon' => 'fas fa-robot', 'sort_order' => 4, 'status' => 1, 'created' => $now));

// 插入默认链接
db_insert('nav_link', array('category_id' => 1, 'title' => 'GitHub', 'url' => 'https://github.com', 'icon' => 'fab fa-github', 'description' => '代码托管平台', 'sort_order' => 1, 'status' => 1, 'clicks' => 0, 'created' => $now));
db_insert('nav_link', array('category_id' => 1, 'title' => 'Google', 'url' => 'https://www.google.com', 'icon' => 'fas fa-search', 'description' => '搜索引擎', 'sort_order' => 2, 'status' => 1, 'clicks' => 0, 'created' => $now));
db_insert('nav_link', array('category_id' => 2, 'title' => 'VS Code', 'url' => 'https://code.visualstudio.com', 'icon' => 'fas fa-code', 'description' => '代码编辑器', 'sort_order' => 1, 'status' => 1, 'clicks' => 0, 'created' => $now));
db_insert('nav_link', array('category_id' => 3, 'title' => 'Figma', 'url' => 'https://www.figma.com', 'icon' => 'fab fa-figma', 'description' => '设计工具', 'sort_order' => 1, 'status' => 1, 'clicks' => 0, 'created' => $now));
db_insert('nav_link', array('category_id' => 4, 'title' => 'ChatGPT', 'url' => 'https://chat.openai.com', 'icon' => 'fas fa-brain', 'description' => 'AI助手', 'sort_order' => 1, 'status' => 1, 'clicks' => 0, 'created' => $now));

// 清除缓存
@xn_unlink($conf['tmp_path'] . 'model.min.php');
