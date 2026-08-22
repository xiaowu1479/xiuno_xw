<?php exit;
$source = param('source', '', FALSE);
if ($source !== '') {
	post__update($pid, array('source' => $source));
}
