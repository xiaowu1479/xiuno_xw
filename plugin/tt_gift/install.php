<?php

!defined('DEBUG') AND exit('Forbidden');
$tablepre = $db->tablepre;

$sql="CREATE TABLE IF NOT EXISTS `{$tablepre}gift` (
  `zid` int(10) NOT NULL AUTO_INCREMENT,
  `card_id` CHAR(50) NOT NULL,
  `uid` int(10) DEFAULT '0',
  `credits` int(10) DEFAULT '0',
  `golds` int(10) DEFAULT '0',
  `rmbs` int(10) DEFAULT '0',
  `tids` CHAR(100) DEFAULT '0',
  `status` int(2) DEFAULT '0',
  `time_from` int(12) DEFAULT '0',
  `time_to` int(12) DEFAULT '0',
  PRIMARY KEY (zid),					# 
	KEY (uid),						# 
	UNIQUE KEY (zid, uid)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;";
db_exec($sql);


?>