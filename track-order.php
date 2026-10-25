<?php
include 'include/head.php';
include 'include/navbar.php';

global $connection_database;

$order      = null;
$items      = [];
$errorMsg   = '';
$searchNum  = trim($_GET['order'] ?? $_POST['order_number'] ?? '');

if ($searchNum !== '') {
    $safe = $connection_database->real_escape_string($searchNum);
    $res  = $connection_database->query("SELECT * FROM orders WHERE order_number='$safe' LIMIT 1");
    if ($res && $res->num_rows > 0) {
        $order = $res->fetch_assoc();
        $iRes  = $connection_database->query(
            "SELECT oi.*, p.slug FROM order_items oi
             LEFT JOIN products p ON p.id = oi.product_id
             WHERE oi.order_id = " . (int)$order['id']
        );
        if ($iRes) while ($r = $iRes->fetch_assoc()) $items[] = $r;
    } else {
        $errorMsg = 'No order found with number <strong>' . htmlspecialchars($searchNum) . '</strong>. Please check and try again.';
    }
}

// Status pipeline (linear happy-path steps)
$pipeline = [
    ['key' => 'pending',         'label' => 'Order Placed',     'icon' => '<i class="fa fa-shopping-bag"></i>',  'desc' => 'Your order has been received'],
    ['key' => 'confirmed',       'label' => 'Confirmed',        'icon' => '<i class="fa fa-check-circle"></i>',  'desc' => 'Order confirmed by our team'],
    ['key' => 'packaging',       'label' => 'Packaging',        'icon' => '<i class="fa fa-cube"></i>',          'desc' => 'Your items are being packed'],
    ['key' => 'out of delivery', 'label' => 'Out for Delivery', 'icon' => '<i class="fa fa-truck"></i>',         'desc' => 'On the way to your address'],
    ['key' => 'delivered',       'label' => 'Delivered',        'icon' => '<i class="fa fa-home"></i>',          'desc' => 'Successfully delivered'],
];
$errorStates = ['return', 'failed deliver', 'cancel'];

