<?php
require 'include/head.php';
session('WEBSITE-LOGIN');

$msg = '';
$msgType = 'success';

// ── Handle actions ────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $message   = trim($connection_database->real_escape_string($_POST['message'] ?? ''));
        $link      = trim($connection_database->real_escape_string($_POST['link'] ?? ''));
        $sort_order = (int)($_POST['sort_order'] ?? 0);
        if ($message !== '') {
            $connection_database->query(
                "INSERT INTO announcement_bar (message, link, is_active, sort_order)
                 VALUES ('$message', '$link', 1, $sort_order)"
            );
            $msg = 'Announcement added!';
        } else {
            $msg = 'Message cannot be empty.'; $msgType = 'danger';
        }

    } elseif ($action === 'edit') {
        $id        = (int)$_POST['id'];
        $message   = trim($connection_database->real_escape_string($_POST['message'] ?? ''));
        $link      = trim($connection_database->real_escape_string($_POST['link'] ?? ''));
        $sort_order = (int)($_POST['sort_order'] ?? 0);
        $is_active = (int)($_POST['is_active'] ?? 0);
        $connection_database->query(
            "UPDATE announcement_bar SET message='$message', link='$link', sort_order=$sort_order, is_active=$is_active WHERE id=$id"
        );
        $msg = 'Announcement updated!';

    } elseif ($action === 'delete') {
        $id = (int)$_POST['id'];
        $connection_database->query("DELETE FROM announcement_bar WHERE id=$id");
        $msg = 'Announcement deleted.';

    } elseif ($action === 'toggle') {
        $id = (int)$_POST['id'];
        $connection_database->query(
            "UPDATE announcement_bar SET is_active = 1 - is_active WHERE id=$id"
        );
        header('Content-Type: application/json');
        $row = $connection_database->query("SELECT is_active FROM announcement_bar WHERE id=$id")->fetch_assoc();
        echo json_encode(['is_active' => (int)$row['is_active']]);
        exit;
    }
}

// ── Fetch all ─────────────────────────────────────────────
$rows = [];
$res = $connection_database->query("SELECT * FROM announcement_bar ORDER BY sort_order ASC, id ASC");
while ($r = $res->fetch_assoc()) $rows[] = $r;

// Editing?
$editing = null;
if (isset($_GET['edit'])) {
    $eid = (int)$_GET['edit'];
    foreach ($rows as $r) { if ($r['id'] == $eid) { $editing = $r; break; } }
}
?>
<div class="content-body">
<div class="container-fluid">

<div class="d-flex align-items-center justify-content-between mb-4">
    <h4 class="fw-bold mb-0">📢 Announcement Bar</h4>
    <a href="announcement.php" class="btn btn-sm btn-outline-secondary">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-4.96"/></svg>
        Reset
    </a>
</div>

<?php if ($msg): ?>
<div class="alert alert-<?= $msgType ?> alert-dismissible fade show" role="alert">
    <?= htmlspecialchars($msg) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- ── Add / Edit Form ──────────────────────────────────── -->
<div class="card mb-4">
    <div class="card-header fw-bold">
        <?= $editing ? '✏️ Edit Announcement' : '➕ Add New Announcement' ?>
    </div>
    <div class="card-body">
        <form method="post" action="announcement.php">
            <input type="hidden" name="action" value="<?= $editing ? 'edit' : 'add' ?>">
            <?php if ($editing): ?><input type="hidden" name="id" value="<?= $editing['id'] ?>"><?php endif; ?>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-bold">Message <span class="text-danger">*</span></label>
                    <input type="text" name="message" class="form-control"
                           placeholder="e.g. 🚚 Delivery Charges: Rs. 250"
                           value="<?= htmlspecialchars($editing['message'] ?? '') ?>" required>
                    <div class="form-text">You can use emojis: 🚚 ✨ 🎉 📦 🛍️ 🔥</div>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Link (optional)</label>
                    <input type="text" name="link" class="form-control"
                           placeholder="e.g. /products or https://..."
                           value="<?= htmlspecialchars($editing['link'] ?? '') ?>">
                </div>
                <div class="col-md-1">
                    <label class="form-label fw-bold">Order</label>
                    <input type="number" name="sort_order" class="form-control" min="0"
                           value="<?= $editing['sort_order'] ?? (count($rows) + 1) ?>">
                </div>
                <?php if ($editing): ?>
                <div class="col-md-1">
                    <label class="form-label fw-bold">Active</label>
                    <select name="is_active" class="form-control form-select">
                        <option value="1" <?= $editing['is_active'] ? 'selected' : '' ?>>Yes</option>
                        <option value="0" <?= !$editing['is_active'] ? 'selected' : '' ?>>No</option>
                    </select>
                </div>
                <?php endif; ?>
                <div class="col-md-1 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <?= $editing ? 'Update' : 'Add' ?>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- ── Live Preview ─────────────────────────────────────── -->
