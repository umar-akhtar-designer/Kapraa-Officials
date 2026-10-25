<?php
// Fetch active announcements for ticker
$_announcements = [];
$_annRes = $connection_database->query(
    "SELECT message, link FROM announcement_bar WHERE is_active=1 ORDER BY sort_order ASC, id ASC"
);
if ($_annRes) {
    while ($r = $_annRes->fetch_assoc()) $_announcements[] = $r;
}
// Fallback if none active
if (empty($_announcements)) {
    $_announcements[] = ['message' => 'Welcome to Kapraa — Pakistan\'s Premium Clothing Store', 'link' => ''];
}
?>
<style>
/* ── Header layout — works on mobile, tablet, desktop ── */
/* ── Sticky header ── */
.header-section {
    position: sticky;
    top: 0;
    z-index: 9980;
    background: #fff;
    box-shadow: 0 2px 8px rgba(0,0,0,.08);
}
.th-header-row {
    display: flex;
    align-items: center;
    min-height: 50px;
    width: 100%;
}
/* Left & right equal fixed width so logo is always perfectly centered */
.th-hdr-left,
.th-hdr-right {
    flex: 0 0 auto;
    display: flex;
    align-items: center;
    gap: 10px;
    width: 120px;        /* same on both sides → logo truly centered */
}
.th-hdr-right { justify-content: flex-end; }
.th-hdr-logo {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
}
.th-hdr-logo img { display: block; height: 96px; width: auto; }

