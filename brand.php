<?php
// ============================================================
// brand.php — Dedicated Brand Page
// URL: /brand/{slug}  or  /brand/{slug}/volume/{id}
// ============================================================
include 'include/head.php';
include 'include/navbar.php';

$slug      = isset($_GET['slug'])   ? trim($_GET['slug'])   : '';
$activeVol = isset($_GET['volume']) ? (int)$_GET['volume']  : 0;

if (empty($slug)) { header('Location: ' . $url_domain . '/products.php'); exit; }

// ── Fetch brand ──────────────────────────────────────────────
$slug_safe = $connection_database->real_escape_string($slug);
$brandRes  = $connection_database->query("SELECT * FROM brand WHERE slug='$slug_safe' LIMIT 1");
if (!$brandRes || $brandRes->num_rows === 0) { header('Location: ' . $url_domain . '/products.php'); exit; }
$brand     = $brandRes->fetch_assoc();
$brandId   = (int)$brand['id'];
$brandName = htmlspecialchars($brand['name'] ?? '');
$tagLine   = htmlspecialchars($brand['tag_line'] ?? '');
$brandImg  = $brand['image']        ? $url_domain . '/admin/images/brand/'        . $brand['image']        : '';
$bannerImg = $brand['brand_banner'] ? $url_domain . '/admin/images/brand_banner/' . $brand['brand_banner'] : '';

// ── Fetch volumes (admin-managed per brand) ──────────────────
$volumes = [];
$vRes = $connection_database->query(
    "SELECT * FROM brand_volumes WHERE brand_id=$brandId ORDER BY sort_order ASC, volume_number ASC"
);
if ($vRes) while ($row = $vRes->fetch_assoc()) $volumes[] = $row;

// ── Active volume label ──────────────────────────────────────
$activeVolName = '';
if ($activeVol > 0) {
    foreach ($volumes as $v) {
        if ((int)$v['id'] === $activeVol) {
            $activeVolName = $v['volume_name'] ?: 'Vol ' . $v['volume_number'];
            break;
        }
    }
}

// ── Set-suit section removed — all products shown in All Products section ──
$setSuits = [];

// ── Fetch products ───────────────────────────────────────────
$volCondition = $activeVol > 0 ? "AND FIND_IN_SET($activeVol, volume_ids)" : '';

$productsRes  = $connection_database->query(
    "SELECT * FROM products
     WHERE FIND_IN_SET($brandId, brand)
       $volCondition
       AND LOWER(COALESCE(stock,'')) NOT IN ('out of stock','0','out stock')
       AND is_active != 0
     ORDER BY id DESC LIMIT 100"
);
$products      = [];
if ($productsRes) while ($row = $productsRes->fetch_assoc()) $products[] = $row;
$totalProducts = count($products);
?>

<style>
/* ═══════════════════════════════════════════════════════
   COPIED FROM product_home.php — exact same home page
   product card styles so cards look 100% identical
═══════════════════════════════════════════════════════ */
.price-container {
    position: relative;
    padding: 10px 0;
}
.original-price {
    color: #6c757d;
    text-decoration: line-through;
    font-size: 1rem;
    margin: 0;
    font-weight: normal;
}
.discounted-price {
    color: #dc3545;
    font-weight: bold;
    margin: 5px 0;
    font-size: 1.2rem;
}
.regular-price {
    color: #212529;
    font-weight: bold;
    margin: 5px 0;
    font-size: 1.2rem;
}
.discount-badge {
    background-color: #ED2590;
    color: white;
    padding: 3px 8px;
    border-radius: 4px;
    font-size: 0.8rem;
    font-weight: bold;
    position: absolute;
    top: 0;
    right: 0;
}
.image-container {
    position: relative;
}
.sale-badge {
    position: absolute;
    top: 10px;
    left: 10px;
    background-color: #74B549;
    color: white;
    padding: 5px 10px;
    border-radius: 4px;
    font-weight: bold;
    font-size: 12px;
    z-index: 1;
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}
.out-of-stock-badge {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background-color: rgba(0,0,0,0.8);
    color: white;
    padding: 8px 15px;
    border-radius: 4px;
    font-weight: bold;
    font-size: 14px;
    z-index: 1;
    text-align: center;
    width: 80%;
}
.brand-product-img {
    width: 100%;
    height: auto;
    display: block;
    border-radius: 4px;
}
@media (max-width: 768px) {
    .price-container { padding-top: 25px; }
    .discount-badge  { font-size: .7rem; padding: 2px 5px; top: 3px; right: -5px; }
}

