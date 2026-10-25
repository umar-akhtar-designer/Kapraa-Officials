<?php
include 'include/head.php';
include 'include/navbar.php';

// Must be logged in
if (!isset($_SESSION['WEBSITE-LOGINSS'])) {
    header('Location: ' . $url_domain . '/login');
    exit;
}

$userId   = (int)$_SESSION['WEBSITE-LOGINSS'];
global $connection_database;

// Get customer info
$custRow = $connection_database->query("SELECT name, email FROM customers WHERE id=$userId")->fetch_assoc();
$custName  = $custRow['name']  ?? 'Customer';
$custEmail = $custRow['email'] ?? '';

// Pagination
$perPage = 8;
$page    = max(1, (int)($_GET['page'] ?? 1));
$offset  = ($page - 1) * $perPage;

// Filter
$filterStatus = $_GET['status'] ?? '';
$allowed = ['pending','confirmed','packaging','out of delivery','return','failed deliver','cancel'];
$statusWhere = ($filterStatus && in_array($filterStatus, $allowed))
    ? " AND order_status='" . $connection_database->real_escape_string($filterStatus) . "'"
    : '';

// Total count
$totalRes = $connection_database->query("SELECT COUNT(*) c FROM orders WHERE email='" . $connection_database->real_escape_string($custEmail) . "'{$statusWhere}");
$total    = $totalRes ? (int)$totalRes->fetch_assoc()['c'] : 0;
$totalPages = max(1, ceil($total / $perPage));

