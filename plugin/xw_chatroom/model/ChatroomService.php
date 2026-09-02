<?php
!defined('DEBUG') AND exit('Access Denied');

class ChatroomService {
    const MSG_NORMAL = 0;
    const MSG_SHARE = 1;
    const MSG_SYSTEM = 2;
    const FILTER_NONE = 'none';
    const FILTER_WHITELIST = 'whitelist';
    const FILTER_BLACKLIST = 'blacklist';

    public static function defaults() {
        return array(
            'enabled' => 1,
            'show_nav' => 1,
            'msg_max_length' => 500,
            'msg_interval' => 3,
            'history_limit' => 50,
            'poll_interval' => 3000,
            'url_filter_mode' => 'none',
            'url_whitelist' => '',
            'url_blacklist' => '',
            'url_replace' => '[链接已屏蔽]',
            'allow_guest_read' => 1,
            'allow_guest_send' => 0,
        );
    }

    public static function settings() {
        $s = setting_get('xw_chatroom');
        return is_array($s) ? array_merge(self::defaults(), $s) : self::defaults();
    }

    public static function saveSettings($s) {
        return setting_set('xw_chatroom', $s);
    }

    // 频道
    public static function channels() {
        $list = db_find('xw_chat_channel', array('status' => 1), array('sort_order' => 1, 'id' => 1), 1, 100, 'id');
        return $list ? $list : array();
    }

    public static function allChannels() {
        $list = db_find('xw_chat_channel', array(), array('sort_order' => 1, 'id' => 1), 1, 200, 'id');
        return $list ? $list : array();
    }

    public static function channelById($id) {
        $id = intval($id);
        if($id <= 0) return array();
        return db_find_one('xw_chat_channel', array('id' => $id));
    }

    public static function channelBySlug($slug) {
        $slug = trim(strval($slug));
        if($slug === '') return array();
        return db_find_one('xw_chat_channel', array('slug' => $slug));
    }

    public static function defaultChannel() {
        $ch = db_find_one('xw_chat_channel', array('is_default' => 1, 'status' => 1));
        if($ch) return $ch;
        return db_find_one('xw_chat_channel', array('status' => 1), array('sort_order' => 1, 'id' => 1));
    }

    private static function genSlug($slug, $name, $excludeId = 0) {
        global $db;
        $slug = trim(strval($slug));
        if($slug === '') {
            $slug = preg_replace('/[^a-zA-Z0-9_-]/', '', strtolower($name));
            if($slug === '') $slug = 'ch';
            $slug = substr($slug, 0, 20);
        }
        $slug = preg_replace('/[^a-zA-Z0-9_-]/', '', $slug);
        $slug = substr($slug, 0, 32);
        if($slug === '') $slug = 'ch';
        $reserved = array('messages', 'send', 'share', 'history', 'index', 'chat');
        if(in_array($slug, $reserved, true)) $slug = $slug . '-ch';
        $base = $slug;
        $i = 1;
        while(true) {
            $sql = "SELECT id FROM {$db->tablepre}xw_chat_channel WHERE slug='" . $slug . "'";
            if($excludeId > 0) $sql .= " AND id!=" . intval($excludeId);
            $exist = db_sql_find_one($sql);
            if(!$exist) break;
            $slug = $base . '-' . $i;
            $i++;
            if($i > 50) { $slug = $base . '-' . substr(md5(time() . $i), 0, 6); break; }
        }
        return $slug;
    }

