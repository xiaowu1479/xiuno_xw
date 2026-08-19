<?php exit;
$pnumber = $thread['readp'];
$input['readp_status'] = form_radio_yes_no('readp_status', $pnumber > 0 ? 1 : 0);
?>