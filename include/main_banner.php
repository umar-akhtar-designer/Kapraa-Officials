<?php
// ── Fetch banners ────────────────────────────────────────────
$desktopBannersQuery = "SELECT b.image, br.slug, br.name AS brand_name
                        FROM banner b
                        LEFT JOIN brand br ON b.category = br.id
                        WHERE b.banner_type = 'Home Banner 1' AND b.banner_for = 'For Desktop'";
$desktopBannersResult = mysqli_query($connection_database, $desktopBannersQuery);

$mobileBannersQuery = "SELECT b.image, br.slug, br.name AS brand_name
                       FROM banner b
                       LEFT JOIN brand br ON b.category = br.id
                       WHERE b.banner_type = 'Home Banner 1' AND b.banner_for = 'For Mobile'";
$mobileBannersResult = mysqli_query($connection_database, $mobileBannersQuery);

// ── Fetch featured brands (with image) ──────────────────────
$brandsResult = $connection_database->query(
    "SELECT id, name, slug, image, image_position, discount_text FROM brand WHERE image IS NOT NULL AND image != '' ORDER BY name ASC LIMIT 12"
);
$featuredBrands = [];
if ($brandsResult) while ($b = $brandsResult->fetch_assoc()) {
    if (file_exists(__DIR__ . '/../admin/images/brand/' . $b['image'])) {
        $featuredBrands[] = $b;
    }
}
?>

<style>
/* ════════════════════════════════════════════════════════
   Hero Banner — fully responsive, no distortion
════════════════════════════════════════════════════════ */
.hero-section {
    position: relative;
    width: 100%;
    background: #111;
    overflow: hidden;
}

/* Slideshow container */
.hero-slides {
    position: relative;
    width: 100%;
    overflow: hidden;
}

/* Each slide */
.hero-slide {
    display: none;
    width: 100%;
}
.hero-slide.active { display: block; }

/* Banner image — scales with window, no distortion */
.hero-slide a { display: block; width: 100%; }
.hero-slide img {
    display: block;
    width: 100%;
    height: auto;          /* ← natural aspect-ratio, never stretches */
    max-height: 92vh;      /* never taller than viewport */
    object-fit: cover;
    object-position: top center;
}

/* Slideshow controls */
.hero-prev, .hero-next {
    position: absolute;
    top: 50%; transform: translateY(-50%);
    z-index: 10;
    background: rgba(0,0,0,.35);
    border: none;
    color: #fff;
    width: 40px; height: 40px;
    border-radius: 50%;
    cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    font-size: 18px;
    transition: background .2s;
}
.hero-prev:hover, .hero-next:hover { background: rgba(0,0,0,.65); }
.hero-prev { left: 16px; }
.hero-next { right: 16px; }

/* Slide dots */
.hero-dots {
    position: absolute;
    bottom: 14px; left: 50%; transform: translateX(-50%);
    display: flex; gap: 7px; z-index: 10;
}
.hero-dot {
    width: 8px; height: 8px; border-radius: 50%;
    background: rgba(255,255,255,.5);
    cursor: pointer; border: none;
    transition: background .2s, transform .2s;
}
.hero-dot.active {
    background: #fff;
    transform: scale(1.25);
}

/* ── Featured Brands bar — inside hero section ─────── */
.hero-brands {
    background: #fff;
    padding: 28px 0 20px;
    border-top: 1px solid #f0f0f0;
}
.hero-brands-inner {
    max-width: 1300px;
    margin: 0 auto;
    padding: 0 24px;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 18px;
    position: relative;
}
.hero-brands-title {
    font-size: 22px;
    font-weight: 700;
    letter-spacing: 0.5px;
    color: #222;
    margin: 0;
}
.hero-brands-slider-wrap {
    width: 100%;
    position: relative;
    padding-bottom: 62px;
}
.hero-brands-row {
    display: flex;
    gap: 28px;
    flex-wrap: nowrap;
    align-items: flex-start;
    justify-content: center;
    overflow-x: auto;
    scroll-behavior: smooth;
    -webkit-overflow-scrolling: touch;
    padding-bottom: 14px;
    scrollbar-width: none;
    -ms-overflow-style: none;
}
.hero-brands-row::-webkit-scrollbar { display: none; }

.hero-brand-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    text-decoration: none;
    cursor: pointer;
    flex-shrink: 0;
    perspective: 700px;
}

