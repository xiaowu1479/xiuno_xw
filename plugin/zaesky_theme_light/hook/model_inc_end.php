include _include(APP_PATH.'plugin/zaesky_theme_light/model/light.func.php');

light_lazy_init_globals();

function light_lazy_init_globals() {
    static $initialized = false;
    if ($initialized) {
        return;
    }
    $initialized = true;
    
    global $arronline, $rew, $action, $light_config;
    global $main_switch, $index_header_switch, $icon_switch;
    global $thread_nav_switch, $thread_left_l, $thread_left_m, $footer_icon_switch;
    
    $action = light_get_action();
    $light_config = light_get_config();
    $arronline = light_get_online_users();
    $rew = light_get_rew();
    
    $layout = light_get_layout_config();
    $main_switch = $layout['main_switch'];
    $index_header_switch = $layout['index_header_switch'];
    $icon_switch = $layout['icon_switch'];
    $thread_nav_switch = $layout['thread_nav_switch'];
    $thread_left_l = $layout['thread_left_l'];
    $thread_left_m = $layout['thread_left_m'];
    $footer_icon_switch = $layout['footer_icon_switch'];
}
