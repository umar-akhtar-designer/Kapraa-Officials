<?php
// ============================================================
// payment/jazzcash_demo_return.php — JazzCash Demo Success
// Called after user completes demo payment flow
// Marks order as paid and redirects to confirmation page
// ============================================================
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

$txnRef = trim($_POST['txnRef'] ?? '');
$order  = trim($_POST['order']  ?? '');
$action = trim($_POST['action'] ?? '');

// Security: must be success action and match session
if ($action !== 'success' || empty($txnRef) || empty($order)) {
    header('Location: ' . SITE_URL . '/checkout.php?payment_error=jazzcash_invalid');
    exit;
}

// Verify matches session
$sessionOrder = $_SESSION['jc_demo_order'] ?? '';
$sessionRef   = $_SESSION['jc_demo_txnref'] ?? '';

if ($order !== $sessionOrder || $txnRef !== $sessionRef) {
    error_log("[JazzCash Demo] Session mismatch — order=$order session=$sessionOrder");
    header('Location: ' . SITE_URL . '/checkout.php?payment_error=jazzcash_invalid');
    exit;
}

// ── Mark order as paid in DB ─────────────────────────────────
$_db = paymentDB();
if ($_db->connect_errno) {
    error_log('[JazzCash Demo] DB connect failed: ' . $_db->connect_error);
    header('Location: ' . SITE_URL . '/checkout.php?payment_error=db_error');
    exit;
}
$_db->set_charset('utf8mb4');

$safeOrder = $_db->real_escape_string($order);
$safeRef   = $_db->real_escape_string('DEMO_' . $txnRef);

$_db->query(
    "UPDATE orders
     SET payment_status='paid', payment_gateway_ref='$safeRef'
     WHERE order_number='$safeOrder'"
);

error_log("[JazzCash Demo] Order $order marked as PAID (demo). TxnRef=$txnRef");

// ── Clear cart from DB ────────────────────────────────────────
$userId = $_SESSION['WEBSITE-LOGINSS'] ?? $_SESSION['user_id'] ?? '';
if ($userId) {
    $safeUser = $_db->real_escape_string($userId);
    $_db->query("DELETE FROM cart WHERE user_id='$safeUser'");
}

$_db->close();

// ── Clear payment session vars ────────────────────────────────
$_SESSION['cart'] = [];
unset(
    $_SESSION['pending_order_id'],
    $_SESSION['pending_order_number'],
    $_SESSION['pending_order_amount'],
    $_SESSION['pending_order_email'],
    $_SESSION['jc_txnref_map'],
    $_SESSION['jc_demo_txnref'],
    $_SESSION['jc_demo_order'],
    $_SESSION['jc_demo_amount'],
    $_SESSION['jc_demo_otp']
);

// ── Redirect to order confirmation ────────────────────────────
header('Location: ' . SITE_URL . '/order-confirmation.php?order=' . urlencode($order) . '&paid=1');
exit;
