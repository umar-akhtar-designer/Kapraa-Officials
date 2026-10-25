<?php
// ============================================================
// review_ajax.php — Product Reviews AJAX Handler
// Supports: submit (with photo), list, admin edit/delete
// ============================================================
ob_start(); // buffer any accidental PHP warnings so JSON stays clean
if (session_status() === PHP_SESSION_NONE) session_start();
require_once 'admin/include/config.php';
ob_clean(); // discard anything printed so far (PHP notices, etc.)
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');
header('ngrok-skip-browser-warning: true');

// ── Auto-create table with all needed columns ─────────────
$connection_database->query("
    CREATE TABLE IF NOT EXISTS product_reviews (
        id              INT AUTO_INCREMENT PRIMARY KEY,
        product_id      INT NOT NULL,
        user_id         INT DEFAULT NULL,
        reviewer_name   VARCHAR(80)  NOT NULL,
        reviewer_photo  VARCHAR(255) DEFAULT NULL,
        rating          TINYINT NOT NULL DEFAULT 5,
        review_text     TEXT NOT NULL,
        status          ENUM('visible','hidden') DEFAULT 'visible',
        created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_product (product_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");
// Add missing columns to existing table (safe on re-run)
@$connection_database->query("ALTER TABLE product_reviews ADD COLUMN IF NOT EXISTS reviewer_photo VARCHAR(255) DEFAULT NULL");
@$connection_database->query("ALTER TABLE product_reviews ADD COLUMN IF NOT EXISTS status ENUM('visible','hidden') DEFAULT 'visible'");
@$connection_database->query("ALTER TABLE product_reviews ADD COLUMN IF NOT EXISTS likes INT DEFAULT 0");
@$connection_database->query("ALTER TABLE product_reviews ADD COLUMN IF NOT EXISTS dislikes INT DEFAULT 0");

$action    = $_POST['action'] ?? $_GET['action'] ?? '';
$productId = (int)($_POST['product_id'] ?? $_GET['product_id'] ?? 0);

$photoDir    = __DIR__ . '/uploads/review_photos/';
$photoDirUrl = '/uploads/review_photos/';

// ── SUBMIT REVIEW ─────────────────────────────────────────
if ($action === 'submit') {
    $userId = $_SESSION['WEBSITE-LOGINSS'] ?? null;
    $name   = trim(strip_tags($_POST['name']   ?? ''));
    $text   = trim(strip_tags($_POST['review'] ?? ''));
    $rating = max(1, min(5, (int)($_POST['rating'] ?? 5)));

    if (!$productId || !$name || !$text) {
        echo json_encode(['success' => false, 'message' => 'Please fill in your name and review.']);
        exit;
    }
    if (mb_strlen($text) < 1) {
        echo json_encode(['success' => false, 'message' => 'Please write something in your review.']);
        exit;
    }

    // Prevent duplicate from same logged-in user
    if ($userId) {
        $chk = $connection_database->query("SELECT id FROM product_reviews WHERE product_id=$productId AND user_id=$userId LIMIT 1");
        if ($chk && $chk->num_rows > 0) {
            echo json_encode(['success' => false, 'message' => 'You have already reviewed this product.']);
            exit;
        }
    }

    // Handle optional photo upload
    $photoName = null;
    if (!empty($_FILES['photo']['tmp_name'])) {
        $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg','jpeg','png','webp','gif'])) {
            $photoName = 'rev_' . uniqid() . '.' . $ext;
            if (!is_dir($photoDir)) mkdir($photoDir, 0777, true);
            move_uploaded_file($_FILES['photo']['tmp_name'], $photoDir . $photoName);
        }
    }

    $nameSafe  = $connection_database->real_escape_string($name);
    $textSafe  = $connection_database->real_escape_string($text);
    $photoSafe = $photoName ? "'" . $connection_database->real_escape_string($photoName) . "'" : 'NULL';
    $userVal   = $userId ? (int)$userId : 'NULL';

    $connection_database->query("
        INSERT INTO product_reviews (product_id, user_id, reviewer_name, reviewer_photo, rating, review_text, status)
        VALUES ($productId, $userVal, '$nameSafe', $photoSafe, $rating, '$textSafe', 'visible')
    ");

    if ($connection_database->affected_rows > 0) {
        echo json_encode(['success' => true, 'message' => 'Review posted! Thank you.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Could not save review. Try again.', 'debug' => $connection_database->error]);
    }
    exit;
}

// ── LIKE / DISLIKE ────────────────────────────────────────
if ($action === 'like' || $action === 'dislike') {
    $id    = (int)($_POST['id'] ?? 0);
    $col   = ($action === 'like') ? 'likes' : 'dislikes';
    if (!$id) { echo json_encode(['success'=>false]); exit; }
    $connection_database->query("UPDATE product_reviews SET $col = $col + 1 WHERE id=$id AND status='visible'");
    $r   = $connection_database->query("SELECT likes, dislikes FROM product_reviews WHERE id=$id");
    $row = ($r && $r->num_rows) ? $r->fetch_assoc() : ['likes'=>0,'dislikes'=>0];
    echo json_encode(['success'=>true, 'likes'=>(int)$row['likes'], 'dislikes'=>(int)$row['dislikes']]);
    exit;
}

// ── LIST REVIEWS (frontend — visible only) ────────────────
if ($action === 'list' && $productId) {
    $res = $connection_database->query("
        SELECT id, reviewer_name, reviewer_photo, rating, review_text, created_at,
               COALESCE(likes,0) likes, COALESCE(dislikes,0) dislikes
        FROM product_reviews
        WHERE product_id = $productId AND status = 'visible'
        ORDER BY created_at DESC LIMIT 50
    ");
    $reviews = [];
    if ($res) while ($r = $res->fetch_assoc()) $reviews[] = $r;

    $avg = 0; $count = 0;
    $tot = $connection_database->query("SELECT AVG(rating) avg, COUNT(*) cnt FROM product_reviews WHERE product_id=$productId AND status='visible'");
    if ($tot) { $row = $tot->fetch_assoc(); $avg = round((float)$row['avg'], 1); $count = (int)$row['cnt']; }

    // Per-star breakdown
    $breakdown = [1=>0,2=>0,3=>0,4=>0,5=>0];
    $bq = $connection_database->query("SELECT rating, COUNT(*) c FROM product_reviews WHERE product_id=$productId AND status='visible' GROUP BY rating");
    if ($bq) while ($br = $bq->fetch_assoc()) $breakdown[(int)$br['rating']] = (int)$br['c'];

    echo json_encode(['success' => true, 'reviews' => $reviews, 'average' => $avg, 'count' => $count, 'breakdown' => $breakdown]);
    exit;
}

// ═══════════════════════════════════════════════════════════
// ADMIN ACTIONS — require admin session
// ═══════════════════════════════════════════════════════════
$isAdmin = isset($_SESSION['WEBSITE-LOGIN']); // admin session key

// ── ADMIN LIST ALL ────────────────────────────────────────
if ($action === 'admin_list') {
    if (!$isAdmin) { echo json_encode(['success'=>false,'message'=>'Unauthorized']); exit; }
    $search   = $connection_database->real_escape_string(trim($_GET['search'] ?? ''));
    $statusF  = in_array($_GET['status'] ?? '', ['visible','hidden']) ? $_GET['status'] : '';
    $page     = max(1, (int)($_GET['page'] ?? 1));
    $perPage  = 20;
    $offset   = ($page - 1) * $perPage;

    $where = ['1=1'];
    if ($search) $where[] = "(r.reviewer_name LIKE '%{$search}%' OR r.review_text LIKE '%{$search}%' OR p.name LIKE '%{$search}%')";
    if ($statusF) $where[] = "r.status='$statusF'";
    $wStr = implode(' AND ', $where);

    $res = $connection_database->query("
        SELECT r.*, p.name AS product_name, p.slug AS product_slug
        FROM product_reviews r
        LEFT JOIN products p ON p.id = r.product_id
        WHERE $wStr
        ORDER BY r.created_at DESC
        LIMIT $perPage OFFSET $offset
    ");
    $rows = [];
    if ($res) while ($r = $res->fetch_assoc()) $rows[] = $r;

    $tot = $connection_database->query("SELECT COUNT(*) c FROM product_reviews r LEFT JOIN products p ON p.id=r.product_id WHERE $wStr");
    $total = $tot ? (int)$tot->fetch_assoc()['c'] : 0;

    echo json_encode(['success'=>true,'reviews'=>$rows,'total'=>$total,'page'=>$page,'per_page'=>$perPage]);
    exit;
}

// ── ADMIN EDIT ────────────────────────────────────────────
if ($action === 'admin_edit') {
    if (!$isAdmin) { echo json_encode(['success'=>false,'message'=>'Unauthorized']); exit; }
    $id     = (int)($_POST['id'] ?? 0);
    $name   = $connection_database->real_escape_string(trim($_POST['name']   ?? ''));
    $text   = $connection_database->real_escape_string(trim($_POST['review'] ?? ''));
    $rating = max(1, min(5, (int)($_POST['rating'] ?? 5)));
    $status = in_array($_POST['status'] ?? '', ['visible','hidden']) ? $_POST['status'] : 'visible';

    if (!$id || !$name || !$text) { echo json_encode(['success'=>false,'message'=>'Missing fields']); exit; }

    $connection_database->query("UPDATE product_reviews SET reviewer_name='$name', review_text='$text', rating=$rating, status='$status' WHERE id=$id");
    echo json_encode(['success' => $connection_database->affected_rows >= 0]);
    exit;
}

// ── ADMIN DELETE ──────────────────────────────────────────
if ($action === 'admin_delete') {
    if (!$isAdmin) { echo json_encode(['success'=>false,'message'=>'Unauthorized']); exit; }
    $id = (int)($_POST['id'] ?? 0);
    // Delete photo file if exists
    $r = $connection_database->query("SELECT reviewer_photo FROM product_reviews WHERE id=$id");
    if ($r && $row = $r->fetch_assoc()) {
        if ($row['reviewer_photo'] && file_exists($photoDir . $row['reviewer_photo'])) {
            unlink($photoDir . $row['reviewer_photo']);
        }
    }
    $connection_database->query("DELETE FROM product_reviews WHERE id=$id");
    echo json_encode(['success' => $connection_database->affected_rows > 0]);
    exit;
}

// ── ADMIN TOGGLE STATUS ───────────────────────────────────
if ($action === 'admin_toggle') {
    if (!$isAdmin) { echo json_encode(['success'=>false,'message'=>'Unauthorized']); exit; }
    $id = (int)($_POST['id'] ?? 0);
    $connection_database->query("UPDATE product_reviews SET status = IF(status='visible','hidden','visible') WHERE id=$id");
    $r   = $connection_database->query("SELECT status FROM product_reviews WHERE id=$id");
    $new = ($r && $row = $r->fetch_assoc()) ? $row['status'] : 'visible';
    echo json_encode(['success'=>true, 'new_status'=>$new]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Unknown action']);
