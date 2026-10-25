<?php
include 'include/head.php';

// Redirect if not logged in
if (!isset($_SESSION['WEBSITE-LOGINSS'])) {
    header('Location: ' . $url_domain . '/login?redirect=wishlist');
    exit;
}

$userId = intval($_SESSION['WEBSITE-LOGINSS']);

// Auto-create table if needed
$connection_database->query("CREATE TABLE IF NOT EXISTS wishlist (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT NOT NULL,
    product_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_wl (user_id, product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Fetch wishlist products
$sql = "SELECT p.id, p.name, p.slug, p.image, p.price, p.price_after_discount, p.sale, p.stock
        FROM products p
        INNER JOIN wishlist w ON p.id = w.product_id
        WHERE w.user_id = $userId
        ORDER BY w.created_at DESC";
$result    = $connection_database->query($sql);
$items     = [];
$itemCount = 0;
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }
    $itemCount = count($items);
}

include 'include/navbar.php';
?>

<style>
/* ── Wishlist page ─────────────────────────────────── */
.wl-hero {
    background: linear-gradient(135deg,#1a0533 0%,#6d28d9 60%,#be185d 100%);
    padding: 40px 0 32px;
    text-align: center;
    color: #fff;
}
.wl-hero h1 { font-size: clamp(1.4rem,3vw,2rem); font-weight:800; margin:0 0 6px; }
.wl-hero p  { font-size:13px; opacity:.75; margin:0; }
.wl-badge   {
    display:inline-flex; align-items:center; gap:6px;
    background:rgba(255,255,255,.15); border:1px solid rgba(255,255,255,.3);
    border-radius:50px; padding:5px 16px; font-size:12px; font-weight:700;
    margin-bottom:12px;
}

.wl-section { padding:40px 0 60px; background:#f8f8f8; min-height:50vh; }

/* Product card heart overlay */
.wl-remove-btn {
    position:absolute; top:8px; right:8px; z-index:20;
    width:34px; height:34px; border-radius:50%;
    background:rgba(255,255,255,.92); border:none; cursor:pointer;
    display:flex; align-items:center; justify-content:center;
    box-shadow:0 2px 8px rgba(0,0,0,.15); transition:all .22s;
}
.wl-remove-btn:hover { background:#e53935; }
.wl-remove-btn:hover .fa { color:#fff; }
.wl-remove-btn .fa { color:#e53935; font-size:14px; transition:color .22s; }

/* Empty state */
.wl-empty {
    text-align:center; padding:70px 20px;
}
.wl-empty-icon { font-size:64px; margin-bottom:16px; opacity:.25; display:block; }
.wl-empty h3   { font-size:1.3rem; font-weight:700; color:#374151; margin-bottom:8px; }
.wl-empty p    { color:#9ca3af; font-size:14px; margin-bottom:24px; }
.wl-empty .btn-shop {
    background:linear-gradient(135deg,#7c3aed,#be185d);
    color:#fff; border:none; padding:12px 32px;
    border-radius:50px; font-size:14px; font-weight:700;
    text-decoration:none; display:inline-block;
}

/* Item removing animation */
.item.wl-removing {
    opacity:0; transform:scale(.92);
    transition:opacity .35s, transform .35s;
    pointer-events:none;
}

.wl-count-bar {
    display:flex; align-items:center; justify-content:space-between;
    margin-bottom:20px; padding:0 2px;
}
.wl-count-bar h5  { font-size:15px; font-weight:800; color:#1f2937; margin:0; }
.wl-count-badge   { background:#7c3aed; color:#fff; border-radius:50px; padding:3px 12px; font-size:11px; font-weight:700; }

.price { font-size:1.1rem; font-weight:700; }
.old-price { text-decoration:line-through; color:#999; font-size:.9rem; margin-right:4px; }

/* Standard product card pieces needed */
.product-image { position:relative; overflow:hidden; margin:0 auto 15px; }
.product-image .primary { display:block; width:100%; }
.product-image .hover   { display:none; position:absolute; top:0; left:0; width:100%; }
.product-image:hover .hover   { display:block; }
.product-image:hover .primary { opacity:0; }
.product-labels { position:absolute; top:10px; right:10px; z-index:11; }
.lbl { display:inline-block; padding:3px 8px; font-size:11px; font-weight:700; border-radius:3px; }
.pr-label1 { background:#e53935; color:#fff; }
.sold-out-label {
    position:absolute; top:50%; left:50%;
    transform:translate(-50%,-50%);
    background:rgba(0,0,0,.7); color:#fff;
    padding:8px 16px; font-weight:700;
    text-transform:uppercase; z-index:10;
    font-size:14px; border-radius:4px;
}
.product-details .product-name a { font-size:13px; color:#333; text-decoration:none; }
.product-details .product-name a:hover { color:#7c3aed; }
.button-set { position:absolute; bottom:0; left:0; right:0; text-align:center; padding:8px; }
.quick-view { font-size:12px; padding:6px 14px; background:#fff; color:#333; border:1px solid #ddd; }
</style>

<!-- Hero -->
<section class="wl-hero">
  <div class="container">
    <div class="wl-badge">❤️ My Wishlist</div>
    <h1>Saved Items</h1>
    <p>Your favourite products, all in one place</p>
  </div>
</section>

<!-- Wishlist Content -->
<section class="wl-section">
  <div class="container">

    <?php if ($itemCount === 0): ?>
    <!-- Empty State -->
    <div class="wl-empty">
      <span class="wl-empty-icon">🤍</span>
      <h3>Your wishlist is empty</h3>
      <p>Browse our collection and tap the ❤️ heart on any product to save it here</p>
      <a href="<?= $url_domain ?>/products" class="btn-shop">Shop Now</a>
    </div>

    <?php else: ?>
    <!-- Count bar -->
    <div class="wl-count-bar">
      <h5>Saved Products</h5>
      <span class="wl-count-badge" id="wlPageCount"><?= $itemCount ?> items</span>
    </div>

    <div class="row" id="wl-grid">
      <?php foreach ($items as $p):
          $images  = array_map('trim', explode(',', $p['image']));
          $img1    = $url_domain . '/admin/images/product/' . $images[0];
          $img2    = isset($images[1]) && $images[1] ? $url_domain . '/admin/images/product/' . $images[1] : $img1;
          $slug    = $p['slug'];
          $pid     = (int)$p['id'];
          $price   = (float)$p['price'];
          $final   = (float)$p['price_after_discount'];
          $hasDisc = $final > 0 && $final < $price;
          $wait    = $url_domain . '/images/waiting images.jpeg';
      ?>
      <div class="col-6 col-sm-6 col-md-3 col-lg-3 item" id="wl-item-<?= $pid ?>" data-pid="<?= $pid ?>">
        <div class="product-image" style="position:relative;">
          <a href="<?= $url_domain ?>/product/<?= htmlspecialchars($slug) ?>">
            <img class="primary" src="<?= $img1 ?>" alt="<?= htmlspecialchars($p['name']) ?>" title="product"
                 onerror="this.src='<?= $wait ?>'">
            <img class="hover"   src="<?= $img2 ?>" alt="<?= htmlspecialchars($p['name']) ?>" title="product"
                 onerror="this.src='<?= $wait ?>'">
          </a>
          <?php if (!empty($p['sale'])): ?>
            <div class="product-labels rectangular"><span class="lbl pr-label1"><?= $p['sale'] ?></span></div>
          <?php endif; ?>
          <?php if ($p['stock'] === 'Out Stock'): ?>
            <div class="sold-out-label">SOLD OUT</div>
          <?php endif; ?>
          <!-- Remove button -->
          <button class="wl-remove-btn" data-pid="<?= $pid ?>" onclick="removeFromWishlist(<?= $pid ?>, this)" title="Remove from wishlist">
            <i class="fa fa-heart"></i>
          </button>
          <div class="button-set d-none d-md-block">
            <div class="quickview-btn">
              <a href="<?= $url_domain ?>/product/<?= htmlspecialchars($slug) ?>" class="btn quick-view rounded">
                <span>View Product</span>
              </a>
            </div>
          </div>
        </div>
        <div class="product-details text-center">
          <div class="product-name">
            <a href="<?= $url_domain ?>/product/<?= htmlspecialchars($slug) ?>"><?= htmlspecialchars($p['name']) ?></a>
          </div>
          <div class="product-price">
            <?php if ($hasDisc): ?>
              <span class="old-price">RS. <?= number_format($price, 2) ?></span>
              <span class="price">RS. <?= number_format($final, 2) ?></span>
            <?php else: ?>
              <span class="price">RS. <?= number_format($price, 2) ?></span>
            <?php endif; ?>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <?php endif; ?>

  </div>
</section>

<?php include 'include/footer.php'; ?>

<script>
const WL_AJAX = '<?= $url_domain ?>/wishlist_toggle.php';

function removeFromWishlist(pid, btn) {
  const card = document.getElementById('wl-item-' + pid);
  if (!card) return;

  const fd = new FormData();
  fd.append('action', 'toggle');
  fd.append('product_id', pid);

  fetch(WL_AJAX, { method: 'POST', body: fd })
    .then(r => r.json())
    .then(d => {
      if (d.login) { location.href = '<?= $url_domain ?>/login?redirect=wishlist'; return; }
      // Animate removal
      card.classList.add('wl-removing');
      setTimeout(() => {
        card.remove();
        // Update count
        const remaining = document.querySelectorAll('#wl-grid .item').length;
        const badge = document.getElementById('wlPageCount');
        if (badge) badge.textContent = remaining + ' item' + (remaining !== 1 ? 's' : '');
        // Update navbar badge
        const nb = document.getElementById('WishlistCount');
        if (nb) {
          if (d.count > 0) { nb.textContent = '(' + d.count + ')'; nb.style.display = ''; }
          else             { nb.style.display = 'none'; }
        }
        // Show empty state if grid is empty
        if (remaining === 0) {
          document.querySelector('.wl-count-bar').style.display = 'none';
          const grid = document.getElementById('wl-grid');
          if (grid) grid.outerHTML = `
            <div class="wl-empty">
              <span class="wl-empty-icon">🤍</span>
              <h3>Your wishlist is empty</h3>
              <p>Browse our collection and tap the ❤️ heart on any product to save it here</p>
              <a href="<?= $url_domain ?>/products" class="btn-shop">Shop Now</a>
            </div>`;
        }
      }, 370);
    })
    .catch(() => {});
}
</script>