// Fetch orders
$orders = [];
$res = $connection_database->query("SELECT * FROM orders WHERE email='" . $connection_database->real_escape_string($custEmail) . "'{$statusWhere} ORDER BY id DESC LIMIT $perPage OFFSET $offset");
if ($res) while ($r = $res->fetch_assoc()) $orders[] = $r;
?>
<style>
/* ── My Orders Page ─────────────────────────────── */
.mo-page { padding: 36px 0 60px; background: #f8f8f8; min-height: 80vh; }
.mo-header { display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px; margin-bottom:24px; }
.mo-title { font-size:1.6rem; font-weight:800; color:#111; position:relative; padding-bottom:10px; }
.mo-title::after { content:''; position:absolute; bottom:0; left:0; width:50px; height:3px; background:#111; border-radius:2px; }
/* Filter tabs */
.mo-filters { display:flex; gap:8px; flex-wrap:wrap; margin-bottom:20px; }
.mo-filter-btn { padding:6px 16px; border-radius:20px; border:1.5px solid #ddd; background:#fff; font-size:.8rem; font-weight:600; cursor:pointer; text-decoration:none; color:#555; transition:all .2s; }
.mo-filter-btn:hover, .mo-filter-btn.active { background:#111; color:#fff; border-color:#111; }
/* Order cards */
.mo-card { background:#fff; border-radius:14px; border:1.5px solid #ebebeb; margin-bottom:14px; overflow:hidden; transition:box-shadow .2s; }
.mo-card:hover { box-shadow:0 4px 18px rgba(0,0,0,.08); }
.mo-card-head { display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px; padding:16px 20px; border-bottom:1px solid #f0f0f0; background:#fafafa; }
.mo-order-num { font-weight:800; font-size:.95rem; color:#111; }
.mo-order-date { font-size:.78rem; color:#999; }
.mo-status { display:inline-flex; align-items:center; gap:5px; padding:4px 12px; border-radius:20px; font-size:.75rem; font-weight:700; }
.status-pending      { background:#fff3cd; color:#856404; }
.status-confirmed    { background:#d1e7dd; color:#0a5236; }
.status-packaging    { background:#cff4fc; color:#055160; }
.status-delivery     { background:#dde5ff; color:#3730a3; }
.status-return       { background:#f8d7da; color:#842029; }
.status-failed       { background:#f8d7da; color:#842029; }
.status-cancelled    { background:#e2e3e5; color:#495057; }
.mo-card-body { padding:16px 20px; }
.mo-items-row { display:flex; gap:10px; flex-wrap:wrap; align-items:center; }
.mo-item-img { width:52px; height:60px; object-fit:cover; border-radius:8px; border:1px solid #eee; flex-shrink:0; }
.mo-item-info { flex:1; min-width:120px; }
.mo-item-name { font-weight:600; font-size:.85rem; color:#222; margin-bottom:2px; }
.mo-item-meta { font-size:.75rem; color:#888; }
.mo-more-items { font-size:.78rem; color:#555; background:#f5f5f5; border-radius:6px; padding:4px 10px; white-space:nowrap; }
.mo-card-foot { display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px; padding:12px 20px; border-top:1px solid #f0f0f0; }
.mo-total { font-weight:800; font-size:.95rem; color:#111; }
.mo-total span { font-size:.75rem; color:#888; font-weight:400; margin-left:4px; }
.mo-actions { display:flex; gap:8px; flex-wrap:wrap; }
.mo-btn { display:inline-flex; align-items:center; gap:5px; padding:7px 16px; border-radius:8px; font-size:.8rem; font-weight:700; text-decoration:none; border:none; cursor:pointer; transition:all .2s; }
.mo-btn-primary { background:#111; color:#fff; }
.mo-btn-primary:hover { background:#333; color:#fff; }
.mo-btn-outline { background:#fff; color:#111; border:1.5px solid #ddd; }
.mo-btn-outline:hover { border-color:#111; }
/* Payment badge */
.pay-pill { display:inline-flex; align-items:center; gap:4px; font-size:.72rem; font-weight:700; padding:3px 9px; border-radius:12px; background:#f0f0f0; color:#555; }
/* Empty state */
.mo-empty { text-align:center; padding:60px 20px; color:#bbb; }
.mo-empty svg { margin-bottom:16px; opacity:.4; }
.mo-empty h4 { font-size:1.1rem; color:#888; margin-bottom:8px; }
.mo-empty p { font-size:.88rem; color:#bbb; }
/* Pagination */
.mo-pagination { display:flex; gap:6px; justify-content:center; margin-top:24px; flex-wrap:wrap; }
.mo-page-btn { width:36px; height:36px; display:flex; align-items:center; justify-content:center; border-radius:8px; border:1.5px solid #ddd; text-decoration:none; font-size:.85rem; font-weight:600; color:#555; background:#fff; transition:all .2s; }
.mo-page-btn:hover, .mo-page-btn.active { background:#111; color:#fff; border-color:#111; }
/* Responsive */
@media (max-width: 575px) {
    .mo-card-head { padding:12px 14px; }
    .mo-card-body { padding:12px 14px; }
    .mo-card-foot { padding:10px 14px; }
    .mo-title { font-size:1.3rem; }
    .mo-total { font-size:.88rem; }
}
</style>

<div class="mo-page">
<div class="container">
    <!-- Header -->
    <div class="mo-header">
        <div>
            <h1 class="mo-title">My Orders</h1>
            <p style="color:#888;font-size:.85rem;margin-top:6px;">Welcome back, <?= htmlspecialchars($custName) ?> — <?= $total ?> order<?= $total != 1 ? 's' : '' ?> found</p>
        </div>
        <a href="<?= $url_domain ?>/track-order" class="mo-btn mo-btn-outline">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            Track an Order
        </a>
    </div>

    <!-- Status Filters -->
    <div class="mo-filters">
        <a href="my-orders" class="mo-filter-btn <?= !$filterStatus ? 'active' : '' ?>">All Orders</a>
        <a href="my-orders?status=pending"      class="mo-filter-btn <?= $filterStatus=='pending'      ? 'active' : '' ?>"><svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg> Pending</a>
        <a href="my-orders?status=confirmed"    class="mo-filter-btn <?= $filterStatus=='confirmed'    ? 'active' : '' ?>"><svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg> Confirmed</a>
        <a href="my-orders?status=packaging"    class="mo-filter-btn <?= $filterStatus=='packaging'    ? 'active' : '' ?>"><svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg> Packaging</a>
        <a href="my-orders?status=out of delivery" class="mo-filter-btn <?= $filterStatus=='out of delivery' ? 'active' : '' ?>"><svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg> Shipped</a>
        <a href="my-orders?status=cancel"    class="mo-filter-btn <?= $filterStatus=='cancel'    ? 'active' : '' ?>"><svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg> Cancelled</a>
    </div>

    <!-- Orders List -->
    <?php if (empty($orders)): ?>
    <div class="mo-empty">
        <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="#bbb" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/></svg>
        <h4><?= $filterStatus ? "No $filterStatus orders found" : "No orders yet" ?></h4>
        <p>When you place an order, it will appear here.</p>
        <a href="<?= $url_domain ?>/products" class="mo-btn mo-btn-primary" style="margin-top:16px;display:inline-flex;">Shop Now</a>
    </div>
    <?php else: ?>

    <?php foreach ($orders as $order):
        // Status class
        $statusKey = strtolower(trim($order['order_status'] ?? 'pending'));
        $statusMap = [
            'pending'          => ['class'=>'status-pending',  'icon'=>'<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>', 'label'=>'Pending'],
            'confirmed'        => ['class'=>'status-confirmed','icon'=>'<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>', 'label'=>'Confirmed'],
            'packaging'        => ['class'=>'status-packaging','icon'=>'<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>', 'label'=>'Packaging'],
            'out of delivery'  => ['class'=>'status-delivery', 'icon'=>'<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>', 'label'=>'Out for Delivery'],
            'return'           => ['class'=>'status-return',   'icon'=>'<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-3.57"/></svg>', 'label'=>'Return'],
            'failed deliver'   => ['class'=>'status-failed',   'icon'=>'<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>', 'label'=>'Failed Delivery'],
            'cancel'           => ['class'=>'status-cancelled','icon'=>'<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>', 'label'=>'Cancelled'],
        ];
        $st = $statusMap[$statusKey] ?? ['class'=>'status-pending','icon'=>'⏳','label'=>ucfirst($statusKey)];

        // Payment method label
        $pmLabel = match($order['payment_method'] ?? 'cod') {
            'cod'         => '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg> Cash on Delivery',
            'jazzcash'    => '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="2" width="14" height="20" rx="2" ry="2"/><line x1="12" y1="18" x2="12.01" y2="18"/></svg> JazzCash',
            'easypaisa'   => '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="2" width="14" height="20" rx="2" ry="2"/><line x1="12" y1="18" x2="12.01" y2="18"/></svg> EasyPaisa',
            'stripe'      => '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg> Card',
            'qr_transfer' => '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg> Bank Transfer',
            default       => ucfirst($order['payment_method'] ?? 'COD'),
        };

        // Get order items
        $itemsRes = $connection_database->query("SELECT oi.*, p.slug FROM order_items oi LEFT JOIN products p ON p.id=oi.product_id WHERE oi.order_id=" . (int)$order['id'] . " LIMIT 4");
        $items = [];
        if ($itemsRes) while ($it = $itemsRes->fetch_assoc()) $items[] = $it;
        $totalItems = (int)$connection_database->query("SELECT COUNT(*) c FROM order_items WHERE order_id=" . (int)$order['id'])->fetch_assoc()['c'];
    ?>
    <div class="mo-card">
        <!-- Card Head -->
        <div class="mo-card-head">
            <div>
                <div class="mo-order-num"><?= htmlspecialchars($order['order_number']) ?></div>
                <div class="mo-order-date"><?= date('d M Y, h:i A', strtotime($order['created_at'] ?? 'now')) ?></div>
            </div>
            <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                <span class="pay-pill"><?= $pmLabel ?></span>
                <span class="mo-status <?= $st['class'] ?>"><?= $st['icon'] ?> <?= $st['label'] ?></span>
            </div>
        </div>

        <!-- Items Preview -->
        <div class="mo-card-body">
            <div class="mo-items-row">
                <?php foreach (array_slice($items, 0, 3) as $item):
                    $img = trim($item['product_image'] ?? '');
                    // Smart URL: full Cloudinary/external URLs used as-is; relative paths get local prefix
                    if (!empty($img) && (strpos($img, 'http://') === 0 || strpos($img, 'https://') === 0)) {
                        $imgUrl = $img;
                    } elseif (!empty($img)) {
                        $imgUrl = $url_domain . '/admin/images/product/' . $img;
                    } else {
                        $imgUrl = $url_domain . '/assets/images/profile.jpg';
                    }
                ?>
                <img src="<?= htmlspecialchars($imgUrl) ?>" alt="<?= htmlspecialchars($item['product_name']) ?>" class="mo-item-img"
                     onerror="this.src='<?= $url_domain ?>/assets/images/profile.jpg'">
                <?php endforeach; ?>

                <?php if (count($items) >= 1): ?>
                <div class="mo-item-info">
                    <div class="mo-item-name"><?= htmlspecialchars($items[0]['product_name'] ?? 'Product') ?></div>
                    <div class="mo-item-meta">
                        <?php if (!empty($items[0]['size'])): ?>Size: <?= htmlspecialchars($items[0]['size']) ?> · <?php endif; ?>
                        Qty: <?= (int)($items[0]['quantity'] ?? 1) ?>
                    </div>
                    <?php if ($totalItems > 1): ?>
                    <div class="mo-more-items">+<?= $totalItems - 1 ?> more item<?= $totalItems - 1 > 1 ? 's' : '' ?></div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Card Footer -->
        <div class="mo-card-foot">
            <div class="mo-total">
                Rs <?= number_format((float)($order['total_amount'] ?? 0), 0) ?>
                <span>(<?= $totalItems ?> item<?= $totalItems != 1 ? 's' : '' ?>)</span>
            </div>
            <div class="mo-actions">
                <a href="<?= $url_domain ?>/track-order?order=<?= urlencode($order['order_number']) ?>" class="mo-btn mo-btn-outline">
                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    Track
                </a>
                <a href="<?= $url_domain ?>/order-confirmation?order=<?= urlencode($order['order_number']) ?>" class="mo-btn mo-btn-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    View Details
                </a>
            </div>
        </div>
    </div>
    <?php endforeach; ?>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <div class="mo-pagination">
        <?php if ($page > 1): ?>
        <a href="?page=<?= $page-1 ?><?= $filterStatus ? '&status='.$filterStatus : '' ?>" class="mo-page-btn">‹</a>
        <?php endif; ?>
        <?php for ($i = max(1,$page-2); $i <= min($totalPages,$page+2); $i++): ?>
        <a href="?page=<?= $i ?><?= $filterStatus ? '&status='.$filterStatus : '' ?>" class="mo-page-btn <?= $i==$page ? 'active' : '' ?>"><?= $i ?></a>
        <?php endfor; ?>
        <?php if ($page < $totalPages): ?>
        <a href="?page=<?= $page+1 ?><?= $filterStatus ? '&status='.$filterStatus : '' ?>" class="mo-page-btn">›</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    <?php endif; ?>

</div>
</div>

<?php include 'include/footer.php'; ?>