    public static function createChannel($arr) {
        $name = trim(strval(isset($arr['name']) ? $arr['name'] : ''));
        if($name === '') return array('ok' => false, 'message' => '频道名称不能为空');
        if(mb_strlen($name) > 32) return array('ok' => false, 'message' => '频道名称不能超过32个字');
        $slug = self::genSlug(isset($arr['slug']) ? $arr['slug'] : '', $name);
        $isDefault = intval(isset($arr['is_default']) ? $arr['is_default'] : 0) ? 1 : 0;
        if($isDefault) {
            db_update('xw_chat_channel', array('is_default' => 1), array('is_default' => 0));
        }
        $id = db_insert('xw_chat_channel', array(
            'name' => $name,
            'description' => mb_substr(trim(strval(isset($arr['description']) ? $arr['description'] : '')), 0, 255),
            'slug' => $slug,
            'sort_order' => intval(isset($arr['sort_order']) ? $arr['sort_order'] : 0),
            'is_default' => $isDefault,
            'status' => intval(isset($arr['status']) ? $arr['status'] : 1) ? 1 : 0,
            'online_count' => 0,
            'created' => time(),
        ));
        if(!$id) return array('ok' => false, 'message' => '创建失败');
        return array('ok' => true, 'id' => intval($id));
    }

    public static function updateChannel($id, $arr) {
        $id = intval($id);
        $ch = self::channelById($id);
        if(!$ch) return array('ok' => false, 'message' => '频道不存在');
        $name = trim(strval(isset($arr['name']) ? $arr['name'] : ''));
        if($name === '') return array('ok' => false, 'message' => '频道名称不能为空');
        if(mb_strlen($name) > 32) return array('ok' => false, 'message' => '频道名称不能超过32个字');
        $slug = self::genSlug(isset($arr['slug']) ? $arr['slug'] : '', $name, $id);
        $isDefault = intval(isset($arr['is_default']) ? $arr['is_default'] : 0) ? 1 : 0;
        if($isDefault && intval($ch['is_default']) !== 1) {
            db_update('xw_chat_channel', array('is_default' => 1), array('is_default' => 0));
        }
        db_update('xw_chat_channel', array('id' => $id), array(
            'name' => $name,
            'description' => mb_substr(trim(strval(isset($arr['description']) ? $arr['description'] : '')), 0, 255),
            'slug' => $slug,
            'sort_order' => intval(isset($arr['sort_order']) ? $arr['sort_order'] : 0),
            'is_default' => $isDefault,
            'status' => intval(isset($arr['status']) ? $arr['status'] : 1) ? 1 : 0,
        ));
        return array('ok' => true);
    }

    public static function deleteChannel($id) {
        $id = intval($id);
        $ch = self::channelById($id);
        if(!$ch) return array('ok' => false, 'message' => '频道不存在');
        if(intval($ch['is_default']) === 1) return array('ok' => false, 'message' => '默认频道不能删除');
        db_delete('xw_chat_channel', array('id' => $id));
        db_delete('xw_chat_message', array('channel_id' => $id));
        return array('ok' => true);
    }

    // 消息
    public static function messages($channelId, $lastId, $limit) {
        $channelId = intval($channelId);
        $lastId = intval($lastId);
        $limit = max(1, min(100, intval($limit)));
        if($lastId > 0) {
            $list = db_find('xw_chat_message', array('channel_id' => $channelId, 'id' => array('>' => $lastId)), array('id' => 1), 1, $limit, 'id');
        } else {
            $list = db_find('xw_chat_message', array('channel_id' => $channelId), array('id' => -1), 1, $limit, 'id');
            if($list) $list = array_reverse($list, true);
        }
        if(!$list) $list = array();
        $uids = array();
        foreach($list as $m) { $uids[intval($m['uid'])] = 1; }
        $usersMap = array();
        if($uids) {
            $users = user_find_by_uids(implode(',', array_keys($uids)));
            if(is_array($users)) {
                foreach($users as $u) {
                    if(is_array($u) && isset($u['uid'])) $usersMap[intval($u['uid'])] = $u;
                }
            }
        }
        $out = array();
        foreach($list as $m) {
            self::formatMessage($m);
            $u = isset($usersMap[$m['uid']]) ? $usersMap[$m['uid']] : array();
            $m['username'] = isset($u['username']) ? $u['username'] : ('用户' . $m['uid']);
            $m['avatar'] = isset($u['avatar_url']) ? $u['avatar_url'] : 'view/img/avatar.png';
            $m['gid'] = isset($u['gid']) ? intval($u['gid']) : 0;
            if(intval($m['type']) === self::MSG_SHARE && intval($m['ref_channel_id']) > 0) {
                $ref = self::channelById($m['ref_channel_id']);
                $m['ref_channel'] = $ref ? array('id' => intval($ref['id']), 'name' => $ref['name'], 'slug' => $ref['slug']) : null;
            }
            $out[] = $m;
        }
        return $out;
    }

