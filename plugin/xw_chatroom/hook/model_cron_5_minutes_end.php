if(!class_exists('ChatroomService', false)) include_once APP_PATH.'plugin/xw_chatroom/model/ChatroomService.php';
if(class_exists('ChatroomService', false)) {
    ChatroomService::refreshOnline();
    ChatroomService::cleanOnline();
}
