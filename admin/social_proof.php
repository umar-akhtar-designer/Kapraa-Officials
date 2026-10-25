<?php
include 'include/head.php';
session('WEBSITE-LOGIN');
global $connection_database;
?>
<script>
(function(){
    function kill(){
        var p=document.getElementById('preloader');
        if(p){p.style.cssText='display:none!important';try{p.remove();}catch(e){}}
        var m=document.getElementById('main-wrapper');
        if(m)m.classList.add('show');
    }
    if(document.readyState==='loading'){document.addEventListener('DOMContentLoaded',function(){setTimeout(kill,100);});}
    else{setTimeout(kill,100);}
    window.addEventListener('load',function(){setTimeout(kill,200);});
    setTimeout(kill,800);
})();
</script>
<?php

// Ensure tables exist
mysqli_query($connection_database, "CREATE TABLE IF NOT EXISTS social_proof_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    custom_city VARCHAR(100) DEFAULT '',
    custom_time_label VARCHAR(60) DEFAULT '',
    is_active TINYINT(1) DEFAULT 1,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");
mysqli_query($connection_database, "CREATE TABLE IF NOT EXISTS social_proof_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(60) UNIQUE,
    setting_value VARCHAR(255)
)");

// Default settings
$settings    = [];
$settingsRes = mysqli_query($connection_database, "SELECT setting_key, setting_value FROM social_proof_settings");
if ($settingsRes) while ($s = mysqli_fetch_assoc($settingsRes)) $settings[$s['setting_key']] = $s['setting_value'];
$delay    = (int)($settings['delay_seconds']    ?? 5);
$duration = (int)($settings['duration_seconds'] ?? 4);
$enabled  = (int)($settings['enabled']          ?? 1);

$success = $error = '';

// Save settings
if (isset($_POST['save_settings'])) {
    $d  = (int)$_POST['delay_seconds'];
    $du = (int)$_POST['duration_seconds'];
    $en = isset($_POST['enabled']) ? 1 : 0;
    mysqli_query($connection_database, "INSERT INTO social_proof_settings (setting_key,setting_value) VALUES ('delay_seconds','$d'),('duration_seconds','$du'),('enabled','$en') ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)");
    $delay = $d; $duration = $du; $enabled = $en;
    $success = 'Settings saved.';
}

// Add notification
if (isset($_POST['add_notification'])) {
    $pid  = (int)$_POST['product_id'];
    $city = mysqli_real_escape_string($connection_database, trim($_POST['custom_city']));
    $time = mysqli_real_escape_string($connection_database, trim($_POST['custom_time_label']));
    $sort = (int)$_POST['sort_order'];
    if ($pid > 0) {
        mysqli_query($connection_database, "INSERT INTO social_proof_notifications (product_id,custom_city,custom_time_label,sort_order) VALUES ('$pid','$city','$time','$sort')");
        $success = 'Notification added.';
    } else {
        $error = 'Please select a product.';
    }
}

// Toggle active
if (isset($_POST['toggle_id'])) {
    $tid = (int)$_POST['toggle_id'];
    mysqli_query($connection_database, "UPDATE social_proof_notifications SET is_active = 1 - is_active WHERE id=$tid");
}

// Delete
if (isset($_POST['delete_id'])) {
    $did = (int)$_POST['delete_id'];
    mysqli_query($connection_database, "DELETE FROM social_proof_notifications WHERE id=$did");
    $success = 'Deleted.';
}

// Fetch all notifications with product info
$rows = [];
$res  = mysqli_query($connection_database,
    "SELECT sp.*, p.name AS product_name, p.image AS product_image, p.price, p.dis_amount,
            b.name AS brand_name
     FROM social_proof_notifications sp
     LEFT JOIN products p ON p.id = sp.product_id
     LEFT JOIN brand b ON b.id = p.brand
     ORDER BY sp.sort_order ASC, sp.id DESC");
if ($res) while ($r = mysqli_fetch_assoc($res)) $rows[] = $r;

// Fetch products for dropdown
$prodRes  = mysqli_query($connection_database, "SELECT id, name, image, price FROM products WHERE stock != 'Out Stock' ORDER BY name ASC LIMIT 300");
$products = [];
if ($prodRes) while ($p = mysqli_fetch_assoc($prodRes)) $products[] = $p;
?>
<div class="content-body">
<div class="container-fluid">

<div class="d-flex align-items-center mb-4 gap-3">
    <div>
        <h4 class="mb-0 font-w700"><i class="fa fa-bell me-2 text-warning"></i>Social Proof Notifications</h4>
        <small class="text-muted">Show "Someone just bought" popups on the storefront</small>
    </div>
    <?php if ($enabled): ?>
        <span class="badge bg-success ms-auto">Live</span>
    <?php else: ?>
        <span class="badge bg-secondary ms-auto">Disabled</span>
    <?php endif; ?>
</div>

<?php if ($success): ?><div class="alert alert-success alert-dismissible fade show py-2"><?= $success ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
<?php if ($error):   ?><div class="alert alert-danger  alert-dismissible fade show py-2"><?= $error   ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>

<div class="row g-4">

<!-- Settings Card -->
<div class="col-lg-4">
    <div class="card border-0 shadow-sm h-100">
        <div class="card-header bg-white border-bottom py-3">
            <h6 class="mb-0 font-w700"><i class="fa fa-sliders me-2 text-primary"></i>Display Settings</h6>
        </div>
        <div class="card-body">
        <form method="POST">
            <div class="mb-3">
                <label class="form-label fw-bold" style="font-size:.83rem;">Delay Between Notifications (seconds)</label>
                <input type="number" name="delay_seconds" class="form-control" value="<?= $delay ?>" min="2" max="60">
                <div class="form-text">How long to wait before showing the next popup.</div>
            </div>
            <div class="mb-3">
                <label class="form-label fw-bold" style="font-size:.83rem;">How Long Each Toast Shows (seconds)</label>
                <input type="number" name="duration_seconds" class="form-control" value="<?= $duration ?>" min="2" max="15">
            </div>
            <div class="mb-3">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="enabled" id="spEnabled" <?= $enabled ? 'checked' : '' ?>>
                    <label class="form-check-label fw-bold" for="spEnabled">Enable notifications on storefront</label>
                </div>
            </div>
            <button type="submit" name="save_settings" class="btn btn-primary w-100"><i class="fa fa-save me-1"></i>Save Settings</button>
        </form>
        </div>
    </div>
</div>

<!-- Add Notification -->
<div class="col-lg-8">
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom py-3">
            <h6 class="mb-0 font-w700"><i class="fa fa-plus-circle me-2 text-success"></i>Add New Notification</h6>
        </div>
        <div class="card-body">
        <form method="POST">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-bold" style="font-size:.83rem;">Product <span class="text-danger">*</span></label>
                    <select name="product_id" class="form-control" required>
                        <option value="">— Select Product —</option>
                        <?php foreach ($products as $p): ?>
                        <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?> (Rs <?= number_format($p['price']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold" style="font-size:.83rem;">City (optional)</label>
                    <input type="text" name="custom_city" class="form-control" placeholder="e.g. Karachi">
                    <div class="form-text">Leave blank to auto-pick.</div>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold" style="font-size:.83rem;">Time Label</label>
                    <input type="text" name="custom_time_label" class="form-control" placeholder="e.g. 2 min ago">
                    <div class="form-text">Leave blank to auto-generate.</div>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold" style="font-size:.83rem;">Sort Order</label>
                    <input type="number" name="sort_order" class="form-control" value="0" min="0">
                </div>
                <div class="col-12">
                    <button type="submit" name="add_notification" class="btn btn-success"><i class="fa fa-plus me-1"></i>Add Notification</button>
                </div>
            </div>
        </form>
        </div>
    </div>
</div>

<!-- Notifications List -->
<div class="col-12">
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
            <h6 class="mb-0 font-w700"><i class="fa fa-list me-2"></i>All Notifications (<?= count($rows) ?>)</h6>
            <small class="text-muted">Shown in order of Sort Order → newest first</small>
        </div>
        <div class="card-body p-0">
        <?php if (empty($rows)): ?>
            <div class="text-center py-5 text-muted"><i class="fa fa-bell-slash" style="font-size:2.5rem;display:block;margin-bottom:10px;"></i>No notifications yet. Add one above.</div>
        <?php else: ?>
        <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="font-size:.85rem;">
            <thead class="table-light">
                <tr>
                    <th>Product</th>
                    <th>City</th>
                    <th>Time Label</th>
                    <th>Order</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $row):
                $img = $row['product_image'] ?: '';
                $imgSrc = $img ? (strpos($img,'http') === 0 ? $img : 'images/product/'.$img) : 'images/placeholder.png';
                $timeLabel = $row['custom_time_label'] ?: 'just now';
                $city      = $row['custom_city']       ?: 'Pakistan';
            ?>
            <tr>
                <td>
                    <div style="display:flex;align-items:center;gap:10px;">
                        <img src="<?= htmlspecialchars($imgSrc) ?>" style="width:44px;height:44px;object-fit:cover;border-radius:8px;border:1px solid #eee;" onerror="this.src='images/placeholder.png'">
                        <div>
                            <div class="fw-bold" style="max-width:200px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= htmlspecialchars($row['product_name'] ?? '—') ?></div>
                            <div class="text-muted" style="font-size:.75rem;"><?= htmlspecialchars($row['brand_name'] ?? '') ?> · Rs <?= number_format((float)($row['price'] ?? 0)) ?></div>
                        </div>
                    </div>
                </td>
                <td><?= htmlspecialchars($city) ?></td>
                <td><span class="badge bg-light text-dark"><?= htmlspecialchars($timeLabel) ?></span></td>
                <td><?= (int)$row['sort_order'] ?></td>
                <td>
                    <?php if ($row['is_active']): ?>
                        <span class="badge bg-success">Active</span>
                    <?php else: ?>
                        <span class="badge bg-secondary">Paused</span>
                    <?php endif; ?>
                </td>
                <td>
                    <div class="d-flex gap-2">
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="toggle_id" value="<?= $row['id'] ?>">
                            <button type="submit" class="btn btn-sm <?= $row['is_active'] ? 'btn-warning' : 'btn-success' ?>" title="<?= $row['is_active'] ? 'Pause' : 'Activate' ?>">
                                <i class="fa fa-<?= $row['is_active'] ? 'pause' : 'play' ?>"></i>
                            </button>
                        </form>
                        <form method="POST" class="d-inline" onsubmit="return confirm('Delete this notification?')">
                            <input type="hidden" name="delete_id" value="<?= $row['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-danger"><i class="fa fa-trash"></i></button>
                        </form>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php endif; ?>
        </div>
    </div>
</div>

</div><!-- row -->
</div>
</div>

<?php include 'include/footer.php'; ?>
