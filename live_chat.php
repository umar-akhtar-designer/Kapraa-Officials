<?php
// ============================================================
// live_chat.php  — Kapraa Live Chat Public API
// Actions: start | send | send_media | poll
// ============================================================
session_start();
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

$isLocal = (strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false
         || strpos($_SERVER['HTTP_HOST'] ?? '', '127.0.0.1') !== false);

$db = $isLocal
    ? new mysqli('localhost', 'root', '', 'kapraa')
    : new mysqli('localhost', 'u167258434_kapraa', 'Kapraa@2026', 'u167258434_kapraa');

if ($db->connect_error) { json_out(['ok' => false, 'error' => 'DB']); }
$db->set_charset('utf8mb4');

$baseUrl = $isLocal ? 'http://localhost/kapraa' : 'https://kapraa.pk';
$uploadDir = __DIR__ . '/uploads/chat/';

// ── Ensure tables exist ─────────────────────────────────────
$db->query("CREATE TABLE IF NOT EXISTS live_chats (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    session_id   VARCHAR(64)  NOT NULL UNIQUE,
    visitor_name VARCHAR(100) NOT NULL DEFAULT 'Visitor',
    status       ENUM('waiting','active','closed') NOT NULL DEFAULT 'waiting',
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$db->query("CREATE TABLE IF NOT EXISTS live_chat_messages (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    chat_id      INT  NOT NULL,
    sender       ENUM('user','admin') NOT NULL,
    message      TEXT NOT NULL,
    message_type ENUM('text','image','voice') NOT NULL DEFAULT 'text',
    file_url     VARCHAR(500) DEFAULT NULL,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_chat (chat_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Add columns if they don't exist (upgrade safety)
@$db->query("ALTER TABLE live_chat_messages ADD COLUMN IF NOT EXISTS message_type ENUM('text','image','voice') NOT NULL DEFAULT 'text'");
@$db->query("ALTER TABLE live_chat_messages ADD COLUMN IF NOT EXISTS file_url VARCHAR(500) DEFAULT NULL");
@$db->query("ALTER TABLE live_chats ADD COLUMN IF NOT EXISTS user_deleted TINYINT(1) NOT NULL DEFAULT 0");

// ── Input ───────────────────────────────────────────────────
// Detect if multipart (file upload) or JSON
$isMultipart = (strpos($_SERVER['CONTENT_TYPE'] ?? '', 'multipart') !== false);
if ($isMultipart) {
    $in     = $_POST;
    $action = trim($_POST['action'] ?? '');
} else {
    header('Content-Type: application/json; charset=utf-8');
    $in     = json_decode(file_get_contents('php://input'), true) ?? [];
    $action = trim($in['action'] ?? $_GET['action'] ?? '');
}

function json_out($d) {
    if (!headers_sent()) header('Content-Type: application/json; charset=utf-8');
    echo json_encode($d, JSON_UNESCAPED_UNICODE); exit;
}

// ── Validate chat ownership ─────────────────────────────────
function lc_verify($db, $cid, $sid) {
    $cid = (int)$cid;
    $sid = $db->real_escape_string($sid);
    $r = $db->query("SELECT status FROM live_chats WHERE id=$cid AND session_id='$sid'");
    return ($r && $r->num_rows > 0) ? $r->fetch_assoc() : null;
}

// ── Save uploaded file ──────────────────────────────────────
function save_upload($file, $type, $uploadDir, $baseUrl) {
    // Use finfo for real MIME detection (don't trust client-provided type)
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $realMime = $finfo->file($file['tmp_name']);
    $clientMime = $file['type'] ?? '';
    // Accept either real or client MIME (browser sometimes sends different for audio/webm)
    $mime = $realMime ?: $clientMime;

    $allowedImg   = ['image/jpeg','image/png','image/gif','image/webp'];
    $allowedVoice = ['audio/webm','audio/ogg','audio/mpeg','audio/mp4','audio/wav',
                     'audio/x-m4a','video/webm','application/ogg'];

    if ($type === 'image') {
        if (!in_array($mime, $allowedImg) && !in_array($clientMime, $allowedImg)) return null;
    } elseif ($type === 'voice') {
        $voiceOk = in_array($mime, $allowedVoice) || in_array($clientMime, $allowedVoice)
                   || str_starts_with($mime, 'audio/') || str_starts_with($clientMime, 'audio/')
                   || str_starts_with($mime, 'video/webm');  // webm audio recorded in Chrome
        if (!$voiceOk) return null;
    } else {
        return null;
    }

    $ext  = ($type === 'voice') ? 'webm' : pathinfo($file['name'], PATHINFO_EXTENSION);
    if (!$ext || $ext === 'webm' && $type === 'image') $ext = 'jpg';
    $ext  = preg_replace('/[^a-z0-9]/', '', strtolower($ext)) ?: ($type === 'voice' ? 'webm' : 'jpg');
    $name = $type . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
    $dest = $uploadDir . $name;

    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
    if (!move_uploaded_file($file['tmp_name'], $dest)) return null;
    return $baseUrl . '/uploads/chat/' . $name;
}

switch ($action) {

    // ── START: create a new chat session ─────────────────────
    case 'start':
        $name = substr(trim($in['name'] ?? 'Visitor'), 0, 100);
        $name = $db->real_escape_string($name ?: 'Visitor');
        $sid  = bin2hex(random_bytes(16));
        $db->query("INSERT INTO live_chats (session_id, visitor_name) VALUES ('$sid', '$name')");
        json_out(['ok' => true, 'chat_id' => $db->insert_id, 'session_id' => $sid]);

    // ── SEND: user sends a text message ─────────────────────
    case 'send':
        $cid = (int)($in['chat_id'] ?? 0);
        $sid = $db->real_escape_string($in['session_id'] ?? '');
        $msg = trim($in['message'] ?? '');
        if (!$cid || !$sid || $msg === '') json_out(['ok' => false, 'error' => 'Missing fields']);

        $chat = lc_verify($db, $cid, $sid);
        if (!$chat) json_out(['ok' => false, 'error' => 'Invalid session']);
        if ($chat['status'] === 'closed') json_out(['ok' => false, 'error' => 'Chat closed']);

        $m = $db->real_escape_string(substr($msg, 0, 2000));
        $db->query("INSERT INTO live_chat_messages (chat_id, sender, message, message_type) VALUES ($cid, 'user', '$m', 'text')");
        $db->query("UPDATE live_chats SET updated_at=NOW() WHERE id=$cid");
        json_out(['ok' => true]);

    // ── SEND_MEDIA: user uploads image or voice ─────────────
    case 'send_media':
        $cid  = (int)($in['chat_id'] ?? 0);
        $sid  = $in['session_id'] ?? '';
        $type = $in['media_type'] ?? 'image'; // 'image' or 'voice'
        if (!$cid || !$sid) json_out(['ok' => false, 'error' => 'Missing fields']);

        $chat = lc_verify($db, $cid, $sid);
        if (!$chat) json_out(['ok' => false, 'error' => 'Invalid session']);
        if ($chat['status'] === 'closed') json_out(['ok' => false, 'error' => 'Chat closed']);

        $file = $_FILES['file'] ?? null;
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) json_out(['ok' => false, 'error' => 'Upload failed']);

        $url = save_upload($file, $type, $uploadDir, $baseUrl);
        if (!$url) json_out(['ok' => false, 'error' => 'Invalid file type']);

        $safeUrl = $db->real_escape_string($url);
        $safeType = ($type === 'voice') ? 'voice' : 'image';
        $label = ($type === 'voice') ? '[Voice Message]' : '[Image]';
        $db->query("INSERT INTO live_chat_messages (chat_id, sender, message, message_type, file_url) VALUES ($cid, 'user', '$label', '$safeType', '$safeUrl')");
        $db->query("UPDATE live_chats SET updated_at=NOW() WHERE id=$cid");
        // Return message_id so client can update lcLast and skip duplicate in next poll
        json_out(['ok' => true, 'file_url' => $url, 'message_id' => $db->insert_id]);

    // ── POLL: get new messages since last_id ─────────────────
    case 'poll':
        $cid    = (int)($in['chat_id'] ?? 0);
        $sid    = $db->real_escape_string($in['session_id'] ?? '');
        $lastId = (int)($in['last_id'] ?? 0);
        if (!$cid || !$sid) json_out(['ok' => false]);

        $chat = lc_verify($db, $cid, $sid);
        if (!$chat) json_out(['ok' => false, 'error' => 'Invalid session']);

        $msgs = [];
        $res  = $db->query(
            "SELECT id, sender, message, message_type, COALESCE(file_url,'') AS file_url,
                    DATE_FORMAT(created_at,'%h:%i %p') AS t
             FROM live_chat_messages
             WHERE chat_id=$cid AND id > $lastId
             ORDER BY id ASC LIMIT 50"
        );
        if ($res) while ($row = $res->fetch_assoc()) $msgs[] = $row;

        json_out(['ok' => true, 'messages' => $msgs, 'status' => $chat['status']]);

    // ── USER_DELETE: user deletes chat from their frontend ───
    // Sets user_deleted=1 so admin knows; admin can still view but NOT reply
    case 'user_delete':
        $cid = (int)($in['chat_id'] ?? 0);
        $sid = $db->real_escape_string($in['session_id'] ?? '');
        if (!$cid || !$sid) json_out(['ok' => false, 'error' => 'Missing fields']);

        $chat = lc_verify($db, $cid, $sid);
        if (!$chat) json_out(['ok' => false, 'error' => 'Invalid session']);

        // Mark as user-deleted and insert a system notice for the admin
        $db->query("UPDATE live_chats SET user_deleted=1, updated_at=NOW() WHERE id=$cid");
        $db->query("INSERT INTO live_chat_messages (chat_id, sender, message, message_type) VALUES ($cid, 'admin', '🗑️ This chat was deleted by the user. No further replies can be sent.', 'text')");
        json_out(['ok' => true]);

    default:
        json_out(['ok' => false, 'error' => 'Unknown action']);
}
