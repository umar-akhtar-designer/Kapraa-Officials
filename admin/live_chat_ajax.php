<?php
// ============================================================
// admin/live_chat_ajax.php — Admin Live Chat AJAX Endpoint
// Actions: list | messages | reply | send_media | close | delete | poll_new
// ============================================================
require_once __DIR__ . '/include/config.php';

// Must be logged in as admin
if (empty($_SESSION['WEBSITE-LOGIN'])) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => false, 'error' => 'Unauthorized']); exit;
}

$isLocal  = (strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false);
$baseUrl  = $isLocal ? 'http://localhost/kapraa' : 'https://kapraa.pk';
$uploadDir = dirname(__DIR__) . '/uploads/chat/';

$db = $connection_database;

// Detect multipart or JSON
$isMultipart = (strpos($_SERVER['CONTENT_TYPE'] ?? '', 'multipart') !== false);
if ($isMultipart) {
    $in     = $_POST;
    $action = trim($_POST['action'] ?? '');
} else {
    header('Content-Type: application/json; charset=utf-8');
    $in     = json_decode(file_get_contents('php://input'), true) ?? [];
    $action = trim($in['action'] ?? $_GET['action'] ?? '');
}

function lca_out($d) {
    if (!headers_sent()) header('Content-Type: application/json; charset=utf-8');
    echo json_encode($d, JSON_UNESCAPED_UNICODE); exit;
}

function lca_save_upload($file, $type, $uploadDir, $baseUrl) {
    $finfo    = new finfo(FILEINFO_MIME_TYPE);
    $realMime = $finfo->file($file['tmp_name']);
    $clientMime = $file['type'] ?? '';
    $mime = $realMime ?: $clientMime;
    $allowedImg   = ['image/jpeg','image/png','image/gif','image/webp'];
    $allowedVoice = ['audio/webm','audio/ogg','audio/mpeg','audio/mp4','audio/wav',
                     'audio/x-m4a','video/webm','application/ogg'];
    if ($type === 'image') {
        if (!in_array($mime, $allowedImg) && !in_array($clientMime, $allowedImg)) return null;
    } elseif ($type === 'voice') {
        $voiceOk = in_array($mime, $allowedVoice) || in_array($clientMime, $allowedVoice)
                   || str_starts_with($mime, 'audio/') || str_starts_with($clientMime, 'audio/')
                   || str_starts_with($mime, 'video/webm');
        if (!$voiceOk) return null;
    } else { return null; }
    $ext  = ($type === 'voice') ? 'webm' : pathinfo($file['name'], PATHINFO_EXTENSION);
    $ext  = preg_replace('/[^a-z0-9]/', '', strtolower($ext)) ?: ($type === 'voice' ? 'webm' : 'jpg');
    $name = 'adm_' . $type . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
    $dest = $uploadDir . $name;
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
    if (!move_uploaded_file($file['tmp_name'], $dest)) return null;
    return $baseUrl . '/uploads/chat/' . $name;
}

function lca_render_preview($msg) {
    $type = $msg['message_type'] ?? 'text';
    $url  = $msg['file_url'] ?? '';
    if ($type === 'image' && $url) return '<i class="fa fa-image"></i> Image';
    if ($type === 'voice' && $url) return '<i class="fa fa-microphone"></i> Voice message';
    return mb_substr($msg['message'] ?? '', 0, 45);
}

