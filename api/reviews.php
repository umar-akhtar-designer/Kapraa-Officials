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

// Auto-create reviews table if it doesn't exist
mysqli_query($conn,
    "CREATE TABLE IF NOT EXISTS reviews (
        id          INT AUTO_INCREMENT PRIMARY KEY,
        product_id  INT NOT NULL,
        user_id     INT NOT NULL,
        user_name   VARCHAR(100) NOT NULL,
        rating      TINYINT(1)   NOT NULL DEFAULT 5,
        comment     TEXT,
        created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_product (product_id),
        UNIQUE KEY one_per_user (product_id, user_id)
    )"
);

$action     = $_POST['action'] ?? $_GET['action'] ?? 'list';
$product_id = (int)($_POST['product_id'] ?? $_GET['product_id'] ?? 0);
$user_id    = (int)($_POST['user_id']    ?? $_GET['user_id']    ?? 0);

// ── GET REVIEWS for a product ─────────────────────────────────
if ($action === 'list') {
    if (!$product_id) { echo json_encode(['success'=>false,'message'=>'No product']); exit; }

    $result = mysqli_query($conn,
        "SELECT id, user_name, rating, comment, created_at
         FROM reviews WHERE product_id=$product_id ORDER BY id DESC"
    );
    $reviews = [];
    while ($r = mysqli_fetch_assoc($result)) {
        $r['rating'] = (int)$r['rating'];
        $reviews[] = $r;
    }

    // Summary stats
    $stats = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT COUNT(*) as total, AVG(rating) as avg_rating FROM reviews WHERE product_id=$product_id"
    ));
    $avg = round((float)($stats['avg_rating'] ?? 0), 1);

    // Check if current user already reviewed
    $user_reviewed = false;
    if ($user_id) {
        $chk = mysqli_query($conn, "SELECT id FROM reviews WHERE product_id=$product_id AND user_id=$user_id LIMIT 1");
        $user_reviewed = mysqli_num_rows($chk) > 0;
    }

    echo json_encode([
        'success'       => true,
        'reviews'       => $reviews,
        'total'         => (int)$stats['total'],
        'avg_rating'    => $avg,
        'user_reviewed' => $user_reviewed,
    ]);
}

// ── ADD REVIEW ────────────────────────────────────────────────
elseif ($action === 'add') {
    if (!$user_id)    { echo json_encode(['success'=>false,'message'=>'Login required']); exit; }
    if (!$product_id) { echo json_encode(['success'=>false,'message'=>'No product']); exit; }

    $rating    = max(1, min(5, (int)($_POST['rating'] ?? 5)));
    $comment   = mysqli_real_escape_string($conn, trim($_POST['comment'] ?? ''));
    $user_name = mysqli_real_escape_string($conn, trim($_POST['user_name'] ?? 'Customer'));

    if (!$comment) { echo json_encode(['success'=>false,'message'=>'Please write a review']); exit; }

    // One review per user per product (UNIQUE KEY handles it, use INSERT IGNORE or REPLACE)
    $ok = mysqli_query($conn,
        "INSERT INTO reviews (product_id, user_id, user_name, rating, comment)
         VALUES ($product_id, $user_id, '$user_name', $rating, '$comment')
         ON DUPLICATE KEY UPDATE rating='$rating', comment='$comment'"
    );

    if ($ok) {
        echo json_encode(['success'=>true,'message'=>'Review submitted! Thank you.']);
    } else {
        echo json_encode(['success'=>false,'message'=>mysqli_error($conn)]);
    }
}

else {
    echo json_encode(['success'=>false,'message'=>'Unknown action']);
}
