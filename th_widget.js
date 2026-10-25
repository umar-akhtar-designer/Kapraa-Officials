(function () {
  "use strict";

  var PINK    = "#c9436e";
  var LIGHT   = "#fbeaf0";
  var CHAT_ID = "th-ai-chat";
  var IS_LOCAL =
    window.location.hostname === "localhost" ||
    window.location.hostname === "127.0.0.1";
  var IMG_BASE = IS_LOCAL
    ? "http://localhost/kapraa/admin/images/product/"
    : "https://kapraa.pk/admin/images/product/";
  var API_URL = IS_LOCAL
    ? "http://localhost/kapraa/ai_chat.php"
    : "https://kapraa.pk/ai_chat.php";
  var LC_URL = IS_LOCAL
    ? "http://localhost/kapraa/live_chat.php"
    : "https://kapraa.pk/live_chat.php";
  var GREEN      = "#25d366";
  var GREEN_DARK = "#128C7E";

  if (document.getElementById(CHAT_ID)) return;

  /* ── SVG ICONS ────────────────────────────────────────────── */
  var IC = {
    camera : '<svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>',
    mic    : '<svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 1a3 3 0 0 0-3 3v8a3 3 0 0 0 6 0V4a3 3 0 0 0-3-3z"/><path d="M19 10v2a7 7 0 0 1-14 0v-2"/><line x1="12" y1="19" x2="12" y2="23"/><line x1="8" y1="23" x2="16" y2="23"/></svg>',
    send   : '<svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2" fill="currentColor" stroke="none"/></svg>',
    trash  : '<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>',
    close  : '<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>',
    user   : '<svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>',
    minus  : '<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="5" y1="12" x2="19" y2="12"/></svg>',
    volume : '<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"/><path d="M19.07 4.93a10 10 0 0 1 0 14.14"/><path d="M15.54 8.46a5 5 0 0 1 0 7.07"/></svg>',
    mute   : '<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"/><line x1="23" y1="9" x2="17" y2="15"/><line x1="17" y1="9" x2="23" y2="15"/></svg>',
    closeSm: '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>',
  };

  /* ── CSS ─────────────────────────────────────────────────── */
  var css = `
  /* === FAB BUTTON === */
  #th-fab {
    position:fixed!important; bottom:24px!important; right:24px!important;
    left:auto!important; top:auto!important;
    z-index:2147483646!important;
    width:56px; height:56px; border-radius:50%;
    background:linear-gradient(140deg,${PINK},#a52d55);
    color:#fff; font-size:20px; border:none;
    display:flex; align-items:center; justify-content:center;
    cursor:pointer;
    box-shadow:0 4px 20px rgba(201,67,110,.5),0 2px 8px rgba(0,0,0,.15);
    transition:transform .2s,box-shadow .2s;
  }
  #th-fab:hover { transform:scale(1.08); box-shadow:0 6px 28px rgba(201,67,110,.65); }
  #th-notif {
    position:absolute; top:-4px; right:-4px;
    width:18px; height:18px; border-radius:50%;
    background:#ff3b30; border:2.5px solid #fff;
    font-size:9px; font-weight:800; color:#fff;
    display:flex; align-items:center; justify-content:center;
    animation:th-notif-pulse 2s ease infinite;
  }
  @keyframes th-notif-pulse {
    0%,100%{box-shadow:0 0 0 0 rgba(255,59,48,.5);}
    50%{box-shadow:0 0 0 6px rgba(255,59,48,0);}
  }

  /* === CHAT WINDOW === */
  #th-ai-chat {
    position:fixed!important; bottom:92px!important; right:24px!important;
    left:auto!important; top:auto!important;
    z-index:2147483647!important;
    width:360px; max-width:calc(100vw - 32px);
    background:#fff; border-radius:20px; overflow:hidden;
    box-shadow:0 12px 48px rgba(0,0,0,.16),0 2px 12px rgba(0,0,0,.08);
    display:none; flex-direction:column;
    font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Arial,sans-serif;
    max-height:calc(100vh - 110px);
    border:1px solid rgba(201,67,110,.12);
  }
  #th-ai-chat.th-open { display:flex; animation:th-pop .22s cubic-bezier(.34,1.3,.64,1); }
  @keyframes th-pop {
    from{opacity:0;transform:translateY(12px) scale(.96);}
    to{opacity:1;transform:translateY(0) scale(1);}
  }
  @media(max-width:480px){
    #th-ai-chat{right:0!important;left:0!important;bottom:0!important;width:100%!important;max-width:none!important;border-radius:20px 20px 0 0;max-height:90vh;}
    #th-fab{bottom:14px!important;right:14px!important;}
  }

  /* === HEADER === */
  .th-hdr {
    background:linear-gradient(135deg,${PINK} 0%,#a52d55 100%);
    padding:14px 14px 12px; display:flex; align-items:center; gap:11px; flex-shrink:0;
    position:relative;
  }
  .th-hdr-av {
    width:40px; height:40px; border-radius:50%;
    background:rgba(255,255,255,.22); border:2px solid rgba(255,255,255,.35);
    display:flex; align-items:center; justify-content:center;
    font-size:17px; font-weight:800; color:#fff;
    flex-shrink:0; position:relative; letter-spacing:-1px;
  }
  .th-hdr-av-dot {
    position:absolute; bottom:0; right:0;
    width:11px; height:11px; border-radius:50%;
    background:#2ecc71; border:2px solid #a52d55;
  }
  .th-hdr-info { flex:1; min-width:0; }
  .th-hdr-name { color:#fff; font-weight:700; font-size:14.5px; margin:0; line-height:1.2; }
  .th-hdr-sub  { color:rgba(255,255,255,.75); font-size:11px; margin:3px 0 0; }
  .th-hdr-btns { display:flex; gap:6px; align-items:center; }
  .th-hdr-btn {
    background:rgba(255,255,255,.18); border:none; color:#fff;
    width:30px; height:30px; border-radius:8px;
    display:flex; align-items:center; justify-content:center;
    cursor:pointer; transition:background .15s;
  }
  .th-hdr-btn:hover { background:rgba(255,255,255,.32); }

  /* === LEVEL BAR === */
  .th-level-bar {
    padding:7px 12px; background:#fdf5f8;
    border-bottom:1px solid rgba(201,67,110,.1);
    display:flex; align-items:center; gap:8px; flex-shrink:0;
  }
  .th-level-lbl { font-size:11px; color:#aaa; white-space:nowrap; }
  .th-level-btns { display:flex; gap:4px; }
  .th-lvl-btn {
    font-size:10.5px; padding:3px 10px; border-radius:20px;
    border:1.5px solid #e0c8d8; background:#fff;
    color:#999; cursor:pointer; transition:all .15s; font-weight:500;
  }
  .th-lvl-btn.active { background:${PINK}; border-color:${PINK}; color:#fff; font-weight:600; }
  .th-lvl-btn:hover:not(.active) { border-color:${PINK}; color:${PINK}; }

  /* === MESSAGES === */
  .th-msgs {
    flex:1; overflow-y:auto; padding:16px 12px 10px;
    background:#faf4f7;
    display:flex; flex-direction:column; gap:12px;
    min-height:80px;
  }
  .th-msgs::-webkit-scrollbar{width:3px;}
  .th-msgs::-webkit-scrollbar-thumb{background:rgba(201,67,110,.2);border-radius:4px;}

  .th-row{display:flex;align-items:flex-end;gap:7px;}
  .th-row.u{flex-direction:row-reverse;}

  .th-av {
    width:27px; height:27px; border-radius:50%;
    display:flex; align-items:center; justify-content:center;
    font-size:11px; flex-shrink:0;
    background:linear-gradient(135deg,${PINK},#a52d55); color:#fff;
    font-weight:700;
  }
  .th-row.u .th-av { background:#e8e0e4; color:#888; }

  .th-col{display:flex;flex-direction:column;max-width:82%;}
  .th-row.u .th-col{align-items:flex-end;}

  .th-bub {
    padding:9px 13px; border-radius:18px;
    font-size:13px; line-height:1.65; word-break:break-word;
  }
  .th-bub.ai {
    background:#fff; color:#333;
    border:1px solid rgba(201,67,110,.15);
    border-bottom-left-radius:5px;
    box-shadow:0 1px 4px rgba(0,0,0,.06);
  }
  .th-bub.usr {
    background:linear-gradient(135deg,${PINK},#a52d55);
    color:#fff; border-bottom-right-radius:5px;
    box-shadow:0 2px 8px rgba(201,67,110,.3);
  }
  .th-time { font-size:10px; color:#ccc; margin-top:3px; }
  .th-msg-img { width:80px; height:80px; object-fit:cover; border-radius:10px; display:block; margin-top:6px; border:2px solid rgba(255,255,255,.4); }

  /* feedback buttons */
  .th-fb{display:flex;gap:5px;margin-top:8px;flex-wrap:wrap;}
  .th-fb-btn {
    font-size:10.5px; padding:4px 11px; border-radius:20px;
    border:1.5px solid #edd8e4; background:#fff; color:#b07090;
    cursor:pointer; transition:all .18s; display:inline-flex;
    align-items:center; gap:5px; font-weight:500;
  }
  .th-fb-btn svg { flex-shrink:0; }
  .th-fb-btn:hover{border-color:${PINK};color:${PINK};background:#fdf0f5;transform:translateY(-1px);}
  .th-fb-btn:disabled{opacity:.5;cursor:default;transform:none;}

  /* typing dots */
  .th-typing {
    display:flex; gap:5px; align-items:center; padding:10px 14px;
    background:#fff; border:1px solid rgba(201,67,110,.15);
    border-radius:18px; border-bottom-left-radius:5px;
    box-shadow:0 1px 4px rgba(0,0,0,.06);
  }
  .th-dot{width:6px;height:6px;border-radius:50%;background:#e0aabb;animation:th-bounce 1.3s ease infinite;}
  .th-dot:nth-child(2){animation-delay:.2s;}
  .th-dot:nth-child(3){animation-delay:.4s;}
  @keyframes th-bounce{0%,60%,100%{transform:translateY(0);opacity:.5;}30%{transform:translateY(-6px);opacity:1;}}

  /* === CHIPS === */
  .th-chips {
    padding:8px 12px; display:flex; gap:6px;
    flex-wrap:nowrap; overflow-x:auto; background:#fff;
    border-top:1px solid rgba(201,67,110,.1); flex-shrink:0;
    scrollbar-width:none;
  }
  .th-chips::-webkit-scrollbar{display:none;}
  .th-chip {
    font-size:11.5px; padding:5px 12px; border-radius:22px;
    border:1.5px solid rgba(201,67,110,.35); color:${PINK};
    background:#fff; cursor:pointer;
    white-space:nowrap; transition:all .15s; font-weight:500;
    flex-shrink:0; display:inline-flex; align-items:center; gap:5px;
  }
  .th-chip svg { flex-shrink:0; }
  .th-chip:hover { background:${PINK}; color:#fff; border-color:${PINK}; transform:translateY(-1px); }
  .th-chip:hover svg { stroke:#fff; }

  /* === PRODUCT SECTION === */
  .th-prods {
    padding:10px 12px 12px; background:#fdf5f8;
    border-top:1px solid rgba(201,67,110,.12); flex-shrink:0;
  }
  .th-prods-top{display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;}
  .th-prods-lbl{font-size:11.5px;color:#c9436e;font-weight:700;}
  .th-prods-tog{
    font-size:10px;border:1.5px solid ${PINK};color:${PINK};
    background:#fff;border-radius:20px;padding:2px 10px;cursor:pointer;
    transition:all .15s;
  }
  .th-prods-tog:hover{background:${PINK};color:#fff;}

  /* product horizontal scroll */
  .th-prods-row{display:flex;gap:9px;overflow-x:auto;padding-bottom:4px;}
  .th-prods-row::-webkit-scrollbar{height:3px;}
  .th-prods-row::-webkit-scrollbar-thumb{background:rgba(201,67,110,.3);border-radius:4px;}
  .th-prods-row.hide{display:none;}

  /* product card */
  .th-card {
    width:136px; flex-shrink:0; border-radius:14px; overflow:hidden;
    background:#fff; border:1.5px solid #f0dde6;
    text-decoration:none; color:inherit; display:block;
    transition:border-color .2s,transform .22s,box-shadow .22s;
    box-shadow:0 2px 8px rgba(0,0,0,.07);
  }
  .th-card:hover{border-color:${PINK};transform:translateY(-4px);box-shadow:0 8px 24px rgba(201,67,110,.22);}

  .th-cimg-wrap{position:relative;overflow:hidden;background:linear-gradient(135deg,#fdf0f5,#f5e0ea);}
  .th-cimg{width:100%;height:160px;object-fit:cover;object-position:top center;display:block;transition:transform .35s;}
  .th-card:hover .th-cimg{transform:scale(1.06);}
  .th-cph{
    width:100%;height:160px;display:flex;flex-direction:column;
    align-items:center;justify-content:center;
    background:linear-gradient(135deg,#fdf0f5,#f2d8e4);
    color:${PINK};gap:4px;
  }
  .th-cph svg{opacity:.45;}
  .th-cph span{font-size:9px;color:#d4a0b4;font-weight:600;letter-spacing:.5px;}
  .th-sale-tag{
    position:absolute;top:7px;left:7px;
    background:linear-gradient(135deg,${PINK},#a52d55);color:#fff;
    font-size:8px;font-weight:800;padding:3px 7px;border-radius:6px;
    letter-spacing:.6px;box-shadow:0 2px 6px rgba(201,67,110,.4);
  }

  .th-cinfo{padding:8px 9px 10px;}
  .th-cbrand{font-size:9px;color:${PINK};font-weight:800;text-transform:uppercase;letter-spacing:.5px;margin-bottom:3px;opacity:.9;}
  .th-cname{font-size:11px;color:#2d2d2d;line-height:1.4;margin:0 0 6px;min-height:30px;overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;font-weight:500;}
  .th-cprice{font-size:11px;display:flex;align-items:center;gap:5px;flex-wrap:wrap;}
  .th-cprice s{color:#ccc;font-size:9.5px;}
  .th-cprice b{color:${PINK};font-weight:800;font-size:12.5px;}
  .th-cbadge{
    display:inline-block;font-size:9px;
    background:linear-gradient(135deg,#fbeaf0,#fdd8e8);
    color:${PINK};padding:2px 7px;border-radius:6px;margin-top:5px;
    font-weight:700;border:1px solid rgba(201,67,110,.15);
  }

  /* === IMAGE PREVIEW === */
  .th-img-pre{
    padding:8px 12px;background:#fdf5f8;
    border-top:1px solid rgba(201,67,110,.1);
    display:none;align-items:center;gap:9px;flex-shrink:0;
  }
  .th-img-pre.show{display:flex;}
  .th-pre-img{width:44px;height:44px;object-fit:cover;border-radius:9px;border:2px solid rgba(201,67,110,.2);}
  .th-pre-txt{font-size:11.5px;color:#888;flex:1;}
  .th-pre-clr{background:none;border:none;color:${PINK};cursor:pointer;padding:4px;display:flex;align-items:center;justify-content:center;border-radius:6px;transition:background .15s;}
  .th-pre-clr:hover{background:rgba(201,67,110,.1);}

  /* === VOICE STATUS === */
  .th-voice-st{font-size:11.5px;color:${PINK};text-align:center;padding:5px 12px;background:#fbeaf0;display:none;flex-shrink:0;font-weight:500;}
  .th-voice-st.show{display:block;}

  /* === TTS BUTTON === */
  .th-tts-btn{background:none;border:none;padding:2px 4px;cursor:pointer;color:#bbb;border-radius:4px;transition:color .15s;font-size:11px;line-height:1;display:inline-flex;align-items:center;gap:2px;vertical-align:middle;margin-left:3px;}
  .th-tts-btn:hover{color:${PINK};}
  .th-tts-btn.speaking{color:${PINK};}

  /* === INPUT ROW === */
  .th-inp-row {
    padding:10px 12px; display:flex; gap:7px; align-items:center;
    border-top:1px solid rgba(201,67,110,.1); background:#fff; flex-shrink:0;
  }
  .th-inp {
    flex:1; font-size:13px; padding:10px 14px;
    border-radius:24px; border:1.5px solid #e8d4de;
    outline:none; color:#333; background:#fdf8fb;
    transition:border-color .2s,box-shadow .2s;
  }
  .th-inp:focus{border-color:${PINK};box-shadow:0 0 0 3px rgba(201,67,110,.1);}
  .th-inp::placeholder{color:#bbb;}

  .th-icon-btn {
    width:37px; height:37px; border-radius:50%; border:none;
    display:flex; align-items:center; justify-content:center;
    cursor:pointer; flex-shrink:0; transition:all .18s;
  }
  .th-img-btn  { background:#f5eaf0; color:#b05070; }
  .th-img-btn:hover  { background:#f0d0e0; color:${PINK}; transform:scale(1.06); }
  .th-voice-btn { background:#f5eaf0; color:#b05070; }
  .th-voice-btn:hover,.th-voice-btn.active { background:${PINK}; color:#fff; transform:scale(1.06); }
  .th-voice-btn.active { animation:th-voice-pulse 1.5s ease infinite; }
  @keyframes th-voice-pulse{0%,100%{box-shadow:0 0 0 0 rgba(201,67,110,.5);}50%{box-shadow:0 0 0 7px rgba(201,67,110,0);}}
  .th-send-btn { background:linear-gradient(135deg,${PINK},#a52d55); color:#fff; box-shadow:0 2px 8px rgba(201,67,110,.4); }
  .th-send-btn:hover { transform:scale(1.07); box-shadow:0 3px 12px rgba(201,67,110,.55); }
  #th-file-inp{display:none;}

  /* === POWERED BY === */
  .th-powered{
    text-align:center;font-size:9.5px;color:#ccc;
    padding:4px 0 6px;background:#fff;flex-shrink:0;
  }

  /* === LIVE CHAT WINDOW === */
  #th-live-chat {
    position:fixed!important; bottom:92px!important; right:24px!important;
    left:auto!important; top:auto!important;
    z-index:2147483647!important;
    width:360px; max-width:calc(100vw - 32px);
    background:#fff; border-radius:20px; overflow:hidden;
    box-shadow:0 12px 48px rgba(0,0,0,.16),0 2px 12px rgba(0,0,0,.08);
    display:none; flex-direction:column;
    font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Arial,sans-serif;
    max-height:calc(100vh - 110px);
    border:1px solid rgba(37,211,102,.18);
  }
  #th-live-chat.th-open { display:flex; animation:th-pop .22s cubic-bezier(.34,1.3,.64,1); }
  @media(max-width:480px){
    #th-live-chat{right:0!important;left:0!important;bottom:0!important;width:100%!important;max-width:none!important;border-radius:20px 20px 0 0;max-height:90vh;}
  }

  /* Live chat header */
  .lc-hdr {
    background:linear-gradient(135deg,${GREEN} 0%,${GREEN_DARK} 100%);
    padding:14px 14px 12px; display:flex; align-items:center; gap:11px; flex-shrink:0;
  }
  .lc-hdr-av {
    width:40px; height:40px; border-radius:50%;
    background:rgba(255,255,255,.22); border:2px solid rgba(255,255,255,.35);
    display:flex; align-items:center; justify-content:center; flex-shrink:0;
  }
  .lc-hdr-av-dot {
    position:absolute; bottom:0; right:0; width:11px; height:11px; border-radius:50%;
    background:#fff; border:2px solid ${GREEN_DARK};
  }
  .lc-hdr-av-wrap { position:relative; flex-shrink:0; }
  .lc-hdr-info { flex:1; min-width:0; }
  .lc-hdr-name { color:#fff; font-weight:700; font-size:14.5px; margin:0; line-height:1.2; }
  .lc-hdr-sub  { color:rgba(255,255,255,.78); font-size:11px; margin:3px 0 0; }
  .lc-hdr-btns { display:flex; gap:6px; align-items:center; }
  .lc-hdr-btn {
    background:rgba(255,255,255,.18); border:none; color:#fff;
    width:30px; height:30px; border-radius:8px;
    display:flex; align-items:center; justify-content:center;
    cursor:pointer; transition:background .15s;
  }
  .lc-hdr-btn:hover { background:rgba(255,255,255,.32); }

  /* Live chat messages area */
  .lc-msgs {
    flex:1; overflow-y:auto; padding:14px 12px 10px;
    background:#f4fdf7;
    display:flex; flex-direction:column; gap:10px;
    min-height:80px;
  }
  .lc-msgs::-webkit-scrollbar{width:3px;}
  .lc-msgs::-webkit-scrollbar-thumb{background:rgba(37,211,102,.25);border-radius:4px;}

  .lc-row { display:flex; align-items:flex-end; gap:7px; }
  .lc-row.u { flex-direction:row-reverse; }
  .lc-av {
    width:27px; height:27px; border-radius:50%;
    display:flex; align-items:center; justify-content:center; font-size:11px;
    flex-shrink:0; font-weight:700;
    background:linear-gradient(135deg,${GREEN},${GREEN_DARK}); color:#fff;
  }
  .lc-row.u .lc-av { background:#e8e8e8; color:#888; }
  .lc-col { display:flex; flex-direction:column; max-width:82%; }
  .lc-row.u .lc-col { align-items:flex-end; }
  .lc-bub {
    padding:9px 13px; border-radius:18px;
    font-size:13px; line-height:1.65; word-break:break-word;
  }
  .lc-bub.ai {
    background:#fff; color:#333;
    border:1px solid rgba(37,211,102,.2);
    border-bottom-left-radius:5px;
    box-shadow:0 1px 4px rgba(0,0,0,.06);
  }
  .lc-bub.usr {
    background:linear-gradient(135deg,${GREEN},${GREEN_DARK});
    color:#fff; border-bottom-right-radius:5px;
    box-shadow:0 2px 8px rgba(37,211,102,.35);
  }
  .lc-time { font-size:10px; color:#ccc; margin-top:3px; }
  .lc-sender-name { font-size:11px; font-weight:700; color:#c9436e; margin-bottom:3px; letter-spacing:0.2px; }

  /* Waiting / status bar */
  .lc-status-bar {
    padding:8px 14px; text-align:center; font-size:11.5px;
    background:#e8f8ee; color:#2d7a4f; border-top:1px solid rgba(37,211,102,.15);
    flex-shrink:0;
  }
  .lc-status-bar.lc-waiting { color:#888; background:#f5f5f5; }
  .lc-status-bar.lc-closed  { color:#999; background:#f8f8f8; }

  /* Live typing dots (same style as AI) */
  .lc-typing {
    display:flex; gap:5px; align-items:center; padding:10px 14px;
    background:#fff; border:1px solid rgba(37,211,102,.2);
    border-radius:18px; border-bottom-left-radius:5px;
    box-shadow:0 1px 4px rgba(0,0,0,.06);
  }
  .lc-dot{width:6px;height:6px;border-radius:50%;background:#a0dbb4;animation:th-bounce 1.3s ease infinite;}
  .lc-dot:nth-child(2){animation-delay:.2s;}
  .lc-dot:nth-child(3){animation-delay:.4s;}

  /* Live chat input row */
  .lc-inp-row {
    padding:10px 12px; display:flex; gap:7px; align-items:center;
    border-top:1px solid rgba(37,211,102,.12); background:#fff; flex-shrink:0;
  }
  .lc-inp {
    flex:1; font-size:13px; padding:10px 14px;
    border-radius:24px; border:1.5px solid #cce8d4;
    outline:none; color:#333; background:#f4fdf7;
    transition:border-color .2s;
  }
  .lc-inp:focus { border-color:${GREEN}; box-shadow:0 0 0 3px rgba(37,211,102,.1); }
  .lc-inp::placeholder { color:#aaa; }
  .lc-inp:disabled { opacity:.55; cursor:not-allowed; }
  .lc-send-btn {
    width:37px; height:37px; border-radius:50%; border:none; flex-shrink:0;
    background:linear-gradient(135deg,${GREEN},${GREEN_DARK}); color:#fff;
    display:flex; align-items:center; justify-content:center;
    cursor:pointer; transition:all .18s;
    box-shadow:0 2px 8px rgba(37,211,102,.4);
  }
  .lc-send-btn:hover { transform:scale(1.07); }
  .lc-send-btn:disabled { opacity:.45; cursor:default; transform:none; }

  /* Icon buttons (image/mic) in live chat input */
  .lc-icon-btn {
    width:34px; height:34px; border-radius:50%; border:1.5px solid #cce8d4;
    background:#f4fdf7; color:#25d366; display:flex; align-items:center;
    justify-content:center; cursor:pointer; flex-shrink:0; transition:all .18s;
  }
  .lc-icon-btn:hover { background:#25d366; color:#fff; border-color:#25d366; }
  .lc-mic-btn { user-select:none; -webkit-user-select:none; touch-action:none; }
  .lc-mic-btn.recording {
    background:linear-gradient(135deg,#e74c3c,#c0392b) !important;
    border-color:#e74c3c !important; color:#fff !important;
    animation:lc-pulse 1s infinite;
  }
  @keyframes lc-pulse { 0%,100%{box-shadow:0 0 0 0 rgba(231,76,60,.4)} 50%{box-shadow:0 0 0 6px rgba(231,76,60,0)} }

  /* Image preview bar */
  .lc-media-pre {
    display:flex; align-items:center; gap:8px;
    padding:6px 12px; background:#f4fdf7;
    border-top:1px solid rgba(37,211,102,.12);
    flex-shrink:0;
  }
  .lc-media-pre.lc-hidden { display:none; }

  /* Voice recording indicator */
  .lc-voice-rec {
    display:flex; align-items:center; gap:8px;
    padding:7px 12px; background:#fff8f8;
    border-top:1px solid #fddada; flex-shrink:0;
  }
  .lc-voice-rec.lc-hidden { display:none; }
  .lc-rec-dot {
    width:10px; height:10px; border-radius:50%; background:#e74c3c;
    animation:lc-blink .8s infinite; flex-shrink:0;
  }
  @keyframes lc-blink { 0%,100%{opacity:1} 50%{opacity:.3} }
  #lc-rec-time { font-size:12px; font-weight:700; color:#e74c3c; min-width:28px; }

  /* Chat image/voice bubbles */
  .lc-bub-img { max-width:180px; max-height:180px; border-radius:10px; display:block; cursor:pointer; }

  /* WhatsApp-style voice player */
  .lc-wv { display:flex; align-items:center; gap:9px; padding:3px 2px; min-width:170px; max-width:220px; }
  .lc-wv-btn {
    width:36px; height:36px; border-radius:50%; border:none; cursor:pointer;
    display:flex; align-items:center; justify-content:center;
    flex-shrink:0; font-size:13px; transition:transform .15s; line-height:1;
  }
  .lc-wv-btn:active { transform:scale(.93); }
  .lc-bub.usr .lc-wv-btn { background:rgba(255,255,255,.25); color:#fff; }
  .lc-bub.ai  .lc-wv-btn { background:#25d366; color:#fff; }
  .lc-wv-body { flex:1; display:flex; flex-direction:column; gap:4px; }
  .lc-wv-bars { display:flex; align-items:center; gap:2.5px; height:22px; }
  .lc-wv-bar  { width:3px; border-radius:3px; flex-shrink:0; transition:opacity .12s; }
  .lc-bub.usr .lc-wv-bar { background:rgba(255,255,255,.9); }
  .lc-bub.ai  .lc-wv-bar { background:#25d366; }
  .lc-wv-foot { display:flex; justify-content:space-between; font-size:10px; }
  .lc-bub.usr .lc-wv-foot { color:rgba(255,255,255,.7); }
  .lc-bub.ai  .lc-wv-foot { color:rgba(0,0,0,.45); }

  /* Powered by */
  .lc-powered {
    text-align:center; font-size:9.5px; color:#ccc;
    padding:4px 0 6px; background:#fff; flex-shrink:0;
  }

  /* Name entry screen */
  #lc-name-screen {
    flex:1; display:flex; flex-direction:column; align-items:center;
    justify-content:center; padding:28px 24px 20px; background:#f4fdf7; gap:12px;
  }
  #lc-name-screen.lc-hidden { display:none; }
  .lc-name-icon {
    width:54px; height:54px; border-radius:50%;
    background:linear-gradient(135deg,${GREEN},${GREEN_DARK});
    display:flex; align-items:center; justify-content:center;
    color:#fff; margin-bottom:4px;
  }
  .lc-name-title { font-size:15px; font-weight:700; color:#222; margin:0; text-align:center; }
  .lc-name-sub { font-size:12px; color:#888; margin:0; text-align:center; line-height:1.5; }
  .lc-name-inp-wrap { width:100%; }
  .lc-name-field {
    width:100%; box-sizing:border-box;
    font-size:13.5px; padding:11px 16px;
    border-radius:24px; border:1.5px solid #cce8d4;
    outline:none; color:#333; background:#fff;
    transition:border-color .2s;
  }
  .lc-name-field:focus { border-color:${GREEN}; box-shadow:0 0 0 3px rgba(37,211,102,.1); }
  .lc-name-field::placeholder { color:#aaa; }
  .lc-name-start {
    width:100%; padding:11px 0; border:none; border-radius:24px;
    background:linear-gradient(135deg,${GREEN},${GREEN_DARK}); color:#fff;
    font-size:13.5px; font-weight:700; cursor:pointer;
    transition:opacity .18s; box-shadow:0 3px 12px rgba(37,211,102,.4);
  }
  .lc-name-start:hover { opacity:.9; }

  /* === CHAT MODE SELECTOR OVERLAY === */
  #th-mode-overlay {
    position:fixed!important; inset:0!important; z-index:2147483645!important;
    background:rgba(0,0,0,.45); backdrop-filter:blur(4px);
    display:none; align-items:flex-end; justify-content:flex-end;
    padding-bottom:92px!important; padding-right:24px!important;
  }
  @media(max-width:480px){
    #th-mode-overlay{ padding-right:0!important; padding-bottom:80px!important; justify-content:center!important; }
  }
  #th-mode-overlay.th-show { display:flex; animation:th-ov-in .18s ease; }
  @keyframes th-ov-in{ from{opacity:0} to{opacity:1} }

  #th-mode-card {
    background:#fff; border-radius:22px; overflow:hidden;
    box-shadow:0 16px 56px rgba(0,0,0,.22),0 4px 16px rgba(0,0,0,.10);
    width:300px; max-width:calc(100vw - 32px);
    animation:th-pop .24s cubic-bezier(.34,1.3,.64,1);
  }
  .th-mode-hdr {
    background:linear-gradient(135deg,${PINK} 0%,#a52d55 100%);
    padding:18px 20px 14px; position:relative;
  }
  .th-mode-hdr-title { color:#fff; font-weight:800; font-size:15.5px; margin:0 0 3px; }
  .th-mode-hdr-sub   { color:rgba(255,255,255,.78); font-size:11.5px; margin:0; }
  .th-mode-hdr-close {
    position:absolute; top:12px; right:12px;
    background:rgba(255,255,255,.18); border:none; color:#fff;
    width:28px; height:28px; border-radius:8px;
    display:flex; align-items:center; justify-content:center;
    cursor:pointer; font-size:14px; transition:background .15s;
  }
  .th-mode-hdr-close:hover { background:rgba(255,255,255,.32); }

  .th-mode-opts { padding:10px 12px 12px; display:flex; flex-direction:column; gap:8px; }

  .th-mode-btn {
    display:flex; align-items:center; gap:14px;
    padding:14px 16px; cursor:pointer; border-radius:14px;
    border:1.5px solid #f0e8ed; background:#fff; width:100%; text-align:left;
    transition:all .18s; position:relative; overflow:hidden;
  }
  .th-mode-btn:hover { transform:translateY(-2px); box-shadow:0 6px 20px rgba(0,0,0,.10); }
  .th-mode-btn.th-ai-btn:hover   { border-color:${PINK}; background:#fff8fb; }
  .th-mode-btn.th-live-btn:hover { border-color:#25d366; background:#f0fdf4; }

  .th-mode-icon {
    width:48px; height:48px; border-radius:14px; flex-shrink:0;
    display:flex; align-items:center; justify-content:center;
  }
  .th-mode-icon.ai {
    background:linear-gradient(135deg,${PINK},#a52d55);
    color:#fff; font-size:20px; font-weight:800; letter-spacing:-1px;
  }
  .th-mode-icon.live {
    background:linear-gradient(135deg,#25d366,#128C7E); color:#fff;
  }
  .th-mode-txt { flex:1; min-width:0; }
  .th-mode-label { font-weight:700; font-size:14px; color:#222; line-height:1.2; }
  .th-mode-desc  { font-size:11px; color:#aaa; margin-top:3px; line-height:1.4; }
  .th-mode-arrow {
    color:#ddd; font-size:18px; flex-shrink:0; transition:color .18s;
  }
  .th-mode-btn:hover .th-mode-arrow { color:#bbb; }
  .th-mode-btn.th-ai-btn:hover   .th-mode-arrow { color:${PINK}; }
  .th-mode-btn.th-live-btn:hover .th-mode-arrow { color:#25d366; }

  .th-mode-badge {
    position:absolute; top:10px; right:36px;
    background:#25d366; color:#fff;
    font-size:8.5px; font-weight:800; padding:2px 7px; border-radius:10px;
    letter-spacing:.4px; text-transform:uppercase;
  }

  /* === LIVE CHAT LIST VIEW === */
  #lc-list-view { flex:1; display:flex; flex-direction:column; overflow:hidden; min-height:0; }
  #lc-list-view.lc-hidden { display:none; }
  #lc-chat-view { flex:1; display:flex; flex-direction:column; overflow:hidden; min-height:0; }
  #lc-chat-view.lc-hidden { display:none; }

  .lc-list-top {
    padding:10px 14px; border-bottom:1px solid #d4f0e0; background:#f0fdf6;
    display:flex; align-items:center; justify-content:space-between; flex-shrink:0;
  }
  .lc-list-lbl { font-size:11.5px; font-weight:700; color:#128C7E; }
  .lc-new-btn {
    background:linear-gradient(135deg,${GREEN},${GREEN_DARK}); color:#fff; border:none;
    border-radius:20px; padding:6px 14px; font-size:11.5px; font-weight:700;
    cursor:pointer; transition:opacity .18s; box-shadow:0 2px 8px rgba(37,211,102,.3);
  }
  .lc-new-btn:hover { opacity:.88; }
  .lc-list-scroll { flex:1; overflow-y:auto; }
  .lc-list-scroll::-webkit-scrollbar { width:3px; }
  .lc-list-scroll::-webkit-scrollbar-thumb { background:rgba(37,211,102,.25); border-radius:4px; }

  .lc-list-item {
    display:flex; align-items:center; padding:12px 14px; gap:11px;
    cursor:pointer; border-bottom:1px solid #f0f0f0; transition:background .15s;
  }
  .lc-list-item:hover { background:#f4fdf7; }
  .lc-list-av {
    width:42px; height:42px; border-radius:50%; color:#fff;
    display:flex; align-items:center; justify-content:center;
    font-size:18px; font-weight:800; flex-shrink:0; letter-spacing:0;
  }
  .lc-list-info { flex:1; min-width:0; }
  .lc-list-name { font-weight:700; font-size:13px; color:#1a1a1a; margin:0 0 2px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
  .lc-list-preview { font-size:11.5px; color:#888; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; margin:0; }
  .lc-list-meta { display:flex; flex-direction:column; align-items:flex-end; gap:4px; flex-shrink:0; }
  .lc-list-time { font-size:10px; color:#aaa; white-space:nowrap; }
  .lc-list-pill {
    font-size:9px; font-weight:800; padding:2px 7px; border-radius:10px;
    text-transform:uppercase; letter-spacing:.4px;
  }
  .lc-list-pill.waiting { background:#fff3cd; color:#856404; }
  .lc-list-pill.active  { background:#d4edda; color:#155724; }
  .lc-list-pill.closed  { background:#e9ecef; color:#6c757d; }
  .lc-list-empty {
    flex:1; display:flex; flex-direction:column; align-items:center;
    justify-content:center; padding:32px 20px; gap:10px; text-align:center;
  }
  .lc-list-empty-icon { font-size:2.8rem; margin-bottom:4px; }
  .lc-list-empty p { font-size:13px; color:#999; margin:0; line-height:1.5; }
  .lc-list-empty-btn {
    margin-top:4px; background:linear-gradient(135deg,${GREEN},${GREEN_DARK}); color:#fff;
    border:none; border-radius:24px; padding:10px 28px; font-size:13px; font-weight:700;
    cursor:pointer; box-shadow:0 3px 12px rgba(37,211,102,.4); transition:opacity .18s;
  }
  .lc-list-empty-btn:hover { opacity:.88; }

  /* === SELECTION MODE (widget) === */
  .lc-sel-btn {
    background:none; border:1px solid #b2dfdb; color:#128C7E;
    border-radius:20px; padding:4px 11px; font-size:11px; font-weight:700;
    cursor:pointer; transition:all .15s; margin-left:6px;
  }
  .lc-sel-btn:hover { background:#e0f2f1; }
  #lc-sel-bar {
    padding:7px 14px; border-bottom:1px solid #ffe082; background:#fffde7;
    display:none; align-items:center; gap:8px; flex-shrink:0;
  }
  #lc-sel-bar.lc-sel-active { display:flex; }
  .lc-sel-all-lbl {
    display:flex; align-items:center; gap:5px;
    font-size:11.5px; color:#555; cursor:pointer; user-select:none;
  }
  .lc-sel-cb { width:14px; height:14px; cursor:pointer; accent-color:${GREEN}; }
  #lc-sel-count { font-size:11px; color:#888; flex:1; }
  .lc-sel-del-btn {
    background:#e74c3c; color:#fff; border:none;
    border-radius:14px; padding:5px 13px; font-size:11px; font-weight:700;
    cursor:pointer; opacity:.4; pointer-events:none; transition:opacity .15s;
  }
  .lc-sel-del-btn.lc-can-del { opacity:1; pointer-events:auto; }
  .lc-sel-cancel-btn {
    font-size:11px; color:#aaa; background:none; border:none;
    cursor:pointer; padding:4px 6px; font-weight:700;
  }
  .lc-list-item-cb {
    width:15px; height:15px; cursor:pointer; accent-color:${GREEN};
    flex-shrink:0; display:none;
  }
  .lc-sel-mode .lc-list-item-cb { display:block; }
  .lc-sel-mode .lc-list-item { cursor:default; }
  .lc-list-item.lc-selected { background:#f0fdf4; }

  /* Back button */
  .lc-back-btn {
    background:rgba(255,255,255,.18); border:none; color:#fff;
    width:30px; height:30px; border-radius:8px; flex-shrink:0;
    display:flex; align-items:center; justify-content:center;
    cursor:pointer; transition:background .15s;
  }
  .lc-back-btn:hover { background:rgba(255,255,255,.32); }
  .lc-back-btn.lc-hidden { display:none; }
  `;

  var styleEl = document.createElement("style");
  styleEl.textContent = css;
  document.head.appendChild(styleEl);

  /* ── HTML ────────────────────────────────────────────────── */
  /* ── SVG for live chat headset icon ─── */
  var SVG_HEADSET = '<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 18v-6a9 9 0 0 1 18 0v6"/><path d="M21 19a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3z"/><path d="M3 19a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-3a2 2 0 0 0-2-2H3z"/></svg>';
  var SVG_ARROW_R = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>';
  var SVG_BACK    = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>';

  var wrap = document.createElement("div");
  wrap.innerHTML =

    /* ── Mode selector overlay ── */
    '<div id="th-mode-overlay">' +
      '<div id="th-mode-card">' +
        '<div class="th-mode-hdr">' +
          '<p class="th-mode-hdr-title">Hi! How can we help? &#128075;</p>' +
          '<p class="th-mode-hdr-sub">Choose how you\'d like to connect with us</p>' +
          '<button class="th-mode-hdr-close" id="th-mode-close" title="Close">' + IC.close + '</button>' +
        '</div>' +
        '<div class="th-mode-opts">' +
          '<button class="th-mode-btn th-ai-btn" id="th-mode-ai">' +
            '<div class="th-mode-icon ai">L</div>' +
            '<div class="th-mode-txt">' +
              '<div class="th-mode-label">AI Chat</div>' +
              '<div class="th-mode-desc">Instant answers from Laiba, our AI stylist</div>' +
            '</div>' +
            '<span class="th-mode-arrow">' + SVG_ARROW_R + '</span>' +
          '</button>' +
          '<button class="th-mode-btn th-live-btn" id="th-mode-live">' +
            '<div class="th-mode-icon live">' + SVG_HEADSET + '</div>' +
            '<div class="th-mode-txt">' +
              '<div class="th-mode-label">Live Chat</div>' +
              '<div class="th-mode-desc">Chat with our real team — we\'re here to help</div>' +
            '</div>' +
            '<span class="th-mode-arrow">' + SVG_ARROW_R + '</span>' +
            '<span class="th-mode-badge">LIVE</span>' +
          '</button>' +
        '</div>' +
      '</div>' +
    '</div>' +

    '<button id="th-fab" title="Chat with us — Kapraa">' +
      '<span style="font-size:22px;font-weight:700;letter-spacing:-1px">L</span>' +
      '<span id="th-notif" style="display:none">1</span>' +
    '</button>' +

    '<div id="' + CHAT_ID + '">' +
      '<div class="th-hdr">' +
        '<div class="th-hdr-av">L<span class="th-hdr-av-dot"></span></div>' +
        '<div class="th-hdr-info">' +
          '<p class="th-hdr-name">Laiba — AI Stylist</p>' +
          '<p class="th-hdr-sub" id="th-status">Kapraa &bull; Online</p>' +
        '</div>' +
        '<div class="th-hdr-btns">' +
          '<button class="th-hdr-btn" id="th-tts-toggle" title="Toggle voice replies">' + IC.mute + '</button>' +
          '<button class="th-hdr-btn" id="th-clear" title="Clear chat">' + IC.trash + '</button>' +
          '<button class="th-hdr-btn" id="th-close" title="Close">' + IC.close + '</button>' +
        '</div>' +
      '</div>' +

      '<div class="th-level-bar">' +
        '<span class="th-level-lbl"><svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#aaa" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:3px"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>Level:</span>' +
        '<div class="th-level-btns">' +
          '<button class="th-lvl-btn" data-level="beginner">Beginner</button>' +
          '<button class="th-lvl-btn active" data-level="normal">Normal</button>' +
          '<button class="th-lvl-btn" data-level="advanced">Advanced</button>' +
        '</div>' +
      '</div>' +

      '<div class="th-msgs" id="th-msgs"></div>' +

      '<div class="th-chips" id="th-chips"></div>' +

      '<div class="th-img-pre" id="th-img-pre">' +
        '<img class="th-pre-img" id="th-pre-img" src="" alt="">' +
        '<span class="th-pre-txt" id="th-pre-txt">Image ready</span>' +
        '<button class="th-pre-clr" id="th-pre-clr">' + IC.closeSm + '</button>' +
      '</div>' +
      '<div class="th-voice-st" id="th-voice-st">Listening…</div>' +

      '<div class="th-inp-row">' +
        '<input class="th-inp" id="th-inp" type="text" placeholder="Ask Laiba anything…" autocomplete="off">' +
        '<button class="th-icon-btn th-img-btn"   id="th-img-btn"   title="Upload photo">' + IC.camera + '</button>' +
        '<button class="th-icon-btn th-voice-btn" id="th-voice-btn" title="Voice message">' + IC.mic + '</button>' +
        '<button class="th-icon-btn th-send-btn"  id="th-send-btn"  title="Send">' + IC.send + '</button>' +
      '</div>' +
      '<div class="th-powered">✦ Powered by Laiba AI &bull; Kapraa</div>' +
      '<input type="file" id="th-file-inp" accept="image/*">' +
    '</div>' +

    /* ── Live Chat window ── */
    '<div id="th-live-chat">' +

      /* Header — shared, content swapped by JS */
      '<div class="lc-hdr">' +
        '<button class="lc-back-btn lc-hidden" id="lc-back" title="Back">' + SVG_BACK + '</button>' +
        '<div class="lc-hdr-av-wrap">' +
          '<div class="lc-hdr-av">' + SVG_HEADSET + '</div>' +
          '<span class="lc-hdr-av-dot"></span>' +
        '</div>' +
        '<div class="lc-hdr-info">' +
          '<p class="lc-hdr-name" id="lc-hdr-title">My Chats</p>' +
          '<p class="lc-hdr-sub" id="lc-status-sub">Kapraa Support</p>' +
        '</div>' +
        '<div class="lc-hdr-btns">' +
          '<button class="lc-hdr-btn" id="lc-close" title="Close">' + IC.close + '</button>' +
        '</div>' +
      '</div>' +

      /* LIST VIEW */
      '<div id="lc-list-view">' +
        '<div class="lc-list-top">' +
          '<span class="lc-list-lbl">&#128172; Your conversations</span>' +
          '<div style="display:flex;align-items:center;gap:4px;">' +
            '<button class="lc-sel-btn" id="lc-sel-mode-btn">Select</button>' +
            '<button class="lc-new-btn" id="lc-new-btn">+ New</button>' +
          '</div>' +
        '</div>' +
        '<div id="lc-sel-bar">' +
          '<label class="lc-sel-all-lbl"><input type="checkbox" class="lc-sel-cb" id="lc-sel-all"> All</label>' +
          '<span id="lc-sel-count">0 selected</span>' +
          '<button class="lc-sel-del-btn" id="lc-sel-del-btn">' + IC.trash + ' Delete</button>' +
          '<button class="lc-sel-cancel-btn" id="lc-sel-cancel-btn">Cancel</button>' +
        '</div>' +
        '<div class="lc-list-scroll" id="lc-list-scroll"></div>' +
      '</div>' +

      /* CHAT VIEW */
      '<div id="lc-chat-view" class="lc-hidden">' +
        '<div id="lc-name-screen">' +
          '<div class="lc-name-icon">' + SVG_HEADSET + '</div>' +
          '<p class="lc-name-title">Start a live chat</p>' +
          '<p class="lc-name-sub">Enter your name and we\'ll connect you<br>with our support team right away.</p>' +
          '<div class="lc-name-inp-wrap">' +
            '<input class="lc-name-field" id="lc-name-field" type="text" placeholder="Your name…" autocomplete="off" maxlength="60">' +
          '</div>' +
          '<button class="lc-name-start" id="lc-name-start">Start Chat</button>' +
        '</div>' +
        '<div class="lc-msgs lc-hidden" id="lc-msgs"></div>' +
        '<div class="lc-status-bar lc-waiting lc-hidden" id="lc-status-bar">⏳ Connecting to Laiba…</div>' +
        /* Image preview bar */
        '<div class="lc-media-pre lc-hidden" id="lc-media-pre">' +
          '<img id="lc-pre-img" src="" alt="" style="max-height:60px;max-width:80px;border-radius:6px;object-fit:cover;">' +
          '<span id="lc-pre-lbl" style="font-size:11px;color:#555;flex:1;"></span>' +
          '<button id="lc-pre-clr" style="background:none;border:none;font-size:18px;color:#aaa;cursor:pointer;line-height:1;">&times;</button>' +
        '</div>' +
        /* Voice recording indicator */
        '<div class="lc-voice-rec lc-hidden" id="lc-voice-rec">' +
          '<span class="lc-rec-dot"></span>' +
          '<span id="lc-rec-time">0:00</span>' +
          '<span style="flex:1;font-size:11px;color:#555;padding-left:6px;">Recording… release to send</span>' +
          '<button id="lc-rec-cancel" style="background:none;border:none;font-size:12px;color:#e74c3c;cursor:pointer;font-weight:700;">Cancel</button>' +
        '</div>' +
        '<div class="lc-inp-row lc-hidden" id="lc-inp-row">' +
          '<button class="lc-icon-btn" id="lc-img-btn" title="Send image">' +
            '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>' +
          '</button>' +
          '<button class="lc-icon-btn lc-mic-btn" id="lc-mic-btn" title="Hold to record voice">' +
            '<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><rect x="9" y="2" width="6" height="12" rx="3"/><path fill="none" stroke="currentColor" stroke-width="2" d="M19 10a7 7 0 01-14 0"/><line stroke="currentColor" stroke-width="2" x1="12" y1="17" x2="12" y2="21"/><line stroke="currentColor" stroke-width="2" x1="8" y1="21" x2="16" y2="21"/></svg>' +
          '</button>' +
          '<input class="lc-inp" id="lc-inp" type="text" placeholder="Type your message…" autocomplete="off">' +
          '<input type="file" id="lc-file-inp" accept="image/*" style="display:none">' +
          '<button class="lc-send-btn" id="lc-send" title="Send">' + IC.send + '</button>' +
        '</div>' +
      '</div>' +

      '<div class="lc-powered">Kapraa Live Support</div>' +
    '</div>';

  document.body.appendChild(wrap);

  /* ── REFS ───────────────────────────────────────────────── */
  var modeOverlay  = document.getElementById("th-mode-overlay");
  var modeCloseBtn = document.getElementById("th-mode-close");
  var modeAiBtn    = document.getElementById("th-mode-ai");
  var modeLiveBtn  = document.getElementById("th-mode-live");
  /* live chat refs */
  var lcWin       = document.getElementById("th-live-chat");
  var lcNameScreen= document.getElementById("lc-name-screen");
  var lcNameField = document.getElementById("lc-name-field");
  var lcNameStart = document.getElementById("lc-name-start");
  var lcMsgs      = document.getElementById("lc-msgs");
  var lcInpRow    = document.getElementById("lc-inp-row");
  var lcInp       = document.getElementById("lc-inp");
  var lcSendBtn   = document.getElementById("lc-send");
  var lcCloseBtn  = document.getElementById("lc-close");
  var lcStatusBar = document.getElementById("lc-status-bar");
  var lcStatusSub = document.getElementById("lc-status-sub");
  /* media refs */
  var lcImgBtn    = document.getElementById("lc-img-btn");
  var lcMicBtn    = document.getElementById("lc-mic-btn");
  var lcFileInp   = document.getElementById("lc-file-inp");
  var lcMediaPre  = document.getElementById("lc-media-pre");
  var lcPreImg    = document.getElementById("lc-pre-img");
  var lcPreLbl    = document.getElementById("lc-pre-lbl");
  var lcPreClr    = document.getElementById("lc-pre-clr");
  var lcVoiceRec  = document.getElementById("lc-voice-rec");
  var lcRecTime   = document.getElementById("lc-rec-time");
  var lcRecCancel = document.getElementById("lc-rec-cancel");
  /* pending media */
  var lcPendingFile = null;  // {blob, type:'image'|'voice'}
  /* voice recorder state */
  var lcMediaRecorder = null;
  var lcAudioChunks   = [];
  var lcRecTimer      = null;
  var lcRecSeconds    = 0;
  var lcIsRecording   = false; // guard against concurrent recordings
  var fab       = document.getElementById("th-fab");
  var chat      = document.getElementById(CHAT_ID);
  var msgs      = document.getElementById("th-msgs");
  var inp       = document.getElementById("th-inp");
  var sendBtn   = document.getElementById("th-send-btn");
  var closeBtn  = document.getElementById("th-close");
  var clearBtn  = document.getElementById("th-clear");
  var voiceBtn  = document.getElementById("th-voice-btn");
  var imgBtn    = document.getElementById("th-img-btn");
  var fileInp   = document.getElementById("th-file-inp");
  var imgPre    = document.getElementById("th-img-pre");
  var preImg    = document.getElementById("th-pre-img");
  var preTxt    = document.getElementById("th-pre-txt");
  var preClr    = document.getElementById("th-pre-clr");
  var voiceSt   = document.getElementById("th-voice-st");
  var statusEl  = document.getElementById("th-status");
  var chipsEl   = document.getElementById("th-chips");
  var notif     = document.getElementById("th-notif");

  var history        = [];
  var lastShownProds = [];  // last carousel products — for price/detail follow-up questions
  var b64       = null;
  var mime      = null;
  var cloudinaryUrl = null;
  var opened    = false;
  var count     = 0;
  var userLevel = "normal"; // beginner | normal | advanced
  var ttsEnabled = false;   // voice replies toggle
  var ttsToggleBtn = document.getElementById("th-tts-toggle");
  var isWaiting = false;    // true while waiting for AI reply — blocks extra sends

  /* ── PERSISTENCE (localStorage) ────────────────────────── */
  var SK = { msgs:'th_msgs', hist:'th_hist', cnt:'th_cnt',
             lvl:'th_lvl',   open:'th_open', ts:'th_ts', notif:'th_notif' };
  var EXPIRY_MS = 60 * 60 * 1000; // 1 hour auto-clear

  // Detect page refresh (reload) vs navigation between pages
  var isRefresh = false;
  try {
    var _navType = (performance.getEntriesByType("navigation")[0] || {}).type;
    isRefresh = (_navType === "reload");
  } catch(e) {}

  // On refresh → wipe stored chat
  if (isRefresh) {
    try { [SK.msgs,SK.hist,SK.cnt,SK.lvl,SK.open,SK.ts,SK.notif].forEach(function(k){ localStorage.removeItem(k); }); } catch(e) {}
  }

  function saveState() {
    try {
      localStorage.setItem(SK.msgs,  msgs.innerHTML);
      localStorage.setItem(SK.hist,  JSON.stringify(history));
      localStorage.setItem(SK.cnt,   String(count));
      localStorage.setItem(SK.lvl,   userLevel);
      localStorage.setItem(SK.open,  chat.classList.contains("th-open") ? "1" : "0");
      localStorage.setItem(SK.ts,    String(Date.now()));
    } catch(e) {}
  }

  function loadState() {
    try {
      var ts = parseInt(localStorage.getItem(SK.ts) || "0");
      if (!ts || (Date.now() - ts) > EXPIRY_MS) {
        try { [SK.msgs,SK.hist,SK.cnt,SK.lvl,SK.open,SK.ts,SK.notif].forEach(function(k){ localStorage.removeItem(k); }); } catch(e) {}
        return false;
      }
      var savedMsgs = localStorage.getItem(SK.msgs);
      var savedHist = localStorage.getItem(SK.hist);
      if (savedMsgs && savedMsgs.trim()) { msgs.innerHTML = savedMsgs; scrollDown(); }
      if (savedHist) { try { history = JSON.parse(savedHist); } catch(e) {} }
      count     = parseInt(localStorage.getItem(SK.cnt) || "0");
      userLevel = localStorage.getItem(SK.lvl) || "normal";
      // Restore level button highlight
      chat.querySelectorAll(".th-lvl-btn").forEach(function(b){
        b.classList.toggle("active", b.getAttribute("data-level") === userLevel);
      });
      return true;
    } catch(e) { return false; }
  }

  function playChime() {
    try {
      var ctx = new (window.AudioContext || window.webkitAudioContext)();
      var osc = ctx.createOscillator();
      var gain = ctx.createGain();
      osc.connect(gain); gain.connect(ctx.destination);
      osc.type = "sine";
      osc.frequency.setValueAtTime(880, ctx.currentTime);
      osc.frequency.exponentialRampToValueAtTime(660, ctx.currentTime + 0.15);
      gain.gain.setValueAtTime(0.15, ctx.currentTime);
      gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.45);
      osc.start(ctx.currentTime);
      osc.stop(ctx.currentTime + 0.45);
    } catch(e) {}
  }

  function notifyUser() {
    if (!chat.classList.contains("th-open")) {
      var n = parseInt(localStorage.getItem(SK.notif) || "0") + 1;
      try { localStorage.setItem(SK.notif, String(n)); } catch(e) {}
      notif.textContent = n > 9 ? "9+" : String(n);
      notif.style.display = "flex";
      playChime();
    }
  }

  // Restore state on page load (navigation, not refresh)
  var _restored = loadState();
  if (_restored) {
    opened = true; // history exists — skip welcome on first open
    // Re-open chat if it was open before navigation
    if (localStorage.getItem(SK.open) === "1") {
      chat.classList.add("th-open");
    }
  }

  // Show pending notification badge if any
  var _pendingNotif = parseInt(localStorage.getItem(SK.notif) || "0");
  if (_pendingNotif > 0 && !chat.classList.contains("th-open")) {
    notif.textContent = _pendingNotif > 9 ? "9+" : String(_pendingNotif);
    notif.style.display = "flex";
  }

  /* ── LEVEL BUTTONS ──────────────────────────────────────── */
  chat.querySelectorAll(".th-lvl-btn").forEach(function(btn) {
    btn.addEventListener("click", function() {
      chat.querySelectorAll(".th-lvl-btn").forEach(function(b){ b.classList.remove("active"); });
      btn.classList.add("active");
      userLevel = btn.getAttribute("data-level");
    });
  });

  /* ── CHIPS ──────────────────────────────────────────────── */
  // SVG icons for chips (14x14, inline)
  var CI = {
    star  : '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>',
    moon  : '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>',
    kids  : '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="5" r="3"/><path d="M12 8v8M9 11h6M9 22l3-6 3 6"/></svg>',
    tag   : '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>',
    bulb  : '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="9" y1="18" x2="15" y2="18"/><line x1="10" y1="22" x2="14" y2="22"/><path d="M15.09 14c.18-.98.65-1.74 1.41-2.5A4.65 4.65 0 0 0 18 8 6 6 0 0 0 6 8c0 1 .23 2.23 1.5 3.5A4.61 4.61 0 0 1 8.91 14"/></svg>',
    dress : '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 2h8l2 6-4 2v12H10V10L6 8z"/></svg>',
    needle: '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21l6-6m0 0l9-9-3-3-9 9m0 0l-3 3"/></svg>',
    ring  : '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="14" r="8"/><path d="M8.56 2.75c4.37 6.03 6.02 9.42 8 17.72m2.54-15.38c-3.72 4.35-8.94 5.66-16.88 5.85m19.5 1.9c-3.5-.93-6.63-.82-8.94 0-2.58.92-5.01 2.86-7.44 6.32"/></svg>',
    shirt : '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.38 3.46L16 2a4 4 0 0 1-8 0L3.62 3.46a2 2 0 0 0-1.34 2.23l.58 3.57a1 1 0 0 0 .99.84H6v10c0 1.1.9 2 2 2h8a2 2 0 0 0 2-2V10h2.15a1 1 0 0 0 .99-.84l.58-3.57a2 2 0 0 0-1.34-2.23z"/></svg>',
    boy   : '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="5" r="3"/><path d="M12 8v5m-3 3l-2 5m5-5l2 5m-2-5H10"/></svg>',
    girl  : '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="5" r="3"/><path d="M12 8v4m-4 4h8m-6 0v5m4-5v5"/></svg>',
    money : '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 1 0 0 7h5a3.5 3.5 0 1 1 0 7H6"/></svg>',
    fire  : '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8.5 14.5A2.5 2.5 0 0 0 11 12c0-1.38-.5-2-1-3-1.072-2.143-.224-4.054 2-6 .5 2.5 2 4.9 4 6.5 2 1.6 3 3.5 3 5.5a7 7 0 1 1-14 0c0-1.153.433-2.294 1-3a2.5 2.5 0 0 0 2.5 2.5z"/></svg>',
    zap   : '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>',
  };
  function chip(icon, label) { return icon + ' ' + label; }

  var chipSets = {
    default: [
      { q: "Show new arrivals",          l: chip(CI.star,  "New In")  },
      { q: "Eid collection suits",       l: chip(CI.moon,  "Eid")     },
      { q: "Kids wear collection",       l: chip(CI.kids,  "Kids")    },
      { q: "Sale items discount",        l: chip(CI.tag,   "Sale")    },
      { q: "Suggest me something nice",  l: chip(CI.bulb,  "Suggest") },
    ],
    women: [
      { q: "Lawn 3 piece suits",         l: chip(CI.dress,  "Lawn")       },
      { q: "Unstitched embroidered suits",l: chip(CI.needle,"Unstitched") },
      { q: "Embroidered formal wear",    l: chip(CI.star,   "Embroidered")},
      { q: "Bridal collection",          l: chip(CI.ring,   "Bridal")     },
      { q: "Sale items",                 l: chip(CI.tag,    "Sale")       },
    ],
    men: [
      { q: "Mens kurta pajama",          l: chip(CI.shirt, "Kurta")        },
      { q: "Mens shalwar kameez",        l: chip(CI.shirt, "Shalwar")      },
      { q: "Mens formal shirts",         l: chip(CI.shirt, "Shirts")       },
      { q: "Sale items",                 l: chip(CI.tag,   "Sale")         },
    ],
    kids: [
      { q: "Kids boys collection",       l: chip(CI.boy,   "Boys")   },
      { q: "Kids girls dresses",         l: chip(CI.girl,  "Girls")  },
      { q: "Affordable kids suits",      l: chip(CI.money, "Budget") },
    ],
    sale: [
      { q: "Best sale deals",            l: chip(CI.fire,  "Hot Deals") },
      { q: "Sale under Rs 3000",         l: chip(CI.money, "Under 3k")  },
      { q: "Sale lawn suits",            l: chip(CI.dress, "Lawn Sale") },
      { q: "New arrivals on sale",       l: chip(CI.zap,   "New Sale")  },
    ],
  };

  function setChips(ctx) {
    var set = chipSets[ctx] || chipSets.default;
    chipsEl.innerHTML = set.map(function(c) {
      return '<button class="th-chip" data-q="' + esc(c.q) + '">' + c.l + '</button>';
    }).join("");
    chipsEl.querySelectorAll(".th-chip").forEach(function(b) {
      b.addEventListener("click", function() {
        inp.value = b.getAttribute("data-q");
        send();
      });
    });
  }
  setChips("default");

  /* ── SELECTOR HELPERS ───────────────────────────────────── */
  function openSelector() {
    modeOverlay.classList.add("th-show");
  }
  function closeSelector() {
    modeOverlay.classList.remove("th-show");
  }
  function openAiChat() {
    closeSelector();
    chat.classList.add("th-open");
    notif.style.display = "none";
    notif.textContent = "";
    try { localStorage.removeItem(SK.notif); } catch(e) {}
    inp.focus();
    if (!opened) { opened = true; welcome(); saveState(); }
  }
  /* ══════════════════════════════════════════════════════════
     LIVE CHAT — WhatsApp-style multi-session
     localStorage: th_lc_chats = [{id,sid,name,preview,time,status,last_id}]
  ══════════════════════════════════════════════════════════ */
  var LC_SK    = { chats: 'th_lc_chats', name: 'th_lc_visitor_name' };
  // Avatar color helper: derive a stable color from the first letter of a name
  function lcAvBg(name) {
    var avColors = ['#e91e63','#9c27b0','#673ab7','#3f51b5','#2196f3','#00897b','#f57c00','#d32f2f','#00796b','#1565c0'];
    var ch = (name || 'V').trim().charAt(0).toUpperCase();
    return avColors[ch.charCodeAt(0) % avColors.length];
  }

  var lcChats  = [];    // array of chat session objects
  var lcId     = null;  // active chat_id
  var lcSid    = null;  // active session_id
  var lcLast   = 0;     // last polled message id
  var lcTimer  = null;
  var lcOpen   = false;
  var lcSelMode = false; // selection mode active?

  /* ── Extra refs needed for new UI ── */
  var lcBackBtn    = document.getElementById('lc-back');
  var lcListView   = document.getElementById('lc-list-view');
  var lcListScroll = document.getElementById('lc-list-scroll');
  var lcChatView   = document.getElementById('lc-chat-view');
  var lcNewBtn     = document.getElementById('lc-new-btn');
  var lcHdrTitle   = document.getElementById('lc-hdr-title');

  /* ── Persist / load chat list ─────────────────────────── */
  function lcSaveChats() {
    try { localStorage.setItem(LC_SK.chats, JSON.stringify(lcChats)); } catch(e){}
  }
  function lcLoadChats() {
    try {
      var raw = localStorage.getItem(LC_SK.chats);
      if (raw) { var parsed = JSON.parse(raw); if (Array.isArray(parsed)) { lcChats = parsed; return; } }
    } catch(e){}
    lcChats = [];
  }

  /* ── Update a chat entry in the list ─────────────────── */
  function lcUpdateChat(id, updates) {
    for (var i = 0; i < lcChats.length; i++) {
      if (lcChats[i].id === id) {
        for (var k in updates) lcChats[i][k] = updates[k];
        lcSaveChats();
        return;
      }
    }
  }

  /* ── View switching ───────────────────────────────────── */
  function lcSwitchView(view) {
    if (view === 'list') {
      lcListView.classList.remove('lc-hidden');
      lcChatView.classList.add('lc-hidden');
      lcBackBtn.classList.add('lc-hidden');
      lcHdrTitle.textContent = 'My Chats';
      lcStatusSub.textContent = 'Kapraa Support';
      lcRenderList();
    } else {
      lcListView.classList.add('lc-hidden');
      lcChatView.classList.remove('lc-hidden');
      lcBackBtn.classList.remove('lc-hidden');
    }
  }

  /* ── Render the chat list panel ───────────────────────── */
  function lcRenderList() {
    if (!lcChats.length) {
      lcListScroll.innerHTML =
        '<div class="lc-list-empty">' +
          '<div class="lc-list-empty-icon">&#128172;</div>' +
          '<p>No chats yet. Start a conversation<br>with our support team!</p>' +
          '<button class="lc-list-empty-btn" id="lc-empty-start-btn">Start Chat</button>' +
        '</div>';
      var eb = document.getElementById('lc-empty-start-btn');
      if (eb) eb.addEventListener('click', lcBeginNewChat);
      return;
    }

    lcListScroll.innerHTML = lcChats.map(function(c, idx) {
      var avLetter = (c.name || 'V').trim().charAt(0).toUpperCase();
      var avColor  = lcAvBg(c.name);
      var pillClass = c.status === 'active' ? 'active' : (c.status === 'closed' ? 'closed' : 'waiting');
      var pillText  = c.status === 'active' ? 'Active' : (c.status === 'closed' ? 'Ended' : 'Waiting');
      var preview;
      if (c.previewType === 'image') {
        preview = '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:3px"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>Image';
      } else if (c.previewType === 'voice') {
        preview = '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:3px"><rect x="9" y="2" width="6" height="12" rx="3"/><path d="M19 10a7 7 0 01-14 0"/><line x1="12" y1="17" x2="12" y2="21"/><line x1="8" y1="21" x2="16" y2="21"/></svg>Voice message';
      } else {
        preview = c.preview ? lcEsc(c.preview.substring(0,42) + (c.preview.length > 42 ? '…' : '')) : 'No messages yet';
      }
      return '<div class="lc-list-item" data-idx="' + idx + '">' +
        '<input type="checkbox" class="lc-list-item-cb" data-idx="' + idx + '">' +
        '<div class="lc-list-av" style="background:' + avColor + ';">' + avLetter + '</div>' +
        '<div class="lc-list-info">' +
          '<p class="lc-list-name">' + lcEsc(c.name || 'Visitor') + '</p>' +
          '<p class="lc-list-preview">' + preview + '</p>' +
        '</div>' +
        '<div class="lc-list-meta">' +
          '<span class="lc-list-time">' + lcEsc(c.time || '') + '</span>' +
          '<span class="lc-list-pill ' + pillClass + '">' + pillText + '</span>' +
        '</div>' +
      '</div>';
    }).join('');

    lcListScroll.querySelectorAll('.lc-list-item').forEach(function(el) {
      el.addEventListener('click', function(e) {
        if (lcSelMode) {
          // In select mode — toggle checkbox, don't open chat
          var cb = el.querySelector('.lc-list-item-cb');
          if (e.target !== cb) cb.checked = !cb.checked;
          el.classList.toggle('lc-selected', cb.checked);
          lcSelUpdateBar();
          return;
        }
        lcOpenChatByIdx(parseInt(this.dataset.idx));
      });
    });
    // Prevent checkbox click propagating twice
    lcListScroll.querySelectorAll('.lc-list-item-cb').forEach(function(cb) {
      cb.addEventListener('change', function(e) {
        var item = cb.closest('.lc-list-item');
        item.classList.toggle('lc-selected', cb.checked);
        lcSelUpdateBar();
      });
      cb.addEventListener('click', function(e) { e.stopPropagation(); });
    });
  }

  /* ── Selection mode helpers ───────────────────────────── */
  function lcEnterSelMode() {
    lcSelMode = true;
    lcListScroll.classList.add('lc-sel-mode');
    document.getElementById('lc-sel-bar').classList.add('lc-sel-active');
    document.getElementById('lc-sel-mode-btn').style.display = 'none';
    lcSelUpdateBar();
  }
  function lcExitSelMode() {
    lcSelMode = false;
    lcListScroll.classList.remove('lc-sel-mode');
    document.getElementById('lc-sel-bar').classList.remove('lc-sel-active');
    document.getElementById('lc-sel-mode-btn').style.display = '';
    document.getElementById('lc-sel-all').checked = false;
    // Uncheck + deselect all items
    lcListScroll.querySelectorAll('.lc-list-item-cb').forEach(function(cb){ cb.checked = false; });
    lcListScroll.querySelectorAll('.lc-list-item').forEach(function(el){ el.classList.remove('lc-selected'); });
  }
  function lcSelUpdateBar() {
    var checked = lcListScroll.querySelectorAll('.lc-list-item-cb:checked').length;
    var total   = lcChats.length;
    var countEl = document.getElementById('lc-sel-count');
    var delBtn  = document.getElementById('lc-sel-del-btn');
    var selAll  = document.getElementById('lc-sel-all');
    if (countEl) countEl.textContent = checked ? checked + ' selected' : '0 selected';
    if (delBtn)  { delBtn.classList.toggle('lc-can-del', checked > 0); }
    if (selAll)  selAll.checked = (checked === total && total > 0);
  }
  function lcDeleteSelected() {
    var toDelete = [];
    lcListScroll.querySelectorAll('.lc-list-item-cb:checked').forEach(function(cb){
      toDelete.push(parseInt(cb.dataset.idx));
    });
    if (!toDelete.length) return;
    // Sort highest index first to preserve indices during splice
    toDelete.sort(function(a,b){ return b - a; });

    // Notify admin for each chat being deleted (fire-and-forget, don't block UI)
    toDelete.forEach(function(i) {
      var c = lcChats[i];
      if (c && c.id && c.sid) {
        fetch(LC_URL, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ action: 'user_delete', chat_id: c.id, session_id: c.sid })
        }).catch(function(){});
      }
    });

    // Remove from local list
    toDelete.forEach(function(i){ lcChats.splice(i, 1); });
    lcSaveChats();
    lcExitSelMode();
    lcRenderList();
  }

  // Wire up selection bar buttons (after DOM is ready)
  (function() {
    var selModeBtn = document.getElementById('lc-sel-mode-btn');
    var selBar     = document.getElementById('lc-sel-bar');
    var selAll     = document.getElementById('lc-sel-all');
    var selDelBtn  = document.getElementById('lc-sel-del-btn');
    var selCancel  = document.getElementById('lc-sel-cancel-btn');
    if (selModeBtn) selModeBtn.addEventListener('click', lcEnterSelMode);
    if (selCancel)  selCancel.addEventListener('click', lcExitSelMode);
    if (selDelBtn)  selDelBtn.addEventListener('click', function() {
      if (lcChats.length === 0) return;
      var checked = lcListScroll.querySelectorAll('.lc-list-item-cb:checked').length;
      if (!checked) return;
      if (!confirm('Remove ' + checked + ' chat(s) from your history? The support team can still see these conversations.')) return;
      lcDeleteSelected();
    });
    if (selAll) selAll.addEventListener('change', function() {
      var cbs = lcListScroll.querySelectorAll('.lc-list-item-cb');
      cbs.forEach(function(cb) {
        cb.checked = selAll.checked;
        cb.closest('.lc-list-item').classList.toggle('lc-selected', selAll.checked);
      });
      lcSelUpdateBar();
    });
  })();

  /* ── Open a specific chat from the list ───────────────── */
  function lcOpenChatByIdx(idx) {
    var chat = lcChats[idx];
    if (!chat) return;
    lcId   = chat.id;
    lcSid  = chat.sid;
    lcLast = chat.last_id || 0;

    lcSwitchView('chat');
    lcHdrTitle.textContent  = chat.name || 'Support';
    lcStatusSub.textContent = 'Loading…';

    lcShowChatArea();
    lcMsgs.innerHTML = '';

    lcPost({ action:'poll', chat_id:lcId, session_id:lcSid, last_id:0 }, function(res) {
      if (!res.ok) {
        lcAddMsg('admin', 'Could not load this chat. Please try again.', '', 'text', '');
        return;
      }
      lcSetStatus(res.status);
      if (res.messages && res.messages.length) {
        res.messages.forEach(function(m) {
          lcAddMsg(m.sender, m.message, m.t, m.message_type, m.file_url);
          lcLast = Math.max(lcLast, parseInt(m.id));
        });
        var lastMsg = res.messages[res.messages.length - 1];
        lcUpdateChat(lcId, { last_id: lcLast, status: res.status, preview: lastMsg.message });
      }
      if (res.status !== 'closed') lcStartPoll();
    });
  }

  /* ── Start brand new chat (from New Chat btn) ─────────── */
  function lcBeginNewChat() {
    lcId = null; lcSid = null; lcLast = 0;
    var savedName = '';
    try { savedName = localStorage.getItem(LC_SK.name) || ''; } catch(e){}

    if (savedName) {
      // Name already known — auto-start with numbered label
      var chatNum = lcChats.length + 1;
      var chatLabel = chatNum > 1 ? savedName + ' - ' + chatNum : savedName;
      lcSwitchView('chat');
      lcHdrTitle.textContent  = chatLabel;
      lcStatusSub.textContent = 'Kapraa Support';
      lcShowChatArea();
      lcMsgs.innerHTML = '';
      lcNameStart.disabled = false;
      lcStartFresh(chatLabel);
    } else {
      // First time — ask for name
      lcSwitchView('chat');
      lcHdrTitle.textContent  = 'New Chat';
      lcStatusSub.textContent = 'Kapraa Support';
      lcShowNameScreen();
      lcNameField.value = '';
      setTimeout(function(){ lcNameField.focus(); }, 100);
    }
  }

  /* ── Show / hide within chat view ────────────────────── */
  function lcShowChatArea() {
    lcNameScreen.classList.add('lc-hidden');
    lcMsgs.classList.remove('lc-hidden');
    lcStatusBar.classList.remove('lc-hidden');
    lcInpRow.classList.remove('lc-hidden');
  }
  function lcShowNameScreen() {
    lcNameScreen.classList.remove('lc-hidden');
    lcMsgs.classList.add('lc-hidden');
    lcStatusBar.classList.add('lc-hidden');
    lcInpRow.classList.add('lc-hidden');
  }

  /* ── WhatsApp-style voice player ─────────────────────────── */
  var lcWvAudio = null, lcWvActive = null;
  function lcWvFmt(s) { s=Math.round(s||0); return Math.floor(s/60)+':'+('0'+(s%60)).slice(-2); }
  function lcBuildWv(url, knownDuration) {
    var seed = 0;
    for (var i = 0; i < url.length; i++) seed = (seed + url.charCodeAt(i)) & 0xFFFF;
    var bars = '', n = 26;
    for (var i = 0; i < n; i++) {
      var h = 4 + ((seed * (i + 1) * 7919 + i * 431) % 18);
      bars += '<div class="lc-wv-bar" style="height:' + h + 'px;" data-i="' + i + '"></div>';
    }
    var dur = knownDuration > 0 ? lcWvFmt(knownDuration) : '0:00';
    var playIco = '<svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><polygon points="5,3 19,12 5,21"/></svg>';
    return '<div class="lc-wv" data-src="' + lcEsc(url) + '">'
      + '<button class="lc-wv-btn" onclick="lcWvToggle(this)">' + playIco + '</button>'
      + '<div class="lc-wv-body">'
      + '<div class="lc-wv-bars">' + bars + '</div>'
      + '<div class="lc-wv-foot"><span class="lc-wv-el">0:00</span><span class="lc-wv-tot">' + dur + '</span></div>'
      + '</div></div>';
  }
  window.lcWvToggle = function(btn) {
    var player = btn.closest('.lc-wv');
    var src    = player.dataset.src;
    var playIco  = '<svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><polygon points="5,3 19,12 5,21"/></svg>';
    var pauseIco = '<svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><rect x="6" y="4" width="4" height="16"/><rect x="14" y="4" width="4" height="16"/></svg>';
    var barCount = player.querySelectorAll('.lc-wv-bar').length;
    // Stop previous
    if (lcWvActive && lcWvActive !== player) {
      lcWvAudio.pause();
      lcWvActive.querySelector('.lc-wv-btn').innerHTML = playIco;
      lcWvActive.querySelectorAll('.lc-wv-bar').forEach(function(b){ b.style.opacity='1'; });
      lcWvAudio = null; lcWvActive = null;
    }
    if (!lcWvAudio || lcWvActive !== player) {
      lcWvAudio = new Audio(src);
      lcWvActive = player;
      lcWvAudio.play().catch(function(){});
      btn.innerHTML = pauseIco;
      lcWvAudio.addEventListener('loadedmetadata', function() {
        var tot = player.querySelector('.lc-wv-tot');
        if (tot && isFinite(lcWvAudio.duration)) tot.textContent = lcWvFmt(lcWvAudio.duration);
      });
      lcWvAudio.addEventListener('timeupdate', function() {
        var pct = lcWvAudio.duration ? lcWvAudio.currentTime / lcWvAudio.duration : 0;
        var el  = player.querySelector('.lc-wv-el');
        if (el) el.textContent = lcWvFmt(lcWvAudio.currentTime);
        var active = Math.floor(pct * barCount);
        player.querySelectorAll('.lc-wv-bar').forEach(function(b, i){
          b.style.opacity = i < active ? '1' : '0.35';
        });
      });
      lcWvAudio.addEventListener('ended', function() {
        btn.innerHTML = playIco;
        player.querySelectorAll('.lc-wv-bar').forEach(function(b){ b.style.opacity='1'; });
        var el = player.querySelector('.lc-wv-el');
        if (el) el.textContent = '0:00';
        lcWvAudio = null; lcWvActive = null;
      });
    } else {
      if (lcWvAudio.paused) { lcWvAudio.play(); btn.innerHTML = pauseIco; }
      else { lcWvAudio.pause(); btn.innerHTML = playIco; }
    }
  };

  function lcAddMsg(sender, text, time, msgType, fileUrl, knownDuration) {
    var isAi = sender === 'admin';
    var row  = document.createElement('div');
    row.className = 'lc-row' + (isAi ? '' : ' u');
    var t = time || '';
    var type = msgType || 'text';
    var url  = fileUrl  || '';
    var dur  = parseInt(knownDuration) || 0;

    // Build bubble content
    var bubContent = '';
    if (type === 'image' && url) {
      bubContent = '<img class="lc-bub-img" src="' + lcEsc(url) + '" alt="Image" onclick="window.open(\'' + lcEsc(url) + '\',\'_blank\')">';
    } else if (type === 'voice' && url) {
      bubContent = lcBuildWv(url, dur);
    } else {
      bubContent = lcEsc(text);
    }

    row.innerHTML =
      '<div class="lc-av">' + (isAi ? SVG_HEADSET.replace('22','13').replace('22','13') : IC.user) + '</div>' +
      '<div class="lc-col">' +
        (isAi ? '<div class="lc-sender-name">Laiba — Kapraa</div>' : '') +
        '<div class="lc-bub ' + (isAi ? 'ai' : 'usr') + '">' + bubContent + '</div>' +
        (t ? '<div class="lc-time">' + t + '</div>' : '') +
      '</div>';
    lcMsgs.appendChild(row);
    lcMsgs.scrollTop = lcMsgs.scrollHeight;
  }

  function lcEsc(s) { var d = document.createElement('div'); d.textContent = String(s); return d.innerHTML; }

  function lcSetStatus(status) {
    if (status === 'active') {
      lcStatusBar.textContent = '✓ Agent is online — we\'ll reply shortly';
      lcStatusBar.className   = 'lc-status-bar';
      lcStatusSub.textContent = 'Kapraa • Online';
      lcInp.disabled     = false;
      lcSendBtn.disabled = false;
      lcInpRow.classList.remove('lc-hidden');
      var old = document.getElementById('lc-closed-row');
      if (old) old.remove();
      lcUpdateChat(lcId, { status: 'active' });
    } else if (status === 'closed') {
      lcStatusBar.textContent = 'This chat has been closed.';
      lcStatusBar.className   = 'lc-status-bar lc-closed';
      lcStatusSub.textContent = 'Chat ended';
      lcInp.disabled     = true;
      lcSendBtn.disabled = true;
      lcStopPoll();
      lcUpdateChat(lcId, { status: 'closed' });
      if (!document.getElementById('lc-closed-row')) {
        var row = document.createElement('div');
        row.id = 'lc-closed-row';
        row.style.cssText = 'padding:12px 16px;background:#fff;border-top:1px solid rgba(37,211,102,.12);display:flex;gap:8px;';
        row.innerHTML =
          '<button id="lc-back-to-list-btn" style="flex:1;padding:10px 0;border:1.5px solid #cce8d4;border-radius:24px;background:#fff;color:#128C7E;font-size:13px;font-weight:700;cursor:pointer;">&#8592; All Chats</button>' +
          '<button id="lc-new-from-closed-btn" style="flex:1;padding:10px 0;border:none;border-radius:24px;background:linear-gradient(135deg,#25d366,#128C7E);color:#fff;font-size:13px;font-weight:700;cursor:pointer;box-shadow:0 2px 8px rgba(37,211,102,.35);">+ New Chat</button>';
        lcInpRow.parentNode.insertBefore(row, lcInpRow);
        lcInpRow.classList.add('lc-hidden');
        document.getElementById('lc-back-to-list-btn').addEventListener('click', function() {
          var cr = document.getElementById('lc-closed-row'); if (cr) cr.remove();
          lcInpRow.classList.remove('lc-hidden');
          lcStopPoll();
          lcSwitchView('list');
        });
        document.getElementById('lc-new-from-closed-btn').addEventListener('click', function() {
          var cr = document.getElementById('lc-closed-row'); if (cr) cr.remove();
          lcInpRow.classList.remove('lc-hidden');
          lcStopPoll();
          lcBeginNewChat();
        });
      }
    } else {
      lcStatusBar.textContent = '⏳ Connecting to Laiba…';
      lcStatusBar.className   = 'lc-status-bar lc-waiting';
      lcStatusSub.textContent = 'Connecting…';
    }
  }

  function lcStartPoll() {
    lcStopPoll();
    lcTimer = setInterval(lcPoll, 3000);
  }
  function lcStopPoll() {
    if (lcTimer) { clearInterval(lcTimer); lcTimer = null; }
  }

  function lcPoll() {
    if (!lcId || !lcSid) return;
    lcPost({ action:'poll', chat_id:lcId, session_id:lcSid, last_id:lcLast }, function(res) {
      if (!res.ok) return;
      lcSetStatus(res.status);
      if (res.messages && res.messages.length) {
        var lastText = '', lastPreviewType = 'text';
        res.messages.forEach(function(m) {
          var msgId = parseInt(m.id);
          // Always advance lcLast so future polls don't re-fetch
          lcLast = Math.max(lcLast, msgId);
          // Skip user's own messages — they're already shown as optimistic previews
          // Only display admin/agent replies from the poll
          if (m.sender === 'user') return;
          lcAddMsg(m.sender, m.message, m.t, m.message_type, m.file_url);
          lastPreviewType = m.message_type || 'text';
          lastText = m.message_type === 'image' ? 'Image' : (m.message_type === 'voice' ? 'Voice message' : m.message);
        });
        var timeStr = new Date().toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'});
        lcUpdateChat(lcId, { last_id: lcLast, preview: lastText, previewType: lastPreviewType, time: timeStr, status: res.status });
      }
    });
  }

  function lcSendMessage() {
    if (!lcId || !lcSid) return;

    // If there's a pending media file — send that
    if (lcPendingFile) {
      lcSendMedia(lcPendingFile.blob, lcPendingFile.type);
      return;
    }

    var txt = lcInp.value.trim();
    if (!txt) return;
    lcInp.value = '';

    // Optimistic preview — show immediately before server confirms
    var now = new Date().toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'});
    lcAddMsg('user', txt, now, 'text', '');

    lcPost({ action:'send', chat_id:lcId, session_id:lcSid, message:txt }, function(res) {
      if (!res.ok) {
        lcInp.value = txt; // restore on failure
        lcAddMsg('admin', 'Sorry, your message could not be sent. Please try again.', '', 'text', '');
      }
      // No lcPoll() — the regular 3s timer handles admin replies.
      // Skipping poll here prevents the user's own message from showing twice
      // (lcPoll already skips sender==='user' messages).
    });
  }

  /* ── Send media (image or voice blob) ────────────────────── */
  function lcSendMedia(blob, type, recDuration) {
    if (!lcId || !lcSid || !blob) return;
    var fd = new FormData();
    // Always upload as .webm for voice (Chrome's native output)
    var ext = (type === 'voice') ? 'webm' : (blob.type.split('/')[1] || 'jpg').replace(/;.*/,'');
    fd.append('action', 'send_media');
    fd.append('chat_id', lcId);
    fd.append('session_id', lcSid);
    fd.append('media_type', type);
    fd.append('file', blob, type + '_msg.' + ext);

    // Show optimistic preview with known duration for voice
    var localUrl = URL.createObjectURL(blob);
    var previewLabel = type === 'voice' ? 'Voice message' : 'Image';
    lcAddMsg('user', previewLabel, new Date().toLocaleTimeString([],{hour:'2-digit',minute:'2-digit'}), type, localUrl, recDuration || 0);
    var timeStr = new Date().toLocaleTimeString([],{hour:'2-digit',minute:'2-digit'});
    lcUpdateChat(lcId, { preview: previewLabel, previewType: type, time: timeStr });

    lcClearPendingMedia();

    fetch(LC_URL, { method:'POST', body: fd })
      .then(function(r){ return r.json(); })
      .then(function(res) {
        if (res.ok) {
          // Advance lcLast past the server-side message ID so the next poll
          // does NOT show it again (we already showed it via optimistic preview)
          if (res.message_id) lcLast = Math.max(lcLast, parseInt(res.message_id));
          // Do NOT call lcPoll() here — optimistic preview is already visible
        }
      })
      .catch(function(){});
  }

  /* ── Clear pending media state ───────────────────────────── */
  function lcClearPendingMedia() {
    lcPendingFile = null;
    lcMediaPre.classList.add('lc-hidden');
    lcPreImg.src = '';
    lcPreLbl.textContent = '';
    lcInp.placeholder = 'Type your message…';
  }

  /* ── Image button ─────────────────────────────────────────── */
  lcImgBtn.addEventListener('click', function() {
    lcFileInp.value = '';
    lcFileInp.click();
  });
  lcFileInp.addEventListener('change', function() {
    var file = lcFileInp.files[0];
    if (!file) return;
    if (!file.type.startsWith('image/')) return;
    if (file.size > 5 * 1024 * 1024) { alert('Image must be under 5 MB.'); return; }
    lcPendingFile = { blob: file, type: 'image' };
    // Show preview
    var reader = new FileReader();
    reader.onload = function(e) {
      lcPreImg.src = e.target.result;
      lcPreImg.style.display = '';
      lcPreLbl.textContent = file.name.length > 25 ? file.name.substr(0,22) + '…' : file.name;
      lcMediaPre.classList.remove('lc-hidden');
      lcInp.placeholder = 'Add a caption or just send…';
    };
    reader.readAsDataURL(file);
  });
  lcPreClr.addEventListener('click', lcClearPendingMedia);

  /* ── Voice recording (hold mic button) ───────────────────── */
  function lcFormatTime(s) {
    return Math.floor(s/60) + ':' + ('0' + (s%60)).slice(-2);
  }
  function lcStartRecording() {
    if (!lcId || !lcSid) return;
    if (lcIsRecording) return; // prevent double-start (freeze fix)
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
      alert('Microphone not supported in this browser.'); return;
    }
    lcIsRecording = true;
    navigator.mediaDevices.getUserMedia({ audio: true }).then(function(stream) {
      lcAudioChunks = [];
      lcRecSeconds  = 0;
      // Use video/webm;codecs=opus — Chrome's actual output format (WebM container)
      // 'audio/webm' MIME causes silent failure in some Chrome versions
      var mimePrefs = ['video/webm;codecs=opus','audio/webm;codecs=opus','video/webm','audio/webm',''];
      var chosenMime = '';
      for (var mi = 0; mi < mimePrefs.length; mi++) {
        if (!mimePrefs[mi] || MediaRecorder.isTypeSupported(mimePrefs[mi])) {
          chosenMime = mimePrefs[mi]; break;
        }
      }
      var opts = chosenMime ? { mimeType: chosenMime } : {};
      lcMediaRecorder = new MediaRecorder(stream, opts);
      lcAudioChunks = [];
      lcMediaRecorder.addEventListener('dataavailable', function(e) {
        if (e.data && e.data.size > 0) lcAudioChunks.push(e.data);
      });
      lcMediaRecorder.addEventListener('stop', function() {
        stream.getTracks().forEach(function(t){ t.stop(); });
        if (!lcAudioChunks.length) return;
        // Use video/webm as blob type — most compatible with Chrome's MediaRecorder output
        var blob = new Blob(lcAudioChunks, { type: 'video/webm' });
        var savedDur = lcRecSeconds; // capture duration before reset
        lcIsRecording = false;       // release guard so next recording can start
        lcMediaRecorder = null;
        if (blob.size > 0) lcSendMedia(blob, 'voice', savedDur);
        lcAudioChunks = [];
      });
      lcMediaRecorder.start(250); // 250ms timeslice — captures even short recordings

      // UI
      lcMicBtn.classList.add('recording');
      lcVoiceRec.classList.remove('lc-hidden');
      lcRecTime.textContent = '0:00';
      lcRecTimer = setInterval(function() {
        lcRecSeconds++;
        lcRecTime.textContent = lcFormatTime(lcRecSeconds);
        if (lcRecSeconds >= 120) lcStopRecording(false); // max 2 min
      }, 1000);
    }).catch(function(err) {
      lcIsRecording = false; // release guard so user can try again
      alert('Microphone access denied. Please allow mic access to record voice messages.');
    });
  }
  function lcStopRecording(cancel) {
    clearInterval(lcRecTimer);
    lcMicBtn.classList.remove('recording');
    lcVoiceRec.classList.add('lc-hidden');
    if (lcMediaRecorder && lcMediaRecorder.state !== 'inactive') {
      if (cancel) {
        lcAudioChunks = []; // discard chunks so stop event sends nothing
        lcRecSeconds = 0;
        lcMediaRecorder.stop();
        lcIsRecording = false;
        lcMediaRecorder = null;
      } else {
        // lcRecSeconds saved in stop event handler above; clear UI counter now
        lcMediaRecorder.stop(); // fires dataavailable then stop event → lcIsRecording cleared there
      }
    } else {
      // Guard cleanup if stop called when not recording
      lcIsRecording = false;
      lcRecSeconds  = 0;
      lcMediaRecorder = null;
    }
  }

  // Mouse events (desktop)
  lcMicBtn.addEventListener('mousedown', function(e) { e.preventDefault(); lcStartRecording(); });
  lcMicBtn.addEventListener('mouseup',   function(e) { e.preventDefault(); if(lcMediaRecorder && lcMediaRecorder.state !== 'inactive') lcStopRecording(false); });
  lcMicBtn.addEventListener('mouseleave',function(e) { if(lcMediaRecorder && lcMediaRecorder.state !== 'inactive') lcStopRecording(false); });
  // Touch events (mobile)
  lcMicBtn.addEventListener('touchstart', function(e) { e.preventDefault(); lcStartRecording(); }, {passive:false});
  lcMicBtn.addEventListener('touchend',   function(e) { e.preventDefault(); if(lcMediaRecorder && lcMediaRecorder.state !== 'inactive') lcStopRecording(false); }, {passive:false});
  // Cancel button
  lcRecCancel.addEventListener('click', function() { lcStopRecording(true); });

  function openLiveChat() {
    closeSelector();
    lcLoadChats();
    lcWin.classList.add('th-open');
    lcOpen = true;
    lcInp.disabled = false;
    lcSendBtn.disabled = false;
    // Always open to list view
    lcSwitchView('list');
  }

  function lcStartFresh(visitorName) {
    lcShowChatArea();
    lcMsgs.innerHTML = '';
    lcSetStatus('waiting');
    // Strip " - N" suffix from display name in welcome message
    var displayName = visitorName.replace(/\s*-\s*\d+$/, '');
    lcAddMsg('admin', 'Hi ' + lcEsc(displayName) + '! 👋 I\'m Laiba, your AI fashion assistant at Kapraa. How can I help you today?', new Date().toLocaleTimeString([], {hour:'2-digit',minute:'2-digit'}), 'text', '');
    lcPost({ action:'start', name: visitorName }, function(res) {
      if (!res.ok) {
        lcAddMsg('admin', 'Sorry, we could not connect. Please refresh the page and try again.', '', 'text', '');
        lcNameStart.disabled = false;
        return;
      }
      lcId   = res.chat_id;
      lcSid  = res.session_id;
      lcLast = 0;
      var timeStr = new Date().toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'});
      // Add to chat list
      lcChats.unshift({ id: lcId, sid: lcSid, name: visitorName, preview: 'Chat started', time: timeStr, status: 'waiting', last_id: 0 });
      lcSaveChats();
      lcHdrTitle.textContent  = visitorName;
      lcStatusSub.textContent = 'Connecting…';
      lcStartPoll();
    });
  }

  function lcPost(data, cb) {
    fetch(LC_URL, {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify(data)
    }).then(function(r){ return r.json(); }).then(cb).catch(function(){});
  }

  /* Name screen submit — save name once, then start */
  function lcSubmitName() {
    var name = lcNameField.value.trim();
    if (!name) { lcNameField.focus(); return; }
    lcNameStart.disabled = true;
    // Save base name for all future chats
    try { localStorage.setItem(LC_SK.name, name); } catch(e){}
    lcStartFresh(name);
  }
  lcNameStart.addEventListener('click', lcSubmitName);
  lcNameField.addEventListener('keydown', function(e) {
    if (e.key === 'Enter') { e.preventDefault(); lcSubmitName(); }
  });

  /* Back button → go to list */
  lcBackBtn.addEventListener('click', function() {
    lcStopPoll();
    var cr = document.getElementById('lc-closed-row'); if (cr) cr.remove();
    lcInpRow.classList.remove('lc-hidden');
    lcSwitchView('list');
  });

  /* New Chat button (top of list) */
  lcNewBtn.addEventListener('click', lcBeginNewChat);

  /* Live chat send / close events */
  lcSendBtn.addEventListener('click', lcSendMessage);
  lcInp.addEventListener('keydown', function(e) {
    if (e.key === 'Enter') { e.preventDefault(); lcSendMessage(); }
  });
  lcCloseBtn.addEventListener('click', function() {
    lcWin.classList.remove('th-open');
    lcOpen = false;
    lcStopPoll();
    lcNameStart.disabled = false;
  });

  /* ── OPEN / CLOSE ───────────────────────────────────────── */
  fab.addEventListener("click", function() {
    if (chat.classList.contains("th-open")) {
      // AI chat is open — close it
      chat.classList.remove("th-open");
      saveState();
    } else if (lcWin.classList.contains("th-open")) {
      // Live chat is open — close it
      lcWin.classList.remove("th-open");
      lcOpen = false;
      lcStopPoll();
    } else if (modeOverlay.classList.contains("th-show")) {
      // Selector is open — close it
      closeSelector();
    } else {
      // Nothing open — show the mode selector
      notif.style.display = "none";
      notif.textContent = "";
      try { localStorage.removeItem(SK.notif); } catch(e) {}
      openSelector();
    }
  });

  // Selector button events
  modeAiBtn.addEventListener("click", openAiChat);
  modeLiveBtn.addEventListener("click", openLiveChat);

  // Close selector via X button or clicking outside the card
  modeCloseBtn.addEventListener("click", closeSelector);
  modeOverlay.addEventListener("click", function(e) {
    if (e.target === modeOverlay) closeSelector();
  });

  // Escape key closes selector, AI chat, or live chat
  document.addEventListener("keydown", function(e) {
    if (e.key !== "Escape") return;
    if (modeOverlay.classList.contains("th-show")) closeSelector();
    if (chat.classList.contains("th-open")) { chat.classList.remove("th-open"); saveState(); }
    if (lcWin.classList.contains("th-open")) { lcWin.classList.remove("th-open"); lcOpen = false; lcStopPoll(); }
  });

  closeBtn.addEventListener("click", function() {
    chat.classList.remove("th-open");
    saveState();
  });
  clearBtn.addEventListener("click", function() {
    if (!confirm("Clear chat history?")) return;
    msgs.innerHTML = ""; history = []; count = 0; opened = false;
    try { [SK.msgs,SK.hist,SK.cnt,SK.lvl,SK.open,SK.ts,SK.notif].forEach(function(k){ localStorage.removeItem(k); }); } catch(e) {}
    removeProd(); setChips("default"); welcome(); opened = true; saveState();
  });

  /* ── WELCOME ────────────────────────────────────────────── */
  function welcome() {
    var ws = [
      "Assalamu Alaikum! I'm Laiba, your fashion assistant at Kapraa. Tell me what you're looking for — brand, occasion, or budget!",
      "Hi! I'm Laiba from Kapraa. Share a photo, name a brand, or tell me the occasion — I'll find the perfect outfit for you!",
      "Hey there! I'm Laiba, Kapraa's AI stylist. We have lawn suits, embroidered designs, bridal wear and more. What can I help you find today?",
      "Welcome to Kapraa! I'm Laiba, your personal AI fashion assistant. Tell me what you need and I'll recommend the best options for you!",
    ];
    aiMsg(ws[Math.floor(Math.random() * ws.length)], false);
  }

  /* ── SEND ───────────────────────────────────────────────── */
  sendBtn.addEventListener("click", send);
  inp.addEventListener("keydown", function(e) {
    if (e.key === "Enter") { e.preventDefault(); send(); }
  });

  /* ── Lock / unlock input while waiting for AI ──────────────── */
  function lockInput() {
    isWaiting = true;
    inp.disabled = true;
    sendBtn.disabled = true;
    sendBtn.style.opacity = "0.45";
    inp.placeholder = "Laiba is replying…";
  }
  function unlockInput() {
    isWaiting = false;
    inp.disabled = false;
    sendBtn.disabled = false;
    sendBtn.style.opacity = "";
    inp.placeholder = "Ask Laiba anything…";
    inp.focus();
  }

  function send() {
    // Block sending while AI is still replying
    if (isWaiting) return;

    var text   = inp.value.trim();
    var hasImg = !!b64;
    if (!text && !hasImg) return;
    count++;

    if (hasImg) {
      var row = mkRow("u");
      var col = mkCol();
      col.style.alignItems = "flex-end";
      var bub = document.createElement("div");
      bub.className = "th-bub usr";
      if (text) bub.innerHTML = esc(text).replace(/\n/g,"<br>") + "<br>";
      var im = document.createElement("img");
      im.src = "data:" + mime + ";base64," + b64;
      im.className = "th-msg-img";
      bub.appendChild(im);
      col.appendChild(bub);
      col.appendChild(mkTime());
      row.appendChild(col);
      row.appendChild(mkAv(IC.user));
      msgs.appendChild(row);
    } else {
      userMsg(text);
    }
    scrollDown();
    inp.value = "";
    chipsEl.style.display = "none";
    typingOn();
    lockInput(); // disable input while waiting for reply
    statusEl.textContent = "Typing…";
    history.push({ role: "user", content: text || "[image]" });
    saveState(); // save user message immediately

    var payload = {
      message      : text || "Find products from this image",
      history      : history.slice(-10),
      lang_mode    : detectLang(text),
      user_level   : userLevel,
      msg_count    : count,
      last_products: lastShownProds.slice(0, 5),  // send last carousel for price follow-ups
    };
    if (hasImg) {
      payload.image_base64 = b64;
      payload.image_type   = mime;
      if (cloudinaryUrl) payload.cloudinary_url = cloudinaryUrl;
    }
    clearImg();

    fetch(API_URL, {
      method : "POST",
      headers: { "Content-Type": "application/json" },
      body   : JSON.stringify(payload),
    })
    .then(function(r){ return r.json(); })
    .then(function(data) {
      var reply = String(data.reply || "Let me help you find something!");
      var delay = Math.min(2200, 450 + reply.length * 25);
      setTimeout(function() {
        typingOff();
        statusEl.textContent = "Kapraa \u2022 Online";
        aiMsg(reply, true);
        speakText(reply);
        history.push({ role: "assistant", content: reply });
        chipsEl.style.display = "flex";
        if (Array.isArray(data.products) && data.products.length > 0) {
          showProds(data.products);
          lastShownProds = data.products;   // remember for price/detail follow-ups
          setChips(data.context || "default");
        } else {
          removeProd();
          setChips(data.context || "default");
        }
        saveState();
        notifyUser(); // ping if chat is closed/minimized
        unlockInput(); // re-enable input after reply is shown
      }, delay);
    })
    .catch(function() {
      typingOff();
      statusEl.textContent = "Kapraa \u2022 Online";
      chipsEl.style.display = "flex";
      aiMsg("Sorry, something went wrong! Please try again in a moment.", false);
      saveState();
      unlockInput(); // re-enable even on error
    });
  }

  /* ── HELPERS ────────────────────────────────────────────── */
  function esc(t) {
    return String(t).replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;");
  }
  function now() {
    var d=new Date(),h=d.getHours(),m=d.getMinutes(),a=h>=12?"PM":"AM";
    h=h%12||12;
    return h+":"+(m<10?"0":"")+m+" "+a;
  }
  function mkRow(cls){ var e=document.createElement("div"); e.className="th-row"+(cls?" "+cls:""); return e; }
  function mkCol(){   var e=document.createElement("div"); e.className="th-col"; return e; }
  function mkAv(icon){
    var e=document.createElement("div"); e.className="th-av";
    e.innerHTML=icon; return e;
  }
  function mkTime(){ var e=document.createElement("div"); e.className="th-time"; e.textContent=now(); return e; }

  function userMsg(text) {
    var row=mkRow("u"), col=mkCol();
    col.style.alignItems="flex-end";
    var bub=document.createElement("div");
    bub.className="th-bub usr";
    bub.innerHTML=esc(text).replace(/\n/g,"<br>");
    col.appendChild(bub); col.appendChild(mkTime());
    row.appendChild(col);
    row.appendChild(mkAv(IC.user));
    msgs.appendChild(row); scrollDown();
  }

  function renderMarkdown(text) {
    return esc(text)
      .replace(/\*\*(.*?)\*\*/g,"<strong>$1</strong>")
      .replace(/\*(.*?)\*/g,"<em>$1</em>")
      .replace(/(^|\n)([-•]\s+)([^\n]+)/g,function(_,pre,dash,item){ return (pre?"<br>":"")+"• "+item; })
      .replace(/(^|\n)(\d+\.\s+)([^\n]+)/g,function(_,pre,num,item){ return (pre?"<br>":"")+"<strong>"+num.trim()+"</strong> "+item; })
      .replace(/\n/g,"<br>");
  }

  function aiMsg(text, showFb) {
    var row=mkRow(""), col=mkCol();
    var bub=document.createElement("div");
    bub.className="th-bub ai";
    bub.innerHTML=renderMarkdown(text);
    col.appendChild(bub); col.appendChild(mkTime());
    if (showFb) {
      var fb=document.createElement("div"); fb.className="th-fb";
      var svgThumb='<svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 9V5a3 3 0 0 0-3-3l-4 9v11h11.28a2 2 0 0 0 2-1.7l1.38-9a2 2 0 0 0-2-2.3H14z"/><path d="M7 22H4a2 2 0 0 1-2-2v-7a2 2 0 0 1 2-2h3"/></svg>';
      var svgMore='<svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/><line x1="11" y1="8" x2="11" y2="14"/><line x1="8" y1="11" x2="14" y2="11"/></svg>';
      var svgDiff='<svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="1 4 1 10 7 10"/><polyline points="23 20 23 14 17 14"/><path d="M20.49 9A9 9 0 0 0 5.64 5.64L1 10m22 4l-4.64 4.36A9 9 0 0 1 3.51 15"/></svg>';
      var svgOk='<svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>';
      fb.innerHTML=
        '<button class="th-fb-btn" data-v="good">'+svgThumb+' Helpful</button>'+
        '<button class="th-fb-btn" data-v="more">'+svgMore+' More</button>'+
        '<button class="th-fb-btn" data-v="diff">'+svgDiff+' Different</button>';
      fb.querySelectorAll(".th-fb-btn").forEach(function(b){
        b.addEventListener("click",function(){
          var v=b.getAttribute("data-v");
          if(v==="good"){ b.innerHTML=svgOk+' Thanks!'; b.style.color='#2ecc71'; b.style.borderColor='#2ecc71'; b.disabled=true; }
          else if(v==="more"){ inp.value="Show me more options like this"; send(); }
          else { inp.value="Show me something different"; send(); }
        });
      });
      col.appendChild(fb);
    }
    row.appendChild(mkAv("L"));
    row.appendChild(col);
    msgs.appendChild(row); scrollDown();
  }

  function typingOn() {
    typingOff();
    var row=mkRow(""); row.id="th-typ";
    var d=document.createElement("div"); d.className="th-typing";
    d.innerHTML='<div class="th-dot"></div><div class="th-dot"></div><div class="th-dot"></div>';
    row.appendChild(mkAv("L")); row.appendChild(d);
    msgs.appendChild(row); scrollDown();
  }
  function typingOff(){ var e=document.getElementById("th-typ"); if(e) e.remove(); }
  function scrollDown(){ msgs.scrollTop=msgs.scrollHeight; }

  /* ── PRODUCTS ───────────────────────────────────────────── */
  function removeProd(){ var e=document.getElementById("th-prod-sec"); if(e) e.remove(); }

  function iSrc(raw) {
    if (!raw) return "";
    // Handle comma-separated images — take first only
    var v = String(raw).trim().split(",")[0].trim();
    if (!v) return "";
    if (/^https?:\/\//i.test(v)) return v;
    return IMG_BASE + v.split(/[\/\\]/).pop();
  }

  function showProds(prods) {
    removeProd();
    var sec=document.createElement("div"); sec.className="th-prods"; sec.id="th-prod-sec";
    var top=document.createElement("div"); top.className="th-prods-top";
    var lbl=document.createElement("div"); lbl.className="th-prods-lbl";
    lbl.textContent=prods.length+" item"+(prods.length!==1?"s":"")+" found";
    var tog=document.createElement("button"); tog.className="th-prods-tog"; tog.textContent="Hide";
    var row=document.createElement("div"); row.className="th-prods-row";

    tog.addEventListener("click",function(){
      row.classList.toggle("hide");
      tog.textContent=row.classList.contains("hide")?"Show":"Hide";
    });

    prods.forEach(function(p) {
      var card=document.createElement("a");
      card.className="th-card"; card.href=p.product_url||"#"; card.target="_blank";

      var iw=document.createElement("div"); iw.className="th-cimg-wrap";
      var src=iSrc(p.image_url);
      var phSvg='<svg xmlns="http://www.w3.org/2000/svg" width="38" height="38" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.38 3.46L16 2a4 4 0 0 1-8 0L3.62 3.46a2 2 0 0 0-1.34 2.23l.58 3.57a1 1 0 0 0 .99.84H6v10c0 1.1.9 2 2 2h8a2 2 0 0 0 2-2V10h2.15a1 1 0 0 0 .99-.84l.58-3.57a2 2 0 0 0-1.34-2.23z"/></svg>';
      if (src) {
        var img=document.createElement("img");
        img.className="th-cimg"; img.src=src; img.loading="lazy";
        img.alt=p.name||"Product";
        img.onerror=function(){
          img.style.display="none";
          var ph=document.createElement("div"); ph.className="th-cph";
          ph.innerHTML=phSvg+'<span>NO IMAGE</span>';
          iw.insertBefore(ph,img);
        };
        iw.appendChild(img);
      } else {
        var ph=document.createElement("div"); ph.className="th-cph";
        ph.innerHTML=phSvg+'<span>NO IMAGE</span>';
        iw.appendChild(ph);
      }
      if (p.is_on_sale) {
        var st=document.createElement("span"); st.className="th-sale-tag"; st.textContent="SALE";
        iw.appendChild(st);
      }

      var info=document.createElement("div"); info.className="th-cinfo";
      var nameShort=(p.name||"").substring(0,48);
      info.innerHTML=
        '<div class="th-cbrand">'+esc(p.brand||"Kapraa")+'</div>'+
        '<div class="th-cname">'+esc(nameShort)+'</div>'+
        '<div class="th-cprice">'+
          (p.price && p.price!==p.sale_price ? '<s>Rs '+esc(p.price)+'</s> ' : '')+
          '<b>Rs '+esc(p.sale_price||p.price||"")+'</b>'+
        '</div>'+
        (p.discount||p.ai_reason ? '<div class="th-cbadge">'+esc(p.discount||p.ai_reason)+'</div>' : '');

      card.appendChild(iw); card.appendChild(info); row.appendChild(card);
    });

    top.appendChild(lbl); top.appendChild(tog);
    sec.appendChild(top); sec.appendChild(row);
    var ir=chat.querySelector(".th-inp-row");
    chat.insertBefore(sec,ir);
  }

  /* ── IMAGE UPLOAD ───────────────────────────────────────── */
  imgBtn.addEventListener("click",function(){
    if(!chat.classList.contains("th-open")) chat.classList.add("th-open");
    fileInp.click();
  });
  fileInp.addEventListener("change",function(){
    var file=fileInp.files[0]; if(!file) return;
    if(file.size>5*1024*1024){ alert("Max 5MB please."); return; }
    var reader=new FileReader();
    reader.onload=function(e){
      var parts=e.target.result.split(",");
      b64=parts[1]; mime=file.type; cloudinaryUrl=null;
      preImg.src=e.target.result;
      preTxt.textContent=file.name;
      imgPre.classList.add("show");
      inp.placeholder="Describe what you're looking for (optional)…";
      inp.focus();
      // Upload to Cloudinary in background
      preTxt.textContent="Uploading…";
      var cu=IS_LOCAL?"http://localhost/kapraa/cloudinary_upload.php":"https://kapraa.pk/cloudinary_upload.php";
      fetch(cu,{method:"POST",headers:{"Content-Type":"application/json"},body:JSON.stringify({image_base64:b64,image_type:mime})})
        .then(function(r){return r.json();})
        .then(function(data){
          if(data&&data.success&&data.url){ cloudinaryUrl=data.url; preTxt.textContent="Uploaded — ready to send!"; }
          else { preTxt.textContent="Ready (tap send)"; }
        })
        .catch(function(){ preTxt.textContent="Ready (tap send)"; });
    };
    reader.readAsDataURL(file);
    fileInp.value="";
  });
  preClr.addEventListener("click",clearImg);
  function clearImg(){
    b64=null; mime=null; cloudinaryUrl=null;
    imgPre.classList.remove("show"); preImg.src="";
    inp.placeholder="Ask Laiba anything…";
  }

  /* ── LANGUAGE AUTO-DETECTION ────────────────── */
  function detectLang(text) {
    if (!text) return "auto";
    if (/[؀-ۿ]/.test(text)) return "urdu";
    var urduWords = /\b(mujhe|chahiye|kya|hai|aap|kahan|ji|bhi|kar|ka|ki|ke|ko|aur|nahi|hain|hoon|tha|thi|mein|se|pe|par|yaar|bilkul|haan|shukriya|kapra|kapray|suit|kameez|dupatta|shalwar|batao|dhundh|kitna|daam|wala|wali|wale|chahte|chahti|lagta|lagti)\b/i;
    if (urduWords.test(text)) return "urdu";
    return "english";
  }

  /* ── TEXT-TO-SPEECH (AI speaks replies) ─────────── */
  var ttsSupported = "speechSynthesis" in window;

  function speakText(text) {
    if (!ttsEnabled || !ttsSupported) return;
    window.speechSynthesis.cancel();
    var clean = text
      .replace(/\*\*(.*?)\*\*/g,"$1").replace(/\*(.*?)\*/g,"$1")
      .replace(/https?:\/\/\S+/g,"").replace(/[\u2022\u2726\u2728]/g,"")
      .replace(/\n+/g,". ").trim();
    if (!clean) return;
    var utt = new SpeechSynthesisUtterance(clean);
    var voices = window.speechSynthesis.getVoices();
    var pick = voices.find(function(v){ return /en-IN|en-PK|hi-IN/i.test(v.lang) && /female|woman/i.test(v.name); })
            || voices.find(function(v){ return /en-IN|en-PK|hi-IN/i.test(v.lang); })
            || voices.find(function(v){ return /en/i.test(v.lang); })
            || voices[0];
    if (pick) utt.voice = pick;
    utt.rate = 0.9; utt.pitch = 1.1; utt.lang = "en-IN";
    window.speechSynthesis.speak(utt);
  }

  // TTS toggle
  if (ttsToggleBtn) {
    ttsToggleBtn.addEventListener("click", function(){
      ttsEnabled = !ttsEnabled;
      ttsToggleBtn.innerHTML = ttsEnabled ? IC.volume : IC.mute;
      ttsToggleBtn.style.color = ttsEnabled ? "#fff" : "rgba(255,255,255,.55)";
      ttsToggleBtn.title = ttsEnabled ? "Voice replies ON \u2014 click to mute" : "Voice replies OFF \u2014 click to enable";
      if (!ttsEnabled && ttsSupported) window.speechSynthesis.cancel();
    });
  }
  if (ttsSupported) window.speechSynthesis.getVoices();

  /* ── VOICE INPUT ─────────────────────────────────────────── */
  var recog=null, listening=false, voiceLang="en-PK";
  if("webkitSpeechRecognition" in window||"SpeechRecognition" in window){
    var SR=window.SpeechRecognition||window.webkitSpeechRecognition;
    recog=new SR(); recog.interimResults=true; recog.maxAlternatives=1;
    voiceBtn.title="Voice (hold 0.6s for Urdu / tap for English)";
    var voicePressTimer=null;
    voiceBtn.addEventListener("mousedown",function(){
      voicePressTimer=setTimeout(function(){
        voiceLang="ur-PK";
        voiceSt.textContent="\uD83C\uDDF5\uD83C\uDDF0 Urdu mode — click mic to speak";
        voiceSt.classList.add("show");
      },650);
    });
    voiceBtn.addEventListener("mouseup",function(){ clearTimeout(voicePressTimer); });
    voiceBtn.addEventListener("touchstart",function(){
      voicePressTimer=setTimeout(function(){
        voiceLang="ur-PK";
        voiceSt.textContent="\uD83C\uDDF5\uD83C\uDDF0 Urdu mode — click mic to speak";
        voiceSt.classList.add("show");
      },650);
    },{passive:true});
    voiceBtn.addEventListener("touchend",function(){ clearTimeout(voicePressTimer); },{passive:true});
    voiceBtn.addEventListener("click",function(){
      if(!chat.classList.contains("th-open")) chat.classList.add("th-open");
      if(listening){ recog.stop(); return; }
      recog.lang = voiceLang;
      try { recog.start(); } catch(e){}
    });
    recog.onstart=function(){
      listening=true; voiceBtn.classList.add("active");
      voiceSt.classList.add("show");
      voiceSt.textContent = voiceLang==="ur-PK" ? "\uD83C\uDDF5\uD83C\uDDF0 Bol dein (Urdu)…" : "\uD83C\uDDEC\uD83C\uDDE7 Listening…";
      statusEl.textContent="Listening…";
    };
    recog.onresult=function(e){
      var isFinal=e.results[e.results.length-1].isFinal;
      var t=Array.from(e.results).map(function(r){return r[0].transcript;}).join("");
      inp.value=t; voiceSt.textContent='"'+t+'"';
      if(isFinal){ voiceLang="en-PK"; if(!isWaiting) setTimeout(send,350); }
    };
    recog.onerror=function(ev){
      var msg=ev.error==="no-speech"?"No speech detected — try again":ev.error==="not-allowed"?"Mic permission denied":"Mic error";
      voiceSt.textContent=msg; setTimeout(function(){voiceSt.classList.remove("show");},2500);
    };
    recog.onend=function(){
      listening=false; voiceBtn.classList.remove("active");
      statusEl.textContent="Kapraa \u2022 Online";
      setTimeout(function(){voiceSt.classList.remove("show");},1200);
    };
  } else {
    voiceBtn.style.opacity=".35"; voiceBtn.style.cursor="not-allowed";
    voiceBtn.title="Voice not supported in this browser";
  }

  /* ── PULSE ANIMATION (first visit only) ─────────────────── */
  if (!_restored) {
    setTimeout(function(){
      notif.style.display="flex";
      fab.style.animation="th-pulse 1.8s ease 3";
    }, 3000);
  }

  (function(){
    var s=document.createElement("style");
    s.textContent="@keyframes th-pulse{0%,100%{box-shadow:0 4px 20px rgba(201,67,110,.5)}50%{box-shadow:0 4px 36px rgba(201,67,110,.9),0 0 0 12px rgba(201,67,110,.1)}}";
    document.head.appendChild(s);
  })();

})();
