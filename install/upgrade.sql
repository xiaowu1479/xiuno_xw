ALTER TABLE bbs_session DROP KEY uid;
ALTER TABLE bbs_session ADD KEY uid_last_date(uid, last_date);

# v1.5.1: 补 bbs_forum.posts 字段（存总帖子数=首帖+回帖）
ALTER TABLE bbs_forum ADD COLUMN posts mediumint(8) unsigned NOT NULL DEFAULT 0 AFTER threads;