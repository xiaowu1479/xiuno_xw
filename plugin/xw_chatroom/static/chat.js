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
        avatarBox.textContent = '👤';
    }
    wrap.appendChild(avatarBox);

    var body = createEl('div', 'xw-chat-msg-body');
    body.appendChild(createEl('div', 'xw-chat-msg-meta', m.username + ' · ' + m.created_txt));

    if (m.type === 1 && m.ref_channel) {
        var bubbleWrap = createEl('div', 'xw-chat-msg-bubble xw-chat-msg-share-card-wrap');
        bubbleWrap.appendChild(createEl('div', null, m.content));
        var link = createEl('a', 'xw-chat-share-card');
        link.setAttribute('href', 'chat-' + encodeURIComponent(m.ref_channel.slug) + '.htm');
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

function appendMessages(list) {
    if (!list || !list.length) return;
    var empty = messagesEl.querySelector('.xw-chat-empty');
    if (empty) empty.remove();
    var frag = document.createDocumentFragment();
    for (var i = 0; i < list.length; i++) {
        frag.appendChild(renderMessage(list[i]));
        if (list[i].id > state.lastId) state.lastId = list[i].id;
    }
    messagesEl.appendChild(frag);
    scrollToBottom();
    // 更新已读位置
    if (state.uid > 0 && state.current) {
        updateRead(state.current.id, state.lastId);
    }
}

function scrollToBottom() { messagesEl.scrollTop = messagesEl.scrollHeight; }

function renderInitial() {
    messagesEl.replaceChildren();
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
    var url = 'chat-heartbeat-' + state.current.id + '.htm';
    var fd = new FormData();
    fd.append('csrf_token', getCsrfToken());
    fetch(url, { method: 'POST', body: fd, credentials: 'same-origin' })
        .then(function (r) { return r.text().then(function(t) { return { ok: r.ok, text: t }; }); })
        .then(function (resp) {
            var d;
            try { d = JSON.parse(resp.text); } catch(e) { d = null; }
            if (d && d.ok && typeof d.online === 'number') {
                updateChannelOnline(state.current.id, d.online);
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
        if (!onlineEl) {
            onlineEl = createEl('span', 'xw-chat-channel-online');
            link.appendChild(onlineEl);
        }
        onlineEl.textContent = count;
    }
    // 同时更新头部当前频道在线数
    if (channelId === state.current?.id) {
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
    var url = 'chat-online-' + channelId + '.htm';
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
    var url = 'chat-read-' + channelId + '-' + lastReadId + '.htm';
    fetch(url, { method: 'POST', credentials: 'same-origin' }).catch(function () {});
}

// 获取在线用户列表（可选：点击在线数显示）
function fetchOnlineUsers(channelId) {
    var url = 'chat-online-' + channelId + '.htm';
    fetch(url, { credentials: 'same-origin' })
        .then(function (r) { return r.json(); })
        .then(function (d) {
            if (d && d.code === 0 && d.users && d.users.length) {
                showOnlineUsers(d.users);
            }
        })
        .catch(function () {});
}

function showOnlineUsers(users) {
    // 简单实现：alert 显示，后续可做成弹层
    var names = users.map(function(u) { return u.username; }).join(', ');
    alert('在线用户：' + names);
}

// 轮询新消息
function poll() {
    if (!state.current) return;
    var url = 'chat-messages-' + state.current.id + '-' + state.lastId + '.htm';
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
    if (!state.canSend) { alert(I.needLogin || '请先登录'); return; }
    var content = inputEl.value.trim();
    if (!content) return;
    state.sending = true;
    sendBtn.disabled = true;
    var fd = new FormData(formEl);
    var url = 'chat-send-' + state.current.id + '.htm';
    fetch(url, { method: 'POST', body: fd, credentials: 'same-origin' })
        .then(function (r) { return r.json(); })
        .then(function (d) {
            if (d && d.code === 0) {
                inputEl.value = '';
                autoGrow();
                poll();
            } else {
                alert((d && d.message) || (I.sendFail || '发送失败'));
            }
        })
        .catch(function () { alert(I.sendFail || '发送失败'); })
        .finally(function () { state.sending = false; sendBtn.disabled = false; });
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
        fetch('chat-share.htm', { method: 'POST', body: fd, credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                if (d && d.code === 0) {
                    alert(I.shareOk || '已分享');
                    closeShareModal();
                    poll();
                } else {
                    alert((d && d.message) || (I.shareFail || '分享失败'));
                }
            })
            .catch(function () { alert(I.shareFail || '分享失败'); });
    });
}

function initSidebar() {
    var openBtn = byId('xw-chat-open-sidebar');
    var closeBtn = byId('xw-chat-close-sidebar');
    if (openBtn) openBtn.addEventListener('click', function () { sidebar.classList.add('open'); });
    if (closeBtn) closeBtn.addEventListener('click', function () { sidebar.classList.remove('open'); });
    // 点击在线数显示在线用户
    if (channelListEl) {
        channelListEl.addEventListener('click', function (e) {
            var onlineEl = e.target.closest('.xw-chat-channel-online');
            if (onlineEl) {
                var link = onlineEl.closest('[data-id]');
                if (link) {
                    var channelId = link.getAttribute('data-id');
                    fetchOnlineUsers(channelId);
                }
            }
        });
    }
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
    console.log('[chat] init uid=' + state.uid + ' current=' + (state.current ? state.current.id : 'none'));
    // 初始在线数已由服务端渲染，直接显示
    var initialOnline = parseInt(document.getElementById('xw-chat-current-online-count')?.textContent || '0', 10);
    if (initialOnline > 0) updateCurrentOnline(initialOnline);
    if (state.current) fetchOnlineCount(state.current.id);
    document.addEventListener('visibilitychange', function () {
        if (document.hidden) { stopPolling(); stopHeartbeat(); } else { poll(); heartbeat(); startPolling(); startHeartbeat(); }
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
} else {
    init();
}
})();