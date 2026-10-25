<?php
require 'include/head.php';
session('WEBSITE-LOGIN');

// ── Auto-create coupons table ─────────────────────────────────
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

$success = '';
$error   = '';

// ── Handle POST actions ───────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // ADD new coupon
    if ($action === 'add') {
        $code             = strtoupper(trim($_POST['code'] ?? ''));
        $discount_type    = in_array($_POST['discount_type'] ?? '', ['percent','fixed']) ? $_POST['discount_type'] : 'percent';
        $discount_value   = floatval($_POST['discount_value'] ?? 0);
        $min_order_amount = floatval($_POST['min_order_amount'] ?? 0);
        $max_discount     = (isset($_POST['max_discount']) && $_POST['max_discount'] !== '') ? floatval($_POST['max_discount']) : null;
        $max_uses_raw     = isset($_POST['max_uses']) && $_POST['max_uses'] !== '' ? intval($_POST['max_uses']) : null;
        $max_uses         = ($max_uses_raw === 0 || $max_uses_raw === null) ? null : $max_uses_raw;
        $expires_at       = (!empty($_POST['expires_at'])) ? $connection_database->real_escape_string($_POST['expires_at']) : null;
        $description      = $connection_database->real_escape_string(trim($_POST['description'] ?? ''));
        $enabled          = isset($_POST['enabled']) ? 1 : 0;

        if (empty($code)) {
            $error = 'Coupon code cannot be empty.';
        } elseif ($discount_value <= 0) {
            $error = 'Discount value must be greater than zero.';
        } else {
            $safeCode = $connection_database->real_escape_string($code);
            $maxDiscField  = ($max_discount !== null)  ? floatval($max_discount)  : 'NULL';
            $maxUsesField  = ($max_uses    !== null)  ? intval($max_uses)         : 'NULL';
            $expiresField  = ($expires_at  !== null)  ? "'$expires_at'"           : 'NULL';

            $sql = "INSERT INTO coupons (code, discount_type, discount_value, min_order_amount, max_discount, max_uses, expires_at, description, enabled)
                    VALUES ('$safeCode', '$discount_type', $discount_value, $min_order_amount, $maxDiscField, $maxUsesField, $expiresField, '$description', $enabled)";

            if ($connection_database->query($sql)) {
                $success = "Coupon <strong>$code</strong> created successfully!";
            } else {
                if ($connection_database->errno === 1062) {
                    $error = "Coupon code <strong>$code</strong> already exists.";
                } else {
                    $error = 'Database error: ' . htmlspecialchars($connection_database->error);
                }
            }
        }
    }

    // TOGGLE enabled/disabled
    if ($action === 'toggle') {
        $id = intval($_POST['id'] ?? 0);
        if ($id > 0) {
            $connection_database->query("UPDATE coupons SET enabled = 1 - enabled WHERE id = $id");
            $success = 'Coupon status updated.';
        }
    }

    // DELETE coupon
    if ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        if ($id > 0) {
            $checkQ = $connection_database->query("SELECT used_count, code FROM coupons WHERE id=$id LIMIT 1");
            if ($checkQ && $row = $checkQ->fetch_assoc()) {
                if ($row['used_count'] > 0) {
                    $error = "Cannot delete coupon <strong>{$row['code']}</strong> — it has been used {$row['used_count']} time(s). Disable it instead.";
                } else {
                    $connection_database->query("DELETE FROM coupons WHERE id=$id");
                    $success = 'Coupon deleted.';
                }
            }
        }
    }
}

// ── Fetch all coupons ─────────────────────────────────────────
$couponsQ  = $connection_database->query("SELECT * FROM coupons ORDER BY created_at DESC");
$coupons   = [];
if ($couponsQ) {
    while ($row = $couponsQ->fetch_assoc()) {
        $coupons[] = $row;
    }
}

$today = date('Y-m-d');
?>

