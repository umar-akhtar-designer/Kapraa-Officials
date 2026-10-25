<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

$host    = $_SERVER['HTTP_HOST'] ?? 'localhost';
$isLocal = (strpos($host,'localhost')!==false||strpos($host,'127.0.0.1')!==false||strpos($host,'192.168.')!==false);
$base    = $isLocal ? 'http://'.$host.'/kapraa' : 'https://'.$host;
if ($isLocal) {
    $conn = mysqli_connect('localhost', 'root', '', 'kapraa');
} else {
    $conn = mysqli_connect('localhost', 'u167258434_kapraa', 'Kapraa@2026', 'u167258434_kapraa');
}
if (!$conn) { echo json_encode(['success'=>false,'error'=>mysqli_connect_error()]); exit; }
mysqli_set_charset($conn, 'utf8');

$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$base = $isLocal ? 'http://'.$host.'/kapraa' : 'https://'.$host;
$action  = $_POST['action'] ?? $_GET['action'] ?? 'list';
$user_id = (int)($_POST['user_id'] ?? $_GET['user_id'] ?? 0);

// Helper: get customer email from user_id
function getCustomerEmail($conn, $user_id) {
    $uid = (int)$user_id;
    if (!$uid) return '';
    $r = mysqli_query($conn, "SELECT email FROM customers WHERE id = $uid LIMIT 1");
    $row = mysqli_fetch_assoc($r);
    return $row['email'] ?? '';
}

// ── LIST ORDERS ───────────────────────────────────────────────
if ($action === 'list') {
    if (!$user_id) { echo json_encode(['success'=>false,'message'=>'No user']); exit; }

    $email = getCustomerEmail($conn, $user_id);
    if (!$email) { echo json_encode(['success'=>true,'data'=>[]]); exit; }

    $email_esc = mysqli_real_escape_string($conn, $email);
    $result = mysqli_query($conn,
        "SELECT id, order_number, total_amount, order_status, created_at,
                address, city, state, country, shipping_cost, subtotal, payment_method
         FROM orders
         WHERE email = '$email_esc'
         ORDER BY id DESC"
    );
    $orders = [];
    while ($r = mysqli_fetch_assoc($result)) {
        // Build a readable shipping address
        $parts = array_filter([$r['address'], $r['city'], $r['state'], $r['country']]);
        $r['shipping_address'] = implode(', ', $parts);
        $r['status']           = $r['order_status'];
        $r['total_price']      = $r['total_amount'];
        $orders[] = $r;
    }
    echo json_encode(['success'=>true, 'data'=>$orders]);
}

// ── SINGLE ORDER ──────────────────────────────────────────────
elseif ($action === 'single') {
    $order_id = (int)($_GET['order_id'] ?? $_POST['order_id'] ?? 0);
    if (!$order_id) { echo json_encode(['success'=>false,'message'=>'No order id']); exit; }

    $oid    = mysqli_real_escape_string($conn, $order_id);
    $result = mysqli_query($conn,
        "SELECT id, order_number, total_amount, subtotal, shipping_cost, tax,
                coupon_discount, order_status, payment_method, created_at,
                customer_name, email, phone, address, city, state, country, postal_code, notes
         FROM orders WHERE id = $oid LIMIT 1"
    );
    $order = mysqli_fetch_assoc($result);
    if (!$order) { echo json_encode(['success'=>false,'message'=>'Order not found']); exit; }

    // Normalise field names for the app
    $parts = array_filter([$order['address'], $order['city'], $order['state'], $order['country'], $order['postal_code']]);
    $order['shipping_address'] = implode(', ', $parts);
    $order['status']           = $order['order_status'];
    $order['total_price']      = $order['total_amount'];
    $order['discount']         = $order['coupon_discount'];

    // Get order items
    $items_result = mysqli_query($conn,
        "SELECT product_name, quantity, price, size AS sizes, colors, product_image AS image
         FROM order_items WHERE order_id = $oid"
    );
    $items = [];
    while ($row = mysqli_fetch_assoc($items_result)) {
        $imgs = explode(',', $row['image'] ?? '');
        $row['image_url'] = $base . '/admin/images/product/' . trim($imgs[0]);
        $items[] = $row;
    }
    $order['items'] = $items;

    echo json_encode(['success'=>true, 'data'=>$order]);
}

else {
    echo json_encode(['success'=>false,'message'=>'Unknown action']);
}
