<?php
!defined('DEBUG') AND exit('Forbidden');
$tablepre = $db->tablepre;
!db_sql_find_one("SHOW COLUMNS FROM {$tablepre}group LIKE 'allowOffer'") AND db_exec("ALTER TABLE {$tablepre}group ADD allowOffer INT(5) NOT NULL default '0'");
!db_sql_find_one("SHOW COLUMNS FROM {$tablepre}forum LIKE 'allowOffer'") AND db_exec("ALTER TABLE {$tablepre}forum ADD allowOffer INT(5) NOT NULL default '0'");
!db_sql_find_one("SHOW COLUMNS FROM {$tablepre}thread LIKE 'offerNum'") AND db_exec("ALTER TABLE {$tablepre}thread ADD offerNum INT(20) NOT NULL default '0'");
!db_sql_find_one("SHOW COLUMNS FROM {$tablepre}thread LIKE 'offerStatus'") AND db_exec("ALTER TABLE {$tablepre}thread ADD offerStatus INT(20) NOT NULL default '0'");
forum_list_cache_delete();
group_list_cache_delete();
setting_set('tt_offer',array('credits_type'=>3));
?>