/* ── Flip circle ───────────────────────────────────── */
.hero-brand-circle {
    width: 155px; height: 155px;
    border-radius: 50%;
    border: 3px solid #222;
    background: #f5f5f5;
    transition: border-color .22s, box-shadow .22s;
    flex-shrink: 0;
    position: relative;
}
.hero-brand-item:hover .hero-brand-circle {
    border-color: #c9436e;
    box-shadow: 0 6px 20px rgba(201,67,110,.22);
}

/* The inner rotating element — ALL circles animate */
.circle-flip-inner {
    width: 100%; height: 100%;
    position: relative;
    transform-style: preserve-3d;
    border-radius: 50%;
    animation: brandCardFlip 9s ease-in-out infinite;
    animation-delay: var(--flip-delay, 0s);
}

/* Front face: photo */
.circle-front {
    position: absolute;
    inset: 0;
    backface-visibility: hidden;
    -webkit-backface-visibility: hidden;
    border-radius: 50%;
    overflow: hidden;
}
.circle-front img {
    width: 100%; height: 100%;
    object-fit: cover;
    display: block;
}

/* Back face: discount text */
.circle-back {
    position: absolute;
    inset: 0;
    backface-visibility: hidden;
    -webkit-backface-visibility: hidden;
    border-radius: 50%;
    overflow: hidden;
    transform: rotateY(180deg);
    background: #fff;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
    padding: 8px;
}
.disc-type {
    font-size: 11px;
    font-weight: 900;
    letter-spacing: 1.5px;
    color: #111;
    line-height: 1;
    text-transform: uppercase;
    margin-bottom: 2px;
}
.disc-value {
    font-size: 30px;
    font-weight: 900;
    color: #111;
    line-height: 1;
    font-family: 'Arial Black', 'Impact', Arial, sans-serif;
    letter-spacing: -1px;
}
.disc-off {
    font-size: 11px;
    font-weight: 900;
    letter-spacing: 1.5px;
    color: #111;
    line-height: 1;
    text-transform: uppercase;
    margin-top: 2px;
}
/* Back face — no discount: show brand name + shop now */
.disc-brandname {
    font-size: 13px;
    font-weight: 900;
    color: #111;
    line-height: 1.2;
    text-align: center;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding: 0 6px;
    word-break: break-word;
}
.disc-shopnow {
    font-size: 9px;
    font-weight: 900;
    letter-spacing: 1.5px;
    color: #111;
    text-transform: uppercase;
    margin-top: 6px;
    border-top: 1.5px solid #111;
    padding-top: 5px;
}

/* Flip animation: show front → flip to back → flip back to front */
@keyframes brandCardFlip {
    0%,  43% { transform: rotateY(0deg);   }   /* show photo */
    47%      { transform: rotateY(180deg); }   /* flip to discount */
    88%      { transform: rotateY(180deg); }   /* show discount */
    92%      { transform: rotateY(360deg); }   /* flip back to photo */
    100%     { transform: rotateY(360deg); }   /* hold photo */
}

