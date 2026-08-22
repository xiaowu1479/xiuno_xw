<?php exit;
		// 删除主题时同步删除标签关联
		tag_thread_delete_by_tid($tid);
?>