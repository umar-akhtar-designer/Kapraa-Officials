<?php
    require 'include/head.php';
    session('WEBSITE-LOGIN');
?>
<style>
.rev-avatar {
    width: 42px; height: 42px; border-radius: 50%; object-fit: cover;
    background: linear-gradient(135deg,#ED2590,#a855f7);
    color: #fff; font-weight: 700; font-size: 1.1rem;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0; overflow: hidden;
}
.rev-avatar img { width: 100%; height: 100%; object-fit: cover; }
.star-display { color: #f5a623; font-size: 1rem; letter-spacing: 1px; }
.star-display .empty { color: #ddd; }
.badge-visible   { background:#28a745; color:#fff; }
.badge-hidden    { background:#6c757d; color:#fff; }
.search-bar { max-width: 340px; }
.action-btns .btn { padding: 3px 8px; font-size: .78rem; }
/* Edit modal star picker */
.modal-star { font-size: 1.6rem; cursor: pointer; color: #ddd; }
.modal-star.on { color: #f5a623; }
</style>

<div class="content-body">
    <div class="container-fluid">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header d-flex align-items-center gap-3 flex-wrap">
                    <h4 class="card-title mb-0">Product Reviews</h4>
                    <!-- Filters -->
                    <div class="ms-auto d-flex gap-2 flex-wrap align-items-center">
                        <form method="get" class="d-flex gap-2" id="filterForm">
                            <input type="text" name="search" class="form-control form-control-sm search-bar"
                                placeholder="Search name / review / product…"
                                value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                            <select name="status" class="form-select form-select-sm" style="width:130px;" onchange="this.form.submit()">
                                <option value="">All Status</option>
                                <option value="visible" <?= (($_GET['status'] ?? '') === 'visible')  ? 'selected' : '' ?>>Visible</option>
                                <option value="hidden"  <?= (($_GET['status'] ?? '') === 'hidden')   ? 'selected' : '' ?>>Hidden</option>
                            </select>
                            <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                            <a href="reviews.php" class="btn btn-outline-secondary btn-sm">Reset</a>
                        </form>
                    </div>
                </div>
                <div class="card-body p-0">
                    <?php
                        // ── Query ──────────────────────────────────────────────
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

                        $totQ  = $connection_database->query("SELECT COUNT(*) c FROM product_reviews r LEFT JOIN products p ON p.id=r.product_id WHERE $wStr");
                        $total = ($totQ) ? (int)$totQ->fetch_assoc()['c'] : 0;
                        $totalPages = max(1, ceil($total / $perPage));
                    ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:50px">#</th>
                                    <th style="width:200px">Reviewer</th>
                                    <th>Product</th>
                                    <th style="width:110px">Rating</th>
                                    <th>Review</th>
                                    <th style="width:90px">👍 / 👎</th>
                                    <th style="width:105px">Date</th>
                                    <th style="width:90px">Status</th>
                                    <th style="width:120px">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php if ($res && $res->num_rows > 0):
                                $idx = $offset + 1;
                                while ($r = $res->fetch_assoc()):
                                    $initials = mb_strtoupper(mb_substr($r['reviewer_name'], 0, 1));
                                    $photoUrl = $r['reviewer_photo']
                                        ? '../uploads/review_photos/' . htmlspecialchars($r['reviewer_photo'])
                                        : '';
                                    $stars = '';
                                    for ($i = 1; $i <= 5; $i++) {
                                        $stars .= $i <= (int)$r['rating']
                                            ? '<span>★</span>'
                                            : '<span class="empty">★</span>';
                                    }
                            ?>
                                <tr id="row-<?= $r['id'] ?>">
                                    <td><?= $idx++ ?></td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="rev-avatar">
                                                <?php if ($photoUrl): ?>
                                                    <img src="<?= $photoUrl ?>" alt="">
                                                <?php else: ?>
                                                    <?= $initials ?>
                                                <?php endif; ?>
                                            </div>
                                            <div>
                                                <div class="fw-600 text-dark" style="font-size:.9rem"><?= htmlspecialchars($r['reviewer_name']) ?></div>
                                                <?php if ($r['user_id']): ?>
                                                    <small class="text-muted">User #<?= $r['user_id'] ?></small>
                                                <?php else: ?>
                                                    <small class="text-muted">Guest</small>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($r['product_name']): ?>
                                            <a href="../product-details.php?slug=<?= htmlspecialchars($r['product_slug']) ?>" target="_blank" class="text-dark text-decoration-none" style="font-size:.85rem">
                                                <?= htmlspecialchars($r['product_name']) ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="star-display"><?= $stars ?></div>
                                        <small class="text-muted"><?= $r['rating'] ?>/5</small>
                                    </td>
                                    <td style="max-width:300px">
                                        <div style="font-size:.88rem;max-height:72px;overflow:hidden;line-height:1.4">
                                            <?= nl2br(htmlspecialchars($r['review_text'])) ?>
                                        </div>
                                    </td>
                                    <td style="white-space:nowrap;text-align:center">
                                        <span style="color:#27ae60;font-weight:600;">👍 <?= (int)($r['likes'] ?? 0) ?></span><br>
                                        <span style="color:#e74c3c;font-weight:600;">👎 <?= (int)($r['dislikes'] ?? 0) ?></span>
                                    </td>
                                    <td style="font-size:.82rem;white-space:nowrap">
                                        <?= date('d M Y', strtotime($r['created_at'])) ?><br>
                                        <span class="text-muted"><?= date('h:i A', strtotime($r['created_at'])) ?></span>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?= $r['status'] ?> px-2 py-1" id="badge-<?= $r['id'] ?>">
                                            <?= ucfirst($r['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-btns d-flex gap-1 flex-wrap">
                                            <!-- Toggle visible/hidden -->
                                            <button class="btn btn-sm btn-outline-secondary" title="Toggle Visibility"
                                                onclick="toggleReview(<?= $r['id'] ?>)">
                                                <i class="fa fa-eye"></i>
                                            </button>
                                            <!-- Edit -->
                                            <button class="btn btn-sm btn-outline-primary" title="Edit Review"
                                                onclick="openEdit(<?= $r['id'] ?>, '<?= addslashes(htmlspecialchars($r['reviewer_name'])) ?>', <?= $r['rating'] ?>, '<?= addslashes(htmlspecialchars($r['review_text'])) ?>', '<?= $r['status'] ?>')">
                                                <i class="fa fa-edit"></i>
                                            </button>
                                            <!-- Delete -->
                                            <button class="btn btn-sm btn-outline-danger" title="Delete Review"
                                                onclick="deleteReview(<?= $r['id'] ?>)">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; else: ?>
                                <tr><td colspan="8" class="text-center py-4 text-muted">No reviews found.</td></tr>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                    <div class="px-3 py-3 d-flex justify-content-between align-items-center">
                        <small class="text-muted">
                            Showing <?= $offset + 1 ?>–<?= min($offset + $perPage, $total) ?> of <?= $total ?> reviews
                        </small>
                        <nav>
                            <ul class="pagination pagination-sm mb-0">
                                <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                                    <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                                        <a class="page-link" href="reviews.php?page=<?= $p ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($statusF) ?>">
                                            <?= $p ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                            </ul>
                        </nav>
                    </div>
                    <?php endif; ?>

                </div><!-- /card-body -->
            </div>
        </div>
    </div>
</div>

<!-- ── Edit Modal ──────────────────────────────────────── -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">Edit Review</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="editId">

                <!-- Rating stars -->
                <div class="mb-3">
                    <label class="form-label fw-bold">Rating</label>
                    <div id="editStarPicker" class="d-flex gap-1">
                        <span class="modal-star" data-v="1">★</span>
                        <span class="modal-star" data-v="2">★</span>
                        <span class="modal-star" data-v="3">★</span>
                        <span class="modal-star" data-v="4">★</span>
                        <span class="modal-star" data-v="5">★</span>
                    </div>
                    <input type="hidden" id="editRating" value="5">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Reviewer Name <span class="text-danger">*</span></label>
                    <input type="text" id="editName" class="form-control" placeholder="Reviewer name">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Review Text <span class="text-danger">*</span></label>
                    <textarea id="editText" class="form-control" rows="4" placeholder="Review text…"></textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Status</label>
                    <select id="editStatus" class="form-select">
                        <option value="visible">Visible</option>
                        <option value="hidden">Hidden</option>
                    </select>
                </div>

                <div id="editMsg" class="mt-2" style="min-height:22px;font-size:.88rem;"></div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-primary" onclick="saveEdit()">Save Changes</button>
            </div>
        </div>
    </div>
</div>

<!-- ── Delete Confirm Modal ────────────────────────────── -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width:380px">
        <div class="modal-content">
            <div class="modal-body text-center py-4">
                <i class="fa fa-trash text-danger" style="font-size:2.5rem;"></i>
                <h5 class="mt-3 mb-1">Delete this review?</h5>
                <p class="text-muted mb-4" style="font-size:.9rem">This action cannot be undone.</p>
                <div class="d-flex gap-2 justify-content-center">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn btn-danger" id="confirmDeleteBtn">Yes, Delete</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const AJAX_URL = '../review_ajax.php';

// ── Toggle visible/hidden ─────────────────────────────────
function toggleReview(id) {
    fetch(AJAX_URL, {
        method: 'POST',
        body: new URLSearchParams({ action: 'admin_toggle', id })
    }).then(r => r.json()).then(data => {
        if (data.success) {
            const badge = document.getElementById('badge-' + id);
            badge.textContent = data.new_status.charAt(0).toUpperCase() + data.new_status.slice(1);
            badge.className   = 'badge badge-' + data.new_status + ' px-2 py-1';
        }
    });
}

// ── Edit modal ────────────────────────────────────────────
let editStarVal = 5;
function openEdit(id, name, rating, text, status) {
    document.getElementById('editId').value     = id;
    document.getElementById('editName').value   = name;
    document.getElementById('editText').value   = text;
    document.getElementById('editStatus').value = status;
    document.getElementById('editMsg').innerHTML = '';
    setEditStars(rating);
    new bootstrap.Modal(document.getElementById('editModal')).show();
}

function setEditStars(v) {
    editStarVal = v;
    document.getElementById('editRating').value = v;
    document.querySelectorAll('#editStarPicker .modal-star').forEach(s => {
        s.classList.toggle('on', +s.dataset.v <= v);
    });
}

document.querySelectorAll('#editStarPicker .modal-star').forEach(s => {
    s.addEventListener('click',      () => setEditStars(+s.dataset.v));
    s.addEventListener('mouseenter', () => {
        document.querySelectorAll('#editStarPicker .modal-star').forEach(ss => ss.classList.toggle('on', +ss.dataset.v <= +s.dataset.v));
    });
    s.addEventListener('mouseleave', () => setEditStars(editStarVal));
});

function saveEdit() {
    const id     = document.getElementById('editId').value;
    const name   = document.getElementById('editName').value.trim();
    const text   = document.getElementById('editText').value.trim();
    const rating = document.getElementById('editRating').value;
    const status = document.getElementById('editStatus').value;
    const msg    = document.getElementById('editMsg');

    if (!name || !text) { msg.innerHTML = '<span class="text-danger">Name and review are required.</span>'; return; }

    fetch(AJAX_URL, {
        method: 'POST',
        body: new URLSearchParams({ action: 'admin_edit', id, name, review: text, rating, status })
    }).then(r => r.json()).then(data => {
        if (data.success) {
            msg.innerHTML = '<span class="text-success">✅ Saved!</span>';
            // Update the row in table
            const row = document.getElementById('row-' + id);
            if (row) {
                // Refresh the page after short delay to show updated data
                setTimeout(() => location.reload(), 800);
            }
        } else {
            msg.innerHTML = `<span class="text-danger">⚠️ ${data.message || 'Error saving.'}</span>`;
        }
    });
}

// ── Delete ────────────────────────────────────────────────
let pendingDeleteId = null;
function deleteReview(id) {
    pendingDeleteId = id;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}

document.getElementById('confirmDeleteBtn').addEventListener('click', () => {
    if (!pendingDeleteId) return;
    fetch(AJAX_URL, {
        method: 'POST',
        body: new URLSearchParams({ action: 'admin_delete', id: pendingDeleteId })
    }).then(r => r.json()).then(data => {
        if (data.success) {
            const row = document.getElementById('row-' + pendingDeleteId);
            if (row) row.remove();
            bootstrap.Modal.getInstance(document.getElementById('deleteModal')).hide();
        }
    });
    pendingDeleteId = null;
});
</script>

<?php include 'include/footer.php'; ?>
