<?php exit;

    $attach_maxsize = empty($conf['attach_maxsize']) ? 20971520 : $conf['attach_maxsize'];
	$size > $attach_maxsize AND message(-1, lang('filesize_too_large', array('maxsize'=> round($attach_maxsize / 1048576 * 100) / 100 .'Mb', 'size'=>round($size / 1048576 * 100) / 100 .'Mb')));

?>