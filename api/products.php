<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

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
$action = $_GET['action'] ?? 'all';

// ── New Arrivals ──────────────────────────────────────────────
if ($action === 'new_arrivals') {
    $limit  = (int)($_GET['limit'] ?? 10);
    $result = mysqli_query($conn,
        "SELECT p.id, p.name, p.slug, p.image, p.price, p.price_after_discount,
                p.dis_amount, p.stock, b.name AS brand_name
         FROM products p
         LEFT JOIN brand b ON p.brand = b.id
         WHERE p.stock != 'Out Stock' AND p.is_active != 0
         ORDER BY p.id DESC
         LIMIT $limit"
    );
    $rows = [];
    while ($r = mysqli_fetch_assoc($result)) {
        $imgs = explode(',', $r['image']);
        $r['image_url']   = $base . '/admin/images/product/' . trim($imgs[0]);
        $r['sale_price']  = $r['price_after_discount'];
        $r['final_price'] = ($r['price_after_discount'] > 0) ? $r['price_after_discount'] : $r['price'];
        $r['discount_percent'] = ($r['price'] > 0 && $r['dis_amount'] > 0)
            ? round(($r['dis_amount'] / $r['price']) * 100)
            : 0;
        $rows[] = $r;
    }
    echo json_encode(['success'=>true, 'data'=>$rows]);
}

// ── All Products ──────────────────────────────────────────────
elseif ($action === 'all') {
    $limit     = (int)($_GET['limit']     ?? 30);
    $offset    = (int)($_GET['offset']    ?? 0);
    $brand     = mysqli_real_escape_string($conn, $_GET['brand']     ?? '');
    $search    = mysqli_real_escape_string($conn, $_GET['search']    ?? '');
    $category  = mysqli_real_escape_string($conn, $_GET['category']  ?? '');
    $min_price = (float)($_GET['min_price'] ?? 0);
    $max_price = (float)($_GET['max_price'] ?? 0);
    $in_stock  = isset($_GET['in_stock'])  ? (int)$_GET['in_stock']  : -1;
    $on_sale   = isset($_GET['on_sale'])   ? (int)$_GET['on_sale']   : -1;
    $sort      = mysqli_real_escape_string($conn, $_GET['sort'] ?? 'newest');

    $where = "WHERE p.is_active != 0";
    if ($brand)     $where .= " AND br.slug = '$brand'";
    if ($search)    $where .= " AND p.name LIKE '%$search%'";
    if ($category)  $where .= " AND cat.slug = '$category'";
    if ($min_price > 0) $where .= " AND p.price >= $min_price";
    if ($max_price > 0) $where .= " AND p.price <= $max_price";
    if ($in_stock === 1) $where .= " AND p.stock != 'Out Stock'";
    if ($on_sale  === 1) $where .= " AND p.price_after_discount > 0";

    $order = match($sort) {
        'price_asc'  => 'p.price ASC',
        'price_desc' => 'p.price DESC',
        'name_asc'   => 'p.name ASC',
        default      => 'p.id DESC',
    };

    $result = mysqli_query($conn,
        "SELECT p.id, p.name, p.slug, p.image, p.price, p.price_after_discount,
                p.dis_amount, p.stock,
                br.name AS brand_name, br.slug AS brand_slug,
                cat.name AS category_name, cat.slug AS category_slug
         FROM products p
         LEFT JOIN brand br ON p.brand = br.id
         LEFT JOIN category cat ON p.category = cat.id
         $where
         ORDER BY $order
         LIMIT $limit OFFSET $offset"
    );
    $rows = [];
    while ($r = mysqli_fetch_assoc($result)) {
        $imgs = explode(',', $r['image']);
        $r['image_url']   = $base . '/admin/images/product/' . trim($imgs[0]);
        $r['sale_price']  = $r['price_after_discount'];
        $r['final_price'] = ($r['price_after_discount'] > 0) ? $r['price_after_discount'] : $r['price'];
        $r['discount_percent'] = ($r['price'] > 0 && $r['dis_amount'] > 0)
            ? round(($r['dis_amount'] / $r['price']) * 100)
            : 0;
        $rows[] = $r;
    }
    echo json_encode(['success'=>true, 'data'=>$rows]);
}

// ── Single Product ────────────────────────────────────────────
elseif ($action === 'single') {
    $slug = mysqli_real_escape_string($conn, $_GET['slug'] ?? '');
    if (!$slug) { echo json_encode(['success'=>false,'message'=>'No slug']); exit; }

    $result = mysqli_query($conn,
        "SELECT p.*, b.name AS brand_name, b.slug AS brand_slug
         FROM products p
         LEFT JOIN brand b ON p.brand = b.id
         WHERE p.slug = '$slug' LIMIT 1"
    );
    $r = mysqli_fetch_assoc($result);
    if (!$r) { echo json_encode(['success'=>false,'message'=>'Not found']); exit; }

    $imgs = explode(',', $r['image']);
    $r['image_url']  = $base . '/admin/images/product/' . trim($imgs[0]);
    $r['all_images'] = array_map(function($i) use ($base) {
        return $base . '/admin/images/product/' . trim($i);
    }, $imgs);
    $r['sale_price']  = $r['price_after_discount'];
    $r['final_price'] = ($r['price_after_discount'] > 0) ? $r['price_after_discount'] : $r['price'];
    $r['discount_percent'] = ($r['price'] > 0 && $r['dis_amount'] > 0)
        ? round(($r['dis_amount'] / $r['price']) * 100)
        : 0;
    $r['description']  = strip_tags(html_entity_decode($r['description'] ?? ''));
    $r['sizes_list']   = $r['size']  ? array_values(array_filter(array_map('trim', explode(',', $r['size']))))  : [];
    $r['colors_list']  = $r['color'] ? array_values(array_filter(array_map('trim', explode(',', $r['color'])))) : [];

    echo json_encode(['success'=>true, 'data'=>$r]);
}
