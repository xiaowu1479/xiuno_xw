<?php
!defined('DEBUG') AND exit('Forbidden');
$tablepre = $db->tablepre;
db_exec("ALTER TABLE {$tablepre}post DROP COLUMN source");
db_exec("ALTER TABLE {$tablepre}thread DROP COLUMN thumbnail");
kv_delete('post_source');
?>