<?php
// ============================================================
// admin/include/mark-payment-paid.php
// AJAX endpoint — marks an order's payment_status as 'paid'
// ============================================================
require_once __DIR__ . '/config.php';
session('WEBSITE-LOGIN');

header('Content-Type: application/json');

$orderId = (int)($_POST['order_id'] ?? 0);
if (!$orderId) {
    echo json_encode(['success' => false, 'message' => 'Invalid order ID']);
    exit;
}

$result = $connection_database->query(
    "UPDATE orders SET payment_status='paid' WHERE id=" . $orderId
);

if ($result && $connection_database->affected_rows >= 0) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => $connection_database->error]);
}