/* ═══════════════════════════════════════════════════════
   No banner: just brand name centered, clean
═══════════════════════════════════════════════════════ */
.brand-header-nobanner {
    padding: 36px 20px 28px;
    text-align: center;
    background: #fff;
    border-bottom: 1px solid #f0f0f0;
}
.brand-header-nobanner h1 { font-size: 2rem; font-weight: 800; color: #222; margin: 0; }
.brand-header-nobanner p  { color: #888; margin: 6px 0 0; font-size: .9rem; }

/* ═══════════════════════════════════════════════════════
   Banner image — overflow:hidden clips the photo only
═══════════════════════════════════════════════════════ */
.brand-banner-img {
    position: relative;
    width: 100%;
    padding-top: 25%;
    overflow: hidden;
}
@media(max-width:768px){ .brand-banner-img{ padding-top:36%; } }
@media(max-width:576px){ .brand-banner-img{ padding-top:50%; } }
.brand-banner-img > img {
    position: absolute; top: 0; left: 0;
    width: 100%; height: 100%;
    object-fit: cover; display: block;
}

/* ═══════════════════════════════════════════════════════
   Circles strip — SIBLING of banner (not child), so
   banner's overflow:hidden does NOT clip it.

   margin-top: -70px  pulls it up by the circle radius
   (circle = 140px → radius = 70px), making circles
   straddle the banner's bottom edge: top half overlaps
   the banner, bottom half sits on white background.

   background gradient: transparent for the top 70px
   (so the banner image shows through behind the circle
   top-halves), then solid white below.
═══════════════════════════════════════════════════════ */
.vol-circles-strip {
    margin-top: -70px;
    padding: 0 20px 24px;
    background: linear-gradient(to bottom, transparent 70px, #fff 70px);
    display: flex;
    flex-wrap: wrap;
    gap: 28px;
    justify-content: center;
    align-items: flex-start;
    position: relative;
    z-index: 10;
    border-bottom: 1px solid #ebebeb;
}

/* Flat strip — when no banner, circles sit normally */
.vol-strip-flat {
    background: #fff;
    padding: 24px 20px 24px;
    border-bottom: 1px solid #ebebeb;
    display: flex;
    flex-wrap: wrap;
    gap: 28px;
    justify-content: center;
    align-items: flex-start;
}

/* Shared circle item */
.vol-item {
    display: flex; flex-direction: column;
    align-items: center; gap: 10px;
    text-decoration: none;
}
.vol-circle {
    width: 140px; height: 140px; border-radius: 50%;
    border: 4px solid #fff;
    box-shadow: 0 4px 20px rgba(0,0,0,.22);
    overflow: hidden;
    display: flex; align-items: center; justify-content: center;
    font-weight: 800; font-size: 1.1rem; color: #fff;
    transition: all .22s; flex-shrink: 0;
    background: #eee;
}
.vol-circle img {
    width: 100%; height: 100%;
    object-fit: cover; border-radius: 50%; display: block;
}
.vol-item.active .vol-circle,
.vol-item:hover  .vol-circle {
    border-color: #ED2590;
    box-shadow: 0 8px 28px rgba(237,37,144,.38);
    transform: scale(1.08);
}
.vol-name {
    font-size: 13px; font-weight: 700; color: #333;
    text-align: center; max-width: 140px; line-height: 1.3;
}
.vol-item.active .vol-name { color: #ED2590; }

/* Responsive — adjust radius for smaller circles */
@media(max-width:767px){
    .vol-circle { width: 110px; height: 110px; }
    /* radius = 55px */
    .vol-circles-strip { margin-top: -55px; background: linear-gradient(to bottom, transparent 55px, #fff 55px); gap: 20px; }
    .vol-strip-flat    { gap: 18px; }
    .vol-name { font-size: 12px; max-width: 110px; }
}
@media(max-width:576px){
    .vol-circle { width: 88px; height: 88px; }
    /* radius = 44px */
    .vol-circles-strip { margin-top: -44px; background: linear-gradient(to bottom, transparent 44px, #fff 44px); gap: 14px; padding: 0 12px 20px; }
    .vol-strip-flat    { gap: 14px; }
    .vol-name { font-size: 11px; max-width: 90px; }
}

/* ═══════════════════════════════════════════════════════
   Section heading
═══════════════════════════════════════════════════════ */
.bp-heading {
    font-size: 1.1rem; font-weight: 700; color: #222;
    padding: 18px 0 12px;
    display: flex; align-items: center; gap: 8px; flex-wrap: wrap;
    border-bottom: 2px solid #f0f0f0; margin-bottom: 18px;
}
.bp-heading .count { font-size: 13px; color: #aaa; font-weight: 400; }


.empty-state { text-align:center; padding:60px 20px; color:#bbb; }
</style>

<div>

    <?php
    // Build circle items HTML (reused in both banner and no-banner case)
    $grads = [
        'linear-gradient(135deg,#667eea,#764ba2)',
        'linear-gradient(135deg,#f093fb,#f5576c)',
        'linear-gradient(135deg,#4facfe,#00f2fe)',
        'linear-gradient(135deg,#43e97b,#38f9d7)',
        'linear-gradient(135deg,#fa709a,#fee140)',
        'linear-gradient(135deg,#a18cd1,#fbc2eb)',
        'linear-gradient(135deg,#ff9a9e,#fad0c4)',
        'linear-gradient(135deg,#ffecd2,#fcb69f)',
    ];
    ob_start();
    foreach ($volumes as $idx => $vol):
        $vImg    = $vol['volume_image'] ? $url_domain . '/admin/images/brand_volumes/' . $vol['volume_image'] : '';
        $vPos    = in_array($vol['image_position'] ?? '', ['top','center','bottom']) ? $vol['image_position'] : 'center';
        $vLink   = $url_domain . '/brand/' . urlencode($slug) . '/volume/' . $vol['id'];
        $vActive = ($activeVol === (int)$vol['id']) ? 'active' : '';
        $grad    = $grads[$idx % count($grads)];
        $vLabel  = trim($vol['volume_name'] ?? '');
        $vNum    = 'Vol ' . (int)$vol['volume_number'];
        $displayName = $vLabel ?: $vNum;
    ?>
        <a href="<?= $vLink ?>" class="vol-item <?= $vActive ?>">
            <div class="vol-circle" style="<?= $vImg ? '' : 'background:' . $grad ?>">
                <?php if ($vImg): ?>
                    <img src="<?= $vImg ?>" alt="<?= htmlspecialchars($displayName) ?>"
                         style="object-position:<?= $vPos ?> center;">
                <?php else: ?>
                    <span style="font-size:.85rem;"><?= htmlspecialchars($vNum) ?></span>
                <?php endif; ?>
            </div>
            <span class="vol-name"><?= htmlspecialchars($displayName) ?></span>
        </a>
    <?php endforeach;
    $circlesHtml = ob_get_clean();
    ?>

    <!-- ══ BANNER CASE ══════════════════════════════════════════ -->
    <?php if ($bannerImg): ?>

    <!-- Banner image — overflow:hidden clips photo only -->
    <div class="brand-banner-img">
        <img src="<?= $bannerImg ?>" alt="<?= $brandName ?>">
    </div>

    <?php if (!empty($volumes)): ?>
    <!-- Circles SIBLING of banner (not child) so overflow:hidden
         of banner does NOT clip circles.
         margin-top:-70px makes them straddle the banner bottom. -->
    <div class="vol-circles-strip">
        <?= $circlesHtml ?>
    </div>
    <?php endif; ?>

    <?php else: ?>

    <!-- ══ NO BANNER: just brand name centered ══════════════════ -->
    <div class="brand-header-nobanner">
        <h1><?= $brandName ?></h1>
        <?php if ($tagLine): ?><p><?= $tagLine ?></p><?php endif; ?>
    </div>

    <!-- Circles flat below brand name -->
    <?php if (!empty($volumes)): ?>
    <div class="vol-strip-flat">
        <?= $circlesHtml ?>
    </div>
    <?php endif; ?>

    <?php endif; ?>

    <!-- ══ SET SUIT RATES ════════════════════════════════════ -->
    <?php if (!empty($setSuits)): ?>
    <div class="container-fluid mt-3 mb-2">
        <div class="bp-heading">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#ED2590" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
            Set Suit Rates — <?= $brandName ?>
        </div>
        <div class="row g-3">
            <?php foreach ($setSuits as $ss):
                $imgs     = explode(',', $ss['image'] ?? '');
                $img1     = trim($imgs[0] ?? '');
                $imgUrl   = $img1 ? $url_domain . '/admin/images/product/' . $img1 : $url_domain . '/images/waiting images.jpeg';
                $ssPrice  = $ss['setsuite_price'] ?: $ss['price_after_discount'];
                $ssQty    = $ss['setsuite_quantity'] ? (int)$ss['setsuite_quantity'] . ' pcs' : '';
                $ssDisc   = $ss['setsuite_discount'] ? $ss['setsuite_discount'] . '% off' : '';
                $orig     = (float)($ss['price'] ?? 0);
                $sale_p   = (float)($ss['price_after_discount'] ?: $ss['price']);
                $dis_amt  = (float)($ss['dis_amount'] ?? 0);
                $hasDisc  = ($dis_amt > 0 && $sale_p < $orig);
                $discPct  = $hasDisc ? round(($dis_amt / $orig) * 100) : 0;
                $pLink    = $url_domain . '/product/' . $ss['slug'];
                $isSale   = ($ss['sale'] === 'In Sale');
                $isOut    = (strtolower(trim($ss['stock'] ?? '')) === 'out stock');
            ?>
            <div class="col-6 col-md-3 mb-3">
                <a href="<?= $pLink ?>" class="text-decoration-none">
                    <div class="card border-0 border-radius text-center">
                        <div class="image-container position-relative">
                            <img src="<?= $imgUrl ?>" alt="<?= htmlspecialchars($ss['name']) ?>" class="brand-product-img">
                            <?php if ($isSale): ?><div class="sale-badge">In Sale</div><?php endif; ?>
                            <?php if ($isOut):  ?><div class="out-of-stock-badge">SOLD OUT</div><?php endif; ?>
                        </div>
                        <p class="mt-2 mb-0"><?= htmlspecialchars($ss['name']) ?></p>
                        <div class="price-container">
                            <?php if ($hasDisc): ?>
                                <h5 class="original-price">Rs. <?= number_format($orig, 2) ?></h5>
                                <h5 class="discounted-price">Rs. <?= number_format($sale_p, 2) ?></h5>
                                <span class="discount-badge"><?= $discPct ?>% OFF</span>
                            <?php else: ?>
                                <h5 class="regular-price">Rs. <?= number_format($sale_p, 2) ?></h5>
                            <?php endif; ?>
                        </div>
                    </div>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
        <hr style="border:none;border-top:2px solid #f0f0f0;margin:8px 0;">
    </div>
    <?php endif; ?>

    <!-- ══ ALL / VOLUME PRODUCTS ═════════════════════════════ -->
    <div class="container-fluid mt-2">
        <div class="bp-heading">
            <?php if ($activeVol > 0): ?>
                <?= htmlspecialchars($activeVolName) ?> — Products
                <a href="<?= $url_domain ?>/brand/<?= urlencode($slug) ?>" style="font-size:13px;color:#ED2590;margin-left:4px;">← View All</a>
            <?php else: ?>
                All <?= $brandName ?> Products
            <?php endif; ?>
            <span class="count">(<?= $totalProducts ?>)</span>
        </div>

        <?php if (empty($products)): ?>
        <div class="empty-state">
            <p>No products in this collection yet.</p>
            <?php if ($activeVol > 0): ?>
            <p><a href="<?= $url_domain ?>/brand/<?= urlencode($slug) ?>" style="color:#ED2590;">View all <?= $brandName ?> products</a></p>
            <?php endif; ?>
        </div>
        <?php else: ?>

        <!-- EXACT same card markup as product_home.php -->
        <div class="row">
            <?php foreach ($products as $p):
                $imgs    = explode(',', $p['image'] ?? '');
                $img1    = trim($imgs[0] ?? '');
                $imgUrl  = $img1 ? $url_domain . '/admin/images/product/' . $img1 : $url_domain . '/images/waiting images.jpeg';
                $orig    = (float)($p['price'] ?? 0);
                $sale_p  = (float)($p['price_after_discount'] ?: $p['price']);
                $dis_amt = (float)($p['dis_amount'] ?? 0);
                $hasDisc = ($dis_amt > 0 && $sale_p < $orig);
                $discPct = $hasDisc ? round(($dis_amt / $orig) * 100) : 0;
                $pLink   = $url_domain . '/product/' . $p['slug'];
                $isSale  = ($p['sale'] === 'In Sale');
                $isOut   = (strtolower(trim($p['stock'] ?? '')) === 'out stock');
            ?>
            <a href="<?= $pLink ?>" class="col-md-3 col-6 mb-3 text-decoration-none">
                <div class="card border-0 border-radius text-center">
                    <div class="image-container position-relative">
                        <img src="<?= $imgUrl ?>"
                             alt="<?= htmlspecialchars($p['name']) ?>"
                             class="brand-product-img">
                        <?php if ($isSale): ?><div class="sale-badge">In Sale</div><?php endif; ?>
                        <?php if ($isOut):  ?><div class="out-of-stock-badge">SOLD OUT</div><?php endif; ?>
                    </div>
                    <p class="mt-2 mb-0"><?= htmlspecialchars($p['name']) ?></p>
                    <div class="price-container">
                        <?php if ($hasDisc): ?>
                            <h5 class="original-price">Rs. <?= number_format($orig, 2) ?></h5>
                            <h5 class="discounted-price">Rs. <?= number_format($sale_p, 2) ?></h5>
                            <span class="discount-badge"><?= $discPct ?>% OFF</span>
                        <?php else: ?>
                            <h5 class="regular-price">Rs. <?= number_format($sale_p, 2) ?></h5>
                        <?php endif; ?>
                    </div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>

        <?php endif; ?>
    </div>

    <div style="padding-bottom:50px;"></div>
</div>

<?php include 'include/footer.php'; ?>
