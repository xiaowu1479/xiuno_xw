<?php exit;
$source = param('source', '', FALSE);
if (!empty($source)) {
	post__update($pid, array('source' => $source));
}
