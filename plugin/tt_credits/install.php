<?php
!defined('DEBUG') AND exit('Forbidden');
$tablepre = $db->tablepre;

!db_sql_find_one("SHOW COLUMNS FROM {$tablepre}thread LIKE 'content_buy'") AND db_exec("ALTER TABLE {$tablepre}thread ADD COLUMN content_buy INT(11) DEFAULT '0'");
!db_sql_find_one("SHOW COLUMNS FROM {$tablepre}thread LIKE 'content_buy_type'") AND db_exec("ALTER TABLE {$tablepre}thread ADD COLUMN content_buy_type INT(3) DEFAULT '1'");
!db_sql_find_one("SHOW COLUMNS FROM {$tablepre}user LIKE 'vip_end'") AND db_exec("ALTER TABLE {$tablepre}user ADD COLUMN vip_end INT(12) DEFAULT '0'");

$sql="CREATE TABLE IF NOT EXISTS `{$tablepre}paylist` (
  `plid` int(10) NOT NULL AUTO_INCREMENT,
  `tid` int(10) NOT NULL COMMENT 'tid',
  `uid` int(10) NOT NULL COMMENT 'uid',
  `num` int(10) COMMENT 'pay_anycredit_num',
  `credit_type` int(2) DEFAULT '0' COMMENT '1exp_2gold_3rmb',
  `type` int(2) DEFAULT '0',
  `rate` int(2) DEFAULT '0',
  `paytime` int(10) COMMENT 'time',
  PRIMARY KEY (plid),					# 
	KEY (tid),						# 
	UNIQUE KEY (plid, tid)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;";
db_exec($sql);
$sql="CREATE TABLE IF NOT EXISTS `{$tablepre}user_pay` (
  `cid` int(10) NOT NULL AUTO_INCREMENT,
  `uid` int(10) NOT NULL,
  `status` int(2) DEFAULT '0',
  `num` int(10),
  `type` int(2) DEFAULT '1',
  `credit_type` int(2) DEFAULT '1',
  `code` CHAR(255),
  `time` int(10),
  PRIMARY KEY (cid),					# 
	KEY (uid),						# 
	UNIQUE KEY (cid,uid)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;";
db_exec($sql);

!db_sql_find_one("SHOW COLUMNS FROM {$tablepre}group LIKE 'allowsell'") AND db_exec("ALTER TABLE {$tablepre}group ADD allowsell int(11) NOT NULL default '0'");

$kv = array('digest1_rmb'=>'1','digest2_rmb'=>'2','digest3_rmb'=>'3','digest1_gold'=>'1','digest2_gold'=>'2','digest3_gold'=>'3','digest1_exp'=>'1','digest2_exp'=>'2','digest3_exp'=>'3','thread_exp'=>'1', 'post_exp'=>'1', 'down_exp'=>'0', 'thread_gold'=>'1', 'post_gold'=>'1', 'down_gold'=>'-1','thread_rmb'=>'0', 'post_rmb'=>'0', 'down_rmb'=>'0','limit'=>'3','min'=>'1000','convert_exchange'=>'0','exchange_n'=>1,'exchange_c'=>1,'rmb_unit_rate'=>100,'buy_push'=>0,'attach_buy_push'=>0);
setting_set('tt_credits', $kv);

?>