    public static function formatMessage(&$msg) {
        if(!is_array($msg)) return;
        $msg['id'] = intval($msg['id']);
        $msg['channel_id'] = intval($msg['channel_id']);
        $msg['uid'] = intval($msg['uid']);
        $msg['type'] = intval($msg['type']);
        $msg['ref_channel_id'] = intval($msg['ref_channel_id']);
        $msg['created'] = intval($msg['created']);
        $msg['created_txt'] = date('H:i', intval($msg['created']));
        $msg['content'] = strval($msg['content']);
    }

    public static function sendMessage($channelId, $uid, $content, $type = 0, $refChannelId = 0) {
        $s = self::settings();
        if(empty($s['enabled'])) return array('ok' => false, 'message' => '聊天室未开启');
        $channelId = intval($channelId);
        $uid = intval($uid);
        $ch = self::channelById($channelId);
        if(!$ch || intval($ch['status']) !== 1) return array('ok' => false, 'message' => '频道不存在或已关闭');
        if($uid <= 0) {
            if(empty($s['allow_guest_send'])) return array('ok' => false, 'message' => '请先登录后再发送消息');
        }
        $content = trim(strval($content));
        if($content === '') return array('ok' => false, 'message' => '内容不能为空');
        $maxLen = max(1, min(5000, intval($s['msg_max_length'])));
        if(mb_strlen($content) > $maxLen) return array('ok' => false, 'message' => '内容超过最大长度');
        $interval = max(0, intval($s['msg_interval']));
        if($interval > 0 && $uid > 0) {
            $last = db_find_one('xw_chat_message', array('uid' => $uid), array('id' => -1));
            if($last && (time() - intval($last['created'])) < $interval) {
                return array('ok' => false, 'message' => '发送过快，请稍候');
            }
        }
        $content = self::filterUrl($content, $s);
        $type = intval($type);
        $refChannelId = intval($refChannelId);
        $id = db_insert('xw_chat_message', array(
            'channel_id' => $channelId,
            'uid' => $uid,
            'content' => $content,
            'type' => $type,
            'ref_channel_id' => $refChannelId,
            'created' => time(),
        ));
        if(!$id) return array('ok' => false, 'message' => '发送失败');
        return array('ok' => true, 'id' => intval($id));
    }

    // 网址白黑名单过滤
    public static function filterUrl($content, $s = null) {
        $s = $s ? $s : self::settings();
        $mode = strval(isset($s['url_filter_mode']) ? $s['url_filter_mode'] : 'none');
        if($mode === self::FILTER_NONE) return $content;
        $replace = strval(isset($s['url_replace']) ? $s['url_replace'] : '[链接已屏蔽]');
        if($replace === '') $replace = '[链接已屏蔽]';
        $pattern = '#https?://([a-zA-Z0-9][a-zA-Z0-9.\-]*\.[a-zA-Z]{2,})#i';
        $whitelist = self::parseDomains(strval(isset($s['url_whitelist']) ? $s['url_whitelist'] : ''));
        $blacklist = self::parseDomains(strval(isset($s['url_blacklist']) ? $s['url_blacklist'] : ''));
        if($mode === self::FILTER_WHITELIST) {
            $content = preg_replace_callback($pattern, function($m) use ($whitelist, $replace) {
                $domain = strtolower($m[1]);
                foreach($whitelist as $w) {
                    if($domain === $w || self::endsWith($domain, '.' . $w)) return $m[0];
                }
                return $replace;
            }, $content);
        } elseif($mode === self::FILTER_BLACKLIST) {
            $content = preg_replace_callback($pattern, function($m) use ($blacklist, $replace) {
                $domain = strtolower($m[1]);
                foreach($blacklist as $b) {
                    if($domain === $b || self::endsWith($domain, '.' . $b)) return $replace;
                }
                return $m[0];
            }, $content);
        }
        return $content;
    }

