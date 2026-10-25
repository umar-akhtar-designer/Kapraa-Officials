<?php
// Only need DB + session — do NOT include head.php (it outputs full HTML)
require_once __DIR__ . '/admin/include/config.php';
if (session_status() === PHP_SESSION_NONE) session_start();

header('Content-Type: application/json');

// Read JSON body
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) $input = [];

$action     = $input['action'] ?? 'apply';
$code       = strtoupper(trim($input['code'] ?? ''));
$cart_total = floatval($input['cart_total'] ?? 0);

// ── REMOVE action ─────────────────────────────────────────────
if ($action === 'remove') {
    unset($_SESSION['applied_coupon']);
    echo json_encode(['success' => true, 'message' => 'Coupon removed.']);
    exit;
}

// ── APPLY action ──────────────────────────────────────────────

// Ensure coupons table exists
$connection_database->query("CREATE TABLE IF NOT EXISTS coupons (
  id INT AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(50) NOT NULL UNIQUE,
  discount_type ENUM('percent','fixed') NOT NULL DEFAULT 'percent',
  discount_value DECIMAL(10,2) NOT NULL DEFAULT 0,
  min_order_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
  max_discount DECIMAL(10,2) DEFAULT NULL,
  max_uses INT DEFAULT NULL,
  used_count INT NOT NULL DEFAULT 0,
  expires_at DATE DEFAULT NULL,
  enabled TINYINT(1) NOT NULL DEFAULT 1,
  description VARCHAR(200) DEFAULT '',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

if (empty($code)) {
    echo json_encode(['success' => false, 'message' => 'Please enter a coupon code.']);
    exit;
}

$safeCode = $connection_database->real_escape_string($code);
$result   = $connection_database->query("SELECT * FROM coupons WHERE code='$safeCode' LIMIT 1");

if (!$result || $result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid or expired coupon code.']);
    exit;
}

$coupon = $result->fetch_assoc();

// Check enabled
if (!$coupon['enabled']) {
    echo json_encode(['success' => false, 'message' => 'This coupon is no longer active.']);
    exit;
}

// Check expiry
if (!empty($coupon['expires_at']) && $coupon['expires_at'] < date('Y-m-d')) {
    echo json_encode(['success' => false, 'message' => 'This coupon has expired.']);
    exit;
}

// Check max uses
if ($coupon['max_uses'] !== null && $coupon['used_count'] >= $coupon['max_uses']) {
    echo json_encode(['success' => false, 'message' => 'This coupon has reached its usage limit.']);
    exit;
}

// Check minimum order amount
if ($cart_total < floatval($coupon['min_order_amount'])) {
    $minFormatted = number_format($coupon['min_order_amount'], 2);
    echo json_encode(['success' => false, 'message' => "Minimum order of RS. $minFormatted required to use this coupon."]);
    exit;
}

// Calculate discount
$discount = 0;
if ($coupon['discount_type'] === 'percent') {
    $discount = $cart_total * (floatval($coupon['discount_value']) / 100);
    // Cap at max_discount if set
    if ($coupon['max_discount'] !== null && $discount > floatval($coupon['max_discount'])) {
        $discount = floatval($coupon['max_discount']);
    }
} else {
    // fixed
    $discount = floatval($coupon['discount_value']);
    if ($discount > $cart_total) $discount = $cart_total;
}

$discount    = round($discount, 2);
$final_total = round($cart_total - $discount, 2);
if ($final_total < 0) $final_total = 0;

// Store in session
$_SESSION['applied_coupon'] = [
    'code'            => $coupon['code'],
    'discount_amount' => $discount,
    'final_total'     => $final_total,
];

echo json_encode([
    'success'     => true,
    'discount'    => $discount,
    'final_total' => $final_total,
    'message'     => 'Coupon applied! You saved RS. ' . number_format($discount, 2),
]);
exit;
