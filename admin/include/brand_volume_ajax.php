<?php
// ============================================================
// admin/include/brand_volume_ajax.php
// AJAX handler for brand volume CRUD
// ============================================================
require_once 'config.php';
header('Content-Type: application/json');

// Ensure image_position column exists (safe to run every time — MySQL ignores if already exists via IF NOT EXISTS)
@$connection_database->query("ALTER TABLE brand_volumes ADD COLUMN IF NOT EXISTS image_position VARCHAR(10) NOT NULL DEFAULT 'center'");

$action   = $_POST['action'] ?? $_GET['action'] ?? '';
$brandId  = (int)($_POST['brand_id'] ?? $_GET['brand_id'] ?? 0);

// ── LIST volumes for a brand ────────────────────────────────
if ($action === 'list') {
    $rows = [];
    $r = $connection_database->query("SELECT * FROM brand_volumes WHERE brand_id=$brandId ORDER BY sort_order ASC, volume_number ASC");
    if ($r) while ($row = $r->fetch_assoc()) $rows[] = $row;
    echo json_encode(['success' => true, 'volumes' => $rows]);
    exit;
}

// ── ADD volume ──────────────────────────────────────────────
if ($action === 'add') {
    $volumeNumber = max(1, (int)($_POST['volume_number'] ?? 1));
    $volumeName   = $connection_database->real_escape_string(trim($_POST['volume_name'] ?? ''));
    $sortOrder    = (int)($_POST['sort_order'] ?? $volumeNumber);
    $imageName     = '';
    $imagePosition = in_array($_POST['image_position'] ?? '', ['top','center','bottom']) ? $_POST['image_position'] : 'center';

    if (!empty($_FILES['volume_image']['tmp_name'])) {
        $ext = strtolower(pathinfo($_FILES['volume_image']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg','jpeg','png','webp','gif'])) {
            $imageName = 'vol_' . $brandId . '_' . uniqid() . '.' . $ext;
            $dir = __DIR__ . '/../images/brand_volumes/';
            if (!is_dir($dir)) mkdir($dir, 0777, true);
            move_uploaded_file($_FILES['volume_image']['tmp_name'], $dir . $imageName);
        }
    }

    $connection_database->query(
        "INSERT INTO brand_volumes (brand_id, volume_number, volume_name, volume_image, image_position, sort_order)
         VALUES ($brandId, $volumeNumber, '$volumeName', '$imageName', '$imagePosition', $sortOrder)"
    );
    $newId = $connection_database->insert_id;
    echo json_encode(['success' => true, 'id' => $newId, 'image' => $imageName]);
    exit;
}

// ── DELETE volume ───────────────────────────────────────────
if ($action === 'delete') {
    $volId = (int)($_POST['vol_id'] ?? 0);
    // Delete volume image file
    $r = $connection_database->query("SELECT volume_image FROM brand_volumes WHERE id=$volId AND brand_id=$brandId");
    if ($r && $row = $r->fetch_assoc()) {
        if ($row['volume_image']) {
            $f = __DIR__ . '/../images/brand_volumes/' . $row['volume_image'];
            if (file_exists($f)) unlink($f);
        }
    }
    $connection_database->query("DELETE FROM brand_volumes WHERE id=$volId AND brand_id=$brandId");
    // DELETE all products that belong to this volume
    $connection_database->query("DELETE FROM products WHERE FIND_IN_SET($volId, volume_ids)");
    echo json_encode(['success' => true]);
    exit;
}

// ── UPDATE image position ────────────────────────────────────────
if ($action === 'update_position') {
    $volId    = (int)($_POST['vol_id'] ?? 0);
    $pos      = in_array($_POST['image_position'] ?? '', ['top','center','bottom']) ? $_POST['image_position'] : 'center';
    $connection_database->query("UPDATE brand_volumes SET image_position='$pos' WHERE id=$volId AND brand_id=$brandId");
    echo json_encode(['success' => true]);
    exit;
}

// ── GET volumes for product edit (returns for a brand's volumes) ─
if ($action === 'for_brand') {
    $rows = [];
    $r = $connection_database->query("SELECT id, volume_number, volume_name FROM brand_volumes WHERE brand_id=$brandId ORDER BY sort_order ASC, volume_number ASC");
    if ($r) while ($row = $r->fetch_assoc()) $rows[] = $row;
    echo json_encode(['success' => true, 'volumes' => $rows]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Unknown action']);