    private static function parseDomains($text) {
        $lines = preg_split('/\s+/', trim($text));
        $out = array();
        foreach($lines as $line) {
            $line = trim(strtolower($line));
            if($line !== '') $out[] = $line;
        }
        return $out;
    }

    private static function endsWith($str, $suffix) {
        return substr($str, -strlen($suffix)) === $suffix;
    }

    // 分享频道到目标频道
    public static function shareChannel($fromChannelId, $toChannelId, $uid) {
        $fromChannelId = intval($fromChannelId);
        $toChannelId = intval($toChannelId);
        $uid = intval($uid);
        if($fromChannelId <= 0 || $toChannelId <= 0) return array('ok' => false, 'message' => '参数错误');
        if($fromChannelId === $toChannelId) return array('ok' => false, 'message' => '不能分享到自己频道');
        $from = self::channelById($fromChannelId);
        $to = self::channelById($toChannelId);
        if(!$from || !$to) return array('ok' => false, 'message' => '频道不存在');
        if(intval($to['status']) !== 1) return array('ok' => false, 'message' => '目标频道已关闭');
        $content = '分享频道：' . $from['name'];
        return self::sendMessage($toChannelId, $uid, $content, self::MSG_SHARE, $fromChannelId);
    }

    // 定时刷新在线数（最近5分钟发消息的不同用户数）
    public static function refreshOnline() {
        global $db;
        $cutoff = time() - 300;
        $rows = db_sql_find("SELECT channel_id, COUNT(DISTINCT uid) AS cnt FROM {$db->tablepre}xw_chat_message WHERE created >= " . intval($cutoff) . " AND uid > 0 GROUP BY channel_id");
        $cntMap = array();
        if(is_array($rows)) {
            foreach($rows as $r) { $cntMap[intval($r['channel_id'])] = intval($r['cnt']); }
        }
        $chs = self::allChannels();
        foreach($chs as $ch) {
            $cnt = isset($cntMap[intval($ch['id'])]) ? $cntMap[intval($ch['id'])] : 0;
            db_update('xw_chat_channel', array('id' => $ch['id']), array('online_count' => $cnt));
        }
    }

    // 心跳：更新用户在线状态
    public static function heartbeat($channelId, $uid) {
        if($uid <= 0) return array('ok' => true, 'online' => 0);
        $channelId = intval($channelId);
        $uid = intval($uid);
        $now = time();
        $ch = self::channelById($channelId);
        if(!$ch || intval($ch['status']) !== 1) return array('ok' => false, 'message' => '频道不存在');
        try {
            global $db;
            $table = $db->tablepre . 'xw_chat_online';
            $r = db_exec("REPLACE INTO $table (uid, channel_id, last_heartbeat) VALUES ($uid, $channelId, $now)");
            if($r === FALSE) {
                error_log('[xw_chatroom] heartbeat db_exec failed: uid='.$uid.' channel_id='.$channelId);
                return array('ok' => false, 'message' => '心跳失败');
            }
        } catch (\Throwable $e) {
            error_log('[xw_chatroom] heartbeat exception: '.$e->getMessage().' uid='.$uid.' channel_id='.$channelId);
            return array('ok' => false, 'message' => '心跳异常: '.$e->getMessage());
        }
        return array('ok' => true, 'online' => self::getOnlineCount($channelId));
    }

