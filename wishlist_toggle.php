<?php
// ============================================================
// wishlist_toggle.php — Wishlist AJAX endpoint
// Actions: toggle (add/remove), count (get IDs + count)
// ============================================================
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/admin/include/config.php';
header('Content-Type: application/json');

$userId    = $_SESSION['WEBSITE-LOGINSS'] ?? null;
$action    = $_POST['action']     ?? $_GET['action'] ?? 'count';
$productId = (int)($_POST['product_id'] ?? 0);

// ── Auto-create wishlist table if it doesn't exist ───────────
$connection_database->query("CREATE TABLE IF NOT EXISTS wishlist (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT NOT NULL,
    product_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_wl (user_id, product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// ── COUNT / GET IDS ──────────────────────────────────────────
if ($action === 'count') {
    if (!$userId) {
        echo json_encode(['count' => 0, 'ids' => []]);
        exit;
    }
    $uid = intval($userId);
    $r   = $connection_database->query("SELECT product_id FROM wishlist WHERE user_id=$uid");
    $ids = [];
    while ($row = $r->fetch_assoc()) $ids[] = (int)$row['product_id'];
    echo json_encode(['count' => count($ids), 'ids' => $ids]);
    exit;
}

// ── Require login for toggle ─────────────────────────────────
if (!$userId) {
    echo json_encode(['success' => false, 'login' => true, 'message' => 'Please login to use wishlist']);
    exit;
}

// ── TOGGLE ───────────────────────────────────────────────────
if ($action === 'toggle' && $productId > 0) {
    $uid = intval($userId);
    $pid = intval($productId);

    $check = $connection_database->query("SELECT id FROM wishlist WHERE user_id=$uid AND product_id=$pid");
    if ($check->num_rows > 0) {
        $connection_database->query("DELETE FROM wishlist WHERE user_id=$uid AND product_id=$pid");
        $inWishlist = false;
        $msg = 'Removed from wishlist';
    } else {
        $connection_database->query("INSERT IGNORE INTO wishlist (user_id, product_id) VALUES ($uid, $pid)");
        $inWishlist = true;
        $msg = 'Added to wishlist';
    }

    $r     = $connection_database->query("SELECT COUNT(*) AS c FROM wishlist WHERE user_id=$uid");
    $count = (int)$r->fetch_assoc()['c'];

    echo json_encode([
        'success'    => true,
        'inWishlist' => $inWishlist,
        'count'      => $count,
        'message'    => $msg
    ]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid action']);
