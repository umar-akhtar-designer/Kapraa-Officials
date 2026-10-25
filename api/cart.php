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
$action = $_POST['action'] ?? $_GET['action'] ?? 'get';

// App uses numeric user_id; prefix with 'app_' to avoid colliding with web guest tokens
$raw_uid = (int)($_POST['user_id'] ?? $_GET['user_id'] ?? 0);
$user_id = $raw_uid > 0 ? 'app_' . $raw_uid : '';

// ── GET CART ──────────────────────────────────────────────────
if ($action === 'get') {
    if (!$user_id) {
        echo json_encode(['success'=>true,'cart_items'=>[],'cart_total'=>0,'cart_count'=>0]);
        exit;
    }

    $uid_esc = mysqli_real_escape_string($conn, $user_id);
    $result  = mysqli_query($conn,
        "SELECT c.id, c.product_id, c.quantity, c.sizes, c.colors, c.stitching, c.price_type,
                p.name, p.image, p.price, p.price_after_discount, p.stitched_price
         FROM cart c
         JOIN products p ON c.product_id = p.id
         WHERE c.user_id = '$uid_esc'
         ORDER BY c.id DESC"
    );

    $items = []; $total = 0;
    while ($r = mysqli_fetch_assoc($result)) {
        $imgs = explode(',', $r['image']);
        $r['image_url'] = $base . '/admin/images/product/' . trim($imgs[0]);
        $price = ($r['price_after_discount'] > 0) ? $r['price_after_discount'] : $r['price'];
        $stitch_price = ($r['stitching'] == 1) ? (float)$r['stitched_price'] : 0;
        $r['price']          = $price;
        $r['stitching_price'] = $stitch_price;
        $r['cart_key']       = $r['id']; // use DB row id as cart_key — simple & reliable
        $total += ($price + $stitch_price) * $r['quantity'];
        $items[] = $r;
    }

    $count = array_sum(array_column($items, 'quantity'));
    echo json_encode(['success'=>true,'cart_items'=>$items,'cart_total'=>$total,'cart_count'=>$count]);
}

// ── ADD TO CART ───────────────────────────────────────────────
elseif ($action === 'add') {
    if (!$user_id) {
        echo json_encode(['success'=>false,'message'=>'Please login to add items to cart']);
        exit;
    }

    $product_id = (int)($_POST['product_id'] ?? 0);
    $quantity   = max(1, (int)($_POST['quantity']   ?? 1));
    $sizes      = mysqli_real_escape_string($conn, $_POST['sizes']      ?? '');
    $colors     = mysqli_real_escape_string($conn, $_POST['colors']     ?? '');
    $stitching  = (int)($_POST['stitching']  ?? 0);
    $price_type = mysqli_real_escape_string($conn, $_POST['price_type'] ?? 'regular');
    $uid_esc    = mysqli_real_escape_string($conn, $user_id);

    if (!$product_id) {
        echo json_encode(['success'=>false,'message'=>'Invalid product']);
        exit;
    }

    // Check if product exists
    $pr = mysqli_query($conn, "SELECT id FROM products WHERE id = $product_id LIMIT 1");
    if (!$pr || mysqli_num_rows($pr) === 0) {
        echo json_encode(['success'=>false,'message'=>'Product not found']);
        exit;
    }

    // Check if same item already in cart → update qty
    $check = mysqli_query($conn,
        "SELECT id, quantity FROM cart
         WHERE user_id = '$uid_esc'
           AND product_id = $product_id
           AND sizes      = '$sizes'
           AND colors     = '$colors'
           AND stitching  = $stitching
           AND price_type = '$price_type'
         LIMIT 1"
    );

    if ($check && mysqli_num_rows($check) > 0) {
        $row    = mysqli_fetch_assoc($check);
        $newQty = min(5, $row['quantity'] + $quantity);
        mysqli_query($conn, "UPDATE cart SET quantity = $newQty WHERE id = {$row['id']}");
    } else {
        mysqli_query($conn,
            "INSERT INTO cart (user_id, product_id, quantity, sizes, colors, stitching, price_type)
             VALUES ('$uid_esc', $product_id, $quantity, '$sizes', '$colors', $stitching, '$price_type')"
        );
    }

    // Return updated count
    $cnt_r = mysqli_query($conn, "SELECT SUM(quantity) AS total FROM cart WHERE user_id = '$uid_esc'");
    $cnt   = (int)mysqli_fetch_assoc($cnt_r)['total'];
    echo json_encode(['success'=>true,'message'=>'Added to cart','cart_count'=>$cnt]);
}

// ── REMOVE FROM CART ──────────────────────────────────────────
elseif ($action === 'remove') {
    $cart_id = (int)($_POST['cart_key'] ?? 0);
    $uid_esc = mysqli_real_escape_string($conn, $user_id);
    if ($cart_id) {
        mysqli_query($conn, "DELETE FROM cart WHERE id = $cart_id AND user_id = '$uid_esc'");
    }
    $cnt_r = mysqli_query($conn, "SELECT SUM(quantity) AS total FROM cart WHERE user_id = '$uid_esc'");
    $cnt   = (int)(mysqli_fetch_assoc($cnt_r)['total'] ?? 0);
    echo json_encode(['success'=>true,'cart_count'=>$cnt]);
}

// ── UPDATE QUANTITY ───────────────────────────────────────────
elseif ($action === 'update_qty') {
    $cart_id = (int)($_POST['cart_key'] ?? 0);
    $qty     = max(1, min(5, (int)($_POST['quantity'] ?? 1)));
    $uid_esc = mysqli_real_escape_string($conn, $user_id);
    if ($cart_id) {
        mysqli_query($conn, "UPDATE cart SET quantity = $qty WHERE id = $cart_id AND user_id = '$uid_esc'");
    }
    $cnt_r = mysqli_query($conn, "SELECT SUM(quantity) AS total FROM cart WHERE user_id = '$uid_esc'");
    $cnt   = (int)(mysqli_fetch_assoc($cnt_r)['total'] ?? 0);
    echo json_encode(['success'=>true,'cart_count'=>$cnt]);
}

else {
    echo json_encode(['success'=>false,'message'=>'Unknown action']);
}
