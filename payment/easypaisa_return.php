<?php
// ============================================================
// payment/easypaisa_return.php — EasyPaisa callback
// ============================================================
if (session_status() === PHP_SESSION_NONE) session_start();

// Load config without HTML output (head.php outputs DOCTYPE which breaks redirects)
require_once __DIR__ . '/../admin/include/config.php';  // sets $connection_database + SITE_URL
require_once __DIR__ . '/../cart_functions.php';         // getUserId()
require_once __DIR__ . '/config.php';                    // payment constants

$post        = $_POST;
$orderNumber = $post['orderId']            ?? ($post['orderRefNum'] ?? '');
$status      = strtolower($post['paymentDetails'] ?? ($post['status'] ?? ''));
$responseCode = $post['responseCode']      ?? '';

function clearCartEP() {
    global $connection_database;
    $userId = getUserId();
    if ($userId) {
        $connection_database->query("DELETE FROM cart WHERE user_id='" . $connection_database->real_escape_string($userId) . "'");
    }
    $_SESSION['cart'] = [];
}

if ($orderNumber) {
    $safe = $connection_database->real_escape_string($orderNumber);
    if ($responseCode === '0000' || strpos($status, 'paid') !== false || strpos($status, 'success') !== false) {
        $connection_database->query("UPDATE orders SET payment_status='paid' WHERE order_number='$safe'");
        clearCartEP();
        header('Location: ' . SITE_URL . '/order-confirmation.php?order=' . urlencode($orderNumber) . '&paid=1');
    } else {
        $connection_database->query("UPDATE orders SET payment_status='failed' WHERE order_number='$safe'");
        header('Location: ' . SITE_URL . '/checkout.php?payment_error=easypaisa&code=' . urlencode($responseCode));
    }
} else {
    header('Location: ' . SITE_URL . '/checkout.php?payment_error=invalid');
}
exit;
