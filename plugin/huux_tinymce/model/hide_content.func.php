<?php

function huux_hide_content_user_replied($tid, $uid, $author_uid)
{
	if(empty($tid) || empty($uid)) return FALSE;
	if($uid == $author_uid) return TRUE;
	$count = db_count('post', array('tid'=>$tid, 'uid'=>$uid, 'isfirst'=>0));
	return $count > 0;
}

function huux_hide_content_can_view($post)
{
	global $uid, $gid;
	if(empty($post) || empty($uid)) return FALSE;
	if($uid == $post['uid']) return TRUE;
	if($gid == 1) return TRUE;
	return huux_hide_content_user_replied($post['tid'], $uid, $post['uid']);
}

function huux_hide_content_placeholder($label)
{
	global $uid;
	$label = htmlspecialchars($label, ENT_QUOTES, 'UTF-8');
	$href = empty($uid) ? url('user-login') : '#message';
	$href = htmlspecialchars($href, ENT_QUOTES, 'UTF-8');
	return '<div class="huux-hide-box huux-hide-box--locked">'
		.'<div class="huux-hide-box__title">隐藏内容</div>'
		.'<div class="huux-hide-box__text">此处内容需要回复后可见</div>'
		.'<a class="huux-hide-box__tag huux-hide-box__reply-link" href="'.$href.'">'.$label.'</a>'
		.'</div>';
}

function huux_hide_content_visible($content)
{
	return '<div class="huux-hide-box huux-hide-box--visible">'
		.'<div class="huux-hide-box__title">隐藏内容已显示</div>'
		.'<div class="huux-hide-box__content">'.$content.'</div>'
		.'</div>';
}

function huux_hide_content_render($message, $post)
{
	if(stripos($message, '[reply]') === FALSE
		&& stripos($message, '[hide]') === FALSE
		&& stripos($message, '[ttreply]') === FALSE
		&& stripos($message, '[ttlogin]') === FALSE) {
		return $message;
	}

	$can_view = huux_hide_content_can_view($post);
	$labels = array(
		'reply' => '回复可见',
		'hide' => '回复可见',
		'ttreply' => '回复可见',
		'ttlogin' => '回复可见',
	);

	return preg_replace_callback('#(?:<p[^>]*>\s*)?\[(reply|hide|ttreply|ttlogin)\](.*?)(?:\[/\1\])(?:\s*</p>)?#is', function($matches) use ($can_view, $labels) {
		$tag = strtolower($matches[1]);
		$label = array_value($labels, $tag, '回复可见');
		$content = trim($matches[2]);
		$content = preg_replace('#^(<br\s*/?>\s*)+|(\s*<br\s*/?>)+$#i', '', $content);
		return $can_view ? huux_hide_content_visible($content) : huux_hide_content_placeholder($label);
	}, $message);
}

?>
