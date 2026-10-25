<?php
require 'include/config.php';
session('WEBSITE-LOGIN');

// ── Period selection ──────────────────────────────────────────
$period = $_GET['period'] ?? 'today';
$db     = $connection_database;

$now   = date('Y-m-d');
$label = '';
$dateFrom = $dateTo = '';

switch ($period) {
    case 'today':
        $dateFrom = $now;
        $dateTo   = $now;
        $label    = 'Daily Summary — ' . date('d F Y');
        break;
    case 'week':
        $dateFrom = date('Y-m-d', strtotime('monday this week'));
        $dateTo   = date('Y-m-d', strtotime('sunday this week'));
        $label    = 'Weekly Summary — ' . date('d M', strtotime($dateFrom)) . ' to ' . date('d M Y', strtotime($dateTo));
        break;
    case 'month':
        $dateFrom = date('Y-m-01');
        $dateTo   = date('Y-m-t');
        $label    = 'Monthly Summary — ' . date('F Y');
        break;
}

$safeFrom = $db->real_escape_string($dateFrom);
$safeTo   = $db->real_escape_string($dateTo);

// ── Fetch orders in period ─────────────────────────────────────
$ordersRes = $db->query("
    SELECT o.*
    FROM orders o
    WHERE DATE(o.created_at) BETWEEN '$safeFrom' AND '$safeTo'
    ORDER BY o.created_at ASC
");
$orders = [];
if ($ordersRes) {
    while ($r = $ordersRes->fetch_assoc()) $orders[] = $r;
}

// ── For each order fetch its items ────────────────────────────
foreach ($orders as &$ord) {
    $oid   = (int)$ord['id'];
    $iRes  = $db->query("
        SELECT oi.product_name, oi.size, oi.colors, oi.quantity,
               oi.price, oi.stitching_price, oi.shipping_cost, oi.total_price,
               b.name AS brand_name
        FROM order_items oi
        LEFT JOIN products p  ON p.id  = oi.product_id
        LEFT JOIN brand    b  ON FIND_IN_SET(b.id, p.brand)
        WHERE oi.order_id = $oid
    ");
    $ord['items'] = [];
    if ($iRes) while ($ir = $iRes->fetch_assoc()) $ord['items'][] = $ir;
}
unset($ord);

// ── Summary totals ─────────────────────────────────────────────
$totalOrders   = count($orders);
$totalRevenue  = 0;
$statusCounts  = [];
foreach ($orders as $o) {
    $totalRevenue += (float)($o['total_amount'] ?? 0);
    $st = strtolower($o['order_status'] ?? 'pending');
    $statusCounts[$st] = ($statusCounts[$st] ?? 0) + 1;
}

$generatedAt = date('d M Y, h:i A');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Kapraa — <?= htmlspecialchars($label) ?></title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
<style>
  * { margin:0; padding:0; box-sizing:border-box; }
  body { font-family:'Segoe UI', Arial, sans-serif; font-size:11px; color:#1a1a1a; background:#fff; }

  /* ── Print button bar (hidden when printing) ── */
  .no-print {
    position:fixed; top:0; left:0; right:0; z-index:999;
    background:#1a1a1a; padding:10px 24px;
    display:flex; align-items:center; gap:12px;
  }
  .no-print h3 { color:#fff; font-size:13px; flex:1; }
  .btn-print {
    background:#25d366; color:#fff; border:none; padding:8px 22px;
    border-radius:6px; font-size:12px; font-weight:700; cursor:pointer;
  }
  .btn-period {
    background:transparent; color:#ccc; border:1px solid #555;
    padding:6px 14px; border-radius:6px; font-size:11px; cursor:pointer;
    text-decoration:none;
  }
  .btn-period.active { background:#fff; color:#1a1a1a; font-weight:700; }
  .btn-period:hover { background:#333; color:#fff; }

  /* ── Page content ── */
  .page { padding:60px 30px 30px; max-width:900px; margin:0 auto; }

  /* ── Header ── */
  .report-header {
    border-bottom:3px solid #1a1a1a;
    padding-bottom:14px; margin-bottom:18px;
    display:flex; justify-content:space-between; align-items:flex-end;
  }
  .brand-logo { display:flex; align-items:center; gap:10px; }
  .brand-logo-img {
    height:54px; width:auto; object-fit:contain;
  }
  .brand-name { font-size:22px; font-weight:900; color:#1a1a1a; letter-spacing:1px; }
  .report-meta { text-align:right; }
  .report-title { font-size:16px; font-weight:800; color:#1a1a1a; }
  .report-date  { font-size:10px; color:#888; margin-top:3px; }

  /* ── Summary cards ── */
  .summary-row {
    display:flex; gap:10px; margin-bottom:20px; flex-wrap:wrap;
  }
  .summary-card {
    flex:1; min-width:110px; border:1.5px solid #e5e5e5; border-radius:8px;
    padding:10px 14px; text-align:center;
  }
  .summary-card .sc-val {
    font-size:20px; font-weight:900; color:#1a1a1a; display:block;
  }
  .summary-card .sc-lbl {
    font-size:9.5px; color:#888; text-transform:uppercase; letter-spacing:.6px; margin-top:2px;
  }
  .summary-card.green  { border-color:#25d366; background:#f0fdf4; }
  .summary-card.green .sc-val { color:#15803d; }
  .summary-card.orange { border-color:#f59e0b; background:#fffbeb; }
  .summary-card.orange .sc-val { color:#b45309; }
  .summary-card.red    { border-color:#ef4444; background:#fef2f2; }
  .summary-card.red .sc-val { color:#dc2626; }
  .summary-card.blue   { border-color:#3b82f6; background:#eff6ff; }
  .summary-card.blue .sc-val { color:#1d4ed8; }

  /* ── Status breakdown ── */
  .status-row {
    display:flex; gap:8px; flex-wrap:wrap; margin-bottom:20px;
  }
  .status-pill {
    padding:4px 12px; border-radius:20px; font-size:10px; font-weight:700;
    text-transform:uppercase; letter-spacing:.5px;
  }
  .status-pill.pending   { background:#fef9c3; color:#854d0e; border:1px solid #fde047; }
  .status-pill.confirmed { background:#dcfce7; color:#15803d; border:1px solid #86efac; }
  .status-pill.packaging { background:#dbeafe; color:#1e40af; border:1px solid #93c5fd; }
  .status-pill.delivery  { background:#ede9fe; color:#6d28d9; border:1px solid #c4b5fd; }
  .status-pill.delivered { background:#d1fae5; color:#065f46; border:1px solid #6ee7b7; }
  .status-pill.cancelled { background:#fee2e2; color:#991b1b; border:1px solid #fca5a5; }
  .status-pill.returned  { background:#ffedd5; color:#9a3412; border:1px solid #fdba74; }
  .status-pill.failed    { background:#f3f4f6; color:#374151; border:1px solid #d1d5db; }

  /* ── Section heading ── */
  .section-title {
    font-size:12px; font-weight:800; text-transform:uppercase;
    letter-spacing:.8px; color:#1a1a1a; border-left:4px solid #1a1a1a;
    padding-left:8px; margin:20px 0 10px;
  }

  /* ── Order block ── */
  .order-block {
    border:1.5px solid #e5e5e5; border-radius:8px;
    margin-bottom:14px; overflow:hidden; page-break-inside:avoid;
  }
  .order-hdr {
    background:#f8f8f8; border-bottom:1px solid #e5e5e5;
    padding:8px 14px; display:flex; justify-content:space-between; align-items:center;
    flex-wrap:wrap; gap:6px;
  }
  .order-num   { font-weight:800; font-size:11.5px; color:#1a1a1a; }
  .order-date  { font-size:10px; color:#888; }
  .order-badge {
    font-size:9px; font-weight:700; padding:3px 10px; border-radius:12px;
    text-transform:uppercase; letter-spacing:.5px;
  }
  .badge-pending   { background:#fef9c3; color:#854d0e; }
  .badge-confirmed { background:#dcfce7; color:#15803d; }
  .badge-packaging { background:#dbeafe; color:#1e40af; }
  .badge-delivery  { background:#ede9fe; color:#6d28d9; }
  .badge-delivered { background:#d1fae5; color:#065f46; }
  .badge-cancelled { background:#fee2e2; color:#991b1b; }
  .badge-returned  { background:#ffedd5; color:#9a3412; }

  .order-body { padding:10px 14px; }

  .cust-grid {
    display:grid; grid-template-columns:repeat(3,1fr); gap:6px;
    margin-bottom:10px; padding-bottom:10px;
    border-bottom:1px dashed #e5e5e5;
  }
  .cust-item label { font-size:8.5px; color:#999; text-transform:uppercase; letter-spacing:.5px; display:block; }
  .cust-item span  { font-size:10.5px; font-weight:600; color:#1a1a1a; }

  /* ── Items table ── */
  table.items-tbl {
    width:100%; border-collapse:collapse; font-size:10px;
  }
  table.items-tbl thead th {
    background:#1a1a1a; color:#fff; padding:5px 8px;
    text-align:left; font-size:9px; text-transform:uppercase; letter-spacing:.5px;
    font-weight:700;
  }
  table.items-tbl thead th:last-child { text-align:right; }
  table.items-tbl tbody tr:nth-child(even) { background:#fafafa; }
  table.items-tbl tbody td {
    padding:5px 8px; border-bottom:1px solid #f0f0f0; vertical-align:middle;
  }
  table.items-tbl tbody td:last-child { text-align:right; font-weight:700; }
  .brand-tag {
    display:inline-block; background:#e5e7eb; color:#374151;
    font-size:8px; font-weight:700; padding:1px 6px; border-radius:4px;
    margin-top:2px;
  }
  .ai-tag {
    display:inline-block; background:#4f46e5; color:#fff;
    font-size:8px; font-weight:700; padding:1px 6px; border-radius:4px;
    margin-left:4px;
  }

  .order-foot {
    background:#f8f8f8; border-top:1px solid #e5e5e5;
    padding:7px 14px; display:flex; justify-content:space-between; align-items:center;
  }
  .order-foot .pay-method { font-size:9.5px; color:#666; }
  .order-foot .order-total { font-size:13px; font-weight:900; color:#1a1a1a; }

  /* ── Grand total footer ── */
  .grand-total-box {
    border:2px solid #1a1a1a; border-radius:8px;
    margin-top:24px; padding:16px 20px;
    display:flex; justify-content:space-between; align-items:center;
    page-break-inside:avoid;
  }
  .gt-left { font-size:13px; font-weight:800; color:#1a1a1a; }
  .gt-left span { display:block; font-size:9.5px; color:#888; font-weight:400; margin-top:2px; }
  .gt-right { font-size:24px; font-weight:900; color:#15803d; }

  .report-footer {
    margin-top:18px; text-align:center;
    font-size:9px; color:#bbb; border-top:1px solid #f0f0f0; padding-top:10px;
  }

  .no-orders {
    text-align:center; padding:40px; color:#999;
    border:2px dashed #e5e5e5; border-radius:8px; margin-top:10px;
  }
  .no-orders strong { display:block; font-size:14px; color:#555; margin-bottom:4px; }

  /* ── Print media ── */
  @media print {
    .no-print { display:none !important; }
    .page { padding:20px; max-width:100%; }
    body { font-size:10px; }
    .order-block { page-break-inside:avoid; }
    @page { margin:1.5cm; size:A4; }
  }
</style>
</head>
<body>

<!-- Print bar (hidden on print) -->
<div class="no-print">
  <h3>Kapraa — Order Summary Export</h3>
  <a href="?period=today" class="btn-period <?= $period==='today'?'active':'' ?>">Today</a>
  <a href="?period=week"  class="btn-period <?= $period==='week' ?'active':'' ?>">This Week</a>
  <a href="?period=month" class="btn-period <?= $period==='month'?'active':'' ?>">This Month</a>
  <button class="btn-print" onclick="window.print()"><i class="fa fa-print" style="margin-right:6px;"></i>Print / Save as PDF</button>
</div>

<div class="page">

  <!-- Header -->
  <div class="report-header">
    <div class="brand-logo">
      <img src="images/Kapraa-logo.png" alt="Kapraa" class="brand-logo-img"
           onerror="this.style.display='none'">
      <div>
        <div class="brand-name">KAPRAA</div>
      </div>
    </div>
    <div class="report-meta">
      <div class="report-title"><?= htmlspecialchars($label) ?></div>
      <div class="report-date">Generated: <?= $generatedAt ?></div>
      <div class="report-date">Period: <?= htmlspecialchars(date('d M Y', strtotime($dateFrom))) ?> – <?= htmlspecialchars(date('d M Y', strtotime($dateTo))) ?></div>
    </div>
  </div>

  <!-- Summary Cards -->
  <div class="summary-row">
    <div class="summary-card">
      <span class="sc-val"><?= $totalOrders ?></span>
      <span class="sc-lbl">Total Orders</span>
    </div>
    <div class="summary-card green">
      <span class="sc-val">Rs <?= number_format($totalRevenue, 0) ?></span>
      <span class="sc-lbl">Total Revenue</span>
    </div>
    <?php
    $statusColors = [
      'confirmed'=>'green','delivered'=>'green',
      'pending'=>'orange','packaging'=>'blue','delivery'=>'blue',
      'cancelled'=>'red','returned'=>'red','failed'=>'red',
    ];
    foreach ($statusCounts as $st => $cnt):
      $col = $statusColors[$st] ?? '';
    ?>
    <div class="summary-card <?= $col ?>">
      <span class="sc-val"><?= $cnt ?></span>
      <span class="sc-lbl"><?= ucfirst($st) ?></span>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Status breakdown pills -->
  <?php if (!empty($statusCounts)): ?>
  <div class="status-row">
    <?php foreach ($statusCounts as $st => $cnt): ?>
    <span class="status-pill <?= $st ?>"><?= ucfirst($st) ?>: <?= $cnt ?></span>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <!-- Orders Section -->
  <div class="section-title">Order Details</div>

  <?php if (empty($orders)): ?>
    <div class="no-orders">
      <strong>No orders found</strong>
      No orders were placed during this period.
    </div>
  <?php else: ?>

  <?php foreach ($orders as $ord):
    $st        = strtolower($ord['order_status'] ?? 'pending');
    $isAiOrder = strpos($ord['notes'] ?? '', 'AI Chat') !== false;
    $pmMap     = ['cod'=>'Cash on Delivery','jazzcash'=>'JazzCash','stripe'=>'Card/Stripe','qr_transfer'=>'Bank Transfer','easypaisa'=>'EasyPaisa'];
    $pm        = $pmMap[$ord['payment_method'] ?? 'cod'] ?? ucfirst($ord['payment_method'] ?? '');
    $addr      = implode(', ', array_filter([$ord['address'] ?? '', $ord['city'] ?? '', $ord['state'] ?? '', $ord['country'] ?? '']));
  ?>
  <div class="order-block">

    <!-- Order Header -->
    <div class="order-hdr">
      <div>
        <span class="order-num"><?= htmlspecialchars($ord['order_number']) ?></span>
        <?php if ($isAiOrder): ?><span class="ai-tag">🤖 AI</span><?php endif; ?>
        <span class="order-date">&nbsp;|&nbsp;<?= date('d M Y, h:i A', strtotime($ord['created_at'])) ?></span>
      </div>
      <span class="order-badge badge-<?= $st ?>"><?= ucfirst($st) ?></span>
    </div>

    <div class="order-body">

      <!-- Customer Info -->
      <div class="cust-grid">
        <div class="cust-item">
          <label>Customer</label>
          <span><?= htmlspecialchars($ord['customer_name']) ?></span>
        </div>
        <div class="cust-item">
          <label>Phone</label>
          <span><?= htmlspecialchars($ord['phone'] ?? '—') ?></span>
        </div>
        <div class="cust-item">
          <label>Email</label>
          <span><?= htmlspecialchars($ord['email'] ?? '—') ?></span>
        </div>
        <div class="cust-item" style="grid-column:1/-1;">
          <label>Delivery Address</label>
          <span><?= htmlspecialchars($addr ?: '—') ?></span>
        </div>
      </div>

      <!-- Items Table -->
      <?php if (!empty($ord['items'])): ?>
      <table class="items-tbl">
        <thead>
          <tr>
            <th>#</th>
            <th>Product Name</th>
            <th>Brand</th>
            <th>Size</th>
            <th>Qty</th>
            <th>Unit Price</th>
            <th>Total</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($ord['items'] as $idx => $item):
            $brandName = $item['brand_name'] ?? '';
          ?>
          <tr>
            <td><?= $idx + 1 ?></td>
            <td>
              <?= htmlspecialchars($item['product_name'] ?? '—') ?>
              <?php if (!empty($item['colors'])): ?>
              <br><span style="font-size:9px;color:#888;">Color: <?= htmlspecialchars($item['colors']) ?></span>
              <?php endif; ?>
            </td>
            <td>
              <?php if ($brandName): ?>
              <span class="brand-tag"><?= htmlspecialchars($brandName) ?></span>
              <?php else: ?>—<?php endif; ?>
            </td>
            <td><?= htmlspecialchars($item['size'] ?? '—') ?></td>
            <td style="text-align:center;"><?= (int)($item['quantity'] ?? 1) ?></td>
            <td>Rs <?= number_format((float)($item['price'] ?? 0), 0) ?></td>
            <td>Rs <?= number_format((float)($item['total_price'] ?? 0), 0) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php else: ?>
      <p style="font-size:10px;color:#aaa;padding:4px 0;">No item details available for this order.</p>
      <?php endif; ?>

    </div><!-- /order-body -->

    <!-- Order Footer -->
    <div class="order-foot">
      <span class="pay-method">💳 <?= htmlspecialchars($pm) ?></span>
      <span class="order-total">Total: Rs <?= number_format((float)($ord['total_amount'] ?? 0), 0) ?></span>
    </div>

  </div><!-- /order-block -->
  <?php endforeach; ?>

  <!-- Grand Total -->
  <div class="grand-total-box">
    <div class="gt-left">
      Grand Total — <?= $totalOrders ?> Order<?= $totalOrders != 1 ? 's' : '' ?>
      <span><?= htmlspecialchars($label) ?></span>
    </div>
    <div class="gt-right">Rs <?= number_format($totalRevenue, 0) ?></div>
  </div>

  <?php endif; ?>

  <!-- Footer -->
  <div class="report-footer">
    Kapraa &nbsp;|&nbsp; Report generated on <?= $generatedAt ?>
  </div>

</div><!-- /page -->

<script>
  // Auto-highlight correct period button
  document.addEventListener('DOMContentLoaded', function() {
    // Keyboard shortcut: Ctrl+P opens print dialog
    document.addEventListener('keydown', function(e) {
      if ((e.ctrlKey || e.metaKey) && e.key === 'p') { e.preventDefault(); window.print(); }
    });
  });
</script>

</body>
</html>
