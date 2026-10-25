<?php
// ============================================================
// payment/jazzcash_return.php  v2.0  —  JazzCash callback
// Self-contained: own DB connection, single session_start
// ============================================================

// ── 1. ONE session_start (before any output / includes) ─────
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ── 2. Load config + DB helper first ────────────────────────
require_once __DIR__ . '/config.php';   // defines JAZZCASH_* constants + SITE_URL
require_once __DIR__ . '/db.php';       // defines paymentDB()

// ── 3. Connect to DB ─────────────────────────────────────────
$_db = paymentDB();
if ($_db->connect_errno) {
    error_log('[JazzCash Return] DB connect failed: ' . $_db->connect_error);
    header('Location: ' . SITE_URL . '/checkout.php?payment_error=db_error');
    exit;
}
$_db->set_charset('utf8mb4');

// ── 4. Collect POST (JazzCash redirects user's browser) ─────
$post = !empty($_POST) ? $_POST : $_GET;
error_log('[JazzCash Return] data: ' . json_encode($post));

// ── 5. HMAC-SHA256 verification ──────────────────────────────
function jcVerifyHash(array $params, string $key): bool {
    $received = strtoupper($params['pp_SecureHash'] ?? '');
    if ($received === '') return false;
    $p = $params;
    unset($p['pp_SecureHash']);
    ksort($p);
    $str = $key;
    foreach ($p as $k => $v) {
        if ($v !== '') $str .= '&' . $v;   // skip empty — matches official JazzCash JS
    }
    return hash_equals(strtoupper(hash_hmac('sha256', $str, $key)), $received);
}

$txnRefNo     = trim($post['pp_TxnRefNo']      ?? '');
$responseCode = trim($post['pp_ResponseCode']  ?? '');
$responseMsg  = trim($post['pp_ResponseMessage'] ?? '');
$hashValid    = jcVerifyHash($post, JAZZCASH_HASH_KEY);

error_log("[JazzCash Return] TxnRef=$txnRefNo | Code=$responseCode | Hash=" . ($hashValid ? 'OK' : 'FAIL'));

// ── 6. Resolve order number ───────────────────────────────────
// Strategy A: session map (works when checkout & return on same domain)
$orderNumber = $_SESSION['jc_txnref_map'][$txnRefNo]
            ?? $_SESSION['pending_order_number']
            ?? '';

// Strategy B: DB jc_txn_ref column lookup (most reliable — saved when payment started)
if (empty($orderNumber) && $txnRefNo !== '') {
    $safe = $_db->real_escape_string($txnRefNo);
    $r = $_db->query("SELECT order_number FROM orders WHERE jc_txn_ref='$safe' ORDER BY id DESC LIMIT 1");
    if ($r && $r->num_rows) {
        $orderNumber = $r->fetch_assoc()['order_number'];
    }
}

// Strategy C: DB reverse-lookup on stripped order_number (last resort)
if (empty($orderNumber) && $txnRefNo !== '') {
    $safe = $_db->real_escape_string($txnRefNo);
    $r = $_db->query(
        "SELECT order_number FROM orders
         WHERE REPLACE(REPLACE(REPLACE(order_number,'-',''),'/',''),' ','') = '$safe'
         ORDER BY id DESC LIMIT 1"
    );
    if ($r && $r->num_rows) {
        $orderNumber = $r->fetch_assoc()['order_number'];
    }
}

error_log("[JazzCash Return] Resolved order: '$orderNumber'");

if (empty($orderNumber)) {
    error_log('[JazzCash Return] Could not resolve order number — aborting');
    header('Location: ' . SITE_URL . '/checkout.php?payment_error=jazzcash_invalid');
    exit;
}

// ── 7. Update DB & redirect ───────────────────────────────────
$safeOrder = $_db->real_escape_string($orderNumber);
$safeRef   = $_db->real_escape_string($txnRefNo);

if ($hashValid && $responseCode === '000') {
    // ─── SUCCESS ────────────────────────────────────────────
    $_db->query(
        "UPDATE orders
         SET payment_status='paid', payment_gateway_ref='$safeRef'
         WHERE order_number='$safeOrder'"
    );

    // Clear cart from DB
    $userId = $_SESSION['WEBSITE-LOGINSS'] ?? $_SESSION['user_id'] ?? '';
    if ($userId) {
        $safeUser = $_db->real_escape_string($userId);
        $_db->query("DELETE FROM cart WHERE user_id='$safeUser'");
    }
    // Clear session cart + payment session vars
    $_SESSION['cart'] = [];
    unset(
        $_SESSION['pending_order_id'],
        $_SESSION['pending_order_number'],
        $_SESSION['pending_order_amount'],
        $_SESSION['pending_order_email'],
        $_SESSION['jc_txnref_map']
    );

    header('Location: ' . SITE_URL . '/order-confirmation.php?order=' . urlencode($orderNumber) . '&paid=1');
    exit;

} else {
    // ─── FAILED / DECLINED / CANCELLED ──────────────────────
    $_db->query(
        "UPDATE orders
         SET payment_status='failed', payment_gateway_ref='FAIL_{$responseCode}'
         WHERE order_number='$safeOrder'"
    );

    header('Location: ' . SITE_URL . '/checkout.php?payment_error=jazzcash&code=' . urlencode($responseCode) . '&msg=' . urlencode($responseMsg));
    exit;
}