/* Icon button base style */
.th-hdr-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: transparent;
    border: none;
    padding: 4px;
    cursor: pointer;
    color: #222;
    text-decoration: none;
    font-size: 22px;
    line-height: 1;
}
.th-hdr-btn:hover { color: #c9436e; }
.th-hdr-btn .icon { font-size: 22px; line-height: 1; }

/* Cart count badge — overlaid top-right of icon */
.th-cart-wrap {
    position: relative;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}
.site-header__cart-count1 {
    position: absolute;
    top: 1px;
    right: -1px;
    font-size: 10px;
    font-weight: 700;
    color: #222;
    line-height: 1;
    background: none;
    padding: 0;
}

/* Tablet (md): wider sides, user icon now shows */
@media (min-width: 768px) {
    .th-hdr-left, .th-hdr-right { width: 160px; gap: 14px; }
}
/* Desktop (lg+): most space */
@media (min-width: 992px) {
    .th-hdr-left, .th-hdr-right { width: 220px; gap: 18px; }
}

/* ── Announcement Ticker ──────────────────────────────── */
.th-ticker-wrap {
    background: #1a1a1a;
    overflow: hidden;
    width: 100%;
    height: 36px;
    display: flex;
    align-items: center;
    position: relative;
    margin: 0;
    padding: 0;
    line-height: 1;
}
.th-ticker-track {
    display: flex;
    align-items: center;
    white-space: nowrap;
    will-change: transform;
    animation: thTicker linear infinite;
    animation-duration: var(--ticker-speed, 28s);
}
.th-ticker-track:hover { animation-play-state: paused; }
.th-ticker-item {
    display: inline-flex;
    align-items: center;
    font-size: 13px;
    font-weight: 500;
    color: #fff;
    padding: 0 48px;
    letter-spacing: 0.3px;
    white-space: nowrap;
    text-decoration: none;
}
.th-ticker-item a {
    color: #fff;
    text-decoration: none;
}
.th-ticker-sep {
    display: inline-block;
    width: 5px; height: 5px;
    border-radius: 50%;
    background: rgba(255,255,255,.35);
    flex-shrink: 0;
    margin: 0 4px;
}
@keyframes thTicker {
    0%   { transform: translateX(0); }
    100% { transform: translateX(-50%); }
}
/* Fade edges */
.th-ticker-wrap::before,
.th-ticker-wrap::after {
    content: '';
    position: absolute;
    top: 0; bottom: 0;
    width: 60px;
    z-index: 2;
    pointer-events: none;
}
.th-ticker-wrap::before { left:  0; background: linear-gradient(to right,  #1a1a1a, transparent); }
.th-ticker-wrap::after  { right: 0; background: linear-gradient(to left,   #1a1a1a, transparent); }

    /* Sidebar Styles */
    .sidebarsss {
        position: fixed;
        top: 0;
        left: -300px;
        width: 300px;
        height: 100%;
        background-color: white;
        color: black;
        transition: left 0.3s ease;
        z-index: 99990;
        box-shadow: 2px 0 10px rgba(0, 0, 0, 0.2);
        overflow-y: auto;
        display: flex;
        flex-direction: column;
    }

    .sidebarsss.active {
        left: 0;
    }

    .sidebarsss-header {
        padding: 15px;
        background-color: white;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .sidebarsss-title {
        font-size: 20px;
        font-weight: 600;
        color: black;
    }

    .close-btn {
        color: #000;
        font-size: 14px;
        cursor: pointer;
    }
    .sidebarsss-menu {
        flex: 1;
        overflow-y: auto;
    }
    .sidebarsss-menu ul {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .sidebarsss-menu li {
        padding: 0;
        position: relative;
    }

    .sidebarsss-menu a {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 12px 20px;
        color: black;
        text-decoration: none;
        transition: background-color 0.3s;
        font-size: 16px;
        border-bottom: 1px solid #eee;
    }

    .sidebarsss-menu a:hover {
        background-color: black;
        color: white
    }

    .sidebarsss-menu .submenu {
        display: none;
        background-color: #f9f9f9;
        color: black;
    }

    .sidebarsss-menu .submenu.active {
        display: block;
    }

    .sidebarsss-menu .submenu a {
        padding-left: 30px;
        font-size: 12px;
    }

    .sidebarsss-menu .submenu .submenu a {
        padding-left: 50px;
    }

    .sidebarsss-menu .has-submenu > a .icon {
        transition: transform 0.3s ease;
        font-size: 15px;
        display: inline-block;
        width: 16px;
        height: 16px;
        position: relative;
    }

    .sidebarsss-menu .has-submenu > a .icon:before {
        content: '⌵';
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        font-weight: bold;
        transition: transform 0.3s ease;
    }

    .sidebarsss-menu .has-submenu.active > a .icon:before {
        content: '⌵';
        transform: translate(-50%, -50%) rotate(180deg);
    }

    /* Search Bar Styles */
    .search-container {
        padding: 10px 15px;
        border-bottom: 1px solid #eee;
    }

    .search-input {
        width: 100%;
        padding: 8px 12px;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 14px;
        outline: none;
        transition: border-color 0.3s;
    }

    .search-input:focus {
        border-color: #000;
    }

    .no-results {
        padding: 10px 20px;
        color: #666;
        font-style: italic;
        font-size: 14px;
    }

    /* Live Search Results */
    .live-search-results {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        background: white;
        border: 1px solid #ddd;
        border-top: none;
        max-height: 300px;
        overflow-y: auto;
        z-index: 1000;
        display: none;
    }

    .live-search-result-item {
        padding: 10px;
        border-bottom: 1px solid #eee;
        display: flex;
        align-items: center;
    }

    .live-search-result-item:last-child {
        border-bottom: none;
    }

    .live-search-result-item a {
        display: flex;
        align-items: center;
        text-decoration: none;
        color: #333;
        width: 100%;
    }

    .live-search-result-item img {
        width: 50px;
        height: 50px;
        object-fit: cover;
        margin-right: 15px;
        border-radius: 4px;
    }

    .live-search-result-item .product-name {
        font-weight: 500;
    }

    /* Overlay */
    .overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        z-index: 99985;
        display: none;
    }

    .overlay.active {
        display: block;
    }

    /* Main Content */
    .main-content {
        flex: 1;
        padding: 30px;
        max-width: 1200px;
        margin: 0 auto;
    }

    .content-card {
        background-color: #fff;
        border-radius: 8px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        padding: 30px;
        margin-bottom: 30px;
    }

    /* Menu Button */
    .menu-button {
        background: none;
        border: none;
        cursor: pointer;
        padding: 10px;
    }

    /* Social Media in Sidebar */
    .sidebar-social {
        padding: 20px;
        border-top: 1px solid #eee;
        background-color: white;
    }

    .sidebar-social .social-icons {
        display: flex;
        justify-content: center;
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .sidebar-social .social-icons li {
        margin: 0 15px;
    }

    .sidebar-social .social-icons a {
        color: black;
        font-size: 18px;
        transition: color 0.3s;
    }

    .sidebar-social .social-icons a:hover {
        color: #4267B2; /* Facebook blue */
    }

    .sidebar-social .social-icons li:nth-child(2) a:hover {
        color: #E1306C; /* Instagram pink */
    }

    .sidebar-social .social-icons li:nth-child(3) a:hover {
        color: #FF0000; /* YouTube red */
    }

    /* Responsive */
    @media (max-width: 768px) {
        .sidebarsss {
            width: 250px;
        }
        
        .main-content {
            padding: 20px;
        }
    }
    .ttt{
        font-size: 11px !important;
    }

    /* Mobile nav wrapper must sit above the sticky header */
    .mobile-nav-wrapper {
        z-index: 99990 !important;
    }
    
    #minicart-drawer.modal {
    z-index: 999999 !important;
}
.modal-backdrop {
    z-index: 999990 !important;
}
</style>
<?php
    // Fetch categories with their subcategories
    $categories = [];
    $categoryQuery = "SELECT * FROM category ORDER BY priority ASC";
    $categoryResult = $connection_database->query($categoryQuery);

    if ($categoryResult && $categoryResult->num_rows > 0) {
        while($category = $categoryResult->fetch_assoc()) {
            $categoryId = $category['id'];
            $category['subcategories'] = [];
            
            // Fetch subcategories for this category
            $subcategoryQuery = "SELECT * FROM subcategory WHERE main_category = $categoryId ORDER BY priority ASC";
            $subcategoryResult = $connection_database->query($subcategoryQuery);
            
            if ($subcategoryResult && $subcategoryResult->num_rows > 0) {
                while($subcategory = $subcategoryResult->fetch_assoc()) {
                    $category['subcategories'][] = $subcategory;
                }
            }
            
            $categories[] = $category;
        }
    }

    // Fetch brands
    $brands = [];
    $brandQuery = "SELECT * FROM brand ORDER BY name ASC";
    $brandResult = $connection_database->query($brandQuery);

    if ($brandResult && $brandResult->num_rows > 0) {
        while($brand = $brandResult->fetch_assoc()) {
            $brands[] = $brand;
        }
    }
    
    // Check if user is logged in
    $isLoggedIn = isset($_SESSION['WEBSITE-LOGINSS']);
?>

<!-- Search Form Drawer -->
<div class="search">
    <div class="search__form">
        <!-- Change this form action -->
        <form class="search-bar__form" action="<?php echo $url_domain; ?>/product/" method="get">
            <button class="go-btn search__button" type="submit"><i class="icon an an-search"></i></button>
            <input class="search__input" type="search" name="search_query" value="" placeholder="Search Entire Store..." aria-label="Search" autocomplete="off" />
        </form>
        <div class="live-search-results"></div>
        <!-- Camera + Close in a flex row, perfectly aligned -->
        <div style="position:absolute;right:15px;top:0;height:100%;display:flex;align-items:center;gap:18px;z-index:6;">
            <button type="button" id="vs-trigger-btn" title="Search by Image (AI)" style="background:none;border:none;cursor:pointer;padding:0;color:#1a1a2e;font-size:22px;line-height:1;display:flex;align-items:center;">
                <i class="fa fa-camera"></i>
            </button>
            <button type="button" class="search-trigger" style="background:none;border:none;cursor:pointer;padding:0;font-size:25px;line-height:1;display:flex;align-items:center;color:#333;"><i class="icon an an-times"></i></button>
        </div>

<!-- ── AI Visual Search Modal ── -->
<div id="vs-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.7);z-index:99999;align-items:center;justify-content:center;padding:20px 0;">
  <div style="background:#fff;border-radius:16px;padding:32px 28px 28px;max-width:500px;width:90%;max-height:86vh;overflow-y:auto;position:relative;margin:auto;">
    <button onclick="vsClose()" style="position:absolute;top:12px;right:16px;background:none;border:none;font-size:22px;cursor:pointer;color:#666;">&times;</button>
    <h4 style="margin:0 0 6px;color:#1a1a2e;font-weight:800;"><i class="fa fa-camera" style="color:#c8a96e;margin-right:8px;"></i>AI Visual Search</h4>
    <p style="margin:0 0 16px;color:#666;font-size:13px;">Upload a photo of any dress or outfit — AI will find similar products in our store.</p>

    <div id="vs-drop-zone" onclick="document.getElementById('vs-file').click()"
      style="border:2px dashed #c8a96e;border-radius:10px;padding:36px 32px;text-align:center;cursor:pointer;background:#fffdf7;transition:background 0.2s;margin-top:4px;">
      <i class="fa fa-cloud-upload" style="font-size:36px;color:#c8a96e;"></i>
      <p style="margin:10px 0 0;color:#888;font-size:13px;">Click or drag & drop an image here</p>
    </div>
    <input type="file" id="vs-file" accept="image/*" style="display:none;" onchange="vsHandleFile(this.files[0])">

    <div id="vs-preview" style="display:none;margin-top:14px;text-align:center;">
      <img id="vs-img" style="max-height:180px;border-radius:8px;max-width:100%;">
    </div>

    <div id="vs-status" style="display:none;margin-top:14px;text-align:center;color:#1a1a2e;font-weight:600;">
      <i class="fa fa-spinner fa-spin"></i> Analyzing image with AI...
    </div>

    <div id="vs-analysis" style="display:none;margin-top:12px;padding:10px 14px;background:#f0f4ff;border-radius:8px;font-size:12px;color:#444;"></div>

    <div id="vs-results" style="display:none;margin-top:16px;">
      <h6 style="margin:0 0 10px;font-weight:700;color:#1a1a2e;">Similar Products Found</h6>
      <div id="vs-grid" style="display:grid;grid-template-columns:repeat(2,1fr);gap:10px;"></div>
      <a id="vs-view-all" href="#" style="display:block;margin-top:12px;text-align:center;background:#1a1a2e;color:#fff;padding:10px;border-radius:8px;text-decoration:none;font-weight:700;font-size:13px;">View All Results</a>
    </div>

    <div id="vs-no-results" style="display:none;text-align:center;margin-top:16px;color:#888;font-size:13px;">
      <i class="fa fa-search" style="font-size:24px;display:block;margin-bottom:8px;"></i>
      No matching products found. Try a different image.
    </div>
  </div>
</div>

<script>
function vsOpen(){document.getElementById('vs-modal').style.display='flex';}
function vsClose(){
  document.getElementById('vs-modal').style.display='none';
  ['vs-preview','vs-status','vs-analysis','vs-results','vs-no-results'].forEach(function(id){document.getElementById(id).style.display='none';});
  document.getElementById('vs-file').value='';
  document.getElementById('vs-grid').innerHTML='';
  var h=document.querySelector('#vs-results h6'); if(h){h.style.display='block';h.textContent='Similar Products Found';}
  var b=document.getElementById('vs-view-all'); if(b){b.textContent='View All Results';b.style.display='block';}
}
document.getElementById('vs-trigger-btn').addEventListener('click', vsOpen);

// Drag and drop
var dz = document.getElementById('vs-drop-zone');
dz.addEventListener('dragover', function(e){e.preventDefault(); dz.style.background='#fff8e8';});
dz.addEventListener('dragleave', function(){dz.style.background='#fffdf7';});
dz.addEventListener('drop', function(e){e.preventDefault(); dz.style.background='#fffdf7'; vsHandleFile(e.dataTransfer.files[0]);});

function vsHandleFile(file){
  if(!file||!file.type.startsWith('image/')) return;
  var reader = new FileReader();
  reader.onload = function(e){
    document.getElementById('vs-img').src = e.target.result;
    document.getElementById('vs-preview').style.display='block';
  };
  reader.readAsDataURL(file);
  vsAnalyze(file);
}

function vsAnalyze(file){
  ['vs-results','vs-no-results','vs-analysis'].forEach(function(id){document.getElementById(id).style.display='none';});
  document.getElementById('vs-status').style.display='block';

  var fd = new FormData();
  fd.append('image', file);

  fetch('<?php echo $url_domain; ?>/api/visual_search.php', {method:'POST', body: fd})
    .then(function(r){return r.json();})
    .then(function(data){
      document.getElementById('vs-status').style.display='none';

      // ── NOT A CLOTHING IMAGE ──────────────────────────────────
      if(data.not_clothing){
        document.getElementById('vs-analysis').innerHTML =
          '<span style="background:#dc2626;color:#fff;padding:6px 14px;border-radius:12px;font-size:12px;font-weight:700;">⚠️ Not a clothing image</span>';
        document.getElementById('vs-analysis').style.display='block';
        document.getElementById('vs-no-results').innerHTML =
          '<p style="margin:8px 0 0;font-size:13px;color:#888;text-align:center;">Please upload a photo of a dress, kurta, suit or any clothing item to search our store.</p>';
        document.getElementById('vs-no-results').style.display='block';
        return;
      }

      // ── EXACT MATCH ───────────────────────────────────────────
      if(data.success && data.exact_match){
        document.getElementById('vs-analysis').innerHTML =
          '<span style="background:#1a1a2e;color:#c8a96e;padding:4px 12px;border-radius:12px;font-size:12px;font-weight:700;">✓ Exact Product Found!</span>';
        document.getElementById('vs-analysis').style.display='block';
      }
      // ── AI TAGS (not exact) ───────────────────────────────────
      else if(data.success && data.analysis){
        var a = data.analysis;
        var tags = [a.type, a.fabric, a.style, a.pattern].filter(Boolean).concat(a.colors||[]);
        var fallbackNote = (a._fallback && a._fallback_reason)
          ? '<br><small style="color:#f0932b;font-size:11px;">'+a._fallback_reason+'</small>' : '';
        document.getElementById('vs-analysis').innerHTML =
          '<b>AI Detected:</b> ' + tags.map(function(t){
            return '<span style="background:#1a1a2e;color:#fff;padding:2px 8px;border-radius:12px;margin:2px;display:inline-block;font-size:11px;">'+t+'</span>';
          }).join('') + fallbackNote;
        document.getElementById('vs-analysis').style.display='block';
      }

      // ── SHOW PRODUCTS ─────────────────────────────────────────
      if(data.success && data.products && data.products.length > 0){
        var grid    = document.getElementById('vs-grid');
        var heading = document.querySelector('#vs-results h6');
        var viewBtn = document.getElementById('vs-view-all');

        if(data.exact_match){
          if(heading) heading.style.display='none';
          grid.innerHTML = '';
          viewBtn.textContent = 'View Product';
          viewBtn.href = '<?php echo $url_domain; ?>/product/'+data.products[0].slug;
          viewBtn.style.display = 'block';
        } else {
          if(heading){ heading.textContent='Similar Products Found'; heading.style.display='block'; }
          grid.innerHTML = data.products.slice(0,4).map(function(p){
            var imgSrc = p.image_url || '';
            return '<a href="<?php echo $url_domain; ?>/product/'+p.slug
              +'" style="text-decoration:none;border:1px solid #eee;border-radius:8px;overflow:hidden;display:block;">'
              +'<img src="'+imgSrc+'" onerror="this.src=\'<?php echo $url_domain; ?>/assets/images/product-placeholder.jpg\'" '
              +'style="width:100%;height:120px;object-fit:cover;background:#f5f5f5;">'
              +'<div style="padding:6px 8px;">'
              +'<p style="margin:0;font-size:11px;font-weight:700;color:#1a1a2e;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">'+p.name+'</p>'
              +'<p style="margin:2px 0 0;font-size:12px;color:#c8a96e;font-weight:800;">Rs '+Number(p.final_price).toLocaleString()+'</p>'
              +'</div></a>';
          }).join('');
          viewBtn.textContent = 'View All Results';
          viewBtn.href = '<?php echo $url_domain; ?>/product/?vs_search=1&keywords='
            +encodeURIComponent(((data.analysis&&data.analysis.keywords)||[]).join(','));
          viewBtn.style.display = 'block';
        }
        document.getElementById('vs-results').style.display='block';
      } else {
        document.getElementById('vs-no-results').innerHTML =
          '<p style="margin:0;font-size:13px;color:#888;text-align:center;">No matching products found in our store for this image.</p>';
        document.getElementById('vs-no-results').style.display='block';
      }
    })
    .catch(function(){
      document.getElementById('vs-status').style.display='none';
      document.getElementById('vs-no-results').style.display='block';
    });
}
</script>
    </div>
</div>
<!-- End Search Form Drawer -->

<!-- ── Announcement Ticker Bar ── -->
<div class="th-ticker-wrap" id="thTickerWrap">
    <div class="th-ticker-track" id="thTickerTrack">
        <?php
        // Render items twice for seamless infinite loop
        for ($__i = 0; $__i < 2; $__i++):
            foreach ($_announcements as $__a):
                $__msg  = htmlspecialchars($__a['message']);
                $__link = trim($__a['link'] ?? '');
        ?>
        <span class="th-ticker-item"><?php if ($__link): ?><a href="<?= htmlspecialchars($__link) ?>"><?= $__msg ?></a><?php else: echo $__msg; endif; ?></span><span class="th-ticker-sep"></span>
        <?php endforeach; endfor; ?>
    </div>
</div>
<script>
(function(){
    var track = document.getElementById('thTickerTrack');
    if (!track) return;
    // Set speed based on total content width — ~80px/s
    function setSpeed() {
        var half = track.scrollWidth / 2;
        var secs = Math.max(15, half / 80);
        track.style.setProperty('--ticker-speed', secs + 's');
        track.parentElement.style.setProperty('--ticker-speed', secs + 's');
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', setSpeed);
    } else {
        setSpeed();
    }
})();
</script>

<!-- Main Header -->
<div class="header-section clearfix animated hdr-sticky">
    <!-- Desktop Header -->
    <div class="header-6 classic-style">

        <!-- Header -->
        <div class="header-wrap d-flex border-bottom">
            <div class="container-fluid px-3">
                <div class="th-header-row">

                    <!-- ── LEFT: ONE hamburger only ── -->
                    <div class="th-hdr-left">
                        <!-- Mobile + Tablet (<lg): opens mobile slide-nav -->
                        <button type="button"
                            class="btn--link site-header__menu js-mobile-nav-toggle mobile-nav--open th-hdr-btn d-lg-none"
                            title="Menu">
                            <i class="icon an an-bars"></i>
                        </button>
                        <!-- Desktop only (lg+): opens sidebarsss panel -->
                        <button type="button" id="menu-button"
                            class="th-hdr-btn d-none d-lg-flex"
                            title="Menu">
                            <i class="icon an an-bars"></i>
                        </button>
                    </div>

                    <!-- ── CENTER: Logo always centered ── -->
                    <div class="th-hdr-logo logo">
                        <a href="<?php echo $url_domain;?>/index">
                            <img src="<?php echo $url_domain;?>/assets/images/Kapraa-logo.png" alt="Kapraa" height="96px" style="max-height:96px;width:auto;">
                        </a>
                    </div>

                    <!-- ── RIGHT: icons ── -->
                    <div class="th-hdr-right">
                        <!-- Search: always show -->
                        <button type="button" class="search-trigger th-hdr-btn" title="Search">
                            <i class="icon an an-search"></i>
                        </button>
                        <!-- Account / Login: HIDDEN on mobile, show md+ -->
                        <span class="d-none d-md-flex">
                            <?php if($isLoggedIn): ?>
                                <a href="<?php echo $url_domain;?>/account" class="th-hdr-btn" title="My Account">
                                    <i class="icon an an-user-alt"></i>
                                </a>
                            <?php else: ?>
                                <a href="<?php echo $url_domain;?>/login" class="th-hdr-btn" title="Login">
                                    <i class="icon an an-user-alt"></i>
                                </a>
                            <?php endif; ?>
                        </span>
                        <!-- Cart: always show -->
                        <a href="#" class="icon-cart site-header-cart btn-minicart th-hdr-btn th-cart-wrap"
                           data-bs-toggle="modal" data-bs-target="#minicart-drawer" title="Cart">
                            <i class="icon an an-shopping-bag"></i>
                            <span id="CartCount" class="site-header__cart-count1"><?php echo getCartItemCount(); ?></span>
                        </a>
                    </div>

                </div>
            </div>
        </div>
        <!-- End Header -->
    </div>
    <!-- End Desktop Header -->
</div>
<!-- End Main Header -->
<!-- Sidebar Desktop -->
<div class="sidebarsss" id="sidebarsss">
    <div class="sidebarsss-header">
        <div class="sidebarsss-title">Kapraa</div>
        <div class="close-btn" id="close-sidebar">
            <i class="icon an an-times-circle closemenu m-1 text-dark fa-2x"></i>
        </div>
    </div>
    <div class="sidebarsss-menu">
        <ul id="MobileNav" class="mobile-nav">
             <li class="lvl1 parent megamenu">
                <a href="<?php echo $url_domain;?>/index" class="site-nav">Home</a>
            </li>
            <!-- Categories Section -->
            <?php foreach($categories as $category): ?>
            <li class="lvl1 parent megamenu has-submenu">
                <a href="<?php echo $url_domain;?>/products/category/<?php echo $category['slug']; ?>" class="site-nav">
                    <?php echo $category['name']; ?>
                    <?php if(!empty($category['subcategories'])): ?>
                    <span class="icon"></span>
                    <?php endif; ?>
                </a>
                <?php if(!empty($category['subcategories'])): ?>
                <ul class="submenu">
                    <!-- Search bar for subcategories -->
                    <li class="search-container">
                        <input type="text" class="search-input" placeholder="Search..." data-search-target="subcategories-<?php echo $category['id']; ?>">
                    </li>
                    <div class="submenu-items" id="subcategories-<?php echo $category['id']; ?>">
                        <?php foreach($category['subcategories'] as $subcategory): ?>
                        <li><a href="<?php echo $url_domain;?>/products/subcategory/<?php echo $subcategory['slug']; ?>" class="site-nav"><?php echo $subcategory['name']; ?></a></li>
                        <?php endforeach; ?>
                    </div>
                </ul>
                <?php endif; ?>
            </li>
            <?php endforeach; ?>

            <!-- Brands Section -->
            <li class="lvl1 parent megamenu has-submenu">
                <a href="#" class="site-nav">Brands <span class="icon"></span></a>
                <ul class="submenu">
                    <!-- Search bar for brands -->
                    <li class="search-container">
                        <input type="text" class="search-input" placeholder="Search brands..." data-search-target="brands">
                    </li>
                    <div class="submenu-items" id="brands">
                        <?php foreach($brands as $brand): ?>
                        <li><a href="<?php echo $url_domain;?>/brand/<?php echo $brand['slug']; ?>" class="site-nav"><?php echo $brand['name']; ?></a></li>
                        <?php endforeach; ?>
                    </div>
                </ul>
            </li>
            
            <!-- AI Recommender -->
            <li class="lvl1 parent megamenu">
                <a href="<?php echo $url_domain;?>/recommend" class="site-nav" style="color:#7c3aed;font-weight:700;">✨ AI Stylist</a>
            </li>
            <!-- Static Pages (Keep your existing pages) -->
            <li class="lvl1 parent megamenu">
                <a href="<?php echo $url_domain;?>/contact" class="site-nav">Contact</a>
            </li>
            <li class="lvl1 parent megamenu">
                <?php if($isLoggedIn): ?>
                    <a href="<?php echo $url_domain;?>/my-orders" class="site-nav">My Orders</a>
                <?php else: ?>
                    <a href="<?php echo $url_domain;?>/login" class="site-nav">Login</a>
                <?php endif; ?>
            </li>
        </ul>
    </div>
    
    <!-- Social Media Icons at Bottom -->
    <div class="sidebar-social">
        <ul class="social-icons">
            <li><a href="https://www.facebook.com/kapraaofficial" target="_blank" data-bs-toggle="tooltip" data-bs-placement="top" title="Facebook"><i class="icon an an-facebook"></i></a></li>
            <li><a href="https://www.instagram.com/kapraaofficial" target="_blank" data-bs-toggle="tooltip" data-bs-placement="top" title="Instagram"><i class="icon an an-instagram"></i></a></li>
            <li>
                <a href="https://www.tiktok.com/@kapraaofficial" target="_blank" data-bs-toggle="tooltip" data-bs-placement="top" title="Tik Tok">
                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z"/>
                    </svg> 
                </a>
            </li>
        </ul>
    </div>
</div>
<div class="overlay" id="overlay"></div>
<!-- Mobile Menu -->
<div class="mobile-nav-wrapper dark-wrapper" role="navigation">
    <div class="sidebarsss-header closemobileMenu">
        <div class="sidebarsss-title">Kapraa</div>
        <div class="close-btn" id="close-mobile-sidebar">
            <i class="icon an an-times-circle closemenu m-1 text-dark fa-2x"></i>
        </div>
    </div>
    <ul id="MobileNav" class="mobile-nav">
        <li class="lvl1 parent megamenu">
            <a href="<?php echo $url_domain;?>/index" class="site-nav">Home</a>
        </li>
        <!-- Categories Section - Modified for mobile as well -->
        <?php foreach($categories as $category): ?>
        <li class="lvl1 parent megamenu has-submenu">
            <a href="<?php echo $url_domain;?>/products/category/<?php echo $category['slug']; ?>" class="site-nav">
                <?php echo $category['name']; ?>
                <?php if(!empty($category['subcategories'])): ?>
                <i class="an an-plus icon mobile-icon"></i>
                <?php endif; ?>
            </a>
            <?php if(!empty($category['subcategories'])): ?>
            <ul class="submenu mobile-submenu">
                <li class="search-container">
                    <input type="text" class="search-input" placeholder="Search..." data-search-target="mobile-subcategories-<?php echo $category['id']; ?>">
                </li>
                <div class="submenu-items" id="mobile-subcategories-<?php echo $category['id']; ?>">
                    <?php foreach($category['subcategories'] as $subcategory): ?>
                    <li><a href="<?php echo $url_domain;?>/products/subcategory/<?php echo $subcategory['slug']; ?>" class="site-nav ttt"><?php echo $subcategory['name']; ?></a></li>
                    <?php endforeach; ?>
                </div>
            </ul>
            <?php endif; ?>
        </li>
        <?php endforeach; ?>

        <!-- Brands Section -->
        <li class="lvl1 parent megamenu has-submenu">
            <a href="#" class="site-nav">Brands <i class="an an-plus icon mobile-icon"></i></a>
            <ul class="submenu mobile-submenu">
                <li class="search-container">
                    <input type="text" class="search-input" placeholder="Search brands..." data-search-target="mobile-brands">
                </li>
                <div class="submenu-items" id="mobile-brands">
                    <?php foreach($brands as $brand): ?>
                    <li><a href="<?php echo $url_domain;?>/brand/<?php echo $brand['slug']; ?>" class="site-nav ttt"><?php echo $brand['name']; ?></a></li>
                    <?php endforeach; ?>
                </div>
            </ul>
        </li>
        
        <!-- AI Recommender mobile -->
        <li class="lvl1 parent megamenu">
            <a href="<?php echo $url_domain;?>/recommend" class="site-nav" style="color:#7c3aed;font-weight:700;">✨ AI Stylist</a>
        </li>
        <!-- Static Pages (Keep your existing pages) -->
        <li class="lvl1 parent megamenu">
            <a href="<?php echo $url_domain;?>/contact" class="site-nav">Contact</a>
        </li>
        <li class="lvl1 parent megamenu">
            <?php if($isLoggedIn): ?>
                <a href="<?php echo $url_domain;?>/my-orders" class="site-nav">My Orders</a>
            <?php else: ?>
                <a href="<?php echo $url_domain;?>/login" class="site-nav">Login</a>
            <?php endif; ?>
        </li>
    </ul>
</div>
<!-- End Mobile Menu -->
<!-- Minicart Drawer -->
<div class="minicart-right-drawer right modal fade" id="minicart-drawer" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="minicart-header">
                <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close"><i class="an an-times" aria-hidden="true" data-bs-toggle="tooltip" data-bs-placement="left" title="Close"></i></button>
                <h4 class="modal-title" id="myModalLabel2">Shopping Cart <strong id="cart-item-count">(0)</strong> items</h4>
            </div>
            <div class="minicart-body">
                <div class="empty-cart">
                    <p>You have no items in your shopping cart.</p>
                </div>
            </div>
            <div class="minicart-footer minicart-action" style="display: none;">
                <!-- Will be populated by JavaScript -->
            </div>
        </div>
    </div>
</div>
<!-- End Minicart Drawer -->
<?php
  include 'include/mobile_sidebar.php';
?>