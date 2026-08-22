<?php exit;

function forum_format_tag(&$forum) {
	// todo:
	$forum['tagcatelist'] = tag_cate_find_by_fid($forum['fid']);
	// 过滤掉已禁用的标签
	if(!empty($forum['tagcatelist'])) {
		foreach($forum['tagcatelist'] as &$tagcate) {
			$taglist_filtered = array();
			if(!empty($tagcate['taglist'])) {
				foreach($tagcate['taglist'] as $tag) {
					if(!empty($tag['enable'])) $taglist_filtered[] = $tag;
				}
			}
			$tagcate['taglist'] = $taglist_filtered;
		}
		unset($tagcate);
	}
	$forum['tagcatemap'] = arrlist_change_key($forum['tagcatelist'], 'cateid');
	$forum['tagmap'] = tag_fetch_from_catelist($forum['tagcatelist']);
}

?>