<?php
require 'include/head.php';
session('WEBSITE-LOGIN');

$isLocal = (strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false
         || strpos($_SERVER['HTTP_HOST'] ?? '', '127.0.0.1') !== false);
$ajaxUrl = ($isLocal ? 'http://localhost/kapraa' : 'https://kapraa.pk') . '/admin/live_chat_ajax.php';
?>

<style>
/* Hide footer & fix height gap on live chat page only */
.footer { display:none !important; }
</style>

<div class="content-body">
<div class="container-fluid p-0" style="height:calc(100vh - var(--height, 80px));min-height:400px;display:flex;flex-direction:column;">

  <!-- Page Header -->
  <div class="row mx-0 px-3 align-items-center" style="height:46px;flex-shrink:0;background:#fff;border-bottom:1px solid #eee;">
    <div class="col">
      <h6 class="mb-0" style="font-size:14px;font-weight:700;">
        <i class="fa fa-comments text-success me-2"></i>Live Chat Inbox
        <span id="badgeWaiting" class="badge bg-danger ms-2" style="display:none;font-size:10px;">0 waiting</span>
      </h6>
    </div>
    <div class="col-auto">
      <span id="adminStatus" class="badge bg-success px-2 py-1" style="font-size:11px;">
        <i class="fa fa-circle me-1" style="font-size:7px;"></i>Online
      </span>
    </div>
  </div>

  <!-- Main Layout -->
  <div class="d-flex" style="flex:1;min-height:0;overflow:hidden;">

    <!-- ── LEFT: Chat List ──────────────────────────────────── -->
    <div id="chatList" style="width:280px;min-width:240px;flex-shrink:0;border-right:1px solid #eee;overflow-x:hidden;background:#fafafa;display:flex;flex-direction:column;">
      <!-- Normal header -->
      <div id="chatListHdr" style="padding:8px 10px 8px 14px;border-bottom:1px solid #eee;background:#fff;flex-shrink:0;display:flex;align-items:center;justify-content:space-between;">
        <span style="font-size:11px;font-weight:700;color:#777;text-transform:uppercase;letter-spacing:.5px;">All Chats</span>
        <button id="btnSelectMode" title="Select chats to delete"
                style="background:none;border:1px solid #ddd;border-radius:12px;padding:2px 10px;
                       font-size:10.5px;color:#888;cursor:pointer;transition:all .15s;">
          <i class="fa fa-check-square-o me-1"></i>Select
        </button>
      </div>
      <!-- Selection bar (shown in select mode) -->
      <div id="chatSelBar" style="display:none;padding:6px 10px;border-bottom:1px solid #ffe082;background:#fffde7;align-items:center;gap:8px;flex-shrink:0;">
        <label style="display:flex;align-items:center;gap:5px;font-size:11px;color:#555;cursor:pointer;user-select:none;margin:0;">
          <input type="checkbox" id="chkSelAll" style="accent-color:#25d366;cursor:pointer;">
          <span>All</span>
        </label>
        <span id="selCount" style="font-size:11px;color:#888;flex:1;">0 selected</span>
        <button id="btnDeleteSelected"
                style="background:#e74c3c;color:#fff;border:none;border-radius:10px;
                       padding:4px 12px;font-size:11px;font-weight:700;cursor:pointer;
                       opacity:.4;pointer-events:none;transition:opacity .15s;">
          <i class="fa fa-trash me-1"></i>Delete
        </button>
        <button id="btnCancelSel"
                style="background:none;border:none;font-size:11px;color:#aaa;cursor:pointer;font-weight:700;padding:2px 4px;">
          Cancel
        </button>
      </div>
      <div id="chatItems" style="flex:1;overflow-y:auto;">
        <div style="padding:30px 16px;text-align:center;color:#ccc;font-size:12px;">
          <i class="fa fa-inbox fa-2x d-block mb-2"></i>
          No active chats yet
        </div>
      </div>
    </div>

    <!-- ── RIGHT: Conversation ─────────────────────────────── -->
    <div style="display:flex;flex-direction:column;flex:1;min-width:0;overflow:hidden;background:#f8f9fa;">

      <!-- Active conversation (hidden until chat selected) -->
      <div id="convoPane" style="display:none;flex-direction:column;height:100%;">

        <!-- Convo header -->
        <div id="convoHeader" style="padding:8px 16px;border-bottom:1px solid #eee;background:#fff;display:flex;align-items:center;gap:10px;flex-shrink:0;">
          <div style="width:34px;height:34px;border-radius:50%;background:linear-gradient(135deg,#25d366,#128C7E);
                      flex-shrink:0;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:800;font-size:14px;">
            V
          </div>
          <div class="flex-grow-1" style="min-width:0;">
            <div id="convoName" style="font-weight:700;font-size:13px;color:#222;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">Visitor</div>
            <div id="convoStatus" style="font-size:10px;color:#25d366;">
              <i class="fa fa-circle" style="font-size:6px;"></i> Waiting for reply
            </div>
          </div>
          <button id="btnCloseChat" class="btn btn-sm btn-outline-danger" style="font-size:11px;padding:3px 10px;">
            <i class="fa fa-times me-1"></i>End
          </button>
          <button id="btnDeleteChat" class="btn btn-sm btn-outline-secondary" style="font-size:11px;padding:3px 10px;" title="Delete this chat permanently">
            <i class="fa fa-trash me-1"></i>Del
          </button>
        </div>

        <!-- Messages -->
        <div id="convoMsgs"
             style="flex:1;min-height:0;overflow-y:auto;padding:14px 16px;background:#f8f9fa;display:flex;flex-direction:column;gap:8px;">
        </div>

        <!-- Quick Replies -->
        <div id="quickRepliesBar" style="padding:6px 14px 4px;border-top:1px solid #eee;background:#fafafa;display:none;flex-shrink:0;">
          <div style="font-size:9.5px;font-weight:700;color:#aaa;text-transform:uppercase;letter-spacing:.5px;margin-bottom:4px;">
            <i class="fa fa-bolt me-1" style="color:#25d366;"></i>Quick Replies
            <button id="btnAddQuick" title="Add quick reply" style="background:none;border:none;cursor:pointer;color:#25d366;font-size:10px;float:right;font-weight:700;padding:0;">
              <i class="fa fa-plus"></i> Add
            </button>
          </div>
          <div id="quickList" style="display:flex;flex-wrap:wrap;gap:4px;max-height:60px;overflow-y:auto;"></div>
        </div>

        <!-- Media preview (admin) -->
        <div id="adminMediaPre" style="display:none;align-items:center;gap:8px;padding:5px 14px;background:#f4fdf7;border-top:1px solid rgba(37,211,102,.15);flex-shrink:0;">
          <img id="adminPreImg" src="" alt="" style="max-height:45px;max-width:60px;border-radius:5px;object-fit:cover;display:none;">
          <span id="adminPreLbl" style="font-size:11px;color:#555;flex:1;"></span>
          <button id="adminPreClr" style="background:none;border:none;font-size:18px;color:#aaa;cursor:pointer;line-height:1;">&times;</button>
        </div>
        <!-- Voice recording indicator (admin) -->
        <div id="adminVoiceRec" style="display:none;align-items:center;gap:8px;padding:5px 14px;background:#fff8f8;border-top:1px solid #fddada;flex-shrink:0;">
          <span id="adminRecDot" style="width:8px;height:8px;border-radius:50%;background:#e74c3c;flex-shrink:0;animation:adm-blink .8s infinite;"></span>
          <span id="adminRecTime" style="font-size:12px;font-weight:700;color:#e74c3c;min-width:28px;">0:00</span>
          <span style="flex:1;font-size:10px;color:#888;">Recording… release to send</span>
          <button id="adminRecCancel" style="background:none;border:none;font-size:11px;color:#e74c3c;cursor:pointer;font-weight:700;">Cancel</button>
        </div>
        <!-- Reply box -->
        <div id="replyInputRow" style="padding:10px 14px 12px;border-top:1px solid #f0f0f0;background:#fafafa;display:flex;align-items:center;gap:8px;flex-shrink:0;">
          <input type="file" id="adminFileInp" accept="image/*" style="display:none">
          <!-- Left icon buttons -->
          <button id="btnSendImg" title="Send image"
                  style="width:38px;height:38px;flex-shrink:0;border-radius:50%;border:1.5px solid #cce8d4;
                         background:#fff;color:#25d366;display:flex;align-items:center;justify-content:center;
                         cursor:pointer;transition:all .18s;box-shadow:0 1px 4px rgba(37,211,102,.1);">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
          </button>
          <button id="btnSendVoice" title="Hold to record voice"
                  style="width:38px;height:38px;flex-shrink:0;border-radius:50%;border:1.5px solid #cce8d4;
                         background:#fff;color:#25d366;display:flex;align-items:center;justify-content:center;
                         cursor:pointer;transition:all .18s;box-shadow:0 1px 4px rgba(37,211,102,.1);user-select:none;-webkit-user-select:none;">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor"><rect x="9" y="2" width="6" height="12" rx="3"/><path fill="none" stroke="currentColor" stroke-width="2" d="M19 10a7 7 0 01-14 0"/><line stroke="currentColor" stroke-width="2" x1="12" y1="17" x2="12" y2="21"/><line stroke="currentColor" stroke-width="2" x1="8" y1="21" x2="16" y2="21"/></svg>
          </button>
          <!-- Full-width pill input -->
          <textarea id="replyInput" rows="1" placeholder="Type your message…"
                    style="flex:1;resize:none;border:1.5px solid #e4e4e4;border-radius:24px;
                           padding:10px 18px;font-size:13px;outline:none;background:#fff;
                           transition:border-color .2s,box-shadow .2s;line-height:1.5;
                           min-height:44px;max-height:110px;box-shadow:0 1px 4px rgba(0,0,0,.05);"
                    onfocus="this.style.borderColor='#25d366';this.style.boxShadow='0 0 0 3px rgba(37,211,102,.12)'"
                    onblur="this.style.borderColor='#e4e4e4';this.style.boxShadow='0 1px 4px rgba(0,0,0,.05)'"></textarea>
          <!-- Quick replies icon -->
          <button id="btnToggleQuick" title="Quick replies"
                  style="width:38px;height:38px;flex-shrink:0;border-radius:50%;border:1.5px solid #cce8d4;
                         background:#fff;color:#25d366;display:flex;align-items:center;justify-content:center;
                         cursor:pointer;transition:all .18s;box-shadow:0 1px 4px rgba(37,211,102,.1);">
            <i class="fa fa-bolt" style="font-size:13px;color:#25d366;"></i>
          </button>
          <!-- Send circle button -->
          <button id="btnSendReply" title="Send"
                  style="width:44px;height:44px;flex-shrink:0;border-radius:50%;
                         background:linear-gradient(135deg,#25d366,#128C7E);
                         color:#fff;border:none;display:flex;align-items:center;justify-content:center;
                         cursor:pointer;transition:opacity .2s,transform .15s;
                         box-shadow:0 3px 10px rgba(37,211,102,.4);">
            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
          </button>
        </div>

      </div>
    </div>
  </div>
</div>
</div>

<style>
/* ── Live Chat Layout ─────────────────────────────────────── */
/* Ensure footer stays visible */
.content-body { overflow:hidden; }

.chat-item {
  padding:10px 12px; border-bottom:1px solid #f0f0f0; cursor:pointer;
  transition:background .15s; background:#fff;
}
.chat-item:hover { background:#f5fdf8; }
.chat-item.active { background:#eaf8ef; border-left:3px solid #25d366; padding-left:9px; }
.chat-item-name { font-weight:700; font-size:12.5px; color:#222; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.chat-item-preview { font-size:11px; color:#999; margin-top:2px;
  white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:220px; }
.chat-item-meta { font-size:9.5px; color:#ccc; margin-top:3px; display:flex; gap:5px; align-items:center; }
.badge-waiting { background:#ff4444; color:#fff; font-size:8.5px; font-weight:800;
  padding:1px 6px; border-radius:8px; }
.badge-active  { background:#25d366; color:#fff; font-size:8.5px; font-weight:800;
  padding:1px 6px; border-radius:8px; }

/* Message bubbles */
.msg-row { display:flex; width:100%; align-items:flex-end; }
.msg-row.admin { justify-content:flex-end; }
.msg-row.user  { justify-content:flex-start; }
.msg-wrap { max-width:68%; min-width:0; }
.msg-row.admin .msg-wrap { text-align:right; }
.msg-bub {
  display:inline-block; padding:8px 13px; border-radius:16px;
  font-size:13px; line-height:1.55; word-break:break-word;
  max-width:100%;
}
.msg-bub.user  { background:#fff; border:1px solid #e8e8e8; border-bottom-left-radius:3px; color:#333; }
.msg-bub.admin { background:linear-gradient(135deg,#25d366,#128C7E); color:#fff; border-bottom-right-radius:3px; }
.msg-time { font-size:9.5px; color:#ccc; margin-top:3px; }
.msg-row.admin .msg-time { text-align:right; }

/* Chat item avatar */
.chat-av {
  width:38px; height:38px; border-radius:50%; color:#fff; flex-shrink:0;
  display:flex; align-items:center; justify-content:center;
  font-size:15px; font-weight:800; margin-right:9px; letter-spacing:0;
}

/* WhatsApp-style voice player */
.wv-player {
  display:flex; align-items:center; gap:9px;
  padding:4px 2px; min-width:180px; max-width:230px;
}
.wv-btn {
  width:36px; height:36px; border-radius:50%; border:none;
  display:flex; align-items:center; justify-content:center;
  cursor:pointer; flex-shrink:0; font-size:13px; transition:transform .15s;
  line-height:1;
}
.wv-btn:active { transform:scale(.93); }
.msg-bub.user  .wv-btn { background:#e8f5e9; color:#128C7E; }
.msg-bub.admin .wv-btn { background:rgba(255,255,255,.25); color:#fff; }
.wv-body { flex:1; display:flex; flex-direction:column; gap:4px; }
.wv-bars { display:flex; align-items:center; gap:2.5px; height:22px; }
.wv-bar  { width:3px; border-radius:3px; flex-shrink:0; transition:opacity .12s; }
.msg-bub.user  .wv-bar { background:#25d366; }
.msg-bub.admin .wv-bar { background:rgba(255,255,255,.85); }
.wv-foot { display:flex; justify-content:space-between; font-size:10px; }
.msg-bub.user  .wv-foot { color:rgba(0,0,0,.45); }
.msg-bub.admin .wv-foot { color:rgba(255,255,255,.7); }

/* User-deleted banner */
.user-deleted-banner {
  display:flex; align-items:center; gap:8px;
  background:#fff3cd; border:1px solid #ffc107; border-radius:8px;
  padding:8px 14px; margin:8px 12px; font-size:12px; color:#856404;
}

/* Quick reply chips */
.qr-chip {
  display:inline-flex; align-items:center; gap:4px;
  padding:3px 8px 3px 7px; border-radius:16px;
  background:rgba(37,211,102,.07); border:1px solid #c8e8d4;
  font-size:10.5px; color:#1a6e3c; cursor:pointer;
  transition:background .15s; white-space:nowrap; max-width:200px;
  overflow:hidden; text-overflow:ellipsis;
}
.qr-chip:hover { background:#dcfce7; border-color:#25d366; }
.qr-chip-del {
  flex-shrink:0; font-size:9.5px; color:#bbb; cursor:pointer;
  line-height:1; padding:1px 2px; border-radius:3px;
}
.qr-chip-del:hover { color:#e74c3c; background:#fef2f2; }
</style>

<script>
(function(){
  var AJAX = '<?= $ajaxUrl ?>';
  var activeChatId = null;
  var lastMsgId    = 0;
  var lastPollId   = 0;
  var pollTimer    = null;
  var listTimer    = null;
  var adminSelMode = false;

  /* ── Helpers ─────────────────────────────────────────────── */
  function post(data, cb) {
    fetch(AJAX, { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(data) })
      .then(function(r){ return r.json(); }).then(cb).catch(function(){});
  }
  function esc(s) { var d=document.createElement('div'); d.textContent=String(s); return d.innerHTML; }

  /* ── Render chat list ─────────────────────────────────────── */
  function loadList() {
    post({action:'list'}, function(res) {
      if (!res.ok) return;
      var el = document.getElementById('chatItems');
      var waiting = res.chats.filter(function(c){ return c.status === 'waiting'; }).length;
      var bw = document.getElementById('badgeWaiting');
      if (waiting > 0) { bw.style.display=''; bw.textContent = waiting + ' waiting'; }
      else bw.style.display='none';

      if (!res.chats.length) {
        el.innerHTML = '<div style="padding:40px 20px;text-align:center;color:#bbb;font-size:13px;">'
          + '<i class="fa fa-inbox fa-2x d-block mb-2"></i>No active chats yet</div>';
        return;
      }
      el.innerHTML = res.chats.map(function(c) {
        var isDeleted = parseInt(c.user_deleted) === 1;
        var badge = isDeleted
          ? '<span style="background:#dc3545;color:#fff;font-size:8px;font-weight:800;padding:1px 5px;border-radius:8px;">DELETED</span>'
          : c.status === 'waiting'
            ? '<span class="badge-waiting">WAITING</span>'
            : c.status === 'closed'
              ? '<span style="background:#aaa;color:#fff;font-size:8.5px;font-weight:800;padding:1px 6px;border-radius:8px;">CLOSED</span>'
              : '<span class="badge-active">ACTIVE</span>';
        var unread = parseInt(c.unread) > 0
          ? '<span style="background:#ff4444;color:#fff;border-radius:50%;width:16px;height:16px;display:inline-flex;align-items:center;justify-content:center;font-size:9px;font-weight:800;">' + c.unread + '</span>'
          : '';
        var lastType = c.last_type || 'text';
        var rawPreview = c.last_msg || '';
        var senderPfx  = (c.last_msg && c.last_sender === 'admin') ? 'You: ' : '';
        var previewHTML;
        if (isDeleted) {
          previewHTML = '<i class="fa fa-trash-o" style="color:#dc3545;margin-right:4px;font-size:11px;"></i><em style="color:#dc3545;">Deleted by user</em>';
        } else if (lastType === 'image') {
          previewHTML = '<i class="fa fa-image" style="color:#25d366;margin-right:4px;font-size:11px;"></i>' + senderPfx + 'Image';
        } else if (lastType === 'voice') {
          previewHTML = '<i class="fa fa-microphone" style="color:#25d366;margin-right:4px;font-size:11px;"></i>' + senderPfx + 'Voice message';
        } else {
          var txt = rawPreview.substring(0,45) + (rawPreview.length>45?'…':'');
          previewHTML = c.last_msg ? esc(senderPfx + txt) : 'No messages yet';
        }
        var cbHtml = adminSelMode
          ? '<input type="checkbox" class="adm-chat-cb" data-id="' + c.id + '" style="accent-color:#25d366;width:14px;height:14px;flex-shrink:0;cursor:pointer;margin-right:6px;" onclick="event.stopPropagation();">'
          : '';
        // Avatar: first letter of name with a consistent color
        var avColors = ['#e91e63','#9c27b0','#673ab7','#3f51b5','#2196f3','#00897b','#f57c00','#d32f2f','#00796b','#1565c0'];
        var vn = (c.visitor_name || 'V');
        var avLetter = vn.trim().charAt(0).toUpperCase();
        var avColor  = avColors[avLetter.charCodeAt(0) % avColors.length];
        var avHtml = '<div class="chat-av" style="background:' + avColor + ';">' + avLetter + '</div>';
        return '<div class="chat-item' + (c.id == activeChatId && !adminSelMode ? ' active' : '') + '" data-id="' + c.id + '" data-name="' + esc(c.visitor_name) + '" style="display:flex;align-items:center;">'
          + cbHtml
          + avHtml
          + '<div style="flex:1;min-width:0;">'
          + '<div class="d-flex align-items-center justify-content-between">'
          + '<div class="chat-item-name">' + esc(c.visitor_name) + '</div>'
          + '<div class="d-flex align-items-center gap-1">' + unread + badge + '</div>'
          + '</div>'
          + '<div class="chat-item-preview">' + previewHTML + '</div>'
          + '<div class="chat-item-meta"><i class="fa fa-clock-o"></i>' + esc(c.started) + '</div>'
          + '</div>'
          + '</div>';
      }).join('');

      // Re-bind click
      el.querySelectorAll('.chat-item').forEach(function(item) {
        item.addEventListener('click', function() {
          if (adminSelMode) {
            var cb = item.querySelector('.adm-chat-cb');
            if (cb) { cb.checked = !cb.checked; adminSelUpdateBar(); }
            return;
          }
          openChat(parseInt(this.dataset.id), this.dataset.name);
        });
      });
      // Checkbox change also updates bar
      el.querySelectorAll('.adm-chat-cb').forEach(function(cb) {
        cb.addEventListener('change', adminSelUpdateBar);
      });
      if (adminSelMode) adminSelUpdateBar();
    });
  }

  /* ── Admin select-mode helpers ──────────────────────────── */
  function adminEnterSelMode() {
    adminSelMode = true;
    document.getElementById('chatListHdr').style.display = 'none';
    document.getElementById('chatSelBar').style.display  = 'flex';
    loadList();
  }
  function adminExitSelMode() {
    adminSelMode = false;
    document.getElementById('chatListHdr').style.display = '';
    document.getElementById('chatSelBar').style.display  = 'none';
    document.getElementById('chkSelAll').checked = false;
    loadList();
  }
  function adminSelUpdateBar() {
    var cbs     = document.querySelectorAll('.adm-chat-cb');
    var checked = document.querySelectorAll('.adm-chat-cb:checked').length;
    var selAll  = document.getElementById('chkSelAll');
    var countEl = document.getElementById('selCount');
    var delBtn  = document.getElementById('btnDeleteSelected');
    if (countEl) countEl.textContent = checked ? checked + ' selected' : '0 selected';
    if (selAll)  selAll.checked = (cbs.length > 0 && checked === cbs.length);
    if (delBtn) {
      delBtn.style.opacity       = checked > 0 ? '1'    : '.4';
      delBtn.style.pointerEvents = checked > 0 ? 'auto' : 'none';
    }
  }
  function adminDeleteSelected() {
    var ids = [];
    document.querySelectorAll('.adm-chat-cb:checked').forEach(function(cb){ ids.push(parseInt(cb.dataset.id)); });
    if (!ids.length) return;
    var done = 0;
    ids.forEach(function(id) {
      post({action:'delete', chat_id: id}, function() {
        done++;
        if (done === ids.length) {
          // If the active chat was deleted, clear the pane
          if (ids.indexOf(activeChatId) !== -1) {
            clearInterval(pollTimer);
            activeChatId = null;
            document.getElementById('convoPane').style.display = 'none';
          }
          adminExitSelMode();
        }
      });
    });
  }

  // Wire up admin selection controls
  document.getElementById('btnSelectMode').addEventListener('click', adminEnterSelMode);
  document.getElementById('btnCancelSel').addEventListener('click', adminExitSelMode);
  document.getElementById('btnDeleteSelected').addEventListener('click', function() {
    var checked = document.querySelectorAll('.adm-chat-cb:checked').length;
    if (!checked) return;
    if (!confirm('Permanently delete ' + checked + ' chat(s) and all their messages?')) return;
    adminDeleteSelected();
  });
  document.getElementById('chkSelAll').addEventListener('change', function() {
    document.querySelectorAll('.adm-chat-cb').forEach(function(cb){ cb.checked = document.getElementById('chkSelAll').checked; });
    adminSelUpdateBar();
  });

  /* ── Open a conversation ──────────────────────────────────── */
  function openChat(chatId, name) {
    activeChatId = chatId;
    lastMsgId    = 0;
    var cp = document.getElementById('convoPane');
    cp.style.display = 'flex';
    document.getElementById('convoName').textContent = name;
    document.getElementById('convoMsgs').innerHTML = '';
    // Highlight in list
    document.querySelectorAll('.chat-item').forEach(function(el){ el.classList.remove('active'); });
    var target = document.querySelector('.chat-item[data-id="'+chatId+'"]');
    if (target) target.classList.add('active');
    // Load messages
    loadMessages();
    // Start polling
    clearInterval(pollTimer);
    pollTimer = setInterval(loadMessages, 3000);
  }

  /* ── WhatsApp-style voice player ─────────────────────────── */
  var wvAudio = null, wvActive = null;
  function wvFmtDur(s) {
    s = Math.round(s || 0);
    return Math.floor(s/60) + ':' + ('0' + (s%60)).slice(-2);
  }
  function wvBuildBars(seed, count) {
    var html = '', n = count || 26;
    for (var i = 0; i < n; i++) {
      var h = 4 + ((seed * (i + 1) * 7919 + i * 431) % 18); // 4-22px
      html += '<div class="wv-bar" style="height:' + h + 'px;" data-i="' + i + '"></div>';
    }
    return html;
  }
  function wvBuild(url, bubClass) {
    // Generate stable seed from URL characters
    var seed = 0;
    for (var i = 0; i < url.length; i++) seed = (seed + url.charCodeAt(i)) & 0xFFFF;
    var bars = wvBuildBars(seed, 26);
    var playIcon  = '<svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor"><polygon points="5,3 19,12 5,21"/></svg>';
    return '<div class="wv-player" data-src="' + esc(url) + '" data-bub="' + bubClass + '">'
      + '<button class="wv-btn" onclick="wvToggle(this)">' + playIcon + '</button>'
      + '<div class="wv-body">'
      + '<div class="wv-bars">' + bars + '</div>'
      + '<div class="wv-foot"><span class="wv-el">0:00</span><span class="wv-tot">0:00</span></div>'
      + '</div></div>';
  }
  window.wvToggle = function(btn) {
    var player = btn.closest('.wv-player');
    var src    = player.dataset.src;
    var playIcon  = '<svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor"><polygon points="5,3 19,12 5,21"/></svg>';
    var pauseIcon = '<svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor"><rect x="6" y="4" width="4" height="16"/><rect x="14" y="4" width="4" height="16"/></svg>';
    var barCount  = player.querySelectorAll('.wv-bar').length;

    if (wvActive && wvActive !== player) {
      wvAudio.pause();
      wvActive.querySelector('.wv-btn').innerHTML = playIcon;
      wvActive.querySelectorAll('.wv-bar').forEach(function(b){ b.style.opacity='1'; });
      wvAudio = null; wvActive = null;
    }
    if (!wvAudio || wvActive !== player) {
      wvAudio = new Audio(src);
      wvActive = player;
      wvAudio.play().catch(function(){});
      btn.innerHTML = pauseIcon;
      wvAudio.addEventListener('loadedmetadata', function() {
        var tot = player.querySelector('.wv-tot');
        if (tot && isFinite(wvAudio.duration)) tot.textContent = wvFmtDur(wvAudio.duration);
      });
      wvAudio.addEventListener('timeupdate', function() {
        var pct = wvAudio.duration ? wvAudio.currentTime / wvAudio.duration : 0;
        var el  = player.querySelector('.wv-el');
        if (el) el.textContent = wvFmtDur(wvAudio.currentTime);
        var active = Math.floor(pct * barCount);
        player.querySelectorAll('.wv-bar').forEach(function(b, i){
          b.style.opacity = i < active ? '1' : '0.35';
        });
      });
      wvAudio.addEventListener('ended', function() {
        btn.innerHTML = playIcon;
        player.querySelectorAll('.wv-bar').forEach(function(b){ b.style.opacity='1'; });
        var el = player.querySelector('.wv-el');
        if (el) el.textContent = '0:00';
        wvAudio = null; wvActive = null;
      });
    } else {
      if (wvAudio.paused) { wvAudio.play(); btn.innerHTML = pauseIcon; }
      else { wvAudio.pause(); btn.innerHTML = playIcon; }
    }
  };

  /* ── Load messages for active chat ───────────────────────── */
  function loadMessages() {
    if (!activeChatId) return;
    post({action:'messages', chat_id: activeChatId}, function(res) {
      if (!res.ok) return;
      var box = document.getElementById('convoMsgs');
      var isUserDeleted = parseInt(res.info.user_deleted) === 1;

      // Update status
      var stEl = document.getElementById('convoStatus');
      if (isUserDeleted) {
        stEl.innerHTML = '<i class="fa fa-trash-o" style="font-size:10px;color:#dc3545;"></i> Deleted by user';
      } else if (res.info.status === 'active') {
        stEl.innerHTML = '<i class="fa fa-circle" style="font-size:7px;color:#25d366;"></i> Connected';
      } else if (res.info.status === 'closed') {
        stEl.innerHTML = '<i class="fa fa-circle" style="font-size:7px;color:#ccc;"></i> Chat closed';
      } else {
        stEl.innerHTML = '<i class="fa fa-circle" style="font-size:7px;color:#ffb300;"></i> Waiting for reply';
      }

      // Lock/unlock reply input based on user_deleted
      var replyRow = document.getElementById('replyInputRow');
      var delBanner = document.getElementById('userDeletedBanner');
      if (isUserDeleted) {
        if (replyRow) replyRow.style.display = 'none';
        if (!delBanner) {
          var banner = document.createElement('div');
          banner.id = 'userDeletedBanner';
          banner.className = 'user-deleted-banner';
          banner.innerHTML = '<i class="fa fa-trash-o"></i> This chat was deleted by the user. You can view the conversation but cannot send replies.';
          box.parentNode.appendChild(banner);
        }
      } else {
        if (replyRow) replyRow.style.display = '';
        if (delBanner) delBanner.remove();
      }

      // Re-render messages only if count changed
      if (res.messages.length === lastMsgId) return;
      lastMsgId = res.messages.length;
      var wasAtBottom = box.scrollHeight - box.scrollTop - box.clientHeight < 60;
      box.innerHTML = res.messages.map(function(m) {
        var type = m.message_type || 'text';
        var url  = m.file_url || '';
        var bubContent = '';
        if (type === 'image' && url) {
          bubContent = '<a href="' + esc(url) + '" target="_blank"><img src="' + esc(url) + '" style="max-width:200px;max-height:200px;border-radius:8px;display:block;" alt="Image"></a>';
        } else if (type === 'voice' && url) {
          bubContent = wvBuild(url, m.sender);
        } else {
          bubContent = esc(m.message);
        }
        return '<div class="msg-row ' + m.sender + '">'
          + '<div class="msg-wrap"><div class="msg-bub ' + m.sender + '">' + bubContent + '</div>'
          + '<div class="msg-time">' + esc(m.t) + '</div></div>'
          + '</div>';
      }).join('');
      if (wasAtBottom || lastMsgId <= 1) box.scrollTop = box.scrollHeight;
    });
  }

  /* ── Send reply ────────────────────────────────────────────── */
  document.getElementById('btnSendReply').addEventListener('click', sendReply);
  document.getElementById('replyInput').addEventListener('keydown', function(e) {
    if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendReply(); }
  });
  function sendReply() {
    if (!activeChatId) return;
    var inp = document.getElementById('replyInput');
    var msg = inp.value.trim();
    if (!msg) return;
    inp.value = '';
    post({action:'reply', chat_id: activeChatId, message: msg}, function(res) {
      if (res.ok) loadMessages();
      else if (res.error === 'user_deleted') {
        alert('This chat was deleted by the user. You cannot send replies.');
        loadMessages();
      }
    });
  }

  /* ── Close / end chat ─────────────────────────────────────── */
  document.getElementById('btnCloseChat').addEventListener('click', function() {
    if (!activeChatId || !confirm('End this chat session?')) return;
    post({action:'close', chat_id: activeChatId}, function(res) {
      if (res.ok) {
        clearInterval(pollTimer);
        activeChatId = null;
        document.getElementById('convoPane').style.display = 'none';
        loadList();
      }
    });
  });

  /* ── Delete chat ─────────────────────────────────────────── */
  document.getElementById('btnDeleteChat').addEventListener('click', function() {
    if (!activeChatId || !confirm('Delete this chat permanently? All messages will be removed.')) return;
    post({action:'delete', chat_id: activeChatId}, function(res) {
      if (res.ok) {
        clearInterval(pollTimer);
        activeChatId = null;
        document.getElementById('convoPane').style.display = 'none';
        loadList();
      }
    });
  });

  /* ── Poll for new chats / messages (admin side) ───────────── */
  function pollNew() {
    post({action:'poll_new', last_id: lastPollId}, function(res) {
      if (!res.ok) return;
      if (res.max_id > lastPollId) {
        lastPollId = res.max_id;
        loadList(); // refresh list to show unread counts
      }
      if (res.new_msgs > 0) {
        // Play a soft sound notification
        try {
          var ctx = new (window.AudioContext || window.webkitAudioContext)();
          var o = ctx.createOscillator(); var g = ctx.createGain();
          o.connect(g); g.connect(ctx.destination);
          o.frequency.value = 880; g.gain.setValueAtTime(.1, ctx.currentTime);
          g.gain.exponentialRampToValueAtTime(.001, ctx.currentTime+.3);
          o.start(); o.stop(ctx.currentTime+.3);
        } catch(e){}
      }
    });
  }

  /* ── Init ─────────────────────────────────────────────────── */
  loadList();
  listTimer = setInterval(function(){ loadList(); pollNew(); }, 4000);

  /* ── Quick Replies ────────────────────────────────────────── */
  var QR_KEY = 'th_admin_quick_replies';
  var defaultQuickReplies = [
    'We\'ll get back to you shortly!',
    'Thank you for contacting Kapraa!',
    'Please share your order ID or product link.',
    'Yes, this item is currently in stock!',
    'Our delivery takes 3–5 working days across Pakistan.',
    'You can place your order at kapraa.pk',
  ];

  function loadQuickReplies() {
    var saved = null;
    try { saved = JSON.parse(localStorage.getItem(QR_KEY)); } catch(e){}
    return Array.isArray(saved) ? saved : defaultQuickReplies.slice();
  }
  function saveQuickReplies(list) {
    try { localStorage.setItem(QR_KEY, JSON.stringify(list)); } catch(e){}
  }
  function renderQuickReplies() {
    var list = loadQuickReplies();
    var el = document.getElementById('quickList');
    el.innerHTML = '';
    list.forEach(function(txt, idx) {
      var chip = document.createElement('span');
      chip.className = 'qr-chip';
      chip.title = txt;
      chip.innerHTML = '<span style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:190px;">' + esc(txt) + '</span>'
        + '<span class="qr-chip-del" data-idx="' + idx + '" title="Remove">&times;</span>';
      // Click chip text → insert into reply box
      chip.querySelector('span:first-child').addEventListener('click', function() {
        var inp = document.getElementById('replyInput');
        inp.value = txt;
        inp.focus();
      });
      // Click × → delete
      chip.querySelector('.qr-chip-del').addEventListener('click', function(e) {
        e.stopPropagation();
        var arr = loadQuickReplies();
        arr.splice(idx, 1);
        saveQuickReplies(arr);
        renderQuickReplies();
      });
      el.appendChild(chip);
    });
  }

  document.getElementById('btnToggleQuick').addEventListener('click', function() {
    var bar = document.getElementById('quickRepliesBar');
    var shown = bar.style.display !== 'none';
    bar.style.display = shown ? 'none' : 'block';
    if (!shown) renderQuickReplies();
    this.style.background = shown ? '#f5f5f5' : '#e8f8ee';
  });

  document.getElementById('btnAddQuick').addEventListener('click', function() {
    var txt = prompt('Enter quick reply text:');
    if (!txt || !txt.trim()) return;
    var arr = loadQuickReplies();
    arr.push(txt.trim());
    saveQuickReplies(arr);
    renderQuickReplies();
  });

  /* ── Admin: media send helpers ───────────────────────────── */
  var adminPendingFile = null;
  var adminRecorder    = null;
  var adminChunks      = [];
  var adminRecTimer    = null;
  var adminRecSecs     = 0;

  function adminFormatTime(s){ return Math.floor(s/60)+':'+(('0'+(s%60)).slice(-2)); }

  function adminSendMedia(blob, type) {
    if (!activeChatId || !blob) return;
    var fd = new FormData();
    var ext = (type === 'voice') ? 'webm' : (blob.type.split('/')[1] || 'jpg');
    fd.append('action', 'send_media');
    fd.append('chat_id', activeChatId);
    fd.append('media_type', type);
    fd.append('file', blob, type + '_msg.' + ext);
    fetch(AJAX, { method:'POST', body: fd })
      .then(function(r){ return r.json(); })
      .then(function(res){ if(res.ok) loadMessages(); })
      .catch(function(){});
    adminClearMedia();
  }

  function adminClearMedia() {
    adminPendingFile = null;
    document.getElementById('adminMediaPre').style.display = 'none';
    document.getElementById('adminPreImg').style.display = 'none';
    document.getElementById('adminPreImg').src = '';
    document.getElementById('adminPreLbl').textContent = '';
  }

  // Image button
  document.getElementById('btnSendImg').addEventListener('click', function() {
    document.getElementById('adminFileInp').value = '';
    document.getElementById('adminFileInp').click();
  });
  document.getElementById('adminFileInp').addEventListener('change', function() {
    var file = this.files[0];
    if (!file) return;
    if (!file.type.startsWith('image/')) return;
    if (file.size > 5 * 1024 * 1024) { alert('Image must be under 5 MB.'); return; }
    adminPendingFile = { blob: file, type: 'image' };
    var reader = new FileReader();
    reader.onload = function(e) {
      document.getElementById('adminPreImg').src = e.target.result;
      document.getElementById('adminPreImg').style.display = '';
      document.getElementById('adminPreLbl').textContent = file.name.length > 28 ? file.name.substr(0,25) + '…' : file.name;
      document.getElementById('adminMediaPre').style.display = 'flex';
    };
    reader.readAsDataURL(file);
  });
  document.getElementById('adminPreClr').addEventListener('click', adminClearMedia);

  // Send pending image on Send button if media pending
  (function() {
    var origSend = document.getElementById('btnSendReply').onclick;
    document.getElementById('btnSendReply').addEventListener('click', function(e) {
      if (adminPendingFile) {
        e.stopImmediatePropagation();
        adminSendMedia(adminPendingFile.blob, adminPendingFile.type);
      }
    }, true);
  })();

  // Voice record button
  var voiceBtn = document.getElementById('btnSendVoice');
  function adminStartRec() {
    if (!activeChatId) return;
    if (!navigator.mediaDevices) { alert('Microphone not supported.'); return; }
    navigator.mediaDevices.getUserMedia({ audio: true }).then(function(stream) {
      adminChunks = []; adminRecSecs = 0;
      var mimePrefs = ['video/webm;codecs=opus','audio/webm;codecs=opus','video/webm','audio/webm',''];
      var chosenMime = '';
      for (var mi = 0; mi < mimePrefs.length; mi++) {
        if (!mimePrefs[mi] || MediaRecorder.isTypeSupported(mimePrefs[mi])) { chosenMime = mimePrefs[mi]; break; }
      }
      var opts = chosenMime ? { mimeType: chosenMime } : {};
      adminRecorder = new MediaRecorder(stream, opts);
      adminChunks = [];
      adminRecorder.addEventListener('dataavailable', function(e) { if(e.data && e.data.size>0) adminChunks.push(e.data); });
      adminRecorder.addEventListener('stop', function() {
        stream.getTracks().forEach(function(t){ t.stop(); });
        adminRecorder = null;
        if (!adminChunks.length) return;
        var blob = new Blob(adminChunks, { type: 'video/webm' });
        adminChunks = [];
        if (blob.size > 0) adminSendMedia(blob, 'voice');
      });
      adminRecorder.start(250); // 250ms timeslice
      voiceBtn.style.background = 'linear-gradient(135deg,#e74c3c,#c0392b)';
      voiceBtn.style.borderColor = '#e74c3c';
      voiceBtn.style.color = '#fff';
      var recEl = document.getElementById('adminVoiceRec');
      var recTimeEl = document.getElementById('adminRecTime');
      recEl.style.display = 'flex';
      recTimeEl.textContent = '0:00';
      adminRecTimer = setInterval(function() {
        adminRecSecs++;
        recTimeEl.textContent = adminFormatTime(adminRecSecs);
        if (adminRecSecs >= 120) adminStopRec(false);
      }, 1000);
    }).catch(function(){ alert('Microphone access denied.'); });
  }
  function adminStopRec(cancel) {
    clearInterval(adminRecTimer); adminRecSecs = 0;
    voiceBtn.style.background = ''; voiceBtn.style.borderColor = ''; voiceBtn.style.color = '';
    document.getElementById('adminVoiceRec').style.display = 'none';
    if (adminRecorder && adminRecorder.state !== 'inactive') {
      if (cancel) { adminChunks = []; adminRecorder.ondataavailable = null; }
      adminRecorder.stop();
    }
  }
  voiceBtn.addEventListener('mousedown',  function(e){ e.preventDefault(); adminStartRec(); });
  voiceBtn.addEventListener('mouseup',    function(e){ e.preventDefault(); if(adminRecorder&&adminRecorder.state!=='inactive') adminStopRec(false); });
  voiceBtn.addEventListener('mouseleave', function(){ if(adminRecorder&&adminRecorder.state!=='inactive') adminStopRec(false); });
  voiceBtn.addEventListener('touchstart', function(e){ e.preventDefault(); adminStartRec(); }, {passive:false});
  voiceBtn.addEventListener('touchend',   function(e){ e.preventDefault(); if(adminRecorder&&adminRecorder.state!=='inactive') adminStopRec(false); }, {passive:false});
  document.getElementById('adminRecCancel').addEventListener('click', function(){ adminStopRec(true); });

})();
</script>

<style>
@keyframes adm-blink { 0%,100%{opacity:1} 50%{opacity:.3} }

/* Force reply bar to always be a horizontal row — overrides any template CSS */
#replyInputRow {
  display: flex !important;
  flex-direction: row !important;
  align-items: center !important;
  flex-wrap: nowrap !important;
  gap: 8px !important;
  padding: 10px 14px 12px !important;
}
#replyInputRow textarea { flex: 1 1 auto !important; width: auto !important; }
#replyInputRow button   { flex-shrink: 0 !important; width: auto !important; }

#btnSendImg, #btnSendVoice, #btnToggleQuick {
  width: 38px !important; height: 38px !important;
  min-width: 38px !important; min-height: 38px !important;
  border-radius: 50% !important;
  display: flex !important; align-items: center !important; justify-content: center !important;
}
#btnSendReply {
  width: 44px !important; height: 44px !important;
  min-width: 44px !important; min-height: 44px !important;
  border-radius: 50% !important;
  display: flex !important; align-items: center !important; justify-content: center !important;
}
#btnSendImg:hover, #btnSendVoice:hover, #btnToggleQuick:hover {
  background: #e8faf0 !important; border-color: #25d366 !important;
  box-shadow: 0 2px 8px rgba(37,211,102,.25) !important;
}
#btnSendReply:hover { opacity: .88 !important; transform: scale(1.06) !important; }

/* convoPane fills parent when open */
#convoPane { overflow: hidden; }
</style>

<?php include 'include/footer.php'; ?>