function getStatusIndex(string $status, array $pipeline): int {
    foreach ($pipeline as $i => $step) {
        if ($step['key'] === $status) return $i;
    }
    return -1; // error/special state
}
?>
<style>
/* ── Track Order Page ─────────────────────────────── */
.to-page { padding: 42px 0 72px; background: #f7f7f7; min-height: 80vh; }

/* Search box */
.to-search-wrap { max-width: 580px; margin: 0 auto 36px; }
.to-search-title { font-size: 2rem; font-weight: 900; color: #111; text-align: center; margin-bottom: 6px; }
.to-search-sub   { text-align: center; color: #888; font-size: .9rem; margin-bottom: 24px; }
.to-search-form  { display: flex; gap: 10px; }
.to-search-input { flex: 1; padding: 13px 18px; border: 2px solid #e0e0e0; border-radius: 10px; font-size: .95rem; outline: none; transition: border-color .2s; }
.to-search-input:focus { border-color: #111; }
.to-search-btn   { padding: 13px 26px; background: #111; color: #fff; border: none; border-radius: 10px; font-size: .92rem; font-weight: 700; cursor: pointer; white-space: nowrap; transition: background .2s; }
.to-search-btn:hover { background: #333; }
.to-error { background: #fff3cd; border: 1px solid #ffc107; border-radius: 10px; padding: 14px 18px; color: #856404; font-size: .9rem; margin-top: 14px; text-align: center; }

/* Result card */
.to-card { background: #fff; border-radius: 16px; border: 1.5px solid #ebebeb; overflow: hidden; max-width: 800px; margin: 0 auto; box-shadow: 0 4px 24px rgba(0,0,0,.06); }

/* Card header */
.to-card-head { padding: 22px 28px; background: linear-gradient(135deg, #111 0%, #333 100%); color: #fff; display: flex; align-items: flex-start; justify-content: space-between; flex-wrap: wrap; gap: 12px; }
.to-order-num  { font-size: 1.15rem; font-weight: 800; letter-spacing: .5px; }
.to-order-date { font-size: .78rem; color: rgba(255,255,255,.65); margin-top: 3px; }
.to-status-pill { display: inline-flex; align-items: center; gap: 6px; padding: 6px 14px; border-radius: 20px; font-size: .8rem; font-weight: 700; }
.pill-pending        { background: #fff3cd; color: #856404; }
.pill-confirmed      { background: #d1e7dd; color: #0a5236; }
.pill-packaging      { background: #cff4fc; color: #055160; }
.pill-delivery       { background: #dde5ff; color: #3730a3; }
.pill-delivered      { background: #d1e7dd; color: #0a5236; }
.pill-return         { background: #f8d7da; color: #842029; }
.pill-failed         { background: #f8d7da; color: #842029; }
.pill-cancel         { background: #e2e3e5; color: #495057; }

/* Status stepper */
.to-stepper-wrap { padding: 32px 28px 24px; border-bottom: 1px solid #f0f0f0; }
.to-stepper { display: flex; align-items: flex-start; justify-content: space-between; position: relative; }
.to-stepper::before {
    content: '';
    position: absolute;
    top: 22px;
    left: 22px;
    right: 22px;
    height: 3px;
    background: #e0e0e0;
    z-index: 0;
}
.to-step-progress {
    position: absolute;
    top: 22px;
    left: 22px;
    height: 3px;
    background: linear-gradient(90deg, #111, #555);
    z-index: 1;
    transition: width .5s ease;
    border-radius: 3px;
}
.to-step { flex: 1; display: flex; flex-direction: column; align-items: center; text-align: center; position: relative; z-index: 2; }
.to-step-dot { width: 44px; height: 44px; border-radius: 50%; border: 3px solid #e0e0e0; background: #fff; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; margin-bottom: 8px; transition: all .3s; position: relative; }
.to-step.done .to-step-dot   { border-color: #111; background: #111; }
.to-step.active .to-step-dot { border-color: #111; background: #fff; box-shadow: 0 0 0 4px rgba(17,17,17,.12); }
.to-step-label { font-size: .72rem; font-weight: 700; color: #bbb; line-height: 1.3; max-width: 70px; }
.to-step.done .to-step-label   { color: #111; }
.to-step.active .to-step-label { color: #111; }
.to-step-desc { font-size: .65rem; color: #bbb; margin-top: 3px; max-width: 70px; display: none; }
.to-step.active .to-step-desc { display: block; color: #666; }

/* Error/special state banner */
.to-special-banner { margin: 24px 28px; padding: 16px 20px; border-radius: 12px; display: flex; align-items: center; gap: 12px; }
.to-special-banner.return   { background: #fff3cd; border: 1px solid #ffc107; }
.to-special-banner.failed   { background: #f8d7da; border: 1px solid #f5c2c7; }
.to-special-banner.cancel   { background: #e2e3e5; border: 1px solid #c8cdd2; }
.to-special-icon { font-size: 1.6rem; }
.to-special-text h5 { margin: 0 0 4px; font-size: .95rem; font-weight: 700; color: #333; }
.to-special-text p  { margin: 0; font-size: .82rem; color: #666; }

/* Order meta */
.to-meta { padding: 20px 28px; border-bottom: 1px solid #f0f0f0; display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; }
.to-meta-item label { font-size: .72rem; color: #999; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; display: block; margin-bottom: 4px; }
.to-meta-item span  { font-size: .9rem; font-weight: 600; color: #222; }

/* Order items */
.to-items-head { padding: 16px 28px 10px; font-size: .8rem; font-weight: 800; text-transform: uppercase; letter-spacing: .5px; color: #aaa; }
.to-items-list { padding: 0 28px 8px; }
.to-item-row  { display: flex; align-items: center; gap: 14px; padding: 10px 0; border-bottom: 1px solid #f5f5f5; }
.to-item-row:last-child { border-bottom: none; }
.to-item-img  { width: 52px; height: 60px; object-fit: cover; border-radius: 8px; border: 1px solid #eee; flex-shrink: 0; }
.to-item-info { flex: 1; }
.to-item-name { font-size: .88rem; font-weight: 600; color: #222; margin-bottom: 2px; }
.to-item-meta { font-size: .75rem; color: #999; }
.to-item-price { font-size: .9rem; font-weight: 700; color: #111; white-space: nowrap; }

/* Footer total */
.to-card-foot { padding: 16px 28px; background: #fafafa; border-top: 1.5px solid #f0f0f0; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px; }
.to-foot-total { font-size: 1.05rem; font-weight: 800; color: #111; }
.to-foot-pm    { font-size: .8rem; color: #888; }
.to-share-btn  { display: inline-flex; align-items: center; gap: 6px; padding: 9px 18px; background: #111; color: #fff; border-radius: 8px; font-size: .82rem; font-weight: 700; text-decoration: none; border: none; cursor: pointer; }
.to-share-btn:hover { background: #333; color: #fff; }

/* Delivery info */
.to-delivery { padding: 18px 28px; border-top: 1px solid #f0f0f0; }
.to-delivery h6 { font-size: .78rem; font-weight: 800; text-transform: uppercase; letter-spacing: .5px; color: #aaa; margin-bottom: 8px; }
.to-delivery p  { font-size: .88rem; color: #555; line-height: 1.6; margin: 0; }

/* FA icons inside stepper dots */
.to-step-dot i { font-size: 15px; color: #ccc; }
.to-step.active .to-step-dot i { color: #111; }

/* FA icons inside status pills & payment */
.to-status-pill i { font-size: .8rem; }
.to-foot-pm i { font-size: .8rem; }

/* Special banner icon */
.to-special-icon i { font-size: 1.6rem; }

/* Responsive */
@media (max-width: 600px) {
    .to-search-form { flex-direction: column; }
    .to-search-btn  { width: 100%; }
    .to-card-head   { padding: 16px 18px; }
    .to-stepper-wrap { padding: 24px 12px 18px; }
    .to-step-label  { font-size: .62rem; max-width: 54px; }
    .to-step-dot    { width: 36px; height: 36px; font-size: .9rem; }
    .to-stepper::before { top: 18px; }
    .to-step-progress   { top: 18px; }
    .to-meta { grid-template-columns: repeat(2,1fr); }
    .to-meta-item:last-child { grid-column: span 2; }
    .to-items-list, .to-items-head, .to-delivery, .to-card-foot { padding-left: 16px; padding-right: 16px; }
    .to-search-title { font-size: 1.5rem; }
}
</style>

<div class="to-page">
<div class="container">

    <!-- Search Form -->
    <div class="to-search-wrap">
        <h1 class="to-search-title">Track Your Order</h1>
        <p class="to-search-sub">Enter your order number to see live status and delivery updates</p>
        <form class="to-search-form" method="GET" action="">
            <input type="text" name="order" class="to-search-input"
                   placeholder="e.g. ORD-2026-XXXX"
                   value="<?= htmlspecialchars($searchNum) ?>" required>
            <button type="submit" class="to-search-btn">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="margin-right:5px;vertical-align:middle"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                Track
            </button>
        </form>
        <?php if ($errorMsg): ?>
        <div class="to-error"><?= $errorMsg ?></div>
        <?php endif; ?>
    </div>

    <?php if ($order):
        $status      = strtolower(trim($order['order_status'] ?? 'pending'));
        $isError     = in_array($status, $errorStates);
        $stepIndex   = getStatusIndex($status, $pipeline);
        $activeIndex = $isError ? 0 : $stepIndex; // error states don't advance pipeline
        $totalSteps  = count($pipeline);

        // Progress bar width
        $progressPct = $isError ? '0%' : ($stepIndex >= $totalSteps - 1
            ? 'calc(100% - 44px)'
            : 'calc(' . ($stepIndex / ($totalSteps - 1) * 100) . '% - ' . (44 - $stepIndex * (44 / ($totalSteps - 1))) . 'px)');

        // Status pill class
        $pillClass = match($status) {
            'confirmed'       => 'pill-confirmed',
            'packaging'       => 'pill-packaging',
            'out of delivery' => 'pill-delivery',
            'delivered'       => 'pill-delivered',
            'return'          => 'pill-return',
            'failed deliver'  => 'pill-failed',
            'cancel'          => 'pill-cancel',
            default           => 'pill-pending',
        };
        $pillLabel = match($status) {
            'confirmed'       => '<i class="fa fa-check-circle"></i> Confirmed',
            'packaging'       => '<i class="fa fa-cube"></i> Packaging',
            'out of delivery' => '<i class="fa fa-truck"></i> Out for Delivery',
            'delivered'       => '<i class="fa fa-home"></i> Delivered',
            'return'          => '<i class="fa fa-undo"></i> Return',
            'failed deliver'  => '<i class="fa fa-times-circle"></i> Failed Delivery',
            'cancel'          => '<i class="fa fa-ban"></i> Cancelled',
            default           => '<i class="fa fa-clock-o"></i> Pending',
        };

        // Payment method
        $pmLabel = match($order['payment_method'] ?? 'cod') {
            'jazzcash'    => '<i class="fa fa-mobile"></i> JazzCash',
            'easypaisa'   => '<i class="fa fa-mobile"></i> EasyPaisa',
            'stripe'      => '<i class="fa fa-credit-card"></i> Card / Stripe',
            'qr_transfer' => '<i class="fa fa-university"></i> Bank Transfer',
            default       => '<i class="fa fa-money"></i> Cash on Delivery',
        };

        $totalItems = count($items);
    ?>
    <div class="to-card">

        <!-- Header -->
        <div class="to-card-head">
            <div>
                <div class="to-order-num"><?= htmlspecialchars($order['order_number']) ?></div>
                <div class="to-order-date">Placed on <?= date('d M Y, h:i A', strtotime($order['created_at'])) ?></div>
            </div>
            <span class="to-status-pill <?= $pillClass ?>"><?= $pillLabel ?></span>
        </div>

        <!-- Stepper -->
        <?php if (!$isError): ?>
        <div class="to-stepper-wrap">
            <div class="to-stepper">
                <!-- Progress bar -->
                <div class="to-step-progress" style="width:<?= $progressPct ?>"></div>

                <?php foreach ($pipeline as $i => $step):
                    $isDone   = $i < $stepIndex;
                    $isActive = $i === $stepIndex;
                    $cls      = $isDone ? 'done' : ($isActive ? 'active' : '');
                ?>
                <div class="to-step <?= $cls ?>">
                    <div class="to-step-dot">
                        <?php if ($isDone): ?>
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                        <?php else: ?>
                        <?= $step['icon'] ?>
                        <?php endif; ?>
                    </div>
                    <div class="to-step-label"><?= $step['label'] ?></div>
                    <div class="to-step-desc"><?= $step['desc'] ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php else:
            // Show error state banner
            $bannerConf = match($status) {
                'return'         => ['cls'=>'return', 'icon'=>'<i class="fa fa-undo"></i>',         'title'=>'Return Requested', 'msg'=>'Your return request is being processed. Our team will contact you shortly.'],
                'failed deliver' => ['cls'=>'failed', 'icon'=>'<i class="fa fa-times-circle"></i>', 'title'=>'Delivery Failed', 'msg'=>'We could not deliver your order. Our team will reach out to reschedule.'],
                'cancel'         => ['cls'=>'cancel', 'icon'=>'<i class="fa fa-ban"></i>',          'title'=>'Order Cancelled', 'msg'=>'This order has been cancelled. If you need help, contact our support team.'],
                default          => ['cls'=>'cancel', 'icon'=>'<i class="fa fa-info-circle"></i>',  'title'=>ucfirst($status), 'msg'=>''],
            };
        ?>
        <div class="to-special-banner <?= $bannerConf['cls'] ?>">
            <span class="to-special-icon"><?= $bannerConf['icon'] ?></span>
            <div class="to-special-text">
                <h5><?= $bannerConf['title'] ?></h5>
                <p><?= $bannerConf['msg'] ?></p>
            </div>
        </div>
        <?php endif; ?>

        <!-- Meta grid -->
        <div class="to-meta">
            <div class="to-meta-item">
                <label>Customer</label>
                <span><?= htmlspecialchars($order['customer_name']) ?></span>
            </div>
            <div class="to-meta-item">
                <label>Payment</label>
                <span><?= $pmLabel ?></span>
            </div>
            <div class="to-meta-item">
                <label>Total</label>
                <span>Rs <?= number_format((float)$order['total_amount'], 0) ?></span>
            </div>
        </div>

        <!-- Delivery address -->
        <?php if (!empty($order['address'])): ?>
        <div class="to-delivery">
            <h6>Delivery Address</h6>
            <p>
                <?= htmlspecialchars($order['address']) ?>,
                <?= htmlspecialchars($order['city']) ?>
                <?php if (!empty($order['postal_code'])): ?> — <?= htmlspecialchars($order['postal_code']) ?><?php endif; ?><br>
                <?= htmlspecialchars($order['country']) ?>
                <?php if (!empty($order['phone'])): ?>&nbsp;&nbsp;<i class="fa fa-phone"></i> <?= htmlspecialchars($order['phone']) ?><?php endif; ?>
            </p>
        </div>
        <?php endif; ?>

        <!-- Items -->
        <?php if (!empty($items)): ?>
        <div class="to-items-head">Order Items (<?= $totalItems ?>)</div>
        <div class="to-items-list">
            <?php foreach ($items as $item):
                $img    = trim($item['product_image'] ?? '');
                $imgUrl = $img ? $url_domain . '/admin/images/product/' . $img : $url_domain . '/assets/images/profile.jpg';
            ?>
            <div class="to-item-row">
                <img src="<?= htmlspecialchars($imgUrl) ?>" alt="<?= htmlspecialchars($item['product_name']) ?>"
                     class="to-item-img" onerror="this.src='<?= $url_domain ?>/assets/images/profile.jpg'">
                <div class="to-item-info">
                    <div class="to-item-name">
                        <?php if (!empty($item['slug'])): ?>
                        <a href="<?= $url_domain ?>/product/<?= htmlspecialchars($item['slug']) ?>" style="color:inherit;text-decoration:none;">
                            <?= htmlspecialchars($item['product_name']) ?>
                        </a>
                        <?php else: ?>
                        <?= htmlspecialchars($item['product_name']) ?>
                        <?php endif; ?>
                    </div>
                    <div class="to-item-meta">
                        Qty: <?= (int)($item['quantity'] ?? 1) ?>
                        <?php if (!empty($item['size'])): ?> &middot; Size: <?= htmlspecialchars($item['size']) ?><?php endif; ?>
                        <?php if (!empty($item['colors'])): ?> &middot; Color: <?= htmlspecialchars($item['colors']) ?><?php endif; ?>
                    </div>
                </div>
                <div class="to-item-price">Rs <?= number_format((float)($item['price'] ?? 0), 0) ?></div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Footer -->
        <div class="to-card-foot">
            <div>
                <div class="to-foot-total">Total: Rs <?= number_format((float)$order['total_amount'], 0) ?></div>
                <div class="to-foot-pm"><?= $pmLabel ?> &middot; <?= ucfirst($order['payment_status'] ?? 'pending') ?> payment</div>
            </div>
            <div style="display:flex;gap:10px;flex-wrap:wrap;">
                <?php if (isset($_SESSION['WEBSITE-LOGINSS'])): ?>
                <a href="<?= $url_domain ?>/my-orders" style="display:inline-flex;align-items:center;gap:5px;padding:9px 16px;border:1.5px solid #ddd;border-radius:8px;font-size:.82rem;font-weight:700;color:#555;text-decoration:none;">
                    ← My Orders
                </a>
                <?php endif; ?>
                <button class="to-share-btn" onclick="copyLink()">
                    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg>
                    <span id="shareLabel">Share Tracking</span>
                </button>
            </div>
        </div>

    </div><!-- /to-card -->
    <?php endif; ?>

    <!-- Help note -->
    <p style="text-align:center;color:#bbb;font-size:.82rem;margin-top:28px;">
        Need help? <a href="<?= $url_domain ?>/contact" style="color:#111;font-weight:700;">Contact Support</a>
        &nbsp;·&nbsp;
        <a href="https://wa.me/923209815213" target="_blank" style="color:#25d366;font-weight:700;">WhatsApp Us</a>
    </p>

</div>
</div>

<script>
function copyLink() {
    const url = window.location.href;
    navigator.clipboard.writeText(url).then(() => {
        const lbl = document.getElementById('shareLabel');
        lbl.textContent = 'Link Copied!';
        setTimeout(() => lbl.textContent = 'Share Tracking', 2000);
    }).catch(() => {
        prompt('Copy this link:', url);
    });
}
</script>

<?php include 'include/footer.php'; ?>