switch ($action) {

    // ── LIST: all chats ──────────────────────────────────────
    case 'list':
        $res = $db->query("
            SELECT lc.id, lc.visitor_name, lc.status, lc.user_deleted,
                   DATE_FORMAT(lc.created_at,'%h:%i %p') AS started,
                   DATE_FORMAT(lc.updated_at,'%h:%i %p') AS last_activity,
                   (SELECT message      FROM live_chat_messages WHERE chat_id=lc.id ORDER BY id DESC LIMIT 1) AS last_msg,
                   (SELECT message_type FROM live_chat_messages WHERE chat_id=lc.id ORDER BY id DESC LIMIT 1) AS last_type,
                   (SELECT file_url     FROM live_chat_messages WHERE chat_id=lc.id ORDER BY id DESC LIMIT 1) AS last_file,
                   (SELECT sender       FROM live_chat_messages WHERE chat_id=lc.id ORDER BY id DESC LIMIT 1) AS last_sender,
                   (SELECT COUNT(*)     FROM live_chat_messages WHERE chat_id=lc.id AND sender='user') AS user_msgs,
                   (SELECT COUNT(*) FROM live_chat_messages
                    WHERE chat_id=lc.id AND sender='user'
                      AND id > COALESCE((SELECT MAX(id) FROM live_chat_messages WHERE chat_id=lc.id AND sender='admin'),0)
                   ) AS unread
            FROM live_chats lc
            ORDER BY lc.updated_at DESC
            LIMIT 100
        ");
        $chats = [];
        if ($res) while ($row = $res->fetch_assoc()) $chats[] = $row;
        lca_out(['ok' => true, 'chats' => $chats]);

    // ── MESSAGES: full conversation ───────────────────────────
    case 'messages':
        $cid = (int)($in['chat_id'] ?? 0);
        if (!$cid) lca_out(['ok' => false]);
        $res = $db->query(
            "SELECT id, sender, message, message_type, COALESCE(file_url,'') AS file_url,
                    DATE_FORMAT(created_at,'%h:%i %p') AS t
             FROM live_chat_messages WHERE chat_id=$cid ORDER BY id ASC"
        );
        $msgs = [];
        if ($res) while ($row = $res->fetch_assoc()) $msgs[] = $row;
        $ci   = $db->query("SELECT visitor_name, status, user_deleted FROM live_chats WHERE id=$cid");
        $info = ($ci && $ci->num_rows) ? $ci->fetch_assoc() : ['visitor_name'=>'Visitor','status'=>'waiting','user_deleted'=>0];
        lca_out(['ok' => true, 'messages' => $msgs, 'info' => $info]);

    // ── REPLY: admin sends a text message ────────────────────
    case 'reply':
        $cid = (int)($in['chat_id'] ?? 0);
        $msg = trim($in['message'] ?? '');
        if (!$cid || $msg === '') lca_out(['ok' => false]);
        // Block reply if user deleted the chat
        $chk = $db->query("SELECT user_deleted FROM live_chats WHERE id=$cid LIMIT 1");
        if ($chk && ($chk->fetch_assoc()['user_deleted'] ?? 0)) lca_out(['ok' => false, 'error' => 'user_deleted']);
        $m = $db->real_escape_string(substr($msg, 0, 2000));
        $db->query("INSERT INTO live_chat_messages (chat_id, sender, message, message_type) VALUES ($cid, 'admin', '$m', 'text')");
        $db->query("UPDATE live_chats SET status='active', updated_at=NOW() WHERE id=$cid");
        lca_out(['ok' => true]);

    // ── SEND_MEDIA: admin sends image or voice ────────────────
    case 'send_media':
        $cid  = (int)($in['chat_id'] ?? 0);
        $type = $in['media_type'] ?? 'image';
        if (!$cid) lca_out(['ok' => false, 'error' => 'Missing chat_id']);

        $file = $_FILES['file'] ?? null;
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) lca_out(['ok' => false, 'error' => 'Upload failed: ' . ($file['error'] ?? 'no file')]);

        $url = lca_save_upload($file, $type, $uploadDir, $baseUrl);
        if (!$url) lca_out(['ok' => false, 'error' => 'Invalid file type']);

        $safeUrl  = $db->real_escape_string($url);
        $safeType = ($type === 'voice') ? 'voice' : 'image';
        $label    = ($type === 'voice') ? '[Voice Message]' : '[Image]';
        $db->query("INSERT INTO live_chat_messages (chat_id, sender, message, message_type, file_url) VALUES ($cid, 'admin', '$label', '$safeType', '$safeUrl')");
        $db->query("UPDATE live_chats SET status='active', updated_at=NOW() WHERE id=$cid");
        lca_out(['ok' => true, 'file_url' => $url]);

    // ── CLOSE: end a chat ─────────────────────────────────────
    case 'close':
        $cid = (int)($in['chat_id'] ?? 0);
        if (!$cid) lca_out(['ok' => false]);
        $db->query("INSERT INTO live_chat_messages (chat_id, sender, message, message_type) VALUES ($cid, 'admin', 'This chat has been closed by our team. Thank you for contacting Kapraa!', 'text')");
        $db->query("UPDATE live_chats SET status='closed' WHERE id=$cid");
        lca_out(['ok' => true]);

    // ── DELETE: permanently remove chat + messages ───────────
    case 'delete':
        $cid = (int)($in['chat_id'] ?? 0);
        if (!$cid) lca_out(['ok' => false]);
        // Also delete uploaded files for this chat
        $fr = $db->query("SELECT file_url FROM live_chat_messages WHERE chat_id=$cid AND file_url IS NOT NULL");
        if ($fr) {
            while ($frow = $fr->fetch_assoc()) {
                $fname = basename($frow['file_url'] ?? '');
                if ($fname) @unlink($uploadDir . $fname);
            }
        }
        $db->query("DELETE FROM live_chat_messages WHERE chat_id=$cid");
        $db->query("DELETE FROM live_chats WHERE id=$cid");
        lca_out(['ok' => true]);

    // ── POLL_NEW: unread count badge ─────────────────────────
    case 'poll_new':
        $lastId = (int)($in['last_id'] ?? 0);
        $r = $db->query("SELECT COUNT(*) c FROM live_chat_messages WHERE sender='user' AND id > $lastId");
        $cnt = $r ? (int)$r->fetch_assoc()['c'] : 0;
        $rw  = $db->query("SELECT COUNT(*) c FROM live_chats WHERE status='waiting'");
        $waiting = $rw ? (int)$rw->fetch_assoc()['c'] : 0;
        $rm  = $db->query("SELECT COALESCE(MAX(id),0) m FROM live_chat_messages");
        $maxId = $rm ? (int)$rm->fetch_assoc()['m'] : 0;
        lca_out(['ok' => true, 'new_msgs' => $cnt, 'waiting' => $waiting, 'max_id' => $maxId]);

    default:
        lca_out(['ok' => false, 'error' => 'Unknown action']);
}
