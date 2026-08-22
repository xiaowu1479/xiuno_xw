<?php
!defined('DEBUG') AND exit('Forbidden');
$tablepre = $db->tablepre;
db_exec("ALTER TABLE {$tablepre}group DROP COLUMN allowOffer");
db_exec("ALTER TABLE {$tablepre}forum DROP COLUMN allowOffer");
db_exec("ALTER TABLE {$tablepre}thread DROP COLUMN offerNum");
db_exec("ALTER TABLE {$tablepre}thread DROP COLUMN offerStatus");
setting_delete('tt_offer');