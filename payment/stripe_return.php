<?php
// ============================================================
// payment/stripe_return.php — Stripe success/cancel handler
// NOTE: Must NOT include head.php (it outputs HTML which breaks header redirects)
// ============================================================
session_start();

// Load DB connection and helpers directly (no HTML output)
require_once __DIR__ . '/../admin/include/config.php';
require_once __DIR__ . '/../cart_functions.php';
require_once __DIR__ . '/config.php';

$sessionId   = $_GET['session_id'] ?? '';
$orderNumber = $_GET['order']       ?? '';

function clearCartStripe() {
    global $connection_database;
    $userId = getUserId();
    if ($userId) {
        $connection_database->query("DELETE FROM cart WHERE user_id='" . $connection_database->real_escape_string($userId) . "'");
    }
    $_SESSION['cart'] = [];
}

if (!$sessionId || !$orderNumber) {
    header('Location: ' . SITE_URL . '/checkout.php?payment_error=stripe_invalid');
    exit;
}

// ── Verify session with Stripe API ──────────────────────────
$isLocalEnv = (strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false
            || strpos($_SERVER['HTTP_HOST'] ?? '', '127.0.0.1') !== false
            || strpos($_SERVER['HTTP_HOST'] ?? '', 'ngrok') !== false);

$ch = curl_init('https://api.stripe.com/v1/checkout/sessions/' . urlencode($sessionId));
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_USERPWD        => STRIPE_SECRET_KEY . ':',
    CURLOPT_TIMEOUT        => 30,
    CURLOPT_SSL_VERIFYPEER => !$isLocalEnv,
    CURLOPT_SSL_VERIFYHOST => $isLocalEnv ? 0 : 2,
]);
$response = curl_exec($ch);
$httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    header('Location: ' . SITE_URL . '/checkout.php?payment_error=stripe_verify_failed');
    exit;
}

$session = json_decode($response, true);
$safe    = $connection_database->real_escape_string($orderNumber);

if (($session['payment_status'] ?? '') === 'paid') {
    $payRef = $connection_database->real_escape_string($session['id']);
    $connection_database->query("UPDATE orders SET payment_status='paid', payment_gateway_ref='$payRef' WHERE order_number='$safe'");
    clearCartStripe();
    $_SESSION['order_success'] = true;
    $_SESSION['order_number']  = $orderNumber;
    header('Location: ' . SITE_URL . '/order-confirmation.php?order=' . urlencode($orderNumber) . '&paid=1');
    exit;
} else {
    $connection_database->query("UPDATE orders SET payment_status='failed' WHERE order_number='$safe'");
    header('Location: ' . SITE_URL . '/checkout.php?payment_error=stripe_unpaid');
    exit;
}
