<?php
!defined('DEBUG') AND exit('Forbidden');
$tablepre = $db->tablepre;
//用户蚀刻表
$sql="CREATE TABLE IF NOT EXISTS `{$tablepre}user_medal` (
  `log_id` int(10) NOT NULL AUTO_INCREMENT,
  `uid` int(10) NOT NULL,
  `mid` int(10) NOT NULL,
  `time` int(20) DEFAULT '0',
  PRIMARY KEY (log_id),
	KEY (log_id),
	UNIQUE KEY (log_id)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;";
db_exec($sql);
//蚀刻列表
$sql="CREATE TABLE IF NOT EXISTS `{$tablepre}medal` (
  `mid` int(10) NOT NULL AUTO_INCREMENT,
  `name` CHAR(50) NOT NULL,
  `filename` CHAR(30) NOT NULL,
  `description` CHAR(200) NOT NULL,
  `isbuy` int(5) NOT NULL DEFAULT '0',
  `money` int(20) DEFAULT '0',
  `money_type` int(5) DEFAULT '0',
  `have_count` int(15) DEFAULT '0',
  PRIMARY KEY (mid),
	KEY (mid),
	UNIQUE KEY (mid)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;";
db_exec($sql);
//蚀刻审核表
$sql="CREATE TABLE IF NOT EXISTS `{$tablepre}medal_check` (
  `mcid` int(10) NOT NULL AUTO_INCREMENT,
  `mid` int(10) NOT NULL,
  `uid` int(10) NOT NULL,
  `reason` CHAR(200) NOT NULL,
  `time` int(20) DEFAULT '0',
  `result` int(10) NOT NULL,
  PRIMARY KEY (mcid),
	KEY (mcid),
	UNIQUE KEY (mcid)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;";
db_exec($sql);
//如果蚀刻列表为空则默认10个初始蚀刻
$medal_count = db_count('medal');
if($medal_count==0)
    for($i=1;$i<=10;$i++)
        db_insert('medal',array('name'=>'勋章'.$i,'filename'=>$i.'.gif','description'=>'我是勋章'.$i,'isbuy'=>0,'money'=>0,'money_type'=>0));
//20210319 新增了 销毁比例
//20210319 ADD proportion
$kv = array('proportion'=>5);
//tt_medal
setting_set('tt_medal',$kv);
//20210320 新增了有效期字段
!db_sql_find_one("SHOW COLUMNS FROM `{$tablepre}medal` LIKE 'time'") AND db_exec("ALTER TABLE `{$tablepre}medal` ADD COLUMN `time` int(5) NULL DEFAULT 0 COMMENT '有效期'");
!db_sql_find_one("SHOW COLUMNS FROM `{$tablepre}user_medal` LIKE 'validity'") AND db_exec("ALTER TABLE `{$tablepre}user_medal` ADD COLUMN `validity` int(20) NULL DEFAULT 0 COMMENT '有效期'");
?>