    // 获取频道在线人数
    public static function getOnlineCount($channelId) {
        global $db;
        $channelId = intval($channelId);
        $timeout = self::settings();
        $timeout = intval(isset($timeout['online_timeout']) ? $timeout['online_timeout'] : 120);
        $cutoff = time() - $timeout;
        $r = db_sql_find_one("SELECT COUNT(*) AS cnt FROM {$db->tablepre}xw_chat_online WHERE channel_id = $channelId AND last_heartbeat >= $cutoff");
        return $r ? intval($r['cnt']) : 0;
    }

    // 获取频道在线用户列表（最多50个）
    public static function getOnlineUsers($channelId, $limit = 50) {
        global $db;
        $channelId = intval($channelId);
        $timeout = self::settings();
        $timeout = intval(isset($timeout['online_timeout']) ? $timeout['online_timeout'] : 120);
        $cutoff = time() - $timeout;
        $rows = db_sql_find("SELECT uid FROM {$db->tablepre}xw_chat_online WHERE channel_id = $channelId AND last_heartbeat >= $cutoff ORDER BY last_heartbeat DESC LIMIT $limit");
        $uids = array();
        if(is_array($rows)) {
            foreach($rows as $r) { $uids[] = intval($r['uid']); }
        }
        if(empty($uids)) return array();
        $users = user_find_by_uids(implode(',', $uids));
        $out = array();
        if(is_array($users)) {
            foreach($users as $u) {
                $out[] = array(
                    'uid' => intval($u['uid']),
                    'username' => $u['username'],
                    'avatar_url' => $u['avatar_url'],
                );
            }
        }
        return $out;
    }

    // 清理过期在线记录（cron 调用）
    public static function cleanOnline() {
        global $db;
        $timeout = self::settings();
        $timeout = intval(isset($timeout['online_timeout']) ? $timeout['online_timeout'] : 120);
        $cutoff = time() - $timeout;
        db_exec("DELETE FROM {$db->tablepre}xw_chat_online WHERE last_heartbeat < $cutoff");
    }

    // 更新已读位置
    public static function updateRead($channelId, $uid, $lastReadId) {
        if($uid <= 0) return array('ok' => true);
        $channelId = intval($channelId);
        $uid = intval($uid);
        $lastReadId = intval($lastReadId);
        try {
            global $db;
            $table = $db->tablepre . 'xw_chat_read';
            $r = db_exec("REPLACE INTO $table (uid, channel_id, last_read_id) VALUES ($uid, $channelId, $lastReadId)");
            if($r === FALSE) {
                error_log('[xw_chatroom] updateRead db_exec failed: uid='.$uid.' channel_id='.$channelId);
                return array('ok' => false, 'message' => '更新已读失败');
            }
        } catch (\Throwable $e) {
            error_log('[xw_chatroom] updateRead exception: '.$e->getMessage().' uid='.$uid.' channel_id='.$channelId);
            return array('ok' => false, 'message' => '更新已读异常: '.$e->getMessage());
        }
        return array('ok' => true);
    }

    // 获取未读数
    public static function getUnreadCount($channelId, $uid) {
        if($uid <= 0) return 0;
        $channelId = intval($channelId);
        $uid = intval($uid);
        global $db;
        $r = db_sql_find_one("SELECT last_read_id FROM {$db->tablepre}xw_chat_read WHERE uid = $uid AND channel_id = $channelId");
        $lastRead = $r ? intval($r['last_read_id']) : 0;
        $r2 = db_sql_find_one("SELECT MAX(id) AS maxid FROM {$db->tablepre}xw_chat_message WHERE channel_id = $channelId");
        $maxId = $r2 ? intval($r2['maxid']) : 0;
        return max(0, $maxId - $lastRead);
    }

    // 获取用户所有频道的未读总数
    public static function getTotalUnread($uid) {
        if($uid <= 0) return 0;
        $uid = intval($uid);
        global $db;
        $channels = self::allChannels();
        $total = 0;
        foreach($channels as $ch) {
            $total += self::getUnreadCount($ch['id'], $uid);
        }
        return $total;
    }
}
