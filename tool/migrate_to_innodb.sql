-- MyISAM to InnoDB 迁移脚本
-- 执行前请先备份数据库！

-- 1. 先查看当前所有 MyISAM 表
-- SELECT CONCAT('ALTER TABLE ', TABLE_NAME, ' ENGINE=InnoDB;')
-- FROM INFORMATION_SCHEMA.TABLES
-- WHERE TABLE_SCHEMA = 'xiuluo'
-- AND ENGINE = 'MyISAM';

-- 2. 迁移核心表（按依赖顺序）
ALTER TABLE bbs_user ENGINE=InnoDB;
ALTER TABLE bbs_group ENGINE=InnoDB;
ALTER TABLE bbs_forum ENGINE=InnoDB;
ALTER TABLE bbs_thread ENGINE=InnoDB;
ALTER TABLE bbs_post ENGINE=InnoDB;
ALTER TABLE bbs_session ENGINE=InnoDB;
ALTER TABLE bbs_session_data ENGINE=InnoDB;
ALTER TABLE bbs_kv ENGINE=InnoDB;
ALTER TABLE bbs_runtime ENGINE=InnoDB;
ALTER TABLE bbs_attach ENGINE=InnoDB;
ALTER TABLE bbs_forum_access ENGINE=InnoDB;
ALTER TABLE bbs_mythread ENGINE=InnoDB;
ALTER TABLE bbs_modlog ENGINE=InnoDB;
ALTER TABLE bbs_thread_top ENGINE=InnoDB;
ALTER TABLE bbs_queue ENGINE=InnoDB;
ALTER TABLE bbs_table_day ENGINE=InnoDB;
ALTER TABLE bbs_medal ENGINE=InnoDB;
ALTER TABLE bbs_user_medal ENGINE=InnoDB;
ALTER TABLE bbs_notice ENGINE=InnoDB;

-- 3. 插件表（如果存在）
-- ALTER TABLE bbs_post_like ENGINE=InnoDB;
-- ALTER TABLE bbs_haya_favorite ENGINE=InnoDB;
-- ALTER TABLE bbs_sg_sign ENGINE=InnoDB;
-- ALTER TABLE bbs_sg_sign_set ENGINE=InnoDB;

-- 4. 验证是否全部迁移成功
-- SELECT TABLE_NAME, ENGINE
-- FROM INFORMATION_SCHEMA.TABLES
-- WHERE TABLE_SCHEMA = 'xiuluo'
-- AND ENGINE != 'InnoDB';
