<?php exit;
if($action == 'extend') {
		
	if($method == 'GET') {

	    $input = array();
		$input['runlevel_reason'] = form_textarea('runlevel_reason', $conf['runlevel_reason'], '100%', 100);
		$input['url_rewrite_on'] = form_radio_yes_no('url_rewrite_on', $conf['url_rewrite_on']); 
		$input['cdn_on'] = form_radio_yes_no('cdn_on', $conf['cdn_on']); 
		$input['admin_bind_ip'] = form_radio_yes_no('admin_bind_ip',$conf['admin_bind_ip']); 
		$input['pagesize'] = form_text('pagesize', $conf['pagesize'], 100); 
		$input['postlist_pagesize'] = form_text('postlist_pagesize', $conf['postlist_pagesize'], 100); 
		$input['site_keywords'] = form_text('site_keywords', empty($conf['site_keywords'])?'':$conf['site_keywords']); 
		$input['attach_maxsize'] = form_text('attach_maxsize', empty($conf['attach_maxsize'])? 20480000 :$conf['attach_maxsize']);
		$input['view_url'] = form_text('view_url', empty($conf['view_url'])? 'view/' :$conf['view_url']);
		$input['logo_mobile_url'] = form_text('logo_mobile_url', empty($conf['logo_mobile_url'])? 'view/img/logo.png' :$conf['logo_mobile_url']);
		$input['logo_pc_url'] = form_text('logo_pc_url', empty($conf['logo_pc_url'])? 'view/img/logo.png' :$conf['logo_pc_url']);
		$input['logo_water_url'] = form_text('logo_water_url', empty($conf['logo_water_url'])? 'view/img/water-small.png' :$conf['logo_water_url']);
		$input['cache_thread_list_pages'] = form_text('cache_thread_list_pages', empty($conf['cache_thread_list_pages'])? 10 :$conf['cache_thread_list_pages']);
		$input['online_update_span'] = form_text('online_update_span', empty($conf['online_update_span'])? 120 :$conf['online_update_span']);
		$input['online_hold_time'] = form_text('online_hold_time', empty($conf['online_hold_time'])? 3600 :$conf['online_hold_time']);
		$input['session_delay_update'] = form_text('session_delay_update', empty($conf['session_delay_update'])? 0 :$conf['session_delay_update']);
		$input['upload_image_width'] = form_text('upload_image_width', empty($conf['upload_image_width'])? 927 :$conf['upload_image_width']);
		$input['order_default'] = form_text('order_default', empty($conf['order_default'])? 'lastpid' :$conf['order_default']);
		$input['attach_dir_save_rule'] = form_text('attach_dir_save_rule', empty($conf['attach_dir_save_rule'])? 'Ym' :$conf['attach_dir_save_rule']);
		$input['update_views_on'] = form_radio_yes_no('update_views_on', empty($conf['update_views_on'])? 1 :$conf['update_views_on']);
		$input['disabled_plugin'] = form_radio_yes_no('disabled_plugin', empty($conf['disabled_plugin'])? 0 :$conf['disabled_plugin']);
		$input['static_version'] = form_text('static_version', empty($conf['static_version'])? '?1.0' :$conf['static_version']);
		$input['seo_sitename'] = form_text('seo_sitename', empty($conf['seo_sitename'])?'':$conf['seo_sitename']);
	    $header['title'] = lang('admin_setting_extend');
		$header['mobile_title'] = lang('admin_setting_extend');
		include _include(APP_PATH.'plugin/zaesky_theme_light/view/htm/setting_extend.htm');
	} else {
		$runlevel_reason = param('runlevel_reason', '', FALSE);
		$url_rewrite_on = param('url_rewrite_on', 0); 
		$cdn_on = param('cdn_on', 0); 
		$admin_bind_ip = param('admin_bind_ip', 0); 
		$pagesize = param('pagesize', 0); 
		$postlist_pagesize = param('postlist_pagesize', 0);  
		$site_keywords = param('site_keywords', '', FALSE);
		$attach_maxsize = param('attach_maxsize', 0); 
		$view_url = param('view_url', '', FALSE);
		$logo_mobile_url = param('logo_mobile_url', '', FALSE);
		$logo_pc_url = param('logo_pc_url', '', FALSE);
		$logo_water_url = param('logo_water_url', '', FALSE);
		$cache_thread_list_pages = param('cache_thread_list_pages', 0);
		$online_update_span = param('online_update_span', 0);
		$online_hold_time = param('online_hold_time', 0);
		$session_delay_update = param('session_delay_update', 0);
		$upload_image_width = param('upload_image_width', 0);
		$order_default = param('order_default', '', FALSE);
		$attach_dir_save_rule = param('attach_dir_save_rule', '', FALSE);
		$update_views_on = param('update_views_on', 0);
		$disabled_plugin = param('disabled_plugin', 0);
		$static_version = param('static_version', '', FALSE);
		$installed = param('installed', 0);
		$seo_sitename = param('seo_sitename', '', FALSE);

		$replace = array();
		$replace['runlevel_reason'] = $runlevel_reason;
		$replace['url_rewrite_on'] = $url_rewrite_on;
		$replace['cdn_on'] = $cdn_on;
		$replace['admin_bind_ip'] = $admin_bind_ip;
		$replace['pagesize'] = $pagesize;
		$replace['postlist_pagesize'] = $postlist_pagesize; 
		$replace['site_keywords'] = $site_keywords;
		$replace['attach_maxsize'] = $attach_maxsize;
		$replace['view_url'] = $view_url;
		$replace['logo_mobile_url'] = $logo_mobile_url;
		$replace['logo_pc_url'] = $logo_pc_url;
		$replace['logo_water_url'] = $logo_water_url;
		$replace['cache_thread_list_pages'] = $cache_thread_list_pages;
		$replace['online_update_span'] = $online_update_span;
		$replace['online_hold_time'] = $online_hold_time;
		$replace['session_delay_update'] = $session_delay_update;
		$replace['upload_image_width'] = $upload_image_width;
		$replace['order_default'] = $order_default;
		$replace['attach_dir_save_rule'] = $attach_dir_save_rule;
		$replace['update_views_on'] = $update_views_on;
		$replace['disabled_plugin'] = $disabled_plugin;
		$replace['static_version'] = $static_version;
		$replace['installed'] = $installed;
		$replace['seo_sitename'] = $seo_sitename;
		file_replace_var(APP_PATH.'conf/conf.php', $replace);
	    message(0, lang('save_successfully'));

	}

}
?>
