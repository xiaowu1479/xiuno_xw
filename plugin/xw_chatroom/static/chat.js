(function () {
'use strict';
if (!window.xw_chat_DATA) return;
var D = window.xw_chat_DATA;
var I = window.xw_chat_I18N || {};
var EMOJIS = ['😀','😁','😂','🤣','😃','😄','😅','😆','😉','😊','😋','😎','😍','😘','🥰','😗','🤔','🤨','😐','😑','😶','🙄','😏','😣','😥','😮','🤐','😯','😪','😫','😴','😌','😛','😝','🤤','😒','😓','😔','😕','🙃','🤑','😲','☹️','🙁','😖','😞','😟','😤','😢','😭','😦','😧','😨','😩','🤯','😬','😰','😱','😳','🤪','😵','😡','😠','🤬','😷','🤒','🤕','🤢','🤮','🤧','😇','🤠','🤡','🥳','🥺','🤥','🤫','🤭','🧐','🤓','😈','👻','💀','👍','👎','👌','✌️','🤞','🤟','🤘','🤙','👈','👉','👆','👇','✋','🤚','🖐','🖖','👋','🤝','👏','🙌','👐','💪','❤️','🧡','💛','💚','💙','💜','🖤','🤍','🤎','💔','❣️','💕','💞','💓','💗','💖','💘','💝','🔥','⭐','🌟','✨','⚡','💯','🎉','🎊','🎁'];

var state = {
    current: D.current,
    lastId: D.lastId,
    uid: D.uid,
    canSend: D.canSend,
    pollTimer: null,
    heartbeatTimer: null,
    sending: false
};

function byId(id) { return document.getElementById(id); }
var messagesEl = byId('xw-chat-messages');
var inputEl = byId('xw-chat-input');
var formEl = byId('xw-chat-form');
var sendBtn = byId('xw-chat-send');
var emojiBtn = byId('xw-chat-emoji-btn');
var emojiPanel = byId('xw-chat-emoji-panel');
var sidebar = byId('xw-chat-sidebar');
var channelListEl = byId('xw-chat-channel-list');
var jumpBtn = byId('xw-chat-jump');
var jumpCountEl = byId('xw-chat-jump-count');
var onlinePanel = byId('xw-chat-online-panel');
var onlinePanelList = byId('xw-chat-online-panel-list');
var onlinePanelTitle = byId('xw-chat-online-panel-title');
var backdropEl = byId('xw-chat-backdrop');
// 用户正在往上看历史时，积压的未读新消息条数
var pendingNew = 0;
// 已渲染的最后一条消息所属日期，用来决定是否插入日期分隔条
var lastDay = null;
// 在线用户面板是否展开
var onlinePanelOpen = false;

function getCsrfToken() {
    var inp = document.querySelector('#xw-chat-form input[name="csrf_token"]');
    if (inp) return inp.value;
    return '';
}

function createEl(tag, className, text) {
    var el = document.createElement(tag);
    if (className) el.className = className;
    if (text !== undefined && text !== null) el.textContent = text;
    return el;
}

// 轻提示：替代 alert。alert 会阻塞页面，风格也和论坛其它地方不一致
function toast(msg, isError) {
    var box = byId('xw-chat-toast-box');
    if (!box) { alert(msg); return; }   // 兜底：容器缺失时仍能提示
    var el = createEl('div', 'xw-chat-toast' + (isError ? ' xw-chat-toast-error' : ''), msg);
    box.appendChild(el);
    setTimeout(function () {
        el.className += ' xw-chat-toast-out';
        setTimeout(function () {
            if (el.parentNode) el.parentNode.removeChild(el);
        }, 260);
    }, 2200);
}

function renderMessage(m) {
    var isSelf = (m.uid === state.uid && state.uid > 0);
    var cls = 'xw-chat-msg' + (isSelf ? ' xw-chat-msg-self' : '');

    var wrap = createEl('div', cls);
    wrap.setAttribute('data-id', String(m.id));

    var avatarBox = createEl('div', 'xw-chat-msg-avatar');
    if (m.avatar) {
        var img = createEl('img');
        img.setAttribute('src', String(m.avatar));
        avatarBox.appendChild(img);
    } else {
        // 兜底头像用主题图标字体，不用 emoji（emoji 与站内图标风格不一致）
        avatarBox.appendChild(createEl('i', 'icon-user'));
    }
    wrap.appendChild(avatarBox);

    var body = createEl('div', 'xw-chat-msg-body');
    body.appendChild(createEl('div', 'xw-chat-msg-meta', m.username + ' · ' + m.created_txt));

    if (m.type === 1 && m.ref_channel) {
        var bubbleWrap = createEl('div', 'xw-chat-msg-bubble xw-chat-msg-share-card-wrap');
        bubbleWrap.appendChild(createEl('div', null, m.content));
        var link = createEl('a', 'xw-chat-share-card');
        link.setAttribute('href', xn.url('chat-' + encodeURIComponent(m.ref_channel.slug)));
        link.textContent = '# ' + m.ref_channel.name + ' →';
        bubbleWrap.appendChild(link);
        body.appendChild(bubbleWrap);
    } else if (m.reply_to && m.reply_preview) {
        var bubbleWrap = createEl('div', 'xw-chat-msg-bubble xw-chat-msg-reply-wrap');
        var replyPreview = createEl('div', 'xw-chat-msg-reply-preview', m.reply_preview);
        bubbleWrap.appendChild(replyPreview);
        bubbleWrap.appendChild(createEl('div', 'xw-chat-msg-bubble', m.content));
        body.appendChild(bubbleWrap);
    } else {
        body.appendChild(createEl('div', 'xw-chat-msg-bubble', m.content));
    }

    wrap.appendChild(body);
    return wrap;
}

// 距底部 80px 以内算「贴着底部」：只有这时新消息才自动滚动，
// 否则用户正在翻历史，硬拽到底部会很打断人。
function nearBottom() {
    return (messagesEl.scrollHeight - messagesEl.scrollTop - messagesEl.clientHeight) < 80;
}

function showJump(n) {
    pendingNew = n;
    if (!jumpBtn) return;
    if (jumpCountEl) jumpCountEl.textContent = n;
    jumpBtn.hidden = false;
}

function hideJump() {
    pendingNew = 0;
    if (jumpBtn) jumpBtn.hidden = true;
}

function appendMessages(list) {
    if (!list || !list.length) return;
    var empty = messagesEl.querySelector('.xw-chat-empty');
    if (empty) empty.remove();
    // 追加之前先判断位置：贴底才自动滚
    var stick = nearBottom();
    var frag = document.createDocumentFragment();
    for (var i = 0; i < list.length; i++) {
        var m = list[i];
        // 日期变化处插一条分隔条（今天 / 昨天 / 10月5日），
        // 否则跨天看时每条只有 H:i，分不清是哪天的消息
        if (m.day_txt && m.day_txt !== lastDay) {
            frag.appendChild(createEl('div', 'xw-chat-day', m.day_label || m.day_txt));
            lastDay = m.day_txt;
        }
        frag.appendChild(renderMessage(m));
        if (m.id > state.lastId) state.lastId = m.id;
    }
    messagesEl.appendChild(frag);
    if (stick) {
        hideJump();
        scrollToBottom();
    } else {
        // 用户在看历史：不滚动，改为提示积压了多少条新消息
        showJump(pendingNew + list.length);
    }
    // 更新已读位置
    if (state.uid > 0 && state.current) {
        updateRead(state.current.id, state.lastId);
    }
}

function scrollToBottom() { messagesEl.scrollTop = messagesEl.scrollHeight; }

function renderInitial() {
    messagesEl.innerHTML = '';
    lastDay = null;
    if (!D.messages || !D.messages.length) {
        var emptyBox = createEl('div', 'xw-chat-empty text-center py-5 text-muted');
        emptyBox.textContent = I.empty || '暂无消息';
        messagesEl.appendChild(emptyBox);
        return;
    }
    appendMessages(D.messages);
}

// 心跳
function heartbeat() {
    if (!state.current || state.uid <= 0) return;
    var url = xn.url('chat-heartbeat-' + state.current.id);
    var fd = new FormData();
    fd.append('csrf_token', getCsrfToken());
    fetch(url, { method: 'POST', body: fd, credentials: 'same-origin' })
        .then(function (r) { return r.text().then(function(t) { return { ok: r.ok, text: t }; }); })
        .then(function (resp) {
            var d;
            try { d = JSON.parse(resp.text); } catch(e) { d = null; }
            if (d && d.ok && typeof d.online === 'number') {
                updateChannelOnline(state.current.id, d.online);
                // 心跳顺带返回所有频道的在线数，一次心跳就能刷新整个侧栏，
                // 不用为每个频道各发一次请求
                if (d.counts) {
                    for (var cid in d.counts) {
                        if (Object.prototype.hasOwnProperty.call(d.counts, cid)) {
                            updateChannelOnline(cid, d.counts[cid]);
                        }
                    }
                }
            } else {
                console.error('[chat heartbeat] failed:', resp.text);
            }
        })
        .catch(function (e) { console.error('[chat heartbeat] error:', e); });
}

// 更新频道在线数显示
function updateChannelOnline(channelId, count) {
    var link = channelListEl ? channelListEl.querySelector('[data-id="' + channelId + '"]') : null;
    if (link) {
        var onlineEl = link.querySelector('.xw-chat-channel-online');
        if (count > 0) {
            if (!onlineEl) {
                onlineEl = createEl('span', 'xw-chat-channel-online');
                onlineEl.title = '查看在线用户';
                link.appendChild(onlineEl);
            }
            onlineEl.textContent = count;
        } else if (onlineEl && onlineEl.parentNode) {
            // 0 人时不必留一个「0」徽章
            onlineEl.parentNode.removeChild(onlineEl);
        }
    }
    // 同时更新头部当前频道在线数
    // （channelId 可能来自 getAttribute 是字符串，所以统一转数字再比，避免永远不相等）
    if (state.current && parseInt(channelId, 10) === parseInt(state.current.id, 10)) {
        updateCurrentOnline(count);
    }
}

// 更新头部当前频道在线数
function updateCurrentOnline(count) {
    var badge = document.getElementById('xw-chat-current-online');
    var countEl = document.getElementById('xw-chat-current-online-count');
    if (badge && countEl) {
        countEl.textContent = count;
        badge.style.display = 'inline-flex';
    }
}

// 页面加载时立即获取在线数
function fetchOnlineCount(channelId) {
    var url = xn.url('chat-online-' + channelId);
    fetch(url, { credentials: 'same-origin' })
        .then(function (r) { return r.json(); })
        .then(function (d) {
            if (d && d.code === 0 && d.users) {
                updateCurrentOnline(d.users.length);
                updateChannelOnline(channelId, d.users.length);
            }
        })
        .catch(function () {});
}

// 更新已读
function updateRead(channelId, lastReadId) {
    if (state.uid <= 0) return;
    var url = xn.url('chat-read-' + channelId + '-' + lastReadId);
    fetch(url, { method: 'POST', credentials: 'same-origin' }).catch(function () {});
}

// 获取在线用户列表并展开面板（原来是 alert 弹一串名字）
function fetchOnlineUsers(channelId, channelName) {
    if (onlinePanelTitle) {
        onlinePanelTitle.textContent = channelName ? ('在线用户 · # ' + channelName) : '在线用户';
    }
    var url = xn.url('chat-online-' + channelId);
    fetch(url, { credentials: 'same-origin' })
        .then(function (r) { return r.json(); })
        .then(function (d) {
            showOnlineUsers((d && d.code === 0 && d.users) ? d.users : []);
        })
        .catch(function () { showOnlineUsers([]); });
}

function showOnlineUsers(users) {
    if (!onlinePanel || !onlinePanelList) return;
    onlinePanelList.innerHTML = '';
    if (!users || !users.length) {
        onlinePanelList.appendChild(createEl('div', 'xw-chat-online-empty', '暂无在线用户'));
    } else {
        for (var i = 0; i < users.length; i++) {
            var u = users[i];
            var row = createEl('a', 'xw-chat-online-item');
            row.setAttribute('href', xn.url('user-' + u.uid));
            var img = createEl('img');
            img.setAttribute('src', String(u.avatar_url || ''));
            img.setAttribute('alt', String(u.username || ''));
            row.appendChild(img);
            row.appendChild(createEl('span', null, u.username || ('用户' + u.uid)));
            onlinePanelList.appendChild(row);
        }
    }
    onlinePanel.hidden = false;
    onlinePanelOpen = true;
}

function closeOnlinePanel() {
    if (onlinePanel) onlinePanel.hidden = true;
    onlinePanelOpen = false;
}

// 轮询新消息
function poll() {
    if (!state.current) return;
    var url = xn.url('chat-messages-' + state.current.id + '-' + state.lastId);
    fetch(url, { credentials: 'same-origin' })
        .then(function (r) { return r.json(); })
        .then(function (d) {
            if (d && d.code === 0 && d.messages && d.messages.length) appendMessages(d.messages);
        })
        .catch(function () {});
}

function startPolling() {
    stopPolling();
    var interval = D.pollInterval || 3000;
    state.pollTimer = setInterval(poll, interval);
}
function stopPolling() {
    if (state.pollTimer) { clearInterval(state.pollTimer); state.pollTimer = null; }
}

// 启动心跳
function startHeartbeat() {
    stopHeartbeat();
    var interval = D.heartbeatInterval || 30000;
    heartbeat(); // 立即发一次
    state.heartbeatTimer = setInterval(heartbeat, interval);
}
function stopHeartbeat() {
    if (state.heartbeatTimer) { clearInterval(state.heartbeatTimer); state.heartbeatTimer = null; }
}

function sendMessage() {
    if (state.sending) return;
    if (!state.canSend) { toast(I.needLogin || '请先登录', true); return; }
    var content = inputEl.value.trim();
    if (!content) return;
    state.sending = true;
    sendBtn.disabled = true;
    function doneSending() { state.sending = false; sendBtn.disabled = false; }
    var fd = new FormData(formEl);
    var url = xn.url('chat-send-' + state.current.id);
    fetch(url, { method: 'POST', body: fd, credentials: 'same-origin' })
        .then(function (r) { return r.json(); })
        .then(function (d) {
            if (d && d.code === 0) {
                inputEl.value = '';
                autoGrow();
                poll();
            } else {
                toast((d && d.message) || (I.sendFail || '发送失败'), true);
            }
        }, function () {
            toast(I.sendFail || '发送失败', true);
        })
        // 不用 Promise.prototype.finally（ES2018，es6-shim 不补，老浏览器会报错）
        .then(doneSending, doneSending);
}

function autoGrow() {
    inputEl.style.height = 'auto';
    inputEl.style.height = Math.min(inputEl.scrollHeight, 120) + 'px';
}

function initEmoji() {
    var grid = createEl('div', 'xw-chat-emoji-grid');
    for (var i = 0; i < EMOJIS.length; i++) {
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.setAttribute('data-e', EMOJIS[i]);
        btn.textContent = EMOJIS[i];
        grid.appendChild(btn);
    }
    emojiPanel.appendChild(grid);
    emojiPanel.addEventListener('click', function (e) {
        var b = e.target.closest('button[data-e]');
        if (!b) return;
        var ch = b.getAttribute('data-e');
        var start = inputEl.selectionStart || inputEl.value.length;
        var end = inputEl.selectionEnd || inputEl.value.length;
        inputEl.value = inputEl.value.slice(0, start) + ch + inputEl.value.slice(end);
        inputEl.focus();
        var pos = start + ch.length;
        inputEl.setSelectionRange(pos, pos);
        autoGrow();
    });
    emojiBtn.addEventListener('click', function () { emojiPanel.hidden = !emojiPanel.hidden; });
    document.addEventListener('click', function (e) {
        if (emojiPanel.hidden) return;
        if (!emojiPanel.contains(e.target) && e.target !== emojiBtn && !emojiBtn.contains(e.target)) {
            emojiPanel.hidden = true;
        }
    });
}

function initShare() {
    var shareModalEl = document.getElementById('xw-chat-share-modal');
    var list = document.getElementById('xw-chat-share-list');
    if (!list || !shareModalEl) return;
    
    // Bootstrap 4: jQuery modal
    function openShareModal() {
        if (window.$ && $(shareModalEl).modal) {
            $(shareModalEl).modal('show');
        }
    }
    function closeShareModal() {
        if (window.$ && $(shareModalEl).modal) {
            $(shareModalEl).modal('hide');
        }
    }
    
    var shareBtn = document.getElementById('xw-chat-share-btn');
    if (shareBtn) {
        shareBtn.addEventListener('click', function (e) {
            e.preventDefault();
            openShareModal();
        });
    }
    
    // Click backdrop to close
    shareModalEl.addEventListener('click', function (e) {
        if (e.target === shareModalEl) closeShareModal();
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && $(shareModalEl).hasClass('show')) closeShareModal();
    });
    
    list.addEventListener('click', function (e) {
        var btn = e.target.closest('.xw-chat-share-target');
        if (!btn) return;
        var toId = btn.getAttribute('data-to');
        var fd = new FormData();
        fd.append('from_channel_id', state.current.id);
        fd.append('to_channel_id', toId);
        fd.append('csrf_token', getCsrfToken());
        fetch(xn.url('chat-share'), { method: 'POST', body: fd, credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                if (d && d.code === 0) {
                    toast(I.shareOk || '已分享');
                    closeShareModal();
                    poll();
                } else {
                    toast((d && d.message) || (I.shareFail || '分享失败'), true);
                }
            })
            .catch(function () { toast(I.shareFail || '分享失败', true); });
    });
}

function openSidebar() {
    sidebar.classList.add('open');
    // 只在抽屉模式（窄屏）下显示遮罩；PC 上侧栏本来就常驻
    if (backdropEl && window.innerWidth < 992) backdropEl.hidden = false;
}
function closeSidebar() {
    sidebar.classList.remove('open');
    if (backdropEl) backdropEl.hidden = true;
}

function initSidebar() {
    var openBtn = byId('xw-chat-open-sidebar');
    var closeBtn = byId('xw-chat-close-sidebar');
    if (openBtn) openBtn.addEventListener('click', openSidebar);
    if (closeBtn) closeBtn.addEventListener('click', closeSidebar);
    if (backdropEl) backdropEl.addEventListener('click', closeSidebar);
    document.addEventListener('keydown', function (e) {
        if (e.key !== 'Escape') return;
        closeSidebar();
        closeOnlinePanel();
    });

    // 点频道的在线数 → 展开在线用户面板。
    // 这个徽章在 <a> 内部，必须 preventDefault，否则点完还会跳去那个频道
    if (channelListEl) {
        channelListEl.addEventListener('click', function (e) {
            var onlineEl = e.target.closest ? e.target.closest('.xw-chat-channel-online') : null;
            if (!onlineEl) return;
            e.preventDefault();
            var link = onlineEl.closest('[data-id]');
            if (!link) return;
            var nameEl = link.querySelector('.xw-chat-channel-name');
            fetchOnlineUsers(link.getAttribute('data-id'), nameEl ? nameEl.textContent : '');
        });
    }

    // 点头部在线徽章 → 看当前频道的在线用户；再点一次收起
    var headBadge = byId('xw-chat-current-online');
    if (headBadge) {
        headBadge.addEventListener('click', function (e) {
            e.preventDefault();
            if (onlinePanelOpen) { closeOnlinePanel(); return; }
            if (!state.current) return;
            fetchOnlineUsers(state.current.id, state.current.name);
        });
    }

    // 点面板与在线徽章之外的地方收起面板
    document.addEventListener('click', function (e) {
        if (!onlinePanelOpen) return;
        if (onlinePanel && onlinePanel.contains(e.target)) return;
        if (e.target.closest && e.target.closest('.xw-chat-online-badge, .xw-chat-channel-online')) return;
        closeOnlinePanel();
    });
}

function init() {
    renderInitial();
    initEmoji();
    initShare();
    initSidebar();
    if (formEl) {
        formEl.addEventListener('submit', function (e) { e.preventDefault(); sendMessage(); });
    }
    if (inputEl) {
        inputEl.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendMessage(); }
        });
        inputEl.addEventListener('input', autoGrow);
    }
    startPolling();
    startHeartbeat();
    // 初始在线数已由服务端渲染，这里只做一次校正
    // （不用可选链 ?. —— 那是 ES2020 语法，老浏览器会让整份脚本解析失败）
    var onlineEl = document.getElementById('xw-chat-current-online-count');
    var initialOnline = parseInt((onlineEl && onlineEl.textContent) || '0', 10);
    if (initialOnline > 0) updateCurrentOnline(initialOnline);
    if (state.current) fetchOnlineCount(state.current.id);

    // 「N 条新消息」按钮：点击回到底部；手动滚回底部时也自动收起
    if (jumpBtn) {
        jumpBtn.addEventListener('click', function () {
            hideJump();
            scrollToBottom();
        });
    }
    messagesEl.addEventListener('scroll', function () {
        if (pendingNew > 0 && nearBottom()) hideJump();
    });

    document.addEventListener('visibilitychange', function () {
        if (document.hidden) { stopPolling(); stopHeartbeat(); } else {
            // poll() 需要手动调一次（startPolling 只挂定时器，不会立即执行）；
            // 心跳则不用——startHeartbeat() 内部已经会立即发一次，再调 heartbeat() 会重复发请求
            poll();
            startPolling();
            startHeartbeat();
        }
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
} else {
    init();
}
})();