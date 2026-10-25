<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

$host    = $_SERVER['HTTP_HOST'] ?? 'localhost';
$isLocal = (strpos($host,'localhost')!==false||strpos($host,'127.0.0.1')!==false||strpos($host,'192.168.')!==false);
$base    = $isLocal ? 'http://'.$host.'/ka' : 'https://'.$host;
if ($isLocal) {
    $conn = mysqli_connect('localhost', 'root', '', 'kapraa');
} else {
    $conn = mysqli_connect('localhost', 'u167258434_kapraa', 'Kapraa@2026', 'u167258434_kapraa');
}
if (!$conn) { echo json_encode(['success'=>false,'error'=>mysqli_connect_error()]); exit; }
mysqli_set_charset($conn, 'utf8');
$action  = $_POST['action'] ?? $_GET['action'] ?? 'list';
$user_id = (int)($_POST['user_id'] ?? $_GET['user_id'] ?? 0);

if (!$user_id) { echo json_encode(['success'=>false,'message'=>'Login required']); exit; }

// ── LIST ──────────────────────────────────────────────────────
if ($action === 'list') {
    $result = mysqli_query($conn,
        "SELECT w.product_id, p.name, p.slug, p.image, p.price, p.price_after_discount,
                p.dis_amount, p.stock, b.name AS brand_name
         FROM wishlist w
         JOIN products p ON w.product_id = p.id
         LEFT JOIN brand b ON p.brand = b.id
         WHERE w.user_id = $user_id
         ORDER BY w.id DESC"
    );
    $items = [];
    while ($r = mysqli_fetch_assoc($result)) {
        $imgs = explode(',', $r['image']);
        $r['image_url']        = $base . '/admin/images/product/' . trim($imgs[0]);
        $r['sale_price']       = $r['price_after_discount'];
        $r['discount_percent'] = ($r['price'] > 0 && $r['dis_amount'] > 0)
            ? round(($r['dis_amount'] / $r['price']) * 100) : 0;
        $items[] = $r;
    }
    echo json_encode(['success'=>true,'data'=>$items]);
}

// ── TOGGLE (add if not exists, remove if exists) ──────────────
elseif ($action === 'toggle') {
    $product_id = (int)($_POST['product_id'] ?? 0);
    if (!$product_id) { echo json_encode(['success'=>false,'message'=>'No product']); exit; }

    $check = mysqli_query($conn,
        "SELECT id FROM wishlist WHERE user_id=$user_id AND product_id=$product_id LIMIT 1"
    );
    if (mysqli_num_rows($check) > 0) {
        $row = mysqli_fetch_assoc($check);
        mysqli_query($conn, "DELETE FROM wishlist WHERE id={$row['id']}");
        echo json_encode(['success'=>true,'wishlisted'=>false,'message'=>'Removed from wishlist']);
    } else {
        mysqli_query($conn, "INSERT INTO wishlist (user_id,product_id) VALUES ($user_id,$product_id)");
        echo json_encode(['success'=>true,'wishlisted'=>true,'message'=>'Added to wishlist']);
    }
}

// ── CHECK single product ──────────────────────────────────────
elseif ($action === 'check') {
    $product_id = (int)($_GET['product_id'] ?? 0);
    $check = mysqli_query($conn,
        "SELECT id FROM wishlist WHERE user_id=$user_id AND product_id=$product_id LIMIT 1"
    );
    echo json_encode(['success'=>true,'wishlisted'=>mysqli_num_rows($check) > 0]);
}

// ── GET IDs only (for bulk heart state on listing screens) ────
elseif ($action === 'ids') {
    $result = mysqli_query($conn, "SELECT product_id FROM wishlist WHERE user_id=$user_id");
    $ids = [];
    while ($r = mysqli_fetch_assoc($result)) $ids[] = (int)$r['product_id'];
    echo json_encode(['success'=>true,'ids'=>$ids]);
}

else {
    echo json_encode(['success'=>false,'message'=>'Unknown action']);
}