.hero-brand-name {
    font-size: 14px;
    color: #444;
    font-weight: 600;
    text-align: center;
    max-width: 135px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.hero-brand-item:hover .hero-brand-name { color: #c9436e; }

/* Prev / Next arrows */
.hero-brands-nav {
    position: absolute;
    bottom: 10px;
    right: 0;
    display: flex;
    gap: 8px;
}
.hero-brands-btn {
    width: 38px; height: 38px;
    border-radius: 50%;
    border: 1px solid #ddd;
    background: #fff;
    color: #444;
    font-size: 16px;
    cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    transition: background .18s, border-color .18s, color .18s;
    box-shadow: 0 2px 8px rgba(0,0,0,.08);
    flex-shrink: 0;
}
.hero-brands-btn:hover { background: #c9436e; border-color: #c9436e; color: #fff; }
.hero-brands-btn:disabled { opacity: 0.35; cursor: default; background: #fff; border-color: #ddd; color: #aaa; }

@media (max-width: 768px) {
    .hero-brand-circle { width: 110px; height: 110px; }
    .disc-value        { font-size: 24px; }
    .hero-brand-name   { font-size: 12px; max-width: 112px; }
    .hero-brands-row   { gap: 18px; }
    .hero-brands-title { font-size: 18px; }
}
@media (max-width: 480px) {
    .hero-brand-circle { width: 88px; height: 88px; }
    .disc-value        { font-size: 20px; }
    .hero-brand-name   { font-size: 11px; max-width: 90px; }
    .hero-brands-row   { gap: 14px; }
}

/* ── Mobile-only / desktop-only ────────────────────── */
.hero-desktop { display: block; }
.hero-mobile  { display: none;  }
@media (max-width: 767px) {
    .hero-desktop { display: none; }
    .hero-mobile  { display: block; }
}
</style>

<!-- ════════════════════════════════════════════════════
     HERO SECTION
════════════════════════════════════════════════════ -->
<div class="hero-section">

    <!-- ── Desktop Slideshow ─────────────────────── -->
    <div class="hero-slides hero-desktop" id="heroDesktop">
        <?php
        $dCount = 0;
        if ($desktopBannersResult && mysqli_num_rows($desktopBannersResult) > 0):
            mysqli_data_seek($desktopBannersResult, 0);
            while ($banner = mysqli_fetch_assoc($desktopBannersResult)):
                $catSlug  = !empty($banner['slug']) ? $banner['slug'] : '';
                $catLink  = !empty($catSlug) ? $url_domain . '/brand/' . htmlspecialchars($catSlug) : '#';
        ?>
        <div class="hero-slide<?= $dCount === 0 ? ' active' : '' ?>">
            <div class="slideimg">
                <a href="<?= $catLink ?>">
                    <img src="<?= $url_domain ?>/admin/images/banner/<?= htmlspecialchars($banner['image']) ?>"
                         alt="<?= htmlspecialchars($banner['brand_name'] ?? 'Banner') ?>">
                </a>
            </div>
        </div>
        <?php $dCount++; endwhile; endif; ?>
    </div>

    <!-- ── Mobile Slideshow ──────────────────────── -->
    <div class="hero-slides hero-mobile" id="heroMobile">
        <?php
        $mCount = 0;
        if ($mobileBannersResult && mysqli_num_rows($mobileBannersResult) > 0):
            mysqli_data_seek($mobileBannersResult, 0);
            while ($banner = mysqli_fetch_assoc($mobileBannersResult)):
                $catSlug = !empty($banner['slug']) ? $banner['slug'] : '';
                $catLink = !empty($catSlug) ? $url_domain . '/brand/' . htmlspecialchars($catSlug) : '#';
        ?>
        <div class="hero-slide<?= $mCount === 0 ? ' active' : '' ?>">
            <div class="slideimg">
                <a href="<?= $catLink ?>">
                    <img src="<?= $url_domain ?>/admin/images/banner/<?= htmlspecialchars($banner['image']) ?>"
                         alt="<?= htmlspecialchars($banner['brand_name'] ?? 'Banner') ?>">
                </a>
            </div>
        </div>
        <?php $mCount++; endwhile; endif; ?>
    </div>

    <!-- ── Featured Brands  (INSIDE hero section) ── -->
    <?php if (!empty($featuredBrands)): ?>
    <div class="hero-brands">
        <div class="hero-brands-inner">
            <p class="hero-brands-title">Featured Brands</p>
            <div class="hero-brands-slider-wrap">
                <div class="hero-brands-row" id="heroBrandsRow">
                    <?php foreach ($featuredBrands as $fbIdx => $fb):
                        $fbImg      = $url_domain . '/admin/images/brand/' . $fb['image'];
                        $fbLink     = $url_domain . '/brand/' . $fb['slug'];
                        $fbPos      = !empty($fb['image_position']) ? $fb['image_position'] : 'center';
                        $fbDiscount = trim($fb['discount_text'] ?? '');
                        $hasDisc    = !empty($fbDiscount);

                        // Parse "UPTO 40% OFF" → type / value / off
                        if ($hasDisc) {
                            $discParts = array_pad(explode(' ', strtoupper($fbDiscount), 3), 3, '');
                            $discType  = $discParts[0];   // UPTO / FLAT
                            $discVal   = $discParts[1];   // 40%
                            $discOff   = $discParts[2] ?: 'OFF';
                        }

                        // Stagger each brand's flip by 1.1s
                        $flipDelay = round($fbIdx * 1.1, 1) . 's';
                    ?>
                    <a href="<?= $fbLink ?>" class="hero-brand-item">
                        <div class="hero-brand-circle">
                            <div class="circle-flip-inner" style="--flip-delay:<?= $flipDelay ?>">
                                <!-- Front: brand photo -->
                                <div class="circle-front">
                                    <img src="<?= $fbImg ?>"
                                         alt="<?= htmlspecialchars($fb['name']) ?>"
                                         style="object-position:<?= htmlspecialchars($fbPos) ?> center;"
                                         onerror="this.parentElement.parentElement.parentElement.style.background='#f0f0f0';this.style.display='none'">
                                </div>
                                <!-- Back: discount if set, otherwise brand name + SHOP NOW -->
                                <div class="circle-back">
                                    <?php if ($hasDisc): ?>
                                        <span class="disc-type"><?= htmlspecialchars($discType) ?></span>
                                        <span class="disc-value"><?= htmlspecialchars($discVal) ?></span>
                                        <span class="disc-off"><?= htmlspecialchars($discOff) ?></span>
                                    <?php else: ?>
                                        <span class="disc-brandname"><?= htmlspecialchars($fb['name']) ?></span>
                                        <span class="disc-shopnow">SHOP NOW</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <span class="hero-brand-name"><?= htmlspecialchars($fb['name']) ?></span>
                    </a>
                    <?php endforeach; ?>
                </div>
                <!-- Prev / Next arrows -->
                <div class="hero-brands-nav">
                    <button class="hero-brands-btn" id="heroBrandsPrev" aria-label="Previous">&#8249;</button>
                    <button class="hero-brands-btn" id="heroBrandsNext" aria-label="Next">&#8250;</button>
                </div>
            </div>
        </div>
    </div>
    <script>
    (function(){
        var row  = document.getElementById('heroBrandsRow');
        var prev = document.getElementById('heroBrandsPrev');
        var next = document.getElementById('heroBrandsNext');
        if (!row || !prev || !next) return;

        function scrollAmt() {
            var item = row.querySelector('.hero-brand-item');
            if (!item) return 200;
            var style = getComputedStyle(row);
            var gap = parseFloat(style.gap) || 28;
            return (item.offsetWidth + gap) * 3;
        }

        function updateBtns() {
            prev.disabled = row.scrollLeft <= 2;
            next.disabled = row.scrollLeft + row.offsetWidth >= row.scrollWidth - 2;
        }

        prev.addEventListener('click', function(){
            row.scrollBy({ left: -scrollAmt(), behavior: 'smooth' });
        });
        next.addEventListener('click', function(){
            row.scrollBy({ left: scrollAmt(), behavior: 'smooth' });
        });
        row.addEventListener('scroll', updateBtns, { passive: true });
        updateBtns();
    })();
    </script>
    <?php endif; ?>

</div><!-- /hero-section -->

<script>
(function() {
    function initHero(slidesEl) {
        if (!slidesEl) return;
        const slides = slidesEl.querySelectorAll('.hero-slide');
        if (slides.length === 0) return;

        // Build controls only if >1 slide
        if (slides.length > 1) {
            // Prev / Next buttons
            const prev = document.createElement('button');
            prev.className = 'hero-prev';
            prev.innerHTML = '&#8249;';
            prev.setAttribute('aria-label', 'Previous');

            const next = document.createElement('button');
            next.className = 'hero-next';
            next.innerHTML = '&#8250;';
            next.setAttribute('aria-label', 'Next');

            // Dots
            const dotsWrap = document.createElement('div');
            dotsWrap.className = 'hero-dots';
            slides.forEach(function(_, i) {
                const dot = document.createElement('button');
                dot.className = 'hero-dot' + (i === 0 ? ' active' : '');
                dot.setAttribute('aria-label', 'Slide ' + (i + 1));
                dotsWrap.appendChild(dot);
            });

            slidesEl.appendChild(prev);
            slidesEl.appendChild(next);
            slidesEl.appendChild(dotsWrap);

            const dots = dotsWrap.querySelectorAll('.hero-dot');
            let current = 0;
            let timer;

            function goTo(n) {
                slides[current].classList.remove('active');
                dots[current].classList.remove('active');
                current = (n + slides.length) % slides.length;
                slides[current].classList.add('active');
                dots[current].classList.add('active');
            }

            function startAuto() {
                timer = setInterval(function() { goTo(current + 1); }, 5000);
            }
            function stopAuto() { clearInterval(timer); }

            prev.addEventListener('click', function() { stopAuto(); goTo(current - 1); startAuto(); });
            next.addEventListener('click', function() { stopAuto(); goTo(current + 1); startAuto(); });
            dots.forEach(function(dot, i) {
                dot.addEventListener('click', function() { stopAuto(); goTo(i); startAuto(); });
            });

            startAuto();
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        initHero(document.getElementById('heroDesktop'));
        initHero(document.getElementById('heroMobile'));
    });
})();
</script>
