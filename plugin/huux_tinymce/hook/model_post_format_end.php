if(!empty($post['message_fmt'])) {
	$post['message_fmt'] = huux_hide_content_render($post['message_fmt'], $post);
}