<div class="card mb-4">
    <div class="card-header fw-bold">👁️ Live Ticker Preview</div>
    <div class="card-body p-0">
        <div style="background:#1a1a1a;overflow:hidden;height:38px;display:flex;align-items:center;position:relative;">
            <div id="previewTrack" style="display:flex;align-items:center;white-space:nowrap;animation:thTicker linear infinite;animation-duration:20s;">
                <?php foreach ($rows as $r): if (!$r['is_active']) continue; ?>
                <span style="color:#fff;font-size:13px;font-weight:500;padding:0 48px;white-space:nowrap;"><?= htmlspecialchars($r['message']) ?></span>
                <span style="display:inline-block;width:5px;height:5px;border-radius:50%;background:rgba(255,255,255,.4);margin:0 4px;"></span>
                <?php endforeach; ?>
                <!-- duplicate for seamless loop -->
                <?php foreach ($rows as $r): if (!$r['is_active']) continue; ?>
                <span style="color:#fff;font-size:13px;font-weight:500;padding:0 48px;white-space:nowrap;"><?= htmlspecialchars($r['message']) ?></span>
                <span style="display:inline-block;width:5px;height:5px;border-radius:50%;background:rgba(255,255,255,.4);margin:0 4px;"></span>
                <?php endforeach; ?>
            </div>
            <style>@keyframes thTicker{0%{transform:translateX(0)}100%{transform:translateX(-50%)}}</style>
        </div>
    </div>
</div>

<!-- ── Announcements List ───────────────────────────────── -->
<div class="card">
    <div class="card-header fw-bold">All Announcements (<?= count($rows) ?>)</div>
    <div class="card-body p-0">
        <?php if (empty($rows)): ?>
        <p class="text-muted text-center py-4">No announcements yet. Add one above!</p>
        <?php else: ?>
        <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th style="width:50px">Order</th>
                    <th>Message</th>
                    <th>Link</th>
                    <th style="width:90px">Status</th>
                    <th style="width:120px">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $r): ?>
            <tr id="row-<?= $r['id'] ?>" <?= !$r['is_active'] ? 'style="opacity:.5"' : '' ?>>
                <td><span class="badge bg-secondary"><?= (int)$r['sort_order'] ?></span></td>
                <td>
                    <span style="font-size:14px;"><?= htmlspecialchars($r['message']) ?></span>
                </td>
                <td>
                    <?php if ($r['link']): ?>
                    <a href="<?= htmlspecialchars($r['link']) ?>" class="text-primary small" target="_blank"><?= htmlspecialchars($r['link']) ?></a>
                    <?php else: ?>
                    <span class="text-muted small">—</span>
                    <?php endif; ?>
                </td>
                <td>
                    <div class="form-check form-switch mb-0">
                        <input class="form-check-input toggle-ann" type="checkbox"
                               data-id="<?= $r['id'] ?>"
                               <?= $r['is_active'] ? 'checked' : '' ?>>
                    </div>
                </td>
                <td>
                    <a href="announcement.php?edit=<?= $r['id'] ?>" class="btn btn-sm btn-outline-primary me-1" title="Edit">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                    </a>
                    <form method="post" action="announcement.php" class="d-inline"
                          onsubmit="return confirm('Delete this announcement?')">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= $r['id'] ?>">
                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4h6v2"/></svg>
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

</div><!-- /container -->
</div><!-- /content-body -->

<script>
// Toggle active via AJAX
document.querySelectorAll('.toggle-ann').forEach(function(cb) {
    cb.addEventListener('change', function() {
        var id  = this.dataset.id;
        var row = document.getElementById('row-' + id);
        var fd  = new FormData();
        fd.append('action', 'toggle');
        fd.append('id', id);
        fetch('announcement.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(d => {
                row.style.opacity = d.is_active ? '1' : '0.5';
            });
    });
});
</script>
<?php include 'include/footer.php'; ?>
