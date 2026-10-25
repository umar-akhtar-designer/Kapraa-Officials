<?php
// ── Shared DB connection for payment files ──────────────────
// Auto-switches between localhost and Hostinger live server
function paymentDB(): mysqli {
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $isLocal = strpos($host,'localhost') !== false
            || strpos($host,'127.0.0.1') !== false
            || strpos($host,'192.168.')   !== false
            || strpos($host,'ngrok')      !== false;

    if ($isLocal) {
        $db = new mysqli('localhost', 'root', '', 'kapraa');
    } else {
        $db = new mysqli('localhost', 'u167258434_kapraa', '', 'u167258434_kapraa');
    }

    if ($db->connect_errno) {
        error_log('[PaymentDB] Connect failed: ' . $db->connect_error);
        return $db;
    }
    $db->set_charset('utf8mb4');
    return $db;
}