<div class="content-body">
  <div class="container-fluid">

    <div class="row page-titles mx-0 mb-3">
      <div class="col-sm-6 p-md-0">
        <div class="welcome-text">
          <h4><i class="fa fa-ticket me-2"></i>Coupon Codes</h4>
          <p class="mb-0 text-muted">Create and manage discount coupons for your store</p>
        </div>
      </div>
    </div>

    <?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show">
      <i class="fas fa-check-circle me-1"></i> <?= $success ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>
    <?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show">
      <i class="fas fa-times-circle me-1"></i> <?= $error ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <div class="row">

      <!-- ── ADD NEW COUPON FORM ─────────────────────────────── -->
      <div class="col-xl-4">
        <div class="card">
          <div class="card-header">
            <h4 class="card-title"><i class="fas fa-plus-circle me-2"></i>Add New Coupon</h4>
          </div>
          <div class="card-body">
            <form method="POST" id="addCouponForm">
              <input type="hidden" name="action" value="add">

              <div class="mb-3">
                <label class="form-label fw-semibold">Coupon Code <span class="text-danger">*</span></label>
                <input type="text" name="code" class="form-control"
                       placeholder="e.g. SAVE20"
                       style="text-transform:uppercase;font-weight:700;letter-spacing:1px;"
                       oninput="this.value=this.value.toUpperCase()"
                       required>
                <small class="text-muted">Auto-converted to uppercase</small>
              </div>

              <div class="mb-3">
                <label class="form-label fw-semibold">Discount Type <span class="text-danger">*</span></label>
                <select name="discount_type" class="form-select" id="discountTypeSelect" required>
                  <option value="percent">Percentage (%)</option>
                  <option value="fixed">Fixed Amount (RS.)</option>
                </select>
              </div>

              <div class="mb-3">
                <label class="form-label fw-semibold" id="discountValueLabel">Discount Value (%) <span class="text-danger">*</span></label>
                <input type="number" name="discount_value" class="form-control"
                       min="0.01" step="0.01" placeholder="e.g. 20" required>
              </div>

              <div class="mb-3" id="maxDiscountWrap">
                <label class="form-label fw-semibold">Max Discount (RS.) <small class="text-muted fw-normal">optional</small></label>
                <input type="number" name="max_discount" class="form-control"
                       min="0" step="0.01" placeholder="Leave blank = no cap">
                <small class="text-muted">For % coupons: caps the maximum discount amount</small>
              </div>

              <div class="mb-3">
                <label class="form-label fw-semibold">Minimum Order Amount (RS.)</label>
                <input type="number" name="min_order_amount" class="form-control"
                       min="0" step="0.01" value="0" placeholder="0 = no minimum">
              </div>

              <div class="mb-3">
                <label class="form-label fw-semibold">Max Uses <small class="text-muted fw-normal">optional</small></label>
                <input type="number" name="max_uses" class="form-control"
                       min="0" step="1" placeholder="0 or blank = unlimited">
                <small class="text-muted">0 or blank = unlimited uses</small>
              </div>

              <div class="mb-3">
                <label class="form-label fw-semibold">Expiry Date <small class="text-muted fw-normal">optional</small></label>
                <input type="date" name="expires_at" class="form-control">
                <small class="text-muted">Leave blank = never expires</small>
              </div>

              <div class="mb-3">
                <label class="form-label fw-semibold">Description <small class="text-muted fw-normal">optional</small></label>
                <input type="text" name="description" class="form-control"
                       placeholder="e.g. Summer sale 2025" maxlength="200">
              </div>

              <div class="mb-4">
                <div class="form-check form-switch">
                  <input class="form-check-input" type="checkbox" id="enabledCheck" name="enabled" checked
                         style="width:42px;height:22px;cursor:pointer;">
                  <label class="form-check-label fw-semibold" for="enabledCheck">Active (enabled)</label>
                </div>
              </div>

              <button type="submit" class="btn btn-primary w-100">
                <i class="fas fa-save me-1"></i> Create Coupon
              </button>
            </form>
          </div>
        </div>
      </div>

      <!-- ── COUPONS TABLE ──────────────────────────────────── -->
      <div class="col-xl-8">
        <div class="card">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="card-title mb-0"><i class="fas fa-list me-2"></i>All Coupons</h4>
            <span class="badge bg-primary"><?= count($coupons) ?> total</span>
          </div>
          <div class="card-body">
            <?php if (empty($coupons)): ?>
            <div class="text-center py-5 text-muted">
              <i class="fa fa-ticket fa-3x mb-3 d-block" style="opacity:.3;"></i>
              <p>No coupons created yet. Add your first coupon on the left.</p>
            </div>
            <?php else: ?>
            <div class="table-responsive">
              <table class="table table-hover table-bordered align-middle" style="font-size:13px;">
                <thead class="table-dark">
                  <tr>
                    <th>Code</th>
                    <th>Type</th>
                    <th>Discount</th>
                    <th>Min Order</th>
                    <th>Uses</th>
                    <th>Expires</th>
                    <th>Status</th>
                    <th class="text-center">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($coupons as $c):
                    $isExpired = !empty($c['expires_at']) && $c['expires_at'] < $today;
                    $isActive  = $c['enabled'] && !$isExpired;

                    // Discount display
                    if ($c['discount_type'] === 'percent') {
                        $discDisplay = number_format($c['discount_value'], 0) . '%';
                        if ($c['max_discount']) {
                            $discDisplay .= ' <small class="text-muted">(max RS.'.number_format($c['max_discount'],0).')</small>';
                        }
                    } else {
                        $discDisplay = 'RS. ' . number_format($c['discount_value'], 2);
                    }

                    // Uses display
                    if ($c['max_uses'] === null || $c['max_uses'] == 0) {
                        $usesDisplay = $c['used_count'] . ' / <span class="text-muted">∞ Unlimited</span>';
                        $remaining   = '<span class="text-muted">Unlimited</span>';
                    } else {
                        $rem = max(0, $c['max_uses'] - $c['used_count']);
                        $usesDisplay = $c['used_count'] . ' / ' . $c['max_uses'];
                        $remaining   = $rem . ' left';
                    }

                    // Expires display
                    if (empty($c['expires_at'])) {
                        $expDisplay = '<span class="text-muted">Never</span>';
                    } else {
                        $expDisplay = date('d M Y', strtotime($c['expires_at']));
                    }
                  ?>
                  <tr>
                    <td>
                      <strong style="letter-spacing:.5px;font-size:14px;"><?= htmlspecialchars($c['code']) ?></strong>
                      <?php if (!empty($c['description'])): ?>
                      <br><small class="text-muted"><?= htmlspecialchars($c['description']) ?></small>
                      <?php endif; ?>
                    </td>
                    <td>
                      <?php if ($c['discount_type'] === 'percent'): ?>
                        <span class="badge bg-info">%</span>
                      <?php else: ?>
                        <span class="badge bg-secondary">Fixed</span>
                      <?php endif; ?>
                    </td>
                    <td><?= $discDisplay ?></td>
                    <td>
                      <?php if ($c['min_order_amount'] > 0): ?>
                        RS. <?= number_format($c['min_order_amount'], 0) ?>
                      <?php else: ?>
                        <span class="text-muted">None</span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <?= $usesDisplay ?><br>
                      <small class="text-muted"><?= $remaining ?></small>
                    </td>
                    <td><?= $expDisplay ?></td>
                    <td>
                      <?php if ($isExpired): ?>
                        <span class="badge bg-danger">Expired</span>
                      <?php elseif ($isActive): ?>
                        <span class="badge bg-success">Active</span>
                      <?php else: ?>
                        <span class="badge bg-warning text-dark">Inactive</span>
                      <?php endif; ?>
                    </td>
                    <td class="text-center" style="white-space:nowrap;">
                      <!-- Toggle -->
                      <form method="POST" class="d-inline">
                        <input type="hidden" name="action" value="toggle">
                        <input type="hidden" name="id" value="<?= $c['id'] ?>">
                        <button type="submit" class="btn btn-sm <?= $c['enabled'] ? 'btn-warning' : 'btn-success' ?>"
                                title="<?= $c['enabled'] ? 'Disable' : 'Enable' ?>">
                          <i class="fas <?= $c['enabled'] ? 'fa-ban' : 'fa-check' ?>"></i>
                        </button>
                      </form>
                      <!-- Delete -->
                      <form method="POST" class="d-inline"
                            onsubmit="return confirm('Delete coupon <?= htmlspecialchars(addslashes($c['code'])) ?>?<?= $c['used_count'] > 0 ? ' (Used '.$c['used_count'].' times — cannot delete)' : '' ?>')">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= $c['id'] ?>">
                        <button type="submit" class="btn btn-sm btn-danger"
                                title="Delete" <?= $c['used_count'] > 0 ? 'disabled' : '' ?>>
                          <i class="fas fa-trash"></i>
                        </button>
                      </form>
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

    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var typeSelect  = document.getElementById('discountTypeSelect');
    var valueLabel  = document.getElementById('discountValueLabel');
    var maxDiscWrap = document.getElementById('maxDiscountWrap');

    function updateLabels() {
        if (typeSelect.value === 'percent') {
            valueLabel.innerHTML  = 'Discount Value (%) <span class="text-danger">*</span>';
            maxDiscWrap.style.display = '';
        } else {
            valueLabel.innerHTML  = 'Discount Amount (RS.) <span class="text-danger">*</span>';
            maxDiscWrap.style.display = 'none';
        }
    }
    typeSelect.addEventListener('change', updateLabels);
    updateLabels();
});
</script>

<script>
(function(){
  function f(){var p=document.getElementById('preloader');if(p){p.style.display='none';try{p.remove();}catch(e){}}var m=document.getElementById('main-wrapper');if(m)m.classList.add('show');}
  if(document.readyState==='complete'){setTimeout(f,100);}else{window.addEventListener('load',function(){setTimeout(f,950);});}
  setTimeout(f,4000);
})();
</script>
<?php include 'include/footer.php'; ?>
